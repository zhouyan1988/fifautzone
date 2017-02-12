<?php
require_once(Mage::getBaseDir('lib') . '/Izberg/Html2Text/Html2Text.php');

class Sebfie_Izberg_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_izberg;

    protected $_shipping_izberg_address_id;

    protected $_billing_izberg_address_id;

    protected $_izberg_categories;

    protected $_izberg_current_cart;

    const IZBERG_ORDER_STATE = "izberg_order_created";

    const IZBERG_CART_STATUS_EMPTY = "0";
    const IZBERG_CART_STATUS_ACTIVE = "10";
    const IZBERG_CART_STATUS_VALID = "20";
    const IZBERG_CART_STATUS_DONE = "30";
    const IZBERG_CART_STATUS_SHIPPING_IMPOSSIBLE = "50";
    const IZBERG_CART_STATUS_NO_STOCK = "100";
    const IZBERG_CART_STATUS_CANCELED = "1000";


    protected $_token_to_update_with_quote;

    public static $IZBERG_GENDERS = array(
      "M" => "Man",
      "W" => "Woman",
      "U" => "Unisex",
      "B" => "Boy",
      "G" => "Girl",
      "K" => "Kids",
      "N" => "Non Applicable"
    );

    // Associative array(code: json)
    public static $IZBERG_COUNTRIES = array();

    public static $IZBERG_WEBHOOK_EVENTS = array("product_offer_updated", "merchant_order_confirmed", "merchant_order_sent", "merchant_order_received", "merchant_order_cancelled", "new_merchant_available");

    // We expect
    // $ssoData = array("quote_id" => 10)
    public function getIzberg($ssoData = array(), $force = false)
    {

      if ($this->_izberg === null || $force)
      {
        $sandbox = Mage::getStoreConfig('izberg/izberg_settings/izberg_sandbox', Mage::app()->getStore()) ? true : false;
        $settings_group = $this->getSettingsGroup();

        if ((!isset($ssoData["quote_id"]) && Mage::getSingleton('admin/session')->isLoggedIn()) || isset($ssoData["force_admin"]) ) {
          $accessToken = Mage::getStoreConfig("izberg/$settings_group/izberg_access_token", Mage::app()->getStore());
        } else if ($this->getAccessTokenObject($ssoData["quote_id"])->getId()) {
          $accessToken = $this->getAccessTokenObject($ssoData["quote_id"])->getAccessToken();
        } else {
          $accessToken = null;
        }

        if ((!isset($ssoData["quote_id"]) && Mage::getSingleton('admin/session')->isLoggedIn()) || isset($ssoData["force_admin"])) {
          $userName = Mage::getStoreConfig("izberg/$settings_group/izberg_username", Mage::app()->getStore());
        } else if ($this->getAccessTokenObject($ssoData["quote_id"])->getId()) {
          $userName = $this->getAccessTokenObject($ssoData["quote_id"])->getUsername();
        } else {
          $userName = null;
        }

        $this->_izberg = new Izberg\Izberg(array(
          "appNamespace" => Mage::getStoreConfig("izberg/$settings_group/izberg_app_namespace", Mage::app()->getStore()),
          "accessToken" => $accessToken,
          "username" => $userName,
          "anonymous" => ((Mage::getSingleton('admin/session')->isLoggedIn() && !isset($ssoData["quote_id"]) || isset($ssoData["force_admin"])) || $this->getAccessTokenObject($ssoData["quote_id"])->getUsername() != "Anonymous") ? false : true,
          "sandbox" => $sandbox
        ));

        // If we do not find an access token
        if (!$accessToken && !isset($ssoData["force_admin"]) && !$this->getAccessTokenObject($ssoData["quote_id"])->getId()) {
          $response = $this->_izberg->sso(array(
            "apiSecret" => Mage::getStoreConfig("izberg/$settings_group/izberg_api_secret_key", Mage::app()->getStore()),
            "apiKey" => Mage::getStoreConfig("izberg/$settings_group/izberg_api_key", Mage::app()->getStore())
          ));
          Mage::getSingleton('core/session')->setIzbergSessionType("anonymous");
          $this->saveAccessToken(array(
            "access_token" => $response->access_token,
            "username" => $response->username,
            "quote_id" => $ssoData["quote_id"]
          ));
        }

        if (Mage::getStoreConfig('izberg/izberg_settings/izberg_debug',Mage::app()->getStore())) {
          $this->_izberg->setDebug(true);
        }
      }
      return $this->_izberg;
    }

    public function getIzbergCart($izberg, $force = false)
    {
      if (!$this->_izberg_current_cart || $force) {
        $this->_izberg_current_cart = $izberg->get("cart");
      }
      return $this->_izberg_current_cart;
    }

    public function getSettingsGroup()
    {
      $sandbox = Mage::getStoreConfig('izberg/izberg_settings/izberg_sandbox', Mage::app()->getStore()) ? true : false;
      return $sandbox ? "sandbox_izberg_cedentials" : "production_izberg_cedentials";
    }

    public function manageMerchantsFromAPIResponse($response)
    {
      foreach ($response as $merchant) {
        // We load it from the database to update it in case it already exists
        // It will return a new instance or the existing one
        $m = Mage::getModel('izberg/merchant')->getCollection()->addFieldToFilter('izberg_merchant_id', $merchant->id)->getFirstItem();

        $m->setIzbergMerchantId($merchant->id);
        $m->setName($merchant->name);
        $m->setDescription($merchant->description);
        $m->setStatus($merchant->status);
        $m->setDefaultCurrency($merchant->default_currency);
        $m->setStoreUrl($merchant->url);

        // Manage images
        if (isset($merchant->cover_image)) $m->setCoverImageUrl($merchant->cover_image->image_path);
        if (isset($merchant->profile_image)) $m->setMerchantImageUrl($merchant->profile_image->image_path);
        if (isset($merchant->logo_image)) $m->setMerchantImageUrl($merchant->logo_image->image_path);

        $m->setRegion($merchant->region);
        if (!$m->getId()) $m->setCreatedAt(time());
        $m->setCreatedFromJson($merchant->__toString());
        $m->save();
      }
    }

    public function retreiveIzbergCategory($id)
    {
      try {
        if (!isset($this->_izberg_categories[(int) $id])) {
          $izberg = Mage::helper("izberg")->getIzberg();
          $this->_izberg_categories[(int) $id] = $izberg->get("category", $id);
        }
        return $this->_izberg_categories[(int) $id];
      } catch (Exception $e) {
        $emptyCat = new stdClass();
        $emptyCat->parent_ids_tree = array();
        return $emptyCat;
      }
    }


    public function saveCategory($category, $parent = null) {
      $m = Mage::getModel('izberg/category')->getCollection()->addFieldToFilter('izberg_category_id', $category->id)->getFirstItem();
      $m->setIzbergCategoryId($category->id);
      ($parent !== null) ? $m->setIzbergParentCategoryId($parent->getId()) : true;
      $m->setSlug($category->slug);
      $m->setName($category->name);
      $m->setDescription($category->description);
      $m->setType($category->type);
      $m->setSortOrder($category->sort_order);
      $m->setCreatedAt(time());
      $m->setCreatedFromJson(json_encode((array)$category));
      $m->save();

      if (count($category->children) > 0) {
        foreach($category->children as $category) {
          $this->saveCategory($category, $m);
        }
      }
    }

    public function uploadImage($url, $path)
    {
      try {
        //add time to the current filename
        $izberg_name = basename($url);
        list($txt, $ext) = explode(".", $izberg_name);
        $name = $txt.time();
        $name = md5($name) .".".$ext;

        $maxTries = 3;
        $upload = false;
        for ($try=1; $try<=$maxTries; $try++) {
            if (!file_exists("$path/$izberg_name")) {
              $upload = file_put_contents("$path/$izberg_name",$this->get_url_contents($url));
              if ($upload) {
                  break;
              }
            } else {
              $upload = true;
              break;
            }
        }
        copy("$path/$izberg_name", "$path/$name");

        if (!$upload) Mage::throwException("Upload did not worked for image: $url");

        return $name;
      } catch (Exception $e) {
        Mage::helper("izberg")->log("We got an error trying to get picture from url: $url with exception" . $e->getMessage(), 1);
      }
    }

    public function deleteDirectory($dir) {
      if (!file_exists($dir)) {
          return true;
      }

      if (!is_dir($dir)) {
          return unlink($dir);
      }

      foreach (scandir($dir) as $item) {
          if ($item == '.' || $item == '..') {
              continue;
          }

          if (!$this->deleteDirectory($dir . DS . $item)) {
              return false;
          }

      }

      return rmdir($dir);
   }


    public function get_url_contents($url){
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_REFERER, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

      // curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1");
      // curl_setopt($ch, CURLOPT_PROXYPORT, 8888);

      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
    }

    public function startsWith($haystack, $needle)
    {
      return strncmp($haystack, $needle, strlen($needle)) === 0;
    }

    public function endsWith($haystack, $needle)
    {
      return $needle === '' || substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }


    public function manageCategoriesFromAPIResponse($response)
    {
      foreach($response as $category) {
        $this->saveCategory($category);
      }
    }

    // const EMERG   = 0;  // Emergency: system is unusable
    // const ALERT   = 1;  // Alert: action must be taken immediately
    // const CRIT    = 2;  // Critical: critical conditions
    // const ERR     = 3;  // Error: error conditions
    // const WARN    = 4;  // Warning: warning conditions
    // const NOTICE  = 5;  // Notice: normal but significant condition
    // const INFO    = 6;  // Informational: informational messages
    // const DEBUG   = 7;  // Debug: debug messages
    // We want to log on production
    public function log($message, $level = 7, $extra = "", $scope = null, $entity_id = null) {
      Mage::getModel("izberg/log")
        ->setCreatedAt(time())
        ->setMessage($message)
        ->setLevel($level)
        ->setExtraMessage($extra)
        ->setEntityId($entity_id)
        ->setScope($scope)
        ->save();
    }

    // (string) $message - message to be passed to Slack
    // (string) $room - room in which to write the message, too
    // (string) $icon - You can set up custom emoji icons to use with each message
    public static function slack($message, $room = "magento_errors") {
      $room = ($room) ? $room : "magento_errors";

      $sandbox = Mage::getStoreConfig('izberg/izberg_settings/izberg_sandbox', Mage::app()->getStore()) ? true : false;
      $settings_group = $sandbox ? "sandbox_izberg_cedentials" : "production_izberg_cedentials";

      // Extra data to know which site installed it!
      $extra_data = array(
        "Domain : " . Mage::getBaseUrl() . ' ',
        "Mode : " . $settings_group,
        "Magento version : " . Mage::getVersion(),
        "Module version : " . self::getExtensionVersion(),
        "Debug : " . Mage::getStoreConfig("izberg/izberg_settings/izberg_debug", Mage::app()->getStore()),
        "Description html : " . Mage::getStoreConfig("izberg/izberg_product_settings/izberg_description_html", Mage::app()->getStore()),
        "Attribute set name : " . Mage::helper("izberg")->getDefaultProductAttributeSet()->getAttributeSetName(),
        "Is installed : " . Mage::helper("izberg")->isInstalled(),
        "Current store name : " . Mage::app()->getStore()->getName(),
        "Izberg namespace : " . Mage::getStoreConfig("izberg/$settings_group/izberg_app_namespace", Mage::app()->getStore()),
        "Access token : " . substr_replace(Mage::getStoreConfig("izberg/$settings_group/izberg_access_token", Mage::app()->getStore()), "********a", 0, 8),
        "Username : " . Mage::getStoreConfig("izberg/$settings_group/izberg_username", Mage::app()->getStore()),
        "Izberg api key : " . Mage::getStoreConfig("izberg/$settings_group/izberg_api_key", Mage::app()->getStore()),
        "Izberg api secret : " . substr_replace(Mage::getStoreConfig("izberg/$settings_group/izberg_api_secret_key", Mage::app()->getStore()), "********a", 0, 8),
        "Trace :" . json_encode(debug_backtrace())
      );

      $data = "payload=" . json_encode(array(
              "channel"       =>  "#{$room}",
              "text"          =>  "*" . $message . "* \n ```" . implode(", \n", $extra_data) . "```"
          ));

      // You can get your webhook endpoint from your Slack settings
      $ch = curl_init("https://izberg-mp.slack.com/services/hooks/incoming-webhook?token=kK5v4YEBjcbMOB3ZUMz8jdhB");
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $result = curl_exec($ch);
      curl_close($ch);

      // Laravel-specific log writing method
      // Log::info("Sent to Slack: " . $message, array('context' => 'Notifications'));

      return $result;
    }

    public static function getExtensionVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Sebfie_Izberg->version;
    }

    public function setIzbergBillingAddress($billingAddress)
    {
      // We call this method once by thread
      if ($this->_billing_izberg_address_id) return;

      $izberg = $this->getIzberg(array("quote_id" => $billingAddress->getQuote()->getId()));
      $cart = $this->getIzbergCart($izberg);
      if (isset(self::$IZBERG_COUNTRIES[$billingAddress->getCountry()])) {
        $countryBilling = self::$IZBERG_COUNTRIES[$billingAddress->getCountry()];
      } else {
        $countryBilling = $izberg->get_list("country", array("code" => $billingAddress->getCountry()))[0];
        self::$IZBERG_COUNTRIES[$billingAddress->getCountry()] = $countryBilling;
      }
      $izbergBillingAddressId = $this->retreiveAddressFromIzberg($billingAddress->getId());

      if (!$izbergBillingAddressId) {
        $addresses = $billingAddress->getStreet();

        $izbergBillingAddress = $izberg->create("address", array(
            "address" => $addresses[0],
            "address2" => isset($addresses[1]) ? $addresses[1] : null,
            "city" => $billingAddress->getCity(),
            "company" => $billingAddress->getCompany(),
            "country" => "/v1/country/" . $countryBilling->id . "/",
            "default_billing" => false,
            "default_shipping" => false,
            "digicode" => null,
            "first_name" => $billingAddress->getFirstname(),
            "floor" => null,
            "last_name" => $billingAddress->getLastname(),
            "name" => "magento_address_" . $billingAddress->getId(),
            "phone" => $billingAddress->getTelephone(),
            "state" => null,
            "status" => 10,
            "zipcode" => $billingAddress->getPostcode(),
            "external_id" => $billingAddress->getId()
        ));
        $izbergBillingAddressId = $izbergBillingAddress->id;
      }
      if (!isset($cart->billing_address) || (!$cart->billing_address->id && $izbergBillingAddressId && $cart->billing_address->id != $izbergBillingAddressId)) {
        $cart->setBillingAddress($izbergBillingAddressId);
        $this->getIzbergCart($izberg, true);
      }
      $this->_billing_izberg_address_id = $billingAddress->getId();
    }

    public function setIzbergShippingAddress($shippingAddress)
    {
      // We call this method once by thread
      if ($this->_shipping_izberg_address_id) return;

      $izberg = $this->getIzberg(array("quote_id" => $shippingAddress->getQuote()->getId()));
      $cart = $this->getIzbergCart($izberg);

      // Get the country country
      if (isset(self::$IZBERG_COUNTRIES[$shippingAddress->getCountry()])) {
        $countryShipping = self::$IZBERG_COUNTRIES[$shippingAddress->getCountry()];
      } else {
        $countryShipping = $izberg->get_list("country", array("code" => $shippingAddress->getCountry()))[0];
        self::$IZBERG_COUNTRIES[$shippingAddress->getCountry()] = $countryShipping;
      }
      $izbergShippingAddressId = $this->retreiveAddressFromIzberg($shippingAddress->getId());

      if (!$izbergShippingAddressId) {
        $addresses = $shippingAddress->getStreet();

        $izbergShippingAddress = $izberg->create("address", array(
            "address" => $addresses[0],
            "address2" => isset($addresses[1]) ? $addresses[1] : null,
            "city" => $shippingAddress->getCity(),
            "company" => $shippingAddress->getCompany(),
            "country" => "/v1/country/" . $countryShipping->id . "/",
            "default_billing" => false,
            "default_shipping" => false,
            "digicode" => null,
            "first_name" => $shippingAddress->getFirstname(),
            "floor" => null,
            "last_name" => $shippingAddress->getLastname(),
            "name" => "magento_address_" . $shippingAddress->getId(),
            "phone" => $shippingAddress->getTelephone(),
            "state" => null,
            "status" => 10,
            "zipcode" => $shippingAddress->getPostcode(),
            "external_id" => $shippingAddress->getId()
        ));
        $izbergShippingAddressId = $izbergShippingAddress->id;
      }

      if (!isset($cart->shipping_address) || (!$cart->shipping_address->id && $izbergShippingAddressId && $cart->shipping_address->id != $izbergShippingAddressId)) {
        $cart->setShippingAddress($izbergShippingAddressId);
        $this->getIzbergCart($izberg, true);
      }
      $this->_shipping_izberg_address_id = $izbergShippingAddressId;
    }

    public function createIzbergOrder($order, $quote)
    {
        try {
          $izberg = Mage::helper("izberg")->getIzberg(array("quote_id" => $order->getQuoteId()));
          $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());

          $ssoData = array(
            "email" => $order->getCustomerEmail(),
            "firstName" => $order->getFirstname(),
            "lastName" => $order->getCustomerLastname(),
            "quote_id" => $order->getQuoteId(),
          );

          Mage::helper("izberg")->log("We use sso for customer id: " .  $order->getCustomerId());
          Mage::helper("izberg")->log("We use sso with data: " .  json_encode($ssoData));

          $this->sso($ssoData);
          Mage::getSingleton('core/session')->setIzbergSessionType("loggedin");
          $izberg = Mage::helper("izberg")->getIzberg(array("quote_id" => $quote->getId()));

          // We get the current cart
          $izbergCart = $izberg->get("cart");
          $izbergCartItems = $izbergCart->getItems();

          // All products of the orders are izberg products
          if ($izbergCart->id && count($order->getAllVisibleItems()) == count($izbergCartItems) && (float) $izbergCart->total_amount == (float) $order->getBaseGrandTotal() ) {
            Mage::helper("izberg")->log("The izberg cart is good : " . $izbergCart->id . " " . count($order->getAllVisibleItems()) . " " . count($izbergCartItems) . " " . (float) $izbergCart->total_amount . " " . $order->getBaseGrandTotal());
            // We do nothing, the cart is good
          } else {
            Mage::helper("izberg")->log("The izberg cart is not good : " . $izbergCart->id . " " . count($order->getAllVisibleItems()) . " " . count($izbergCartItems) . " " . (float) $izbergCart->total_amount . " " . $order->getBaseGrandTotal());
            // We create a new cart
            $izberg_cart = $izberg->create("cart");
            foreach($order->getAllVisibleItems() as $item) {
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $item->getSku());
                if (Sebfie_Izberg_Model_Product::isCreatedFromIzberg($product)) {
                  $izbergCatalogProduct = Mage::getModel("izberg/catalog_product")->getCollection()->addFieldToFilter("catalog_product_sku" , $product->getSku())->getFirstItem();
                  $izbergProduct = Mage::getModel("izberg/product")->load($izbergCatalogProduct->getIzbergProductId());

                  Mage::helper("izberg")->log("We add item to izberg cart with offer_id: " . $izbergCatalogProduct->getOfferId() . " and variation_id " . $izbergCatalogProduct->getVariationId());

                  $izberg_cart->addItem(array(
                      "offer_id" => $izbergCatalogProduct->getOfferId(),
                      "variation_id" => $izbergCatalogProduct->getVariationId(),
                      "quantity" => (int) $item->getQtyOrdered()
                  ));
                }
            }
          }

          $billingAddress = $order->getBillingAddress()->setQuote($quote);
          $shippingAddress = $order->getShippingAddress()->setQuote($quote);

          $this->setIzbergBillingAddress($billingAddress);

          if ($billingAddress->getId() == $shippingAddress->getId() && !is_null($shippingAddress->getId())) {
            $izberg->setShippingAddress($this->_billing_izberg_address_id);
            $this->_shipping_izberg_address_id = $this->_billing_izberg_address_id;
          } else {
            $this->setIzbergShippingAddress($shippingAddress);
          }

          $izberg_order = $izbergCart->createOrder(array("external_id" => $order->getId()));
          $izberg_order->updateStatus('authorizeOrder');

          // Mage::helper("izberg")->slack("Izberg order created from cart: " . json_encode((array)$izberg->getCart()) );
          // Mage::helper("izberg")->slack("Izberg order created: " . json_encode((array)$order) );

          $order->addStatusToHistory(Mage::helper("izberg")->getDefaultOrderStatus($order->getStatus()), Mage::helper("izberg")->__('This order has been created by our system. It will be managed by izberg merchants. The created izberg order id is: ') . $izberg_order->id , false);

          // We save created order
          $order->setIsIzbergExternalOrder(true);
          $order->setIzbergExternalOrderId($izberg_order->id);
          $order->save();

          return $izberg_order;

        } catch (Exception $e) {
          Mage::helper("izberg")->slack("Crashed during order creation on izberg with error: " . $e->getMessage());
          Mage::helper("izberg")->log("Crashed during order creation on izberg with error: " . $e->getMessage(), 2);
          Mage::throwException($e->getMessage());
        }
    }

    public function retreiveAddressFromIzberg($external_id)
    {
      $izberg = Mage::helper("izberg")->getIzberg();
      $izbergAddressesIdsFromMagentoAddressIds = Mage::getSingleton('customer/session')->getIzbergAddressesIdFromMagentoAddressIds() ? Mage::getSingleton('customer/session')->getIzbergAddressesIdFromMagentoAddressIds() : array();
      if (isset($izbergAddressesIdsFromMagentoAddressIds[(int) $external_id])) {
        return $izbergAddressesIdsFromMagentoAddressIds[(int) $external_id];
      } else {
        $response = $izberg->get_list("address", array(
          "external_id" => $external_id
        ));
        if (count($response) > 0) {
          $izbergAddressesIdsFromMagentoAddressIds[$external_id] = $response[0]->id;
          Mage::getSingleton('customer/session')->setIzbergAddressesIdFromMagentoAddressIds($izbergAddressesIdsFromMagentoAddressIds);
          return $response[0]->id;
        }
        return false;
      }
    }

    public function sendCancelOrderEmail($order)
    {
      $emailTemplate  = Mage::getModel('core/email_template')->loadDefault('izberg_merchant_order_cancelled');

      //Create an array of variables to assign to template
      $emailTemplateVariables = array();
      $emailTemplateVariables['order_cremental'] = $order->getIncrementId();
      $emailTemplateVariables['order_url'] = Mage::helper("adminhtml")->getUrl("adminhtml/sales_order/view", array( "order_id" => $order->getId()));

      $processedTemplate = $emailTemplate->getProcessedTemplate($emailTemplateVariables);

      $to_email = Mage::getStoreConfig('trans_email/ident_sales/email'); //fetch sender email Admin
      $to_name = Mage::getStoreConfig('trans_email/ident_sales/name'); //fetch sender name Admin
      $emailTemplate->setSenderName($to_name);
      $emailTemplate->setSenderEmail($to_email);

      return $emailTemplate->send($to_email, $to_name, $emailTemplateVariables);
    }

    public function getDefaultProductAttributeSet($attribute_set_id = null)
    {
      $attribute_set_id_from_izberg_options = $attribute_set_id;
      if (is_null($attribute_set_id_from_izberg_options)) {
        $attribute_set_id_from_izberg_options = Mage::getStoreConfig("izberg/izberg_product_settings/izberg_attribute_set", Mage::app()->getStore()) ? Mage::getStoreConfig("izberg/izberg_product_settings/izberg_attribute_set", Mage::app()->getStore()) : Mage::getModel('catalog/product')->getDefaultAttributeSetId();
      }
      $entityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();


      return Mage::getModel("eav/entity_attribute_set")
                ->getResourceCollection()
                ->addFilter('entity_type_id', $entityTypeId)
                ->addFilter('attribute_set_id', $attribute_set_id_from_izberg_options)
                ->getFirstItem();
    }

    public function isInstalled()
    {
      $sandbox = Mage::getStoreConfig('izberg/izberg_settings/izberg_sandbox', Mage::app()->getStore()) ? true : false;
      $settings_group = $sandbox ? "sandbox_izberg_cedentials" : "production_izberg_cedentials";
      $namespace = Mage::getStoreConfig("izberg/$settings_group/izberg_app_namespace", Mage::app()->getStore());
      $izberg_access_token = Mage::getStoreConfig("izberg/$settings_group/izberg_access_token", Mage::app()->getStore());
      $izberg_username = Mage::getStoreConfig("izberg/$settings_group/izberg_username", Mage::app()->getStore());
      $izberg_api_key = Mage::getStoreConfig("izberg/$settings_group/izberg_api_key", Mage::app()->getStore());
      $izberg_api_secret_key = Mage::getStoreConfig("izberg/$settings_group/izberg_api_secret_key", Mage::app()->getStore());

      if (!empty($namespace) && !empty($izberg_access_token) && !empty($izberg_username) && !empty($izberg_api_key) && !empty($izberg_api_secret_key)) {
        return true;
      }
      return false;
    }

    public function toText($text)
    {
      // If we want html
      if (Mage::getStoreConfig('izberg/izberg_product_settings/izberg_description_html', Mage::app()->getStore())) {
        return $text;
      } else {
        $html = new \Html2Text\Html2Text($text);
        return $html->getText();
      }
    }

    public function getWebhook($webhooks, $url, $event){
      $result = null;
      foreach ($webhooks as $webhook) {
        if (($webhook->url == $url) && ($webhook->event == $event)) {
          $result = $webhook;
        }
      }
      return $result;
    }

    public function getOrderLogs($order_id)
    {
      $izberg_logs =  Mage::getModel("izberg/log")->getCollection()
                  ->addFieldToFilter("scope", array("order",))
                  ->addFieldToFilter("entity_id", $order_id);
      $izberg_logs->getSelect()->order('log_id ASC');

      return $izberg_logs;
    }

    // Return the cart_item if found, null otherwise
    public function izbergProductInIzbergCart($items, $izberg_product_id)
    {
      $result = null;
      foreach($items as $item) {
        if ($item->product->id == $izberg_product_id) {
          $result = $item;
        }
      }
      return $result;
    }

    public function sso($params)
    {
      $izberg = $this->getIzberg(array("quote_id" => $params["quote_id"]));

      // We return if we already did the sso
      $accessTokenObj = $this->getAccessTokenObject($params["quote_id"]);

      if ($accessTokenObj->getId() && ($accessTokenObj->getUserEmail() == $params["email"]) && $izberg->getUsername() == $accessTokenObj->getUsername()) return true;

      $settings_group = $this->getSettingsGroup();

      $response = $izberg->sso(array(
          "apiKey" => Mage::getStoreConfig("izberg/$settings_group/izberg_api_key", Mage::app()->getStore()),
          "apiSecret" => Mage::getStoreConfig("izberg/$settings_group/izberg_api_secret_key", Mage::app()->getStore()),
          "email" => $params["email"],
          "firstName" => $params["firstName"],
          "lastName" => $params["lastName"],
          "appNamespace" => Mage::getStoreConfig("izberg/$settings_group/izberg_app_namespace", Mage::app()->getStore()),
          "from_session_id" => $this->getAccessTokenObject($params["quote_id"])->getAccessToken() ? $this->getAccessTokenObject($params["quote_id"])->getAccessToken() : null,
          "currency" => Mage::app()->getStore()->getCurrentCurrencyCode()
      ));

      Mage::getSingleton('core/session')->setIzbergAccessToken($response->access_token);
      Mage::getSingleton('core/session')->setIzbergUsername($response->username);

      $this->saveAccessToken(array(
        "email" => isset($response->email) ? $response->email : "",
        "access_token" => $response->access_token,
        "username" => $response->username,
        "quote_id" => $params["quote_id"]
      ));
    }

    public function getTaxeGroupId()
    {
      $optionValue = Mage::getStoreConfig("izberg/izberg_product_settings/izberg_product_tax_group", Mage::app()->getStore());
      if (!$optionValue || $optionValue == "0" || $optionValue == 0) {
        return null;
      }
      return (int) $optionValue;
    }

    public function isDevelopment()
    {
      return Mage::getStoreConfig("izberg/izberg_settings/izberg_development", Mage::app()->getStore());
    }

    /**
     * Save with a queued retry upon deadlock, set isolation level
     * @param  stdClass $obj object must have a pre-defined save() method
     * @return n/a
     */
    public function saferSave(&$obj)
    {

        // Deadlock Workaround
        $adapter = Mage::getModel('core/resource')->getConnection('core_write');
        // Commit any existing transactions (use with caution!)
        if ($adapter->getTransactionLevel() > 0) {
            $adapter->commit();
        }
        $adapter->query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');

        //begin a retry loop that will recycle should a deadlock pop up
        $tries = 0;
            do {
                $retry = false;
                try {
                    $obj->save();
                } catch (Exception $e) {
                    if ($tries < 10 and $e->getMessage()=='SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction') {
                        $retry = true;
                    } else {
                        //we tried at least 10 times, go ahead and throw exception
                        throw new Zend_Db_Statement_Exception($e->getMessage() . " for object " . get_class($obj) . " with id : " . $obj->getId());
                    }
                    sleep($this->getDelay($tries));
                    $tries++;
                }
            } while ($retry);

        //free resources
        unset($adapter);
        return;
    }

    public function getDelay($tries){
        return (int) pow(2, $tries);
    }

    public function getDefaultOrderStatus($default = Mage_Sales_Model_Order::STATE_PROCESSING)
    {
      $status = Mage::getStoreConfig("izberg/izberg_order_settings/izberg_default_order_state", Mage::app()->getStore());
      return $status ? $status : $default;
    }

    public function saveAccessToken($data)
    {
      $sandbox = Mage::getStoreConfig('izberg/izberg_settings/izberg_sandbox', Mage::app()->getStore()) ? true : false;
      $settings_group = $sandbox ? "sandbox_izberg_cedentials" : "production_izberg_cedentials";
      $namespace = Mage::getStoreConfig("izberg/$settings_group/izberg_app_namespace", Mage::app()->getStore());

      $izbergAccessToken = $this->getAccessTokenObject($data["quote_id"]);
      if (isset($data["email"])) $izbergAccessToken->setUserEmail($data["email"]);
      if (isset($data["username"])) $izbergAccessToken->setUsername($data["username"]);
      $izbergAccessToken->setAccessToken($data["access_token"]);
      $izbergAccessToken->setQuoteId($data["quote_id"]);
      $izbergAccessToken->setAppNamespace($namespace);
      $izbergAccessToken->setEnvironment($sandbox ? "sandbox" : "production");
      $izbergAccessToken->setExpireAt((time() + 30 * 60));
      $izbergAccessToken->setCreatedAt(time());
      $izbergAccessToken->setUpdatedAt(time());
      $izbergAccessToken->save();

      // If we specify a quote_id as null, the next quote saved in this thread will be used. I think it's pertinent
      // This case should happen only on quote creation (first product add)
      if (is_null($data["quote_id"])) $this->_token_to_update_with_quote = $izbergAccessToken->getId();
    }

    public function updateTokenIfNeeded($quote_id)
    {
      if ($this->_token_to_update_with_quote)
      {
        $izbergAccessToken = Mage::getModel("izberg/accesstoken")->load($this->_token_to_update_with_quote)
                                                                  ->setQuoteId($quote_id)
                                                                  ->save();
        $this->_token_to_update_with_quote = null;
      }
    }

    public function getAccessTokenObject($quote_id) {
      return Mage::getModel("izberg/accesstoken")->getCollection()->addFieldToFilter("quote_id", $quote_id)->getFirstItem();
    }

    public function retreiveCartItemFromOfferId($izberg, $offer_id, $variation_id)
    {
      $items = $this->getIzbergCart($izberg)->getItems();
      foreach($items as $item) {
          if ($item->offer->id == $offer_id) {
            if (is_null($variation_id)) return $item;
            if ($variation_id && isset($item->product_offer_variation->id) && $item->product_offer_variation->id == $variation_id) {
              return $item;
            }
          }
      }
    }

    public function disableProductsWithoutStock()
    {
      return Mage::getStoreConfig("izberg/izberg_product_settings/izberg_action_if_no_stock", Mage::app()->getStore()) == "disable";
    }

    public function skipProductsWithoutStock()
    {
      return Mage::getStoreConfig("izberg/izberg_product_settings/izberg_action_if_no_stock", Mage::app()->getStore()) == "skip";
    }

    public function importProductsWithoutStock()
    {
      return Mage::getStoreConfig("izberg/izberg_product_settings/izberg_action_if_no_stock", Mage::app()->getStore()) == "" || is_null(Mage::getStoreConfig("izberg/izberg_product_settings/izberg_action_if_no_stock", Mage::app()->getStore()));
    }

    public function appendToParentCategories()
    {
      return Mage::getStoreConfig("izberg/izberg_product_settings/izberg_add_to_magento_parent_categories_if_match", Mage::app()->getStore());
    }

}

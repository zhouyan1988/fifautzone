<?php
class Sebfie_Izberg_Model_Product extends Mage_Core_Model_Abstract
{

    public static $CONFIGURABLE_ATTRIBUTES = array("size", "color");

    protected $_merchant;

    public $_images_to_delete;

    public function _construct()
    {
        parent::_construct();
        $this->_init('izberg/product');
    }

    protected function _beforeSave()
    {
      $this->setUpdatedAt(time());
      return $this;
    }

    public function getMerchant()
    {
      if ($this->_merchant === null) {
        $this->_merchant = Mage::getModel("izberg/merchant")->load($this->getIzbergMerchantId());
      }
      return $this->_merchant;
    }

    public static function getStatusAsArray()
    {
      return array(
        "inactive"  => Mage::helper('izberg')->__("Inactive"),
        "active"  => Mage::helper('izberg')->__("Active"),
        "draft"  => Mage::helper('izberg')->__("Draft"),
        "deleted"  => Mage::helper('izberg')->__("Deleted")
      );
    }

    public static function getActionsIfNoStock()
    {
      return array(
        "skip"  => Mage::helper('izberg')->__("No import"),
        "disable"  => Mage::helper('izberg')->__("Disable")
      );
    }

    public function getCatalogProducts()
    {
      $result = array();
      foreach (Mage::getModel("izberg/catalog_product")->getCollection()->addFieldToFilter('izberg_product_id', $this->getId()) as $izberg_catalog_product)
      {
        array_push($result, $izberg_catalog_product->getCatalogProduct());
      }
      return compact($result);
    }

    public function getLogs($catalog_product_id = null)
    {
      $izberg_logs =  Mage::getModel("izberg/log")->getCollection()
                  ->addFieldToFilter("scope", array("product",))
                  ->addFieldToFilter("entity_id", $this->getId());

      $izberg_magmi_logs =  Mage::getModel("izberg/magmi_log")->getCollection()
                  ->addFieldToFilter("scope", "product")
                  ->addFieldToFilter("entity_id", $this->getId());

      $izberg_magmi_catalog_product_logs = array();
      if ($catalog_product_id) {
      $izberg_magmi_catalog_product_logs =  Mage::getModel("izberg/magmi_log")->getCollection()
                  ->addFieldToFilter("scope", "catalog_product")
                  ->addFieldToFilter("entity_id", $catalog_product_id);
      }
      $result = array();
      foreach($izberg_logs as $item) {
        array_push($result, $item);
      }
      foreach($izberg_magmi_logs as $item) {
        array_push($result, $item);
      }
      foreach($izberg_magmi_catalog_product_logs as $item) {
        array_push($result, $item);
      }

      usort($result, function($a1, $a2) {
         $v1 = strtotime($a1->getCreatedAt());
         $v2 = strtotime($a2->getCreatedAt());
         return $v2 - $v1;
      });
      return $result;
    }

    public function getFirstImage()
    {
      return $this->getIzbergResponse()->best_offer->images->image->url;
    }

    public function disable()
    {
      $this->setEnabledForImport(false);
      foreach($this->getCatalogProducts() as $product) {
          Mage::getModel('catalog/product_status')->updateProductStatus($product->getId(), Mage::app()->getStore()->getId(), Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
          $product->save();
      }
      $this->save();
      return $this;
    }

    public function enable()
    {
      $this->setEnabledForImport(true);
      $this->importInMagentoDb();
      return $this;
    }

    public function getIzbergResponse()
    {
      return simplexml_load_string($this->getCreatedFromJson());
    }

    // Reminder :
    // Only attributes with scope "Global", input type "Dropdown" and Use To Create Configurable Product "Yes" are available.
    public static function createConfigurableProductAttributes($attribute_set_id)
    {
      $set = Mage::helper('izberg')->getDefaultProductAttributeSet($attribute_set_id);
      // We have to create the size attribute and the color attribute if it does not exist
      foreach(self::$CONFIGURABLE_ATTRIBUTES as $attr) {
        $attribute = Sebfie_Izberg_Model_Attribute::getMagentoAttributeConfigFromIzbergAttributeCode($attr);
        $attribute->setIdGlobal(Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL);
        $attribute->setIsConfigurable(true);
        $attribute->setFrontendInput("select");
        $attribute->setFrontendLabel(ucfirst($attr));
        $attribute->setIsSearchable(true);
        $attribute->setIsComparable(true);
        $attribute->setIsVisibleInAdvancedSearch(true);
        $attribute->setIsUserDefined(true);
        $attribute->setBackendType('int');
        $attribute->setSourceModel('eav/entity_attribute_source_table');
        $attribute->setApplyTo(array(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE));
        $attribute->setIsFilterable(1); // See app/code/core/Mage/Adminhtml/Block/Catalog/Product/Attribute/Grid.php for available values
        $attribute->save();

        $attSet = Mage::getModel('eav/entity_type')->getCollection()->addFieldToFilter('entity_type_code','catalog_product')->getFirstItem();

        $group_id = $set->getDefaultGroupId();
        $result = Mage::getModel('catalog/product_attribute_api')->items($attSet->getId());

        $entityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();

        try {
          $newItem = Mage::getModel('eav/entity_attribute');
          $newItem->setEntityTypeId($entityTypeId) // catalog_product eav_entity_type id ( usually 10 )
                    ->setAttributeSetId($set->getId()) // Attribute Set ID
                    ->setAttributeGroupId($group_id) // Attribute Group ID ( usually general or whatever based on the query i automate to get the first attribute group in each attribute set )
                    ->setAttributeId($attribute->getId()) // Attribute ID that need to be added manually
                    ->setSortOrder(10) // Sort Order for the attribute in the tab form edit
                    ->save();
        } catch (Exception $e) {

        }
      }
    }

    public static function createFreeShippingAttribute($attribute_set_id)
    {
      $set = Mage::helper('izberg')->getDefaultProductAttributeSet($attribute_set_id);

      // We have to create the size attribute and the color attribute if it does not exist
      $attribute = Sebfie_Izberg_Model_Attribute::getMagentoAttributeConfigFromIzbergAttributeCode("free_izberg_shipping");
      $attribute->setIdGlobal(Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL);
      $attribute->setIsConfigurable(false);
      $attribute->setFrontendInput("boolean");
      $attribute->setFrontendLabel(" Free izberg shipping");
      $attribute->setIsSearchable(false);
      $attribute->setIsComparable(false);
      $attribute->setIsVisibleInAdvancedSearch(false);
      $attribute->setIsUserDefined(false);
      $attribute->setBackendType('int');
      $attribute->setSourceModel('eav/entity_attribute_source_boolean');
      $attribute->setApplyTo(array(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, Mage_Catalog_Model_Product_Type::TYPE_GROUPED, Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL, Mage_Catalog_Model_Product_Type::TYPE_BUNDLE));
      $attribute->setIsFilterable(1);
      $attribute->setDefault(0);
      $attribute->setIsUsedForPromoRules(true);
      $attribute->save();

      $attSet = Mage::getModel('eav/entity_type')->getCollection()->addFieldToFilter('entity_type_code','catalog_product')->getFirstItem();

      $group_id = $set->getDefaultGroupId();

      try {
        $newItem = Mage::getModel('eav/entity_attribute');
        $newItem->setEntityTypeId($attSet->getId()) // catalog_product eav_entity_type id ( usually 10 )
                  ->setAttributeSetId($set->getId()) // Attribute Set ID
                  ->setAttributeGroupId($group_id) // Attribute Group ID ( usually general or whatever based on the query i automate to get the first attribute group in each attribute set )
                  ->setAttributeId($attribute->getId()) // Attribute ID that need to be added manually
                  ->setSortOrder(10) // Sort Order for the attribute in the tab form edit
                  ->save();
        } catch (Exception $e) {

        }

    }

    public static function createFreeShippingCartRule()
    {
      $rule = Mage::getModel('salesrule/rule')->getCollection()->addFieldToFilter('name', "Free shipping for izberg - DO NOT EDIT")->getFirstItem();
      $data = array(
        "name" => "Free shipping for izberg - DO NOT EDIT",
        "description" => "We have to set izberg products with free shipping because we use the izberg API to add shipping cost to the current magento cart.",
        "is_active" => true,
        "website_ids" => self::getWebsitesIds(),
        "customer_group_ids" => self::getCustomerGroupIds(),
        "simple_free_shipping" => true,
        "coupon_type" => 1,
        "conditions" => array(
            "1"         => array(
                    "type"          => "salesrule/rule_condition_combine",
                    "aggregator"    => "all",
                    "value"         => "1",
                    "new_child"     => null
                )
        ),
        "actions" => array(
            "1"    => array(
                    "type"          => "salesrule/rule_condition_product_combine",
                    "aggregator"    => "all",
                    "value"         => "1",
                    "new_child"     => false
                ),
            "1--1" => array(
                    "type"          => "salesrule/rule_condition_product",
                    "attribute"     => "free_izberg_shipping",
                    "operator"      => "==",
                    "value"         => 1
                )
        )
      );

      $rule->loadPost($data);
      $rule->save();
    }

    public function hasProductVariation()
    {
      return count($this->getVariations()) > 0;
    }

    public function getVariations()
    {
      $xmlResponse = $this->getIzbergResponse();
      return $xmlResponse->best_offer->variations->children();
    }

    public static function getWebsitesIds()
    {
      $result = array();
      $websites = Mage::app()->getWebsites();
      foreach($websites as $website) {
        array_push($result, $website->getId());
      }
      return $result;
    }

    public static function getWebsitesCodes()
    {
      $result = array();
      $websites = Mage::app()->getWebsites();
      foreach($websites as $website) {
        array_push($result, $website->getCode());
      }
      return $result;
    }

    public static function getCustomerGroupIds()
    {
      $result = array();
      $allGroups = Mage::getModel('customer/group')->getCollection();
      foreach($allGroups as $group){
         array_push($result, $group->getId());
      }
      return $result;
    }

    public function addCatalogProduct($sku, $offer_id = null, $variation_id = null)
    {
      // It will create or update list of magento products created
      Mage::getModel("izberg/catalog_product")->getCollection()
        ->addFieldToFilter("izberg_product_id", $this->getId())
        ->addFieldToFilter("catalog_product_sku", $sku)
        ->getFirstItem()
        ->setIzbergProductId($this->getId())
        ->setCatalogProductSku($sku)
        ->setOfferId($offer_id)
        ->setVariationId($variation_id)
        ->save();
    }

    public function prepareForImportInMagentoDb()
    {
      // Do not import if the product is marked as "disable" for import
      if (!$this->getEnabledForImport()) {
        return false;
      }

      $xmlResponse = $this->getIzbergResponse();
      try {
        if ($xmlResponse) {
          if ($this->hasProductVariation()) {
            return $this->createConfigurableProduct();
          } else {
            return $this->createProduct();
          }
        } else {
          Mage::helper("izberg")->log("We did not have any XML for product with id " . $this->getId(), 4, "", "product", $this->getId());
          return null;
        }
        $this->save();
      } catch (Exception $e) {
        Mage::throwException("Crashed on izberg product (prepareForImportInMagentoDb) " . $this->getId() . " (" . $this->getIzbergProductId() . ") with error message: " . $e->getMessage() . ". Please report this error message to izberg.");
      }
    }

    // Spec :
    // A izberg product can have variations using two criterias : color and size
    public function importInMagentoDb()
    {
      // Do not import if the product is marked as "disable" for import
      if (!$this->getEnabledForImport()) {
        return false;
      }
      try {
        $product_data = $this->prepareForImportInMagentoDb();
        if ($product_data) {

          $to_import = Mage::getModel("izberg/import")->addData(array(
            "to_import" => serialize($product_data),
            "count" => count($product_data["product"])
          ))->save();

          return true;
        }
        return false;
      } catch (Exception $e) {
        Mage::throwException("Crashed on izberg product " . $this->getId() . "(" . $this->getIzbergProductId() . ") with error message: " . $e->getMessage() . ". Please report this error message to izberg.");
      }
    }


    public function getSets()
    {
      $entityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();

      return Mage::getModel("eav/entity_attribute_set")
                ->getResourceCollection()
                ->addFilter('entity_type_id', $entityTypeId);
    }

    public function completeProductAttributesFromMatchingAttributes(&$attributes, $xml){
      $matchedAttributes = Mage::getModel("izberg/attribute")->getCollection();

      foreach($matchedAttributes as $attribute){
        $attr = Sebfie_Izberg_Model_Attribute::getMagentoAttributeFromIzbergAttributeCode($attribute->getIzbergMatchingAttributeCode());
        if (isset($xml[$attribute->getIzbergMatchingAttributeCode()])) {
          $text = preg_replace( "/\r|\n/", "", (string)$xml[$attribute->getIzbergMatchingAttributeCode()]);
          $attributes[$attr->getAttributeCode()] = Mage::helper("izberg")->toText($text);
        }
      }
    }

    public function getGender()
    {
      $attrs = $this->getForceAttributesValues();
      if (isset($attrs->gender)) {
        return $attrs->gender;
      }
      return $this->getData("gender");
    }

    // This will return a simple array on "product" key with all attributes required by the model api_import/import_api
    // Return false if product not in stock and user does not want to import it
    // Execute Mage::helper("api_import/test")->generateRandomSimpleProduct(2) as example;
    // array (size=14)
    //   'description' => string 'Some description' (length=16)
    //   '_attribute_set' => string 'Default' (length=7)
    //   'short_description' => string 'Some short description' (length=22)
    //   '_product_websites' => string 'base' (length=4)
    //   'status' => int 1
    //   'visibility' => int 4
    //   'tax_class_id' => int 0
    //   'is_in_stock' => int 1
    //   'sku' => string 'some_sku_1' (length=10)
    //   '_type' => string 'simple' (length=6)
    //   'name' => string 'Some product ( 1 )' (length=18)
    //   'price' => int 642
    //   'weight' => int 690
    //   'qty' => int 1
    public function createProduct($extraData = array(), $xmlResponse = null, $is_product_from_configurable = false, $configurable_xmlResponse = null)
    {
      $storeId = Mage::app()
        ->getWebsite(true)
        ->getDefaultGroup()
        ->getDefaultStoreId();

      if (is_null($this->_images_to_delete)) $this->_images_to_delete = array();
      if (is_null($xmlResponse)) $xmlResponse = $this->getIzbergResponse();

      $name_format = Mage::getStoreConfig("izberg/izberg_product_settings/izberg_default_product_name_format", Mage::app()->getStore());

      if ($is_product_from_configurable) {
        $name = preg_replace("/{{[\W+]?brand}}/i", (string) $configurable_xmlResponse->brand->name, $name_format);
        $name = preg_replace("/{{[\W+]?name}}/i", (string) $configurable_xmlResponse->name, $name);

        foreach($xmlResponse->variation_type->varying_attribute as $attr) {
          if ((string)$attr == "size" || (string)$attr == "color") {
            preg_match("/{{([\W]+)variation\|" .(string)$attr. "}}/i", $name, $matches);
            $name = preg_replace("/{{[\W]+variation\|" .(string)$attr. "}}/i", $matches[1]. (string)$xmlResponse->$attr , $name);
          }
        }
        $xmlResponse->merchant_name = (string) $configurable_xmlResponse->best_offer->merchant->name;
      } else {
        $name = preg_replace("/{{[\W+]?brand}}/i", (string) $xmlResponse->brand->name, $name_format);
        $name = preg_replace("/{{[\W+]?name}}/i", (string) $xmlResponse->name, $name);

        $xmlResponse->merchant_name = (string) $xmlResponse->best_offer->merchant->name;
      }

      // We remove raimining {{*}}
      $name = trim(preg_replace("/{{?[\W]+[a-z|]+}}/i", '', $name));

      // The product as array to return
      $product = array();

      if (!$is_product_from_configurable && Mage::app()->getStore()->getCurrentCurrencyCode() != (string) $xmlResponse->best_offer->currency) {
        Mage::helper("izberg")->log("We ignored izberg product due to bad currency. We got " . (string) $xmlResponse->best_offer->currency . " and our magento is " . Mage::app()->getStore()->getCurrentCurrencyCode(), 7, "", "product", $this->getId());
        return false;
      }

      $set = Mage::helper("izberg")->getDefaultProductAttributeSet();

      // By default special_price is null
      $special_price = null;
      $special_from_date = null;
      $special_to_date = null;

      if (!$is_product_from_configurable) {
        $price = Mage::getStoreConfig("izberg/izberg_product_settings/izberg_price_with_tax", Mage::app()->getStore()) ? (float) $xmlResponse->best_offer->price_with_vat : (float) $xmlResponse->best_offer->price_without_vat;

        // If promotion the special price is the current price and the regular price is previous price
        if ((string)$xmlResponse->best_offer->discounted == "True") {
          $special_price = $price;
          $price = Mage::getStoreConfig("izberg/izberg_product_settings/izberg_price_with_tax", Mage::app()->getStore()) ? (float) $xmlResponse->best_offer->previous_price_with_vat : (float) $xmlResponse->best_offer->previous_price_without_vat;
        }

      } else {
        $price = Mage::getStoreConfig("izberg/izberg_product_settings/izberg_price_with_tax", Mage::app()->getStore()) ? (float) $xmlResponse->price_with_vat : (float) $xmlResponse->price_without_vat;

        // If promotion
        if ((string)$xmlResponse->discounted == "True") {
          $special_price = $price;
          $price = Mage::getStoreConfig("izberg/izberg_product_settings/izberg_price_with_tax", Mage::app()->getStore()) ? (float) $xmlResponse->previous_price_with_vat : (float) $xmlResponse->previous_price_without_vat;
        }
      }

      if (!is_null($special_price)) {
        $special_from_date = $is_product_from_configurable ? (string)$xmlResponse->discount_start_date : (string)$xmlResponse->best_offer->discount_start_date;
        $special_to_date = $is_product_from_configurable ? (string)$xmlResponse->discount_end_date : (string)$xmlResponse->best_offer->discount_end_date;

        // In case we got an error on parse
        if (!$special_from_date) $special_from_date = date('Y-m-d H:i:s');
        if (!$special_to_date) $special_to_date = date('Y-m-d H:i:s', strtotime($special_from_date . ' +1 year'));
      }

      if (!$is_product_from_configurable) {
        $stock = (int) $xmlResponse->best_offer->stock;
      } else {
        $stock = (int) $xmlResponse->stock;
      }
      $is_in_stock = $stock > 0 ? 1 : 0;

      $rootCategoryId = Mage::app()->getStore($storeId)->getRootCategoryId();
      $rootCategory = Mage::getModel("catalog/category")->load($rootCategoryId);

      if (!$is_product_from_configurable) {
        $weight = (float) $xmlResponse->best_offer->weight_numeral;
        $description = preg_replace('/\r|\n/','',(string) $xmlResponse->description);
        $sku = "izb-" .  $xmlResponse->id;
      } else {
        $weight = (float) $xmlResponse->weight_numeral;
        $description = preg_replace('/\r|\n/','',(string) $configurable_xmlResponse->description);
        $sku = "izb-v" . (string)$xmlResponse->id;
      }

      // By default we enable product
      $status = Mage_Catalog_Model_Product_Status::STATUS_ENABLED;

      // If no stock, we check module options
      if ($is_in_stock == 0) {
        if (Mage::helper("izberg")->skipProductsWithoutStock()) {
          // We remove product if already imported
          $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
          // We also delete all childs if it's a configurable product
          if ($product) {
            if (!$is_product_from_configurable && $product->getTypeId() == "configurable") {
              $childProducts = Mage::getModel('catalog/product_type_configurable')
                      ->getUsedProducts(null,$product);
              foreach($childProducts as $child) {
                $child->delete();
              }
            }
            $product->delete();
            Mage::helper("izberg")->log("We deleted product with sku: " . $sku . "and izberg_product_id: " . $this->getId() . " because it doesn't have stock anymore", 7, "", "product", $this->getId());
          }
          return false;
        } else if (Mage::helper("izberg")->disableProductsWithoutStock()) {
          $status = Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
        }
      }

      // If the product is disabled from Izberg and we allow disabled for imported products
      if ((string) $xmlResponse->status != "active") {
        $status = Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
      }


      // ======================//
      // ! VISIBILITY !        //
      // ======================//
      if ($is_product_from_configurable) {
        $visibility = Mage::getStoreConfig("izberg/izberg_product_settings/izberg_product_visibility_configurable_simple", Mage::app()->getStore());
      } else {
        if (isset($extraData["type"]) && $extraData["type"] == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
          $visibility = Mage::getStoreConfig("izberg/izberg_product_settings/izberg_product_visibility_configurable", Mage::app()->getStore());
        } else {
          $visibility = Mage::getStoreConfig("izberg/izberg_product_settings/izberg_product_visibility_simple", Mage::app()->getStore());
        }
      }

      // We set attributes
      $product[0] = array_merge($product, array(
        'sku' => $sku,
        'name' => $name,
        'description' => Mage::helper("izberg")->toText($description),
        'short_description' => Mage::helper("izberg")->toText($description),
        'weight' => $weight,
        'status' => $status,
        'visibility' => $visibility,
        'type' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        'price' => $price,
        'special_price' => $special_price,
        'special_from_date' => $special_from_date,
        'special_to_date' => $special_to_date,
        'tax_class_id' => Mage::helper("izberg")->getTaxeGroupId() ? Mage::helper("izberg")->getTaxeGroupId() : 0,
        'free_izberg_shipping' => 1,
        'attribute_set' => $set->getAttributeSetName(),
        "is_in_stock" => $is_in_stock,
        "qty" => $stock,
        "store" => Mage::helper("izberg/magmi")->getAllStoreCodes(),
        "url_rewrite" => 1
      ));

      $product[0] = array_merge($product[0], $extraData);

      $this->completeProductAttributesFromMatchingAttributes($product[0], (array) $xmlResponse);
      $this->overwriteAttributeFromModuleSettings($product[0]);
      $this->overwriteAttributeFromProductForceAttributes($product[0]);

      // We add product website

      $product[0] = array_merge(
        (isset($product[0]) ? $product[0] : array()),
        array(
          "websites" => implode(",",self::getWebsitesCodes())
        )
      );

      if (($is_product_from_configurable && Mage::getStoreConfig("izberg/izberg_product_settings/izberg_link_simple_products_from_configurable_to_categories", Mage::app()->getStore())) || !$is_product_from_configurable) {
        // We add product categories
        $magentoCategories = $this->getCategoryMatching();
        $category_ids = array();
        if ($magentoCategories) {
          $final_ids = array();
          foreach($magentoCategories as $c) {
            $path = $c->getPath();
            $ids = explode('/', $path);
            unset($ids[0]);
            unset($ids[1]);
            if (!Mage::helper("izberg")->appendToParentCategories()) {
              array_push($category_ids, $c->getId());
            } else {
              $category_ids = $ids;
            }
          }

        } else {
          // Mage::helper("izberg")->log("We did not set any category for product " . $name . " with sku " . $sku . " so it will not appear in your catalog");
        }

        if (!empty($category_ids)) {
          $product[0]["category_ids"] = implode(",",$category_ids);
        } else {
          $product[0]["category_ids"] = "";
        }
      } else {
        $product[0]["category_ids"] = "";
      }

      $this->importImages($is_product_from_configurable ? $configurable_xmlResponse : $xmlResponse, $product);

      // Return data for api importer and for our system
      return array(
        "product" => $product,
        "extra_data" => array(
          $sku => array(
            "sku" => $sku,
            "izberg_product_id" => $is_product_from_configurable ? (int) $configurable_xmlResponse->id : (int) $xmlResponse->id,
            "best_offer_id" => $is_product_from_configurable ? (int) $configurable_xmlResponse->best_offer->id : (int) $xmlResponse->best_offer->id,
            "variation_id" => $is_product_from_configurable ? (int) $xmlResponse->id : null
          )
        )
      );
    }

    public function setOrAddOptionAttribute($arg_attribute, $arg_value) {
        $attribute_model = Mage::getModel('eav/entity_attribute');
        $attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');

        $attribute_code = $attribute_model->getIdByCode('catalog_product', $arg_attribute);
        $attribute = $attribute_model->load($attribute_code);

        $attribute_options_model->setAttribute($attribute);
        $options = $attribute_options_model->getAllOptions(false);

        // determine if this option exists
        $value_exists = false;
        foreach($options as $option) {
            if ($option['label'] === $arg_value) {
                $value_exists = true;
                break;
            }
        }

        // if this option does not exist, add it.
        if (!$value_exists) {
            $attribute->setData('option', array(
                'value' => array(
                    'option' => array($arg_value,$arg_value)
                )
            ));
            $attribute->save();
        }
        return $attribute;
    }

    // The logic is, we have an array for categories :
    // [100,200,300]
    // We search for match /100/200/300/
    // Then /100/200/
    // Then /100/
    public function getMatchingFromIds($category_ids)
    {
      $continue = true;
      $result = false;

      if (empty($category_ids)) return $result;

      while ($continue == true) {
        $path_to_search = "/".implode("/", $category_ids)."/";
        $result = Mage::getModel("izberg/category")->getCollection()->getItemFromPath($path_to_search, $this);
        if ($result->getId()) {
          $continue = false;
          $result = $result->getMagentoCategory();
        } else {
          array_pop($category_ids);
          if (empty($category_ids)) {
            $continue = false;
            $result = false;
          }
        }
      }
      return $result;
    }

    public function getCategoryMatching()
    {
      $xmlResponse = $this->getIzbergResponse();
      $attrs = $this->getForceAttributesValues();
      if (isset($attrs->category_ids)) {
        return  Mage::getModel("catalog/category")->getCollection()->addFieldToFilter("entity_id", array(
          "in" => $attrs->category_ids
        ));
      } else {
        $result = array();

        // Application categories
        $application_categories = $xmlResponse->application_categories_dict;
        //   <application_categories_dict type="list">
        //     <application_categories_dict type="integer">1000</application_categories_dict>
        //     <application_categories_dict type="integer">8</application_categories_dict>
        //  </application_categories_dict>
        $application_category_ids = array();
        $application_category_names = array();
        foreach($application_categories->application_categories_dict as $cat) {
          array_push($application_category_ids, (string) $cat->id);
          array_push($application_category_names, (string) $cat->name);
        }
        $result1 = $this->getMatchingFromIds($application_category_ids);
        if ($result1) array_push($result, $result1);

        // Izberg categories
        $categories = $xmlResponse->categories_dict;
        //   <categories type="list">
        //     <category type="integer">1000</category>
        //     <category type="integer">8</category>
        //  </categories>
        $category_ids = array();
        $category_names = array();
        foreach($categories->categories_dict as $cat) {
          array_push($category_ids, (string) $cat->id);
          array_push($category_names, (string) $cat->name);
        }

        $result2 = $this->getMatchingFromIds($category_ids);
        if ($result2) array_push($result, $result2);

        if (!empty($result)) {
          $this->setMatchCategory(true);
        } else {
          $this->setMatchCategory(false);
          if (count($category_ids) > 0 ) {
            Mage::getSingleton('adminhtml/session')->addError("We did not find matching with izberg category " . $cat->name . ". Please match it before to retry.");
          }
          if (count($application_category_ids) > 0 ) {
            Mage::getSingleton('adminhtml/session')->addError("We did not find izberg application category " . $cat->name . ". Please import match application izberg categories before to retry.");
          }
        }
        return $result;
      }
    }

    public function createConfigurableProduct()
    {
      $xmlResponse = $this->getIzbergResponse();

      // The base of the configurable product is the same as a simple product
      $fullConfigurableProductData = $this->createProduct(array(
        "type" => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
      ));

      // If we do not want to import it
      if (!$fullConfigurableProductData) return false;

      $cProductData = $fullConfigurableProductData["product"];

      // The we generate data for configurable
      $counter = 0;
      $sProductDatas = array();
      $cSimpleProductData = array(
        "simples_skus" => array()
      );
      foreach($this->getVariations() as $variation ) {
        $sProductData = $this->createProduct(array(), $variation, true, $xmlResponse);

        if ($sProductData == false) {
            continue;
        }

        array_push($cSimpleProductData["simples_skus"],  $sProductData["product"][0]["sku"]);

        $i = 0;
        $configurable_attributes = array();
        foreach($variation->variation_type->varying_attribute as $attr) {
          if ((string)$attr == "size" || (string)$attr == "color") {
            $this->setOrAddOptionAttribute(Sebfie_Izberg_Model_Attribute::getMagentoAttributeFromIzbergAttributeCode((string)$attr)->getAttributeCode(), (string)$variation->$attr);

            array_push($configurable_attributes, Sebfie_Izberg_Model_Attribute::getMagentoAttributeFromIzbergAttributeCode((string)$attr)->getAttributeCode());

            $sProductData["product"][0][(string)$attr] = (string)$variation->$attr;
          }
        }
        array_push($sProductDatas, $sProductData);

        $counter++;
      }

      $cProductData[0] = array_merge(
        (isset($cProductData[0]) ? $cProductData[0] : array()),
        array(
          "simples_skus" => implode(",",$cSimpleProductData["simples_skus"]),
          "configurable_attributes" => implode(",", $configurable_attributes)
        )
      );

      $result = array();
      $izberg_extra_data = array();
      foreach($sProductDatas as $d) {
        $result = array_merge($result, $d["product"]);
        $izberg_extra_data = array_merge($izberg_extra_data, $d["extra_data"]);
      }
      // Then add the configurable product
      $izberg_extra_data = array_merge($izberg_extra_data, $fullConfigurableProductData["extra_data"]);


      // Finally we push the configurable product
      $result = array_merge($result, $cProductData);

      return array("product" => $result, "extra_data" => $izberg_extra_data);
    }

    public function retreiveOrCreateIzbergProductImage($url, $sku)
    {
      // We create a izberg_product_image
      $izberg_product_image = Mage::getModel("izberg/product_image")
                                  ->getCollection()
                                  ->addFieldToFilter("izberg_product_id", $this->getIzbergProductId())
                                  ->addFieldToFilter("catalog_product_sku", $sku)
                                  ->addFieldToFilter("izberg_image_url", $url)
                                  ->getFirstItem();

      // Create it if not exists
      if (!$izberg_product_image->getId()) {
        $izberg_product_image->addData(array(
          "izberg_product_id" => $this->getIzbergProductId(),
          "catalog_product_sku" => $sku,
          "izberg_image_url" => $url,
          "magento_image_path" => "",
          "to_import" => true,
          "updated_at" => time(),
          "created_at" => time()
        ));
        $izberg_product_image->save();
      }

      return $izberg_product_image;
    }

    public function importImages($xmlResponse, &$product)
    {
      // We add images to csv
      $j = 0;
      $urls = array();
      foreach ($xmlResponse->best_offer->images->children() as $img) {
        $j++;
        $url = (string) $img->url;

        $optionForFirstImage = array();
        $imported = false;
        if ($j==1 && Mage::getStoreConfig("izberg/izberg_product_settings/izberg_first_image_as_small_image", Mage::app()->getStore()))
        {
          $product[0]["small_image"] = "+" . $url;
          $imported = true;
        }
        if ($j==1 && Mage::getStoreConfig("izberg/izberg_product_settings/izberg_first_image_as_thumbnail", Mage::app()->getStore()))
        {
          $product[0]["thumbnail"] = "+" . $url;
          $imported = true;
        }
        if ($j==1 && Mage::getStoreConfig("izberg/izberg_product_settings/izberg_first_image_as_baseimage", Mage::app()->getStore()))
        {
          $product[0]["image"] = "+" . $url;
          $imported = true;
        }

        if (!$imported) {
          array_push($urls, "+" . $url);
        }
      }
      $product[0]["media_gallery"] = implode(";", $urls);
    }


    // Clean images not in $urls
    public function cleanIzbergProductImages($urls, $sku) {
      $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
      if (!$product) return;
      $izbergProductImages = Mage::getModel("izberg/product_image")
                                  ->getCollection()
                                  ->addFieldToFilter("izberg_product_id", $this->getIzbergProductId())
                                  ->addFieldToFilter("catalog_product_sku", $sku)
                                  ->addFieldToFilter('izberg_image_url', array(
                                    'nin' => array($urls)
                                  ));
      $mediaApi = Mage::getModel("catalog/product_attribute_media_api");
      $items = $mediaApi->items($product->getId());
      foreach($izbergProductImages as $image) {
        foreach($items as $item) {
            if (strpos($item['file'],strtolower($image->getMagentoImagePath())) !== false) {
              $mediaApi->remove($product->getId(), $item['file']);
              @unlink(Mage::getBaseDir('media') . '/catalog/product/' . $item['file']);
            }
        }
        $items = $mediaApi->items($product->getId());

        // We removed an image, so set the first as base
        if (Mage::getStoreConfig("izberg/izberg_product_settings/izberg_first_image_as_small_image", Mage::app()->getStore()))
        {
          Mage::getSingleton('catalog/product_action')->updateAttributes(array($product->getId()), array('small_image'=>$items[0]['file']), 0);
        }
        if (Mage::getStoreConfig("izberg/izberg_product_settings/izberg_first_image_as_thumbnail", Mage::app()->getStore()))
        {
          Mage::getSingleton('catalog/product_action')->updateAttributes(array($product->getId()), array('thumbnail'=>$items[0]['file']), 0);
        }
        if (Mage::getStoreConfig("izberg/izberg_product_settings/izberg_first_image_as_baseimage", Mage::app()->getStore()))
        {
          Mage::getSingleton('catalog/product_action')->updateAttributes(array($product->getId()), array('image'=>$items[0]['file']), 0);
        }
        $image->delete();
      }
    }


    public function overwriteAttributeFromModuleSettings(&$data)
    {
      try {
        $json_config = Mage::helper('core')->jsonDecode(Mage::getStoreConfig("izberg/izberg_product_settings/izberg_default_product_attribute", Mage::app()->getStore()));
        foreach($json_config as $key => $value) {
          if (is_bool($value)) $value = ($value) ? Mage::helper("core")->__("Yes") : Mage::helper("core")->__("No") ;
          $data[$key] = $value;
        }
      } catch (Exception $e) {

      }
    }

    public function getForceAttributesValues()
    {
      try {
        $result = json_decode($this->getForceAttributes());
        return $result ? $result : new stdClass();
      } catch(Exception $e) {
        return new stdClass();
      }
    }

    public function overwriteAttributeFromProductForceAttributes(&$data)
    {
      try {
        $json_config = (array) $this->getForceAttributesValues();
        foreach($json_config as $key => $value) {
          if (is_array($value)) continue;
          if (is_bool($value)) $value = ($value) ? Mage::helper("core")->__("Yes") : Mage::helper("core")->__("No") ;
          $data[$key] = $value;
        }
      } catch (Exception $e) {

      }
    }

    public static function isCreatedFromIzberg($product)
    {
      $izbergCatalogProduct = self::getIzbergCatalogProductFromMagentoProduct($product);
      return $izbergCatalogProduct->getId() ? true : false;
    }

    public static function getIzbergCatalogProductFromMagentoProduct($product)
    {
      return Mage::getModel("izberg/catalog_product")->getCollection()->addFieldToFilter("catalog_product_sku" , $product->getSku())->getFirstItem();
    }


    // This function is called by our batched job system
    public static function import($ids, $job)
    {
      try {
        $products = Mage::getModel("izberg/product")->getCollection()->addFieldToFilter('izberg_product_id', array( 'in' => array($ids) ));

        $arrayToImport = array();
        $arrayOfExtraData = array();
        foreach($products as $product) {
          $job->addMessage("We start to import izberg_product with id: " . $product->getId() . " and izberg product id: " . $product->getIzbergProductId());
          $job->save();

          $product_data = $product->prepareForImportInMagentoDb();
          if (isset($product_data["product"]) && $product_data["product"]) {
            $arrayToImport = array_merge($arrayToImport, $product_data["product"]);
            $arrayOfExtraData = array_merge($arrayOfExtraData, $product_data["extra_data"]);
          }

          $job->addMessage("Product " . $product_data["product"][0]["sku"] . " successfully generated to import");
          $job->save();
          $product->save();
        }

        $to_import = Mage::getModel("izberg/import")->addData(array(
          "to_import" => serialize(array(
            "product" => $arrayToImport,
            "extra_data" => $arrayOfExtraData
          )),
          "count" => count($arrayToImport)
        ))->save();

        $job->addLog("Entities saved in mysql for import " . $to_import->getId());

      } catch (Exception $e) {
        $job->addMessage("We got an error while importing products : " . implode(",", $ids) . " with message : " . $e->getMessage());
        $job->save();
        Mage::throwException($e->getMessage());
      }
      $job->addMessage("We successfully imported product with izberg_ids : " . implode(",", $ids));
    }

}

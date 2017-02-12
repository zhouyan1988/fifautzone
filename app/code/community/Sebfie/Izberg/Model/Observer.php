<?php
class Sebfie_Izberg_Model_Observer {

  public static function import_izberg_merchant_products() {
    if (!Mage::helper("izberg")->isInstalled()) return;

    // We get all jobs to process ordered by date
    // We will retry jobs failed > last 20min
    $jobs = Mage::getModel("izberg/job")->getCollection()
              ->addFieldToFilter("status", Sebfie_Izberg_Model_Job::STATUS_ENQUEUED)
              ->addFieldToFilter("magento_model",
              array(
                array('neq' => "Sebfie_Izberg_Model_Webhook")
              ))
              ->addOrder("created_at", "DESC");

    $limit = (int) Mage::getStoreConfig("izberg/izberg_jobs_settings/izberg_concurrency_product_jobs", Mage::app()->getStore());

    if ($limit === 0) return;

    $jobs->getSelect()->limit($limit);
    foreach ($jobs as $job)
    {
        $job->run();
    }
  }

  public static function run_izberg_webhooks() {
    if (!Mage::helper("izberg")->isInstalled()) return;

    // We get all jobs to process ordered by date
    // We will retry jobs failed > last 20min
    $jobs = Mage::getModel("izberg/job")->getCollection()
    ->addFieldToFilter("status", Sebfie_Izberg_Model_Job::STATUS_ENQUEUED)
    ->addFieldToFilter("magento_model", "Sebfie_Izberg_Model_Webhook")
    ->addOrder("created_at", "DESC");

    $limit = (int) Mage::getStoreConfig("izberg/izberg_jobs_settings/izberg_concurrency_webhook_jobs", Mage::app()->getStore());

    if ($limit === 0) return;

    $jobs->getSelect()->limit($limit);

    foreach ($jobs as $job)
    {
      $job->run();
    }
  }


  public static function reenque_wip_jobs() {
    if (!Mage::helper("izberg")->isInstalled()) return;

    // We get all jobs to process ordered by date
    // We will retry jobs failed > last 20min
    $jobs = Mage::getModel("izberg/job")->getCollection()
    ->addFieldToFilter("status", Sebfie_Izberg_Model_Job::STATUS_WIP)
    ->addFieldToFilter("reenqued_count", array(
      array('lt' => 5),
      array('null' => true)
    ))
    ->addFieldToFilter("last_run_at",
    array(
      array('to' =>(time() - 2 * 60 * 60), "date" => true)
    ));

    // We reenqueue them if they are always in WIP after 3 hours
    foreach($jobs as $job) {
      $renque_count = $job->getReenquedCount() ? 0 : $job->getReenquedCount();
      $job->setStatus(Sebfie_Izberg_Model_Job::STATUS_ENQUEUED);
      $job->setRetriesCount(0);
      $job->setReenquedCount($renque_count+1);
      $job->save();
    }
    if ($jobs->count() > 0) {
      $job->addLog("We reenqueued " . $jobs->count() . " jobs because it stays on  wip too long");
    }
  }

  public static function clear_magmi_csv()
  {
    if (!Mage::helper("izberg")->isInstalled()) return;

    Mage::helper("izberg")->log("clear_magmi_csv");
    $threeDbefore = date("Y-m-d", strtotime("-7 days"));
    $path = Mage::getBaseDir('var') . DS . 'izberg_export' . DS;
    /*** cycle through all files in the directory ***/
    $i = 0;
    foreach (glob($path."*") as $file) {
      /*** if file is 24 hours (86400 seconds) old then delete it ***/
      if (filemtime($file) < time() - (24*60*60*7)) {
        $i++;
        unlink($file);
      }
    }
    if ($i > 0) {
      Mage::helper("izberg/magmi")->log("We cleared $i files from clear_magmi_csv");
    }
  }


  public function cancel_magmi()
  {
    if (!Mage::helper("izberg")->isInstalled()) return;
    Mage::helper("izberg/magmi")->cancelIfNeeded();
  }

  public function is_izberg_product(Varien_Event_Observer $observer) {
    if (Mage::helper("izberg")->isDevelopment()) return false;

    // Our module izberg can save products
    if (Mage::app()->getRequest()->getModuleName() == 'izberg') return true;

    $product = $observer->getEvent()->getProduct();
    if (!$product->getId()) return true;
    if (Mage::getModel("izberg/catalog_product")->getCollection()->addFieldToFilter('catalog_product_sku', $product->getSku())->count() > 0) {
      Mage::throwException(Mage::helper('izberg')->__("This product can not be modified because it has been created from your Izberg account"));
    }
    return $product;
  }

  public function clear_logs()
  {
    // We keep only one month of history
    $to_delete = Mage::getModel("izberg/log")
      ->getCollection()
      ->addFieldToFilter('created_at', array(
        'to'       => strtotime('-30 day', time()),
        'datetime' => true
      ));
    foreach($to_delete as $log) {
      $log->delete();
    }
  }

  public function clear_jobs()
  {
    if (!Mage::helper("izberg")->isInstalled()) return;


    // We delete all jobs created > 15 days
    $jobs = Mage::getModel("izberg/job")->getCollection()
              ->addFieldToFilter("created_at",
              array(
                array('to' =>(time() - 15 * 24 * 60 * 60), "date" => true)
              ));

    foreach ($jobs as $job) {
      $job->delete();
    }

    Mage::helper("izberg")->log("We removed " . $jobs->count() . " jobs because they were created > 30 days");

  }

  public function flag_split_order_if_has_izberg_products($observer)
  {
    // We use this to not corrupt email sended to user with invoice
    if (!Mage::helper("izberg")->isInstalled()) return;

    $invoice = $observer->getInvoice();
    $order = $invoice->getOrder();
    $storeId = $order->getStoreId();

    // We check if the order only have izberg products
    $izberg_product_ids = array();

    // We loop and store izberg_products_ids
    foreach($order->getAllVisibleItems() as $item){
        $itemId = $item->getId();
        $productSku = $item->getSku();
        if(Mage::getModel("izberg/catalog_product")->getCollection()->addFieldToFilter("catalog_product_sku" , $productSku)->getFirstItem()->getId()){
            $izberg_product_skus[$productSku] = array( "qty" => $item->getQtyOrdered());
        }
    }

    // We split order only if we have izberg products
    if (!empty($izberg_product_skus)) {
      Mage::getModel("izberg/split")->addData(array(
        "order_id" => $order->getId(),
        "created_at" => time()
      ))->save();
    };

    Mage::helper("izberg")->log("We marked order to split ", 7, "", "order", $order->getId());
  }

  // This function will create 2 orders from one if customer bought izberg products
  // One order with others products and one with izberg products
  // It's called after the sales_order_place_after events => Payment successfull
  public function split_izberg_orders()
  {
      if (!Mage::helper("izberg")->isInstalled()) return;

      foreach(Mage::getModel("izberg/split")->getCollection() as $split) {
        try {
          $old_order = $split->getOrder();
          Mage::helper("izberg")->log("We start to split order " . $old_order->getId(), 7, "", "order", $old_order->getId());
          $total_item_count = $old_order->getTotalItemCount();
          $storeId = $old_order->getStoreId();
          $payment = $old_order->getPayment();
          $old_quote = Mage::getModel("sales/quote")
                        ->setStoreId($storeId)
                        ->load($old_order->getQuoteId());
          $izberg = Mage::helper("izberg")->getIzberg(array("quote_id" => $old_quote->getId()), true);
          $customer = Mage::getModel('customer/customer')->load($old_order->getCustomerId());


          // We check if the order only have izberg products
          $izberg_product_ids = array();

          $has_only_izberg_products = true;
          foreach($old_order->getAllVisibleItems() as $item){
              $productSku = $item->getSku();
              if(!Mage::getModel("izberg/catalog_product")->getCollection()->addFieldToFilter("catalog_product_sku" , $productSku)->getFirstItem()->getId()){
                  $has_only_izberg_products = false;
              }
          }

          // We loop and store izberg_products_ids
          foreach($old_order->getAllVisibleItems() as $item){
              $itemId = $item->getId();
              $productSku = $item->getSku();
              if(Mage::getModel("izberg/catalog_product")->getCollection()->addFieldToFilter("catalog_product_sku" , $productSku)->getFirstItem()->getId()){
                  $izberg_product_skus[$productSku] = array( "qty" => $item->getQtyOrdered());
              }
          }

          // We split order only if we have izberg products
          if (!empty($izberg_product_skus)) {

              Mage::helper("izberg")->log("We founded " . count($izberg_product_skus) . " izberg products in order " . $old_order->getId() . ".");
              Mage::helper("izberg")->slack("We founded " . count($izberg_product_skus) . " izberg products in order " . $old_order->getId() . ".");

              // If we only have izberg products
              if ($has_only_izberg_products) {
                  // We just create izberg order from original order
                  $izberg_order = Mage::helper("izberg")->createIzbergOrder($old_order, $old_quote);
                  Mage::helper("izberg")->log("We set order(" . $old_order->getId() .") status to " . Mage::helper("izberg")->getDefaultOrderStatus($old_order->getStatus()), 7, "", "order", $old_order->getId());
                  // We set the status to the status defined in settings
                  $old_order->setStatus(Mage::helper("izberg")->getDefaultOrderStatus($old_order->getStatus()));
                $old_order->addStatusToHistory(Mage::helper("izberg")->getDefaultOrderStatus($old_order->getStatus()), Mage::helper("izberg")->__('This order has only izberg products, it will be managed by izberg merchants. The created izberg order id is: ') . $izberg_order->id , false);

                Mage::helper("izberg")->log(Mage::helper("izberg")->__('This order has only izberg products, it will be managed by izberg merchants. The created izberg order id is: ') . $izberg_order->id , 7, "", "order", $old_order->getId());

                Mage::helper("izberg")->log("We created izberg_order " . $izberg_order->id, 7, "", "order", $old_order->getId());

                $split->delete();
                $old_order->save();
                continue;
              }

              // =================
              // Create new order
              // =================

              // The new quote is created from the original one
              $quote = Mage::getModel('sales/quote')->setStoreId($storeId);
              $quote->merge($old_quote);
              $quote
                  ->assignCustomer($customer)
                  ->setItemsCount($old_quote->getItemsCount())
                  ->setItemsQty($old_quote->getItemsQty());

              if (!$customer->getId()) {
                $quote->setCustomerEmail($old_quote->getCustomerEmail());
                $quote->setCustomerFirstname($old_quote->getCustomerFirstname());
                $quote->setCustomerLastname($old_quote->getCustomerLastname());
              }
              $quote->save();

              // We remove all items not from izberg
              foreach($quote->getAllItems() as $item){
                  $product = Mage::getModel("catalog/product")->load($item->getProductId());
                  if(!Sebfie_Izberg_Model_Product::isCreatedFromIzberg($product)){
                      $quote->removeItem($item->getId());
                      $item->isDeleted(true);
                  }
              }

              // We set addresses
              $quote->setShippingAddress($old_quote->getShippingAddress());
              $quote->setBillingAddress($old_quote->getBillingAddress());
              $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod($old_order->getShippingMethod())->setPaymentMethod($old_order->getPayment()->getMethod());

              $quote->getPayment()->importData(array('method' => $old_order->getPayment()->getMethod()));

              // We compute totals and save
              $quote->collectTotals()->save();

              //Feed quote object into sales model
              $service = Mage::getModel('sales/service_quote', $quote);

              //submit all orders to MAGE
              $service->submitAll();

              //Setup order object and gather newly entered order
              $new_order = $service->getOrder();

              // We comment the order created by our system
              $new_order->setState(Mage::helper("izberg")->getDefaultOrderStatus(), true, Mage::helper("izberg")->__("Order automatically created with izberg products (from order_id: ") . $old_order->getId() . "). The payment for this order is the payment with transaction Id: " . $payment->getTransactionId() );

              Mage::helper("izberg")->log(Mage::helper("izberg")->__("Order automatically created with izberg products (from order_id: ") . $old_order->getId() . ").", 7, "", "order", $old_order->getId());

              Mage::helper("izberg")->log("Order automatically created with izberg products (from order_id: " . $old_order->getId() . "). The payment for this order is the payment with transaction Id: " . $payment->getTransactionId(), 7, "", "order", $new_order->getId());

              $new_order->setStatus(Mage::helper("izberg")->getDefaultOrderStatus());
              $new_order->save();

              // =================
              // Manage old order
              // =================

              // We remove izberg items from old order
              foreach($old_order->getAllItems() as $item){
                  $itemId = $item->getId();
                  $productId = $item->getProductId();
                  $product = Mage::getModel("catalog/product")->load($item->getProductId());
                  if(Sebfie_Izberg_Model_Product::isCreatedFromIzberg($product)){
                      $item_price = $item->getPrice();
                      $item->delete();
                      $old_order->setTotalItemCount($total_item_count-1);
                  }
              }

              //We recompute totals
              $old_order->setBaseGrandTotal($old_order->getBaseGrandTotal() - $new_order->getBaseGrandTotal());
              $old_order->setBaseSubtotal($old_order->getBaseSubtotal() - $new_order->getBaseSubtotal());
              $old_order->setGrandTotal($old_order->getGrandTotal() - $new_order->getGrandTotal());
              $old_order->setSubtotal($old_order->getSubtotal() - $new_order->getSubtotal());


              $old_order->setBaseShippingAmount($old_order->getBaseShippingAmount() - $new_order->getBaseShippingAmount());
              $old_order->setBaseShippingTaxAmount($old_order->getBaseShippingTaxAmount() - $new_order->getBaseShippingTaxAmount());
              $old_order->setShippingTaxAmount($old_order->getShippingTaxAmount() - $new_order->getShippingTaxAmount());
              $old_order->setBaseShippingInvoiced($old_order->getBaseShippingInvoiced() - $new_order->getShippingAmount());
              $old_order->setShippingAmount($old_order->getShippingAmount() - $new_order->getShippingAmount());
              $old_order->setShippingInclTax($old_order->getShippingInclTax() - $new_order->getShippingInclTax());
              $old_order->setBaseShippingInclTax($old_order->getBaseShippingInclTax() - $new_order->getBaseShippingInclTax());
              $old_order->setShippingInvoiced($old_order->getShippingInvoiced() - $new_order->getShippingAmount());

              $old_order->setBaseSubtotalInclTax($old_order->getBaseSubtotalInclTax() - $new_order->getBaseSubtotalInclTax());
              $old_order->setSubtotalInclTax($old_order->getSubtotalInclTax() - $new_order->getSubtotalInclTax());

              $old_order->addStatusToHistory($old_order->getStatus(), Mage::helper("izberg")->__('This order has been splited into two orders because it contains izberg products. The other order id is #') . $new_order->getIncrementId() , false);

              Mage::helper("izberg")->log('This order has been splited into two orders because it contains izberg products. The other order id is #'. $new_order->getIncrementId(), 7, "", "order", $old_order->getId());

              $new_order->save();
              $old_order->save();

              Mage::helper("izberg")->createIzbergOrder($new_order, $quote);

              $split->delete();
          } else {
              Mage::helper("izberg")->log("We did not find izberg products in order " . $old_order->getId(), 7, "", "order", $old_order->getId());
          }
      } catch (Exception $e) {
        Mage::helper("izberg")->log("An error occurs when trying to split order " . $old_order->getId() . ": " . $e->getMessage(), 2);
      }
    }
  }

  // CAUTION
  // ALL THIS BOTTOM EVENTS OCCURS ON THE CONVERSION FUNNEL
  // BE VERY CAREFUL

  public function addItemToIzbergCart($observer)
  {
    if (!Mage::helper("izberg")->isInstalled()) return;
    try {
        $quoteItems = $observer->getItems();
        $firstQuoteItem = $quoteItems[0];
        $quote = $firstQuoteItem->getQuote();

        foreach($quoteItems as $quoteItem) {
          $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $quoteItem->getSku());
          if (is_null($quoteItem->getParentItem()) && Sebfie_Izberg_Model_Product::isCreatedFromIzberg($product)) {
              $izberg = Mage::helper("izberg")->getIzberg(array("quote_id" => $quote->getId()));
              $izberg_cart = Mage::helper("izberg")->getIzbergCart($izberg);
              $izbergCatalogProduct = Sebfie_Izberg_Model_Product::getIzbergCatalogProductFromMagentoProduct($product);
              $response = $izberg_cart->addItem(array(
                "offer_id" => $izbergCatalogProduct->getOfferId(),
                "variation_id" => $izbergCatalogProduct->getVariationId(),
                "quantity" => (int) $quoteItem->getQty()
              ));
          }
        }
    } catch (Exception $e) {
      Mage::helper("izberg")->slack("An error occurs on addItemToIzbergCart of Observer with message: " . $e->getMessage());
      Mage::throwException(Mage::helper('izberg')->__("This product can not be added to your cart. Please try again later"));
    }
  }


  public function updateItemToIzbergCart($observer)
  {
    if (!Mage::helper("izberg")->isInstalled()) return;

    try {
        $cart = $observer->getEvent()->getCart();
        $items = $cart->getItems();
        $izberg = Mage::helper("izberg")->getIzberg(array("quote_id" => $cart->getQuote()->getId()));
        // Called to update cart quantity
        foreach ($items as $item) {
            $product = $item->getProduct();
            if (Sebfie_Izberg_Model_Product::isCreatedFromIzberg($product)) {
                $izbergCatalogProduct = Sebfie_Izberg_Model_Product::getIzbergCatalogProductFromMagentoProduct($product);
                $cart_item = Mage::helper("izberg")->retreiveCartItemFromOfferId($izberg, $izbergCatalogProduct->getOfferId(), $izbergCatalogProduct->getVariationId());
                $izberg->update("cartItem", $cart_item->id, array("quantity" => $item->getQty()));
            }
        }
    } catch (Exception $e) {
      Mage::helper("izberg")->slack("An error occurs on updateItemToIzbergCart of Observer with message: " . $e->getMessage());
      Mage::throwException(Mage::helper('izberg')->__("This product can not be updated on your cart. Please try again later"));
    }
  }

  public function deleteItemToIzbergCart($observer)
  {
    if (!Mage::helper("izberg")->isInstalled()) return;

    try {
        $quoteItem = $observer->getQuoteItem();
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $quoteItem->getSku());
        if (Sebfie_Izberg_Model_Product::isCreatedFromIzberg($product)) {
            $izberg = Mage::helper("izberg")->getIzberg(array("quote_id" => $quoteItem->getQuote()->getId()));
            $izbergCatalogProduct = Sebfie_Izberg_Model_Product::getIzbergCatalogProductFromMagentoProduct($product);
            $cart_item = Mage::helper("izberg")->retreiveCartItemFromOfferId($izberg, $izbergCatalogProduct->getOfferId(), $izbergCatalogProduct->getVariationId());

            if ($cart_item){
                $cart_item->delete();
            }
        }
    } catch (Exception $e) {
      Mage::helper("izberg")->slack("An error occurs on deleteItemToIzbergCart of Observer with message: " . $e->getMessage());
      Mage::throwException(Mage::helper('izberg')->__("This product can not be deleted from your cart. Please try again later"));
    }

  }

  // Function called when we clear shopping cart
  public function deleteAllItemToIzbergCart()
  {
    if (!Mage::helper("izberg")->isInstalled()) return;

    try {
      $post = Mage::app()->getRequest()->getPost('update_cart_action');
      if ($post == 'empty_cart') {
         $quote = Mage::helper('checkout/cart')->getQuote();
         // We create a new cart
         Mage::helper("izberg")->getIzberg(array("quote_id" => $quote->getId()))->create("cart");
      }
    } catch (Exception $e) {
      Mage::helper("izberg")->slack("An error occurs on deleteItemToIzbergCart of Observer with message: " . $e->getMessage());
      Mage::throwException(Mage::helper('izberg')->__("This product can not be deleted from your cart. Please try again later"));
    }
  }


  public function checkoutCartUpdate()
  {
    $post = Mage::app()->getRequest()->getPost('update_cart_action'); // get value
     if ($post == 'empty_cart') {
         Mage::helper("izberg")->getIzberg()->removeAllCartItems();
     }
  }

  public function afterQuoteMerge($observer)
  {
    try {
        $quote = $observer->getQuote();

        $izberg = Mage::helper("izberg")->getIzberg(array("quote_id" => $quote->getId()));
        $cart = Mage::helper("izberg")->getIzbergCart($izberg);
        $cartItems = $cart->getItems();

        foreach($quote->getAllVisibleItems() as $quoteItem) {
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $quoteItem->getSku());

            if (Sebfie_Izberg_Model_Product::isCreatedFromIzberg($product)) {
                $izbergCatalogProduct = Sebfie_Izberg_Model_Product::getIzbergCatalogProductFromMagentoProduct($product);
                $izbergProduct = Mage::getModel("izberg/product")->load($izbergCatalogProduct->getIzbergProductId());
                // If the product is already in the cart
                $cart_item = Mage::helper("izberg")->izbergProductInIzbergCart($cartItems, $izbergProduct->getIzbergProductId());
                if ($cart_item) {
                    if ((int)$cart_item->quantity == (int) $quoteItem->getQtyOrdered()) {
                        // Mage::helper("izberg")->slack("Same quantity");
                        continue;
                    }
                    $izberg->update("cartItem", $cart_item->id, array(
                        "quantity" => (int) $quoteItem->getQty()
                    ));
                } else {
                    //   offer_id: Integer
                    //   variation_id: Integer
                    //   quantity: Integer
                    //   gift: Boolean
                    //   bundled: Boolean
                    $cart->addItem(array(
                        "offer_id" => $izbergCatalogProduct->getOfferId(),
                         "variation_id" => $izbergCatalogProduct->getVariationId(),
                         "quantity" => (int) $quoteItem->getQty()
                    ));
                }
            }
        }
    } catch (Exception $e) {
      Mage::helper("izberg")->slack("An error occurs on afterQuoteMerge with message: " . $e->getMessage());
    }
  }

  public function removeImages($observer) {
    $product = $observer->getProduct();

    // We remove all associated images
    $sku = $product->getSku();
    $izbergProductImages = Mage::getModel("izberg/product_image")
                                ->getCollection()
                                ->addFieldToFilter("catalog_product_sku", $sku);
    foreach($izbergProductImages as $image) {
      $image->delete();
    }
  }

  // We have to use this event because on first product add, quote_id is null in sales_quote_product_add_after
  public function updateTokenIfNeeded($observer)
  {
    Mage::helper("izberg")->updateTokenIfNeeded($observer->getQuote()->getId());
  }

  public function newCart($observer) {
    $izberg = Mage::helper("izberg")->getIzberg(array("quote_id" => $observer->getQuote()->getId()));
    $izberg->create("cart");
  }

  // ADMINHTML EVENTS
  public function addProductMassAction($observer) {
    $block = $observer->getEvent()->getBlock();
    if(Mage::helper("izberg")->endsWith(get_class($block), 'Block_Widget_Grid_Massaction')
    && $block->getRequest()->getControllerName() == 'catalog_product')
    {
      $block->addItem('izberg', array(
        'label' => 'Reimport izberg product',
        'url' => Mage::app()->getStore()->getUrl('izberg/adminhtml_product/reimport'),
      ));
    }
  }


}

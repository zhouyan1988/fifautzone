<?php
class Sebfie_Izberg_ApiController extends Mage_Core_Controller_Front_Action
{

    public function product_offer_updatedAction() {
      try {
        // We get something like :
        // $datas = Mage::helper('core')->jsonDecode('{
        //   "environment": "sandbox",
        //   "count": 1,
        //   "triggers": [
        //     {
        //       "attempt": 1,
        //       "triggered_at": "2015-02-26T15:53:24.436197+01:00",
        //       "version": "1.0",
        //       "webhook_trigger_id": 12594,
        //       "data": {
        //         "updated_attributes": [
        //           "name"
        //         ],
        //         "product": {
        //           "pk": 16175,
        //           "id": 16175,
        //           "resource_uri": "http://api.local.izberg.technology:8000/v1/product/3050370/"
        //         },
        //         "resource_uri": "http://api.local.izberg.technology:8000/v1/productoffer/14236/",
        //         "id": 14236,
        //         "name": "Patchouli Patch JJJ"
        //       },
        //       "event": "product_offer_updated"
        //     },
        //     {
        //       "attempt": 1,
        //       "triggered_at": "2015-02-26T15:53:24.436197+01:00",
        //       "version": "1.0",
        //       "webhook_trigger_id": 12594,
        //       "data": {
        //         "updated_attributes": [
        //           "name"
        //         ],
        //         "product": {
        //           "pk": 16175,
        //           "id": 16175,
        //           "resource_uri": "http://api.local.izberg.technology:8000/v1/product/3050370/"
        //         },
        //         "resource_uri": "http://api.local.izberg.technology:8000/v1/productoffer/14236/",
        //         "id": 14236,
        //         "name": "Patchouli Patch JJJ"
        //       },
        //       "event": "product_offer_updated"
        //     },
        //     {
        //       "attempt": 1,
        //       "triggered_at": "2015-02-26T15:53:24.436197+01:00",
        //       "version": "1.0",
        //       "webhook_trigger_id": 12594,
        //       "data": {
        //         "updated_attributes": [
        //           "name"
        //         ],
        //         "product": {
        //           "pk": 16179,
        //           "id": 16179,
        //           "resource_uri": "http://api.local.izberg.technology:8000/v1/product/3050370/"
        //         },
        //         "resource_uri": "http://api.local.izberg.technology:8000/v1/productoffer/14236/",
        //         "id": 14236,
        //         "name": "Patchouli Patch JJJ"
        //       },
        //       "event": "product_offer_updated"
        //     }
        //   ],
        //   "event": "product_offer_updated",
        //   "webhook_id": 2
        // }');

        $datas = Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());
        $webhooks = $datas["triggers"];

        // Manage each webhooks
        foreach($webhooks as $data) {
          $data = $data["data"];
          // If a product is inactive, we disable it and to not queue a job
          if (isset($data["status"]) && $data["status"] == "inactive") {
            $izberg_product = Mage::getModel('izberg/product')->getCollection()->addFieldToFilter('izberg_product_id', $data["product"]["id"])->getFirstItem();

            $izberg = Mage::helper("izberg")->getIzberg(array("force_admin" => true));

            Mage::helper("izberg")->log("We changed status of product " . $izberg_product->getId() . "/" . $izberg_product->getName() . " to " . $data["status"] . " using webhook", 7, "", "product", $izberg_product->getId());

            foreach ($izberg_product->getCatalogProducts() as $product) {
              foreach (Mage::app()->getStores() as $store) {
                Mage::getModel('catalog/product_status')->updateProductStatus($product->getId(),$store->getId(), Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
              }
            }
            echo Mage::Helper('core')->jsonEncode(array("success" => "Webhook successfully treated"));
          } else {
          // If the same job already exists, it will not be enqueued twice (see the function enqueue_job)
            $job = Sebfie_Izberg_Model_Job::enqueue_job("Sebfie_Izberg_Model_Webhook","product_offer_updated", $data["product"]["id"]);
            $job->addLog("Job created/updated from data " . Mage::helper('core')->jsonEncode($data));
          }
        }
        if (count($webhooks) > 0)
          echo Mage::Helper('core')->jsonEncode(array("success" => "Webhook successfully enqueued"));
        else
          echo Mage::Helper('core')->jsonEncode(array("success" => "Not webhooks"));

      } catch ( Exception $e) {
        Mage::helper("izberg")->log("An error occurs on magento module during product_offer_updated webhook: " . $e->getMessage());

        Mage::helper("izberg")->slack("An error occurs on magento module during product_offer_updated webhook with message: " . $e->getMessage() . " We got data " . json_encode($data));

        $this->getResponse()->setHttpResponseCode(500);
        Mage::throwException($e->getMessage());
      }
    }

    public function new_merchant_availableAction() {
      $izberg = Mage::helper("izberg")->getIzberg(array("force_admin" => true));
      $data = Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());
      $data = $data["data"];

      try {
        $merchant = $izberg->get("merchant", $data["merchant"]["id"]);
        Mage::helper("izberg")->manageMerchantsFromAPIResponse(array($merchant));

        echo Mage::Helper('core')->jsonEncode(array("success" => "Merchant successfully created/updated"));

      } catch ( Exception $e) {
        Mage::helper("izberg")->log("An error occurs on magento module during new_merchant_available webhook: " . $e->getMessage());

        Mage::helper("izberg")->slack("An error occurs on magento module during new_merchant_available webhook with message: " . $e->getMessage() . " We got data " . json_encode($data));

        $this->getResponse()->setHttpResponseCode(500);
        Mage::throwException($e->getMessage());
      }
    }

    public function product_offer_createdAction() {
      // We get something like :
      // {"data": {"warranty": null, "merchant": {"store_type": 1, "wrapping_score": null, "twitter": null, "profile_image": null, "products_score": null, "store_type_localized": "Professionnel", "created_on": "2014-06-20T18:41:44+02:00", "prefered_language_localized": "French", "id": 3, "overall_score": null, "default_currency": "EUR", "region_localized": "France", "pinterest": null, "long_description": "", "application": {"type": "application", "id": 7, "resource_uri": "https://api.izberg.technology/v1/application/7/"}, "status_localized": "Inactive", "from_list": false, "status": "0", "description": "", "logo_image": null, "facebook": null, "communication_score": null, "slug": "jp", "prefered_language": "fr", "name": "JP", "url": null, "absolute_url": "/jp/", "region": "FR", "shipping_score": null, "shipping_policy": null, "reactivity_score": null, "cover_image": null, "resource_uri": "https://api.izberg.technology/v1/merchant/3/"}, "overwritten_description": "", "weight": null, "description_is_html": false, "from_list": false, "availability": null, "default_image_url": "https://d1uyhd0hkrx9pt.cloudfront.net/images/2014/07/t-shirt-monki-imprime-oeil_111090435cac39c8a73622a681017004.JPEG", "height": null, "visible": false, "hipline": null, "overwritten_description_is_html": false, "id": 56, "start_selling_date": null, "price_includes_vat": false, "sku": "M10", "waist_size": null, "overwritten_details_is_html": false, "depth": null, "chest_size": null, "width": null, "sleeve_length": null, "details": null, "restock_date": null, "delay_before_shipping": "0.00", "stock": 1200, "status": 0, "product": {"type": "product", "category_localized": 8, "id": 57, "resource_uri": "https://api.izberg.technology/v1/product/57/"}, "wrappable": true, "price_without_vat": "35.00", "details_is_html": false, "price": "35.00", "overwritten_details": "", "wrapping": "0.00", "inseam": null, "gtin13": "", "last_modified": "2014-07-11T15:23:51+02:00", "wrist": null, "merchant_url": null, "condition": 0, "neck": null, "shoulder": null, "previous_price": "54.00", "name": "T-shirt Monki - imprim\u00e9 oeil", "language": "fr", "currency": "EUR", "weight_numeral": null, "description": "Robe \u00e9coli\u00e8re", "variations": [{"weight": null, "height": null, "hipline": null, "id": 28, "price_includes_vat": false, "sku": "SH010S", "neck": null, "length": null, "chest_size": null, "width": null, "sleeve_length": null, "waist_size": null, "has_size_infos": false, "stock": 400, "vat_rate": "20.00", "price_without_vat": "35.00", "price": "42.00", "inseam": null, "size_name": "S", "overarm": null, "wrist": null, "size_info": "S", "shoulder": null, "kind": "size", "name": "S", "weight_numeral": null, "depth": null, "crotch": null, "gtin13": "3123456000020", "size_abreviation": "s", "price_with_vat": "42.00"}, {"weight": null, "height": null, "hipline": null, "id": 29, "price_includes_vat": false, "sku": "SH010M", "neck": null, "length": null, "chest_size": null, "width": null, "sleeve_length": null, "waist_size": null, "has_size_infos": false, "stock": 400, "vat_rate": "20.00", "price_without_vat": "35.00", "price": "42.00", "inseam": null, "size_name": "M", "overarm": null, "wrist": null, "size_info": "M", "shoulder": null, "kind": "size", "name": "M", "weight_numeral": null, "depth": null, "crotch": null, "gtin13": "3123456000020", "size_abreviation": "m", "price_with_vat": "42.00"}, {"weight": null, "height": null, "hipline": null, "id": 30, "price_includes_vat": false, "sku": "SH010L", "neck": null, "length": null, "chest_size": null, "width": null, "sleeve_length": null, "waist_size": null, "has_size_infos": false, "stock": 400, "vat_rate": "20.00", "price_without_vat": "35.00", "price": "42.00", "inseam": null, "size_name": "L", "overarm": null, "wrist": null, "size_info": "L", "shoulder": null, "kind": "size", "name": "L", "weight_numeral": null, "depth": null, "crotch": null, "gtin13": "3123456000020", "size_abreviation": "l", "price_with_vat": "42.00"}], "length": null, "overarm": null, "crotch": null, "vat_rate": "20.00", "starred": false, "price_with_vat": "42.00", "resource_uri": "https://api.izberg.technology/v1/productoffer/56/"}, "attempt": 1, "webhook_trigger_id": 15, "event": "product_offer_created", "triggered_at": "2014-07-17T15:02:46+02:00"}

    //   try {
    //     $data = Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());
    //     $data = $data["data"];
    //
    //     $izberg_product = Mage::getModel('izberg/product')->getCollection()->addFieldToFilter("izberg_product_id", $data["id"])->getFirstItem();
    //     $izberg_merchant = Mage::getModel('izberg/merchant')->getCollection()->addFieldToFilter("izberg_merchant_id", $data["merchant"]["id"])->getFirstItem();
    //
    //     if (!$izberg_merchant->getId()) {
    //       Mage::throwException ("We did not find merchant with id : " . $data["merchant"]["id"]);
    //     }
    //
    //     if (!$izberg_merchant->getMagentoEnabled()) {
    //       Mage::helper("izberg")->log("We did not created izberg product because the merchant is not enabled ");
    //       return false;
    //     }
    //
    //     $izberg = Mage::helper("izberg")->getIzberg();
    //
    //     $xml = $izberg->getProduct($data["id"], null, 'Accept: application/xml');
    //
    //     Mage::helper("izberg")->log("We start to create product " . $data["id"] . "/" . $data["name"] . " using webhook");
    //
    //     $izberg_product->addData(array(
    //         "izberg_product_id" => (int) $xml->id,
    //         "izberg_category_id" => (int) $xml->category,
    //         "izberg_merchant_id" => $izberg_merchant->getId(),
    //         "slug" => (string) $xml->slug,
    //         "name" => (string) $xml->name,
    //         "description" => (string) $xml->description,
    //         "price" => (float) $xml->best_offer->price,
    //         "price_with_vat" => (float) $xml->best_offer->price_with_vat,
    //         "price_without_vat" => (float) $xml->best_offer->price_without_vat,
    //         // "gender" => (string) $xml->gender,
    //         "brand" => (string) $xml->brand->name,
    //         "status" => (string) $xml->best_offer->status,
    //         "free_shipping" => (bool) $xml->best_offer->free_shipping,
    //         "shipping_estimation" => (string) $xml->best_offer->shipping_estimation,
    //         "in_stock" => (bool) $xml->best_offer->in_stock,
    //         "stock" => (int) $xml->best_offer->stock,
    //         "created_at" => time(),
    //         "created_from_json" => $xml->asXML()
    //     ));
    //     $izberg_product->save();
    //
    //     $izberg_product->importInMagentoDb();
    //
    //     echo Mage::Helper('core')->jsonEncode(array("success" => "Product successfully created"));
    //
    // } catch( Exception $e) {
    //
    //   Mage::helper("izberg")->log("An error occurs on magento module during product_offer_created webhook: " . $e->getMessage());
    //
    //   Mage::helper("izberg")->slack("An error occurs on magento module during product_offer_created webhook with message: " . $e->getMessage() . " We got data " . json_encode($data) );
    //
    //   $this->getResponse()->setHttpResponseCode(500);
    //   Mage::throwException($e->getMessage());
    //
    // }
  }

  //========
  // ORDERS
  //========
  // Web hooks of izberg
  // merchant_order_confirmed
  // merchant_order_sent
  // merchant_order_received
  // merchant_order_cancelled

  public function merchant_order_confirmedAction() {
    // Not necessary for the moment, in magento we do not have confirmed order status
    // try {
    //     $data = Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());
    //     $data = $data["data"];

    //     Mage::helper("izberg")->slack("Data received in merchant_order_confirmed: " . $this->getRequest()->getRawBody());

    // } catch( Exception $e) {
    //   Mage::helper("izberg")->log("An error occurs on magento module during merchant_order_confirmed webhook: " . $e->getMessage());

    //   Mage::helper("izberg")->slack("An error occurs on magento module during merchant_order_confirmed webhook with message: " . $e->getMessage() . " and izberg app namespace is : " . Mage::getStoreConfig('izberg/izberg_cedentials/izberg_app_namespace', Mage::app()->getStore()) . " We got data " . json_encode($data) );

    //   Mage::throwException($e->getMessage());
    // }
  }

  public function merchant_order_sentAction() {
    try {
        $data = Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());
        $data = $data["data"];

        $order = Mage::getModel('sales/order')->load($data["order"]["external_id"]);
        if (!$order->getId()) {
          Mage::throwException("We did not find order from webhook with external_id: " . $data->order->external_id);
        }

        $itemQty =  $order->getItemsCollection()->count();
        $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($itemQty);
        $shipment = new Mage_Sales_Model_Order_Shipment_Api();
        $shipmentId = $shipment->create($order->getIncrementId());

        if($order->canShip())
        {
          $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_COMPLETE);
        } else {
          Mage::throwException("We try to make a shipment from webhook on an order which can not be send:" . $data->order->id);
        }

        Mage::helper("izberg")->log("We marked the order " . $order->getId() ." as complete (because order has been sent).", 7, "", "order", $order->getId());

    } catch( Exception $e) {
      Mage::helper("izberg")->log("An error occurs on magento module during merchant_order_sent webhook: " . $e->getMessage());

      Mage::helper("izberg")->slack("An error occurs on magento module during merchant_order_sent webhook with message: " . $e->getMessage() . " and izberg app namespace is : " . Mage::getStoreConfig('izberg/izberg_cedentials/izberg_app_namespace', Mage::app()->getStore()) . " We got data " . json_encode($data) );

      $this->getResponse()->setHttpResponseCode(500);
      Mage::throwException($e->getMessage());
    }
  }

  public function merchant_order_receivedAction() {
    try {
        $data = Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());
        $data = $data["data"];

        $order = Mage::getModel('sales/order')->load($data["order"]["external_id"]);
        if (!$order->getId()) {
          Mage::throwException("We did not find order from webhook with external_id: " . $data->order->external_id);
        }

        $order->setState(Mage_Sales_Model_Order::STATE_CLOSED, true)->save();
        Mage::helper("izberg")->log("We marked the order " . $order->getId() ." as closed.", 7, "", "order", $order->getId());

    } catch( Exception $e) {
      Mage::helper("izberg")->log("An error occurs on magento module during merchant_order_received webhook: " . $e->getMessage());

      Mage::helper("izberg")->slack("An error occurs on magento module during merchant_order_received webhook with message: " . $e->getMessage() . " and izberg app namespace is : " . Mage::getStoreConfig('izberg/izberg_cedentials/izberg_app_namespace', Mage::app()->getStore()) . " We got data " . json_encode($data) );

      $this->getResponse()->setHttpResponseCode(500);
      Mage::throwException($e->getMessage());
    }
  }

  public function merchant_order_cancelledAction() {
    try {
        $data = Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());
        $data = $data["data"];

        $order = Mage::getModel('sales/order')->load($data["order"]["external_id"]);
        if (!$order->getId()) {
          Mage::helper("izberg")->log("We did not find order from webhook with external_id: " . $data["order"]["external_id"]);
          Mage::throwException("We did not find order from webhook with external_id: " . $data["order"]["external_id"]);
        }

        // We can not cancel a paid order, so we just send an email
        // $order->cancel();
        $result = Mage::helper("izberg")->sendCancelOrderEmail($order);

        Mage::helper("izberg")->log("We marked the order " . $order->getId() ." as cancelled.", 7, "", "order", $order->getId());

    } catch( Exception $e) {
      Mage::helper("izberg")->log("An error occurs on magento module during merchant_order_cancelled webhook: " . $e->getMessage());

      Mage::helper("izberg")->slack("An error occurs on magento module during merchant_order_cancelled webhook with message: " . $e->getMessage() . " and izberg app namespace is : " . Mage::getStoreConfig('izberg/izberg_cedentials/izberg_app_namespace', Mage::app()->getStore()) . " We got data " . json_encode($data) );

      $this->getResponse()->setHttpResponseCode(500);
      Mage::throwException($e->getMessage());
    }
  }

}

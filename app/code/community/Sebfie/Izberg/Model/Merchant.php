<?php
class Sebfie_Izberg_Model_Merchant extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('izberg/merchant');
    }

    public static function getStatus()
    {
      return array(
        0  => Mage::helper('izberg')->__("Inactive"),
        10  => Mage::helper('izberg')->__("Active"),
        20  => Mage::helper('izberg')->__("Paused"),
        30  => Mage::helper('izberg')->__("Stopped"),
        90  => Mage::helper('izberg')->__("Deleted")
      );
    }

    public function getJsonResponse()
    {
        return json_decode($this->getCreatedFromJson());
    }

    // Return true if store_type in [1,2]
    public function toProcess(){
        $json_answer = $this->getJsonResponse();
        // We do not process merchant if its not enabled
        if (!$this->getMagentoEnabled()) return false;
        return $json_answer->store_type == 1 || $json_answer->store_type == 2 ;
    }

    public function getIzbergProducts() {
        return Mage::getModel('izberg/product')->getCollection()->addFieldToFilter("izberg_merchant_id", $this->getId());
    }

    public function getMagentoProducts() {
        $ids = array();
        foreach($this->getIzbergProducts() as $product) {
            array_push($ids, $product->getId());
        }
        return Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('entity_id', array('in' => $ids));
    }

    public function disableProducts() {
        foreach($this->getMagentoProducts() as $product) {
            Mage::getModel('catalog/product_status')->updateProductStatus($product->getId(), Mage::app()->getStore()->getId(), Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
            $product->save();
        }
    }

    public function enableProducts() {
        foreach($this->getMagentoProducts() as $product) {
            Mage::getModel('catalog/product_status')->updateProductStatus($product->getId(), Mage::app()->getStore()->getId(), Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
            $product->save();
        }
    }

    // This function is called by our batched job system
    public static function import($ids, $job)
    {
      $izberg = Mage::helper("izberg")->getIzberg();

      $merchants = Mage::getModel("izberg/merchant")->getCollection()->addFieldToFilter('merchant_id', array(
       'in' => array($ids)
      ));

      foreach($merchants as $merchant) {
        try {
          if ($merchant->toProcess()) {
              $job->addLog("We start to import products from xml for merchant with id: " . $merchant->getId());
              $job->save();

              $izberg_merchant = $izberg->get("merchant", $merchant->getIzbergMerchantId());

              // We load the XML
              $xml = $izberg_merchant->get_catalog();

              if ($xml === false) {
                $job->addLog("Error while parsing xml");
                foreach(libxml_get_errors() as $error) {
                  $job->addLog($error->message);
                }
              }

              $i = 0;
              $products = array();
              $izberg_product_ids = array();
              // We update/create merchant's products
              $job->addLog("We founded " . count($xml->product) . " products to import");

              foreach ($xml->product as $product) {
                  // We load it from the database to update it in case it already exists
                  // It will return a new instance or the existing one
                  $statuses = Sebfie_Izberg_Model_Product::getStatusAsArray();
                  $p = Mage::getModel('izberg/product')->getCollection()->addFieldToFilter('izberg_product_id', (int) $product->id)->getFirstItem();
                  $p->addData(array(
                      "izberg_product_id" => (int) $product->id,
                      "izberg_merchant_id" => $merchant->getId(),
                      "izberg_category_id" => (int) $product->category,
                      "slug" => (string) $product->slug,
                      "name" => (string) $product->name,
                      "description" => (string) $product->description,
                      "price" => (float) $product->best_offer->price,
                      "price_with_vat" => (float) $product->best_offer->price_with_vat,
                      "price_without_vat" => (float) $product->best_offer->price_without_vat,
                      "gender" => (string) $product->gender,
                      "brand" => (string) $product->brand->name,
                      "status" => $statuses[(string) $product->best_offer->status],
                      "free_shipping" => (bool) $product->best_offer->free_shipping,
                      "shipping_estimation" => (string) $product->best_offer->shipping_estimation,
                      "in_stock" => (bool) $product->best_offer->in_stock,
                      "stock" => (int) $product->best_offer->stock,
                      "created_at" => time(),
                      "created_from_json" => $product->asXML()
                  ));

                  Mage::helper("izberg")->saferSave($p);
                  array_push($izberg_product_ids, $p->getIzbergProductId());
                  $i ++;
              }

              $product_splitted = array_chunk($izberg_product_ids,(int)Mage::getStoreConfig("izberg/izberg_jobs_settings/izberg_products_by_job", Mage::app()->getStore()));
              foreach($product_splitted as $pool) {
                Sebfie_Izberg_Model_Job::enqueue_job("Sebfie_Izberg_Model_Product", "import", $pool);
              }

              $merchant->setImportedAt(time());
              $merchant->save();

              // This function will get all merchant products in our database not in this $product_splitted
              // It means they do not exist anymore, so disable it
              self::disabledUnfindProducts($merchant, $izberg_product_ids);
              $job->addLog("We enqueued jobs");
              Mage::helper("izberg")->log("We successfully imported izberg products from merchant: " . $merchant->getName());
          }
          Mage::helper("izberg")->saferSave($job);
        } catch (Exception $e) {
          $job->addMessage("We got an error while importing merchant products : " . implode(",", $ids) . " with message : " . $e->getMessage());
          $job->save();
          Mage::throwException($e->getMessage());
        }
      }
      $job->addMessage("We successfully imported merchant products : " . implode(",", $ids));
    }

    public static function disabledUnfindProducts($merchant, $izberg_product_ids)
    {
      // Get all products where ids not in $izberg_product_ids
      if (empty($izberg_product_ids)) return;
      $collection = Mage::getModel('izberg/product')->getCollection()
                              ->addFieldToFilter('izberg_product_id', array(
                                'nin' => array($izberg_product_ids)
                              ))
                              ->addFieldToFilter('izberg_merchant_id', array(
                                'eq' => $merchant->getId()
                              ));

      // We disable this products
      foreach($collection as $product) {
        foreach($product->getCatalogProducts() as $p) {
          if (!$p) continue;
          if ($p->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
            Mage::getModel('catalog/product_status')->updateProductStatus($p->getId(), Mage::app()->getStore()->getStoreId(), Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
            Mage::helper("izberg")->log("We disabled catalog product: " . $p->getId() . " for izberg product " . $product->getId() . "  because it does not exist anymore for this merchant", 7, "", "product", $product->getId());
          }
        }
      }
    }
}

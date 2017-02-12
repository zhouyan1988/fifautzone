<?php
class Sebfie_Izberg_Model_Magmi extends Mage_Core_Model_Abstract
{
  protected static $_collection;

  const IMPORT_LIMIT = 5000;

  public static function getItems()
  {
    $collection = Mage::getModel("izberg/import")->getCollection();
    $collection->getSelect()->order('import_id ASC');
    // For performance issue, only load this fields !
    $collection->addFieldToSelect('import_id');
    $collection->addFieldToSelect('count');

    $total = 0;
    $final = array();

    foreach($collection as $item) {
	  $item = Mage::getModel("izberg/import")->load($item->getId());
      if ($total > self::IMPORT_LIMIT) break;
      array_push($final, $item);
      $total+= count($item->getToImport()["product"]);
    }
    self::$_collection = $final;
    return self::$_collection;
  }

  public static function reformatData()
  {
    $collection = self::getItems();
    $product_data = array();
    $product_extradata = array();

    foreach($collection as $item) {
      $data = $item->getToImport();
      if ($data === false) continue;
      $product_data = array_merge($product_data, $data["product"]);
      $product_extradata = array_merge($product_extradata, $data["extra_data"]);
    }
    return array($product_data, $product_extradata);
  }

  // This function is called by our batched job system
  public static function import()
  {
    set_time_limit(0);

    if (Mage::helper("izberg/magmi")->isRunning()) {
      Mage::helper("izberg/magmi")->log("Magmi was running, waiting my turn", Sebfie_Izberg_Model_Magmi_Log::TYPE_DEBUG);
      return false;
    }

    Mage::helper("izberg/magmi")->log("Start to import in magmi", Sebfie_Izberg_Model_Magmi_Log::TYPE_DEBUG);

    $data = self::reformatData();

    $arrayToImport = $data[0];
    $arrayOfExtraData = $data[1];
    $currentTimestamp = Mage::getModel('core/date')->timestamp(time()); //Magento's timestamp function makes a usage of timezone and converts it to timestamp
    $file_name = date('Y-m-d_H', $currentTimestamp);

    if (count($arrayToImport) == 0) {
      Mage::helper("izberg/magmi")->log("Nothing to import in magmi", Sebfie_Izberg_Model_Magmi_Log::TYPE_DEBUG);
      return;
    }
    Mage::helper("izberg/magmi")->log("Trying to import " . count($arrayToImport) . " products", Sebfie_Izberg_Model_Magmi_Log::TYPE_DEBUG);

    // We link products to import with future imported data. Why ?
    // Because if magmi crash, we want to keep the link between skus dans izberg_product
    foreach($arrayToImport as $product_data) {
      $sku = $product_data["sku"];
      $extra_data = $arrayOfExtraData[$sku];
      $izberg_product = Mage::getModel("izberg/product")->getCollection()->addFieldToFilter("izberg_product_id", $extra_data["izberg_product_id"])->getFirstItem();
      $izberg_product->addCatalogProduct($sku, $extra_data["best_offer_id"], $extra_data["variation_id"]);

      $izberg_product->setImportedAt(time());
      $izberg_product->save();
    }

    $file = Mage::helper("izberg/magmi")->saveCSVToImport($arrayToImport, $file_name);
    Mage::helper("izberg/magmi")->log("CSV File generated : " . $file);
    $result = Mage::helper("izberg/magmi")->sendToMagmi($file, Sebfie_Izberg_Model_Magmi_Log::TYPE_DEBUG);

    Mage::helper("izberg/magmi")->log("Sended request to magmi", Sebfie_Izberg_Model_Magmi_Log::TYPE_DEBUG);

    // We remove imported item
    foreach(self::$_collection as $item) {
        $item->delete();
    }
  }

}

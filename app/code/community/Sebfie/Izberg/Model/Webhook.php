<?php
class Sebfie_Izberg_Model_Webhook extends Mage_Core_Model_Abstract
{

  // This function is called by our batched job system
  public static function product_offer_updated($id, $job)
  {
      $izberg_product = Mage::getModel('izberg/product')->getCollection()->addFieldToFilter('izberg_product_id', $id)->getFirstItem();

      $izberg = Mage::helper("izberg")->getIzberg(array("force_admin" => true));
      $xml = $izberg->get("product", $id, null, 'Accept: application/xml');

      $job->addMessage("We start to updated product " . $izberg_product->getId() . "/" . $izberg_product->getName() . " from a webhook");

      $merchant_id = (int) $xml->best_offer->merchant->id;
      $merchant = Mage::getModel('izberg/merchant')->getCollection()->addFieldToFilter("izberg_merchant_id", (int) $xml->best_offer->merchant->id)->getFirstItem();

      $createdMerchant = false;

      if ($merchant_id == 0) {
          Mage::helper("izberg")->log("From product_offer_updated we did not find merchant_id, issue with XML returned by the server?",2);
	  	    return;
      }

      // If the merchant does not exists we create it, then ignore webhook
      if(!$merchant->getId()) {
        $merchant = $izberg->get("merchant", (int) $xml->best_offer->merchant->id);
        Mage::helper("izberg")->manageMerchantsFromAPIResponse(array($merchant));
        $merchant = Mage::getModel('izberg/merchant')->getCollection()->addFieldToFilter("izberg_merchant_id", (int) $xml->best_offer->merchant->id)->getFirstItem();
        $merchant->setToImport(false);
        $merchant->save();
        $createdMerchant = true;
        $job->addMessage("We just created merchant");
        return;
      }

      $izberg_product_new = $izberg_product->isObjectNew();

      $izberg_product->addData(array(
        "izberg_product_id" => (int) $xml->id,
        "izberg_merchant_id" => (int) $merchant->getId(),
        "izberg_category_id" => (int) $xml->category,
        "slug" => (string) $xml->slug,
        "name" => (string) $xml->name,
        "description" => (string) $xml->description,
        "price" => (float) $xml->best_offer->price,
        "price_with_vat" => (float) $xml->best_offer->price_with_vat,
        "price_without_vat" => (float) $xml->best_offer->price_without_vat,
        // "gender" => (string) $xml->gender,
        "brand" => (string) $xml->brand->name,
        "status" => (string) $xml->best_offer->status,
        "free_shipping" => (bool) $xml->best_offer->free_shipping,
        "shipping_estimation" => (string) $xml->best_offer->shipping_estimation,
        "in_stock" => (bool) $xml->best_offer->in_stock,
        "stock" => (int) $xml->best_offer->stock,
        "created_at" => time(),
        "created_from_json" => $xml->asXML()
      ));
      $izberg_product->save();

	     // Finishing by import product if we already imported it
      if (!$izberg_product_new && $izberg_product->getImportedAt()) {
        $izberg_product->importInMagentoDb();
        $job->addMessage("Product successfully created/updated");
      } else {
        $job->addMessage("Product webhook ignored");
      }

      $job->addMessage("Webhook successfuly treated");
  }

}

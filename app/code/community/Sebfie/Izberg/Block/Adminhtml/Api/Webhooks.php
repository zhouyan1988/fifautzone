<?php
class Sebfie_Izberg_Block_Adminhtml_Api_Webhooks extends Mage_Adminhtml_Block_Template
{

  public function getProductOfferUpdatedUrl()
  {
    return Mage::getUrl('izberg/api/product_offer_updated');
  }

  public function getProductOfferCreatedUrl()
  {
    return Mage::getUrl('izberg/api/product_offer_created');
  }

  public function getMerchantOrderConfirmedUrl()
  {
    return Mage::getUrl('izberg/api/merchant_order_confirmed');
  }

  public function getMerchantOrderSentUrl()
  {
    return Mage::getUrl('izberg/api/merchant_order_sent');
  }

  public function getMerchantOrderReceivedUrl()
  {
    return Mage::getUrl('izberg/api/merchant_order_received');
  }

  public function getMerchantOrderCancelledUrl()
  {
    return Mage::getUrl('izberg/api/merchant_order_cancelled');
  }

  public function getMerchantAvailableUrl()
  {
    return Mage::getUrl('izberg/api/new_merchant_available');
  }

  public function getAutomaticWebhookUrl()
  {
    return Mage::helper("adminhtml")->getUrl("izberg/adminhtml_help/createWebhooks");
  }

}

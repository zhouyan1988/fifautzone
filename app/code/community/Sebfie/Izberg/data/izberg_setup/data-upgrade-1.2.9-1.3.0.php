<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
if (!Mage::helper("izberg")->isInstalled()) return;

$izberg = Mage::helper("izberg")->getIzberg(array("force_admin" => true));
$webhooks = $izberg->get_list("webhook");

foreach (Sebfie_Izberg_Helper_Data::$IZBERG_WEBHOOK_EVENTS as $izberg_event) {
    $url = Mage::getUrl("izberg/api/$izberg_event");
    if (!Mage::helper("izberg")->getWebhook($webhooks, $url, $izberg_event)) {
        $params = array(
          "url" => $url,
          "event" => $izberg_event,
          "status" => "active",
          "comment" => "Webhook created automatically from magento",
          "label" => $izberg_event . " from magento " . Mage::getBaseUrl()
        );
        if ($izberg_event == "product_offer_updated") {
          $params["aggregation_delay"] = 5 * 60; // We aggregate by 5 minutes
          $params["max_trigger_aggregation"] = 10; // We aggregate by 10 webhooks
        }
        $izberg->create("webhook", $params);
    }
}

$installer->endSetup();

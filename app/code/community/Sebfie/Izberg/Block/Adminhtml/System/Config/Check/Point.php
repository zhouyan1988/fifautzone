<?php

class Sebfie_Izberg_Block_Adminhtml_System_Config_Check_Point extends Mage_Adminhtml_Block_Template {

	protected $_element;

	protected $_helper;

  public function __construct() {
  	$this->setTemplate('izberg/check/point.phtml');
  	$this->_helper = Mage::helper('izberg');
  	parent::_construct();
  }

  public function getVersion() {
  	return $this->_helper->getExtensionVersion();
  }

	public function hasWebhookConfigured() {
		if (!Mage::helper("izberg")->isInstalled()) return false;

		$result = true;
		foreach(Sebfie_Izberg_Helper_Data::$IZBERG_WEBHOOK_EVENTS as $event) {
			$izberg = Mage::helper("izberg")->getIzberg();
			$url = Mage::getUrl("izberg/api/$event");
      $webhooks = $izberg->get_list("webhook", array(
				"event" => $event,
				"status" => "active",
				"url" => $url
			));

			$webhook = Mage::helper("izberg")->getWebhook($webhooks, $url, $event);
			if (!$webhook) {
				$result = false;
			}
		}
		return $result;
	}

}

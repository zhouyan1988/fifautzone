<?php
class Sebfie_Izberg_Adminhtml_HelpController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('izberg/api_webhooks')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Api webhooks'), Mage::helper('adminhtml')->__('Api webhooks'));
        return $this;
    }

    public function tutorialAction() {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('core/template')->setTemplate("izberg/help/tutorial.phtml"));
        $this->renderLayout();
    }

    public function aboutAction() {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('core/template')->setTemplate("izberg/help/about.phtml"));
        $this->renderLayout();
    }

    public function createWebhooksAction()
    {
        $izberg = Mage::helper("izberg")->getIzberg(array("force_admin" => true));
        $webhooks = $izberg->get_list("webhook");

        $created = 0;
        $errors = array();
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
                $response = $izberg->create("webhook", $params);
                if (isset($response->errors)) {
                  array_push($errors, $response->errors[0]);
                } else {
                  $created++;
                }
            }
        }

        if ($created > 0 && count($errors) == 0) {
          Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__("We successfully created $created webhooks"));
        } else if (count($errors) > 0) {
          Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__("Error while creating webhooks: " . implode($errors, ", ")));
        } else {
          Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__("Webhooks already created"));
        }


        $this->_redirect('*/adminhtml_api/webhooks');
    }

    public function magmiAction()
    {
      Sebfie_Izberg_Model_Magmi::import();
      Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__("L'import a magmi a bien été envoyé"));
      $this->_redirectReferer();
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('izberg/help');
    }
}

<?php
class Sebfie_Izberg_Adminhtml_ApiController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('izberg/api_webhooks')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Api webhooks'), Mage::helper('adminhtml')->__('Api webhooks'));
        return $this;
    }

    public function webhooksAction() {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('izberg/adminhtml_api_webhooks')->setTemplate("izberg/api/web_hooks.phtml"));
        $this->renderLayout();
    }

    public function logsAction() {
      $this->_initAction();
      $this->_addContent($this->getLayout()->createBlock('izberg/adminhtml_log'));
      $this->renderLayout();
    }

    public function magmilogsAction() {
      $this->_initAction();
      $this->_addContent($this->getLayout()->createBlock('izberg/adminhtml_magmi_log'));
      $this->renderLayout();
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('izberg/matching/matching_api_webhooks');
    }
}

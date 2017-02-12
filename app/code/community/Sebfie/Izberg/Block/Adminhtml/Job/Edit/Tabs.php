<?php
class Sebfie_Izberg_Block_Adminhtml_Job_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
   public function __construct()
   {
        parent::__construct();
        $this->setId('job_tabs');
        $this->setDestElementId('content');
        $this->setTitle(Mage::helper('izberg')->__('Informations about the job'));
    }
    protected function _beforeToHtml()
    {
        $this->addTab('merchant_show', array(
                 'label' => Mage::helper('izberg')->__('Information'),
                 'title' => Mage::helper('izberg')->__('Information'),
                 'content' => $this->getLayout()->createBlock('izberg/adminhtml_job_edit_tab_show')->setTemplate("izberg/job/show.phtml")->toHtml()
       ));
       $this->addTab('merchant_messages', array(
                'label' => Mage::helper('izberg')->__('Messages'),
                'title' => Mage::helper('izberg')->__('Messages'),
                'content' => $this->getLayout()->createBlock('izberg/adminhtml_job_edit_tab_show')->setTemplate("izberg/job/messages.phtml")->toHtml()
      ));
      $this->addTab('merchant_logs', array(
               'label' => Mage::helper('izberg')->__('Logs'),
               'title' => Mage::helper('izberg')->__('Logs'),
               'content' => $this->getLayout()->createBlock('izberg/adminhtml_job_edit_tab_show')->setTemplate("izberg/job/logs.phtml")->toHtml()
     ));
       return parent::_beforeToHtml();
  }
}

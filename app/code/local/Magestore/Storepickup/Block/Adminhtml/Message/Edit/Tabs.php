<?php

class Magestore_Storepickup_Block_Adminhtml_Message_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('storepickup_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('storepickup')->__('Store Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('storepickup')->__('General Information'),
          'title'     => Mage::helper('storepickup')->__('General Information'),
          'content'   => $this->getLayout()->createBlock('storepickup/adminhtml_message_edit_tab_form')->toHtml(),
      ));
       return parent::_beforeToHtml();
  }
}
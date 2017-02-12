  <?php
  class Sebfie_Izberg_Block_Adminhtml_Merchant_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
  {
     public function __construct()
     {
          parent::__construct();
          $this->setId('merchant_tabs');
          $this->setDestElementId('content');
          $this->setTitle(Mage::helper('izberg')->__('Informations about the merchant'));
      }
      protected function _beforeToHtml()
      {
          $this->addTab('merchant_show', array(
                   'label' => Mage::helper('izberg')->__('Merchant information'),
                   'title' => Mage::helper('izberg')->__('Merchant information'),
                   'content' => $this->getLayout()->createBlock('izberg/adminhtml_merchant_edit_tab_show')->setTemplate("izberg/merchant/show.phtml")->toHtml()
         ));
         return parent::_beforeToHtml();
    }
}

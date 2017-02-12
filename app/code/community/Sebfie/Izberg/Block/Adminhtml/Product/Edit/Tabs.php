  <?php
  class Sebfie_Izberg_Block_Adminhtml_Product_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
  {
     public function __construct()
     {
          parent::__construct();
          $this->setId('product_tabs');
          $this->setDestElementId('edit_form');
          $this->setTitle(Mage::helper('izberg')->__('Informations about the product'));
      }
      protected function _beforeToHtml()
      {
          $this->addTab('product_show', array(
                   'label' => Mage::helper('izberg')->__('Product information'),
                   'title' => Mage::helper('izberg')->__('Product information'),
                   'content' => $this->getLayout()->createBlock('izberg/adminhtml_product_edit_tab_show')->setTemplate("izberg/product/show.phtml")->toHtml()
         ));
         $this->addTab('product_form', array(
                   'label' => 'Edit product',
                   'title' => 'Edit product',
                   'content' => $this->getLayout()
               ->createBlock('izberg/adminhtml_product_edit_tab_form')
               ->toHtml()
         ));
         return parent::_beforeToHtml();
    }
}

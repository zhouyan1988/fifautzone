<?php
class Sebfie_Izberg_Block_Adminhtml_Product_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
  /**
  * Constructor
  */
  public function __construct()
  {
    parent::__construct();
    $this->_blockGroup = 'izberg';
    $this->_controller = 'adminhtml_product';
    $this->_headerText = Mage::helper('izberg')->__('Edit Product');
    $this->_removeButton('delete');
    $this->_removeButton('reset');
  }

  public function _prepareLayout()
  {
    $head = $this->getLayout()->getBlock('head');
    $head->addJs('izberg/validator.js');

    return parent::_prepareLayout();
  }


}

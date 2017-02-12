<?php

class Sebfie_Izberg_Block_Adminhtml_Product extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_controller = 'adminhtml_product';
        $this->_blockGroup = 'izberg';
        $this->_headerText = Mage::helper('izberg')->__('Product Manager');
        $this->_addButtonLabel = Mage::helper('izberg')->__('Add Product');

        parent::__construct();
        $this->_removeButton('add');
    }

}
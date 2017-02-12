<?php

class Sebfie_Izberg_Block_Adminhtml_Order extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_controller = 'adminhtml_order';
        $this->_blockGroup = 'izberg';
        $this->_headerText = Mage::helper('izberg')->__('View orders');

        parent::__construct();
        $this->_removeButton('add');
    }

}

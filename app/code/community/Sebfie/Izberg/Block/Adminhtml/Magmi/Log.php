<?php

class Sebfie_Izberg_Block_Adminhtml_Magmi_Log extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_controller = 'adminhtml_magmi_log';
        $this->_blockGroup = 'izberg';
        $this->_headerText = Mage::helper('izberg')->__('Magmi log Manager');

        parent::__construct();
        $this->_removeButton('add');
    }

}

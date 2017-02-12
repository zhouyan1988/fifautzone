<?php

class Sebfie_Izberg_Block_Adminhtml_Log extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_controller = 'adminhtml_log';
        $this->_blockGroup = 'izberg';
        $this->_headerText = Mage::helper('izberg')->__('Log Manager');

        parent::__construct();
        $this->_removeButton('add');
    }

}

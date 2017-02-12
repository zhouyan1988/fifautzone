<?php

class Sebfie_Izberg_Block_Adminhtml_Job extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_controller = 'adminhtml_job';
        $this->_blockGroup = 'izberg';
        $this->_headerText = Mage::helper('izberg')->__('Job Manager');
        $this->_addButtonLabel = Mage::helper('izberg')->__('Add Job');

        parent::__construct();
        $this->_removeButton('add');
    }
    

}

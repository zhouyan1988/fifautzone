<?php

class Sebfie_Izberg_Block_Adminhtml_Merchant extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_controller = 'adminhtml_merchant';
        $this->_blockGroup = 'izberg';
        $this->_headerText = Mage::helper('izberg')->__('Merchant Manager');
        $this->_addButtonLabel = Mage::helper('izberg')->__('Add Merchant');

        $this->_addButton('import_from_izberg', array(
            'label' => Mage::helper('izberg')->__('Import merchants from izberg'),
            'onclick' => "batch_process('offset0')"
        ));

        parent::__construct();
        $this->_removeButton('add');

        $this->setTemplate('izberg/merchant/grid.phtml');
    }


}

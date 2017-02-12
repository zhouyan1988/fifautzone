<?php

class Sebfie_Izberg_Block_Adminhtml_Magmi_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('logsGrid');
        $this->setDefaultSort('log_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('izberg/magmi_log')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('log_id', array(
          'header'    => Mage::helper('izberg')->__('ID'),
          'align'     =>'right',
          'width'     => '10px',
          'index'     => 'log_id'
        ));

        $this->addColumn('message', array(
          'header'    => Mage::helper('izberg')->__('Message'),
          'index'     => 'message'
        ));

        $this->addColumn('extra_info', array(
          'header'    => Mage::helper('izberg')->__('Extra info'),
          'index'     => 'extra_info'
        ));

        $this->addColumn('scope', array(
          'header'    => Mage::helper('izberg')->__('Scope'),
          'index'     => 'scope',
          "type"      => 'options',
          "options" => array(
            "catalog_product" => Mage::helper('izberg')->__('Magento product'),
            "" => "No"
          )
        ));

        $this->addColumn('entity_id', array(
          'header'    => Mage::helper('izberg')->__('Entity id'),
          'index'     => 'entity_id',
          "type"      => 'text'
        ));

        $this->addColumn('level', array(
          'header'    => Mage::helper('izberg')->__('Level'),
          'index'     => 'level',
          "type"      => 'options',
          "options"   => Sebfie_Izberg_Model_Magmi_Log::getLevelsAsArray()
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('izberg')->__('Created at'),
            'width'     => '150px',
            'index'     => 'created_at',
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return "";
    }
}

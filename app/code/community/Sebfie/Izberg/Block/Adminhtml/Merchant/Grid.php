<?php

class Sebfie_Izberg_Block_Adminhtml_Merchant_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        // WARNING => DO NOT CHANGE THE GRID NAME, GENERATE JS FUNCTION IS OVERWRITE USING THIS NAME
        $this->setId('merchantsGrid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('merchant_id');
        $this->getMassactionBlock()->setFormFieldName('merchant_ids');
        // $this->getMassactionBlock()->setUseAjax(true);
        $this->getMassactionBlock()->addItem('import_product', array(
            'label'=> Mage::helper('izberg')->__('Import products'),
            'url'  => $this->getUrl('*/*/massImportProduct')
        ));
        $this->getMassactionBlock()->addItem('enable_merchant', array(
            'label'=> Mage::helper('izberg')->__('Enable merchants'),
            'url'  => $this->getUrl('*/*/massEnable')
        ));
        $this->getMassactionBlock()->addItem('disable_merchant', array(
            'label'=> Mage::helper('izberg')->__('Disable merchants'),
            'url'  => $this->getUrl('*/*/massDisable')
        ));
        return $this;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('izberg/merchant')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('merchant_id', array(
          'header'    => Mage::helper('izberg')->__('ID'),
          'align'     =>'right',
          'width'     => '10px',
          'index'     => 'merchant_id'
        ));

        $this->addColumn('izberg_merchant_id', array(
          'header'    => Mage::helper('izberg')->__('ID on izberg'),
          'align'     =>'right',
          'index'     => 'izberg_merchant_id'
        ));

        $this->addColumn('name', array(
          'header'    => Mage::helper('izberg')->__('Name'),
          'index'     => 'name'
        ));

        $this->addColumn('description', array(
          'header'    => Mage::helper('izberg')->__('Description'),
          'type'      => 'text',
          'index'     => 'description'
        ));

        $this->addColumn('default_currency', array(
            'header'    => Mage::helper('izberg')->__('Default currency'),
            'index'     => 'default_currency',
        ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('izberg')->__('Status'),
            'index'     => 'status',
            "type"      => 'options',
            "options"   => Sebfie_Izberg_Model_Merchant::getStatus()
        ));

        $this->addColumn('magento_enabled', array(
            'header'    => Mage::helper('izberg')->__('Magento enabled'),
            'index'     => 'magento_enabled',
            "type"      => 'options',
            'options'   => array('1' => Mage::helper('izberg')->__('Yes'), '0' => Mage::helper('izberg')->__('No'))
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('izberg')->__('Created at'),
            'width'     => '150px',
            'index'     => 'created_at',
        ));

        $this->addColumn('imported_at', array(
            'header'    => Mage::helper('izberg')->__('Imported at'),
            'width'     => '150px',
            'index'     => 'imported_at',
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}

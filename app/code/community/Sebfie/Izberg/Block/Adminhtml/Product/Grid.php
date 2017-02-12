<?php

class Sebfie_Izberg_Block_Adminhtml_Product_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('productsGrid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _getStore()
    {
      $storeId = (int) $this->getRequest()->getParam('store', 0);
      return Mage::app()->getStore($storeId);
    }

    protected function _prepareCollection()
    {
        $store = $this->_getStore();
        $collection = Mage::getModel('izberg/product')->getCollection();
        $collection->getSelect()
          ->joinInner(
          array('im' => 'izberg_merchant'),
          'main_table.izberg_merchant_id = im.merchant_id',
          array(
            "merchant" => 'im.name'
          )
        );

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('product_id');
        $this->getMassactionBlock()->setFormFieldName('product_ids');
        // $this->getMassactionBlock()->setUseAjax(true);
        $this->getMassactionBlock()->addItem('disabled_product_import', array(
            'label'=> Mage::helper('izberg')->__('Disable product import'),
            'url'  => $this->getUrl('*/*/massDisableProductImport'),
            'confirm' => Mage::helper('izberg')->__('Are you sure? These products will not be imported into your magento database')
        ));

        $this->getMassactionBlock()->addItem('enable_product_import', array(
            'label'=> Mage::helper('izberg')->__('Enable product import'),
            'url'  => $this->getUrl('*/*/massEnableProductImport'),
            'confirm' => Mage::helper('izberg')->__('Are you sure? It can take some time to import product in database. Please be patient...')
        ));

        $this->getMassactionBlock()->addItem('import', array(
          'label'=> Mage::helper('izberg')->__('Reimport'),
          'url'  => $this->getUrl('*/*/massReimport')
        ));

        $this->getMassactionBlock()->addItem('import_force_gender', array(
          'label'=> Mage::helper('izberg')->__('Force gender attribute'),
          'url'  => $this->getUrl('*/*/massSaveForceGenderAttribute'),
          'additional' => array(
            'visibility' => array(
                 'name' => 'gender',
                 'type' => 'select',
                 'class' => 'required-entry',
                 'label' => Mage::helper('izberg')->__('Value'),
                 'values' => Sebfie_Izberg_Helper_Data::$IZBERG_GENDERS
            )
          )
        ));

        $this->getMassactionBlock()->addItem('import_force_categories', array(
          'label'=> Mage::helper('izberg')->__('Lock categories'),
          'url'  => $this->getUrl('*/*/massSaveForceCategoriesAttribute')
        ));

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
          'header'    => Mage::helper('izberg')->__('ID'),
          'align'     =>'right',
          'width'     => '10px',
          'index'     => 'product_id'
        ));

        $this->addColumn('merchant', array(
          'header'    => Mage::helper('izberg')->__('Merchant'),
          'index'     => 'merchant',
          'filter_index' => 'im.name'
        ));

        $this->addColumn('brand', array(
          'header'    => Mage::helper('izberg')->__('Brand'),
          'index'     => 'brand'
        ));

        $this->addColumn('name', array(
          'header'    => Mage::helper('izberg')->__('Name'),
          'index'     => 'name',
          'filter_index' => 'main_table.name'
        ));


        $this->addColumn('price_with_vat', array(
          'header'    => Mage::helper('izberg')->__('Price'),
          'index'     => 'price_with_vat'
        ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('izberg')->__('Status'),
            'index'     => 'status',
            "type"      => 'options',
            "options"   => Sebfie_Izberg_Model_Product::getStatusAsArray()
        ));

        $this->addColumn('stock', array(
          'header'    => Mage::helper('izberg')->__('Stock'),
          'index'     => 'stock'
        ));

        $this->addColumn('enabled_for_import', array(
            'header'    => Mage::helper('izberg')->__('Enabled for import'),
            'width'     => '150px',
            'index'     => 'enabled_for_import',
            "type"      => 'options',
            'options' => array('1' => Mage::helper('izberg')->__('Yes'), '0' => Mage::helper('izberg')->__('No'))
        ));

        $this->addColumn('izberg_product_id', array(
          'header'    => Mage::helper('izberg')->__('Izberg product id'),
          'width'     => '150px',
          'index'     => 'izberg_product_id',
        ));

        $this->addColumn('force_attributes', array(
          'header'    => Mage::helper('izberg')->__('Force attributes values'),
          'index'     => 'force_attributes',
        ));

        $this->addColumn('match_category', array(
            'header'    => Mage::helper('izberg')->__('Match category'),
            'width'     => '150px',
            "type"      => 'options',
            'index'     => 'match_category',
            'options' => array('1' => Mage::helper('izberg')->__('Yes'), '0' => Mage::helper('izberg')->__('No'))
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

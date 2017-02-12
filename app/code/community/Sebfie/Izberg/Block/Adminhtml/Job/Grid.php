<?php

class Sebfie_Izberg_Block_Adminhtml_Job_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('jobsGrid');
        $this->setDefaultSort('job_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('izberg/job')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('job_id');
        $this->getMassactionBlock()->setFormFieldName('job_ids');
        // $this->getMassactionBlock()->setUseAjax(true);
        $this->getMassactionBlock()->addItem('force_run', array(
            'label'=> Mage::helper('izberg')->__('Force run'),
            'url'  => $this->getUrl('*/*/massJobRun'),
            'confirm' => Mage::helper('izberg')->__('Are you sure? It can take some time to run this jobs. Please be patient...')
        ));
        $this->getMassactionBlock()->addItem('delete', array(
            'label'=> Mage::helper('izberg')->__('Delete'),
            'url'  => $this->getUrl('*/*/massDelete'),
        ));

        $options = Sebfie_Izberg_Model_Job::getStatusAsArray();
        $this->getMassactionBlock()->addItem('changestatus', array(
          'label'=> Mage::helper('izberg')->__('Change status'),
          'url'  => $this->getUrl('*/*/massChangestatus', array('_current'=>true)),
          'additional' => array(
            'visibility' => array(
              'name' => 'status',
              'type' => 'select',
              'class' => 'required-entry',
              'label' => Mage::helper('izberg')->__('Status'),
              'values' => $options
              )
            )
          )
        );
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('job_id', array(
          'header'    => Mage::helper('izberg')->__('ID'),
          'align'     =>'right',
          'width'     => '10px',
          'index'     => 'job_id'
        ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('izberg')->__('Status'),
            'index'     => 'status',
            "type"      => 'options',
            "options"   => Sebfie_Izberg_Model_Job::getStatusAsArray()
        ));

        $this->addColumn('magento_model_method', array(
          'header'    => Mage::helper('izberg')->__('Magento model method'),
          'index'     => 'magento_model_method'
        ));

        $this->addColumn('magento_model', array(
          'header'    => Mage::helper('izberg')->__('Magento model'),
          'index'     => 'magento_model'
        ));

        $this->addColumn('params', array(
          'header'    => Mage::helper('izberg')->__('Params'),
          'index'     => 'params',
          'renderer'  => 'Sebfie_Izberg_Block_Adminhtml_Job_Renderer_Params'
        ));

        $this->addColumn('duration', array(
          'header'    => Mage::helper('izberg')->__('Duration (sec)'),
          'index'     => 'duration'
        ));

        $this->addColumn('retries_count', array(
          'header'    => Mage::helper('izberg')->__('Retries done'),
          'index'     => 'retries_count'
        ));

        $this->addColumn('retryable', array(
          'header'    => Mage::helper('izberg')->__('Retryable'),
          'index'     => 'retryable'
        ));

        $this->addColumn('updated_at', array(
            'header'    => Mage::helper('izberg')->__('Updated at'),
            'width'     => '150px',
            'index'     => 'updated_at',
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('izberg')->__('Created at'),
            'width'     => '150px',
            'index'     => 'created_at',
        ));

        $this->addColumn('last_run_at', array(
            'header'    => Mage::helper('izberg')->__('Last run at'),
            'width'     => '150px',
            'index'     => 'last_run_at',
        ));
        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/view', array('id' => $row->getId()));
    }
}

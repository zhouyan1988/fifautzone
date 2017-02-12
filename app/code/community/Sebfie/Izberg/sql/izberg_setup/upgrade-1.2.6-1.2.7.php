<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn(
  $installer->getTable('izberg/category'),
  'gender',
  array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => '32k',
    'comment'   => 'Gender to match for categories matching'
  )
);

$installer->endSetup();

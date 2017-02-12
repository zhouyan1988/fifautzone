<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();


$installer->getConnection()->modifyColumn(
  $installer->getTable('izberg/product'),
  'created_from_json',
  array(
    'type'    => Varien_Db_Ddl_Table::TYPE_BLOB,
    'length'    => '2M'
  )
);

$installer->getConnection()->addColumn($installer->getTable('izberg/job'), 'reenqued_count', array(
  'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
  'nullable'  => true,
  'comment' => 'Reenqued count'
));

$installer->endSetup();

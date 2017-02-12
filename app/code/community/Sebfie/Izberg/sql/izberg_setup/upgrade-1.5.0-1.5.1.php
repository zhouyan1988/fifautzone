<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('izberg/product'), 'force_attributes', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'comment' => 'Force product attributes values'
));


$installer->getConnection()->addColumn($installer->getTable('izberg/product'), 'updated_at', array(
    'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
    'comment' => 'Updated at field'
));


$installer->getConnection()->modifyColumn(
$installer->getTable('izberg/product_image'),
'entity_id',
array(
  'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
  'identity'  => true,
  'unsigned'  => true,
  'nullable'  => false,
  'primary'   => true
)
);

$installer->endSetup();

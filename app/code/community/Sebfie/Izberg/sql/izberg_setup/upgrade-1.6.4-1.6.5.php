<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('izberg/product'), 'imported_at', array(
  'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
  'comment' => 'Last date of import'
));

$installer->getConnection()->addColumn($installer->getTable('izberg/merchant'), 'imported_at', array(
  'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
  'comment' => 'Last date of import'
));

$installer->endSetup();

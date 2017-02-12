<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();


$installer->getConnection()->modifyColumn(
  $installer->getTable('izberg/magmi_log'),
  'entity_id',
  array(
    'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'nullable'  => true
  )
);
$installer->getConnection()->modifyColumn(
  $installer->getTable('izberg/magmi_log'),
  'log_id',
  array(
    'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'identity'  => true,
    'unsigned'  => true,
    'nullable'  => false,
    'primary'   => true
  )
);

$installer->getConnection()->modifyColumn(
  $installer->getTable('izberg/import'),
  'import_id',
  array(
    'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'identity'  => true,
    'unsigned'  => true,
    'nullable'  => false,
    'primary'   => true
  )
);
$installer->endSetup();

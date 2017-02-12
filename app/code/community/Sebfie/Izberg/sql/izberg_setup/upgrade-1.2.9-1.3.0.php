<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();


$installer->getConnection()->modifyColumn(
$installer->getTable('izberg/job'),
'logs',
array(
  'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
  'length'    => '32k'
)
);

$installer->getConnection()->modifyColumn(
$installer->getTable('izberg/job'),
'messages',
array(
  'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
  'length'    => '32k'
)
);

$installer->getConnection()->addColumn(
$installer->getTable('izberg/job'),
'duration',
array(
  'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
  'comment'   => 'Duration take by the job'
)
);

$installer->endSetup();

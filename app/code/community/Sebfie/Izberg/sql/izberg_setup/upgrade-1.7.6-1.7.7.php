<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('izberg/log'), 'scope', array(
  'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
  'nullable'  => true,
  'comment' => 'Log scope'
));

$installer->getConnection()->addColumn($installer->getTable('izberg/magmi_log'), 'scope', array(
  'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
  'nullable'  => true,
  'comment' => 'Log scope'
));

$installer->getConnection()->addColumn($installer->getTable('izberg/log'), 'entity_id', array(
  'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
  'nullable'  => true,
  'comment' => 'Log scope'
));

$installer->getConnection()->addColumn($installer->getTable('izberg/magmi_log'), 'entity_id', array(
  'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
  'nullable'  => true,
  'comment' => 'Log scope'
));

$installer->endSetup();

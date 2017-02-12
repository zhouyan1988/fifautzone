<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('izberg/import'), 'count', array(
  'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
  'comment' => 'Item count'
));

$installer->endSetup();

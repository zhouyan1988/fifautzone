<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('izberg/product'), 'match_category', array(
  'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
  'comment' => 'Boolean to know if product has found category matching'
));

$installer->endSetup();

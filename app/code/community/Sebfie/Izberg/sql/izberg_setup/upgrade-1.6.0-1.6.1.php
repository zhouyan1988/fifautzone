<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('izberg/category'), 'type', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'comment' => 'Matching type (izberg category or application category)'
));


$installer->endSetup();

<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('izberg/category'), 'breadcrumb', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => '1024',
    'comment' => 'Breadcrummb on izberg'
));


$installer->endSetup();

<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->getConnection()->changeColumn(
    $installer->getTable('izberg/product'),
    'created_from_json',
    'created_from_json',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => '64k',
        'comment'   => 'Xml used to create product'
    )
);

$installer->endSetup();

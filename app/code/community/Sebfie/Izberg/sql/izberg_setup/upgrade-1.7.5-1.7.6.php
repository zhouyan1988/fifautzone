<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$installer->getConnection()->dropForeignKey($installer->getTable('izberg/catalog_product'), $installer->getFkName('izberg/catalog_product', 'catalog_product_id', 'catalog/product', 'entity_id'));


$installer->getConnection()->changeColumn(
    $installer->getTable('izberg/catalog_product'),
    'catalog_product_id',
    'catalog_product_sku',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255
    )
);


$installer->endSetup();

<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('izberg/product_image'), 'catalog_product_sku', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'comment' => 'Magento product sku'
));


$installer->endSetup();

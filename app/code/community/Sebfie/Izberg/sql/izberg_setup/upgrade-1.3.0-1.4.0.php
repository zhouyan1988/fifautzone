<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();


/**
 * Create table 'izberg_attributes'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('izberg/product_image'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
      ), 'Product image Id')

    ->addColumn('izberg_product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    ), 'Izberg product id')

    ->addColumn('catalog_product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    ), 'Catalog product id')

    ->addColumn('izberg_image_url', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'Image url on izberg server')

    ->addColumn('magento_image_path', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'Magento image patch')

    ->addColumn('to_import', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
    ), 'To import')

    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Updated at')

    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Created at')

    ->setComment('Izberg product images');

$installer->getConnection()->createTable($table);

$installer->endSetup();

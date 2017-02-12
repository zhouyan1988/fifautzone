<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();


/**
 * Create table 'izberg_attributes'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('izberg/attribute'))
    ->addColumn('attribute_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Attribute Id')

    ->addColumn('magento_matching_attribute_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        ), 'Magento matching attribute id')

    ->addColumn('izberg_matching_attribute_code', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Izberg matching attribute code')

    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Category created_at')

    ->setComment('Izberg Attribute');

$installer->getConnection()->createTable($table);

Sebfie_Izberg_Model_Product::createConfigurableProductAttributes(null);
Sebfie_Izberg_Model_Product::createFreeShippingAttribute(null);

$installer->endSetup();
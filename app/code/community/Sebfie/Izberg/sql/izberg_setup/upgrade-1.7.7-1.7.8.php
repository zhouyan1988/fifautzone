<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();


/**
 * Create table 'izberg_attributes'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('izberg/split'))
    ->addColumn('split_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
      ), 'Split Id')

      ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
      ), 'Order id')

    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Created at')

    ->setComment('Izberg magmi logs');

$installer->getConnection()->createTable($table);

$installer->getConnection()->addForeignKey($installer->getFkName('izberg/split', 'order_id', 'sales/order', 'entity_id'),
    $installer->getTable('izberg/split'), 'order_id', $installer->getTable('sales/order'), 'entity_id',
    Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
);


$installer->endSetup();

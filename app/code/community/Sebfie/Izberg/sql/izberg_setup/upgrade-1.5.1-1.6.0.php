<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();


/**
 * Create table 'izberg_attributes'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('izberg/log'))
    ->addColumn('log_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
      ), 'Log Id')

    ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
    ), 'Log message')

    ->addColumn('extra_info', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
    ), 'Extra info')

    ->addColumn('level', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    ), 'Level information')

    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Created at')

    ->setComment('Izberg sync logs');

$installer->getConnection()->createTable($table);

$installer->endSetup();

<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();


/**
 * Create table 'izberg_attributes'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('izberg/job'))
    ->addColumn('job_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
      ), 'Job Id')

    ->addColumn('magento_model', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'Magento model')

    ->addColumn('magento_model_method', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'Magento model method')

    ->addColumn('params', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
    ), 'Params')

    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    ), 'Job status')

    ->addColumn('logs', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
    ), 'Logs')

    ->addColumn('messages', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
    ), 'Messages')

    ->addColumn('retryable', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    ), 'Retryable')

    ->addColumn('retries_count', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    ), 'Retries count')

    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Updated at')

    ->addColumn('last_run_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Last run')

    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Created at')

    ->setComment('Izberg Job');

$installer->getConnection()->createTable($table);

$installer->endSetup();

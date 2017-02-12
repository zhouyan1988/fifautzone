<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();


/**
 * Create table 'izberg_attributes'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('izberg/accesstoken'))
    ->addColumn('access_token_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
      ), 'Access token Id')

    ->addColumn('quote_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
    ), 'quote id')

    ->addColumn('user_email', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'User email')

    ->addColumn('username', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'Username')

    ->addColumn('access_token', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'Access token')

    ->addColumn('app_namespace', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'App namespace')

    ->addColumn('environment', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'Environment')

    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Updated at')

    ->addColumn('expire_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Expire at')

    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Created at')

    ->setComment('Izberg Job');

$installer->getConnection()->createTable($table);

$installer->endSetup();

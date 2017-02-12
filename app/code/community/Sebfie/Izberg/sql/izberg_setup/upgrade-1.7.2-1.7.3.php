<?php

$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('izberg/import'))
    ->addColumn('import_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
      ), 'Log Id')

    ->addColumn('to_import', Varien_Db_Ddl_Table::TYPE_BLOB, '2M', array(
      "length" => 2147483648
    ), 'Array to import')

    ->setComment('Array to import in Magmi');

$installer->getConnection()->createTable($table);

$installer->endSetup();

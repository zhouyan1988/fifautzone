<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

// Add reset password link token column
$installer->getConnection()->addColumn($installer->getTable('izberg/product'), 'enabled_for_import', array(
    'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'default' => true,
    'comment' => 'Enable to import product'
));

$installer->endSetup();

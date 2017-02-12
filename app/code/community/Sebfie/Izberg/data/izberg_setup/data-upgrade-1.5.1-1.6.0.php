<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
// Compatibility with our export module
if (!Mage::helper('core')->isModuleEnabled('Iceberg_Export')) {
  $installer->run("
    UPDATE ". $installer->getTable('core/config_data') ." SET path = replace(path, 'iceberg', 'izberg');");
}
$installer->endSetup();

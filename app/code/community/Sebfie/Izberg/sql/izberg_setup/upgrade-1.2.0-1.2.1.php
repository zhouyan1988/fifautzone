<?php

$installer = $this;
/**
 * Prepare database for install
 */
$installer->startSetup();

$status = Mage::getModel('sales/order_status');

$status->setStatus('izberg_order')->setLabel('Izberg order')
    ->assignState(Sebfie_Izberg_Helper_Data::IZBERG_ORDER_STATE) //for example, use any available existing state
    ->save();

/**
 * Prepare database after install
 */
$installer->endSetup();

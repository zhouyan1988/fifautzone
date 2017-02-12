<?php

class Sebfie_Izberg_Model_Mysql4_Merchant extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('izberg/merchant', 'merchant_id');
    }
}
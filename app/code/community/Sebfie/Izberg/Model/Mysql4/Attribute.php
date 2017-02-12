<?php

class Sebfie_Izberg_Model_Mysql4_Attribute extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('izberg/attribute', 'attribute_id');
    }
}
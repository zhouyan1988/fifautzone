<?php

class Sebfie_Izberg_Model_Mysql4_Category extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('izberg/category', 'category_id');
    }

}
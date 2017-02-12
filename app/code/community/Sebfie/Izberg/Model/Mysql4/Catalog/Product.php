<?php

class Sebfie_Izberg_Model_Mysql4_Catalog_Product extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('izberg/catalog_product', 'entity_id');
    }

}
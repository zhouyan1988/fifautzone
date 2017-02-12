<?php
class Sebfie_Izberg_Model_Mysql4_Catalog_Product_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Define resource model
     *
     */
    protected function _construct()
    {
        $this->_init('izberg/catalog_product');
    }
}


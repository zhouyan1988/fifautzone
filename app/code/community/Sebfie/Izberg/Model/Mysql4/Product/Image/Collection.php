<?php
class Sebfie_Izberg_Model_Mysql4_Product_Image_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Define resource model
     *
     */
    protected function _construct()
    {
        $this->_init('izberg/product_image');
    }
    
}

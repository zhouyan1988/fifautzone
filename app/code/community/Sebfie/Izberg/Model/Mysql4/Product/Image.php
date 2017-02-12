<?php

class Sebfie_Izberg_Model_Mysql4_Product_Image extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('izberg/product_image', 'entity_id');
    }

}

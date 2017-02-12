<?php

class Sebfie_Izberg_Model_Mysql4_Product extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('izberg/product', 'product_id');
    }

    public static function getGenders()
    {
      return array(
        "H" => "Homme",
        "F" => "Femme",
        "M" => "Unisex",
        "B" => "Boy",
        "G" => "Girl",
        "K" => "Kids"
      );
    }
}
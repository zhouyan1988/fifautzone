<?php
class Sebfie_Izberg_Model_Catalog_Product extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('izberg/catalog_product');
    }

    public function getCatalogProduct()
    {
      return Mage::getModel('catalog/product')->loadByAttribute('sku', $this->getCatalogProductSku());
    }

    public function getIzbergProduct()
    {
      return Mage::getModel('izberg/product')->load($this->getIzbergProductId());
    }

}

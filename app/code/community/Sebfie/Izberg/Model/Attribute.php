<?php
class Sebfie_Izberg_Model_Attribute extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('izberg/attribute');
    }

    public static function getIzbergAttributes()
    {
      return array(
        "color" => Mage::helper("izberg")->__("Color"),
        "size" => Mage::helper("izberg")->__("Size"),
        "description" => Mage::helper("izberg")->__("Description"),
        "merchant_name" => Mage::helper("izberg")->__("Merchant name")
      );
    }

    public static function getMagentoAttributeFromIzbergAttributeCode($code)
    {
      $izberg_attribute = Mage::getModel("izberg/attribute")->getCollection()->addFieldToFilter('izberg_matching_attribute_code', $code)->getFirstItem();
      if ($izberg_attribute->getId()) {
        $attribute = Mage::getModel('eav/entity_attribute')->load($izberg_attribute->getMagentoMatchingAttributeId());
        return Mage::getModel('eav/entity_attribute')->load($izberg_attribute->getMagentoMatchingAttributeId());
      } else {
        $entityType = Mage::getModel('catalog/product')->getResource()->getTypeId();
        return Mage::getModel('eav/entity_attribute')->loadByCode($entityType, $code);
      }
    }

    public static function getMagentoAttributeConfigFromIzbergAttributeCode($code)
    {
      $izberg_attribute = Mage::getModel("izberg/attribute")->getCollection()->addFieldToFilter('izberg_matching_attribute_code', $code)->getFirstItem();
      if ($izberg_attribute->getId()) {
        $attribute = Mage::getModel('eav/entity_attribute')->load($izberg_attribute->getMagentoMatchingAttributeId());
        return Mage::getModel('eav/config')->getAttribute('catalog_product', $attribute->getAttributeCode());
      } else {
        return Mage::getModel('eav/config')->getAttribute('catalog_product', $code);
      }
    }

}

<?php
class Sebfie_Izberg_Model_Adminhtml_Attributeset extends Mage_Core_Model_Config_Data
{
    protected function _afterSaveCommit()
    {
        if ($this->getOldValue() != $this->getValue()) {
          // We associate configurable product attributes to the new attribute set
          Sebfie_Izberg_Model_Product::createConfigurableProductAttributes($this->getValue());
          Sebfie_Izberg_Model_Product::createFreeShippingAttribute($this->getValue());
        }
    }
}

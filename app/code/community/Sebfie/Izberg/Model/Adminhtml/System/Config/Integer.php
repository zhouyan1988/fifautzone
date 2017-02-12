<?php
/**
 * Attribute set options
 *
 */
class Sebfie_Izberg_Model_Adminhtml_System_Config_Integer extends Mage_Core_Model_Config_Data
{

  public function save()
  {
      $value = $this->getValue();
      if(!is_numeric($value))
      {
          Mage::throwException(Mage::helper("izberg")->__("Import job settings should be defined as integer"));
      }

      return parent::save();
  }

}

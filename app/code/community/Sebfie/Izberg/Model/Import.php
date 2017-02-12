<?php
class Sebfie_Izberg_Model_Import extends Mage_Core_Model_Abstract
{
  public function _construct()
  {
      parent::_construct();
      $this->_init('izberg/import');
  }

  public function getToImport()
  {
    return unserialize($this->getData("to_import"));
  }
}

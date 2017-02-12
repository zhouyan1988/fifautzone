<?php
class Sebfie_Izberg_Model_Magmi_Log extends Mage_Core_Model_Abstract
{
    const TYPE_DEBUG = 0;
    const TYPE_RUN = 1;
    const TYPE_FINISHED = 2;
    const TYPE_CANCELED = 3;

    public static function getLevelsAsArray()
    {
      return array(
        0 => Mage::helper('izberg')->__("Debug"),
        1 => Mage::helper('izberg')->__("Run"),
        2 => Mage::helper('izberg')->__("Finished"),
        3 => Mage::helper('izberg')->__("Canceled")
      );
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init('izberg/magmi_log');
    }

}

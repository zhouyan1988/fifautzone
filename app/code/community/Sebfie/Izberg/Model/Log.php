<?php
class Sebfie_Izberg_Model_Log extends Mage_Core_Model_Abstract
{
    // const EMERG   = 0;  // Emergency: system is unusable
    // const ALERT   = 1;  // Alert: action must be taken immediately
    // const CRIT    = 2;  // Critical: critical conditions
    // const ERR     = 3;  // Error: error conditions
    // const WARN    = 4;  // Warning: warning conditions
    // const NOTICE  = 5;  // Notice: normal but significant condition
    // const INFO    = 6;  // Informational: informational messages
    // const DEBUG   = 7;  // Debug: debug messages

    public static function getLevelsAsArray()
    {
      return array(
        0 => Mage::helper('izberg')->__("Emergency"),
        1 => Mage::helper('izberg')->__("Alert"),
        2 => Mage::helper('izberg')->__("Critical"),
        3 => Mage::helper('izberg')->__("Error"),
        4 => Mage::helper('izberg')->__("Warning"),
        5 => Mage::helper('izberg')->__("Notice"),
        6 => Mage::helper('izberg')->__("Informational"),
        7 => Mage::helper('izberg')->__("Debug"),
        8 => Mage::helper('izberg')->__("Magmi")
      );
    }


    public function _construct()
    {
        parent::_construct();
        $this->_init('izberg/log');
    }

}

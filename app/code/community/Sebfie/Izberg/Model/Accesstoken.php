<?php
class Sebfie_Izberg_Model_Accesstoken extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('izberg/accesstoken');
    }

}

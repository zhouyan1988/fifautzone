<?php

class Magestore_Storepickup_Model_Message extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('storepickup/message');
    }
}
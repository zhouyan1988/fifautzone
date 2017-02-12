<?php

class Sebfie_Izberg_Model_Mysql4_Accesstoken extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('izberg/accesstoken', 'access_token_id');
    }

}

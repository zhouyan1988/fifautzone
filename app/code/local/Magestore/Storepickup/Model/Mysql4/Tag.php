<?php

class Magestore_Storepickup_Model_Mysql4_Tag extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('storepickup/tag', 'tag_id');
    }
}
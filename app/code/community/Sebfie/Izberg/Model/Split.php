<?php
class Sebfie_Izberg_Model_Split extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('izberg/split');
    }

    public function getOrder()
    {
      return Mage::getModel("sales/order")->load($this->getOrderId());
    }
}

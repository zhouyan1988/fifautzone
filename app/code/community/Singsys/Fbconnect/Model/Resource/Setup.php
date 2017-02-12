<?php
/**
 * @category    Development
 * @package     Singsys_Fbconnect
 * @version     1.0.0
 * @copyright   Copyright (c) 2015 SingsysShop (https://www.singsys.com)
 * @author	Singsys
 */

class Singsys_Fbconnect_Model_Resource_Setup extends Mage_Eav_Model_Entity_Setup
{
    protected $_customerAttributes = array();

    public function setCustomerAttributes($customerAttributes)
    {
        $this->_customerAttributes = $customerAttributes;

        return $this;
    }
    

    public function installCustomerAttributes()
    {        
        foreach ($this->_customerAttributes as $code => $attr) {
            $this->addAttribute('customer', $code, $attr);
        }

        return $this;
    }

 
    public function removeCustomerAttributes()
    {
        foreach ($this->_customerAttributes as $code => $attr) {
            $this->removeAttribute('customer', $code);
        }

        return $this;
    }  
}

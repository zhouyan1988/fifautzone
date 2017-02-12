<?php
/**
 * @category    Development
 * @package     Singsys_Fbconnect
 * @version     1.0.0
 * @copyright   Copyright (c) 2015 SingsysShop (https://www.singsys.com)
 * @author	Singsys
 */

class Singsys_Fbconnect_Block_Checkout extends Mage_Core_Block_Template
{
    protected $clientFacebook = null;
    protected $numEnabled = 0;
    protected $numShown = 0;    

    protected function _construct() {
        parent::_construct();
        $this->clientFacebook = Mage::getSingleton('singsys_fbconnect/facebook_client'); 
        $this->clientFacebook = Mage::getSingleton('singsys_fbconnect/facebook_client');
        if(!$this->_facebookEnabled())
            return;

        if($this->_facebookEnabled()) {
            $this->numEnabled++;
        }
        
        Mage::register('singsys_fbconnect_button_text', $this->__('Continue'));

        $this->setTemplate('singsys/fbconnect/checkout.phtml');
    }
    
    protected function _getColSet()
    {
        return 'col'.$this->numEnabled.'-set';
    }

    protected function _getCol()
    {
        return 'col-'.++$this->numShown;
    }    

    

    protected function _facebookEnabled()			
    {
        return $this->clientFacebook->isEnabled();
    }

   

}

<?php
/**
 * @category    Development
 * @package     Singsys_Fbconnect
 * @version     1.0.0
 * @copyright   Copyright (c) 2015 SingsysShop (https://www.singsys.com)
 * @author	Singsys
 */

class Singsys_Fbconnect_Block_Login extends Mage_Core_Block_Template
{
    protected $clientFacebook = null;
    protected $numEnabled = 0;
    protected $numDescShown = 0;
    protected $numButtShown = 0;

    protected function _construct() {
        parent::_construct();

       
        $this->clientFacebook = Mage::getSingleton('singsys_fbconnect/facebook_client');
        if(!$this->_facebookEnabled())
             return;

        
        if($this->_facebookEnabled()) {
            $this->numEnabled++;
        }
        Mage::register('singsys_fbconnect_button_text', $this->__('Login via Facebook'));		/*set button text on login page */

        $this->setTemplate('singsys/fbconnect/login.phtml');
    }

    protected function _getColSet()
    {
        return 'col'.$this->numEnabled.'-set';
    }

    protected function _getDescCol()
    {
        return 'col-'.++$this->numDescShown;
    }

    protected function _getButtCol()
    {
        return 'col-'.++$this->numButtShown;
    }

    protected function _facebookEnabled()
    {
        return $this->clientFacebook->isEnabled();
    }

    

}

<?php
/**
 * @category    Development
 * @package     Singsys_Fbconnect
 * @version     1.0.0
 * @copyright   Copyright (c) 2015 SingsysShop (https://www.singsys.com)
 * @author	Singsys
 */

class Singsys_Fbconnect_Block_Facebook_Button extends Mage_Core_Block_Template
{
	
    protected $client = null;
    protected $userInfo = null;
    protected $redirectUri = null;

    protected function _construct() {
	
        parent::_construct();

        $this->client = Mage::getSingleton('singsys_fbconnect/facebook_client');
        if(!($this->client->isEnabled())) {
            return;
        }
	
        $this->userInfo = Mage::registry('singsys_fbconnect_facebook_userinfo');
        
        // CSRF protection
        Mage::getSingleton('core/session')->setFacebookCsrf($csrf = md5(uniqid(rand(), TRUE)));
        $this->client->setState($csrf);
        
        if(!($redirect = Mage::getSingleton('customer/session')->getBeforeAuthUrl())) {
            $redirect = Mage::helper('core/url')->getCurrentUrl();      
        }        
        
        // Redirect uri
        Mage::getSingleton('core/session')->setFacebookRedirect($redirect);        

        $this->setTemplate('singsys/fbconnect/facebook/button.phtml');
    }

    protected function _getButtonUrl()			/* return button Url used on account page */
    {
        if(empty($this->userInfo)) {
            return $this->client->createAuthUrl();
        } else {
            return $this->getUrl('fbconnect/facebook/disconnect');
        }
    }

    protected function _getButtonText()			/* return button Text used on account page */
    {
        if(empty($this->userInfo)) {
            if(!($text = Mage::registry('singsys_fbconnect_button_text'))){
                $text = $this->__('Connect');
            }
        } else {
            $text = $this->__('Disconnect');
        }
        
        return $text;
    }
}

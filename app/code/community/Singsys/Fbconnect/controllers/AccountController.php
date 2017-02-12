<?php
/**
 * @category    Development
 * @package     Singsys_Fbconnect
 * @version     1.0.0
 * @copyright   Copyright (c) 2015 SingsysShop (https://www.singsys.com)
 * @author	Singsys
 */

class Singsys_Fbconnect_AccountController extends Mage_Core_Controller_Front_Action
{
    
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }
        
        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }    

    
    public function facebookAction()
    {        
        $userInfo = Mage::getSingleton('singsys_fbconnect/facebook_userinfo')
            ->getUserInfo();
        
        Mage::register('singsys_fbconnect_facebook_userinfo', $userInfo);
        
        $this->loadLayout();
        $this->renderLayout();
    }    
    

}

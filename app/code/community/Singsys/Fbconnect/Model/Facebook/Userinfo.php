<?php
/**
 * @category    Development
 * @package     Singsys_Fbconnect
 * @version     1.0.0
 * @copyright   Copyright (c) 2015 SingsysShop (https://www.singsys.com)
 * @author	Singsys
 */

class Singsys_Fbconnect_Model_Facebook_Userinfo
{
    protected $client = null;
    protected $userInfo = null;

    public function __construct() {
        if(!Mage::getSingleton('customer/session')->isLoggedIn())
            return;

        $this->client = Mage::getSingleton('singsys_fbconnect/facebook_client');
        if(!($this->client->isEnabled())) {
            return;
        }

        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if(($socialconnectFid = $customer->getSingsysFbconnectFid()) &&
                ($socialconnectFtoken = $customer->getSingsysFbconnectFtoken())) {
            $helper = Mage::helper('singsys_fbconnect/facebook');

            try{
                $this->client->setAccessToken($socialconnectFtoken);			
                $this->userInfo = $this->client->api(							/* load usual info of customer from facebook */
                    '/me',
                    'GET',
                    array(
                        'fields' =>
                        'id,name,first_name,last_name,link,birthday,gender,email,picture.type(large)'
                    )
                );

            } catch(FacebookOAuthException $e) {
                $helper->disconnect($customer);
                Mage::getSingleton('core/session')->addNotice($e->getMessage());
            } catch(Exception $e) {
                $helper->disconnect($customer);
                Mage::getSingleton('core/session')->addError($e->getMessage());
            }

        }
    }

    public function getUserInfo()
    {
        return $this->userInfo;
    }
}

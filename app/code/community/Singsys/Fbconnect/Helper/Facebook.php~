<?php
/**
 * @category    Development
 * @package     Singsys_Fbconnect
 * @version     1.0.0
 * @copyright   Copyright (c) 2015 SingsysShop (https://www.singsys.com)
 * @author	Singsys
 */

class Singsys_Fbconnect_Helper_Facebook extends Mage_Core_Helper_Abstract
{

    public function disconnect(Mage_Customer_Model_Customer $customer) {		/* function Disconnect from facebook */
        $client = Mage::getSingleton('singsys_fbconnect/facebook_client');
        
        try {
            $client->setAccessToken($customer->getSingsysFbconnectFtoken());
            $client->api('/me/permissions', 'DELETE');            
        } catch (Exception $e) { }
        
        $pictureFilename = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA)
                .DS
                .'singsys'
                .DS
                .'fbconnect'
                .DS
                .'facebook'
                .DS                
                .$customer->getSingsysFbconnectFid();
        
        if(file_exists($pictureFilename)) {									/* if profile picture exists, unlink this from account */
            @unlink($pictureFilename);
        }        
        
        $customer->setSingsysFbconnectFid(null)
        ->setSingsysFbconnectFtoken(null)
        ->save();   
    }
    
    public function connectByFacebookId(								   /* connect by Facebook Id in magento account */
            Mage_Customer_Model_Customer $customer,
            $facebookId,
            $token)
    {
        $customer->setSingsysFbconnectFid($facebookId)
                ->setSingsysFbconnectFtoken($token)
                ->save();
        
        Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
    }
    
    public function connectByCreatingAccount(							/* Creates the account in magento */
            $email,
            $firstName,
            $lastName,
            $facebookId,
            $token)
    {
        $customer = Mage::getModel('customer/customer');

        $customer->setEmail($email)
                ->setFirstname($firstName)
                ->setLastname($lastName)
                ->setSingsysFbconnectFid($facebookId)
                ->setSingsysFbconnectFtoken($token)
                ->setPassword($customer->generatePassword(10))
                ->save();

        $customer->setConfirmation(null);
        $customer->save();

        $customer->sendNewAccountEmail();

        Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);            

    }
    
    public function loginByCustomer(Mage_Customer_Model_Customer $customer)	/* Login to magento using facebook details, stored in magento */
    {
        if($customer->getConfirmation()) {
            $customer->setConfirmation(null);
            $customer->save();
        }

        Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);        
    }
    
    public function getCustomersByFacebookId($facebookId)	/* return collection of facebook customer, registered in magento */
    {
        $customer = Mage::getModel('customer/customer');

        $collection = $customer->getCollection()
            ->addAttributeToFilter('singsys_fbconnect_fid', $facebookId)
            ->setPageSize(1);

        if($customer->getSharingConfig()->isWebsiteScope()) {
            $collection->addAttributeToFilter(
                'website_id',
                Mage::app()->getWebsite()->getId()
            );
        }

        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            $collection->addFieldToFilter(
                'entity_id',
                array('neq' => Mage::getSingleton('customer/session')->getCustomerId())
            );
        }

        return $collection;
    }
    
    public function getCustomersByEmail($email)					/*  retrieving customer collection by email */
    {
        $customer = Mage::getModel('customer/customer');

        $collection = $customer->getCollection()
                ->addFieldToFilter('email', $email)
                ->setPageSize(1);

        if($customer->getSharingConfig()->isWebsiteScope()) {
            $collection->addAttributeToFilter(
                'website_id',
                Mage::app()->getWebsite()->getId()
            );
        }  
        
        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            $collection->addFieldToFilter(
                'entity_id',
                array('neq' => Mage::getSingleton('customer/session')->getCustomerId())
            );
        }        
        
        return $collection;
    }

    public function getProperDimensionsPictureUrl($facebookId, $pictureUrl)		/* return profile picture url */
    {
        $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)
                .'singsys'
                .'/'
                .'fbconnect'
                .'/'
                .'facebook'
                .'/'                
                .$facebookId;

        $filename = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA)
                .DS
                .'singsys'
                .DS
                .'fbconnect'
                .DS
                .'facebook'
                .DS                
                .$facebookId;

        $directory = dirname($filename);

        if (!file_exists($directory) || !is_dir($directory)) {
            if (!@mkdir($directory, 0777, true))
                return null;
        }

        if(!file_exists($filename) || 
                (file_exists($filename) && (time() - filemtime($filename) >= 3600))){
            $client = new Zend_Http_Client($pictureUrl);
            $client->setStream();
            $response = $client->request('GET');
            stream_copy_to_stream($response->getStream(), fopen($filename, 'w'));

            $imageObj = new Varien_Image($filename);
            $imageObj->constrainOnly(true);
            $imageObj->keepAspectRatio(true);
            $imageObj->keepFrame(false);
            $imageObj->resize(150, 150);
            $imageObj->save($filename);
        }
        
        return $url;
    }    
    
}

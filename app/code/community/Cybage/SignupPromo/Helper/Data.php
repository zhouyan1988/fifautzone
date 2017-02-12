<?php
/**
 * Cybage Signup Promotion Plugin 
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is available on the World Wide Web at:
 * http://opensource.org/licenses/osl-3.0.php
 * If you are unable to access it on the World Wide Web, please send an email
 * To: Support_Magento@cybage.com.  We will  send you a copy of the source file.
 *
 * @category   Signup Promotion Plugin
 * @package    Cybage_SignupPromo
 * @copyright  Copyright (c) 2014 Cybage Software Pvt. Ltd., India
 *             http://www.cybage.com/pages/centers-of-excellence/ecommerce/ecommerce.aspx
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Cybage Software Pvt. Ltd. <Support_Magento@cybage.com>
 */

class Cybage_SignupPromo_Helper_Data extends Mage_Core_Helper_Abstract {

    const XML_PATH_ENABLED        = 'signuppromo_section/signuppromo_group/active';
    const XML_PATH_SUCCESS_MSG    = 'signuppromo_section/signuppromo_group/success';
    const XML_PATH_EMAIL_TEMPLATE = 'signuppromo_section/signuppromo_group/emailtemplate';
    const XML_PATH_EMAIL_SENDER   = 'signuppromo_section/signuppromo_group/identity';
    const XML_PATH_COUPON_LENGTH  = 'signuppromo_section/signuppromo_group/couponlength';
    const XML_PATH_COUPON_PREFIX  = 'signuppromo_section/signuppromo_group/couponprefix';
        const XML_PATH_COUPON_EXPIRE  = 'signuppromo_section/signuppromo_group/couponexpire';

    public function isModEnabled(){
         return Mage::getStoreConfig(self::XML_PATH_ENABLED);
    }

    public function sendEmail($receiverEmail, $receiverName, $uniqueCode) {
        $msg = Mage::getStoreConfig(self::XML_PATH_SUCCESS_MSG);
        $storeId = Mage::app()->getStore()->getId();
        
        try{
            $vars = array('name' => $receiverName, 'coupon_codes' => $uniqueCode);
            
            $emailTemplate = Mage::getModel('core/email_template')->loadDefault(Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE))
            ->setSenderEmail(Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER, $storeId))
            ->setSenderName(Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER, $storeId))
            ->send($receiverEmail,$receiverName, $vars);
            
        }catch (Exception $e) {
            Mage::getSingleton('customer/session')->addError($e->getMessage());
            return;
        }
    }

    public function _genrateCoupon($customerId,$name,$customerGroupId,$email,$websiteId)
    {
        $model = Mage::getModel('signuppromo/signuppromo');
        $uniqueCode = array();
        $date = date('Y-m-d', Mage::getModel('core/date')->timestamp(time())); 

        $ruleCollection = Mage::getResourceModel('salesrule/rule_collection')
            ->addFieldToFilter('coupon_type', 4)
            ->addFieldToFilter('is_active', 1)
            ->addWebsiteGroupDateFilter($websiteId, $customerGroupId, $date);
        
        foreach($ruleCollection as $rule){

            $ruleId =  $rule->getRuleId();
            $uniqueCode[]= $model->addPromoCode($customerId, $ruleId);
        }

        if($uniqueCode && Mage::getStoreConfig('signuppromo_section/signuppromo_group/couponemailnotification'))
            $this->sendEmail($email, $name, $uniqueCode);
    }
}

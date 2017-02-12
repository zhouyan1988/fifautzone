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

class Cybage_SignupPromo_Model_Observer {

    const XML_PATH_SUCCESS_MSG    = 'signuppromo_section/signuppromo_group/success';
    
    public function customerRegisterSave(Varien_Event_Observer $observer) {
        $msg = Mage::getStoreConfig(self::XML_PATH_SUCCESS_MSG);
        $helper = Mage::helper('signuppromo');
        if($helper->isModEnabled()){
            $customer = $observer->getEvent()->getCustomer();
        
            if (!$customer->getId())
            return $this;
            
            $customerId = $observer->getEvent()->getCustomer()->getId();
            $email = $observer->getEvent()->getCustomer()->getEmail();
            $name  = $observer->getEvent()->getCustomer()->getFirstname();
            $customerGroupId = $customer->getGroupId();
            $websiteId = $customer->getWebsiteId();
            
            $helper->_genrateCoupon($customerId,$name,$customerGroupId,$email,$websiteId);
                        if($msg)
                        Mage::getSingleton('customer/session')->addNotice($msg);
                        
        }
    }

    public function checkSignupPromoCoupon(Varien_Event_Observer $observer) {
        $date = date('Y-m-d', Mage::getModel('core/date')->timestamp(time()));
        if(Mage::helper('signuppromo')->isModEnabled()){
            $ruleCollection = Mage::getResourceModel('salesrule/rule_collection')
                            ->addFieldToFilter('coupon_type', 4);
            foreach($ruleCollection as $rule){
                $ruleId =  $rule->getRuleId();
                $rules[] = $ruleId;
            }		
            if(in_array($observer->getEvent()->getRule()->getRuleId(), $rules)){
                $couponCustomerId = Mage::getModel('salesrule/coupon')->load($observer->getEvent()->getRule()->getCode(), 'code');
                                $customerId = $observer->getEvent()->getQuote()->getCustomer()->getId();
                if ($customerId==$couponCustomerId->getCustomerId() && $couponCustomerId->getExpirationDate() >= $date) {
                    return $this;
                }else {
                    Mage::getSingleton('checkout/session')->getQuote()
                    ->setCouponCode('')
                    ->collectTotals()
                    ->save();
                }
            }
        }
    }

    public function customerRegisterOnCheckout(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('signuppromo');
                 $msg = Mage::getStoreConfig(self::XML_PATH_SUCCESS_MSG);
        if($helper->isModEnabled()) {
            $order = $observer->getEvent()->getOrder();
            $quoteId = Mage::getModel('sales/order')->loadByIncrementId($order->getIncrementId())->getQuoteId();
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            //print_r($quote);
            $method = $quote->getData('checkout_method');

            if($method == 'register')
            {
                $customerId = $quote->getData('customer_id');
                $email = $quote->getData('customer_email');
                $name = $quote->getData('customer_firstname').' '.$quote->getData('customer_lastname');
                $customerGroupId = $quote->getData('customer_group_id');
                $helper->_genrateCoupon($customerId,$name,$customerGroupId,$email);
                if($msg)
                    Mage::getSingleton('customer/session')->addNotice($msg);
            }
        }
    }
}

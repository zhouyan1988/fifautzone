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

class Cybage_SignupPromo_Model_Rule extends Mage_SalesRule_Model_Rule
{
    const COUPON_TYPE_AUTO_CUSTOMER_SPECIFIC  = 4;

    /**
     * Retrieve coupon types
     *
     * @return array
     */
    public function getCouponTypes()
    {
        if ($this->_couponTypes === null) {
            $this->_couponTypes = array(
                Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON => Mage::helper('salesrule')->__('No Coupon'),
                Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC  => Mage::helper('salesrule')->__('Specific Coupon'),
                self::COUPON_TYPE_AUTO_CUSTOMER_SPECIFIC =>Mage::helper('salesrule')->__('Auto generated Coupons for Customer Registration'),
            );

            $transport = new Varien_Object(array(
                'coupon_types'                => $this->_couponTypes,
                'is_coupon_type_auto_visible' => false
            ));

            Mage::dispatchEvent('salesrule_rule_get_coupon_types', array('transport' => $transport));
            $this->_couponTypes = $transport->getCouponTypes();

            if ($transport->getIsCouponTypeAutoVisible()) {
                $this->_couponTypes[Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO] = Mage::helper('salesrule')->__('Auto');
            }
        }

        return $this->_couponTypes;
    }

    public function getCouponData($coupons = null)
    {
        $isLogin = Mage::getSingleton('customer/session')->isLoggedIn();
        $tableName = Mage::getSingleton("core/resource")->getTableName('salesrule/rule');

        if($coupons) {
            $collection = Mage::getResourceModel('salesrule/coupon_collection')
                ->addFieldToFilter('code',array('in'=>array($coupons)))
                ->addGeneratedCouponsFilter();
            $collection->getSelect()->join(array('t2' => $tableName),'main_table.rule_id = t2.rule_id');
        }

        if($isLogin && $coupons == null) {
            $customerId = Mage::getSingleton('customer/session')->getCustomerId();
            $customerCollection = Mage::getSingleton('customer/customer')->load($customerId);

            $collection = Mage::getResourceModel('salesrule/coupon_collection')
                ->addFieldToFilter('customer_id',$customerCollection->getId())
                ->addGeneratedCouponsFilter();
            $collection->getSelect()->join(array('t2' => $tableName),'main_table.rule_id = t2.rule_id',array('t2.name','t2.description'));
        }

        return $collection;
    }
}

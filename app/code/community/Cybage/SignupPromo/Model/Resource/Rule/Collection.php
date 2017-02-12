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

class Cybage_SignupPromo_Model_Resource_Rule_Collection extends Mage_SalesRule_Model_Resource_Rule_Collection
{
    /**
     * Filter collection by specified website, customer group, coupon code, date.
     * Filter collection to use only active rules.
     * Involved sorting by sort_order column.
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string $couponCode
     * @param string|null $now
     * @use $this->addWebsiteGroupDateFilter()
     *
     * @return Mage_SalesRule_Model_Resource_Rule_Collection
     */
    public function setValidationFilter($websiteId, $customerGroupId, $couponCode = '', $now = null)
    {
        if (!$this->getFlag('validation_filter')) {
            /* We need to overwrite joinLeft if coupon is applied */
            $this->getSelect()->reset();
            Mage_Rule_Model_Resource_Rule_Collection_Abstract::_initSelect(); // Called the parent's parent fucntion

            $this->addWebsiteGroupDateFilter($websiteId, $customerGroupId, $now);
            $select = $this->getSelect();

            if (strlen($couponCode)) {
                $select->joinLeft(
                    array('rule_coupons' => $this->getTable('salesrule/coupon')),
                    'main_table.rule_id = rule_coupons.rule_id ',
                    array('code','expiration_date')
                );

            $select->where('(main_table.coupon_type = ? ', Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON)
                ->orWhere('(main_table.coupon_type = ? AND rule_coupons.type = 0',
                    Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO)
                ->orWhere('main_table.coupon_type = ? AND main_table.use_auto_generation = 1 ' .
                    'AND rule_coupons.type = 1', Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC)
                ->orWhere('main_table.coupon_type = ? AND main_table.use_auto_generation = 0 ' .
                    'AND rule_coupons.type = 0', Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC)
                ->orWhere('main_table.coupon_type = ? AND main_table.use_auto_generation = 0 ' .
                    'AND rule_coupons.type = 1)', Cybage_SignupPromo_Model_Rule::COUPON_TYPE_AUTO_CUSTOMER_SPECIFIC)//Added this
                ->where('rule_coupons.code = ?)', $couponCode);
            } else {
                $this->addFieldToFilter('main_table.coupon_type', Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON);
            }

            $this->setOrder('sort_order', parent::SORT_ORDER_ASC);
            $this->setFlag('validation_filter', true);
        }

        return $this;
    }
}

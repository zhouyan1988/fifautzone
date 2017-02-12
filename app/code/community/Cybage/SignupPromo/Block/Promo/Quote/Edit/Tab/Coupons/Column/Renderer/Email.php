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

class Cybage_SignupPromo_Block_Promo_Quote_Edit_Tab_Coupons_Column_Renderer_Email extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    public function render(Varien_Object $row)
    {
        $value = (int)$row->getData($this->getColumn()->getIndex());
        if($value) {
            $customerCollection = Mage::getSingleton('customer/customer')->load($value);
            return Mage::helper('adminhtml')->__($customerCollection->getEmail());
        }
        return '';
    }
}

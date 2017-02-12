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

class Cybage_SignupPromo_Block_Promo_Quote_Edit_Tab_Coupons_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('couponCodesGrid');
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection for grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $priceRule = Mage::registry('current_promo_quote_rule');
        $tableName = Mage::getSingleton("core/resource")->getTableName('customer/entity');
        $couponType = $priceRule['coupon_type'];

        /**
         * @var Mage_SalesRule_Model_Resource_Coupon_Collection $collection
         */
        $collection = Mage::getResourceModel('salesrule/coupon_collection')
            ->addRuleToFilter($priceRule)
            ->addGeneratedCouponsFilter();

        /*Show customer email id for only coupon type "Auto generated Coupons for Customer Registration"*/
        if($couponType == Cybage_SignupPromo_Model_Rule::COUPON_TYPE_AUTO_CUSTOMER_SPECIFIC){
           $collection->getSelect()->join(array('t2' => $tableName),'main_table.customer_id = t2.entity_id',array('t2.email'));
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Define grid columns
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $priceRule = Mage::registry('current_promo_quote_rule');
        $couponType = $priceRule['coupon_type'];

        $this->addColumn('code', array(
            'header' => Mage::helper('salesrule')->__('Coupon Code'),
            'index'  => 'code'
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('salesrule')->__('Created On'),
            'index'  => 'created_at',
            'type'   => 'datetime',
            'align'  => 'center',
            'width'  => '160'
        ));

        /*Show customer email id feild for only coupon type "Auto generated Coupons for Customer Registration"*/
        if($couponType == Cybage_SignupPromo_Model_Rule::COUPON_TYPE_AUTO_CUSTOMER_SPECIFIC){
            $this->addColumn('email', array(
                'header' => Mage::helper('salesrule')->__('Email Address'),
                'index'  => 'email',
                'type'   => 'text',
                'width'  => '100',
                //'renderer' => 'signuppromo/promo_quote_edit_tab_coupons_column_renderer_email',
            ));
        }

        $this->addColumn('used', array(
            'header'   => Mage::helper('salesrule')->__('Used'),
            'index'    => 'times_used',
            'width'    => '100',
            'type'     => 'options',
            'options'  => array(
                Mage::helper('adminhtml')->__('No'),
                Mage::helper('adminhtml')->__('Yes')
            ),
            'renderer' => 'adminhtml/promo_quote_edit_tab_coupons_grid_column_renderer_used',
            'filter_condition_callback' => array(
                Mage::getResourceModel('salesrule/coupon_collection'), 'addIsUsedFilterCallback'
            )
        ));

        $this->addColumn('times_used', array(
            'header' => Mage::helper('salesrule')->__('Times Used'),
            'index'  => 'times_used',
            'width'  => '50',
            'type'   => 'number',
        ));

        $this->addExportType('*/*/exportCouponsCsv', Mage::helper('customer')->__('CSV'));
        $this->addExportType('*/*/exportCouponsXml', Mage::helper('customer')->__('Excel XML'));
        return parent::_prepareColumns();
    }

    /**
     * Configure grid mass actions
     *
     * @return Mage_Adminhtml_Block_Promo_Quote_Edit_Tab_Coupons_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('coupon_id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        $this->getMassactionBlock()->setUseAjax(true);
        $this->getMassactionBlock()->setHideFormElement(true);

        $this->getMassactionBlock()->addItem('delete', array(
             'label'=> Mage::helper('adminhtml')->__('Delete'),
             'url'  => $this->getUrl('*/*/couponsMassDelete', array('_current' => true)),
             'confirm' => Mage::helper('salesrule')->__('Are you sure you want to delete the selected coupon(s)?'),
             'complete' => 'refreshCouponCodesGrid'
        ));

        return $this;
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/couponsGrid', array('_current'=> true));
    }
}

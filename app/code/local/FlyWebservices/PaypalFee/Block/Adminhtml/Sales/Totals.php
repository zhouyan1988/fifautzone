<?php

/**
 * @category    FlyWebservices
 * @package     FlyWebservices_PaypalFee
 */
class FlyWebservices_PaypalFee_Block_Adminhtml_Sales_Totals extends Mage_Adminhtml_Block_Sales_Totals
{
    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
    	parent::_initTotals();
    	
        $source = $this->getSource();

        $this->_totals['paid'] = new Varien_Object(array(
            'code'      => 'paid',
            'strong'    => true,
            'value'     => $this->getSource()->getTotalPaid(),
            'base_value'=> $this->getSource()->getBaseTotalPaid(),
            'label'     => $this->helper('sales')->__('Total Paid'),
            'area'      => 'footer'
        ));
        $this->_totals['refunded'] = new Varien_Object(array(
            'code'      => 'refunded',
            'strong'    => true,
            'value'     => $this->getSource()->getTotalRefunded(),
            'base_value'=> $this->getSource()->getBaseTotalRefunded(),
            'label'     => $this->helper('sales')->__('Total Refunded'),
            'area'      => 'footer'
        ));
        $this->_totals['due'] = new Varien_Object(array(
            'code'      => 'due',
            'strong'    => true,
            'value'     => $this->getSource()->getTotalDue(),
            'base_value'=> $this->getSource()->getBaseTotalDue(),
            'label'     => $this->helper('sales')->__('Total Due'),
            'area'      => 'footer'
        ));
		
        /**
         * Add store rewards
         */
        $totals = $this->_totals;
        $newTotals = array();
        if (count($totals)>0) {
        	foreach ($totals as $index=>$arr) {
        		if ($index == "grand_total") {
        			if (((float)$this->getSource()->getPaymentCharge()) != 0) {
	        			$label = $this->__('Payment Charge');
			            $newTotals['payment_charge'] = new Varien_Object(array(
			                'code'  => 'payment_charge',
			                'field' => 'payment_charge',
			                'base_value' => $source->getBasePaymentCharge(),
			                'value' => $source->getPaymentCharge(),
			                'label' => $label
			            ));
        			}
        		}
        		$newTotals[$index] = $arr;
        	}
        	$this->_totals = $newTotals;
        }

        return $this;
    }
}

<?php
class Sebfie_Izberg_Model_Order_Invoice extends Mage_Sales_Model_Order_Invoice
{
    /**
     * Register invoice
     *
     * Apply to order, order items etc.
     *
     * @return unknown
     */
    public function register()
    {
        Mage::dispatchEvent('sales_order_invoice_register_start', array($this->_eventObject=>$this, 'order' => $this->getOrder()));
        parent::register();
    }

}

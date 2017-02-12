<?php

class Sebfie_Izberg_Block_Adminhtml_Sales_Order_Tab_Events
extends Mage_Adminhtml_Block_Template
implements Mage_Adminhtml_Block_Widget_Tab_Interface {

	/**
	 * Set the template for the block
	 *
	 */
	public function _construct()
	{
		parent::_construct();

		$this->setTemplate('izberg/sales/order/tab/events.phtml');
	}

	/**
	 * Retrieve the label used for the tab relating to this block
	 *
	 * @return string
	 */
    public function getTabLabel()
    {
    	return $this->__('Izberg events');
    }

    /**
     * Retrieve the title used by this tab
     *
     * @return string
     */
    public function getTabTitle()
    {
    	return $this->__('View all izberg events for this order');
    }

	/**
	 * Determines whether to display the tab
	 * Add logic here to decide whether you want the tab to display
	 *
	 * @return bool
	 */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Stops the tab being hidden
     *
     * @return bool
     */
    public function isHidden()
    {
    	return false;
    }

    public function getOrder()
    {
      return Mage::registry('sales_order');
    }

    public function getLogs()
    {
      $order = $this->getOrder();
      return Mage::helper("izberg")->getOrderLogs($order->getId());
    }

}

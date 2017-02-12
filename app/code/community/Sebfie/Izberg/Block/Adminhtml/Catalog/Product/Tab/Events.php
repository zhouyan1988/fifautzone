<?php

class Sebfie_Izberg_Block_Adminhtml_Catalog_Product_Tab_Events
extends Mage_Adminhtml_Block_Template
implements Mage_Adminhtml_Block_Widget_Tab_Interface {

	/**
	 * Set the template for the block
	 *
	 */
	public function _construct()
	{
		parent::_construct();

		$this->setTemplate('izberg/catalog/product/tab/events.phtml');
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
    	return $this->__('View all izberg events for this product');
    }

	/**
	 * Determines whether to display the tab
	 * Add logic here to decide whether you want the tab to display
	 *
	 * @return bool
	 */
    public function canShowTab()
    {
        $product = $this->getProduct();
        return Sebfie_Izberg_Model_Product::isCreatedFromIzberg($product);
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

    public function getProduct()
    {
      return Mage::registry('product');
    }

    public function getLogs()
    {
      $product = $this->getProduct();
      $izberg_product = Sebfie_Izberg_Model_Product::getIzbergCatalogProductFromMagentoProduct($product)->getIzbergProduct();
      return $izberg_product->getLogs($product->getId());
    }

}

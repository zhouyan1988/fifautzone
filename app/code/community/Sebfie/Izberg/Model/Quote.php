<?php

class Sebfie_Izberg_Model_Quote extends Mage_Sales_Model_Quote
{
  public function removeAllItems()
    {
        Mage::dispatchEvent('sales_quote_remove_all_items');
        parent::removeAllItems();
    }
}

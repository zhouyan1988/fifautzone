<?php
class Sebfie_Izberg_Model_Adminhtml_Sandbox extends Mage_Core_Model_Config_Data
{
    protected function _beforeSave()
    {
        if ($this->getOldValue() != $this->getValue()) {
          // We have to clear all stuff created by our module
          // Because we switched sandbox mode
          $collection = Mage::getModel('izberg/catalog_product')->getCollection();
          foreach ($collection as $item) {
              $product = $item->getCatalogProduct();
              $item->delete();
              $product->delete();
          }

          $collection = Mage::getModel('izberg/product')->getCollection();
          foreach ($collection as $item) {
              $item->delete();
          }

          $collection = Mage::getModel('izberg/category')->getCollection();
          foreach ($collection as $item) {
              $item->delete();
          }

          $collection = Mage::getModel('izberg/job')->getCollection();
          foreach ($collection as $item) {
              $item->delete();
          }
        }
    }
}

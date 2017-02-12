<?php
class Sebfie_Izberg_Model_Category extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('izberg/category');
    }

    public function getJsonResponse()
    {
        return json_decode($this->getCreatedFromJson());
    }

    public function getChilds()
    {
      return Mage::getModel("izberg/category")->getCollection()->addFieldToFilter("izberg_parent_category_id", $this->getId());
    }

    public static function resetMatching($type)
    {
      foreach(Mage::getModel("izberg/category")->getCollection()->addFieldToFilter("type", $type) as $item) {
        $item->delete();
      }
    }

    public function getMagentoCategory()
    {
        return Mage::getModel("catalog/category")->load($this->getMagentoMatchingCategoryId());
    }

    public function getIzbergCategoryIds()
    {
        return explode('/', $this->getIzbergCategoryPath());
    }

    public function getIzbergCategoryPath()
    {
      return substr($this->getData("izberg_category_path"), 1, -1);
    }
}

<?php
class Sebfie_Izberg_Model_Mysql4_Category_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public $_izberg_categories;

    /**
     * Define resource model
     *
     */
    protected function _construct()
    {
        $this->_izberg_categories = array();
        $this->_init('izberg/category');
    }

    public function retreiveIzbergCategory($id)
    {
      return Mage::helper("izberg")->retreiveIzbergCategory($id);
    }

    public function getItemFromIzbergCategoryId($id, $izberg_product)
    {
      $category = $this->retreiveIzbergCategory($id);

      $izberg_category_parent_ids = $category->parent_ids_tree; // e.g [1000, 1, 45]

      $magento_categories = array();

      $result = Mage::getModel("izberg/category");

      foreach($izberg_category_parent_ids as $category_id) {
        // We get all categories containing the id but for exemple, if we ask for cat_id = 15 and we have a category with id = 150, it will be returned. So we need to filter it.
        array_push($magento_categories, Mage::getModel("izberg/category")->getCollection()
                                          ->addFieldToFilter(
                                            'izberg_category_path',
                                            array(
                                                array('like'=> "%/$category_id/%")
                                            )
                                          )->addFieldToFilter(
                                          'gender',
                                          array(
                                            array('like'=> "%" . $izberg_product->getGender() . "%")
                                          )
                                          )->getFirstItem()
        );
      }
      foreach($magento_categories as $category) {
        if ($category->getId()) {
          $result = $category;
        }
      }
      return $result;
    }

    // Path should start with / and end with /
    // e.g /100/200/
    public function getItemFromPath($path, $izberg_product)
    {
      return Mage::getModel("izberg/category")->getCollection()
              ->addFieldToFilter('izberg_category_path',$path)
              ->addFieldToFilter(
                'gender',
                array(
                  array('like'=> "%" . $izberg_product->getGender() . "%")
                )
              )->getFirstItem();
    }

    protected function _afterLoad()
    {
      parent::_afterLoad();
      foreach ($this->getItems() as $item) {
        $item->setData('gender',json_decode($item->getData('gender')));
        $item->setDataChanges(false);
      }
      return $this;
    }
}

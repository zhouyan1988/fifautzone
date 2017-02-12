<?php
class Sebfie_Izberg_Block_Adminhtml_Matching_Categories extends Mage_Adminhtml_Block_Template
{

  public function getGenders()
  {
    return Sebfie_Izberg_Helper_Data::$IZBERG_GENDERS;
  }
  public function getImportUrl()
  {
      return Mage::helper("adminhtml")->getUrl("izberg/adminhtml_matching/importCategories");
  }

  public function getPostSaveUrl()
  {
      return Mage::helper("adminhtml")->getUrl("izberg/adminhtml_matching/postCategories");
  }

  public function getRootCategories()
  {
    return Mage::helper("izberg")->getIzberg()->get_list("category", array("parents__isnull" => "true"));
  }

  public function getRootApplicationCategories()
  {
    return Mage::helper("izberg")->getIzberg()->get_list("applicationCategory", array("parents__isnull" => "true"));
  }

  public function getIzbergCategories()
  {
    return Mage::getModel('izberg/category')->getCollection()->addFieldToFilter("type", "category");
  }

  public function getApplicationCategories()
  {
    return Mage::getModel('izberg/category')->getCollection()->addFieldToFilter("type", "application_category");
  }

  public function loadAllIzbergCategories()
  {
    $result = array();
    $category_ids = array();
    foreach($this->getIzbergCategories() as $category)
    {
      foreach(explode("/", $category->getIzbergCategoryPath()) as $cat_id) {
        array_push($category_ids, $cat_id);
      }
    }

    foreach(array_unique($category_ids) as $category_id) {
      $result[$category_id] = Mage::helper("izberg")->getIzberg()->get_list("category", empty($category_id) ? array() : array("parents" => $category_id));
    }

    return $result;
  }

  public function loadAllApplicationCategories()
  {
    $result = array();
    $category_ids = array();
    foreach($this->getApplicationCategories() as $category)
    {
      foreach(explode("/", $category->getIzbergCategoryPath()) as $cat_id) {
        array_push($category_ids, $cat_id);
      }
    }

    foreach(array_unique($category_ids) as $category_id) {
      $result[$category_id] = Mage::helper("izberg")->getIzberg()->get_list("applicationCategory", empty($category_id) ? array() : array("parents" => $category_id));
    }

    return $result;
  }

  public function getMagentoCategories()
  {
    $categories = Mage::getModel('catalog/category')
        ->getCollection()
        ->addAttributeToSelect('name')
        ->addAttributeToSort('path', 'asc')
        ->addFieldToFilter('is_active', array('eq'=>'1'))
        ->load();

    // Arrange categories in required array
    $categoryList = array();
    foreach ($categories as $catId => $category) {
        if ($category->getName()) {

            $space = '&nbsp;';
            $catName = $category->getName();
            for($i=1; $i<$category->getLevel(); $i++){
                $space = $space."&nbsp;&nbsp;&nbsp;";
            }
            $catName = $space.$catName;

            $categoryList[$category->getId()] = array(
                'id'    => $category->getId(),
                'name' => $catName,
                'level' =>$category->getLevel(),
                'path' => $category->getPath()
            );
        }
    }
    return $categoryList;
  }

  public function getCategoryAjaxRequestUrl()
  {
    return Mage::helper("adminhtml")->getUrl("izberg/adminhtml_matching/getChildCategories");
  }
}

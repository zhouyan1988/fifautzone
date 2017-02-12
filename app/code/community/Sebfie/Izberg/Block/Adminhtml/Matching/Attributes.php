<?php
class Sebfie_Izberg_Block_Adminhtml_Matching_Attributes extends Mage_Adminhtml_Block_Template
{

    public function getIzbergAttributes()
    {
      return Sebfie_Izberg_Model_Attribute::getIzbergAttributes();
    }

    public function getAttributeList()
    {
      $set = Mage::helper('izberg')->getDefaultProductAttributeSet();
      $result = Mage::getModel('catalog/product')->getResource()
        ->loadAllAttributes()
        ->getSortedAttributes($set->getId());
      usort($result, function($a, $b){
        return strcmp($a->getAttributeCode(), $b->getAttributeCode());
      });
      return $result;
    }

    public function getPostUrl()
    {
      return Mage::helper("adminhtml")->getUrl("izberg/adminhtml_matching/postAttributes");
    }

    public function getExistingMatching()
    {
      $matchs = Mage::getModel("izberg/attribute")->getCollection();
      $result = array();
      foreach($matchs as $match) {
        $result[$match->getIzbergMatchingAttributeCode()] = $match->getMagentoMatchingAttributeId();
      }
      return $result;
    }
}

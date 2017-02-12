<?php
/**
 * Attribute set options
 *
 */
class Sebfie_Izberg_Model_Adminhtml_System_Config_Source_Attributeset
{

    public function getAttributeSets()
    {
      $entityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();

      // We link this attributes to all attributes set:
      $sets = Mage::getModel("eav/entity_attribute_set")
                ->getResourceCollection()
                ->addFilter('entity_type_id', $entityTypeId);
      return $sets;
    }
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $sets = $this->getAttributeSets();
        $result = array();
        foreach ($sets as $set) {
            array_push($result, array('value' => $set->getId(), 'label'=> $set->getAttributeSetName()));
        }
        return $result;
    }

}
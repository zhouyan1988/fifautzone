<?php
/**
 * Attribute set options
 *
 */
class Sebfie_Izberg_Model_Adminhtml_System_Config_Source_Taxgroup
{

    public function getTaxGroupes()
    {
      return Mage::getResourceModel('tax/class_collection')
                ->addFieldToFilter('class_type', Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT)
                ->load()
                ->toOptionArray();
    }
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $taxes = $this->getTaxGroupes();
        $result = array(array("value" => "0" , "label" => "-----------------------"));
        foreach ($taxes as $tax) {
            array_push($result, $tax);
        }
        return $result;
    }

}
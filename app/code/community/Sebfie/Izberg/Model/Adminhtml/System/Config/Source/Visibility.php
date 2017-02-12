<?php
/**
 * Attribute set options
 *
 */
class Sebfie_Izberg_Model_Adminhtml_System_Config_Source_Visibility
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return Mage_Catalog_Model_Product_Visibility::getAllOption();
    }

}

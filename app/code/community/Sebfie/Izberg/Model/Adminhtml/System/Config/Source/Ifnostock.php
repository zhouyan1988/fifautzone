<?php
/**
 * Attribute set options
 *
 */
class Sebfie_Izberg_Model_Adminhtml_System_Config_Source_Ifnostock
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = array(array("value" => "0" , "label" => "-----------------------"));
        foreach (Sebfie_Izberg_Model_Product::getActionsIfNoStock() as $key => $label) {
            array_push($result, array("value" => $key , "label" => $label));
        }
        return $result;
    }

}

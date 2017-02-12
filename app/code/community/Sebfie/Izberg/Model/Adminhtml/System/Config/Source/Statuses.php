<?php
/**
 * Order Statuses source model
 */
class Sebfie_Izberg_Model_Adminhtml_System_Config_Source_Statuses
{
    public function toOptionArray()
    {
        $statuses = Mage::getSingleton('sales/order_config')->getStatuses();
        $options = array();
        $options[] = array(
               'value' => '',
               'label' => Mage::helper('adminhtml')->__('-- Please Select --')
            );
        foreach ($statuses as $code=>$label) {
            $options[] = array(
               'value' => $code,
               'label' => $label
            );
        }
        return $options;
    }
}

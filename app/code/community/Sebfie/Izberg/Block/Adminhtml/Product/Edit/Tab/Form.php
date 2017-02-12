<?php
class Sebfie_Izberg_Block_Adminhtml_Product_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
   protected function _prepareForm()
   {
       $form = new Varien_Data_Form();
       $this->setForm($form);
       $fieldset = $form->addFieldset('product_form', array('legend'=>'Force attributes value'));
       $fieldset->addField('force_attributes', 'textarea',
                         array(
                          'label' => 'Force attributes',
                          'class' => 'required-entry',
                          'required' => true,
                          'name' => 'force_attributes',
                          'class' => 'validate-json'
                      ));

       if ( Mage::registry('product_data') )
       {
         $form->setValues(Mage::registry('product_data')->getData());
       }
       return parent::_prepareForm();
    }
}

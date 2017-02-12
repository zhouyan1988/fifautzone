<?php
class Sebfie_Izberg_Block_Adminhtml_System_Config_Check extends Mage_Adminhtml_Block_Template implements Varien_Data_Form_Element_Renderer_Interface {

	protected $_element;

    protected function _construct() {
        $this->setTemplate('widget/form/renderer/fieldset.phtml');
    }

    public function getElement() {
        return $this->_element;
    }
	/**
	 * Generate html for button
	 *
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return string $html
	 * @see Mage_Adminhtml_Block_System_Config_Form_Field::_getElementHtml()
	 */
	public function render(Varien_Data_Form_Element_Abstract $element) {
		$html = $this->getLayout()->createBlock('izberg/adminhtml_system_config_check_point', 'izberg_checkpoint')
								  ->toHtml();
		$element->setHtmlContent($html);
		$this->_element = $element;
		return $this->toHtml();
	}
}

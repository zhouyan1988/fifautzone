  <?php
  class Sebfie_Izberg_Block_Adminhtml_Merchant_Edit_Tab_Show extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface
  {

      /**
     * Return Tab label
     *
     * @return string
     */
    public function getTabLabel() {
        return Mage::helper('izberg')->__('Merchant Information');
    }

    /**
     * Return Tab title
     *
     * @return string
     */
    public function getTabTitle() {
        return Mage::helper('izberg')->__('Merchant Information');
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab() {
        if (Mage::registry('merchant_data')->getId()) {
            return true;
        }
        return false;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden() {
        if (Mage::registry('merchant_data')->getId()) {
            return false;
        }
        return true;
    }

    public function getMerchant()
    {
        return Mage::registry('merchant_data');
    }

    public function getBackUrl()
    {
        return Mage::helper("adminhtml")->getUrl("izberg/adminhtml_merchant");
    }
}

<?php
class Sebfie_Izberg_Block_Adminhtml_Job_Edit_Tab_Show extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
   * Return Tab label
   *
   * @return string
   */
  public function getTabLabel() {
      return Mage::helper('izberg')->__('Job Information');
  }

  /**
   * Return Tab title
   *
   * @return string
   */
  public function getTabTitle() {
      return Mage::helper('izberg')->__('Job Information');
  }

  /**
   * Can show tab in tabs
   *
   * @return boolean
   */
  public function canShowTab() {
      if (Mage::registry('job_data')->getId()) {
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
      if (Mage::registry('job_data')->getId()) {
          return false;
      }
      return true;
  }

  public function getJob()
  {
      return Mage::registry('job_data');
  }

  public function getBackUrl()
  {
      return Mage::helper("adminhtml")->getUrl("izberg/adminhtml_job");
  }

  public function getRunUrl()
  {
      return Mage::helper("adminhtml")->getUrl("izberg/adminhtml_job/run", array("id" => $this->getJob()->getId()));
  }
}

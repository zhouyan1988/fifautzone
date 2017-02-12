<?php
class Sebfie_Izberg_Block_Adminhtml_Job_Renderer_Params extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

  public function render(Varien_Object $row)
  {
    return $row->getParamsAsString();
  }

}
?>

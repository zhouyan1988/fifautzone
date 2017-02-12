<?php
class Sebfie_Izberg_Adminhtml_OrderController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('izberg/orders')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('View orders'), Mage::helper('adminhtml')->__('View orders'));
        return $this;
    }

    public function indexAction() {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('izberg/adminhtml_order'));
        $this->renderLayout();
    }

    /**
     * Export order grid to CSV format
     */
    public function exportCsvAction() {
        $filename   = 'orders_iceberg.csv';
        $grid       = $this->getLayout()->createBlock('izberg/adminhtml_order_grid');
        $this->_prepareDownloadResponse($filename, $grid->getCsvFile());
    }

    /**
     * Product grid for AJAX request.
     * Sort and filter result for example.
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
               $this->getLayout()->createBlock('izberg/adminhtml_orders_grid')->toHtml()
        );
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('izberg/orders');
    }
}

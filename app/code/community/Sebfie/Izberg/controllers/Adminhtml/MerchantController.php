<?php
require_once(Mage::getBaseDir('lib') . '/Izberg/Html2Text/Html2Text.php');

class Sebfie_Izberg_Adminhtml_MerchantController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('izberg/merchants')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Merchants Manager'), Mage::helper('adminhtml')->__('Merchants Manager'));
        return $this;
    }

    public function indexAction() {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('core/template')->setTemplate("izberg/help/message.phtml"));
        $this->_addContent($this->getLayout()->createBlock('izberg/adminhtml_merchant'));
        $this->renderLayout();
    }


    public function editAction()
    {
        $merchantId     = $this->getRequest()->getParam('id');
        $merchantModel  = Mage::getModel('izberg/merchant')->load($merchantId);

        if ($merchantModel->getId() || $merchantId == 0) {

            Mage::register('merchant_data', $merchantModel);

            $this->loadLayout();
            $this->_setActiveMenu('izberg/merchants');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Merchant Manager'), Mage::helper('adminhtml')->__('Merchant Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Merchant News'), Mage::helper('adminhtml')->__('Merchant News'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('izberg/adminhtml_merchant_edit_tab_show'))
                 ->_addLeft($this->getLayout()->createBlock('izberg/adminhtml_merchant_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('izberg')->__('Merchant does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function massEnableAction()
    {
        $merchantIds = $this->getRequest()->getParam('merchant_ids');
        if (!$merchantIds) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper("izberg")->__("You did not selected any merchants"));
            return $this->_redirect('*/*/');
        }
        $merchants = Mage::getModel("izberg/merchant")->getCollection()->addFieldToFilter("merchant_id" , array('in' => $merchantIds));
        foreach($merchants as $merchant) {
            $merchant->setMagentoEnabled(true);
            $merchant->enableProducts();
            $merchant->save();
        }

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('izberg')->__('Merchant successfully enabled'));
        $this->_redirect('*/*/');
    }

    public function massDisableAction()
    {
        $merchantIds = $this->getRequest()->getParam('merchant_ids');
        if (!$merchantIds) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper("izberg")->__("You did not selected any merchants"));
            return $this->_redirect('*/*/');
        }
        $merchants = Mage::getModel("izberg/merchant")->getCollection()->addFieldToFilter("merchant_id" , array('in' => $merchantIds));
        foreach($merchants as $merchant) {
            $merchant->setMagentoEnabled(false);
            $merchant->disableProducts();
            $merchant->save();
        }

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('izberg')->__('Merchant successfully disabled'));
        $this->_redirect('*/*/');
    }

    public function massImportProductAction()
    {
        $result = array();

        try {
            $params = $this->getRequest()->getParams();
            $action = $params['do'];

            $izberg = Mage::helper("izberg")->getIzberg();

            $index = str_replace("offset", "", $action);
            $offset = intval($index);

            $merchantIds = $this->getRequest()->getParam('merchant_ids');
            if (!$merchantIds) {
                Mage::throwException("You did not selected any merchants");
            }
            // We get the corresponding merchant
            $merchant = Mage::getModel('izberg/merchant')->load($merchantIds[$offset]);
            $offset = $this->_reindexMassProductImportBatch($offset);

            if ($merchant->toProcess()) {
                // We load the XML
                Sebfie_Izberg_Model_Job::enqueue_job("Sebfie_Izberg_Model_Merchant", "import", array($merchant->getId()));
            }

            if (Mage::getSingleton('adminhtml/session')->getProgressCount() >= Mage::getSingleton('adminhtml/session')->getMerchantsToProcessCount())
            {
                $result["next"] = "done";
                $result["progress"] = Mage::helper('izberg')->__("All merchants products imported");
                Mage::helper("izberg")->log("We imported izberg products from " . count($merchantIds) . " merchants");

                // When we finish, we redirect back with a success message
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('We successfully imported products from ') . Mage::getSingleton('adminhtml/session')->getMerchantsToProcessCount() . ' ' . Mage::helper('adminhtml')->__('merchants'));
                Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('adminhtml')->__('Merchants products will be imported in the next hours. You can follow status on menu Izberg -> Jobs in queue'));
            } else {
                $result["next"] = "offset" . $offset;
                $result["progress"] = Mage::getSingleton('adminhtml/session')->getProgressCount() . " / " . Mage::getSingleton('adminhtml/session')->getMerchantsToProcessCount() . " " . Mage::helper('izberg')->__("merchants products imported") ;
            }

        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::helper("izberg")->log("We failed to import izberg products from merchant with error: " . $e->getMessage(), 2);
            Mage::throwException($e->getMessage());
        }

        $result["offset"] = $offset;
        $result["index"] = $index;
        $result["merchantIds"] = $merchantIds;
        echo Mage::Helper('core')->jsonEncode($result);
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Product grid for AJAX request.
     * Sort and filter result for example.
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
               $this->getLayout()->createBlock('izberg/adminhtml_merchants_grid')->toHtml()
        );
    }

    public function importAction()
    {
        $params = $this->getRequest()->getParams();
        $action = $params['do'];
        $izberg = Mage::helper("izberg")->getIzberg();

        $result = array();
        try {
            // Initial data
            $limit = 20;
            $index = str_replace("offset", "", $action);
            $offset = intval($index);

            if ((int)$offset === 0) {
                Mage::helper("izberg")->log("Start to import merchants from izberg");
            }

            $merchants = $izberg->get_list("merchant",array( "limit" => $limit, "offset" => $offset));
            Mage::helper("izberg")->manageMerchantsFromAPIResponse($merchants);

            $offset = $this->_reindexImportBatch($offset);

            if (Mage::getSingleton('adminhtml/session')->getProgressCount() >= Mage::getSingleton('adminhtml/session')->getMerchantsToProcessCount())
            {
                $result["next"] = "done";
                $result["progress"] = Mage::helper('izberg')->__("All merchants imported");

                Mage::helper("izberg")->log("We successfully imported  " .  Mage::getSingleton('adminhtml/session')->getMerchantsToProcessCount() . " merchants" );

                // When we finish, we redirect back with a success message
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Merchant was successfully imported'));
            } else {
                $result["next"] = "offset" . $offset;
                $result["progress"] = Mage::getSingleton('adminhtml/session')->getProgressCount() . " / " . Mage::getSingleton('adminhtml/session')->getMerchantsToProcessCount() . " " . Mage::helper('izberg')->__("merchants imported") ;
            }
        } catch (Exception $e) {
            Mage::helper("izberg")->log("An error occurs while importing merchants  " .  $e->getMessage(), 2 );

            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $result["offset"] = $offset;
        echo Mage::Helper('core')->jsonEncode($result);
    }


    /**
     * Index merchants
     *
     * Code to index one folder is uninterruptible, so process stats after each folder
     *
     * @param int $index - current iteration
     * @return int
     */
    protected function _reindexMassProductImportBatch($offset)
    {
        if ($offset == 0) {
            $count = 0;

            Mage::getSingleton('adminhtml/session')->setMerchantsToProcessCount(count($this->getRequest()->getParam('merchant_ids')));
            Mage::getSingleton('adminhtml/session')->setProgressCount(0);
            Mage::getSingleton('adminhtml/session')->setNotWritable(array());
        } else {
            $count = Mage::getSingleton('adminhtml/session')->getProgressCount();
        }

        $count += 1;

        Mage::getSingleton('adminhtml/session')->setProgressCount($count);

        return $count;

    }


    /**
     * Index merchants' products
     *
     * Code to index one folder is uninterruptible, so process stats after each folder
     *
     * @param int $index - current iteration
     * @return int
     */
    protected function _reindexImportBatch ($offset)
    {
        $limit = 20;
        if ($offset == 0) {
            $count = 0;
            $izberg = Mage::helper("izberg")->getIzberg();
            $meta = $izberg->get_list_meta("merchant");

            // We have to run parser on this first call
            if ($meta->total_count == 0) return;

            Mage::getSingleton('adminhtml/session')->setMerchantsToProcessCount($meta->total_count);
            Mage::getSingleton('adminhtml/session')->setProgressCount(0);
            Mage::getSingleton('adminhtml/session')->setNotWritable(array());
        } else {
            $count = Mage::getSingleton('adminhtml/session')->getProgressCount();
        }

        $count += $limit;

        Mage::getSingleton('adminhtml/session')->setProgressCount($count);

        return $count;
    }


    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('izberg/merchants');
    }
}

<?php
class Sebfie_Izberg_Adminhtml_ProductController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('izberg/products')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Products Manager'), Mage::helper('adminhtml')->__('Products Manager'));
        return $this;
    }

    public function indexAction() {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('core/template')->setTemplate("izberg/help/message.phtml"));
        $this->_addContent($this->getLayout()->createBlock('izberg/adminhtml_product'));
        $this->renderLayout();
    }

    public function editAction()
    {
        $productId     = $this->getRequest()->getParam('id');
        $productModel  = Mage::getModel('izberg/product')->load($productId);

        if ($productModel->getId() || $productId == 0) {

            Mage::register('product_data', $productModel);

            $this->loadLayout();
            $this->_setActiveMenu('izberg/products');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Product Manager'), Mage::helper('adminhtml')->__('Product Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Product News'), Mage::helper('adminhtml')->__('Product News'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('izberg/adminhtml_product_edit'))
                 ->_addLeft($this->getLayout()->createBlock('izberg/adminhtml_product_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('merchant')->__('Product does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function saveAction()
    {
      if ($this->getRequest()->getPost())
      {
       try {
          $postData = $this->getRequest()->getPost();
          $productModel = Mage::getModel('izberg/product')->load($this->getRequest()->getParam('id'));
          $productModel->addData($postData)->save();
           Mage::getSingleton('adminhtml/session')
                         ->addSuccess('successfully saved');
           Mage::getSingleton('adminhtml/session')
                          ->settestData(false);
           $this->_redirect('*/*/');
           return;
        } catch (Exception $e){
          Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
          Mage::getSingleton('adminhtml/session')->settestData($this->getRequest()->getPost());
          $this->_redirect('*/*/edit',
                      array('id' => $this->getRequest()
                                          ->getParam('id')));
          return;
        }
      }
      $this->_redirect('*/*/');
    }

    public function massDisableProductImportAction()
    {
        $productIds = $this->getRequest()->getParam('product_ids');
        if (!$productIds) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper("izberg")->__("You did not selected any products"));
            return $this->_redirect('*/*/');
        }
        $products = Mage::getModel("izberg/product")->getCollection()->addFieldToFilter("product_id" , array('in' => $productIds));
        foreach($products as $product) {
            $product->disable();
        }
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('izberg')->__('Product successfully disabled. Associated magento product are also disabled'));
        $this->_redirect('*/*/');
    }

    public function massEnableProductImportAction()
    {
        $productIds = $this->getRequest()->getParam('product_ids');
        if (!$productIds) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper("izberg")->__("You did not selected any products"));
            return $this->_redirect('*/*/');
        }
        $products = Mage::getModel("izberg/product")->getCollection()->addFieldToFilter("product_id" , array('in' => $productIds));
        foreach($products as $product) {
            $product->enable();
        }
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('izberg')->__('Product successfully enabled and imported in magento database'));
        $this->_redirect('*/*/');
    }

    public function massReimportAction()
    {
      $productIds = $this->getRequest()->getParam('product_ids');
      if (!$productIds) {
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper("izberg")->__("You did not selected any products"));
        return $this->_redirect('*/*/');
      }
      $products = Mage::getModel("izberg/product")->getCollection()->addFieldToFilter("product_id" , array('in' => $productIds));
      foreach($products as $product) {
        // We force image reimport
        $images = Mage::getModel("izberg/product_image")
          ->getCollection()
          ->addFieldToFilter("izberg_product_id", $product->getIzbergProductId());

        foreach($images as $image) {
          $image->setToImport(true);
          $image->save();
        }

        Sebfie_Izberg_Model_Job::enqueue_job("Sebfie_Izberg_Model_Product", "import", array($product->getIzbergProductId()));
      }
      Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('izberg')->__('Product successfully enqueued to be imported in magento database'));
      $this->_redirect('*/*/');
    }

    public function massSaveForceGenderAttributeAction()
    {
      $productIds = $this->getRequest()->getParam('product_ids');
      $gender = $this->getRequest()->getParam('gender');

      if (!$productIds) {
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper("izberg")->__("You did not selected any products"));
        return $this->_redirect('*/*/');
      }
      $products = Mage::getModel("izberg/product")->getCollection()->addFieldToFilter("product_id" , array('in' => $productIds));

      foreach($products as $product) {
        $f_attrs = $product->getForceAttributesValues();
        $f_attrs->gender = $gender;
        $product->setForceAttributes(json_encode($f_attrs));
        $product->save();
      }
      Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('izberg')->__('Product attributes successfully updated'));
      $this->_redirect('*/*/');

    }


    public function massSaveForceCategoriesAttributeAction()
    {
      $productIds = $this->getRequest()->getParam('product_ids');
      if (!$productIds) {
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper("izberg")->__("You did not selected any products"));
        return $this->_redirect('*/*/');
      }
      $products = Mage::getModel("izberg/product")->getCollection()->addFieldToFilter("product_id" , array('in' => $productIds));

      foreach($products as $product) {
        $categorized = false;
        // We save categories of the first catalog product associated to a category
        foreach($product->getCatalogProducts() as $p) {
          $cids = $p->getCategoryIds();
          if (count($cids)) {
            $f_attrs = $product->getForceAttributesValues();
            $f_attrs->category_ids = $cids;
            $product->setForceAttributes(json_encode($f_attrs));
            $product->save();
            $categorized = true;
            break;
          }
        }
        if (!$categorized) {
          $f_attrs = $product->getForceAttributesValues();
          $f_attrs->category_ids = array();
          $product->setForceAttributes(json_encode($f_attrs));
          $product->save();
        }
      }

      Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('izberg')->__('Product categories successfully updated'));
      $this->_redirect('*/*/');
    }


    public function newAction()
    {
        $this->_forward('edit');
    }

    // This action is called from mass catalog product action select box
    public function reimportAction()
    {
      $productIds = $this->getRequest()->getParam('product');
      if (!$productIds) {
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper("izberg")->__("You did not selected any products"));
        return $this->_redirect('*/*/');
      }
      $mproducts = Mage::getModel("catalog/product")->getCollection() ->addFieldToFilter("entity_id" , array('in' => $productIds));
      $productSkus = array();
      foreach($mproducts as $product) {
          array_push($productSkus, $product->getSku());
      }

      $izberg_catalog_products = Mage::getModel("izberg/catalog_product")->getCollection();
      $izberg_catalog_products->addFieldToFilter("catalog_product_sku" , array('in' => $productSkus));
      $izberg_catalog_products->getSelect()->group('izberg_product_id');

      $ids = array();
      foreach($izberg_catalog_products as $izberg_catalog_product) {
        array_push($ids, $izberg_catalog_product->getIzbergProductId());
      }

      if (empty($ids)) {
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper("izberg")->__("This products are not created by izberg"));
        return $this->_redirect('*/*/');
      }


      $products = Mage::getModel("izberg/product")->getCollection()->addFieldToFilter('product_id', array(
        'in' => array($ids)
      ));

      foreach($products as $product) {
        // We force image reimport
        $images = Mage::getModel("izberg/product_image")
          ->getCollection()
          ->addFieldToFilter("izberg_product_id", $product->getIzbergProductId());

        foreach($images as $image) {
          $image->setToImport(true);
          $image->save();
        }

        $product->importInMagentoDb();
      }
      Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('izberg')->__('Product successfully updated'));
      $this->_redirect('adminhtml/catalog_product/index');
    }


    /**
     * Product grid for AJAX request.
     * Sort and filter result for example.
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
               $this->getLayout()->createBlock('izberg/adminhtml_products_grid')->toHtml()
        );
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('izberg/products');
    }
}

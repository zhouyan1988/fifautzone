<?php

class Magestore_Storepickup_Adminhtml_Storepickup_StorepickupController extends Mage_Adminhtml_Controller_action {

    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu('storepickup/items')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

        return $this;
    }

    public function indexAction() {
        $this->_initAction()
                ->renderLayout();
    }

    public function editAction() {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('storepickup/storepickup')->load($id);

        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }

            Mage::register('storepickup_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('storepickup/items');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('storepickup/adminhtml_storepickup_edit'))
                    ->_addLeft($this->getLayout()->createBlock('storepickup/adminhtml_storepickup_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('storepickup')->__('Item does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function newAction() {
        $this->_forward('edit');
    }

    public function saveAction() {
        if ($data = $this->getRequest()->getPost()) {
            
            if (isset($_FILES['filename']['name']) && $_FILES['filename']['name'] != '') {
                try {
                    /* Starting upload */
                    $uploader = new Varien_File_Uploader('filename');

                    // Any extention would work
                    $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png'));
                    $uploader->setAllowRenameFiles(false);

                    // Set the file upload mode 
                    // false -> get the file directly in the specified folder
                    // true -> get the file in the product like folders 
                    //	(file.jpg will go in something like /media/f/i/file.jpg)
                    $uploader->setFilesDispersion(false);

                    // We set media as the upload dir
                    $path = Mage::getBaseDir('media') . DS;
                    $uploader->save($path, $_FILES['filename']['name']);
                } catch (Exception $e) {
                    
                }

                //this way the name is saved in DB
                $data['filename'] = $_FILES['filename']['name'];
            }


            $model = Mage::getModel('storepickup/storepickup');
            $model->setData($data)
                    ->setId($this->getRequest()->getParam('id'));

            try {
                if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
                    $model->setCreatedTime(now())
                            ->setUpdateTime(now());
                } else {
                    $model->setUpdateTime(now());
                }

                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('storepickup')->__('Item was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('storepickup')->__('Unable to find item to save'));
        $this->_redirect('*/*/');
    }

    public function deleteAction() {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('storepickup/storepickup');

                $model->setId($this->getRequest()->getParam('id'))
                        ->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    public function massDeleteAction() {
        $storepickupIds = $this->getRequest()->getParam('storepickup');
        if (!is_array($storepickupIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($storepickupIds as $storepickupId) {
                    $storepickup = Mage::getModel('storepickup/storepickup')->load($storepickupId);
                    $storepickup->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__(
                                'Total of %d record(s) were successfully deleted', count($storepickupIds)
                        )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massStatusAction() {
        $storepickupIds = $this->getRequest()->getParam('storepickup');
        if (!is_array($storepickupIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
        } else {
            try {
                foreach ($storepickupIds as $storepickupId) {
                    $storepickup = Mage::getSingleton('storepickup/storepickup')
                            ->load($storepickupId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                }
                $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) were successfully updated', count($storepickupIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function exportCsvAction() {
        $fileName = 'storepickup.csv';
        $content = $this->getLayout()->createBlock('storepickup/adminhtml_storepickup_grid')
                ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function changestoreAction() {
        $is_storepickup = $this->getRequest()->getParam('is_storepickup');

        if ($is_storepickup) {
            $data['is_storepickup'] = $is_storepickup;
            Mage::getSingleton('adminhtml/session')->setData('storepickup_session', $data);
            return;
        }
        $data = Mage::getSingleton('adminhtml/session')->getData('storepickup_session');

        //storepickup
        $data['store_id'] = $this->getRequest()->getParam('store_id');

        Mage::getSingleton('adminhtml/session')->setData('storepickup_session', $data);
    }

    public function changedateAction() {
        try {
            $shipping_date = $this->getRequest()->getParam('shipping_date');

            $date = new Zend_Date();
            $date->setLocale(Mage::app()->getLocale()->getLocaleCode());
            $shipping_date_format = $date->setDate($shipping_date);

            $store_id = $this->getRequest()->getParam('store_id');

            $storepickup = Mage::getSingleton('adminhtml/session')->getData('storepickup_session');
            $storepickup['date'] = Mage::helper('core')->formatDate($shipping_date_format, 'medium', false);
            Mage::getSingleton('adminhtml/session')->setData('storepickup_session', $storepickup);

            $html_select = Mage::helper('storepickup')->getTimeSelectHTML($shipping_date, $store_id);
            $this->getResponse()->setBody($html_select);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->setData('myerror', $e->getMessage());
        }
    }

    public function changetimeAction() {
        $time = new Zend_Date();
        $time->setLocale(Mage::app()->getLocale()->getLocaleCode());

        $shipping_time = $this->getRequest()->getParam('shipping_time');
        $shippingtime = $time->setTime($shipping_time);

        $storepickup = Mage::getSingleton('adminhtml/session')->getData('storepickup_session');

        $storepickup['time'] = Mage::helper('core')->formatTime($shippingtime, 'medium', false);

        Mage::getSingleton('adminhtml/session')->setData('storepickup_session', $storepickup);
    }

    public function exportXmlAction() {
        $fileName = 'storepickup.xml';
        $content = $this->getLayout()->createBlock('storepickup/adminhtml_storepickup_grid')
                ->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

    protected function _sendUploadResponse($fileName, $content, $contentType = 'application/octet-stream') {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK', '');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }
    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('storepickup');
    }
}

<?php
class Sebfie_Izberg_Adminhtml_JobController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('izberg/jobs')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Jobs Manager'), Mage::helper('adminhtml')->__('Jobs Manager'));
        return $this;
    }

    public function indexAction() {
        $this->_initAction();
          $this->_addContent($this->getLayout()->createBlock('core/template')->setTemplate("izberg/help/message.phtml"));
        $this->_addContent($this->getLayout()->createBlock('izberg/adminhtml_job'));
        $this->renderLayout();
    }

    public function viewAction()
    {
        $jobId     = $this->getRequest()->getParam('id');
        $jobModel  = Mage::getModel('izberg/job')->load($jobId);

        if ($jobModel->getId() || $jobId == 0) {

            Mage::register('job_data', $jobModel);

            $this->loadLayout();
            $this->_setActiveMenu('izberg/jobs');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Job Manager'), Mage::helper('adminhtml')->__('Job Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Job News'), Mage::helper('adminhtml')->__('Job News'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('izberg/adminhtml_job_edit_tab_show'))
                 ->_addLeft($this->getLayout()->createBlock('izberg/adminhtml_job_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('izberg')->__('Job does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function massDeleteAction()
    {
      $jobIds = $this->getRequest()->getParam('job_ids');
      if (!$jobIds) {
          Mage::getSingleton('adminhtml/session')->addError(Mage::helper("izberg")->__("You did not selected any jobs"));
          return $this->_redirect('*/*/');
      }
      $jobs = Mage::getModel("izberg/job")->getCollection()->addFieldToFilter("job_id" , array('in' => $jobIds));
      foreach($jobs as $job) {
          $job->delete();
      }

      Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('izberg')->__('Job successfully deleted'));
      $this->_redirect('*/*/');
    }

    public function massJobRunAction()
    {
      $jobIds = $this->getRequest()->getParam('job_ids');
      if (!$jobIds) {
          Mage::getSingleton('adminhtml/session')->addError(Mage::helper("izberg")->__("You did not selected any jobs"));
          return $this->_redirect('*/*/');
      }
      $jobs = Mage::getModel("izberg/job")->getCollection()->addFieldToFilter("job_id" , array('in' => $jobIds));
      foreach($jobs as $job) {
          $job->setStatus(Sebfie_Izberg_Model_Job::STATUS_ENQUEUED);
          $job->setRetriesCount(0);
          $job->save();

          $job->run();
      }

      Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('izberg')->__('Jobs successfully runned'));
      $this->_redirect('*/*/');
    }

    public function runAction()
    {
      $job_id = $this->getRequest()->getParam('id');
      if (!$job_id) {
          Mage::getSingleton('adminhtml/session')->addError(Mage::helper("izberg")->__("You did not selected any job"));
          return $this->_redirect('*/*/');
      }
      $job = Mage::getModel("izberg/job")->load($job_id);
      $job->run();

      Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('izberg')->__('Job successfully runned'));
      $this->_redirect('*/*/');
    }

    public function massChangestatusAction()
    {
      $jobIds = $this->getRequest()->getParam('job_ids');
      $status = $this->getRequest()->getParam('status');
      if (!$jobIds) {
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper("izberg")->__("You did not selected any jobs"));
        return $this->_redirect('*/*/');
      }
      $jobs = Mage::getModel("izberg/job")->getCollection()->addFieldToFilter("job_id" , array('in' => $jobIds));
      foreach($jobs as $job) {
        $job->setStatus($status);
        if ($status == Sebfie_Izberg_Model_Job::STATUS_ENQUEUED) {
          $job->setRetriesCount(0);
        }
        $job->save();
      }

      Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('izberg')->__('Job\'s status successfully runned'));
      $this->_redirect('*/*/');
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
               $this->getLayout()->createBlock('izberg/adminhtml_jobs_grid')->toHtml()
        );
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('izberg/jobs');
    }
}

<?php
class Sebfie_Izberg_Model_Job extends Mage_Core_Model_Abstract
{

    const STATUS_ENQUEUED = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_WIP = 2;
    const STATUS_FAILED = 3;


    public function _construct()
    {
        parent::_construct();
        $this->_init('izberg/job');
    }

    public function isFinished()
    {
      return (($this->getStatus() == self::STATUS_SUCCESS) || ($this->getStatus() == self::STATUS_FAILED) || ($this->getRetriesCount() >= $this->getRetryable()));
    }

    public function isRunning()
    {
      return ($this->getStatus() == self::STATUS_WIP);
    }

    public function addLog($msg)
    {
      $this->setLogs(date("d/m/Y h:i:s a", time()) . " - " . $msg . " \n" . $this->getLogs());
    }

    public function addMessage($msg)
    {
      $this->setMessages( date("d/m/Y h:i:s a", time()) . " - " . $msg . " \n" . $this->getMessages());
    }

    public static function getStatusAsArray()
    {
      return array(
        0  => Mage::helper('izberg')->__("Enqueued"),
        1  => Mage::helper('izberg')->__("Succeed"),
        2  => Mage::helper('izberg')->__("Work in progress"),
        3  => Mage::helper('izberg')->__("Failed")
      );
    }

    public function getUnserializedParams()
    {
      return unserialize($this->getParams());
    }

    public function getParamsAsString()
    {
      return is_array($this->getUnserializedParams()) ? implode(",",$this->getUnserializedParams()) : $this->getUnserializedParams();
    }

    public function getStatusAsString()
    {
      $statuses = self::getStatusAsArray();
      return $statuses[$this->getStatus()];
    }

    public function manageStatusOnError()
    {
      if ($this->getRetriesCount() < $this->getRetryable()) {
        $this->setRetriesCount(($this->getRetriesCount() + 1));
        if ($this->getRetriesCount() >= $this->getRetryable()) {
          $this->setStatus(self::STATUS_FAILED);
        } else {
          $this->setStatus(self::STATUS_ENQUEUED);
        }
      } else {
        $this->setStatus(self::STATUS_FAILED);
      }
    }

    public static function enqueue_job($model, $method, $params)
    {
      // Enqueue the same job only once
      $job = Mage::getModel("izberg/job")->getCollection()
        ->addFieldToFilter("magento_model", $model)
        ->addFieldToFilter("magento_model_method", $method)
        ->addFieldToFilter("params", serialize($params))
        ->addFieldToFilter("status", array('in' => array(self::STATUS_ENQUEUED, self::STATUS_WIP)))
        ->getFirstItem()
        ->addData(array(
          "magento_model" => $model,
          "magento_model_method" => $method,
          "params" => serialize($params),
          "status" => self::STATUS_ENQUEUED,
          "logs" => "",
          "messages" => "",
          "created_at" => time(),
          "retryable" => 3,
          "retries_count" => 0,
        ));
        $job->save();
        return $job;
    }

    public function run()
    {
      if ($this->isFinished() || $this->isRunning()) {
        return true;
      }

      # Import take a lot of memory, so do not limit it
      ini_set('memory_limit','1048M');

      $start = time();
      $this->setStatus(self::STATUS_WIP);
      $this->setUpdatedAt(time());
      $this->addLog("Starting to run job at " . date("d/m/Y h:i:s a", time()));
      $this->save();

      try {
        call_user_func($this->getMagentoModel() . "::" . $this->getMagentoModelMethod(), $this->getUnserializedParams(), $this);
        $this->setStatus(self::STATUS_SUCCESS);
        $this->setLastRunAt(time());
        $this->setDuration(time() - $start);
        $this->addLog("Finished job at " . date("d/m/Y h:i:s a", time()));
        $this->save();
      } catch (Exception $e) {
        $this->manageStatusOnError();
        $this->addLog("Error on job at " . date("d/m/Y h:i:s a", time()) . " with message " . $e->getMessage());
        $this->setLastRunAt(time());
        $this->setDuration(time() - $start);
        $this->save();
      }
    }

}

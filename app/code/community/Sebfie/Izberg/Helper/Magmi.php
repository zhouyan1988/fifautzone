<?php
class Sebfie_Izberg_Helper_Magmi extends Mage_Core_Helper_Abstract
{
  // const DEBUG   = 0;  // Debug: debug messages
  // const RUN     = 1;  // Magmi running
  // const FINISHED  = 2 // Magmi finished
  // const CANCELED  = 3 // Magmi canceled
  public function log($message, $level = 0, $extra = "", $scope = null, $entity_id = null) {
    Mage::getModel("izberg/magmi_log")
      ->setCreatedAt(time())
      ->setMessage($message)
      ->setLevel($level)
      ->setExtraMessage($extra)
      ->setEntityId($entity_id)
      ->setScope($scope)
      ->save();
  }

  public static function _getCsvHeaders($data)
  {
    $final_keys = array_keys($data[0]);
    foreach($data as $d) {
      $extra_keys = array_keys($d);
      foreach($extra_keys as $key) {
        if (array_search($key, $final_keys) === false) {
          array_push($final_keys, $key);
        }
      }
    }
    return $final_keys;
  }

  public function saveCSVToImport($arrayToImport, $name = null)
  {
    $io = new Varien_Io_File();
    $path = Mage::getBaseDir('var') . DS . 'izberg_export' . DS;
    if (is_null($name)) $name = md5(microtime());
    $file = $path . $name . '.csv';
    $io->setAllowCreateFolders(true);
    $io->open(array('path' => $path));
    $io->streamOpen($file, 'w+');
    $io->streamLock(true);

    $headers = self::_getCsvHeaders($arrayToImport);
    $io->streamWriteCsv($headers);

    foreach ($arrayToImport as $data) {
        if (!isset($data["sku"])) {
          continue;
        }
        $for_file = array();
        foreach($headers as $index => $header) {
          if (isset($data[$header])) {
            $for_file[$index] = $data[$header];
          } else {
            $for_file[$index] = null;
          }
        }
        $io->streamWriteCsv($for_file);
    }
    return $file;
  }

  public function sendToMagmi($name, $job = null)
  {
    try {
      $store_id = Mage::app()
          ->getWebsite()
          ->getDefaultGroup()
          ->getDefaultStoreId();
      Mage::app()->setCurrentStore($store_id);

      Mage::helper("izberg/magmi")->log("Start sendToMagmi", Sebfie_Izberg_Model_Magmi_Log::TYPE_RUN);
      $startTime = time();
      $logHandle = $name.".magmi.log";

      $username = Mage::getStoreConfig("izberg/izberg_magmi_settings/izberg_magmi_login", Mage::app()->getStore());
      $password = Mage::helper('core')->decrypt(Mage::getStoreConfig("izberg/izberg_magmi_settings/izberg_magmi_password", Mage::app()->getStore()));

      $url = str_replace("//", "//".$username . ":" . $password . "@" ,Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)).'izberg-importer/web/magmi_run.php?mode=create&profile=izberg&engine=magmi_productimportengine:Magmi_ProductImportEngine&CSV:filename='. $name;

      if ($job) {
        $job->addLog("We sended a curl to " . $url);
        $job->save();
      }
      Mage::helper("izberg/magmi")->log("We sended a curl to " . $url, Sebfie_Izberg_Model_Magmi_Log::TYPE_DEBUG);

      $result = exec('PATH=/bin:/usr/bin:/usr/local/bin curl -s -m 3600 "'. $url .'" > '.$logHandle.' 2>&1 &', $output);

      // At this point magmi request is send, but we will not catch the end
      return true;
    } catch (Exception $e) {
      Mage::helper("izberg/magmi")->log("Crashed on sendToMagmi with error: " . $e->getMessage());
      $this->cancelMagmi();
      return false;
    }
  }

  public function cancelMagmi()
  {
    Mage::helper("izberg/magmi")->log("Start cancelMagmi", Sebfie_Izberg_Model_Magmi_Log::TYPE_CANCELED);
    $options = array(
        CURLOPT_RETURNTRANSFER => true,   // return web page
        CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
        CURLOPT_CONNECTTIMEOUT => 0,    // time-out on connect
        CURLOPT_TIMEOUT        => 0,    // time-out on response
        CURLOPT_FILE           => $fileHandle    // file log outputÒ
    );

    $username = Mage::getStoreConfig("izberg/izberg_magmi_settings/izberg_magmi_login", Mage::app()->getStore());
    $password = Mage::helper('core')->decrypt(Mage::getStoreConfig("izberg/izberg_magmi_settings/izberg_magmi_password", Mage::app()->getStore()));

    $url = str_replace("//", "//".$username . ":" . $password . "@" ,Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)).'izberg-importer/web/magmi_cancel.php';

    Mage::helper("izberg/magmi")->log("We sended a cancel curl to " . $url, Sebfie_Izberg_Model_Magmi_Log::TYPE_CANCELED);
    $startTime = time();

    $curl = curl_init($url);
    curl_setopt_array($curl, $options);

    curl_exec($curl);
    curl_close($curl);
    $endTime = time();
    $duration = $endTime - $startTime;
    Mage::helper("izberg/magmi")->log("cancelMagmi tooks: " . $duration, Sebfie_Izberg_Model_Magmi_Log::TYPE_CANCELED);
    return $result;
  }

  public function getLastRun()
  {
    $collection = Mage::getModel("izberg/magmi_log")->getCollection()
      ->addFieldToFilter("level", Sebfie_Izberg_Model_Magmi_Log::TYPE_RUN);
    $collection->getSelect()->order('created_at DESC')
                            ->limit(1);
    return $collection->getFirstItem()->getCreatedAt();
  }

  public function getLastFinish()
  {
    $collection = Mage::getModel("izberg/magmi_log")->getCollection()
      ->addFieldToFilter("level", Sebfie_Izberg_Model_Magmi_Log::TYPE_FINISHED);
    $collection->getSelect()->order('created_at DESC')
                            ->limit(1);
    return $collection->getFirstItem()->getCreatedAt();
  }

  public function getLastCancel()
  {
    $collection = Mage::getModel("izberg/magmi_log")->getCollection()
      ->addFieldToFilter("level", Sebfie_Izberg_Model_Magmi_Log::TYPE_CANCELED);
    $collection->getSelect()->order('created_at DESC')
                            ->limit(1);
    return $collection->getFirstItem()->getCreatedAt();
  }

  public function cancelIfNeeded()
  {
    // If the last run did not finished && lastRun < 2hour ago
    if (!$this->getLastRun()) return;

    // If the cancel is older then the last_run
    // AND finished < last_run
    // AND Last run > 2 hours
    if ($this->getLastCancel() < $this->getLastRun() && $this->getLastFinish() < $this->getLastRun() && (time() - $this->getLastRun()) > 2 * 60 * 60 ) {
      $this->cancelMagmi();
    }
  }

  public function getAllStoreCodes()
  {
    $r = array();
    foreach (Mage::app()->getStores() as $store) {
      array_push($r, $store->getCode());
    }
    return implode(",", $r);
  }

  public function getState()
  {
    $path = Mage::getBaseDir() . DS . "izberg-importer" . DS . "state" . DS . "magmistate";
    return file_get_contents($path);
  }

  public function isRunning()
  {
    return $this->getState() == "running";
  }
}

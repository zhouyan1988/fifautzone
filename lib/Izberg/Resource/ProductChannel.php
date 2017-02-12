<?php
namespace Izberg\Resource;
use Izberg\Resource;

class ProductChannel extends Resource
{
  public function output($params = array(), $save_in_path = null)
  {
    $outputs = self::$Izberg->get_list("productChannelFileOutput", $params, "Accept: application/json", $this->getName()."/".$this->id."/file_outputs/");

    ini_set('memory_limit', '2048M');
    set_time_limit(0);

    if (count($outputs) == 0) {
      // No output file
      return $this->getViewer($params, $save_in_path);
    } else {
      // Return viewer
      $output = $outputs[0];
      return self::$Izberg->getHelper()->readFromUrl($output->output_file, $save_in_path);
    }
  }

  public function getViewer($params = array(), $save_in_path = null) {
    if (!isset($params["format"])) $params["format"] = "xml";
    unset($params["output_format"]);
    return self::$Izberg->Call("product_channel/" . $this->id . "/viewer/", 'GET', $params, 'Accept: application/xml', 'Content-Type: application/xml; charset=UTF-8', $save_in_path);
  }

}

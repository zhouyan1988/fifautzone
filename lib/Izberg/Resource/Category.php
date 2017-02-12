<?php
namespace Izberg\Resource;
use Izberg\Resource;
use Izberg\Resource\Category\CategoryAbstract;

class Category extends Category\CategoryAbstract
{
  public function get_childs()
  {
    return self::$Izberg->get_list("category", array("parents" => $this->id), "Accept: application/json");
  }
}

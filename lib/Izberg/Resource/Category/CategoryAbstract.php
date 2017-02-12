<?php
namespace Izberg\Resource\Category;
use Izberg\Resource;

abstract class CategoryAbstract extends Resource
{
  protected static $resource_endpoint = NULL;

  public function getRootResponse()
  {
    return self::$Izberg->Call("application/" . self::$Izberg->getAppNamespace() . "/locale_config/root_category/", 'GET', array());
  }

  public function setResourceEndPoint()
  {
    $response = $this->getRootResponse();
    self::$resource_endpoint = $response->meta->resource_endpoint;
    return $response;
  }

  public function get_endpoint()
  {
    if (is_null(self::$resource_endpoint)) {
      $this->setResourceEndPoint();
    }
    return self::$resource_endpoint;
  }

  public function get_childs()
  {
    return self::$Izberg->get_list(self::$Izberg->getHelper()->camelize($this->get_endpoint()), array("parents" => $this->id), "Accept: application/json");
  }

  // Functions for tests...
  public static function tearDown()
  {
      static::$resource_endpoint = NULL;
  }
}

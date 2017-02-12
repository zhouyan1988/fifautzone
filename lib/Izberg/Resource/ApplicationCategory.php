<?php
namespace Izberg\Resource;
use Izberg\Resource;
use Izberg\Resource\Category\CategoryAbstract;

class ApplicationCategory extends CategoryAbstract
{
  public function get_list($params, $accept_type) {
    if (is_null(self::$resource_endpoint)) {
      // We set the endpoint
      $response = $this->setResourceEndPoint();
      if (empty($params)) return $response;
    }
    // We ask for a child category
    return self::$Izberg->Call(self::$resource_endpoint . "/", 'GET', $params, $accept_type);
  }
}

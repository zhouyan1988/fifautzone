<?php
namespace Izberg\Resource;
use Izberg\Resource;

class Application extends Resource
{
  public function get_channel($params = null)
	{
		$response = self::$Izberg->Call("application/".$this->id."/active_products_channel/", 'GET', $params);
    $object = new ProductChannel();
    $object->hydrate($response);
    return $object;
	}
}

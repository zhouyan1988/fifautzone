<?php
namespace Izberg\Resource;
use Izberg\Resource;

class LocaleConfig extends Resource
{

    public function getPrefix()
    {
      return "application/" . self::$Izberg->getAppNamespace() . "/";
    }

    public function update($params, $accept_type = "Content-Type: application/json")
    {
      $response = self::$Izberg->Call($this->getPrefix() . $this->getName() . "/", 'PUT', $params, $accept_type);
      $this->hydrate($response);
      return $this;
    }

    public function delete($params = array(), $accept_type = "Content-Type: application/json")
  	{
      return self::$Izberg->Call($this->getPrefix() . $this->getName() . "/", 'DELETE', $params, $accept_type);
  	}
}

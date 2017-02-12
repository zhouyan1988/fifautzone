<?php
namespace Izberg\Resource;
use Izberg\Resource;

class Product extends Resource
{
	public function reviews($id = null)
	{
		if ($id === null && $this->id)
			$id = $this->id;
		return parent::$Izberg->get("review", $params = array("product"=>$id));
	}
}

<?php
namespace Izberg\Resource;
use Izberg\Resource;

class ReturnRequest extends Resource
{
	public function getName()
	{
		return "return";
	}

  /*
	**	Accept the return
	*/
	public function accept()
	{
		$response = self::$Izberg->Call($this->getName()."/". $this->id ."/accept/", "POST");
		$this->hydrate($response);
		return $this;
	}

  /*
	**	Mark return as received
	*/
	public function received()
	{
		$response = self::$Izberg->Call($this->getName()."/". $this->id ."/received/", "POST");
		$this->hydrate($response);
		return $this;
	}

  /*
	**	Mark return as resended so, we close the return
	*/
	public function close()
	{
		$response = self::$Izberg->Call($this->getName()."/". $this->id ."/seller_close/", "POST");
		$this->hydrate($response);
		return $this;
	}
}

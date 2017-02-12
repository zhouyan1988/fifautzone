<?php
namespace Izberg\Resource;
use Izberg\Resource;

class Order extends Resource
{

	/**
	* Updates status of existing order
	*
	* Status : authorizeOrder |  updateOrderPayment
	*
	* @returns object
	*
	**/
	public function updateStatus($status, $id_order = null)
	{
		if (!$id_order && !$this->id)
			throw new Exception\GenericException("No order_id and no URI");
		if ($status != "updateOrderPayment" && $status != "authorizeOrder" && $status != "cancel")
			throw new Exception\GenericException("Wrong Status : authorizeOrder | updateOrderPayment");
		$id = $id_order ? $id_order : $this->id;
		$response = parent::$Izberg->Call($this->getName().'/'.$id.'/'.$status.'/', 'POST');
		$this->hydrate($response);
		return $this;
	}
}

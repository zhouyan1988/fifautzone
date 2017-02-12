<?php
namespace Izberg\Resource;
use Izberg\Resource;

class OrderItem extends Resource
{
	/**
	* Updates status of existing order item
	*
	* Status : confirm | send | cancel
	*
	* @returns object
	*
	**/
	public function updateStatus($status, $id_order = null)
	{
		if (!$id_order && !$this->id)
			throw new Exception\GenericException("No order_id and no URI");
		if ($status != "confirm" && $status != "send" && $status != "cancel")
			throw new Exception\GenericException("Wrong Status : send | confirm | cancel");
		$id = $id_order ? $id_order : $this->id;
		return	(parent::$Izberg->Call($this->_name.'/'.$id.'/'.$status.'/', 'POST'));
	}
}

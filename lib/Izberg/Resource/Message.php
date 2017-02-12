<?php
namespace Izberg\Resource;
use Izberg\Resource;

class Message extends Resource
{
	/*
	**	Mark message as read
	*/
	public function read()
	{
		return self::$Izberg->Call($this->getName()."/". $this->id ."/read/", "POST");
	}

	/*
	**	Mark message as read
	*/
	public function close()
	{
		return self::$Izberg->Call($this->getName()."/". $this->id ."/close/", "POST");
	}

	/*
	**	Get all messages
	*/
	public static function get_list()
	{
		return self::$Izberg->Call("message/current_app/all_messages/", "GET");
	}
}

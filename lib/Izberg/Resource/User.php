<?php
namespace Izberg\Resource;
use Izberg\Resource;

class User extends Resource
{
	public function getAddresses()
	{
		return parent::$Izberg->get_list("address", null, array("user" => $this->id), "Content-Type:Application/json");
	}

	public function getApplications()
	{
		return parent::$Izberg->get_list("application", null, array("contact_user" => $this->id), "Content-Type:Application/json");
	}

	public function getReviews()
	{
		return parent::$Izberg->get_list("review", null, array("user" => $this->id), "Content-Type:Application/json");

	}
	public function getProfile()
	{
		return parent::$Izberg->get("profile", $this->id."/profile/", null, null, $this->getName());
	}

	public function getInbox()
	{
		return parent::$Izberg->get_list("message", array(), "Accept: application/json", "user/" . $this->id . "/inbox/");
	}

	public function getOutbox()
	{
		return parent::$Izberg->get_list("message", array(), "Accept: application/json", "user/" . $this->id . "/outbox/");
	}
}

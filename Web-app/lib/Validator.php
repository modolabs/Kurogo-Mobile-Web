<?php

class Validator
{
	function isValidEmail($emailAddress) 
	{
		if (!is_string($emailAddress)) {
			return false;
		}
		$pattern = "/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/";
		return preg_match($pattern, $emailAddress);
	}

	function isValidPhone($phone)
	{
		if (!is_scalar($phone)) {
			return false;
		}
		$pattern = '/^\(?(\d\d\d)?[-).\s]*(\d\d\d)[-.\s]?(\d\d\d\d)$/';
		return preg_match($pattern, $phone);
	}
}

?>
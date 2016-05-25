<?php

use Database\MySQL\Select;

class FunctionsTest extends PHPUnit_Framework_TestCase
{

	public function testSelectUserWithPassword()
	{
		$select = new Select(["table" => "users", "where" => ["#login" => "email", "#password" => ["PASSWORD" => "value"]]], ["prefix" => "prfx_"]);
		
		$this->assertEquals($select->getRawQuery(), "SELECT * FROM `prfx_users` WHERE `login` = `email` AND `password` = PASSWORD(:__p0)");
	}

}

<?php

use Database\MySQL\Update;

class UpdateTest extends PHPUnit_Framework_TestCase
{

	public function testUpdate()
	{
		$update = new Update(["table" => "users", "where" => ["id" => 1], "data" => ["login" => "login", "time" => "22:22:22"]], ["prefix" => "prfx_"]);

		$this->assertEquals($update->getRawQuery(), "UPDATE `prfx_users` SET `login` = :login, `time` = :time WHERE `id` = 1");
	}

	public function testUpdateFunctions()
	{
		$update = new Update(["table" => "users", "where" => ["id" => 1], "data" => ["#login" => "email", "#time" => "NOW"]], ["prefix" => "prfx_"]);

		$this->assertEquals($update->getRawQuery(), "UPDATE `prfx_users` SET `login` = `email`, `time` = NOW() WHERE `id` = 1");
	}

}

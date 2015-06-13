<?php

use Database\MySQL\Select;

class SimpleSelectTest extends PHPUnit_Framework_TestCase
{

	public function testSelectAll()
	{
		$select = new Select(["table" => "table"], ["prefix" => "prfx_"]);
		
		$this->assertEquals($select->getRawQuery(), "SELECT * FROM `prfx_table`");
	}

}

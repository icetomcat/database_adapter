<?php

use Database\MySQL\Select;

class SimpleSelectTest extends PHPUnit_Framework_TestCase
{

	public function testSelectAll()
	{
		$select = new Select(["table" => "table"], ["prefix" => "prfx_"]);

		$this->assertEquals($select->getRawQuery(), "SELECT * FROM `prfx_table`");
	}
	
	public function testSelectNamedConstant()
	{
		$select = new Select(["columns" => ["#value(var)"]]);

		$this->assertEquals($select->getRawQuery(), "SELECT :__p0 AS `var`");
	}

	public function testSelectConstSumm()
	{
		$select = new Select(["columns" => ["+" => [1, 2, 3]]]);

		$this->assertEquals($select->getRawQuery(), "SELECT (1 + 2 + 3)");
	}

}

<?php

use Database\Base\AbstractQuery;

class Order extends AbstractQuery
{

	use Database\MySQL\Traits\ColumnsTrait,
	 Database\MySQL\Traits\OrderTrait;

	public function getRawQuery()
	{
		return $this->makeOrderSection();
	}

}

class OrderTest extends PHPUnit_Framework_TestCase
{

	public function testOrderBy()
	{
		$order = new Order(["order" => "position DESC"]);
		$this->assertEquals($order->getRawQuery(), "`position` DESC");
	}

	public function testOrderByArray()
	{
		$order = new Order(["order" => ["position ASC", "category_id DESC"]]);
		$this->assertEquals($order->getRawQuery(), "`position` ASC, `category_id` DESC");
	}

	public function testOrderByField()
	{
		$order = new Order(["order" => ["FIELD" => ["category_id", 1, 2, 3, 4, 5]]]);
		$this->assertEquals($order->getRawQuery(), "FIELD(`category_id`, 1,2,3,4,5)");
	}

	public function testOrderByFieldWithMixedData()
	{
		$order = new Order(["order" => ["FIELD" => ["category_id", 1, "2", 3.5]]]);
		$this->assertEquals($order->getRawQuery(), "FIELD(`category_id`, 1,?,3.5)");
	}

	public function testOrderByFieldInSet()
	{
		$order = new Order(["order" => ["FIELD_IN_SET" => ["category_id", "1, 2, 3, 4, 5"]]]);
		$this->assertEquals($order->getRawQuery(), "FIELD_IN_SET(`category_id`, ?)");
	}

}

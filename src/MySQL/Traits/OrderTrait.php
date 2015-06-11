<?php

namespace Database\MySQL\Traits;

use Exception;

trait OrderTrait
{

	public function makeOrder($order)
	{
		$array = explode(" ", $order, 2);
		if (isset($array[0], $array[1]))
		{
			if ($array[1] == "ASC" || $array[1] == "DESC")
			{
				return "{$this->makeColumn(0, $array[0])} {$array[1]}";
			}
			else
			{
				throw new Exception();
			}
		}
		else
		{
			return "{$this->makeColumn(0, $array[0])} ASC";
		}
	}

	public function makeOrderSection()
	{
		if (isset($this->query["order"]))
		{
			$order = $this->query["order"];
			$stack = [];
			if (is_string($order))
			{
				array_push($stack, $this->makeOrder($order));
			}
			elseif (is_array($order))
			{
				foreach ($order as $key => $value)
				{
					if (is_string($value))
					{
						array_push($stack, $this->makeOrder($value));
					}
					elseif (is_array($value))
					{
						switch ($key)
						{
							case "FIELD":
								if (isset($value[0]) && isset($value[1]) && is_string($value[0]))
								{
									$column = $this->makeColumn(0, array_shift($value));
									array_push($stack, "FIELD($column, {$this->arrayQuote($value)})");
								}
								break;
							case "FIELD_IN_SET":
								if (isset($value[0]) && isset($value[1]) && is_string($value[0]))
								{
									$column = $this->makeColumn(0, array_shift($value));
									if (count($value) == 1)
									{
										array_push($stack, "FIELD_IN_SET($column, {$this->addParam($value)})");
									}
									else
									{
										array_push($stack, "FIELD_IN_SET($column, {$this->arrayQuote($value)})");
									}
								}
								break;
							default:
								throw new Exception();
						}
					}
				}
			}
			else
			{
				throw new Exception();
			}
			return implode(", ", $stack);
		}
		return null;
	}
}

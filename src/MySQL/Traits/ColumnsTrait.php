<?php
namespace Database\MySQL\Traits;

use Database\MySQL\Select;
use Exception;

trait ColumnsTrait
{

	protected function makeColumnFn($key, $value)
	{
		$infix = false;
		$count = [null];

		preg_match('/([a-zA-Z0-9_\-\.\+\-\*\/]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $key, $match);
		if (isset($match[1], $match[2]))
		{
			$fn = $match[1];
			$alias = $match[2];
		}
		else
		{
			$fn = $key;
			$alias = null;
		}
		switch ($fn)
		{
			case "EXISTS":
			case "COUNT":
				$count = [1];
				break;
			case "+":
			case "-":
			case "*":
			case "/":
				$count = [2];
				$infix = true;
				break;
			case "RAND":
				$count = [1, 0];
				break;
			case "PI":
				$count = [0];
				break;
			case "MOD":
				$count = [2];
				break;
			default :
				return null;
		}
		foreach ($count as $i)
		{
			if ((is_null($i)) || (is_array($value) && (!is_null($i) && (count($value) == $i))))
			{
				$stack = [];
				foreach ($value as $k => $v)
				{
					array_push($stack, $this->makeColumn($k, $v));
				}
				if ($infix)
				{
					return "(" . implode(" {$fn} ", $stack) . ")" . ($alias ? " AS {$this->columnQuote($alias)}" : "");
				}
				else
				{
					return "{$fn}(" . implode(", ", $stack) . ")" . ($alias ? " AS {$this->columnQuote($alias)}" : "");
				}
			}
		}

		return null;
	}

	protected function makeColumn($key, $value)
	{
		if (is_array($value) && isset($value["table"]))
		{
			return (new Select($value, $this->context, $this->params))->getRawQuery();
		}
		if ($value == "*")
		{
			return $value;
		}
		if (is_string($key))
		{
			$array = explode(".", $key, 2);
		}
		else
		{
			$array = explode(".", $value, 2);
		}
		if (isset($array[1]) && $array[1] == "*")
		{
			if (in_array($array[0], $this->context["table_aliases"]))
			{
				return $this->columnQuote($array[0]) . ".*";
			}
			else
			{
				return $this->columnQuote($this->context["prefix"] . $array[0]) . ".*";
			}
		}
		elseif (is_string($value) && is_integer($key))
		{
			preg_match('/([a-zA-Z0-9_\-\.]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $value, $match);

			if (isset($match[1], $match[2]))
			{
				if (in_array($array[0], $this->context["table_aliases"]))
				{
					return $this->columnQuote($match[1]) . ' AS ' . $this->columnQuote($match[2]);
				}
				else
				{
					return $this->columnQuote($this->context["prefix"] . $match[1]) . ' AS ' . $this->columnQuote($match[2]);
				}
			}
			else
			{
				$fn = $this->makeColumnFn($value, []);
				if ($fn)
				{
					return $fn;
				}
				if (in_array($array[0], $this->context["table_aliases"]))
				{
					return (isset($array[1]) ? $this->columnQuote($array[0]) . "." . $this->columnQuote($array[1]) : $this->columnQuote($value));
				}
				else
				{
					return (isset($array[1]) ? $this->columnQuote($this->context["prefix"] . $array[0]) . "." . $this->columnQuote($array[1]) : $this->columnQuote($value));
				}
			}
		}
		elseif (isset($array[1]))
		{
			return $this->columnQuote($this->context["prefix"] . $array[0]) . "." . $this->columnQuote($array[1]) . (is_string($key) ? " AS " . $this->columnQuote($value) : "");
		}
		elseif (is_string($key))
		{
			$fn = $this->makeColumnFn($key, $value);
			if ($fn)
			{
				return $fn;
			}
			throw new Exception();
		}
		elseif (is_integer($value))
		{
			return "{$value}";
		}
		else
		{
			throw new Exception();
		}
	}

	protected function makeColumnsSection()
	{

		if (!isset($this->query["columns"]) || empty($this->query["columns"]))
		{
			return "*";
		}
		$columns = $this->query["columns"];
		if (is_string($columns))
		{
			$columns = [$columns];
		}

		if (is_array($columns))
		{
			$stack = [];
			foreach ($columns as $key => $value)
			{
				array_push($stack, $this->makeColumn($key, $value));
			}

			return implode(", ", $stack);
		}
		else
		{
			throw new Exception("Error, 'columns' must be an 'array' or 'string', '" . gettype($this->query["columns"]) . "' given");
		}
	}

}
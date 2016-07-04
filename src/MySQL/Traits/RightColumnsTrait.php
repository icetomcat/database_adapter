<?php

namespace Database\MySQL\Traits;

use Database\MySQL\Helper\ReflectionMySQLFunction;
use Database\MySQL\Select;
use Exception;

trait RightColumnsTrait
{

	protected function makeRightColumnFn($key, $value)
	{
		if (is_scalar($value))
		{
			$value = [$value];
		}
		if (preg_match('/(#?)([a-zA-Z0-9_\-\.\+\*\/]*)\s*/i', $key, $match))
		{
			$fn = $match[2];
			$reflection = new ReflectionMySQLFunction($fn);

			if (!$reflection->isDefined())
			{
				return null;
			}

			if (is_array($value) && (count($value) >= $reflection->getNumberOfRequiredParameters() && count($value) <= $reflection->getNumberOfParameters()))
			{
				$stack = [];
				foreach ($value as $k => $v)
				{
					if (($key[0] == "#") || is_array($v))
					{
						array_push($stack, $this->makeRightColumn($k, $v));
					}
					elseif (is_scalar($v))
					{
						array_push($stack, $this->addParam($v));
					}
				}
				if ($reflection->isInfix())
				{
					return "(" . implode(" {$fn} ", $stack) . ")";
				}
				else
				{
					return "{$fn}(" . implode(", ", $stack) . ")";
				}
			}
		}
		else
		{
			throw new Exception();
		}

		return null;
	}

	protected function makeRightColumn($key, $value)
	{
		if (is_array($value) && isset($value["table"]))
		{
			return (new Select($value, $this->context, $this->params))->getRawQuery();
		}

		if (is_string($key))
		{
			$array = explode(".", $key, 2);
		}
		else
		{
			$array = explode(".", $value, 2);
		}
		if (is_string($value) && is_integer($key))
		{
			preg_match('/(#?)([a-zA-Z0-9_\-\.]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $value, $match);

			if (isset($match[2], $match[3]))
			{
				if (in_array($array[0], $this->context["table_aliases"]))
				{
					return $this->columnQuote($match[2]);
				}
				else
				{
					return $this->columnQuote($this->context["prefix"] . $match[2]);
				}
			}
			else
			{
				$fn = $this->makeRightColumnFn($value, []);
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
			return $this->columnQuote($this->context["prefix"] . $array[0]) . "." . $this->columnQuote($array[1]);
		}
		elseif (is_string($key))
		{
			$fn = $this->makeRightColumnFn($key, $value);
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

	protected function makeSetSection(array $data = [])
	{
		$stack = array();

		foreach ($data as $key => $value)
		{
			preg_match('/(#?)([\w]+)/i', $key, $match);


			$column = $this->columnQuote($key);

			switch (gettype($value))
			{
				case 'NULL':
					$stack[] = $column . ' = NULL';
					break;

				case 'array':
					if ((strpos($key, "#") === 0))
					{
						foreach ($value as $k => $v)
						{
							if (is_string($k) && ($fn = $this->makeRightColumnFn($k, $v)))
							{
								array_push($stack, $column . ' = ' . $fn);
							}
							elseif (is_integer($k) && ($fn = $this->makeRightColumnFn($v, [])))
							{
								array_push($stack, $column . ' = ' . $fn);
							}
							else
							{
								throw new Exception();
							}
						}
					}
					else
					{
						preg_match("/\(JSON\)\s*([\w]+)/i", $key, $column_match);

						$stack[] = isset($column_match[0]) ?
								$this->columnQuote($column_match[1]) . ' = ' . $this->addParam(json_encode($value), $key) :
								$column . ' = ' . $this->addParam(serialize($value), $key);
					}
					break;

				case 'boolean':
					$stack[] = $column . ' = ' . ($value ? '1' : '0');
					break;

				case 'integer':
				case 'double':
				case 'string':
					if ((strpos($key, "#") === 0))
					{
						$fn = $this->makeRightColumnFn($value, []);
						if ($fn)
						{
							$stack[] = $column . ' = ' . $fn;
						}
						else
						{
							$stack[] = $column . ' = ' . $this->makeRightColumn(0, $value);
						}
					}
					else
					{
						$stack[] = $column . ' = ' . $this->addParam($value, $key);
					}
					break;
			}
		}

		return implode(', ', $stack);
	}

}

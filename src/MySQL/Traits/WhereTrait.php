<?php

namespace Database\MySQL\Traits;

use Database\MySQL\Select;
use Exception;

trait WhereTrait
{

	protected function makeWhereCondition($key, $value)
	{
		$type = gettype($value);
		if (
				preg_match("/^(AND|OR)(\s+#.*)?$/i", $key, $relation_match) &&
				$type == "array"
		)
		{
			return "({$this->makeWhereBlock($value, $relation_match[1])})";
		}
		else
		{
			preg_match("/(#?)([\w\.]+)(\((\>|\>\=|\<|\<\=|\!\=|\<\>|\>\<|\!?~)\))?/i", $key, $match);
			if (!isset($match[2]))
			{
				throw new Exception();
			}
			else
			{
				$column = $this->makeColumn(0, $match[2]);
			}
			if (!isset($match[4]))
			{
				$operator = "=";
			}
			else
			{
				$operator = $match[4];
			}

			if ($operator == "=" || $operator == "!=")
			{
				switch ($type)
				{
					case "NULL":
						if ($operator == "=")
						{
							return $column . " IS NULL";
						}
						else
						{
							return $column . " IS NOT NULL";
						}
						break;

					case "array":

						if (count($value) > 0)
						{
							if (isset($value["table"]))
							{
								if ($operator == "=")
								{
									return $column . " IN ( " . (new Select($value, $this->context, $this->params))->getRawQuery() . " )";
								}
								else
								{
									return $column . " NOT IN ( " . (new Select($value, $this->context, $this->params))->getRawQuery() . " )";
								}
							}
							elseif ((strpos($key, "#") === 0))
							{
								$stack = [];
								foreach ($value as $k => $v)
								{
									if (is_string($k) && ($fn = $this->makeColumnFn($k, $v)))
									{
										array_push($stack, $fn);
									}
									elseif (is_integer($k) && ($fn = $this->makeColumnFn($v, [])))
									{
										array_push($stack, $fn);
									}
									else
									{
										throw new Exception();
									}
								}
								if (count($stack) == 1)
								{
									return $column . " $operator {$stack[0]}";
								}
								else
								{
									if ($operator == "=")
									{
										return $column . " IN (" . implode(", ", $stack) . ")";
									}
									else
									{
										return $column . " NOT IN (" . implode(", ", $stack) . ")";
									}
								}
							}

							if ($operator == "=")
							{
								return $column . " IN ({$this->arrayQuote($value)})";
							}
							else
							{
								return $column . " NOT IN ({$this->arrayQuote($value)})";
							}
						}
						else
						{
							return $column . " IN (NULL)";
						}
						break;
					case "integer":
					case "double":
						return "{$column} $operator {$value}";
						break;

					case "boolean":
						return "{$column} $operator " . ($value ? "1" : "0");
						break;

					case "string":
						if ((strpos($key, "#") === 0 && ($fn = $this->makeColumnFn($value, []))))
						{
							return "{$column} $operator {$fn}";
						}
						else
						{
							return "{$column} $operator {$this->addParam($value)}";
						}
						break;
				}
			}
			elseif ($operator == "<>" || $operator == "><")
			{

				if ($type == 'array')
				{
					if ($operator == "><")
					{
						$column .= " NOT";
					}
					if (is_numeric($value[0]) && is_numeric($value[1]))
					{
						return "({$column} BETWEEN {$value[0]} AND {$value[1]})";
					}
					else
					{
						//return "({$column} BETWEEN {$this->quote($value[0])} AND {$this->quote($value[1])})";
						throw new Exception();
					}
				}
			}
			elseif ($operator == "~" || $operator == "!~")
			{
				if ($type == "string")
				{
					if ($operator == "!~")
					{
						$column .= " NOT";
					}

					if (strpos($key, "#") === 0)
					{
						if ($fn = $this->makeColumnFn($value, []))
						{
							return "{$column} LIKE $fn";
						}
						else
						{
							return "{$column} LIKE " . $this->makeColumn(0, $value);
						}
					}
					else
					{
						if (preg_match("/^[^%].*[^%]$/", $value))
						{
							$value = "%{$value}%";
						}
						return "{$column} LIKE " . $this->addParam($value);
					}
				}
			}
			elseif ($operator == ">" || $operator == ">=" || $operator == "<" || $operator == "<=")
			{
				if (is_numeric($value))
				{
					return "{$column} {$operator} {$value}";
				}
				elseif (is_string($value))
				{
					return "{$column} {$operator} '{$value}'";
				}
				else
				{
					if (strpos($key, "#") === 0)
					{
						if (is_string($value) && $fn = $this->makeColumnFn($value, []))
						{
							return "{$column} $operator $fn";
						}
						elseif (is_array($value))
						{
							$stack = [];
							if (isset($value["table"]))
							{
								array_push($stack, (new Select($value, $this->context))->getRawQuery());
							}
							else
							{

								foreach ($value as $k => $v)
								{
									if (is_string($k) && ($fn = $this->makeColumnFn($k, $v)))
									{
										array_push($stack, $fn);
									}
									elseif (is_integer($k) && ($fn = $this->makeColumnFn($v, [])))
									{
										array_push($stack, $fn);
									}
									else
									{
										throw new Exception();
									}
								}
							}
							return $column . " $operator (" . implode(", ", $stack) . ")";
						}
						else
						{
							return "{$column} $operator " . $this->makeColumn(0, $value);
						}
					}
					else
					{
						throw new Exception();
					}
				}
			}
		}
	}

	protected function makeWhereBlock($where = [], $operator = "AND")
	{
		if (!$where)
		{
			return null;
		}
		if (!is_array($where))
		{
			return $where;
		}
		$stack = [];
		foreach ($where as $key => $value)
		{
			if ($key == "AND" || $key == "OR")
			{
				if ($block = $this->makeWhereBlock($value, $key))
				{
					array_push($stack, "({$block})");
				}
			}
			elseif (is_string($key))
			{
				if ($block = $this->makeWhereCondition($key, $value))
				{
					array_push($stack, $block);
				}
			}
			else
			{
				throw new Exception();
			}
		}

		return implode(" {$operator} ", $stack);
	}

	protected function makeWhereSection()
	{

		return $this->makeWhereBlock(isset($this->query["where"]) ? $this->query["where"] : []);
	}

}

<?php

namespace Database\MySQL\Traits;

use Exception;

trait JoinTrait
{

	protected function makeJoin($key, $value)
	{
		preg_match("/(\[(\<|\>|\>\<|\<\>)\])?([a-zA-Z0-9_\-]*)\s?(\(([a-zA-Z0-9_\-]*)\))?/", $key, $match);

		if (isset($match[2], $match[3]) && $match[2] !== "" && $match[3] !== "")
		{
			$result = "";
			switch ($match[2])
			{
				case ">":
					$result = "LEFT JOIN";
					break;
				case "<":
					$result = "RIGHT JOIN";
					break;
				case "><":
					$result = "INNER JOIN";
					break;
				case "<>":
					$result = "FULL JOIN";
					break;

				default:
					throw new Exception();
			}
			$result .= " {$this->quote($match[3])}";
			if (is_array($value))
			{
				$stack_using = [];
				$stack_on = [];
				foreach ($value as $k => $v)
				{
					if (is_integer($k))
					{
						$stack_using[] = $this->quote($v);
					}
					elseif (is_string($k))
					{
						$stack_on[$k] = $v;
					}
				}
				if (count($stack_using) > 0)
				{
					$result .= " USING(" . implode(", ", $stack_using) . ")";
				}
				if (count($stack_on) > 0)
				{
					$result .= " ON " . $this->makeWhereBlock($stack_on);
				}
			}
			return $result;
		}
		else
		{
			throw new Exception();
		}
	}

	public function makeJoinSection()
	{
		if (isset($this->query["join"]))
		{
			$stack = [];
			foreach ($this->query["join"] as $key => $value)
			{
				if (is_string($key))
				{
					array_push($stack, $this->makeJoin($key, $value));
				}
				else
				{
					array_push($stack, $this->makeJoin($value, 0));
				}
			}
			return implode(" ", $stack);
		}
		return null;
	}

}
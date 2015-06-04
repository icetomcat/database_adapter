<?php

namespace Services\Database\MySQL\Traits;

trait LimitTrait
{

	public function makeLimitSection()
	{
		if (isset($this->query["limit"]))
		{
			$limit = $this->query["limit"];
			if (is_array($limit) && isset($limit[0], $limit[1]) && is_numeric($limit[0]) && is_numeric($limit[1]))
			{
				return "{$limit[0]}, {$limit[1]}";
			}
			elseif (is_numeric($limit))
			{
				return "{$limit}";
			}
			elseif (is_string($limit))
			{
				$array = explode(",", str_replace(" ", "", $limit), 2);
				if (isset($array[0], $array[1]) && is_numeric($array[0]) && is_numeric($array[1]))
				{
					return "{$array[0]}, {$array[1]}";
				}
				else
				{
					throw new \Exception("Error, 'count' and 'offset' must be an numeric, '" . str_replace(["\n", "  "], "", print_r($limit, true)) . "' given");
				}
			}
			else
			{
				throw new \Exception("You have an error near '" . str_replace(["\n", "  "], "", print_r($limit, true)) . "'");
			}
		}
		return null;
	}

}
<?php

namespace Database\MySQL\Traits;

use Database\MySQL\Select;
use Exception;

trait TableTrait
{

	protected function makeTableSection()
	{
		if (isset($this->query["table"]))
		{
			$tabel = $this->query["table"];
			if (is_string($tabel))
			{
				if ($tabel === "SELECT")
				{
					return "(" . (new Select($tabel, $this->context))->getRawQuery() . ")";
				}
				else
				{
					return $this->quote($this->context["prefix"] . $tabel);
				}
			}
			elseif (is_array($tabel))
			{
				$stack = [];
				foreach ($tabel as $key => $value)
				{
					if (is_array($value))
					{
						if (is_string($key) && $key)
						{
							array_push($stack, "(" . (new Select($value, $this->context, $this->params))->getRawQuery() . ") AS " . $this->quote($key));
							$this->context["table_aliases"][] = $key;
						}
						else
						{
							throw new Exception("Error, every derived table must have its own alias, '" . str_replace(["\n", "  "], "", print_r($tabel, true)) . "' given");
						}
					}
					else
					{
						if (is_string($key) && $key)
						{
							array_push($stack, $this->quote($this->context["prefix"] . $value) . " AS " . $this->quote($key));
							$this->context["table_aliases"][] = $key;
						}
						else
						{
							array_push($stack, $this->quote($this->context["prefix"] . $value));
						}
					}
				}

				return implode(", ", $stack);
			}
			else
			{
				throw new Exception("Error, 'table' must be an array or string, '" . gettype($tabel) . "' given");
			}
		}
		else
		{
			return null;
		}
	}

}
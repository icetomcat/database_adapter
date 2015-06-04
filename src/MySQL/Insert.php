<?php

namespace Database\MySQL;

use Database\Base\AbstractQuery;

class Insert extends AbstractQuery
{

	public function getRawQuery()
	{
		if (!$this->raw_query)
		{
			$table = $this->query["table"];
			$data = $this->query["data"];
			if (isset($this->query["ignore"]))
			{
				$ignore = $this->query["ignore"];
			}
			else
			{
				$ignore = false;
			}
			$keys = array_keys($data);
			$this->params = array();
			$columns = array();

			foreach ($data as $key => $value)
			{
				array_push($columns, $this->columnQuote($key));

				switch (gettype($value))
				{
					case 'NULL':
						$this->addParam('NULL', $key);
						break;

					case 'array':
						preg_match("/\(JSON\)\s*([\w]+)/i", $key, $column_match);
						$this->addParam(isset($column_match[0]) ?
										$this->quote(json_encode($value)) :
										$this->quote(serialize($value)), $key);
						break;

					case 'boolean':
						$this->addParam($value ? '1' : '0', $key);
						break;

					case 'integer':
					case 'double':
					case 'string':
						$this->addParam($value, $key);
						break;
				}
			}
			$this->raw_query = 'INSERT' . ($ignore ? ' IGNORE' : '') . ' INTO "' . $this->context["prefix"] . $table . '" (' . implode(', ', $columns) . ') VALUES (:' . implode(", :", $keys) . ')';
		}

		return $this->raw_query;
	}

	public function execute($params = null)
	{
		parent::execute($params);
		return $this->context["adapter"]->lastInsertId();
	}

}

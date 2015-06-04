<?php

namespace Services\Database\MySQL;

class Update extends \Services\Database\Base\AbstractQuery
{

	use Traits\ColumnsTrait,
	 Traits\TableTrait,
	 Traits\WhereTrait;

	public function getRawQuery()
	{
		if (!$this->raw_query)
		{
			$table = $this->query["table"];
			$data = $this->query["data"];
			$fields = array();

			foreach ($data as $key => $value)
			{
				preg_match('/([\w]+)(\((\+|\-|\*|\/)\))?/i', $key, $match);

				if (isset($match[3]))
				{
					if (is_numeric($value))
					{
						$fields[] = $this->columnQuote($match[1]) . ' = ' . $this->columnQuote($match[1]) . ' ' . $match[3] . ' ' . $value;
					}
				}
				else
				{
					$column = $this->columnQuote($key);

					switch (gettype($value))
					{
						case 'NULL':
							$fields[] = $column . ' = NULL';
							break;

						case 'array':
							preg_match("/\(JSON\)\s*([\w]+)/i", $key, $column_match);

							$fields[] = isset($column_match[0]) ?
									$this->columnQuote($column_match[1]) . ' = ' . $this->addParam(json_encode($value)) :
									$column . ' = ' . $this->addParam(serialize($value));
							break;

						case 'boolean':
							$fields[] = $column . ' = ' . ($value ? '1' : '0');
							break;

						case 'integer':
						case 'double':
						case 'string':
							$fields[] = $column . ' = ' . $this->addParam($value, $key);
							break;
					}
				}
			}
			$this->raw_query = 'UPDATE `' . $this->context["prefix"] . $table . '` SET ' . implode(', ', $fields) . " WHERE " . $this->makeWhereSection();
		}
		return $this->raw_query;
	}

}
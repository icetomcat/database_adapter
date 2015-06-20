<?php

namespace Database\MySQL;

use Database\Base\AbstractQuery;

class Delete extends AbstractQuery
{

	use Traits\ColumnsTrait,
	 Traits\TableTrait,
	 Traits\WhereTrait;

	public function getRawQuery()
	{
		if (!$this->raw_query)
		{
			$where_section = $this->makeWhereSection();
			$this->raw_query = 'DELETE FROM `' . $this->context["prefix"] . $this->query["table"] . '`';
			if ($where_section)
			{
				$this->raw_query .= "WHERE {$where_section}";
			}
		}
		return $this->raw_query;
	}
}

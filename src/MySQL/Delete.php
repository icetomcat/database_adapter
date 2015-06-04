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

			$this->raw_query = 'DELETE FROM `' . $this->context["prefix"] . $this->query["table"] . '` WHERE ' . $this->makeWhereSection();
		}
		return $this->raw_query;
	}

}
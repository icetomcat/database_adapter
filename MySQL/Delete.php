<?php

namespace Services\Database\MySQL;

class Delete extends \Services\Database\Base\AbstractQuery
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
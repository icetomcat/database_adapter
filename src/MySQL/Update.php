<?php

namespace Database\MySQL;

use Database\Base\AbstractQuery;

class Update extends AbstractQuery
{

	use Traits\ColumnsTrait,
	 Traits\RightColumnsTrait,
	 Traits\TableTrait,
	 Traits\WhereTrait;

	public function getRawQuery()
	{
		if (!$this->raw_query)
		{
			$set_section = $this->makeSetSection($this->query["data"]);
			$where_section = $this->makeWhereSection();
			$this->raw_query = 'UPDATE `' . $this->context["prefix"] . $this->query["table"] . ($set_section ? '` SET ' . $set_section : "") . ($where_section ? " WHERE " . $where_section : "");
		}
		return $this->raw_query;
	}

	public function execute(array $params = array())
	{
		parent::execute($params);
		return $this->statment->rowCount();
	}

}

<?php

namespace Database\MySQL;

use Database\Base\AbstractQuery;
use Exception;
use PDO;

class Select extends AbstractQuery
{

	use Traits\ColumnsTrait,
	 Traits\TableTrait,
	 Traits\JoinTrait,
	 Traits\WhereTrait,
	 Traits\GroupTrait,
	 Traits\OrderTrait,
	 Traits\LimitTrait;

	public function getRawQuery()
	{
		if (!$this->raw_query)
		{
			$table_section = $this->makeTableSection();
			$columns_section = $this->makeColumnsSection();
			$where_section = $this->makeWhereSection();
			$join_section = $this->makeJoinSection();
			$order_section = $this->makeOrderSection();
			$limit_section = $this->makeLimitSection();
			$result = null;

			if ($columns_section)
			{
				$result = "SELECT {$columns_section}";
			}
			if ($table_section)
			{
				$result .= " FROM {$table_section}";
			}
			if ($join_section)
			{
				$result .= " {$join_section}";
			}
			if ($where_section)
			{
				$result .= " WHETE {$where_section}";
			}
			if ($order_section)
			{
				$result .= " ORDER by {$order_section}";
			}
			if ($limit_section)
			{
				$result .= " LIMIT {$limit_section}";
			}

			if (!$result)
			{
				throw new Exception();
			}

			$this->raw_query = $result;
		}

		return $this->raw_query;
	}

	public function fetch()
	{
		return $this->getStatment()->fetch(PDO::FETCH_ASSOC);
	}

	public function fetchAll()
	{
		return $this->getStatment()->fetchAll(PDO::FETCH_ASSOC);
	}

}
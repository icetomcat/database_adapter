<?php

namespace Database;

use Database\Interfaces\ISchema;
use Exception;

class Schema implements ISchema
{

	/**
	 *
	 * @var string 
	 */
	protected $name;

	/**
	 *
	 * @var CType[] 
	 */
	protected $columns;

	/**
	 *
	 * @var string 
	 */
	protected $engine;

	/**
	 *
	 * @var string
	 */
	protected $collate;

	/**
	 *
	 * @var string 
	 */
	protected $charset;

	/**
	 *
	 * @var array
	 */
	protected $combinedIndexes;

	/**
	 *
	 * @var array
	 */
	protected $relations;
	protected $exclude = [];

	/**
	 * 
	 * @param string $name
	 * @param Type[] $columns
	 * @param array $combined_indexes ["PRIMARY" => [[id, col1, col2, ...]], "UNIQUE" => [[col1,col2,col3],[col1,col4], ...], "INDEX" => [[col1,col2,col3],[col1,col4], ...]]
	 * @param string[] $relations ["table_name1", "table_name2"]
	 * @param string $engine MyISAM | InnoDB | Memory ...
	 * @param string $collate utf8_general_ci ...
	 */
	public function __construct($name, $columns = [], $combined_indexes = [], $engine = "InnoDB", $collate = "utf8_general_ci", $charset = "utf8")
	{
		if (rtrim($name) == "")
		{
			trigger_error("", E_USER_ERROR);
		}
		$this->name = $name;
		$this->columns = $columns;
		$this->charset = $charset;
		$this->combinedIndexes = $combined_indexes;
		$this->relations = [];

		foreach ($this->columns as $key => $column)
		{
			if (in_array($column->getName(), $this->exclude))
			{
				trigger_error($column->getName() . ": this column is not acceptable", E_USER_WARNING);
				unset($this->columns[$key]);
			}
			else
			{
				$this->exclude[] = $column->getName();
			}
		}

		foreach ($this->columns as &$column)
		{
			if ($column->getIndex() == self::ROLE_PRIMARY)
			{
				$this->combinedIndexes[$column->getIndex()][1][] = $column->getName();
			}
			elseif ($column->getIndex() == self::ROLE_INDEX || $column->getIndex() == self::ROLE_UNIQUE)
			{
				$this->combinedIndexes[$column->getIndex()][] = [$column->getName()];
			}
		}

		$this->engine = $engine;
		$this->collate = $collate;
	}

	public function addCombinedIndex($index, $columns)
	{
		$this->combinedIndexes[$index][] = $columns;
		return $this;
	}

	public function addColumn(Type $column)
	{
		if (in_array($column->getName(), $this->exclude))
		{
			//trigger_error($this->name() . "." . $column->getName() . ": this column is not acceptable", E_USER_WARNING);
			throw new Exception();
		}
		if ($column->getIndex() == self::ROLE_PRIMARY)
		{
			$this->combinedIndexes[$column->getIndex()][1][] = $column->getName();
		}
		elseif ($column->getIndex() == self::ROLE_INDEX || $column->getIndex() == self::ROLE_UNIQUE)
		{
			$this->combinedIndexes[$column->getIndex()][] = [$column->getName()];
		}
		$this->exclude[] = $column->getName();
		$this->columns[] = $column;
		return $this;
	}

	/**
	 * 
	 * @return Type[]
	 */
	public function getColumns()
	{
		return $this->columns;
	}

	/**
	 * 
	 * @return array
	 */
	public function getCombinedIndexes()
	{
		return $this->combinedIndexes;
	}

	/**
	 * 
	 * @return array
	 */
	public function getForeignKeys()
	{
		return $this->foreignKeys;
	}

	/**
	 * 
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * 
	 * @return string
	 */
	public function getEngine()
	{
		return $this->engine;
	}

	/**
	 * 
	 * @return string
	 */
	public function getCollate()
	{
		return $this->collate;
	}

	/**
	 * 
	 * @return string
	 */
	public function getCharset()
	{
		return $this->charset;
	}

	public function integer($name, $default = null, $index = "", $autoIncrement = false)
	{
		$this->addColumn(Type::integer($name, $default, $index, $autoIncrement));
		return $this;
	}

	public function long($name, $default = null, $index = "", $autoIncrement = false)
	{
		$this->addColumn(Type::long($name, $default, $index, $autoIncrement));
		return $this;
	}

	public function string($name, $length = 255, $default = "", $index = "", $collation = "utf8_general_ci")
	{
		$this->addColumn(Type::string($name, $length, $default, $index, $collation));
		return $this;
	}

	public function char($name, $length = 255, $default = "", $index = "", $collation = "utf8_general_ci")
	{
		$this->addColumn(Type::char($name, $length, $default, $index, $collation));
		return $this;
	}

	public function text($name, $collation = "utf8_general_ci")
	{
		$this->addColumn(Type::text($name, $collation));
		return $this;
	}

	public function date($name, $default = "0000-00-00", $index = "")
	{
		$this->addColumn(Type::date($name, $default, $index));
		return $this;
	}

	public function datetime($name, $default = "0000-00-00 00:00:00", $index = "")
	{
		$this->addColumn(Type::datetime($name, $default, $index));
		return $this;
	}

	public function float($name, $default = "0.0")
	{
		$this->addColumn(new Type($name, "FLOAT", "", $default, "", "", false, false));
		return $this;
	}

	public function double($name, $default = "0.0")
	{
		$this->addColumn(Type::double($name, $default));
		return $this;
	}

	public function boolean($name, $default = false, $index = "")
	{
		$this->addColumn(Type::boolean($name, $default, $index));
		return $this;
	}

	public function addUniqueIndex($column = null, $group = null)
	{
		$this->addSomeIndex(self::ROLE_UNIQUE, $column, $group);
		return $this;
	}

	public function addIndex($column = null, $group = null)
	{
		$this->addSomeIndex(self::ROLE_INDEX, $column, $group);
		return $this;
	}

	public function addPrimaryIndex($column = null, $group = null)
	{
		$this->addSomeIndex(self::ROLE_PRIMARY, $column, $group);
		return $this;
	}

	protected function addSomeIndex($role, $column = null, $group = null)
	{
		if (!$column)
		{
			if (!$this->columns)
			{
				throw new Exception();
			}
			$column = end($this->columns)->getName();
		}
		if ($role == self::ROLE_PRIMARY)
		{
			if ($group)
			{
				trigger_error("Maybe error");
			}
			$this->combinedIndexes[$role][1][] = $column;
		}
		elseif ($role == self::ROLE_INDEX || $role == self::ROLE_UNIQUE)
		{
			if ($group)
			{
				$this->combinedIndexes[$role][$group][] = $column;
			}
			else
			{
				$this->combinedIndexes[$role][] = [$column];
			}
		}
	}

}

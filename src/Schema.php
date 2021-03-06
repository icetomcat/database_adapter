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
			if (isset($this->exclude[$column->getName()]))
			{
				trigger_error($column->getName() . ": this column is not acceptable", E_USER_WARNING);
				unset($this->columns[$key]);
			}
			else
			{
				$this->exclude[$column->getName()] = $column->getName();
			}
		}

		foreach ($this->columns as &$column)
		{
			if ($column->getIndex() == self::ROLE_PRIMARY)
			{
				$this->combinedIndexes["PRIMARY"][$column->getIndex()][$column->getName()] = $column->getName();
			}
			elseif ($column->getIndex() == self::ROLE_INDEX || $column->getIndex() == self::ROLE_UNIQUE)
			{
				$this->combinedIndexes[$column->getName()][$column->getIndex()][$column->getName()] = $column->getName();
			}
		}

		$this->engine = $engine;
		$this->collate = $collate;
	}

	public function addColumn(Type $column)
	{
		if (isset($this->exclude[$column->getName()]))
		{
			//trigger_error($this->name() . "." . $column->getName() . ": this column is not acceptable", E_USER_WARNING);
			throw new Exception();
		}
		if ($column->getIndex() == self::ROLE_PRIMARY)
		{
			$this->combinedIndexes["PRIMARY"][$column->getIndex()][$column->getName()] = $column->getName();
		}
		elseif ($column->getIndex() == self::ROLE_INDEX || $column->getIndex() == self::ROLE_UNIQUE)
		{
			$this->combinedIndexes[$column->getName()][$column->getIndex()][$column->getName()] = $column->getName();
		}
		$this->exclude[$column->getName()] = $column->getName();
		$this->columns[$column->getName()] = $column;
		return $this;
	}

	public function getColumn($name = null)
	{
		return is_string($name) ? (isset($this->columns[$name]) ? $this->columns[$name] : null) : end($this->columns);
	}

	public function column($name = null)
	{
		return $this->getColumn($name);
	}

	public function removeColumn($name)
	{
		$column = $this->getColumn($name);
		if ($column)
		{
			unset($this->exclude[$column->getName()]);
			if (isset($this->combinedIndexes[$column->getName()]))
			{
				unset($this->combinedIndexes[$column->getName()]);
			}
			$index_groups = array_keys($this->combinedIndexes);
			foreach ($index_groups as $group)
			{
				$index_roles = array_keys($this->combinedIndexes[$group]);
				foreach ($index_roles as $role)
				{
					if (isset($this->combinedIndexes[$group][$role][$name]))
					{
						unset($this->combinedIndexes[$group][$role][$name]);
					}
				}
			}
			unset($this->columns[$name]);
		}
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
		return $this->addColumn(Type::integer($name, $default, $index, $autoIncrement));
	}

	public function unsigned($name, $default = null, $index = "", $autoIncrement = false)
	{
		return $this->addColumn(Type::unsigned($name, $default, $index, $autoIncrement));
	}

	public function long($name, $default = null, $index = "", $autoIncrement = false)
	{
		return $this->addColumn(Type::long($name, $default, $index, $autoIncrement));
	}

	public function string($name, $length = 255, $default = "", $index = "", $collation = "utf8_general_ci")
	{
		return $this->addColumn(Type::string($name, $length, $default, $index, $collation));
	}

	public function char($name, $length = 255, $default = "", $index = "", $collation = "utf8_general_ci")
	{
		return $this->addColumn(Type::char($name, $length, $default, $index, $collation));
	}

	public function tinytext($name, $collation = "utf8_general_ci")
	{
		return $this->addColumn(Type::tinytext($name, $collation));
	}

	public function text($name, $collation = "utf8_general_ci")
	{
		return $this->addColumn(Type::text($name, $collation));
	}
	
	public function mediumtext($name, $collation = "utf8_general_ci")
	{
		return $this->addColumn(Type::mediumtext($name, $collation));
	}
	
	public function longtext($name, $collation = "utf8_general_ci")
	{
		return $this->addColumn(Type::longtext($name, $collation));
	}

	public function date($name, $default = "0000-00-00", $index = "")
	{
		return $this->addColumn(Type::date($name, $default, $index));
	}

	public function datetime($name, $default = "0000-00-00 00:00:00", $index = "")
	{
		return $this->addColumn(Type::datetime($name, $default, $index));
	}

	public function timestamp($name, $default = "0000-00-00 00:00:00", $index = "", $attribute = "")
	{
		return $this->addColumn(Type::timestamp($name, $default, $index, $attribute));
	}

	public function float($name, $default = "0.0")
	{
		return $this->addColumn(new Type($name, "FLOAT", "", $default, "", "", false, false));
	}

	public function double($name, $default = "0.0")
	{
		return $this->addColumn(Type::double($name, $default));
	}
	
	public function tinyblob($name, $collation = "utf8_general_ci")
	{
		return $this->addColumn(Type::tinyblob($name, $collation));
	}

	public function blob($name, $collation = "utf8_general_ci")
	{
		return $this->addColumn(Type::blob($name, $collation));
	}
	
	public function mediumblob($name, $collation = "utf8_general_ci")
	{
		return $this->addColumn(Type::mediumblob($name, $collation));
	}
	
	public function longblob($name, $collation = "utf8_general_ci")
	{
		return $this->addColumn(Type::longblob($name, $collation));
	}

	public function boolean($name, $default = false, $index = "")
	{
		return $this->addColumn(Type::boolean($name, $default, $index));
	}
	
	public function enum($name, array $enum = [], $default = null, $index = "", $collation = "utf8_general_ci")
	{
		return $this->addColumn(Type::enum($name, $enum, $default, $index, $collation));
	}

	public function unique()
	{
		return $this->addUniqueIndex();
	}

	public function index()
	{
		return $this->addIndex();
	}

	public function primary()
	{
		return $this->addPrimaryIndex();
	}

	public function addUniqueIndex($column = null, $group = null)
	{
		return $this->addSomeIndex(self::ROLE_UNIQUE, $column, $group);
	}

	public function addIndex($column = null, $group = null)
	{
		return $this->addSomeIndex(self::ROLE_INDEX, $column, $group);
	}

	public function addPrimaryIndex($column = null, $group = null)
	{
		return $this->addSomeIndex(self::ROLE_PRIMARY, $column, $group);
	}

	protected function addSomeIndex($role, $column = null, $group = null)
	{
		if (is_array($column))
		{
			if (!$group)
			{
				$group = reset($column);
			}
			foreach ($column as $col)
			{
				$this->addSomeIndex($role, $col, $group);
			}
		}
		else
		{
			if (!$column)
			{
				if (!$this->columns)
				{
					throw new Exception();
				}
				$column = end($this->columns)->getName();
			}
			if (!$group)
			{
				$group = $column;
			}
			if ($role == self::ROLE_PRIMARY)
			{
				if ($group)
				{
					trigger_error("Maybe error");
				}
				$this->combinedIndexes["PRIMARY"][$role][$column] = $column;
			}
			elseif ($role == self::ROLE_INDEX || $role == self::ROLE_UNIQUE)
			{
				if (!isset($this->combinedIndexes[$group]) || (isset($this->combinedIndexes[$group][$role])))
				{
					$this->combinedIndexes[$group][$role][$column] = $column;
				}
				else
				{
					throw new \Exception();
				}
			}
		}
		return $this;
	}

}

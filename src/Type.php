<?php

namespace Database;

use Database\Interfaces\IType;

class Type implements IType
{

	protected $name;
	protected $type;
	protected $length;
	protected $default;
	protected $collate;
	protected $null;
	protected $index;
	protected $autoIncrement;
	protected $attribute;

	/**
	 * @param string $name имя поля
	 * @param string $type тип поля (VARCAHR, INT, ...)
	 * @param string $length длина
	 * @param string $default значение по умолчанию
	 * @param string $collate сравнение (utf8_general_ci, ...)
	 * @param string $index индекс, ключевое поле или уникальное поле
	 * @param bool $autoIncrement автоинкремент
	 * @param bool $null нуль
	 */
	public function __construct($name, $type, $length = "", $default = "", $collate = "", $index = "", $autoIncrement = false, $null = false, $attribute = "")
	{
		$this->name = $name;
		$this->type = $type;
		$this->length = $length;
		$this->default = is_null($default) ? "" : $default;
		$this->collate = $collate;
		$this->index = $index;
		$this->autoIncrement = $autoIncrement;
		$this->null = $null;
		$this->attribute = $attribute;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getType()
	{
		return $this->type;
	}

	public function getLength()
	{
		return $this->length;
	}

	public function getIndex()
	{
		return $this->index;
	}

	public function isNull()
	{
		return $this->null;
	}

	public function getCollate()
	{
		return $this->collate;
	}

	public function getDefault()
	{
		return $this->default;
	}

	public function isAutoIncrement()
	{
		return $this->autoIncrement;
	}

	public function getAttribute()
	{
		return $this->attribute;
	}

	static public function integer($name, $default = null, $index = "", $autoIncrement = false)
	{
		return new Type($name, "INT", "11", $default, "", $index, $autoIncrement, $default ? true : false);
	}

	static public function unsigned($name, $default = null, $index = "", $autoIncrement = false)
	{
		return new Type($name, "INT", "11", $default, "", $index, $autoIncrement, $default ? true : false, "UNSIGNED");
	}

	static public function long($name, $default = null, $index = "", $autoIncrement = false)
	{
		return new Type($name, "BIGINT", "20", $default, "", $index, $autoIncrement, $default ? true : false);
	}

	static public function char($name, $length = "255", $default = "", $index = "", $collation = "utf8_general_ci")
	{
		return new Type($name, "CHAR", $length, $default, $collation, $index, false, $default ? true : false);
	}

	static public function string($name, $length = "255", $default = "", $index = "", $collation = "utf8_general_ci")
	{
		return new Type($name, "VARCHAR", $length, $default, $collation, $index, false, $default ? true : false);
	}

	static public function text($name, $collation = "utf8_general_ci")
	{
		return new Type($name, "TEXT", "", "", $collation, "", false, false);
	}

	static public function date($name, $default = "0000-00-00", $index = "")
	{
		return new Type($name, "DATE", "", $default, "", $index, false, false);
	}

	static public function datetime($name, $default = "0000-00-00 00:00:00", $index = "")
	{
		return new Type($name, "DATETIME", "", $default, "", $index, false, false);
	}

	static public function timestamp($name, $default = "0000-00-00 00:00:00", $index = "", $attribute = "")
	{
		return new Type($name, "TIMESTAMP", "", $default, "", $index, false, true, $attribute);
	}

	static public function float($name, $default = "0.0")
	{
		return new Type($name, "FLOAT", "", $default, "", "", false, false);
	}

	static public function double($name, $default = "0.0")
	{
		return new Type($name, "DOUBLE", "", $default, "", "", false, false);
	}

	static public function boolean($name, $default = false, $index = "")
	{
		return new Type($name, "TINYINT", 1, $default ? "1" : "0", "", $index, false, true);
	}

}

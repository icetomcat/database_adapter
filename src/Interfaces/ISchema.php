<?php

namespace Database\Interfaces;

interface ISchema
{

	const ROLE_PRIMARY = "PRIMARY";
	const ROLE_UNIQUE = "UNIQUE";
	const ROLE_INDEX = "INDEX";
	const DEFAULT_CURRENT_TIMESTAMP = "#CURRENT_TIMESTAMP";

	/**
	 * 
	 * @return IDbType Description
	 */
	public function getColumns();

	/**
	 * 
	 * @param string $name
	 * @param string $default
	 * @param string $index
	 * @param string $autoIncrement
	 * @return ISchema
	 */
	public function integer($name, $default = null, $index = '', $autoIncrement = false);

	/**
	 * 
	 * @param string $name
	 * @param string $default
	 * @param string $index
	 * @param boolean $autoIncrement
	 * @return ISchema
	 */
	public function unsigned($name, $default = null, $index = "", $autoIncrement = false);

	/**
	 * 
	 * @param string $name
	 * @param string $default
	 * @param string $index
	 * @param string $autoIncrement
	 * @return ISchema
	 */
	public function long($name, $default = null, $index = '', $autoIncrement = false);

	/**
	 * 
	 * @param string $name
	 * @param string $length
	 * @param string $default
	 * @param string $index
	 * @param string $collation
	 * @return ISchema
	 */
	public function char($name, $length = 255, $default = "", $index = "", $collation = "utf8_general_ci");

	/**
	 * 
	 * @param string $name
	 * @param string $length
	 * @param string $default
	 * @param string $index
	 * @param string $collation
	 * @return ISchema
	 */
	public function string($name, $length = 255, $default = "", $index = "", $collation = "utf8_general_ci");

	/**
	 * 
	 * @param string $name
	 * @param string $collation
	 * @return ISchema
	 */
	public function tinytext($name, $collation = "utf8_general_ci");

	/**
	 * 
	 * @param string $name
	 * @param string $collation
	 * @return ISchema
	 */
	public function text($name, $collation = "utf8_general_ci");

	/**
	 * 
	 * @param string $name
	 * @param string $collation
	 * @return ISchema
	 */
	public function mediumtext($name, $collation = "utf8_general_ci");

	/**
	 * 
	 * @param string $name
	 * @param string $collation
	 * @return ISchema
	 */
	public function longtext($name, $collation = "utf8_general_ci");

	/**
	 * 
	 * @param string $name
	 * @param string $default
	 * @param string $index
	 * @return ISchema
	 */
	public function date($name, $default = "0000-00-00", $index = '');

	/**
	 * 
	 * @param string $name
	 * @param string $default
	 * @param string $index
	 * @return ISchema
	 */
	public function datetime($name, $default = "0000-00-00 00:00:00", $index = '');

	/**
	 * 
	 * @param string $name
	 * @param string $default
	 * @param string $index
	 * @param string $attribute
	 * @return ISchema
	 */
	public function timestamp($name, $default = "0000-00-00 00:00:00", $index = "", $attribute = "");

	/**
	 * 
	 * @param string $name
	 * @param string $default
	 * @return ISchema
	 */
	public function float($name, $default = "0.0");

	/**
	 * 
	 * @param string $name
	 * @param string $default
	 * @return ISchema
	 */
	public function double($name, $default = "0.0");

	/**
	 * 
	 * @param string $name
	 * @param string $collation
	 * @return ISchema
	 */
	public function tinyblob($name, $collation = "utf8_general_ci");

	/**
	 * 
	 * @param string $name
	 * @param string $collation
	 * @return ISchema
	 */
	public function blob($name, $collation = "utf8_general_ci");

	/**
	 * 
	 * @param string $name
	 * @param string $collation
	 * @return ISchema
	 */
	public function mediumblob($name, $collation = "utf8_general_ci");

	/**
	 * 
	 * @param string $name
	 * @param string $collation
	 * @return ISchema
	 */
	public function longblob($name, $collation = "utf8_general_ci");

	/**
	 * 
	 * @param string $name
	 * @param string $default
	 * @param string $index
	 * @return ISchema
	 */
	public function boolean($name, $default = false, $index = '');

	/**
	 * 
	 * @param string $name
	 * @param array $enum
	 * @param string $default
	 * @param string $index
	 */
	public function enum($name, array $enum = [], $default = null, $index = "");

	/**
	 * 
	 * @param string $column
	 * @param string $group
	 * @return ISchema
	 */
	public function addUniqueIndex($column = null, $group = null);

	/**
	 * 
	 * @param string $column
	 * @param string $group
	 * @return ISchema
	 */
	public function addIndex($column = null, $group = null);

	/**
	 * 
	 * @param string $column
	 * @param string $group
	 * @return ISchema
	 */
	public function addPrimaryIndex($column = null, $group = null);
}

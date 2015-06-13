<?php

namespace Database\Interfaces;

interface ISchema
{

	const ROLE_PRIMARY = "PRIMARY";
	const ROLE_UNIQUE = "UNIQUE";
	const ROLE_INDEX = "INDEX";

	public function addCombinedIndex($index, $columns);

	/**
	 * 
	 * @return IDbType Description
	 */
	public function getColumns();

	public function integer($name, $default = null, $index = '', $autoIncrement = false);

	public function long($name, $default = null, $index = '', $autoIncrement = false);

	public function char($name, $length = 255, $default = "", $index = "", $collation = "utf8_general_ci");

	public function string($name, $length = 255, $default = "", $index = "", $collation = "utf8_general_ci");

	public function text($name, $collation = "utf8_general_ci");

	public function date($name, $default = "0000-00-00", $index = '');

	public function datetime($name, $default = "0000-00-00 00:00:00", $index = '');

	public function float($name, $default = "0.0");

	public function double($name, $default = "0.0");

	public function boolean($name, $default = false, $index = '');
}

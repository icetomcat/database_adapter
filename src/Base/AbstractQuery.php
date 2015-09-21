<?php

namespace Database\Base;

use Database\Exceptions\NoAdapterException;
use PDOStatement;

abstract class AbstractQuery
{

	protected $query;
	protected $context = [];
	protected $params;
	protected $raw_query = null;
	protected $key_iterator = 0;

	/**
	 *
	 * @var PDOStatement
	 */
	protected $statment = null;

	public function __construct($query, $context = [], &$params = [])
	{
		$this->query = $query;
		$this->context = $context;
		$this->params = &$params;

		if (!isset($this->context["prefix"]))
		{
			if (isset($this->context["adapter"]))
			{
				$this->context["prefix"] = $this->context["adapter"]->getPrefix();
			}
			else
			{
				$this->context["prefix"] = "";
			}
		}

		if (!isset($this->context["table_aliases"]))
		{
			$this->context["table_aliases"] = [];
		}
	}

	public function getQuery()
	{
		return $this->query;
	}

	public function quote($string)
	{
		return "`{$string}`";
	}

	/**
	 * 
	 * @return array
	 */
	protected function getParams()
	{
		return $this->params;
	}

	protected function addParam($param, $key = null)
	{
		if (!$key)
		{
			$key = "__p" . ($this->key_iterator++);
		}
		$this->params[":$key"] = $param;
		return ":$key";
	}

	protected function columnQuote($string)
	{
		return "`" . str_replace(".", "`.`", preg_replace("/(^#|\(JSON\))/", "", $string)) . "`";
	}

	protected function arrayQuote($array)
	{
		$temp = array();

		foreach ($array as $value)
		{
			$temp[] = is_int($value) ? $value : is_float($value) ? $value : $this->addParam($value);
		}

		return implode(",", $temp);
	}

	public function execute(array $params = [])
	{
		if (!isset($this->context["adapter"]))
		{
			throw new NoAdapterException();
		}

		if (is_array($params))
		{
			foreach ($params as $key => $value)
			{
				$this->params[$key] = $value;
			}
		}
		try
		{
			$this->statment = $this->context["adapter"]->statment($this->getRawQuery());
			return $this->statment->execute($this->params);
		}
		catch (\PDOException $exc)
		{
			throw new \PDOException($exc->getMessage() . " ( " . $this->getRawQuery() . " )", null, $exc);
		}
	}

	public function getStatment()
	{
		if (!$this->statment)
		{
			$this->execute();
		}
		return $this->statment;
	}

	abstract public function getRawQuery();
}

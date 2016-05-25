<?php

namespace Database\MySQL\Helper;

class ReflectionMySQLFunction
{

	static $signatures = null;
	protected $name = null;
	protected $defined = false;
	protected $reflection = ["infix" => false, "number_of_parameters" => INF, "number_of_required_parameters" => 0];

	public function __construct($name)
	{
		if (is_null(static::$signatures))
		{
			static::$signatures = json_decode(file_get_contents(__DIR__ . "/signatures.json"), true);
		}
		$this->name = $name;
		if (isset(static::$signatures[$this->name]))
		{
			$this->reflection = static::$signatures[$this->name];
			
			if ($this->reflection["number_of_parameters"] === "INF")
				$this->reflection["number_of_parameters"] = INF;
			
			if ($this->reflection["number_of_required_parameters"] === "INF")
				$this->reflection["number_of_required_parameters"] = INF;
			
			$this->defined = true;
		}
	}

	static public function create($name)
	{
		return new static($name);
	}

	public function getName()
	{
		return $this->name;
	}

	public function isDefined()
	{
		return $this->defined;
	}

	public function isInfix()
	{
		return $this->reflection["infix"];
	}

	public function getNumberOfParameters()
	{
		return $this->reflection["number_of_parameters"];
	}

	public function getNumberOfRequiredParameters()
	{
		return $this->reflection["number_of_required_parameters"];
	}

}

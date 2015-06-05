<?php

namespace Database;

use Exception;
use PDO;
use PDOException;

class Adapter
{

	/**
	 *
	 * @var array
	 */
	protected $config = array();

	/**
	 *
	 * @var PDO
	 */
	protected $pdo = null;

	/**
	 *
	 * @var string
	 */
	protected $prefix = "";
	protected $log = [];
	protected $transaction = false;
	protected $namespace = "";

	public function __construct(array $config = [])
	{
		$this->config = $config;
		$this->prefix = $this->config["prefix"];
		$this->connect();
	}

	public function getPrefix()
	{
		return $this->prefix;
	}

	public function connect()
	{
		try
		{
			$commands = array();

			if (isset($this->config["post"]) && is_int($this->config["post"] * 1))
			{
				$port = $this->port;
			}
			$type = strtolower($this->config["type"]);
			$is_port = isset($port);

			switch ($type)
			{
				case 'mariadb':
					$type = 'mysql';

				case 'mysql':
					if ($this->config["socket"])
					{
						$dsn = $type . ':unix_socket=' . $this->config["socket"] . ';dbname=' . $this->config["dbname"];
					}
					else
					{
						$dsn = $type . ':host=' . $this->config["host"] . ($is_port ? ';port=' . $port : '') . ';dbname=' . $this->config["dbname"];
					}

					$commands[] = 'SET SQL_MODE=ANSI_QUOTES';
					$this->namespace = "Services\\Database\\MySQL\\";
					break;

				case 'pgsql':
					$dsn = $type . ':host=' . $this->config["host"] . ($is_port ? ';port=' . $port : '') . ';dbname=' . $this->config["dbname"];
					$this->namespace = "Services\\Database\\MySQL\\";
					break;

				case 'sybase':
					$dsn = 'dblib:host=' . $this->config["host"] . ($is_port ? ':' . $port : '') . ';dbname=' . $this->config["dbname"];
					break;

				case 'oracle':
					$dbname = $this->config["host"] ?
							'//' . $this->config["host"] . ($is_port ? ':' . $port : ':1521') . '/' . $this->config["dbname"] :
							$this->config["dbname"];

					$dsn = 'oci:dbname=' . $dbname . ($this->config["charset"] ? ';charset=' . $this->config["charset"] : '');
					break;

				case 'mssql':
					$dsn = strstr(PHP_OS, 'WIN') ?
							'sqlsrv:server=' . $this->config["host"] . ($is_port ? ',' . $port : '') . ';database=' . $this->config["dbname"] :
							'dblib:host=' . $this->config["host"] . ($is_port ? ':' . $port : '') . ';dbname=' . $this->config["dbname"];

					$commands[] = 'SET QUOTED_IDENTIFIER ON';
					$this->namespace = "Services\\Database\\MySQL\\";
					break;

				case 'sqlite':
					$dsn = $type . ':' . $this->config["database_file"];
					$this->namespace = "Services\\Database\\MySQL\\";
					break;
			}

			if (in_array($type, explode(' ', 'mariadb mysql pgsql sybase mssql')) && $this->config["charset"])
			{
				$commands[] = "SET NAMES '" . $this->config["charset"] . "'";
			}
			$this->pdo = new PDO(
					$dsn, $this->config["username"], $this->config["password"], array(PDO::ATTR_PERSISTENT => false)
			);
			foreach ($commands as $value)
			{
				$this->pdo->exec($value);
			}
		}
		catch (PDOException $e)
		{
			throw new Exception($e->getMessage());
		}
	}

	public function startTransaction()
	{
		if (!$this->transaction)
		{
			$this->query("START TRANSACTION");
			$this->transaction = true;
		}
	}

	public function commit()
	{
		if ($this->transaction)
		{
			$this->query("COMMIT");
			$this->transaction = false;
		}
	}

	public function rollback()
	{
		if ($this->transaction)
		{
			$this->query("ROLLBACK");
			$this->transaction = false;
		}
	}

	public function isTransaction()
	{
		return $this->transaction;
	}

	public function lastInsertId()
	{
		return $this->pdo->lastInsertId();
	}

	public function query($query)
	{
		$this->addToLog($query);
		return $this->pdo->query($query);
	}

	public function exec($query)
	{
		$this->addToLog($query);
		return $this->pdo->exec($query);
	}

	public function statment($query)
	{
		$this->addToLog($query);
		return $this->pdo->prepare($query);
	}

	/**
	 * @param array $query
	 * @return \Services\Database\Select
	 */
	public function select($query)
	{
		$class = $this->namespace . "Select";
		return new $class($query, ["adapter" => $this, "prefix" => $this->getPrefix()]);
	}

	/**
	 * 
	 * @param array $query
	 * @return \Services\Database\Insert
	 */
	public function insert($query)
	{
		$class = $this->namespace . "Insert";
		return new $class($query, ["adapter" => $this, "prefix" => $this->getPrefix()]);
	}

	/**
	 * 
	 * @param array $query
	 * @return \Services\Database\Update
	 */
	public function update($query)
	{
		$class = $this->namespace . "Update";
		return new $class($query, ["adapter" => $this, "prefix" => $this->getPrefix()]);
	}

	/**
	 * 
	 * @param array $query
	 * @return \Services\Database\Delete
	 */
	public function delete($query)
	{
		$class = $this->namespace . "Delete";
		return new $class($query, ["adapter" => $this, "prefix" => $this->getPrefix()]);
	}

	/**
	 * 
	 * @param array $query
	 * @return array
	 */
	public function get($query)
	{
		$query["limit"] = 1;
		$class = $this->namespace . "Select";
		return (new $class($query, ["adapter" => $this, "prefix" => $this->getPrefix()]))->fetch();
	}

	/**
	 * 
	 * @param array $query
	 * @return array
	 */
	public function getAll($query)
	{
		$class = $this->namespace . "Select";
		return (new $class($query, ["adapter" => $this, "prefix" => $this->getPrefix()]))->fetchAll();
	}

	public function has($query)
	{
		return $this->query('SELECT EXISTS(' . $this->select($query)->getRawQuery() . ')')->fetchColumn() === '1';
	}

	/**
	 * 
	 * @param array $query
	 * @return integer
	 */
	public function count($query)
	{
		$query["columns"] = ["COUNT" => ["*"]];
		return 0 + ($this->query($this->select($query))->fetchColumn());
	}

	/*
	  public function max($table, $join = [], $column = [], $where = [])
	  {
	  $max = $this->query($this->selectContext($table, $join, $column, $where, 'MAX'))->fetchColumn();

	  return is_numeric($max) ? $max + 0 : $max;
	  }

	  public function min($table, $join = [], $column = [], $where = [])
	  {
	  $min = $this->query($this->selectContext($table, $join, $column, $where, 'MIN'))->fetchColumn();

	  return is_numeric($min) ? $min + 0 : $min;
	  }

	  public function avg($table, $join = [], $column = [], $where = [])
	  {
	  return 0 + ($this->query($this->selectContext($table, $join, $column, $where, 'AVG'))->fetchColumn());
	  }

	  public function sum($table, $join = [], $column = [], $where = [])
	  {
	  return 0 + ($this->query($this->selectContext($this->config[""] . $table, $join, $column, $where, 'SUM'))->fetchColumn());
	  }
	 */

	public function error()
	{
		return $this->pdo->errorInfo();
	}

	protected function getSQLField($type)
	{
		$result = "`" . $type->getName() . "` " . $type->getType() . "";
		if (!empty($type->getLength()))
		{
			$result .= "(" . $type->getLength() . ")";
		}
		if (!empty($type->getAttribute()))
		{
			$result .= " {$type->getAttribute()}";
		}
		if ($type->getCollate() != "")
		{
			$charset = explode("_", $type->getCollate(), 2);
			$result .= " CHARACTER SET " . $charset[0] . " COLLATE " . $type->getCollate();
		}
		if ($type->isNull())
		{
			$result .= " NULL";
		}
		else
		{
			$result .= " NOT NULL";
		}
		if (!is_null($type->getDefault()) && ($type->getDefault() !== ""))
		{
			$result .= " DEFAULT '" . $type->getDefault() . "'";
		}
		if ($type->isAutoIncrement())
		{
			$result .= " auto_increment";
		}
		return $result;
	}

	protected function getSQLIndex($type)
	{
		$result = '';
		if (!is_a($type, 'Type'))
		{
			trigger_error('expected type: \'Type\'', E_USER_ERROR);
		}
		if ($type->getIndex() != "")
		{
			if ($type->getIndex() == "INDEX")
			{
				$result = "INDEX (`" . $type->getName() . "`)";
			}
			elseif ($type->getIndex() == "PRIMARY")
			{
				$result = "PRIMARY KEY(`" . $type->getName() . "`)";
			}
			elseif ($type->getIndex() == "UNIQUE")
			{
				$result = "UNIQUE(`" . $type->getName() . "`)";
			}
		}
		return $result;
	}

	public function getAutoIncrement($table)
	{
		return 0 + $this->query("SELECT `auto_increment` FROM INFORMATION_SCHEMA.TABLES WHERE table_name = '{$this->prefix}{$table}'")->fetchColumn();
	}

	public function createTable(Schema $table)
	{
		$sql = "CREATE TABLE IF NOT EXISTS `" . $this->prefix . $table->getName() . "` (";
		$first = true;
		foreach ($table->getColumns() as $column)
		{
			$first ? $first = false : $sql .= ',';
			$sql .= $this->getSQLField($column);
		}
		foreach ($table->getCombinedIndexes() as $key => $groups)
		{
			if (is_array($groups))
			{
				foreach ($groups as $cols)
				{
					if (is_array($cols))
					{
						switch ($key)
						{
							case 'PRIMARY':
								$sql .= ",PRIMARY KEY(" . "`" . implode("`,`", $cols) . "`" . ")";
								break;
							case 'UNIQUE':
								$sql .= ",UNIQUE(" . "`" . implode("`,`", $cols) . "`" . ")";
								break;
							case 'INDEX':
								$sql .= ",INDEX(" . "`" . implode("`,`", $cols) . "`" . ")";
								break;
						}
					}
				}
			}
		}
		$sql .= ') ENGINE=' . $table->getEngine() . ' DEFAULT CHARSET=' . $table->getCharset();

		$this->exec($sql);
		return $sql;
	}

	public function alterTable(Schema $table)
	{
		if (!$this->tableExists($this->prefix . $table->getName()))
		{
			return $this->createTable($table);
		}
		$engine = $this->getEngine($this->prefix . $table->getName())['ENGINE'];

		$changes = [];
		$meta = [];
		foreach ($this->getMetaData($this->prefix . $table->getName()) as $column)
		{
			$column['NON_UNIQUE'] = 1;
			$meta[$column['COLUMN_NAME']] = $column;
		}
		$query = $this->getIndexesInfo($this->prefix . $table->getName());
		$key_column_usage = [];
		foreach ($query as $value)
		{
			$key_column_usage[$value['Key_name']][] = $value['Column_name'];
			$meta[$value['Column_name']]['NON_UNIQUE'] = $value['Non_unique'];
			$meta[$value['Column_name']]['COLUMN_KEY'] = $value['Key_name'];
		}

		foreach ($table->getColumns() as $column)
		{

			if (!isset($meta[$column->getName()]))
			{
				//add field
				$changes[] = "ADD " . $this->getSQLField($column);
			}
			else
			{
				if ((strtolower($column->getType() . ($column->getLength() !== '' ? '(' . $column->getLength() . ')' . ($column->getAttribute() ? " {$column->getAttribute()}" : "") : '')) != strtolower($meta[$column->getName()]["COLUMN_TYPE"])) ||
						(strtolower($column->isNull() ? 'YES' : 'NO') != strtolower($meta[$column->getName()]["IS_NULLABLE"])) ||
						(strtolower($column->getCollate()) != strtolower($meta[$column->getName()]["COLLATION_NAME"])) ||
						(strtolower($column->getDefault()) != strtolower($meta[$column->getName()]["COLUMN_DEFAULT"])))
				{
					$changes[] = "MODIFY " . $this->getSQLField($column);
				}

				$meta[$column->getName()]['flag'] = true;
			}
		}

		foreach ($table->getCombinedIndexes() as $key => $groups)
		{
			foreach ($groups as $cols)
			{
				if (is_array($cols))
				{
					$constraint_name = '';
					foreach ($key_column_usage as $constraint => $cols_name)
					{
						if (count($cols) == count($cols_name))
						{
							if (count(array_diff($cols, $cols_name)) == 0)
							{
								$constraint_name = "`$constraint`";
								unset($key_column_usage[$constraint]);
								break;
							}
						}
					}
					switch ($key)
					{
						case 'INDEX':
							switch (isset($meta[$cols[0]]) ? $meta[$cols[0]]["COLUMN_KEY"] : '')
							{
								case 'PRI':
								case 'PRIMARY':
									$changes[] = "DROP PRIMARY KEY $constraint_name";

								case '':
									$changes[] = "ADD INDEX(" . "`" . implode("`,`", $cols) . "`" . ")";
									break;
								default:
									if ($meta[$cols[0]]["NON_UNIQUE"] == '0')
									{
										if (!empty($constraint_name))
											$changes[] = "DROP INDEX $constraint_name";

										$changes[] = "ADD INDEX $constraint_name (" . "`" . implode("`,`", $cols) . "`" . ")";
									}
									break;
							}
							break;
						case 'UNIQUE':
							switch (isset($meta[$cols[0]]) ? $meta[$cols[0]]["COLUMN_KEY"] : '')
							{
								case 'PRI':
								case 'PRIMARY':
									$changes[] = "DROP PRIMARY KEY $constraint_name";
								case '':
									$changes[] = "ADD UNIQUE(" . "`" . implode("`,`", $cols) . "`" . ")";
									break;
								case 'MUL':
									if (!empty($constraint_name))
									{
										$changes[] = "DROP INDEX $constraint_name";
									}

									$changes[] = "ADD UNIQUE $constraint_name(" . "`" . implode("`,`", $cols) . "`" . ")";
									break;
							}
							break;
						case 'PRIMARY':
							switch (isset($meta[$cols[0]]) ? $meta[$cols[0]]["COLUMN_KEY"] : '')
							{
								case 'PRI': break;
								case 'PRIMARY': break;
								case '':
									$changes[] = "ADD PRIMARY KEY(" . "`" . implode("`,`", $cols) . "`" . ")";
									break;
								default:
									if (!empty($constraint_name))
									{
										$changes[] = "DROP INDEX $constraint_name";
									}

									$changes[] = "ADD PRIMARY KEY(" . "`" . implode("`,`", $cols) . "`" . ")";
									break;
							}
							break;
					}
				}
			}
		}

		foreach ($key_column_usage as $constraint => $cols_name)
		{
			switch (isset($meta[$cols_name[0]]) ? $meta[$cols_name[0]]["COLUMN_KEY"] : '')
			{
				case 'PRIMARY': $changes[] = "DROP PRIMARY KEY `$constraint`";
					break;
				default:
					$changes[] = "DROP INDEX `$constraint`";
					break;
			}
		}

		//drop columns
		foreach ($meta as $field)
		{
			if (!isset($field['flag']))
			{
				$changes[] = "DROP COLUMN `" . $field["COLUMN_NAME"] . "`";
			}
		}

		//check engine
		if (strtolower($engine) != strtolower($table->getEngine()))
		{
			$changes[] = "ENGINE = " . $table->getEngine();
		}

		if (!empty($changes))
		{
			$sql = "ALTER TABLE " . $this->prefix . $table->getName() . " " . implode(", ", $changes);
			$this->exec($sql);
			return $sql;
		}
	}

	public function getMetaData($table)
	{
		$sql = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLLATION_NAME, COLUMN_KEY, COLUMN_TYPE 
					FROM INFORMATION_SCHEMA.COLUMNS 
					WHERE `table_name` = '{$table}'";
		return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * 
	 * @param string $table
	 * @return array
	 */
	public function getIndexesInfo($table)
	{
		return $this->query("SHOW INDEX FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getEngine($table)
	{
		return $this->query("SELECT `ENGINE` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE TABLE_NAME='{$table}'")->fetch(PDO::FETCH_ASSOC);
	}

	public function dropTables(array $tables)
	{
		if (count($tables) > 0)
		{
			return $this->exec("DROP TABLE IF EXISTS " . implode(", ", $tables));
		}
		return 0;
	}

	public function tableExists($table)
	{
		return $this->query("SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME` = '{$table}' AND `TABLE_SCHEMA` = '{$this->config['dbname']}' LIMIT 1")->rowCount() > 0;
	}

	public function tables()
	{
		return $this->query("SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA` = '{$this->config['dbname']}'")->fetchAll(PDO::FETCH_ASSOC);
	}

	protected function addToLog($query = "")
	{
		$this->log[] = $query;
	}

	public function getLog()
	{
		return $this->log;
	}

	public function getLastQuery()
	{
		if (count($this->log) != 0)
		{
			return $this->log[count($this->log) - 1];
		}
		else
		{
			return null;
		}
	}

	public function newSchema($name)
	{
		return new Schema($name);
	}

}

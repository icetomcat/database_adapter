<?php

namespace Database;

use Database\Base\AbstractQuery;
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
					if (isset($this->config["socket"]) && $this->config["socket"])
					{
						$dsn = $type . ':unix_socket=' . $this->config["socket"] . ';dbname=' . $this->config["dbname"];
					}
					else
					{
						$dsn = $type . ':host=' . $this->config["host"] . ($is_port ? ';port=' . $port : '') . ';dbname=' . $this->config["dbname"];
					}

					$commands[] = 'SET SQL_MODE=ANSI_QUOTES';
					$this->namespace = "Database\\MySQL\\";
					break;

				case 'pgsql':
					$dsn = $type . ':host=' . $this->config["host"] . ($is_port ? ';port=' . $port : '') . ';dbname=' . $this->config["dbname"];
					$this->namespace = "Database\\MySQL\\";
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
					$this->namespace = "Database\\MySQL\\";
					break;

				case 'sqlite':
					$dsn = $type . ':' . $this->config["database_file"];
					$this->namespace = "Database\\MySQL\\";
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
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
		try
		{
			return $this->pdo->exec($query);
		}
		catch (PDOException $exc)
		{
			throw new Exception($exc->getMessage() . " ( " . $query . " )");
		}
	}

	public function statment($query)
	{
		$this->addToLog($query);
		try
		{
			return $this->pdo->prepare($query);
		}
		catch (PDOException $exc)
		{
			throw new Exception($exc->getMessage() . " ( " . $query . " )", null, $exc);
		}
	}

	/**
	 * @param array $query
	 * @return AbstractQuery
	 */
	public function select($query)
	{
		$class = $this->namespace . "Select";
		return new $class($query, ["adapter" => $this, "prefix" => $this->getPrefix()]);
	}

	/**
	 * 
	 * @param array $query
	 * @return AbstractQuery
	 */
	public function insert($query)
	{
		$class = $this->namespace . "Insert";
		return new $class($query, ["adapter" => $this, "prefix" => $this->getPrefix()]);
	}

	/**
	 * 
	 * @param array $query
	 * @return AbstractQuery
	 */
	public function update($query)
	{
		$class = $this->namespace . "Update";
		return new $class($query, ["adapter" => $this, "prefix" => $this->getPrefix()]);
	}

	/**
	 * 
	 * @param array $query
	 * @return AbstractQuery
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
		$query["limit"] = 1;
		return $this->count($query) > 0;
	}

	/**
	 * 
	 * @param array $query
	 * @return integer
	 */
	public function count($query)
	{
		$query["columns"] = ["COUNT" => ["*"]];
		if (isset($query["order"]))
			unset($query["order"]);

		return 0 + $this->select($query)->fetchColumn();
	}

	public function error()
	{
		return $this->pdo->errorInfo();
	}

	protected function getSQLField(Type $type, $without_auto_increment = false)
	{
		$result = "`" . $type->getName() . "` " . $type->getType() . "";
		if ($type->getLength())
		{
			$result .= "(" . $type->getLength() . ")";
		}
		if ($type->getAttribute())
		{
			$result .= " {$type->getAttribute()}";
		}
		if ($type->getCollate())
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
		if ($type->getDefault() !== null)
		{
			$result .= " DEFAULT '{$type->getDefault()}'";
		}
		if ($type->isAutoIncrement() && !$without_auto_increment)
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
				foreach ($groups as $index_type => $group)
				{
					if (is_array($group))
					{
						switch ($index_type)
						{
							case 'PRIMARY':
								$sql .= ",PRIMARY KEY(" . "`" . implode("`,`", $group) . "`" . ")";
								break;
							case 'UNIQUE':
								$sql .= ",UNIQUE `$key`(" . "`" . implode("`,`", $group) . "`" . ")";
								break;
							case 'INDEX':
								$sql .= ",INDEX `$key`(" . "`" . implode("`,`", $group) . "`" . ")";
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

		foreach ($table->getColumns() as $column)
		{

			if (!isset($meta[$column->getName()]))
			{
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

		foreach ($this->getIndexesInfo($this->prefix . $table->getName()) as $index)
		{
			$indexes[$index["Key_name"]][$index["Column_name"]] = $index;
		}
		$table_indexes = $table->getCombinedIndexes();
		$non_unique = ["PRIMARY" => 0, "UNIQUE" => 0, "INDEX" => 1];

		foreach ($table_indexes as $key => $groups)
		{
			foreach ($groups as $index_type => $group)
			{
				$isGoodIndex = true;
				if (isset($indexes[$key]))
				{
					if (count($group) != count($indexes[$key]))
					{
						$isGoodIndex = false;
					}
					if ($non_unique[$index_type] != reset($indexes[$key])["Non_unique"])
					{
						$isGoodIndex = false;
					}
					if ($isGoodIndex)
					{
						foreach ($group as $column)
						{
							if (!isset($indexes[$key][$column]))
							{
								$isGoodIndex = false;
								break;
							}
						}
					}

					if (!$isGoodIndex)
					{
						if ($index_type == "PRIMARY")
						{
							$changes[] = "DROP PRIMARY KEY";
						}
						else
						{
							$changes[] = "DROP INDEX $key";
						}
					}

					unset($indexes[$key]);
				}
				else
				{
					$isGoodIndex = false;
				}

				if (!$isGoodIndex)
				{
					if ($index_type == "PRIMARY")
					{
						$changes[] = "ADD PRIMARY KEY(" . "`" . implode("`,`", $group) . "`" . ")";
					}
					else
					{
						if (count($group) > 1)
						{
							$changes[] = "ADD $index_type `$key`(" . "`" . implode("`,`", $group) . "`" . ")";
						}
						else
						{
							$changes[] = "ADD $index_type (" . "`" . implode("`,`", $group) . "`" . ")";
						}
					}
				}
			}
		}

		foreach ($indexes as $key => $group)
		{
			if ($key == "PRIMARY")
			{
				$changes[] = "DROP PRIMARY KEY";
			}
			else
			{
				$changes[] = "DROP INDEX $key";
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

	public function getLog($n = 0)
	{
		if ($n > 0)
		{
			return array_slice($this->log, -$n, $n);
		}
		else
		{
			return $this->log;
		}
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

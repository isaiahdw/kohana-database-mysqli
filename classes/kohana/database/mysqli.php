<?php defined('SYSPATH') or die('No direct script access.');
/**
 * MySQL database connection.
 *
 * @package    Kohana/Database
 * @category   Drivers
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Kohana_Database_MySQLi extends Database_MySQL {

	public function connect()
	{
		if ($this->_connection)
			return;

		// Extract the connection parameters, adding required variabels
		extract($this->_config['connection'] + array(
			'database'   => NULL,
			'host'       => NULL,
			'username'   => NULL,
			'password'   => NULL,
			'port'       => NULL,
			'socket'     => NULL,
			'params'     => NULL,
			'persistent' => FALSE,
		));

		// Prevent this information from showing up in traces
		unset($this->_config['connection']['username'], $this->_config['connection']['password']);

		if ($persistent)
		{
			if (version_compare(PHP_VERSION, '5.3', '<'))
				throw new Database_Exception('PHP 5.3+ is required for persistent connections with mysqli.');

			$host = 'p:'.$host;
		}

		$_connection = mysqli_init();

		try
		{
			$_connection->real_connect($host, $username, $password, $database, $port, $socket, $params);
		}
		catch (Exception $e)
		{
			throw new Database_Exception('[:errno] :error', array(
					':error' => $_connection->error,
					':errno' => $_connection->errno,
				));
		}

		$this->_connection = $_connection;

		if ( ! empty($this->_config['charset']))
		{
			$this->set_charset($this->_config['charset']);
		}
	}

	public function disconnect()
	{
		if (is_object($this->_connection))
		{
			if ($this->_connection->close())
			{
				// Clear the connection
				$this->_connection = NULL;
				return TRUE;
			}
		}

		return FALSE;
	}

	public function set_charset($charset)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		// Set the character set
		if ( ! $this->_connection->set_charset($charset))
			throw new Database_Exception('[:errno] :error', array(
					':error' => $this->_connection->error,
					':errno'  => $this->_connection->errno,
				));
	}

	public function query($type, $sql, $as_object = FALSE, array $params = NULL)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		if ( ! empty($this->_config['profiling']))
		{
			// Benchmark this query for the current instance
			$benchmark = Profiler::start("Database ({$this->_instance})", $sql);
		}

		// Execute the query
		if (($result = $this->_connection->query($sql)) === FALSE)
		{
			if (isset($benchmark))
			{
				// This benchmark is worthless
				Profiler::delete($benchmark);
			}

			throw new Database_Exception('[:errno] :error ( :query )', array(
				':error' => $this->_connection->error,
				':errno' => $this->_connection->errno,
				':query' => $sql));
		}

		if (isset($benchmark))
		{
			Profiler::stop($benchmark);
		}

		// Set the last query
		$this->last_query = $sql;

		if ($type === Database::SELECT)
		{
			// Return an iterator of results
			return new Database_MySQLi_Result($result, $sql, $as_object, $params);
		}
		elseif ($type === Database::INSERT)
		{
			// Return a list of insert id and rows created
			return array(
				$this->_connection->insert_id,
				$this->_connection->affected_rows,
			);
		}
		else
		{
			// Return the number of rows affected
			return $this->_connection->affected_rows;
		}
	}

	/**
	 * Start a SQL transaction
	 *
	 * @link http://dev.mysql.com/doc/refman/5.0/en/set-transaction.html
	 *
	 * @param string Isolation level
	 * @return boolean
	 */
	public function begin($mode = NULL)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return $this->_connection->autocommit(FALSE);
	}

	/**
	 * Commit a SQL transaction
	 *
	 * @param string Isolation level
	 * @return boolean
	 */
	public function commit()
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return $this->_connection->commit();
	}

	/**
	 * Rollback a SQL transaction
	 *
	 * @param string Isolation level
	 * @return boolean
	 */
	public function rollback()
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return $this->_connection->rollback();
	}

	public function escape($value)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		if (($value = $this->_connection->real_escape_string((string) $value)) === FALSE)
		{
			throw new Database_Exception('[:errno] :error', array(
				':error' => $this->_connection->error,
				':errno' => $this->_connection->errno,
				));
		}

		// SQL standard is to use single-quotes for all values
		return "'$value'";
	}

} // End Database_MySQLi

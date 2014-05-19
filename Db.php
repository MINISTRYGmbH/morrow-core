<?php
/*////////////////////////////////////////////////////////////////////////////////
    MorrowTwo - a PHP-Framework for efficient Web-Development
    Copyright (C) 2009  Christoph Erdmann, R.David Cummins

	This file is part of MorrowTwo <http://code.google.com/p/morrowtwo/>

	MorrowTwo is free software:  you can redistribute it and/or modify
	it under the terms of the GNU Lesser General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Lesser General Public License for more details.

	You should have received a copy of the GNU Lesser General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
////////////////////////////////////////////////////////////////////////////////*/


namespace Morrow;

/**
* For access to databases we extended the PHP own PDO.
* 
* You have access to all methods as described in the documentation for PDO. Furthermore we added or rewrote the following methods.
* 
* Examples
* ---------
*
* ~~~{.php}
* // ... Controller code
*  
* // This line is not necessary if you use the default path to the config parameters (auto initialized by Morrow)
* // You will need this if you need a second database or you want to use an other path to the config
* $this->prepare('Db', $this->config->get('db'));
*  
* // Query with a prepared statement using named placeholder
* $sql = $this->db->get('
*     SELECT *
*     FROM table
*     WHERE id = :id
* ', array('id'=>$this->input->get('id'))
* );
* Debug::dump($sql);
*  
* // Query with a prepared statement using "?" placeholder
* // If you just want to pass one parameter you can also pass it directly without using an array
* $sql = $this->db->get('
*     SELECT *
*     FROM table
*     WHERE id = ?
* ', $this->input->get('id'));
*  
* Debug::dump($sql);
*  
* // ... Controller code
* ~~~
*
* If you prefix a field name in your query with an `>`, then this column value will be used as key for the result set instead of a numerically indexed array. This way you will get an array which can be easily accessed via an unique key. 
* You must only use fields with the `>` operator whose values are unique. Otherwise all rows with the same field value will become one row. 
*
* ~~~{.php}
* // ... Controller code
*  
* $this->prepare('db', $this->config->get('db'));
*  
* // Query with a prepared statement using named placeholder
* $sql = $this->db->Result('
*    SELECT *, >id
*    FROM table
* ');
* Debug::dump($sql);
*  
* // ... Controller code
* ~~~
*/
class Db extends \PDO {
	/**
	 * The "connected" status
	 * @var boolean $connected
	 */
	protected $connected = false;

	/**
	 * The database configuration
	 * @var array $config
	 */
	protected $config;

	/**
	 * Column cache for *safe() functions
	 * @var array $cache
	 */
	protected $cache = array();
	
	/**
	 * This method overwrites the standard method and expects the DB connection parameters.
	 *
	 * The keys for the parameters are `driver`, `host`, `db`, `user`, `pass` and `encoding`. 
	 *
	 * @param array $config database connection parameters
	 * @return null
	 */
	public function __construct($config) {
		$this -> config = $config;
	}

	/**
	 * Connects to the Database.
	 * 
	 * Because we added Lazy initialization to PDO you only have to call this method if you use PDOs own functions and you did not call any of our methods before. 
	 * 
	 * @return null
	 */
	public function connect() {
		if (!$this->connected) {
			if ($this->config['host']{0} == '/') {
				$connector = $this->config['driver'].':unix_socket='.$this->config['host'].';dbname='.$this->config['db'];
			} else {
				$connector = $this->config['driver'].':host='.$this->config['host'].';dbname='.$this->config['db'];
			}
			
			// sqlite
			if ($this->config['driver'] == 'sqlite') {
				$connector = $this->config['driver'].':'.$this->config['file'].'';
			}
			
			parent::__construct($connector, $this->config['user'], $this->config['pass']);

			$this -> setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL); // leave column names as returned by the database driver
			$this -> setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); // on errors we want to get \Exceptions

			// set encoding
			if (isset($this->config['encoding']) && $this->config['driver'] != 'sqlite') {
				parent::exec('SET NAMES '.$this->config['encoding']);
			}

			$this->connected = true;
		}
	}
		
	/**
	 * This method sends a query to the database and returns the result. 
	 * 
	 * @param  string $query  The SQL query to send.
	 * @param  array $token An array with the prepared statement parameters (indexed or associative array, depends on the use of the prepared statement syntax)
	 * @return array Returns an array with the keys `SUCCESS` (true if the query could successfully sent to the db, otherwise false), `RESULT` (array The complete result set of the request) and `NUM_ROWS` (integer 	The count of returned results). 
	 */
	public function get($query, $token = null) {
		if (is_scalar($token)) $token = array($token);
				
		// search for access keys
		$accesskey = null;
		$found = preg_match('=SELECT.+>([a-z_]+).+FROM=is', $query, $match);
		if ($found === 1) {
			$accesskey = $match[1];
			$query = str_replace('>'.$accesskey, $accesskey, $query);
		}
		
		// do query
		$this->connect();
		$sth = $this->prepare($query);
		$returner['SUCCESS'] = $sth->execute($token);
		$returner['RESULT'] = $sth->fetchAll(\PDO::FETCH_ASSOC);
		$returner['NUM_ROWS'] = count($returner['RESULT']);
		
		// if an access key was provided rearrange array
		if (!is_null($accesskey)) {
			$newreturner = array();
			foreach ($returner['RESULT'] as $row) {
				$newreturner[$row[$accesskey]] = $row;
			}
			$returner['RESULT'] = $newreturner;
		}

		return $returner;
	}

	/**
	 * The same as result(), but an additional key `FOUND_ROWS` contains the count of rows if you had left out the LIMIT in your query. 
	 *
	 * *For MySQL only*
	 *
	 * @param  string $query  The SQL query to send.
	 * @param  array $token An array with the prepared statement parameters (indexed or associative array, depends on the use of the prepared statement syntax)
	 * @return array Returns an array with the keys `SUCCESS` (true if the query could successfully sent to the db, otherwise false), `RESULT` (array The complete result set of the request), `NUM_ROWS` (integer The count of returned results) and `FOUND_ROWS` (integer The count of results without the LIMIT). 
	 */
	public function get_calc_found_rows($query, $token = null) {
		if (is_scalar($token)) $token = array($token);

		// because of two queries we should use transactions if available
		$this->beginTransaction();
		
		// rewrite query
		$query = preg_replace('=SELECT=is', 'SELECT SQL_CALC_FOUND_ROWS', $query, 1);
		$returner = $this->result($query, $token);

		// get found rows
		$sql = $this->query('SELECT FOUND_ROWS() AS FOUND_ROWS')->fetch(\PDO::FETCH_ASSOC);
		$returner['FOUND_ROWS'] = $sql['FOUND_ROWS'];
	
		$this->commit();
		
		return $returner;
	}
	
	/**
	 * Inserts a new row. Every key in the array `$data` represents a field in the table.
	 *
	 * It is also possible to pass an array with the key `FUNC` as value. In that case it will not been sent as string but as expression. 
	 *
	 * ~~~{.php}
	 * // Controller code
	 * 
	 * $data = array(
	 *     'foo' => 'bar',
	 *     'foo2' => array('FUNC' => 'foo2+1')
	 * );
	 * $this->db->insert('table', $data, true);
	 * 
	 * // Controller code
	 * ~~~
	 * 
	 * @param	string	$table  The table name the query refers to.
	 * @param  array $array The data to insert into the table
	 * @param  array $on_duplicate_key_update The data which is used for an UPDATE if a PRIMARY or UNIQUE key already exists (*MySQL only*).
	 * @return array An result array with the key `SUCCESS` (boolean Was the query successful).
	 *
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function insert($table, $array, $on_duplicate_key_update = array()) {
		// The array could be empty if processed by insertSafe
		if (count($array) == 0) return array('SUCCESS' => true);

		$array = $this->_createInsertAndReplaceValues($array);
		extract($array);
		
		$query = "INSERT INTO $table ($keys) VALUES ($values)";

		if (count($on_duplicate_key_update) > 0) {
			$array = $this->_createUpdateValues($on_duplicate_key_update);
			$query .= ' ON DUPLICATE KEY UPDATE '.$array['tokens'];
			$binds = array_merge($binds, $array['values']);
		}

		$this->connect();
		$sth = $this->prepare($query);

		$returner['SUCCESS'] = $sth->execute($binds);
		return $returner;
	}

	/**
	 * The same as insert(), but keys, that do not have a corresponding fields in the database table, will be deleted.
	 *
	 * This method is slower than insert(), because all field names of the target table has to be figured out with a second query. 
	 * 
	 * @param	string	$table  The table name the query refers to.
	 * @param  array $array The data to insert into the table
	 * @param  array $on_duplicate_key_update The data which is used for an UPDATE if a PRIMARY or UNIQUE key already exists (*MySQL only*).
	 * @return array An result array with the key `SUCCESS` (boolean Was the query successful).
	 */
	public function insertSafe($table, $array, $on_duplicate_key_update = array()) {
		$array						= $this->_safe($table, $array);
		$on_duplicate_key_update	= $this->_safe($table, $on_duplicate_key_update);
		return $this -> Insert($table, $array, $on_duplicate_key_update);
	}
	
	/**
	 * Updates a row. Every key in the array `$array` represents a field in the table.
	 *
	 * It is also possible to pass an array with the key `FUNC` as value. In that case it will not been sent as string but as expression.
	 *
	 * ~~~{.php}
	 * // ... Controller-Code
	 *
	 * $data = array(
	 *     'foo' => 'bar',
	 *     'foo2' => array('FUNC' => 'foo2+1')
	 * );
	 * $this->db->update($table, $data, 'where id = ?', true, 1);
	 *
	 * // ... Controller-Code
	 * ~~~
	 * 
	 * @param	string	$table  The table name the query refers to.
	 * @param	array	$array	The data to insert into the table
	 * @param	string	$where   The where condition in the update query
	 * @param	mixed	$where_tokens	An array (or a scalar) for use as a Prepared Statement in the where clause. Only question marks are allowed for the token in the where clause. You cannot use the colon syntax.
	 * @param	boolean	$affected_rows Set to true to return the count of the affected rows
	 * @return	array	An result array with the keys `SUCCESS` (boolean Was the query successful) and `AFFECTED_ROWS` (int The count of the affected rows) 
	 *
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function update($table, $array, $where = '', $where_tokens = array(), $affected_rows = false) {
		// The array could be empty if processed by updateSafe
		if (count($array) == 0) return array('SUCCESS' => true, 'AFFECTED_ROWS' => 0);

		$array = $this->_createUpdateValues($array);
		extract($array);
		
		// add tokens of where clause
		if (is_scalar($where_tokens)) $where_tokens = array($where_tokens);
		foreach ($where_tokens as $value) {
			$values[] = $value;
		}
		
		$query = "UPDATE $table SET $tokens $where";

		$this->connect();
		$sth = $this->prepare($query);
		
		$returner['SUCCESS'] = $sth->execute($values);
		if ($affected_rows) $returner['AFFECTED_ROWS'] = $sth->rowCount();
		return $returner;
	}

	/**
	 * The same as update(), but keys, that do not have a corresponding fields in the database table, will be deleted.
	 *
	 * This method is slower than update(), because all field names of the target table has to be figured out with a second query. 
	 * 
 	 * @param	string	$table  The table name the query refers to.
	 * @param	array	$array	The data to insert into the table
	 * @param	string	$where   The where condition in the update query
	 * @param	mixed	$where_tokens	An array (or a scalar) for use as a Prepared Statement in the where clause. Only question marks are allowed for the token in the where clause. You cannot use the colon syntax.
	 * @param	boolean	$affected_rows Set to true to return the count of the affected rows
	 * @return	array	An result array with the keys `SUCCESS` (boolean Was the query successful) and `AFFECTED_ROWS` (int The count of the affected rows) 
	 */
	public function updateSafe($table, $array, $where = '', $where_tokens = array(), $affected_rows = false) {
		$array = $this->_safe($table, $array);
		return $this -> Update($table, $array, $where, $where_tokens, $affected_rows);
	}
	
	/**
	 * This method inserts a new row or updates an entry if the key already exists. Every key in the array `$data` represents a field in the table.
	 *
	 * It is also possible to pass an array with the key `FUNC` as value. In that case it will not been sent as string but as expression. 
	 *
	 * ~~~{.php}
	 * // ... Controller-Code
	 * 
	 * $data = array(
	 *         'foo' => 'bar',
	 *         'foo2' => array('FUNC' => 'foo2+1')
	 * );
	 * $this->db->replace('table', $data, true);
	 *
	 * // ... Controller-Code
	 * ~~~
	 * 
	 * @param	string	$table  The table name the query refers to.
	 * @param	array	$array	The data to replace
	 * @return	array	An result array with the keys `SUCCESS` (boolean Was the query successful)
	 *
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function replace($table, $array) {
		// The array could be empty if processed by replaceSafe
		if (count($array) == 0) return array('SUCCESS' => true);

		$array = $this->_createInsertAndReplaceValues($array);
		extract($array);
		
		$query = "REPLACE INTO $table ($keys) VALUES ($values)";

		$this->connect();
		$sth = $this->prepare($query);

		$returner['SUCCESS'] = $sth->execute($binds);
		return $returner;
	}

	/**
	 * The same as replace(), but keys, that do not have a corresponding fields in the database table, will be deleted.
	 *
	 * This method is slower than replace(), because all field names of the target table has to be figured out with a second query. 
	 * 
	 * @param	string	$table  The table name the query refers to.
	 * @param	array	$array	The data to replace
	 * @return	array	An result array with the keys `SUCCESS` (boolean Was the query successful)
	 */
	public function replaceSafe($table, $array) {
		$array = $this->_safe($table, $array);
		return $this -> Replace($table, $array);
	}
		
	/**
	 * Deletes a database row.
	 *
	 * @param	string	$table	The table name the query refers to.
	 * @param	string	$where	The where condition in the update query
	 * @param	mixed	$where_tokens	An array (or a scalar) for use as a Prepared Statement in the where clause. Only question marks are allowed for the token in the where clause. You cannot use the colon syntax.
	 * @param	boolean	$affected_rows Set to true to return the count of the affected rows
	 * @return	array	An result array with the keys `SUCCESS` (boolean Was the query successful) and `AFFECTED_ROWS` (int The count of the affected rows) 
	 */
	public function delete($table, $where, $where_tokens = array(), $affected_rows = false) {
		// add tokens of where clause
		$values = array();
		if (is_scalar($where_tokens)) $where_tokens = array($where_tokens);
		foreach ($where_tokens as $value) {
			$values[] = $value;
		}
		
		$query = "DELETE FROM $table $where";

		$this->connect();
		$sth = $this->prepare($query);
		
		$returner['SUCCESS'] = $sth->execute($values);
		if ($affected_rows) $returner['AFFECTED_ROWS'] = $sth->rowCount();
		return $returner;
	}
	
	/**
	 * The same as exec() from PDO with the difference that it connects to the database automatically.
	 * 
	 * @param	string	$query	The query to execute
	 * @return	int	The count of the affected rows
	 */
	public function exec($query) {
		$this->connect();
		$returner['AFFECTED_ROWS'] = parent::exec($query);
		return $returner;
	}

	/**
	 * The same as query() from PDO with the difference that it connects to the database automatically.
	 * 
	 * @param	string	$query	The query to execute
	 * @return	object	The result as a PDOStatement object
	 *
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function query($query) {
		$this->connect();
		$params = func_get_args();
		return call_user_func_array(array($this, 'parent::query'), $params);
	}

	/**
	 * The same as beginTransaction() from PDO with the difference that it connects to the database automatically.
	 * 
	 * @return	boolean	Returns TRUE on success and FALSE on failure
	 */
	public function	beginTransaction() {
		$this->connect();
		$status = parent::beginTransaction();
		return $status;
	}
		
	/**
	 * Creates an update string of an associative array for use in a SQL query
	 * 
	 * @param	array $array An associative array: column_name > value
	 * @return	string
	 */
	protected function _createUpdateValues($array) {
		$values = array();
		$tokens = array();
		
		// divide normal values from function calls
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$tokens[] = '`'.$key.'`='.$value['FUNC'];
			} else {
				$tokens[] = '`'.$key.'`=?';
				$values[] = $value;
			}
		}
		
		$tokens = implode(', ', $tokens);	

		$returner = compact('tokens', 'values');
		return $returner;
	}

	/**
	 * Creates an insert and replace string of an associative array for use in a SQL query
	 * 
	 * @param	array $array An associative array: column_name > value
	 * @return	string
	 */
	protected function _createInsertAndReplaceValues($array) {
		$keys = array();
		$values = array();
		$binds = array();
		
		foreach ($array as $value) {
			if (is_array($value)) {
				$values[] = $value['FUNC'];
			} else {
				$values[] = '?';
				$binds[] = $value;
			}
		}

		$keys = implode(',', array_keys($array));
		$values = implode(',', $values);
		
		$returner = compact('keys', 'values', 'binds');
		return $returner;
	}

	/**
	 * Removes all keys from an array which are not available in the given table
	 * 
	 * @param	string $table The table name to compare the array with
	 * @param	array $array An associative array: column_name > value
	 * @return	array
	 */
	protected function _safe($table, $array) {
		if (!isset($this->cache[$table])) {
			$this->connect();
			
			$query = 'SHOW COLUMNS FROM '.$table;
			if ($this->config['driver'] == 'sqlite') {
				$query = 'PRAGMA table_info('.$table.');';
			}
			$sth = $this->prepare($query);
			$sth->execute();
			$result = $sth->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach ($result as $row) {
				if ($this->config['driver'] == 'sqlite') {
					$columns[] = $row['name'];
				} else {
					$columns[] = $row['Field'];
				}
			}
			$this->cache[$table] = $columns;
		}
		
		// remove all not existent keys
		foreach (array_keys($array) as $key) {
			if (!in_array($key, $this->cache[$table])) unset ($array[$key]);
		}
		
		return $array;
	}
}

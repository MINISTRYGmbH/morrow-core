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

use Morrow\Debug;
use Morrow\Factory;

/**
* Extend this class to have Models following the Table Data Gateway pattern.
*
* A Table Data Gateway holds all the SQL for accessing a single table or view: selects, inserts, updates, and deletes. Other code calls its methods for all interaction with the database.
* If you extend this class you write Models with less code because you don't have to take care of standard actions (see method listing below).
*
* A database table accessed by this class must have a primary field `id`. If a table has a field `created_at` or `updated_at` of the type `DATETIME` it is automatically filled by the corresponding method.
*
* Example
* ---------
*
* First you have to define your model, e.g. **app/models/Products.php**
*
* ~~~{.php}
* <?php
*
* namespace app\models;
* use Morrow\Factory;
*
* class Products extends \Morrow\AbstractTableDataGateway {
* 	protected $_db;
* 	protected $_table					= 'products';
* 	protected $_allowed_insert_fields	= ['title', 'description'];
* 	protected $_allowed_update_fields	= ['description'];
*
* 	public function __construct() {
* 		$this->_db = Factory::load('Db');
* 	}
*
* 	public function exampleAction() {
* 		$sql = $this->_db->get("SELECT *, >id FROM {$this->_table} ORDER BY id ASC");
*  		return $sql['RESULT'];
* 	}
* }
* ~~~
*
* This is a very simple class which is able to handle a table `products` as defined in `$_table`.
* The members `$_db` and `$_table` are both required. For this reason the `__construct()` method is also required because it fills the `$_db`  member.
*
* The other members `$_allowed_insert_fields` and `$_allowed_update_fields` are optional and define which fields are allowed to be inserted or updated.
* This prevents that e.g. an `id` is provided with the `update()` method what could destroy the integrity of your database.
*
* Most of the time you will have multiple model files so it will probably make sense to create a base model class.
* This is a simple example for such a class:
*
* ~~~{.php}
* <?php
*
* namespace app\models;
* use Morrow\Factory;
*
* class Base extends \Morrow\AbstractTableDataGateway {
* 	public function __construct() {
* 		$this->_db		= Factory::load('Db');
* 		$this->_table	= preg_replace('/^.+\\\\/', '', strtolower(get_called_class()));
* 	}
* }
* ~~~
*
* If the names of your database tables are the lower case written class names the first class would look like this:
*
* ~~~{.php}
* <?php
*
* namespace app\models;
*
* class Products extends Base {
* 	protected $_allowed_insert_fields	= ['title', 'description'];
* 	protected $_allowed_update_fields	= ['description'];
*
* 	public function exampleAction() {
* 		$sql = $this->_db->get("SELECT *, >id FROM {$this->_table} ORDER BY id ASC");
*  		return $sql['RESULT'];
* 	}
* }
* ~~~
*
* Now that you have your Table Data Gateway you can work with it in your controlller:
*
*
* ~~~{.php}
* // ... Controller code
*
* $products = new models\Products;
*
* // insert some data
* $data = [
* 	'title'			=> 'Cool product',
* 	'description'	=> 'A very long description ...',
* ];
* $products->insert($data);
* $id = $this->lastInsertId();
*
* // update the row just inserted
* $data = [
* 	'description'	=> 'A very long and changed description ...',
* ];
* $products->update($data, $id);
*
* // we do not need it anymore
* $products->delete($id);
*
* // we defined a method ourself
* $data = $products->exampleAction();
*
* // ... Controller code
* ~~~
*
* Every time you have `$conditions` (take a look at at the methods below) you can pass either an integer (which would be used for `WHERE id = $condition`) or an associative array (where all conditions would be concatenated with `AND`) which defines the `WHERE` clause in the resulting query.
*
*/
abstract class AbstractTableDataGateway {
	/**
	 * Must contain an instance of a \Morrow\Db class.
	 * @var object $_db
	 */
	protected $_db;

	/**
	 * The name of the table to work with.
	 * @var string $_table
	 */
	protected $_table;

	/**
	 * All fields that allowed to be inserted.
	 * @var array $_allowed_insert_fields
	 */
	protected $_allowed_insert_fields = [];

	/**
	 * All fields that allowed to be updated.
	 * @var array $_allowed_update_fields
	 */
	protected $_allowed_update_fields = [];

	/**
	 * Retrieves data from the database.
	 *
	 * @param  mixed $conditions  An integer (as `id`) or an associative array with conditions that must be fulfilled by the rows to be returned.
	 * @return array Returns an array of dataset arrays with the requested data (returns all data if `$conditions` was left empty). The keys of the datasets are the ids of the rows.
	 */
	public function get($conditions = [], $order_by = '') {
		if (is_scalar($conditions)) $conditions = ['id' => $conditions];
		$where = $this->_createWhere($conditions);
		$order_by = $this->_createOrderBy($order_by);

		$conditions	= !empty($where) ? array_values($conditions) : [];

		$sql = $this->_db->get("
			SELECT *, >id
			FROM {$this->_table}
			{$where}
			{$order_by}
		", $conditions);

		return $sql['RESULT'];
	}

	/**
	 * Inserts a new row into the database with `$data` filtered by the member `$_allowed_insert_fields`.
	 *
	 * @param  array $data The data to insert.
	 * @return array An result array with the key `SUCCESS` (is `true` if the query was successful).
	 */
	public function insert(array $data) {
		$data = $this->filterFields($data, $this->_allowed_insert_fields);
		$data['created_at'] = Factory::load('\datetime')->format('Y-m-d H:i:s');

		return $this->_db->insertSafe($this->_table, $data);
	}

	/**
	* Returns the id of the last inserted row.
	*
	* @return string The id of the last inserted row.
	*/
	public function lastInsertId() {
		return $this->_db->lastInsertId();
	}

	/**
	 * Updates a row in the database with `$data` filtered by the member `$_allowed_update_fields`.
	 *
	 * @param  array $data The fields to update.
	 * @param  mixed $conditions  An integer (as `id`) or an associative array with conditions that must be fulfilled by the rows to be processed.
	 * @return array An result array with the keys `SUCCESS` (boolean Was the query successful) and `AFFECTED_ROWS` (int The count of the affected rows).
	 */
	public function update($data, $conditions) {
		$data = $this->filterFields($data, $this->_allowed_update_fields);
		$data['updated_at'] = Factory::load('\datetime')->format('Y-m-d H:i:s');

		if (is_scalar($conditions)) $conditions = ['id' => $conditions];
		$where = $this->_createWhere($conditions);

		return $this->_db->updateSafe($this->_table, $data, "{$where}", array_values($conditions), true);
	}

	/**
	 * Deletes a table row in the database.
	 *
	 * @param  mixed $conditions  An integer (as `id`) or an associative array with conditions that must be fulfilled by the rows to be processed.
	 * @return array An result array with the keys `SUCCESS` (boolean Was the query successful) and `AFFECTED_ROWS` (int The count of the affected rows).
	 */
	public function delete($conditions) {
		if (is_scalar($conditions)) $conditions = ['id' => $conditions];
		$where = $this->_createWhere($conditions);

		return $this->_db->delete($this->_table, "{$where}", array_values($conditions));
	}

	/**
	 * Creates a where string of conditions to use in a SQL query.
	 *
	 * @param  mixed $conditions  An associative array with conditions.
	 * @return array Returns the generated `WHERE ` clause.
	 */
	protected function _createWhere(array $conditions) {
		$where = [];
		foreach ($conditions as $field => $value) {
			$where[] = $field . '=?';
		}
		$where = implode(' AND ', $where);
		if (!empty($where)) $where = 'WHERE ' . $where;
		return $where;
	}

	/**
	 * Creates an ORDER BY string to use in a SQL query.
	 *
	 * @param  string $order_by The ORDER BY string "[COLUMN NAME] [ASC|DESC]"
	 * @return string           Generated ORDER BY string
	 */
	protected function _createOrderBy($order_by){
		return strlen($order_by) ? 'ORDER BY ' . $order_by : '';
	}


	/**
	 * This method filters an associative array by the key names passed with `$allowed_fields`.
	 *
	 * @param  string $data  An associative array with the data to filter.
	 * @param  array $allowed_fields An array of allowed fields names to be in the resulting array.
	 * @return array Returns an array only with key names that exist in `$allowed_fields`.
	 */
	public function filterFields(array $data, array $allowed_fields) {
		return array_intersect_key($data, array_flip($allowed_fields));
	}
}

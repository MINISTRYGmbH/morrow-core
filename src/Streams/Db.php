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


namespace Morrow\Streams;

/**
* Defines a scheme to save files to a database instead of the file system.
*
* This is useful on cluster server systems if you want to save sessions or other user related files into the database without many changes in your codebase,
*
* First you have to create the database table where the files will be stored (Example for MySQL):
* ~~~{.sql}
* CREATE TABLE IF NOT EXISTS `files` (
*   `id` char(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
*   `type` enum('file','dir') CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
*   `data` longblob NOT NULL,
*   `ctime` datetime NOT NULL,
*   `mtime` datetime NOT NULL,
*   PRIMARY KEY (`id`)
* ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Used for Db stream wrapper';
* ~~~
*
* Example
* -------
* 
* ~~~{.php}
* // ... Controller code
* 
* // Initialize the stream handler
* $this->prepare('Db', $this->config->get('db'));
* Factory::load('Streams\Db:streamdb_files', 'db', $this->db, 'files');
* 
* // Write a file
* file_put_contents('db://foo.txt', 'bar');
* 
* // Now delete it
* unlink('db://test.jpg');
* 
* // ... Controller code
* ~~~
* 
* If you want to write sessions to the database instead of the file system just register the stream wrapper in your controller and set the session path in your config to `'session.save_path' => 'dbs://',`.
* ~~~{.php}
* // ... Controller code
*  
* // You have to init the stream wrapper before working with the session
* $this->prepare('Db', $this->config->get('db'));
* Factory::load('Streams\Db:streamdb_sessions', 'dbs', $this->db, 'sessions');
* 
* // ... now work with the session
* 
* // ... Controller code
* ~~~
*
* @SuppressWarnings(PHPMD.UnusedFormalParameter)
*/
class Db {
	/**
	 * The path to the log file.
	 * @var string $config
	 * @hidden
	 */
	public static $config = array();
	
	/**
	 * The path to the log file.
	 * @var string $config
	 */
	protected $scheme;

	/**
	 * The path to the log file.
	 * @var string $config
	 */
	protected $db;

	/**
	 * The path to the log file.
	 * @var string $config
	 */
	protected $table;
	
	// dir parameters
	/**
	 * The path to the log file.
	 * @var string $config
	 */
	protected $dir;

	/**
	 * The path to the log file.
	 * @var string $config
	 */
	protected $entries;

	/**
	 * The path to the log file.
	 * @var string $config
	 */
	protected $entries_pos = 0;

	// file parameters
	/**
	 * The path to the log file.
	 * @var string $config
	 */
	protected $id;

	/**
	 * The path to the log file.
	 * @var string $config
	 */

	protected $entry;
	/**
	 * The path to the log file.
	 * @var string $config
	 */

	protected $pos = 0;

	/**
	 * The path to the log file.
	 * @var string $config
	 */
	protected $mode;

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param string $scheme The scheme that should be registered.
	 * @param string $db The path the scheme should map file access to.
	 * @param string $table The path the scheme should map file access to.
	 */
	public function __construct($scheme = null, \Morrow\Db $db = null, $table = null) {
		if(!$scheme) return;
		
		self::$config[$scheme] = array(
			'db'	=> $db,
			'table'	=> $table,
		);
		stream_register_wrapper($scheme, __CLASS__);
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function dir_closedir() {
		// Any resources which were locked, or allocated, during opening and use of the directory stream should be released.
		return true;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param string $path As defined in PHPs \streamWrapper.
	 * @param integer $options As defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function dir_opendir($path, $options) {
		$parts = explode('://', $path, 2);

		$this->scheme	= $parts[0];
		$this->dir		= rtrim($parts[1], '/') . '/';
		$this->db		= self::$config[$this->scheme]['db'];
		$this->table	= self::$config[$this->scheme]['table'];

		if ($this->dir === '/') $this->dir = '';

		// handle the different modes
		$sql = $this->db->result("
			SELECT
				id,
				type,
				data,
				UNIX_TIMESTAMP(ctime) AS ctime,
				UNIX_TIMESTAMP(mtime) AS mtime
			FROM ". $this->table ."
			WHERE id RLIKE ?
		", $this->dir . '[^/]+$');
		
		$this->entries = $sql['RESULT'];
		return true;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function dir_readdir() {
		if (isset($this->entries[$this->entries_pos])) {
			return $this->entries[$this->entries_pos++]['id'];
		}
		return false;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function dir_rewinddir() {
		$this->entries_pos = 0;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param string $path As defined in PHPs \streamWrapper.
	 * @param integer $mode As defined in PHPs \streamWrapper.
	 * @param integer $options As defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function mkdir($path, $mode, $options) {
		$parts = explode('://', $path, 2);

		$this->scheme	= $parts[0];
		$this->id		= rtrim($parts[1], '/') . '/';
		$this->db		= self::$config[$this->scheme]['db'];
		$this->table	= self::$config[$this->scheme]['table'];

		$this->entry = array(
			'id'	=> $this->id,
			'type'	=> 'dir',
			'data'	=> '',
			'ctime'	=> date('Y-m-d H:i:s', time()),
			'mtime'	=> date('Y-m-d H:i:s', time()),
		);

		//$this->db->insert($this->table, $this->entry);
		return false;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param string $path_from As defined in PHPs \streamWrapper.
	 * @param string $path_to As defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function rename($path_from, $path_to) {
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param string $path As defined in PHPs \streamWrapper.
	 * @param integer $options As defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function rmdir($path, $options) {
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_cast() {
		// Should return the underlying stream resource used by the wrapper, or FALSE.
		return false;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_close() {
		// All resources that were locked, or allocated, by the wrapper should be released.
		return;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_eof() {
		return ($this->pos === strlen($this->entry['data']) - 1);
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_flush() {
		// Should return TRUE if the cached data was successfully stored (or if there was no data to store), or FALSE if the data could not be stored.
		// because er have stored the data in stream_write() there is no possibility to return false.
		return true;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param integer $operation As defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_lock($operation) {
		return false;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param string $path As defined in PHPs \streamWrapper.
	 * @param integer $option As defined in PHPs \streamWrapper.
	 * @param mixed $value As defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_metadata($path, $option, $value) {
		return false;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param string $path As defined in PHPs \streamWrapper.
	 * @param string $mode As defined in PHPs \streamWrapper.
	 * @param integer $options As defined in PHPs \streamWrapper.
	 * @param string $opath As defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_open($path, $mode, $options, &$opath) {
		$parts = explode('://', $path, 2);

		$this->scheme	= $parts[0];
		$this->id		= $parts[1];
		$this->db		= self::$config[$this->scheme]['db'];
		$this->table	= self::$config[$this->scheme]['table'];
		$this->mode		= $mode;
		$this->exists	= false;
		
		$this->entry = array(
			'id'	=> $this->id,
			'type'	=> 'file',
			'data'	=> '',
			'ctime'	=> time(),
			'mtime'	=> time(),
		);

		// handle the different modes
		$sql = $this->db->result("
			SELECT
				id,
				type,
				data,
				UNIX_TIMESTAMP(ctime) AS ctime,
				UNIX_TIMESTAMP(mtime) AS mtime
			FROM ". $this->table ."
			WHERE id = ?
			LIMIT 1
		", $this->id);

		// if file already exists: false
		if (in_array($this->mode, array('x', 'x+')) && $sql['NUM_ROWS'] === 0) return false;

		if (isset($sql['RESULT'][0])) {
			$this->entry = array_merge($this->entry, $sql['RESULT'][0]);
			$this->exists	= true;
		}

		// truncate file
		if (in_array($this->mode, array('w', 'x+'))) {
			$this->entry['data'] = '';
		}

		// set cursor position if different to 0
		if (in_array($this->mode, array('a', 'a+'))) {
			$this->pos = strlen($this->entry['data']) - 1;
		}

		return true;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param integer $count As defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_read($count) {
		$returner = substr($this->entry['data'], $this->pos, $count);
		// update position
		$this->pos = min($this->pos + $count, strlen($this->entry['data']));
		return $returner;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param integer $offset As defined in PHPs \streamWrapper.
	 * @param integer $whence As defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_seek($offset, $whence = SEEK_SET) {
		if ($whence == SEEK_SET) $this->pos = $offset;
		elseif ($whence == SEEK_CUR) $this->pos += $offset;
		elseif ($whence == SEEK_END) $this->pos = strlen($this->entry['data']) - $offset;
		else {
			return false;
		}
		return true;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_set_option() {
		return false;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_stat() {
		// do not return anything if file not exists
		if (!$this->exists) return false;

		return array(
			'dev'		=> 0,
			'ino'		=> 0,
			'mode'		=> ($this->entry['type'] === 'dir' ? 17407 : 33216),
			'nlink'		=> 0,
			'uid'		=> 0,
			'gid'		=> 0,
			'rdev'		=> 0,
			'size'		=> strlen($this->entry['data']),
			'atime'		=> 0,
			'mtime'		=> $this->entry['mtime'],
			'ctime'		=> $this->entry['ctime'],
			'blksize'	=> 0,
			'blocks'	=> 0,
		);
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_tell() {
		return $this->pos;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param integer $new_size As defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_truncate($new_size) {
		$this->entry['data'] = '';
		$this->pos = 0;
		return true;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param string $data As defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function stream_write($data) {
		// try to add entry if it does not exist
		if (in_array($this->mode, array('w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'))) {
			
		}

		$this->entry['data']	= $data;
		$this->entry['ctime']	= date('Y-m-d H:i:s', $this->entry['ctime']);
		$this->entry['mtime']	= date('Y-m-d H:i:s', time());
		$this->db->replace($this->table, $this->entry);

		return strlen($data);
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param string $path As defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function unlink($path) {
		$this->stream_open($path, 'r', array(), $opath);
		$sql = $this->db->delete($this->table, 'WHERE id=?', true, $this->id);
		if ($sql['SUCCESS'] && $sql['AFFECTED_ROWS'] !== 0) return true;
		return false;
	}

	/**
	 * Implements function as defined in PHPs \streamWrapper.
	 * @param string $filename As defined in PHPs \streamWrapper.
	 * @hidden
	 */
	public function url_stat($filename) {
		$this->stream_open($filename, 'r', array(), $opath);
		return $this->stream_stat();

	}
}

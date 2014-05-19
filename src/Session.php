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
* A class for accessing the user session with methods for getting, setting and deleting content and is always initialized by Morrow.
*
* Dot Syntax
* ----------
* 
* This class works with the extended dot syntax. So if you use keys like `foo.bar` and `foo.bar2` as identifiers in your config, you can call `$this->session->get("foo")` to receive an array with the keys `bar` and `bar2`. 
* 
* Examples
* ---------
*
* ~~~{.php}
* // ... Controller code
*  
* // counting per user page visits for the whole site
* 
* // retrieve the value of visits and set a fallback value if visits does not exist. 
* $visits = $this->session->get('visits', 0);
*  
* $this->session->set('visits', ++$visits);
* 
* // ... Controller code
* ~~~
*/
class Session extends Core\Base {
	/**
	 * The array with parsed data that holds the session data.
	 * Has to be static because it represents the superglobal `$_SESSION`.
	 * @var array $_data
	 */
	protected static $_data = array();

	/**
	 * Defines the key we want to use to store the data in `$_SESSION`.
	 * @var string $section
	 */
	protected $section = "main";

	/**
	 * Defines the save path where the sessions are stored
	 * @var string $save_path
	 */
	protected $save_path = "";

	/**
	 * Initializes the class. Usually you don't have to initialize this class yourself.
	 * 
	 * @param array $config	Config parameters as an associative array that are passed to session_set_cookie_params(). Use the keys `lifetime`, `path`, `domain`, `secure` and `httponly` that are described in the documentation to session_set_cookie_params().
	 */
	public function __construct($config) {
		if (ini_get('session.auto_start') == true) {
			session_destroy();
		}
			
		// set cookie params
		session_set_cookie_params(
			$config['cookie_lifetime'],
			$config['cookie_path'],
			$config['cookie_domain'],
			$config['cookie_secure'],
			$config['cookie_httponly']
		);
		
		// we start an own session handler which supports stream wrappers
		session_set_save_handler(
			array($this, "sessionhandler_open"),
			array($this, "sessionhandler_close"),
			array($this, "sessionhandler_read"),
			array($this, "sessionhandler_write"),
			array($this, "sessionhandler_destroy"),
			array($this, "sessionhandler_gc")
		);


		
		// set save path
		if (!is_dir($config['save_path'])) mkdir($config['save_path']);
		$this->save_path = $config['save_path'];

		ini_set('session.gc_probability', $config['gc_probability']);
		ini_set('session.gc_divisor', $config['gc_divisor']);

		// set max lifetime 
		ini_set('session.gc_maxlifetime', $config['gc_maxlifetime']);


		// start session
		session_start();
		
		// it should not be possible to inject a session
		$this->_preventSessionFixation();
		
		// get session data
		self::$_data = $_SESSION;
	}

	/**
	 * Writes the internal data back to `$_SESSION` to let PHP save the data.
	 */
	public function __destruct() {
		$_SESSION = self::$_data;
		session_write_close();
	}

	/**
	 * Retrieves session data saved with a given identifier.
	 * 
	 * @param string $identifier	The identifier you have used on setting the data.
	 * @param mixed $fallback The return value if the identifier was not found.
	 * @return mixed 	The requested data.
	 */
	public function get($identifier = null, $fallback = null) {
		return $this->arrayGet(self::$_data, $this->section . ($identifier !== null ? '.' . $identifier : ''), $fallback);
	}

	/**
	 * Sets session data with a given identifier.
	 * 
	 * @param string $identifier	The identifier you want to store the data with.
	 * @param string $value	The data you want to store.
	 * @return null
	 */
	public function set($identifier, $value) {
		$this->arraySet(self::$_data, $this->section . "." . $identifier, $value);
	}

	/**
	 * Deletes session data.
	 * 
	 * @param string $identifier	The identifier you want to delete.
	 * @return null
	 */
	public function delete($identifier = null) {
		if ($this->get($identifier) === null) return;
		$this->arrayDelete(self::$_data, $this->section . ($identifier !== null ? '.' . $identifier : ''));
	}
	
	/**
	 * Prevents session fixation. It is automatically called if there isn't a session id known for this user and uses the USER AGENT as identifier.
	 * So the session id is regenerated if the USER AGENT changes.
	 * For more information take a look at: http://phpsec.org/projects/guide/4.html
	 * 
	 * @return null
	 */
	protected function _preventSessionFixation() {
		if (!isset($_SERVER['HTTP_USER_AGENT'])) return;
		if (!isset($_SESSION['SERVER_GENERATED_SID']) or $_SESSION['SERVER_GENERATED_SID'] != md5($_SERVER['HTTP_USER_AGENT'])) {
			session_regenerate_id();
			session_unset();
			$_SESSION['SERVER_GENERATED_SID'] = md5($_SERVER['HTTP_USER_AGENT']);
		}
	}

	/**
	 * Initialize session.
	 * @param string $save_path	The path where to store/retrieve the session.
	 * @param string $session_id	The session id.
	 * @return boolean The return value (usually `true` on success, `false` on failure). Note this value is returned internally to PHP for processing.
	 * @hidden
	 */
	public function sessionhandler_open($save_path, $session_id) {
		return true;
	}

	/**
	 * Close the session.
	 * @return boolean The return value (usually `true` on success, `false` on failure). Note this value is returned internally to PHP for processing.
	 * @hidden
	 */
	public function sessionhandler_close() {
		return true;
	}

	/**
	 * Read session data.
	 * @param string $session_id	The session id to read data for.
	 * @return string Returns an encoded string of the read data. If nothing was read, it must return an empty string. Note this value is returned internally to PHP for processing.
	 * @hidden
	 */
	public function sessionhandler_read($session_id) {
		$path = $this->save_path . $session_id;
		if (is_file($path)) return file_get_contents($path);
		return '';
	}

	/**
	 * Write session data.
	 * @param string $session_id	The session id.
	 * @param string $session_data	The encoded session data. This data is the result of the PHP internally encoding the $_SESSION superglobal to a serialized string and passing it as this parameter.
	 * @return boolean The return value (usually `true` on success, `false` on failure). Note this value is returned internally to PHP for processing.
	 * @hidden
	 */
	public function sessionhandler_write($session_id, $session_data) {
		file_put_contents($this->save_path . $session_id, $session_data);
	}

	/**
	 * Destroy a session.
	 * @param string $session_id	The session ID being destroyed.
	 * @return boolean The return value (usually `true` on success, `false` on failure). Note this value is returned internally to PHP for processing.
	 * @hidden
	 */
	public function sessionhandler_destroy($session_id) {
		unlink($this->save_path . $session_id);
	}

	/**
	 * Cleanup old sessions.
	 * @param string $maxlifetime	Sessions that have not updated for the last maxlifetime seconds will be removed.
	 * @return boolean The return value (usually `true` on success, `false` on failure). Note this value is returned internally to PHP for processing.
	 * @hidden
	 */
	public function sessionhandler_gc($maxlifetime) {
		$files = scandir($this->save_path);
		foreach ($files as $file) {
			if ($file{0} === '.') continue;

			if (filemtime($this->save_path . $file) < time() - $maxlifetime) {
				unlink($this->save_path . $file);
			}
		}
		return true;
	}
}

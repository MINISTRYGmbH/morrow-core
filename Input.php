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
 * Handles the access to input that came by $_GET, $_POST and $_FILES. 
 * It cleans the input vars and reformats the $_FILES array for a uniform access to it.
 *
 * Dot Syntax
 * -----------
 * 
 * This class works with the extended dot syntax. So if you have keys like `input_example.host` and `input_example.smtp` in your input, you can call `$this->input->get('input_example')` to receive an array with the keys host and smtp.
 * 
 * Example
 * -------
 * 
 * ~~~{.php}
 * // ... Controller code
 *  
 * // retrieve all input that came from outside (like PHPs $_REQUEST)
 * Debug::dump($this->input->get());
 * 
 * // retrieve an ID passed via POST, and set a fallback value if ID was not passed.
 * Debug::dump($this->input->getPost('id', 0));
 *  
 * // ... Controller code
 * ~~~
 */
class Input extends Core\Base {
	/**
	 * Holds the data for GET input.
	 * @var array $_get
	 */
	protected $_get;

	/**
	 * Holds the data for POST input.
	 * @var array $_post
	 */
	protected $_post;

	/**
	 * Holds the data for file uploads.
	 * @var array $_files
	 */
	protected $_files;

	/**
	 * Holds the data for GET, POST and file uploads. Comparable to $_REQUEST.
	 * @var array $_data
	 */
	protected $_data;
	
	/**
	 * Imports, unifies and cleans user input from PHP Superglobals.
	 */
	public function __construct() {
		$this->_get   = $this->tidy($_GET);
		$this->_post  = $this->tidy($_POST);
		$this->_files = $this->_getFileData($this->tidy($_FILES));
		$this->_data  = $this->_array_merge_recursive_distinct($this->_get, $this->_post, $this->_files);
	}

	/**
	 * Trims a string, unifies line breaks and deletes null bytes.
	 * @param	mixed	$value	An array or scalar to clean up.
	 * @return	mixed	The cleaned version of the input.
	 */
	public function tidy($value) {
		if (is_array($value)) {
			$value = array_map(array(&$this, 'tidy'), $value);
		} else {
			$value = trim($value);
			// unify line breaks
			$value = preg_replace("=(\r\n|\r)=", "\n", $value);
			// filter nullbyte
			$value = str_replace("\0", '', $value);
		}
		return $value;
	}

	/**
	 * Access to all user input (comparable to $_REQUEST).
	 * @param string $identifier Config data to be retrieved.
	 * @param mixed $fallback The return value if the identifier was not found.
	 * @return mixed
	 */
	public function get($identifier = null, $fallback = null) {
		return $this->arrayGet($this->_data, $identifier, $fallback);
	}

	/**
	 * Access to user input that came per POST (comparable to $_POST).
	 * @param string $identifier Config data to be retrieved.
	 * @param mixed $fallback The return value if the identifier was not found.
	 * @return mixed
	 */
	public function getPost($identifier = null, $fallback = null) {
		return $this->arrayGet($this->_post, $identifier, $fallback);
	}

	/**
	 * Access to user input that came per GET (comparable to $_GET).
	 * @param string $identifier Config data to be retrieved.
	 * @param mixed $fallback The return value if the identifier was not found.
	 * @return mixed
	 */
	public function getGet($identifier = null, $fallback = null) {
		return $this->arrayGet($this->_get, $identifier, $fallback);
	}

	/**
	 * Access to user input that came per file upload (comparable to $_FILES).
	 * @param string $identifier Config data to be retrieved.
	 * @param mixed $fallback The return value if the identifier was not found.
	 * @return mixed
	 */
	public function getFiles($identifier = null, $fallback = null) {
		return $this->arrayGet($this->_files, $identifier, $fallback);
	}

	/**
	 * Sets an input value. Used for parameters coming from URL routing rules.
	 * @param string $identifier The identifier to save the data with.
	 * @param mixed $value The data to be set.
	 * @return mixed
	 */
	public function set($identifier, $value) {
		$this->arraySet($this->_data, $identifier, $value);
	}

	/**
	 * Deletes an input value.
	 * @param string $identifier The identifier to delete.
	 * @return null
	 */
	public function delete($identifier) {
		$this->arrayDelete($this->_data, $identifier);
	}

	/**
	 * Merges any number of arrays.
	 *
	 * array_merge_recursive() does indeed merge arrays, but it converts values with duplicate keys to arrays rather than overwriting the value in the first array with the duplicate value in the second array, as array_merge does.
	 * @param	array	$array	Any number of arrays.
	 * @return	array
	 */
	protected function _array_merge_recursive_distinct ($array) {
		$arrays = func_get_args();
		$base = array_shift($arrays);
		if (!is_array($base)) $base = empty($base) ? array() : array($base);
		foreach ($arrays as $append) {
			if (!is_array($append)) $append = array($append);
			foreach ($append as $key => $value) {
				if (!array_key_exists($key, $base)) {
					$base[$key] = $append[$key];
					continue;
				}
				if (is_array($value) or is_array($base[$key])) {
					$base[$key] = $this->_array_merge_recursive_distinct($base[$key], $append[$key]);
				} else {
					$base[$key] = $value;
				}
			}
		}
		return $base;
	}

	/**
	 * Rearranges the format of $_FILE data.
	 *
	 * If arrays of formdata are used, php rearranges the array format of $_FILE.
	 * This method puts them back in a more useful format.
	 * 
	 * @param	array	$_files	The data of $_FILES.
	 * @return	array
	 */
	protected function _getFileData($_files) {
		$return_files = array();
		if (is_array($_files)) {
			foreach ($_files as $fkey => $fvalue) {
				if (is_array($fvalue)) {
					foreach ($fvalue as $varname => $varpair) {
						if (is_array($varpair)) {
							foreach ($varpair as $fieldname => $varvalue) {
								$return_files[$fkey][$fieldname][$varname]=$varvalue;
							}
						} else {
							$return_files[$fkey] = $fvalue;
						}
					}
				}
			}
		}
		return $return_files;
	}
}

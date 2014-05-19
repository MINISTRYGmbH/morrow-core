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
* Helps to store variables of any type (except ressources) in a file cache for performance reasons.
* This is very useful for operations or calculations which take much time to execute.
*
* Imagine a very complex and time consuming database query which results only have to determined once an hour. Or an RSS Reader, whose external rss sources should only be retrieved once a day.
* Or a parser which should not parse a file twice when the modified time of the file did not change.
* For such cases it could be a big performance boost to use the cache class.  
*
* Example
* ---------
*
* ~~~{.php}
* // ... Controller code
* 
* if (!$result = $this->cache->load('result_id')) {
*     // ... time consuming calculation
*     $result = 'result of a very time consuming calculation';
*     $this->cache->save('result_id', $result, '+5 seconds');
* }
*
* // your data
* Debug::dump($result);
*
* // ... Controller code
* ~~~
*
* If your cache item has expired but you cannot generate a new cache, it is possible to use the expired cache. 
*
* ~~~{.php}
* // ... Controller code
* 
* if (!$result = $this->cache->load('result_id')) {
*     try {
*         // ... time consuming calculation
*         $result = 'result of a very time consuming calculation';
*         $this->cache->save('result_id', $result, '+5 seconds');
*     } catch (Exception $e) {
*         // use the old result if something went wrong
*         $temp = $this->cache->get($id);
*         $result = $temp['object'];
*     }
* }
* 
* // your data
* Debug::dump($result);
* 
* // ... Controller code
* ~~~
*/
class Cache {
	/**
	* Path to the cache directory.
	* @var string $cachedir
	*/
	protected $cachedir;

	/**
	* If set to true, the cache is dropped if the client sends the HTTP header HTTP_CACHE_CONTROL. E.g. if the user reloads the page while the same page is already open.
	* @var boolean $user_droppable
	*/
	protected $user_droppable;

	/**
	 * Initializes the cache class.
	 * 
	 * @param string $cachedir path to the cache directory.
	 * @param boolean $user_droppable If set to true, the cache is dropped if the client sends the HTTP header HTTP_CACHE_CONTROL. E.g. if the user reloads the page while the same page is already open.
	 * @return null
	 */
	public function __construct($cachedir, $user_droppable = false) {
		// clean cachedir
		$cachedir = rtrim($cachedir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		// create temp dir if it does not exist
		if (!is_dir($cachedir)) {
			mkdir($cachedir);
		}

		if (!is_writeable($cachedir)) {
			throw new \Exception(__CLASS__.': Directory "'.$cachedir.'" is not writeable.');
		}

		// set new paths
		$this->cachedir = $cachedir;
		
		// set user droppable
		$this->user_droppable = $user_droppable;
	}

	/**
	 * Returns the cache item data also if the item is already stale. Returns FALSE if there is no cached data yet. 
	 * 
	 * @param string $cache_id The cache descriptor id.
	 * @param mixed $comparator If the comparator changes the cache gets instantly stale.
	 * @return mixed Returns the cached data or FALSE if there is no cached data yet.
	 */
	public function get($cache_id, $comparator = null) {
		// clean id
		$cache_id = $this->_cleanId($cache_id);

		// create cache file paths
		$cachefile		= $this->cachedir.$cache_id;
		$cachefile_res	= $cachefile.'%res';

		// does cachefile exist?
		if (!is_file($cachefile)) return false;
		else $item = unserialize(file_get_contents($cachefile));

		$item['cachefile']		= $cachefile;
		$item['cachefile_res']	= $cachefile_res;
		
		// get cached data
		$item = $this->_getObject($item);
		
		// check valid state
		$item['valid'] = $this->_isValid($item, $comparator);
		
		return $item;		
	}
	
	/**
	 * Gets a variable with a given $cache_id from cache, dependent on validity. With the $comparator you can pass an additional comparison variable of any type, which is also used in save(). Do the comparators differ the cache is not valid.
	 * 
	 * @param string $cache_id The cache descriptor id.
	 * @param mixed $comparator If the comparator changes the cache gets instantly stale.
	 * @return mixed Returns the cached variable on success or FALSE on failure or invalid cache. 
	 */
	public function load($cache_id, $comparator = null) {
		// get item
		$item = $this->get($cache_id, $comparator);
		if(!$item) return false;
		
		// check validity
		if (!$item['valid']) return false;
		
		return $item['object'];
	}

	/**
	 * Puts a variable with a given $cache_id into cache. $lifetime determines the maximum lifetime, given as a string strtotime() recognizes. With the $comparator you can pass an additional comparison variable of any type, which is also used in save(). Do the comparators differ the cache is not valid. You could for example pass the modification time of a file to renew the cache on change of that file. 
	 * 
	 * **Take care of references:** Circular references inside the variable you are caching will be stored. Any other reference will be lost. 
	 * 
	 * **Take care of objects:** The class uses serialize() to store variables. When serializing objects, PHP will attempt to call the member function sleep() prior to serialization. This is to allow the object to do any last minute clean-up, etc. prior to being serialized. Likewise, when the object is restored using unserialize() the wakeup() member function is called. 
	 * 
	 * @param string $cache_id The cache descriptor id.
	 * @param mixed $object The data to cache.
	 * @param mixed $cachetime A lifetime in a format strtotime() supports.
	 * @param mixed $comparator If the comparator changes the cache gets instantly stale.
	 * @param mixed $user_droppable If set to true, the cache is dropped if the client sends the HTTP header HTTP_CACHE_CONTROL. E.g. if the user reloads the page while the same page is already open.
	 * @return boolean Returns TRUE on success and FALSE on failure. 
	 */
	public function save($cache_id, $object, $cachetime, $comparator = null, $user_droppable = null) {
		// if user_droppable is given it overwrites default
		$user_droppable = !is_null($user_droppable) ? $user_droppable : $this->user_droppable ;

		// clean id
		$cache_id = $this->_cleanId($cache_id);

		// define filename
		$cachefile			= $this->cachedir.$cache_id;
		$cachefile_tmp		= $cachefile.getmypid();

		// create cache file
		$cache_item						= array();
		$cache_item['id']				= $cache_id;
		$cache_item['created']			= time();
		$cache_item['expires']			= strtotime(date('r').' '.$cachetime); // date(r) fixes a bug with older php5 versions
		$cache_item['cachetime']		= $cachetime;
		$cache_item['comparator']		= $this->_createComparator($comparator);
		$cache_item['user_droppable']	= $user_droppable;

		// add standard objects like arrays, objects and so on
		if (is_resource($object)) {
			throw new \Exception(__CLASS__.': Is is not possible to cache a resource.');
		} else {
			$cache_item['object']		= $object;

			// create hash
			$cache_item['object_hash']	= md5(serialize($object));
		}

		// write cache file
		$io_result = file_put_contents($cachefile_tmp, serialize($cache_item));

		// could cachefile be written?
		if ($io_result === false) return false;

		// create real cache file
		$io_result = rename($cachefile_tmp, $cachefile);

		// could it be renamed? otherwise delete temporary file
		if ($io_result === false) {
			unlink($cachefile_tmp);
			return false;
		}

		return $cache_item;
	}

	/**
	 * Deletes all cache ids with a given pattern $cache_pattern. The pattern works with from shell known wildcards "*" and "?". The pattern "result_*" for example deletes alle cache ids which start with "result_". 
	 * 
	 * @param string $cache_pattern A cache id with shell wildcard patterns.
	 * @return int Returns the number of deleted cache ids. 
	 */
	public function delete($cache_pattern) {
		$files = scandir($this->dir);

		// files with leading dot must not be deleted
		foreach ($files as $key => $file) {
			if ($file[0] == '.') unset($files[$key]);
		}

		// build regex pattern
		$pattern = basename($cache_pattern);
		$pattern = preg_replace("=[^\w\*\?]=i", '', $pattern);
		$pattern = strtolower($pattern);
		$pattern = preg_replace('=([\*|\?])=', '.$1', $pattern);
		$pattern = "=^".$pattern."$=";

		// delete the matching files
		$result = 0;
		foreach ($files as $key => $file) {
			if (preg_match($pattern, $file)) {
				$result++;
				unlink($this->dir.$file);
			}
		}
		return $result;
	}

	/**
	 * Adds the cached data to a cache item array.
	 * 
	 * @param array $cache_item An array with all the meta data of a cache item
	 * @return mixed Returns the changed cache item or false if the cache data does not exist.
	 */
	protected function _getObject($cache_item) {
		// load handle if necessary
		if (!isset($cache_item['object'])) {
			if (!is_file($cache_item['cachefile_res'])) return false;
			$cache_item['object'] = fopen($cache_item['cachefile_res'], 'r');
		}
		
		return $cache_item;
	}
	
	/**
	 * Returns TRUE if cache item is valid and FALSE if not. 
	 * 
	 * @param array $cache_item An array with all the meta data of a cache item
	 * @param mixed $comparator If the comparator changes the cache gets instantly stale.
	 * @return boolean Returns TRUE if cache item is valid and FALSE if not. 
	 */
	protected function _isValid($cache_item, $comparator = null) {
		// were no-cache headers sent?
		if ($cache_item['user_droppable'] && isset($_SERVER['HTTP_CACHE_CONTROL']) && preg_match('/max-age=0|no-cache/i', $_SERVER['HTTP_CACHE_CONTROL'])) return false;

		// is cache file expired?
		if (time() > $cache_item['expires']) return false;
		
		// are the comparators different?
		$comparator = $this->_createComparator($comparator);
		if ($comparator != $cache_item['comparator']) return false;
		
		return true;
	}
	
	/**
	 * Creates a filesafe cache id.
	 * 
	 * @param string $cache_id A string intended to be a cache id.
	 * @return string Returns a filesafe cache id.
	 */
	protected function _cleanId($cache_id) {
		return preg_replace("=[^\w\.]=i", '_', strtolower($cache_id));
	}

	/**
	 * Creates a MD5 hashed comparator string from any variable.
	 * 
	 * @param mixed $input A variable of any type to be used as comparator.
	 * @return string Returns a comparator string.
	 */
	protected function _createComparator($input) {
		if (is_array($input) OR is_object($input)) {
			// So that _sleep is not called of origin object
			if (is_object($input)) $input = clone $input;
			$output = md5(serialize($input));
		} else {
			$output = $input;
		}
		return $output;
	}
}

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
 * This class is a wrapper for the cURL extension and allows to send HTTP requests easily. 
 *
 * Example
 * --------
 *
 * ~~~{.php}
 * // ... Controller code
 *
 * // configure HTTP class
 * $this->load('Http', array(
 *     CURLOPT_USERAGENT => 'Mozilla/2.0 (compatible; MSIE 3.0; AK; Windows 95)',
 *     CURLOPT_REFERER => 'http://www.google.com',
 *     CURLOPT_HTTPHEADER => array('X-Foo-1: Bar 1', 'X-Foo-2: Bar 2'),
 * ));
 *
 * // HEAD request
 * $response = $this->http->head('http://localhost/?test_fdfd=123');
 *
 * // GET request
 * $response = $this->http->get('http://localhost/?test_fdfd=123');
 *
 * // POST request with sending post parameters and a file
 * $response = $this->http->post(
 *     'http://localhost/?test_get=get',
 *     array('test_post' => 'post'),
 * 	   array('test_file' => 'test.png')
 * );
 *
 * // ... Controller code
 * ~~~
 */
class Http {
	/**
	 * cURL options
	 * @var array $_options
	 */
	protected $_options;
	
	/**
	 * Sets default cUrl options 
	 * @param	array	$options	An associative array with cUrl options.
	 */
	public function __construct($options = array()) {
		$this->_options = $options;
	}
	
	/**
	 * Sets a cURL option
	 * @param	const	$key	cUrl option key
	 * @param	string	$value	The value to set
	 * @return null
	 */
	public function setopt($key, $value) {
		$this->_options[$key] = $value;
	}
		
	/**
	 * Sends a GET request and receives the response. 
	 * @param	string	$url	The URL you want to send your request to.
	 * @return	array	Returns an array with the keys `headers` and `body`.
	 */
	public function get($url) {
		return $this->_request('get', $url);
	}

	/**
	 * Sends a HEAD request and receives the response. 
	 * @param	string	$url	The URL you want to send your request to.
	 * @return	array	Returns an array with the keys `headers` and `body`. `body` will be false because of the nature of a HEAD request.
	 */
	public function head($url) {
		return $this->_request('head', $url);
	}

	/**
	 * Sends a POST request and receives the response. 
	 * @param	string	$url	The URL you want to send your request to.
	 * @param	array	$post	An associative array with post parameters to send.
	 * @param	array	$files	An associative array with files to send.
	 * @return	array	Returns an array with the keys `headers` and `body`.
	 */
	public function post($url, $post = array(), $files = array()) {
		return $this->_request('post', $url, $post, $files);
	}
	
	/**
	 * get(), head() and post() are mapped to this function which really does the request.
	 * @param	string	$method	Can be `get`, `post` or `head`.
	 * @param	string	$url	The URL you want to send your request to.
	 * @param	array	$post	An associative array with post parameters to send.
	 * @param	array	$files	An associative array with files to send.
	 * @return	array	Returns an array with the keys `headers` and `body`.
	 */
	protected function _request($method, $url, $post = array(), $files = array()) {
		$ch = curl_init();
		$this->_init($ch);
	
		// GET
		curl_setopt($ch, CURLOPT_URL, $url);
		
		// POST
		if ($method == 'post') {
			// add files to post fields
			foreach ($files as $name => $path) {
				if (!is_readable($path)) throw new \Exception("File '$path' does not exist.");
				$post[$name] = '@' . $path;
			}
			
			// if you did not append a file you post data can disappear
			// http://de2.php.net/manual/de/function.curl-setopt.php#94405
			// I also had that error with verification for BrowserID
			if (count($files) == 0) {
				$post = http_build_query($post, '', '&');
			}
			
			// POST request
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		
		// HEAD
		if ($method == 'head') {
			curl_setopt($ch, CURLOPT_NOBODY, true);
		}
		
		if (!$data = curl_exec($ch)) {
			throw new \Exception(curl_error($ch) . " ($url)");
		}
		
		$data = $this->_splitResponse($ch, $data);
		curl_close($ch);
		
		return $data;
	}
	
	/**
	 * Divides the header from the body of a server response.
	 * @param	object	$ch	cURL session handler.
	 * @param	string	$data	A server response string.
	 * @return	array	Returns an array with the keys `headers` and `body`.
	 */
	protected function _splitResponse($ch, $data) {
		$curl_info = curl_getinfo($ch);
		$header_size = $curl_info["header_size"];
		
		$returner = array(
			'header' => explode("\r\n\r\n", trim(substr($data, 0, $header_size))),
			'body' => substr($data, $header_size),
		);

		return $returner;
	}

	/**
	 * Initializes to cURL connection for all methods.
	 * @param	object	$ch	cURL session handler.
	 * @return	object	cURL session handler.
	 */
	protected function _init($ch) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		try {
			// CURLOPT_FOLLOWLOCATION cannot be activated when in safe_mode or an open_basedir is set
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		} catch (\Exception $e) {
		}
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip");
		
		// Trust invalid certificate
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		// add options injected via constructor
		curl_setopt_array($ch, $this->_options);
		
		return $ch;
	}
}

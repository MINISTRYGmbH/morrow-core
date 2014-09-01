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

use Morrow\Factory;
use Morrow\Debug;

class Headers {
	/**
	 * Contains all HTTP headers that should be set.
	 * @var	array $_header
	 */
	protected $_header = array();

	/**
	 * The time when the cache should expire in a strtotime() format.
	 * @var	string $_cachetime
	 */
	protected $_cachetime = null;

	/**
	 * Is true if an etag should be added to the HTTP headers.
	 * @var	array $_cacheetag
	 */
	protected $_cacheetag = true;

	/**
	 * Sets an additional http header. 
	 *
	 * @param	string	$key	The name of the HTTP header.
	 * @param	string	$value	The value of the HTTP header.
	 * @return	null
	 */
	public function setHeader($key, $value = '') {
		if (stripos($key, 'content-type') !== false) {
			throw new \Exception(__CLASS__.': the content-type header should not be directly set. Use setProperty("mimetype", ...) and setProperty("charset", ...) instead.');
		}

		$header = $key . (!empty($value) ? ': '.$value : '');
		$this->_header[] = $header;
	}

	/**
	 * To set the caching time for the current page.
	 *
	 * @param	string	$cachetime	A string in the format of strtotime() to specify when the current page should expire (via Expires header).
	 * @param	string	$etag	Set to false prevents Morrow to set an eTag header. That means the client cache cannot be unset until the Last-Modified header time expires.
	 * @return	null
	 */
	public function setCache($cachetime, $etag = true) {
		$this->_cachetime = $cachetime;
		$this->_cacheetag = $etag;
	}

	/**
	 * Receiver all headers for a given content.
	 *
	 * @param	object	$handle	The stream handle for the generated content.
	 * @param	string	$mimetype	The mimetype that should be delivered with the HTTP headers.
	 * @param	string	$charset	The charset that should be delivered with the HTTP headers.
	 * @return	array All headers as array.
	 */
	public function get($handle, $mimetype, $charset) {
		// use etag for content validation (only HTTP1.1)
		rewind($handle);
		$hash = hash_init('md5');
		hash_update_stream($hash, $handle);
		$hash = hash_final($hash);
		
		// add charset and mimetype to hash
		// if we change one of those we also want to see the actual view
		$hash = md5($hash . $charset . $mimetype);
		
		if ($this->_cacheetag) $this->_header[] = 'ETag: '.$hash; // HTTP 1.1
		$this->_header[] = 'Vary:';
		
		if ($this->_cachetime === null) {
			// no caching
			if ($this->_cacheetag) $this->_header[] = 'Cache-Control: no-cache, must-revalidate';
			else $this->_header[] = 'Cache-Control: no-store, no-cache, must-revalidate'; // to overwrite default php setting with "no-store"
		} else {
			// caching
			$fileexpired = strtotime($this->_cachetime);
			$filemaxage = $fileexpired-time();

			// HTTP 1.0
			$this->_header[] = 'Pragma: ';
			$this->_header[] = 'Expires: '.gmdate("D, d M Y H:i:s", $fileexpired) ." GMT";

			// HTTP 1.1
			$this->_header[] = 'Cache-Control: public, max-age='.$filemaxage;
		}

		// check for etag
		if (!isset($_SERVER['HTTP_CACHE_CONTROL']) || !preg_match('/max-age=0|no-cache/i', $_SERVER['HTTP_CACHE_CONTROL'])) // by-pass "not modified" on explicit reload
			if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $hash) {
				$this->_header[] = 'HTTP/1.1 304 Not Modified';
				// create empty stream
				$handle = fopen('php://temp/maxmemory:'.(1*1024), 'r+'); // 1kb
			}

		// set standard header lines (those headers will be cached)
		// set download header
		if (!empty($displayHandler->downloadable)) {
			if (!$mimetype_changed) {
				$mimetype = $this->getMimeType($displayHandler->downloadable);
			}
			$this->_header[] = 'Content-Disposition: attachment; filename='.basename($displayHandler->downloadable);
		}

		// set content type
		$this->_header[] = 'Content-Type: '.$mimetype.'; charset='.$charset;

		return $this->_header;
	}
		
}

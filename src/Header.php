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

/**
* Controls the HTTP header of the output.
*
* With this class you are able to set your own headers or overwrite headers which were set by the view handlers.
* This class also controls the caching behaviour of the framework.
* 
* Examples
* ---------
*
* ~~~{.php}
* // ... Controller-Code
*  
* $this->header->set('X-Foo: Bar');
*  
* // ... Controller-Code
* ~~~
*
* The output of every view handler can be cached via HTTP headers. 
* It works with Expiration HTTP headers as defined in RFC 2616.
*
* Morrow uses the `ETag` header by default.
* That saves bandwidth, as a web server does not need to send a full response if the content has not changed.
* You don't have to do anything to profit by it.
*
* If you need a harder caching it is possible to tell the browser how long it should not resend a request (via HTTP headers `Expires` (HTTP 1.0) and `Cache-Control` (HTTP 1.1)).
* Because there is no HTTP request to the server you do not have control over the cache until the cache expires.
*
* In the following example the output will be cached for five seconds.
*
* ~~~{.php}
* $this->header->setCache('+5 seconds');
* ~~~
*
* The passed string defines the lifetime of the cache, given as a string `strtotime()` recognizes. 
* 
*/
class Header {
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
	 * Changes the standard mimetype of the view handler. Possible values are `text/html`, `application/xml` and so on.
	 * @var string $mimetype
	 */
	protected $_mimetype = 'text/html';

	/**
	 * Changes the standard charset of the view handler. Possible values are `utf-8`, `iso-8859-1` and so on.
	 * @var string $charset
	 */
	protected $_charset = 'utf-8';

	/**
	 * Changes the http header so that the output is offered as a download. `$_filename` defines the filename which is presented to the user. The mimetype will be automatically determined via file extension. It you specify a different mimetype, that will be used.
	 * @var string $_filename
	 */
	protected $_filename = '';

	/**
	 * Sets a header (`X-UA-Compatible: IE=edge,chrome=1`) for the Internet Explorer to force the browser to use its best rendering engine. 
	 *
	 * @return	null
	 */
	public function __construct() {
		if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
			$this->_header[] = 'X-UA-Compatible: IE=edge,chrome=1';
		}
	}

	/**
	 * Sets or overwrites an existing http header. 
	 *
	 * @param	string	$header	The header you want to set.
	 * @return	null
	 */
	public function set($header) {
		$this->_header[] = $header;
	}

	/**
	 * Sets the caching time for the current page.
	 *
	 * @param	string	$cachetime	A string in the format of strtotime() to specify when the current page should expire.
	 * @param	string	$etag	Set to false prevents Morrow to set an eTag header. That means the client cache cannot be unset until the Last-Modified header time expires.
	 * @return	null
	 */
	public function setCache($cachetime, $etag = true) {
		$this->_cachetime = $cachetime;
		$this->_cacheetag = $etag;
	}

	/**
	 * Sets a filename. That results in a browser downloading the result.
	 *
	 * @param	string	$filename	The filename that should be offered to the client.
	 * @return	null
	 */
	public function setFilename($filename) {
		$this->_filename = $filename;
	}

	/**
	 * Returns all headers for a given content. This is internally used by the \Morrow\Core\Frontcontroller class.
	 *
	 * @param	object	$handle	The stream handle for the generated content.
	 * @return	array All headers as array.
	 */
	public function getAll($handle) {
		// set content type at first position so it will get overwritten by later definitions
		array_unshift($this->_header, 'Content-Type: text/html; charset=utf-8');
		$this->_header[] = 'Vary:';

		// use etag for content validation (only HTTP1.1)
		rewind($handle);
		$hash = hash_init('md5');
		hash_update_stream($hash, $handle);
		$hash = hash_final($hash);
		
		// add all user defined headers to hash
		// if we change one of those we also want to see the actual view
		$hash = md5($hash . implode('', $this->_header));
		
		if ($this->_cacheetag) $this->_header[] = 'ETag: '.$hash; // HTTP 1.1
		
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

		// set download header
		if (!empty($this->_filename)) {
			$this->_header[] = 'Content-Type: ' . $this->getMimeType($this->_filename);
			$this->_header[] = 'Content-Disposition: attachment; filename='.basename($this->_filename);
		}

		return $this->_header;
	}
	
	/**
	 * Returns the mimetype for a given filename.
	 *
	 * @param	string	$file	The filename to retrieve the mimetype for.
	 * @return	string The detected mimetype.
	 */
	public function getMimeType($file) {
		$mime_types = array(
			'ai'		=> 'application/postscript',
			'aif'		=> 'audio/x-aiff',
			'aiff'		=> 'audio/x-aiff',
			'asf'		=> 'video/x-ms-asf',
			'asx'		=> 'video/x-ms-asf',
			'au'		=> 'audio/basic',
			'avi'		=> 'video/x-msvideo',
			'axs'		=> 'application/olescript',
			'bas'		=> 'text/plain',
			'bin'		=> 'application/octet-stream',
			'bmp'		=> 'image/bmp',
			'c'			=> 'text/plain',
			'cdf'		=> 'application/x-cdf',
			'class'		=> 'application/octet-stream',
			'clp'		=> 'application/x-msclip',
			'crd'		=> 'application/x-mscardfile',
			'css'		=> 'text/css',
			'dcr'		=> 'application/x-director',
			'dir'		=> 'application/x-director',
			'dll'		=> 'application/x-msdownload',
			'dms'		=> 'application/octet-stream',
			'doc'		=> 'application/msword',
			'dot'		=> 'application/msword',
			'dvi'		=> 'application/x-dvi',
			'dxr'		=> 'application/x-director',
			'eps'		=> 'application/postscript',
			'exe'		=> 'application/octet-stream',
			'flr'		=> 'x-world/x-vrml',
			'gif'		=> 'image/gif',
			'gtar'		=> 'application/x-gtar',
			'gz'		=> 'application/x-gzip',
			'h'			=> 'text/plain',
			'hlp'		=> 'application/winhlp',
			'hqx'		=> 'application/mac-binhex40',
			'hta'		=> 'application/hta',
			'htc'		=> 'text/x-component',
			'htm'		=> 'text/html',
			'html'		=> 'text/html',
			'htt'		=> 'text/webviewhtml',
			'ico'		=> 'image/x-icon',
			'iii'		=> 'application/x-iphone',
			'jpe'		=> 'image/jpeg',
			'jpeg'		=> 'image/jpeg',
			'jpg'		=> 'image/jpeg',
			'js'		=> 'application/x-javascript',
			'latex'		=> 'application/x-latex',
			'lha'		=> 'application/octet-stream',
			'lzh'		=> 'application/octet-stream',
			'm3u'		=> 'audio/x-mpegurl',
			'mdb'		=> 'application/x-msaccess',
			'mid'		=> 'audio/mid',
			'mov'		=> 'video/quicktime',
			'movie'		=> 'video/x-sgi-movie',
			'mp2'		=> 'video/mpeg',
			'mp3'		=> 'audio/mpeg',
			'mpeg'		=> 'video/mpeg',
			'mpg'		=> 'video/mpeg',
			'ms'		=> 'application/x-troff-ms',
			'mvb'		=> 'application/x-msmediaview',
			'pbm'		=> 'image/x-portable-bitmap',
			'pdf'		=> 'application/pdf',
			'pgm'		=> 'image/x-portable-graymap',
			'png'		=> 'image/png',
			'pot'		=> 'application/vnd.ms-powerpoint',
			'pps'		=> 'application/vnd.ms-powerpoint',
			'ppt'		=> 'application/vnd.ms-powerpoint',
			'ps'		=> 'application/postscript',
			'pub'		=> 'application/x-mspublisher',
			'qt'		=> 'video/quicktime',
			'ra'		=> 'audio/x-pn-realaudio',
			'ram'		=> 'audio/x-pn-realaudio',
			'rgb'		=> 'image/x-rgb',
			'rmi'		=> 'audio/mid',
			'rtf'		=> 'application/rtf',
			'rtx'		=> 'text/richtext',
			'scd'		=> 'application/x-msschedule',
			'sct'		=> 'text/scriptlet',
			'sh'		=> 'application/x-sh',
			'sit'		=> 'application/x-stuffit',
			'snd'		=> 'audio/basic',
			'spl'		=> 'application/futuresplash',
			'stm'		=> 'text/html',
			'svg'		=> 'image/svg+xml',
			'tar'		=> 'application/x-tar',
			'tcl'		=> 'application/x-tcl',
			'tex'		=> 'application/x-tex',
			'texi'		=> 'application/x-texinfo',
			'texinfo'	=> 'application/x-texinfo',
			'tgz'		=> 'application/x-compressed',
			'tif'		=> 'image/tiff',
			'tiff'		=> 'image/tiff',
			'tsv'		=> 'text/tab-separated-values',
			'txt'		=> 'text/plain',
			'vcf'		=> 'text/x-vcard',
			'vrml'		=> 'x-world/x-vrml',
			'wav'		=> 'audio/x-wav',
			'wcm'		=> 'application/vnd.ms-works',
			'wdb'		=> 'application/vnd.ms-works',
			'wks'		=> 'application/vnd.ms-works',
			'wmf'		=> 'application/x-msmetafile',
			'wps'		=> 'application/vnd.ms-works',
			'wri'		=> 'application/x-mswrite',
			'wrl'		=> 'x-world/x-vrml',
			'wrz'		=> 'x-world/x-vrml',
			'xaf'		=> 'x-world/x-vrml',
			'xbm'		=> 'image/x-xbitmap',
			'xla'		=> 'application/vnd.ms-excel',
			'xlc'		=> 'application/vnd.ms-excel',
			'xlm'		=> 'application/vnd.ms-excel',
			'xls'		=> 'application/vnd.ms-excel',
			'xlt'		=> 'application/vnd.ms-excel',
			'xlw'		=> 'application/vnd.ms-excel',
			'xof'		=> 'x-world/x-vrml',
			'xpm'		=> 'image/x-xpixmap',
			'z'			=> 'application/x-compress',
			'zip'		=> 'application/zip'
		);
		
		$file = pathinfo($file);
		if (isset($file['extension'])) $ext = $file['extension'];
		else $ext = 'unknown';
		if (isset($mime_types[$ext])) return $mime_types[$ext];
		else return 'application/x-'.$ext;
	}
}

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
* Controls the output of the framework.
*
* The assigned content, the output format like (X)HTML, XML, Json and so on. Also the caching of the output is controlled by this class. For a detailed explanation of output caching, take a look at the topic Output Caching.
* 
* Examples
* ---------
*
* ~~~{.php}
* // ... Controller-Code
*  
* $this->view->setHandler('XML');
* $this->view->setContent('content', $data);
* $this->view->setProperty('charset', 'iso-8859-1');
*  
* // ... Controller-Code
* ~~~
*/
class View {
	/**
	 * The name of the view handler that is currently selected.
	 * @var	string $_handler_name
	 */
	protected $_handler_name = null;

	/**
	 * Contains all HTTP headers that should be set.
	 * @var	array $_header
	 */
	protected $_header = array();

	/**
	 * Contains all variables that will be assigned to the view handler.
	 * @var	array $_content
	 */
	protected $_content;

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
	 * Contains all properties set for view handlers.
	 * @var	array $_properties
	 */
	protected $_properties = array();

	/**
	 * Contains all filters set for view handlers.
	 * @var	array $_filters
	 */
	protected $_filters = array();

	/**
	 * Assigns content variables to the actual view handler.
	 * If $key is not set, it will be automatically set to "content". 
	 *
	 * @param	mixed	$value	Variable of any type which will be accessable with key name $key.
	 * @param	string	$key	The variable you can use in the view handler to access the variable.
	 * @param	boolean	$overwrite	Set to true if you want to overwrite an existing value. Otherwise you will get an Exception.
	 * @return	null
	 */
	public function setContent($key, $value, $overwrite = false) {
		// validation
		if (!is_string($key) || empty($key)) {
			throw new \Exception(__CLASS__.': the key has to be of type "string" and not empty.');
		}

		// set
		if (isset($this->_content[$key]) && !$overwrite) {
			throw new \Exception(__CLASS__.': the key "'.$key.' is already set.');
		}
		else $this->_content[$key] = $value;
	}

	/**
	 * Returns the content variables actually assigned to the actual view handler.
	 *
	 * @param	string	$key	The key to access the content variable.
	 * @return	mixed	The variable you asked for
	 */
	public function getContent($key = null) {
		if (is_null($key)) return $this->_content;

		if (!is_string($key) OR !isset($this->_content[$key])) {
			throw new \Exception(__CLASS__.': key "'.$key.'" not found.');
			return;
		}
		return $this->_content[$key];
	}

	/**
	 * The main method to create the content that is at least delivered to the client.
	 *
	 * @return	array	Returns an array with the keys `header` (array - the header data) and `content` (stream - the stream handle for the generated content of the view handler).
	 */
	public function get() {
		// get the underlying display handler
		$displayHandler = $this->_getDisplayHandler($this->_handler_name);

		// overwrite default properties
		$mimetype_changed = false;
		if (isset($this->_properties[$this->_handler_name]))
			foreach ($this->_properties[$this->_handler_name] as $key => $value) {
				if (!isset($displayHandler->$key))
					throw new \Exception(__CLASS__.': the property "'.$key.'" does not exist for handler "'.$this->_handler_name.'".');
				$displayHandler->$key = $value;
				if ($key === 'mimetype') $mimetype_changed = true;
			}

		// add charset and mimetype to the "page" array
		$this->_content['page']['charset'] = $displayHandler->charset;
		$this->_content['page']['mimetype'] = $displayHandler->mimetype;
		
		// set standard header lines (those headers will be cached)
		// set download header
		if (!empty($displayHandler->downloadable)) {
			if (!$mimetype_changed) {
				$displayHandler->mimetype = $this->getMimeType($displayHandler->downloadable);
			}
			$this->_header[] = 'Content-Disposition: attachment; filename='.basename($displayHandler->downloadable);
			
			// this is a workaround for ie
			// see http://support.microsoft.com/kb/316431
			$this->_header[] = 'Pragma: protected';
			$this->_header[] = 'Cache-control: protected, must-revalidate';
		}

		// set content type
		$this->_header[] = 'Content-Type: '.$displayHandler->mimetype.'; charset='.$displayHandler->charset;
		
		// output
		// create stream handle for the output
		$handle = fopen('php://temp/maxmemory:'.(1*1024*1024), 'r+'); // 1MB

		// get body stream
		$handle = $displayHandler->getOutput($this->getContent(), $handle);
		
		// process Filters
		if (isset($this->_filters[$this->_handler_name])) {
			$handle = $this->_processFilters($handle);
		}

		// do not compress files bigger than 1 MB to preserve memory and save cpu power
		$stats = fstat($handle);
		$size = $stats['size'];

		if ($size > (1*1024*1024)) {
			$content = ob_get_clean();
			ob_start();
			echo $content;
		}

		// use etag for content validation (only HTTP1.1)
		rewind($handle);
		$hash = hash_init('md5');
		hash_update_stream($hash, $handle);
		$hash = hash_final($hash);
		
		// add charset and mimetype to hash
		// if we change one of those we also want to see the actual view
		$hash = md5($hash . $displayHandler->charset . $displayHandler->mimetype);
		
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

		// rewind handle
		rewind($handle);
		
		return array(
			'headers' => $this->_header,
			'content' => $handle,
		);
	}

	/**
	 * Processes the filters which were set for the current view handler.
	 *
	 * @param	resource	$handle	The stream handle for the generated content.
	 * @return	resource	The changed stream handle.
	 */
	protected function _processFilters($handle) {
		rewind($handle);
		$content = stream_get_contents($handle);
		
		// target handle
		$fhandle = fopen('php://memory', 'r+');
		fclose($handle);
			
		foreach ($this->_filters[$this->_handler_name] as $value) {
			$filtername = $value[0];
			$filter = new $filtername( $value[1] );
			$content = $filter->get($content);
		}
		fwrite($fhandle, $content);
		
		return $fhandle;
	}

	/**
	 * Returns an instance of a view handler for a given view name.
	 *
	 * @param	string	$handler_name	The name of the view handler to instantiate.
	 * @return	object	The view handler.
	 */
	protected function _getDisplayHandler($handler_name) {
		if ($handler_name == null) {
			throw new \Exception(__CLASS__ . ': You did not set a view handler via $this->view->setHandler().');
		}
		
		$classname = '\\Morrow\\Views\\' . $handler_name;
		return new $classname($this);
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
	 * Sets a filter to be executed after content generation.
	 * If you have not chosen a handler the default handler will be used. For example if you want globally define your settings for all handlers.  
	 *
	 * @param	string	$name	The name of the filter to set.
	 * @param	array	$config	The config that will be passed to the filter.
	 * @param	string	$handler_name	Restricts the execution of the filter to a view handler.
	 * @return	null
	 */
	public function setFilter($name, $config = array(), $handler_name = null) {
		if ($handler_name == null) $handler_name = $this->_handler_name;
		$this->_filters[$handler_name][$name] = array('\\Morrow\\Filters\\' . $name, $config);
	}

	/**
	 * Sets the handler which is responsable for the format of the output.
	 * Possible values are "serpent", "php", "plain", "csv", "excel", "flash", "xml" und "json".
	 * The usage ot the view formats are described in the manual at "View handlers". 
	 *
	 * @param	string	$handler_name	The name of the view handler to set.
	 * @return	null
	 */
	public function setHandler($handler_name) {
		$this->_handler_name = ucfirst(strtolower($handler_name));
	}

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
	 * Sets handler specific properties. The properties mimetype, charset and downloadable are defined for every view handler.
	 * If you have not chosen a handler the default handler will be used. For example if you want globally define your settings for all handlers.  
	 *
	 * @param	string	$key	The name of the property to set.
	 * @param	array	$value	The value of the property.
	 * @param	string	$handler_name	Restricts the passed property to a view handler.
	 * @return	null
	 */
	public function setProperty($key, $value = array(), $handler_name = null) {
		if ($handler_name == null) $handler_name = $this->_handler_name;
		$this->_properties[$handler_name][$key] = $value;
	}

	/**
	 * Returns the mimetype for a given filename.
	 *
	 * @param	string	$file	The filename to retrieve the mimetype for.
	 * @return	string The detected mimetype.
	 */
	public function getMimeType($file) {
		$mime_types = array(
			'ai' => 'application/postscript',
			'aif' => 'audio/x-aiff',
			'aiff' => 'audio/x-aiff',
			'asf' => 'video/x-ms-asf',
			'asx' => 'video/x-ms-asf',
			'au' => 'audio/basic',
			'avi' => 'video/x-msvideo',
			'axs' => 'application/olescript',
			'bas' => 'text/plain',
			'bin' => 'application/octet-stream',
			'bmp' => 'image/bmp',
			'c' => 'text/plain',
			'cdf' => 'application/x-cdf',
			'class' => 'application/octet-stream',
			'clp' => 'application/x-msclip',
			'crd' => 'application/x-mscardfile',
			'css' => 'text/css',
			'dcr' => 'application/x-director',
			'dir' => 'application/x-director',
			'dll' => 'application/x-msdownload',
			'dms' => 'application/octet-stream',
			'doc' => 'application/msword',
			'dot' => 'application/msword',
			'dvi' => 'application/x-dvi',
			'dxr' => 'application/x-director',
			'eps' => 'application/postscript',
			'exe' => 'application/octet-stream',
			'flr' => 'x-world/x-vrml',
			'gif' => 'image/gif',
			'gtar' => 'application/x-gtar',
			'gz' => 'application/x-gzip',
			'h' => 'text/plain',
			'hlp' => 'application/winhlp',
			'hqx' => 'application/mac-binhex40',
			'hta' => 'application/hta',
			'htc' => 'text/x-component',
			'htm' => 'text/html',
			'html' => 'text/html',
			'htt' => 'text/webviewhtml',
			'ico' => 'image/x-icon',
			'iii' => 'application/x-iphone',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'js' => 'application/x-javascript',
			'latex' => 'application/x-latex',
			'lha' => 'application/octet-stream',
			'lzh' => 'application/octet-stream',
			'm3u' => 'audio/x-mpegurl',
			'mdb' => 'application/x-msaccess',
			'mid' => 'audio/mid',
			'mov' => 'video/quicktime',
			'movie' => 'video/x-sgi-movie',
			'mp2' => 'video/mpeg',
			'mp3' => 'audio/mpeg',
			'mpeg' => 'video/mpeg',
			'mpg' => 'video/mpeg',
			'ms' => 'application/x-troff-ms',
			'mvb' => 'application/x-msmediaview',
			'pbm' => 'image/x-portable-bitmap',
			'pdf' => 'application/pdf',
			'pgm' => 'image/x-portable-graymap',
			'png' => 'image/png',
			'pot' => 'application/vnd.ms-powerpoint',
			'pps' => 'application/vnd.ms-powerpoint',
			'ppt' => 'application/vnd.ms-powerpoint',
			'ps' => 'application/postscript',
			'pub' => 'application/x-mspublisher',
			'qt' => 'video/quicktime',
			'ra' => 'audio/x-pn-realaudio',
			'ram' => 'audio/x-pn-realaudio',
			'rgb' => 'image/x-rgb',
			'rmi' => 'audio/mid',
			'rtf' => 'application/rtf',
			'rtx' => 'text/richtext',
			'scd' => 'application/x-msschedule',
			'sct' => 'text/scriptlet',
			'sh' => 'application/x-sh',
			'sit' => 'application/x-stuffit',
			'snd' => 'audio/basic',
			'spl' => 'application/futuresplash',
			'stm' => 'text/html',
			'svg' => 'image/svg+xml',
			'tar' => 'application/x-tar',
			'tcl' => 'application/x-tcl',
			'tex' => 'application/x-tex',
			'texi' => 'application/x-texinfo',
			'texinfo' => 'application/x-texinfo',
			'tgz' => 'application/x-compressed',
			'tif' => 'image/tiff',
			'tiff' => 'image/tiff',
			'tsv' => 'text/tab-separated-values',
			'txt' => 'text/plain',
			'vcf' => 'text/x-vcard',
			'vrml' => 'x-world/x-vrml',
			'wav' => 'audio/x-wav',
			'wcm' => 'application/vnd.ms-works',
			'wdb' => 'application/vnd.ms-works',
			'wks' => 'application/vnd.ms-works',
			'wmf' => 'application/x-msmetafile',
			'wps' => 'application/vnd.ms-works',
			'wri' => 'application/x-mswrite',
			'wrl' => 'x-world/x-vrml',
			'wrz' => 'x-world/x-vrml',
			'xaf' => 'x-world/x-vrml',
			'xbm' => 'image/x-xbitmap',
			'xla' => 'application/vnd.ms-excel',
			'xlc' => 'application/vnd.ms-excel',
			'xlm' => 'application/vnd.ms-excel',
			'xls' => 'application/vnd.ms-excel',
			'xlt' => 'application/vnd.ms-excel',
			'xlw' => 'application/vnd.ms-excel',
			'xof' => 'x-world/x-vrml',
			'xpm' => 'image/x-xpixmap',
			'z' => 'application/x-compress',
			'zip' => 'application/zip'
		);
		
		$file = pathinfo($file);
		if (isset($file['extension'])) $ext = $file['extension'];
		else $ext = 'unknown';
		if (isset($mime_types[$ext])) return $mime_types[$ext];
		else return 'application/x-'.$ext;
	}
}

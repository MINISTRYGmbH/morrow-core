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
* Provides some useful methods for dealing with URLs.
*
* Especially create() and redirect() are important, since they provide correct handling of Morrow paths in the context of language handling.
* 
* A Morrow URL has this structure:
* `language`/`node 1`/`node 2`
* 
* `language` is optional.
* So if you want to link to a page with the default language you can omit the language parameter.
* 
* Examples
* ---------
*
* ~~~{.php}
* // ... Controller code
*
* // extend an absolute URL with an additional GET parameter
* $url = $this->url->create('http://chuck:norris@example.com:80/home?foo=bar#42', array('foo2' => 'bar2'));
* 
* // URLs without a scheme work too
* $url = $this->url->create('//example.com/home', array('foo' => 'bar'));
*
* // create an URL to the actual page
* $url = $this->url->create();
*
* // ... Controller code
* ~~~
*
* ### Change the language
*
* If you just want to change the language just use the `language` query parameter which is handled different to other query parameters.
*
* ~~~{.php}
* // ... Controller code
*
* // create an absolute URL to the homepage and change the language
*
* $url = $this->url->create('home', array('language' => 'de'), true);
* // is the same as
* $url = $this->url->create('de/home', array(), true);
*
* // ... Controller code
* ~~~
*
* ### Redirect
* 
* ~~~{.php}
* // ... Controller code
*
* // redirect to the not-found page
* $this->url->redirect('not-found/', array(), 404);
*
* // ... Controller code
* ~~~
*/
class Url {
	/**
	 * Contains the currently active language.
	 * @var	array $_language_actual
	 */
	protected $_language_actual;

	/**
	 * Contains all valid language keys.
	 * @var	array $_language_possible
	 */
	protected $_language_possible;

	/**
	 * Contains the full path of the current page.
	 * @var	array $_fullpath
	 */
	protected $_fullpath;

	/**
	 * Because of the htaccess rewriting rules the base href can be different from what we expect and want
	 * So we have to strip x levels from the basehref. The count of levels we have to strip is defined here.
	 * @var	int $_fullpath
	 */
	protected $_basehref_depth = 0;

	/**
	 * All parameters passed are used for create(). You don't have to do this yourself in Morrow.
	 *
	 * @param	string	$language_actual	Contains the currently active language.
	 * @param	array	$language_possible	Contains all valid language keys.
	 * @param	string	$fullpath	Contains the full path of the current page.
	 * @param	integer	$basehref_depth	Necessary parameter for `getBasehref()`. Defines how man path nodes we have to skip to the the correct basehref.
	 */
	public function __construct($language_actual, $language_possible, $fullpath, $basehref_depth) {
		$this->_language_actual		= $language_actual;
		$this->_language_possible	= $language_possible;
		$this->_fullpath			= $fullpath;
		$this->_basehref_depth		= $basehref_depth;
	}

	/**
	 * Like PHP's parse_url() but returns an array with all of the keys even if they are empty.
	 * It also adds some keys which are necessary to build a fully qualified URL.
	 * So it is possible to rebuild the parsed URL with an implode() on the result.
	 *
	 * @param	string	$url	The URL to parse.
	 * @return	array	An array with all the keys.
	 */
	public function parse($url) {
		$template = array(
			'scheme'			=> '',
			'scheme_divider'	=> '',
			'user'				=> '',
			'pass_divider'		=> '',
			'pass'				=> '',
			'auth_divider'		=> '',
			'host'				=> '',
			'port_divider'		=> '',
			'port'				=> '',
			'path'				=> '',
			'query_divider'		=> '',
			'query'				=> '',
			'fragment_divider'	=> '',
			'fragment'			=> '',
		);
		$parts = array_merge($template, parse_url($url));

		if ($parts['scheme']	!== '') $parts['scheme_divider'] = '://';
		if ($parts['pass']		!== '') $parts['pass_divider'] = ':';
		if ($parts['user']		!== '') $parts['auth_divider'] = '@';
		if ($parts['port']		!== '') $parts['port_divider'] = ':';
		if ($parts['query']		!== '') $parts['query_divider'] = '?';
		if ($parts['fragment']	!== '') $parts['fragment_divider'] = '#';

		// parse_url does not work with missing schemes until PHP 5.4.7
		if (isset($parts['path']{1}) && $parts['path']{1} === '/') {
			$parts['path']				= ltrim($parts['path'], '/');
			$parts['host']				= strstr($parts['path'], '/', true);
			$parts['path']				= strstr($parts['path'], '/');
			$parts['scheme_divider']	= '//';
		}

		return $parts;
	}

	/**
	 * Does a clean redirect.
	 *
	 * @param	string	$path	The URL or the Morrow path to redirect to.
	 * @param	array	$query	Query parameters to adapt the URL.
	 * @param	integer	$http_response_code	The HTTP code which should be submitted to the client.
	 */
	public function redirect($path, $query = array(), $http_response_code = 302) {
		$url = $this->create($path, $query, true, '&');
		header('Location: '.$url, true, $http_response_code);
		die('');
	}


	/**
	 * Creates a slug (the part of a URL which identifies a page using human-readable keywords).
	 *
	 * @param	string	$text	The text to convert.
	 * @return	string	The created slug.
	 */
	public function slug($text) {
		$table = array(
			'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
			'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'AE', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
			'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
			'Õ'=>'O', 'Ö'=>'OE', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'UE', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'ss',
			'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
			'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
			'ô'=>'o', 'õ'=>'o', 'ö'=>'oe', 'ø'=>'o', 'ü'=>'ue', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
			'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
		);

		$slug = strtr($text, $table);
		$slug = trim(preg_replace("|\W+|", '-', $slug), '-');
		return $slug;
	}

	/**
	 * Creates a URL for use with Morrow. It handles automatically languages in the URL. 
	 *
	 * @param	string	$path	The URL or the Morrow path to work with. Leave empty if you want to use the current page.
	 * @param	array	$query	Query parameters to adapt the URL.
	 * @param	boolean	$absolute	If set to true the URL will be a fully qualified URL.
	 * @param	string	$separator	The string that is used to divide the query parameters.
	 * @return	string	The created URL.
	 */
	public function create($path = '', $query = array(), $absolute = false, $separator = '&amp;') {
		// if the passed path is empty use the path of the current page
		if (empty($path)) $path = $this->_fullpath;

		// parse input url
		$parts = $this->parse($path);
		
		// combine query parameters
		parse_str($parts['query'], $parts['query']);
		$parts['query'] = array_merge($parts['query'], $query);

		// only for URLs without a scheme
		if (empty($parts['scheme_divider'])) {

			$nodes				= explode('/', trim($parts['path'], '/'));

			// **********************  language handling
			$lang = $this->_language_actual;

			// remove the language from the path
			if (in_array($nodes[0], $this->_language_possible)) {
				$lang = array_shift($nodes);
			}

			// lang as GET param has precedence
			if (isset($query['language']) && in_array($query['language'], $this->_language_possible)) {
				$lang = $query['language'];
				unset($parts['query']['language']);
			}

			// now we have to add the language in some cases
			// add the lang to the path if it is not the current language and not the default language
			if ($lang !== $this->_language_actual || $this->_language_possible[0] !== $this->_language_actual) {
				array_unshift($nodes, $lang);
			}
			// **********************

			// put it back together
			$parts['path'] = implode('/', $nodes);

			// create complete url with domain
			if ($absolute) {
				$base			= $this->parse($this->getBaseHref());
				$base['path']	= $base['path'] . $parts['path'];
				$base['query']	= $parts['query'];
				$parts			= $base;
			}
		}

		if (count($parts['query']) > 0) {
			$parts['query_divider'] = '?';
		}

		// create query string
		$parts['query'] = http_build_query($parts['query'], '', $separator);

		return implode('', $parts);
	}

	/**
	 * To get the base href for the actual Morrow installation.
	 *
	 * @return	string	The base href.
	 */
	public function getBasehref() {
		$script_name = dirname($_SERVER['SCRIPT_NAME']);
		$script_name = str_replace('\\', '/', $script_name); // for Windows paths
		$path = '/'. trim($script_name, '/') .'/';
		
		// If it is the root the return value of dirname is slash
		if ($path == '//') $path = '/';
		$scheme = isset($_SERVER['HTTPS']) || isset($_SERVER['HTTP_X_SSL_ACTIVE']) || (isset($_SERVER['SSL_PROTOCOL']) && !empty($_SERVER['SSL_PROTOCOL'])) ? 'https://' : 'http://';
		
		$host = php_sapi_name() === 'cli' ? gethostname() : $_SERVER['HTTP_HOST']; // Exception for cli
		$base_href = $scheme . $host . $path;

		// We have to strip x nodes from the end of the base href
		// Depends on the htaccess entry point
		$base_href = preg_replace('|([^/]+/){'. $this->_basehref_depth .'}$|', '', $base_href);
		return $base_href;
	}
}

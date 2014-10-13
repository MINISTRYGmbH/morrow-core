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
 * Adds a little bit of security to your app or webpage.
 * 
 * For more information on the security handling in the Morrow framework take a look at the [Security page](page/security).
 */
class Security {
	/**
	 * Adds a little bit of security to your app or webpage.
	 *
	 * @param	array	$config	The configuration for this class.
	 * @param	object	$header	An instance of the header class.
	 * @param	object	$url	An instance of the url class.
	 * @param	string	$input_csrf_token	The csrf token that came from the client
	 */
	public function __construct($config, $header, $url, $input_csrf_token) {
		$this->config			= $config;
		$this->header			= $header;
		$this->url				= $url;
		$this->input_csrf_token	= $input_csrf_token;
		$this->csrf_token		= md5(uniqid(rand(), true));

		// hide PHP version
		header_remove("X-Powered-By");

		// set headers
		$this->_setCsp($config['csp']);
		
		$this->header->set("X-Frame-Options: {$config['frame_options']}");

		// prevent MIME type sniffing
		$this->header->set("X-Content-Type-Options: {$config['content_type_options']}");
	}

	/**
	 * Sets the value for the CSP header to prevent XSS attacks.
	 * Should be written as the official specs says (<http://www.w3.org/TR/CSP/>).
	 * For a detailed description take a look at:
	 * <https://developer.mozilla.org/en-US/docs/Security/CSP/CSP_policy_directives>.
	 *
	 * @param	array	$options	The options as associative array. Use the rule name as key and the option as value.
	 * @return  `null`
	 */
	protected function _setCsp($options) {
		$csp_gecko	= '';
		$csp		= '';

		$options['options'] = '';

		// handle some differences between the browsers
		foreach ($options as $key => $value) {
			if ($value == '') continue;
			$key = strtolower($key);
			// handle some differences between the browsers
			// and create the csp string
			if ($key != 'options') {
				$csp	.= $key . ' ' . $value . ';';
			}
		}

		$this->header->set("Content-Security-Policy: {$csp}");
	}
	
	/**
	 * Gets the CSRF token for the current user.
	 * @return	`string`
	 */
	public function getCSRFToken() {
		return $this->csrf_token;
	}
	
	/**
	 * Creates an URL like URL::create() but adds the CSRF token as GET parameter.
	 * You have to check the token yourself via verifyCSRFToken().
	 *
	 * For the parameters see: Url::create().
	 * 
	 * @param	string	$path	The URL or the Morrow path to work with. Leave empty if you want to use the current page.
	 * @param	array	$query	Query parameters to adapt the URL.
	 * @param	boolean	$absolute	If set to true the URL will be a fully qualified URL.
	 * @param	string	$separator	The string that is used to divide the query parameters.
	 * @return	string	The created URL.
	 */
	public function createCSRFUrl($path = '', $query = [], $absolute = false, $separator = '&amp;') {
		$query['csrf_token'] = $this->csrf_token;
		return $this->url->create($path, $query, $absolute, $separator);
	}

	/**
	 * A function to verify a valid CSRF token.
	 * @return	boolean	Returns `true` if a valid token was sent otherwise `false`.
	 */
	public function checkCSRFToken() {
		if ($this->input_csrf_token != $this->csrf_token) {
			return false;
		}
		return true;
	}
	
	/**
	 * Creates a 60 characters long hash by using the crypt function with the Blowfish algorithm.
	 * @param	string	$string	The input string (e.g. a password) to hash.
	 * @return	string	The hash.
	 */
	public static function createHash($string) {
		$salt = substr(str_replace('+', '.', base64_encode(pack('N4', mt_rand(), mt_rand(), mt_rand(), mt_rand()))), 0, 22);
		return crypt($string, '$2a$10$' . $salt . '$'); // we use Blowfish with a cost of 10
	}

	/**
	 * Checks if the hash is valid.
	 * @param	string	$string	The input string (e.g. a password) to hash.
	 * @param	string	$hash	The hash to check against.
	 * @return	`bool`
	 */
	public static function checkHash($string, $hash) {
		if (crypt($string, $hash) == $hash) return true;
		return false;
	}
}

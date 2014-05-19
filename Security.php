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
 * Example
 * -------
 *
 * ~~~{.php}
 * // Controller code
 *
 * // add CSP security rules
 * // all rules which are identical to default-src can be left out.
 * $this->security->setCsp(array(
 *     'default-src'	=> "'self'",
 *     //'script-src'	=> "'self'",
 *     //'img-src'		=> "'self'",
 *     'style-src'		=> "'self' http://fonts.googleapis.com",
 *     //'media-src'	=> "'self'",
 *     //'object-src'	=> "'self'",
 *     //'frame-src'	=> "'self'",
 *     'font-src'		=> "'self' http://themes.googleusercontent.com",
 * ));
 *
 * // Do not allow to show this site in a frameset (prevents clickjacking)
 * $this->security->setFrameOptions('DENY');
 * ~~~
 */
class Security {
	/**
	 * Adds a little bit of security to your app or webpage.
	 *
	 * @param	object	$session	An instance of the session class.
	 * @param	object	$view	An instance of the session class.
	 * @param	object	$input	An instance of the input class.
	 * @param	object	$url	An instance of the url class.
	 */
	public function __construct($session, $view, $input, $url) {
		$this->session	= $session;
		$this->view		= $view;
		$this->input	= $input;
		$this->url		= $url;

		// hide PHP version
		header_remove("X-Powered-By");

		// set new token
		if (is_null($session->get('csrf_token'))) $session->set('csrf_token', md5(uniqid(rand(), true)));
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
	public function setCsp($options) {
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

		$this->view->setHeader('X-Content-Security-Policy', $csp); // Used by Firefox and Internet Explorer,
		$this->view->setHeader('Content-Security-Policy', $csp); // Defined by W3C Specs as standard header, used by Chrome starting with version 25
	}
	
	/**
	 * Sets whether or not a browser should be allowed to render the page in a frame or iframe.
	 * This can be used to avoid clickjacking attacks, by ensuring that their content is not embedded into other sites.
	 *
	 * The following options are possible:
	 *
	 * Value | Description
	 * ------|-------------
	 * `DENY` | The page cannot be displayed in a frame, regardless of the site attempting to do so.
	 * `SAMEORIGIN` | The page can only be displayed in a frame on the same origin as the page itself.
	 * `ALLOW-FROM` _uri_ | The page can only be displayed in a frame on the specified origin.
	 * 
	 * @param	string	$option	The option as described
	 */
	public function setFrameOptions($option) {
		$this->view->setHeader('X-Frame-Options', $option);
	}

	/**
	 * Gets the CSRF token for the current user.
	 * @return	`string`
	 */
	public function getCSRFToken() {
		return $this->session->get('csrf_token');
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
	public function createCSRFUrl($path = '', $query = array(), $absolute = false, $separator = '&amp;') {
		$query['csrf_token'] = $this->session->get('csrf_token');
		return $this->url->create($path, $query, $absolute, $separator);
	}

	/**
	 * A function to verify a valid CSRF token.
	 * @return	boolean	Returns `true` if a valid token was sent otherwise `false`.
	 */
	public function checkCSRFToken() {
		if ($this->input->get('csrf_token') != $this->session->get('csrf_token')) {
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

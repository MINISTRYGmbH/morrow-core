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


namespace Morrow\Core;

use Morrow\Factory;
use Morrow\Debug;

/**
 * Handles the mapping from URL paths to Controller class names.
 */
class Router {
	/**
	* Contains all routes that should be explicitely executed.
	* @var array $_routes
	*/
	protected $_routes;

	/**
	* A callable construct that is executed if no one of the routes matches.
	* @var callable $_fallback
	*/
	protected $_fallback;

	/**
	 * Initializes the class.
	 *
	 * @param  array $routes All routes that should be explicitely executed.
	 * @param  callable $fallback A callable construct that is executed if no one of the routes matches.
	 * @return null
	 */
	public function __construct($routes, Callable $fallback) {
		$this->_routes		= $routes;
		$this->_fallback	= $fallback;
	}

	/**
	 * Parses a given URL path to get the resulting class name and created parameters.
	 *
	 * @param  string $url The url path.
	 * @return array Returns an array with the keys `controller` and `parameters`.
	 */
	public function parse($url) {
		// iterate all rules
		$hit = false;

		foreach ($this->_routes as $rule => $new_class) {
			$regex		= trim($rule, '/');

			// rebuild route to a preg pattern
			if (preg_match($regex, $url, $parameters)) {
				$class = preg_replace($regex, $new_class, $url);
				unset($parameters[0]);
				$hit = true;
				break;
			}
		}

		if ($hit === false) {
			$class = call_user_func($this->_fallback, $url);
		}

		return [
			'controller'		=> $class,
			'parameters'		=> isset($parameters) ? $parameters : [],
		];
	}
}

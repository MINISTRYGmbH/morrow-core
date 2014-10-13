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
* Gives access to several parameters of the environment useful for the current page.
* 
* It is filled by the framework.
* 
* Dot Syntax
* ----------
* 
* This class works with the extended dot syntax. So if you use keys like `foo.bar` and `foo.bar2` as identifiers in your config, you can call `$this->Page->get("foo")` to receive an array with the keys `bar` and `bar2`. 
* 
* Examples
* ---------
*
* ~~~{.php}
* // ... Controller code
*  
* // show current page environment
* Debug::dump($this->Page->get());
* 
* // ... Controller code
* ~~~
* 
* ### Result
* ~~~
* $ => Array (5)
* (
*     ['nodes'] => Array (1)
*     (
*         ['0'] = String(4) "Home"
*     )
*     ['base_href'] = String(27) "http://example.com/morrow/"
*     ['alias'] = String(4) "home"
*     ['url'] = String(4) "home"
*     ['path'] => Array (4)
*     (
*         ['relative'] = String(4) "Home"
*         ['relative_with_query'] = String(4) "Home"
*         ['absolute'] = String(31) "http://example.com/morrow/Home"
*         ['absolute_with_query'] = String(31) "http://example.com/morrow/Home"
*     )
* ~~~
*/
class Page extends Core\Base {
	/**
	* The data array which does not have dotted keys anymore
	* @var array $data
	*/
	protected $data = []; // The array with parsed data

	/**
	 * Retrieves configuration parameters. If `$identifier` is not passed, it returns an array with the complete configuration. Otherwise only the parameters below `$identifier`. 
	 * 
	 * @param string $identifier Config data to be retrieved
	 * @param mixed $fallback The return value if the identifier was not found.
	 * @return mixed
	 */
	public function get($identifier = null, $fallback = null) {
		return $this->arrayGet($this->data, $identifier, $fallback);
	}

	/**
	 * Sets registered config parameters below $identifier. $value can be of type string or array. 
	 * 
	 * @param string $identifier Config data path to be set
	 * @param mixed $value The value to be set
	 * @return null
	 */
	public function set($identifier, $value) {
		return $this->arraySet($this->data, $identifier, $value);
	}
}

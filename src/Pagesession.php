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
* Identical to the Session class but stored information is only accessible from the current page.
* 
* The Pagesession is useful for storing user information that is only relevant for the current page, e.g. table sorting information on database output, or like the example below, counting user visits per page. 
* 
* Dot Syntax
* ----------
* 
* This class works with the extended dot syntax. So if you use keys like `foo.bar` and `foo.bar2` as identifiers in your config, you can call `$this->session->get("foo")` to receive an array with the keys `bar` and `bar2`. 
* 
* Examples
* ---------
*
* ~~~{.php}
* // ... Controller code
*  
* // counting user visits for each page
* 
* $visits = $this->pagesession->get('visits');
* if ($visits === null) $visits = 0;
*  
* $this->pagesession->set('visits', ++$visits);
* 
* // ... Controller code
* ~~~
*/
class PageSession extends Session {
	/**
	 * Initializes the class. Usually you don't have to initialize this class yourself.
	 * 
	 * @param string $section Defines the key you want to save the data in the `$_SESSION`.
	 */
	public function __construct($section) {
		$this->section = $section;
	}
}

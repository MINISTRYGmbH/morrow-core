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


namespace Morrow\Filters;

/**
 * This filter is a generic one to implement simple filters on the fly.
 *
 * Useful if you just have to do simple replacements or actions with your content because you don't need to write your own filter.
 * Use the placeholder `:CONTENT` to pass your content to the php function where it expects the input string.
 * 
 *
 * Example
 * --------
 * 
 * ~~~{.php}
 * // ... Controller code
 *
 * // Replaces all occurences of `#TIME#` with the current timestamp
 * $this->view->setFilter('Generic', array('str_replace', '#TIME#', time(), ':CONTENT') );
 *
 * // Change the encoding of the whole output from iso to utf-8
 * $this->view->setFilter('Generic', array('mb_convert_encoding', ':CONTENT', 'utf-8', 'iso-8859-1'));
 *
 * // ... Controller code
 * ~~~
 */
class Generic extends AbstractFilter {
	/**
	 * The name of the PHP interal function to call.
	 * @var string $userfunction
	 */
	public $userfunction = '';

	/**
	 * The params that will be passed to the $userfunction.
	 * @var array $params
	 */
	public $params = array();
	
	/**
	 * The constructor which handles the passed parameters set in the second parameter of $this->view->setFilter().
	 * @param	array	$config	The configuration parameters.
	 */
	public function __construct($config = array()) {
		$this->userfunction = $config[0];
		$this->params = array_slice($config, 1);
	}
	
	/**
	 * This function calls the $userfunction.
	 * @param   string	$content  The content the view class has created.
	 * @return  string  Returns the modified content.
	 */
	public function get($content) {
		// replace placeholder :CONTENT with $content
		$key = array_search(':CONTENT', $this->params);
		if ($key !== false) $this->params[$key] = $content;

		$content = call_user_func_array($this->userfunction, $this->params);
		return $content;
	}
}

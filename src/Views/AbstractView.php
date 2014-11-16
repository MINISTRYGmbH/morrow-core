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


namespace Morrow\Views;

use Morrow\Factory;
use Morrow\Debug;

/**
 * You should extend this abstract class if you are writing your own filter.
 */
abstract class AbstractView {
	/**
	 * Contains all variables that will be assigned to the view handler.
	 * @var	array $_content
	 */
	protected $_content;

	/**
	 * Set to true if your view handler returns HTML. This flag allows to use Morrows Features.
	 * @var	boolean $is_returning_html
	 */
	public $is_returning_html = false;

	/**
	 * The view handler could extend this method to set some parameters.
	 * @param	string	$class	The full class name of the currently executed controller class.
	 */
	public function init($class) {
	}

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
		if (isset($this->_content[$key]) && !$overwrite) {
			throw new \Exception(__CLASS__.': the key "'.$key.' is already set.');
		}
		else $this->_content[$key] = $value;
	}

	/**
	 * You always have to define this method.
	 * @return  stream  Should return the rendered content.
	 */
	abstract public function getOutput();
}

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
 * This filters saves the output to a given file.
 *
 * Useful if you need a static version of your HTML code.
 * 
 * Example
 * --------
 * 
 * ~~~{.php}
 * // ... Controller code
 *
 * $this->view->setFilter('Save', APP_PATH . 'generated_html/' . $this->page->get('alias') . '.html');
 *
 * // ... Controller code
 * ~~~
 */
class Save extends AbstractFilter {
	/**
	 * The path the output will be saved into.
	 * @var string $_path
	 */
	protected $_path = '';
	
	/**
	 * The constructor which handles the passed parameters set in the second parameter of $this->view->setFilter().
	 * @param   string   $path The path to the path.
	 */
	public function __construct($path) {
		$this->_path = $path;
	}
	
	/**
	 * This function saves the content to the path.
	 * @param   string  $content  The content the view class has created.
	 * @return  string  Returns the modified content.
	 */
	public function get($content) {
		file_put_contents($this->_path, $content);
		return $content;
	}
}

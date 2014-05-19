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

/**
 * This view generates an URL encoded string which can be read from the Flash loadvars object.
 * 
 * The keys of an multidimensional array will be combined with an underscore to create unique identifiers. Numeric keys will automatically prefixed with the `$numeric_prefix`.
 *
 * All public members of a view handler are changeable in the Controller by `\Morrow\View->setProperty($member, $value)`;
 *
 * Example
 * --------
 * 
 * ~~~{.php}
 * // ... Controller code
 *
 * $data['frame']['section1']     = 'Example';
 * $data['frame'][0]['headline']  = 'Example';
 * $data['frame'][0]['copy']      = 'Example text';
 * $data['frame']['section2']     = 'This is a "<a>-link</a>';
 *  
 * $this->view->setHandler('Flash');
 * $this->view->setContent('content', $data);
 *
 * // ... Controller code
 * ~~~
 */
class Flash extends AbstractView {
	/**
	 * Changes the standard mimetype of the view handler. Possible values are `text/html`, `application/xml` and so on.
	 * @var string $mimetype
	 */
	public $mimetype	= 'text/plain';

	/**
	 * If numeric indices are used in the base array this parameter will be prepended to the numeric index.
	 * @var string $numeric_prefix
	 */
	public $numeric_prefix = 'entry';

	/**
	 * You always have to define this method.
	 * @param   array $content Parameters that were passed to \Morrow\View->setContent().
	 * @param   handle $handle  The stream handle you have to write your created content to.
	 * @return  string  Should return the rendered content.
	 * @hidden
	 */
	public function getOutput($content, $handle) {
		if (isset($content['content'])) $this -> _outputVars('', $content['content'], $handle);
		fwrite($handle, '&eof=1');
		return $handle;
	}
	
	/**
	 * A recursive method that generates the loadVars compatible string.
	 * @param   string $var The current name of the variable.
	 * @param   array $input Parameters that were passed to \Morrow\View->setContent().
	 * @param   handle $handle  The stream handle you have to write your created content to.
	 * @return  string  Should return the rendered content.
	 */
	protected function _outputVars($var, $input, $handle) {
		if (is_array($input)) {
			if (count($input) === 0) return '';
			foreach ($input as $key => $value) {
				if (is_numeric($key)) $key = $this->numeric_prefix.$key;
				if (!empty($var)) $var_to = $var.'_'.$key;
				else $var_to = $key;

				if (!isset($output)) $output = '';
				$this -> _outputVars($var_to, $value, $handle);
			}
		} else {
			// Ausgabe des Inhalts
			if ($input === true) $input = 'true';
			elseif ($input === false) $input = 'false';
			elseif ($input === null) $input = 'null';
			else {
				$input = trim(stripslashes($input));
			}

			$body = $input;
			fwrite($handle, '&'.$var.'='.rawurlencode($body));
		}
	}
}

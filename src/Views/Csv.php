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

/**
 * With this view handler it is possible to generate and output CSV (comma separated values) files.
 *
 * Example
 * --------
 * 
 * ~~~{.php}
 * // ... Controller code
 *
 * $data[0]['date']       = '2007-01-01';
 * $data[0]['headline']   = 'I am a Headline.';
 * $data[0]['intro']      = 'Very short text.';
 * $data[0]['text']       = "And a long text.";
 * $data[1]['date']       = '2008-01-01';
 * $data[1]['headline']   = 'I am a second Headline.';
 * $data[1]['intro']      = 'Very short text.';
 * $data[1]['text']       = "And a long text.";
 *  
 * $view = Factory::load('Views\Csv');
 * $view->setContent('content', $data);
 * return $view;
 *
 * // ... Controller code
 * ~~~
 */
class Csv extends AbstractView {
	/**
	 * Fields get separated with this string.
	 * @var string $mimetype
	 */
	public $separator	= ';';

	/**
	 * The linebreak format used for output.
	 * @var string $mimetype
	 */
	public $linebreaks	= "\n";

	/**
	 * The character used to enclose the fields.
	 * @var string $mimetype
	 */
	public $delimiter 	= '"';

	/**
	 * Set to false if you do not want the field names as first row.
	 * @var boolean $mimetype
	 */
	public $table_header= true;
	
	/**
	 * You always have to define this method.
	 * @return  string  Should return the rendered content.
	 * @hidden
	 */
	public function getOutput() {
		// create stream handle for the output
		$handle = fopen('php://temp/maxmemory:'.(1*1024*1024), 'r+'); // 1MB

		foreach ($this->_content['content'] as $nr => $row) {
			// use first row for headlines
			if ($nr == 0 && $this->table_header === true) {
				fwrite($handle, $this -> _createRow(array_keys($row)));
			}

			fwrite($handle, $this -> _createRow($row));
		}

		return $handle;
	}

	/**
	 * Create one row in the CSV file.
	 * @param   array $input An array of the values.
	 * @return  string  Returns the resulting string.
	 */
	protected function _createRow($input) {
		foreach ($input as $key => $value) {
			$temp = str_replace('"', '""', $value);
			$temp = preg_replace("=(\r\n|\r|\n)=", "\n", $temp);
			$input[$key] = $this->delimiter.$temp.$this->delimiter;
		}
		$output = implode($this->separator, $input).$this->linebreaks;
		return $output;
	}
}

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
 * With this view handler it is possible to generate and output HTML files wich can be read from Microsoft Excel and OpenOffice as an spreadsheet.
 *
 * All public members of a view handler are changeable in the Controller by `\Morrow\View->setProperty($member, $value)`;
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
 * $this->view->setHandler('Excel');
 * $this->view->setContent('content', $data);
 *
 * // ... Controller code
 * ~~~
 */
class Excel extends AbstractView {
	/**
	 * Changes the standard mimetype of the view handler. Possible values are `text/html`, `application/xml` and so on.
	 * @var string $mimetype
	 */
	public $mimetype	= 'application/vnd.ms-excel';

	/**
	 * Set to false if you do not want the field names as first row.
	 * @var boolean $mimetype
	 */
	public $table_header= true;

	/**
	 * You always have to define this method.
	 * @param   array $content Parameters that were passed to \Morrow\View->setContent().
	 * @param   handle $handle  The stream handle you have to write your created content to.
	 * @return  string  Should return the rendered content.
	 * @hidden
	 */
	public function getOutput($content, $handle) {
		fwrite($handle, '<html><head><title>Excel</title><meta http-equiv="Content-Type" content="'.$this->mimetype.'; charset='.$this->charset.'"></head><body>');
		$this->_output($content['content'], $handle);
		fwrite($handle, '</body></html>');
		
		return $handle;
	}

	/**
	 * Renders the table with the data.
	 * @param   array $input Parameters that were passed to \Morrow\View->setContent().
	 * @param   handle $handle  The stream handle you have to write your created content to.
	 * @return  string  Should return the rendered content.
	 */
	protected function _output($input, $handle) {
		fwrite($handle, '<table cellpadding="0" cellspacing="0" border="0">');
		foreach ($input as $nr => $row) {

			if ($nr == 0 && $this->table_header === true) {
				fwrite($handle, '<tr>');
				$header = array_keys($row);
				foreach ($header as $key) {
					$key = htmlspecialchars($key);
					$key = preg_replace("=(\r\n|\r|\n)=i", '<br />', $key);
					fwrite($handle, '<th><font face="Arial">'.$key.'</font></th>');
				}
				fwrite($handle, '</tr>');
			}

			fwrite($handle, '<tr>');
			foreach ($row as $value) {
				$value = htmlspecialchars($value);
				$value = preg_replace("=(\r\n|\r|\n)=i", '<br />', $value);
				//$output .= '<td style="mso-number-format:\@" SDVAL="'.$value.'" SDNUM="1033;0;@"><font face="Arial">'.$value.'</font></td>';
				fwrite($handle, '<td><font face="Arial">'.$value.'</font></td>');
			}
			fwrite($handle, '</tr>');
		}
		fwrite($handle, '</table>');
	}
}

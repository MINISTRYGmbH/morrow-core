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
 * With this view handler it is possible to generate valid JSON responses.
 * 
 * The most accentuated difference to XML is the more compact representation of data structures what results in less traffic overhead. For more information on JSON, visit [http://www.json.org](http://www.json.org).
 *
 * Example
 * --------
 * 
 * ~~~{.php}
 * $data['frame']['section 1']['headline']  = 'Example';
 * $data['frame']['section 2']['copy']      = 'Example text';
 * $data['frame'][0]['headline']            = 'Example';
 * $data['frame'][0]['copy']                = 'Example text';
 * $data['frame']['section2']['copy1']      = 'This is a "<a>-link</a>';
 * $data['frame'][':section2']['param_key'] = 'param_value';
 * $content['content'] = $data;
 *  
 * $view = Factory::load('Views\Json');
 * $view->setContent('content', $data);
 * return $view;
 * ~~~
 */
class Json extends AbstractView {
	/**
	 * You always have to define this method.
	 * @return  string  Should return the rendered content.
	 * @hidden
	 */
	public function getOutput() {
		// create stream handle for the output
		$handle = fopen('php://temp/maxmemory:'.(1*1024*1024), 'r+'); // 1MB

		fwrite($handle, json_encode($this->_content['content']));
		return $handle;
	}
}

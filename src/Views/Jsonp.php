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
 * With this view handler it is possible to generate valid JSONP responses.
 * 
 * JSONP is a common technique used in Javascript to request data from a server in a different domain. This is usually prohibited by web browsers because of the same-origin policy.
 *
 * All public members of a view handler are changeable in the Controller by `\Morrow\View->setProperty($member, $value)`;
 *
 * Example
 * --------
 * 
 * ~~~{.php}
 * // ... Controller code
 * 
 * $data['frame']['section 1']['headline']  = 'Example';
 * $data['frame']['section 2']['copy']      = 'Example text';
 * $data['frame'][0]['headline']            = 'Example';
 * $data['frame'][0]['copy']                = 'Example text';
 * $data['frame']['section2']['copy1']      = 'This is a "<a>-link</a>';
 * $data['frame'][':section2']['param_key'] = 'param_value';
 * $content['content'] = $data;
 *  
 * $this->view->setHandler('Jsonp');
 * $this->view->setContent('content', $data);
 *
 * // ... Controller code
 * ~~~
 */
class Jsonp extends AbstractView {
	/**
	 * Changes the standard mimetype of the view handler. Possible values are `text/html`, `application/xml` and so on.
	 * @var string $mimetype
	 */
	public $mimetype = 'application/javascript';

	/**
	 * The name of the callback function that get passed the result as parameter. Default is `$_REQUEST['callback']` what it makes compatible with jQuery without a change.
	 * @var string $callback
	 */
	public $callback;

	/**
	 * You always have to define this method.
	 * @param   array $content Parameters that were passed to \Morrow\View->setContent().
	 * @param   handle $handle  The stream handle you have to write your created content to.
	 * @return  string  Should return the rendered content.
	 * @hidden
	 */
	public function getOutput($content, $handle) {
		if (is_null($this->callback)) $this->callback = $_REQUEST['callback'];

		fwrite($handle, $this->callback . '(');
		fwrite($handle, json_encode($content['content']));
		fwrite($handle, ');');
		return $handle;
	}
}

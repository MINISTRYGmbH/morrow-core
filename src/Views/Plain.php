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
 * With this view handler it is possible to output single strings.
 * 
 * Useful if you create data by hand and want just to display it. The input has to be a scalar variable or a stream.
 * You don't have to pass content, it is optional. This is useful if you write CLI scripts for the \Morrow\MessageQueue and you don't need any output.
 *
 * All public members of a view handler are changeable in the Controller by `\Morrow\View->setProperty($member, $value)`;
 *
 * Example
 * --------
 * 
 * ~~~{.php}
 * // ... Controller code
 * 
 * // Output just a string
 * $this->view->setHandler('Plain');
 * $this->view->setContent('content', 'Hello World!');
 *
 * // ... Controller code
 * ~~~
 * 
 * ~~~{.php}
 * // ... Controller code
 * 
 * // Output an image with the plain handler
 * $data = file_get_contents('testimage.jpg');
 * $this->view->setHandler('Plain');
 * $this->view->setProperty('mimetype', 'image/jpg');
 * $this->view->setContent('content', $data);
 *
 * // ... Controller code
 * ~~~
 */
class Plain extends AbstractView {
	/**
	 * Changes the standard mimetype of the view handler. Possible values are `text/html`, `application/xml` and so on.
	 * @var string $mimetype
	 */
	public $mimetype	= 'text/plain';

	/**
	 * You always have to define this method.
	 * @param   array $content Parameters that were passed to \Morrow\View->setContent().
	 * @param   handle $handle  The stream handle you have to write your created content to.
	 * @return  string  Should return the rendered content.
	 * @hidden
	 */
	public function getOutput($content, $handle) {
		if (!isset($content['content'])) return $handle;
		$content = $content['content'];
		
		if (is_resource($content) && get_resource_type($content) == 'stream') {
			// close the old handle
			fclose($handle);
			return $content;
		}
		
		if (!is_scalar($content)) {
			throw new \Exception(__CLASS__.': The content variable for this handler has to be scalar or a resource of type "stream".');
		}
		fwrite($handle, $content);
		return $handle;
	}
}

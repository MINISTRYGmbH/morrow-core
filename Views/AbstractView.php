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
 * You should extend this abstract class if you are writing your own filter.
 */
abstract class AbstractView {
	/**
	 * Changes the standard mimetype of the view handler. Possible values are `text/html`, `application/xml` and so on.
	 * @var string $mimetype
	 */
	public $mimetype		= 'text/html';

	/**
	 * Changes the standard charset of the view handler. Possible values are `utf-8`, `iso-8859-1` and so on.
	 * @var string $charset
	 */
	public $charset			= 'utf-8';

	/**
	 * Changes the http header so that the output is offered as a download. `$downloadable` defines the filename which is presented to the user. The mimetype will be automatically determined via file extension. It you specify a different mimetype, that will be used.
	 * @var string $downloadable
	 */
	public $downloadable	= '';

	/**
	 * You always have to define this method.
	 * @param   mixed $content Parameters that were passed to \Morrow\View->setContent().
	 * @param   handle $handle  The stream handle you have to write your created content to.
	 * @return  string  Should return the rendered content.
	 */
	abstract public function getOutput($content, $handle);
}

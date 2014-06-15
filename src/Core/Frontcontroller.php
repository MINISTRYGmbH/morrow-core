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


namespace Morrow\Core;

use Morrow\Factory;

/**
 * The main class which defines the cycle of a request.
 */
class Frontcontroller {
	/**
	 * This function contains the main application flow.
	 * @hidden
	 */
	public function __construct($alias, $root_path, $from_bootstrap = true) {
		/* load all config files
		********************************************************************************************/
		$this->config	= Factory::load('Config');
		$config = $this->config->load($root_path . 'configs/');

		/* prepare some internal variables
		********************************************************************************************/
		$controller_file		= $root_path .'_default.php';
		$page_controller_file	= $root_path . $alias .'.php';
		
		/* load classes we need anyway
		********************************************************************************************/
		if ($from_bootstrap) {
 			$this->view	= Factory::load('View');
 		} else {
 			$this->view	= Factory::load('View:view_' . md5($root_path));
 		}
		
		/* load controller and render page
		********************************************************************************************/
		// include global controller class
		include($controller_file);

		// include page controller class
		if (is_file($page_controller_file)) {
			include($page_controller_file);
			$controller = new \App\PageController();
			if (method_exists($controller, 'setup')) $controller->setup();
			$controller->run();
			if (method_exists($controller, 'teardown')) $controller->teardown();
		} else {
			$controller = new \App\DefaultController();
			if (method_exists($controller, 'setup')) $controller->setup();
			if (method_exists($controller, 'teardown')) $controller->teardown();
		}

		return $this->view->get();
	}
}

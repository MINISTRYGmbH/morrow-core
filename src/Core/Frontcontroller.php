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
use Morrow\Debug;

/**
 * The main class which defines the cycle of a request.
 */
class Frontcontroller {
	public function run($namespace, $classname, $master) {

		$root_path			= trim(str_replace('\\', '/', $namespace), '/') . '/';
		$root_path_absolute	= realpath('../' . trim(str_replace('\\', '/', $namespace), '/')) . '/';

		/* load config
		********************************************************************************************/
		// add config of features to master config
		if (!$master) {
			$config_path	= strtolower(str_replace('/', '.', trim($root_path, '/')));
			$config			= Factory::load('Config')->load($root_path_absolute . 'configs/', $config_path);
		}

		/* load view
		********************************************************************************************/
		$view = Factory::load($master ? 'View' : 'View:view-feature');
		$view->setHandler('serpent');
		$view->setProperty('template_path', $root_path_absolute . 'templates/');
		$view->setProperty('template', $classname);
		$view->setContent('page', Factory::load('Page')->get(), true);

		/* load controller
		********************************************************************************************/
		$class		= $namespace . $classname;
		$controller	= new $class;
		$controller->run();

		$view_data = $view->get();
		
		$features_path = $root_path_absolute . 'Features/features.php';
		if (!is_file($features_path)) return $view_data;

		/* load features
		********************************************************************************************/
		$alias		= Factory::load('Page')->get('alias');
		$feature	= Factory::load('Features', $features_path, ucfirst($alias));
		$view_data	= $feature->run($view_data);
		return $view_data;
	}
}

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
 * This class handles a MVC triade.
 * 
 * It is heavily used by the framework to allow every \Morrow\Core\Feature to be executed as single MVC triade.
 */
class Feature {
	/**
	 * Executes a MVC triade.
	 * @param  string $class The controller class name which should be executed.
	 * @param  boolean $master Is set to `true` if this is the top level triade.
	 * @param  instance $dom An instance of the \Morrow\DOM class. It will be passed to the controller `run()`, so features are able to modify the generated HTML source.
	 * @return stream Returns the generated content stream.
	 */
	public function run($class, $config_overwrite = array(), $master = false, $dom = null, $config = null) {
		$namespace			= explode('\\', $class);
		$classname			= array_pop($namespace);
		$namespace			= implode('\\', $namespace);
		$root_path			= trim(str_replace('\\', '/', $namespace), '/') . '/';
		$root_path_absolute	= realpath('../' . trim(str_replace('\\', '/', $namespace), '/')) . '/';
		
		/* load config
		********************************************************************************************/
		// add config of features to master config
		if (!$master) {
			$config			= Factory::load($master ? 'Config': 'Config:feature');
			$config->clear();
			$config->load($root_path_absolute . 'configs/');

			foreach ($config_overwrite as $key => $value) {
				$config->set($key, $value);
			}
		}

		/* load view
		********************************************************************************************/
		$view = Factory::load($master ? 'View' : 'View:feature');
		$view->setHandler('serpent');
		$view->setProperty('template_path', $root_path_absolute . 'templates/');
		$view->setProperty('template', $classname);
		$view->setContent('page', Factory::load('Page')->get(), true);

		/* load controller
		********************************************************************************************/
		$controller	= new $class;
		$controller->run($dom);

		$handle = $view->get();
		
		/* load features
		********************************************************************************************/
		$features_path	= $root_path_absolute . 'features/features.php';
		if (is_file($features_path)) {
			$nodes		= Factory::load('Page')->get('nodes');
			$feature	= Factory::load('Core\Features', include($features_path), $nodes);
			$handle		= $feature->run($handle);
		}

		/* trigger an event so others are able to modify the generated content at the end
		********************************************************************************************/
		if ($master) {
			$handle = Factory::load('Event')->trigger('core.after_view_creation', $handle);
		}


		return $handle;
	}
}

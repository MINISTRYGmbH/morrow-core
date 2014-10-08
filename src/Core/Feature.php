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
 * This class handles a MVC triad.
 * 
 * It is heavily used by the framework to allow every \Morrow\Core\Features to be executed as single MVC triad.
 */
class Feature {
	/**
	 * Executes a MVC triad.
	 * @param  string $class The controller class name which should be executed.
	 * @param  array $config_overwrite Overwrites single config parameters of a feature.
	 * @param  boolean $master Is set to `true` if this is the top level triad.
	 * @param  instance $dom An instance of the \Morrow\DOM class. It will be passed to the controller `run()`, so features are able to modify the generated HTML source.
	 * @return stream Returns the generated content stream.
	 */
	public function run($class, $config_overwrite = array(), $master = false, $dom = null) {
		$namespace			= explode('\\', $class);
		$classname			= array_pop($namespace);
		$namespace			= implode('\\', $namespace);
		$root_path			= trim(str_replace('\\', '/', $namespace), '/') . '/';
		$root_path_absolute	= realpath('../' . trim(str_replace('\\', '/', $namespace), '/')) . '/';
		$page				= Factory::load('Page')->get();
		
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
		$view = Factory::load($master ? 'Views\Serpent' : 'Views\Serpent:feature');
		$view->template_path	= $root_path_absolute . 'templates/';
		$view->template			= $classname;

		/* load controller
		********************************************************************************************/
		// A missing controller will result in an empty page
		$controller	= new $class;

		if (isset($controller)) {
			$view				= $controller->run($dom);
			$is_returning_html	= false;

			if (is_resource($view) && get_resource_type($view) == 'stream') {
				$handle = $view;
			} elseif (is_subclass_of($view, '\Morrow\Views\AbstractView')) {
				$handle				= $view->getOutput();
				$is_returning_html	= $view->is_returning_html;
			} elseif (is_string($view)) {
				$handle = fopen('php://temp/maxmemory:'.(1*1024*1024), 'r+'); // 1MB
				fwrite($handle, $view);
			} else {
				throw new \Exception(__CLASS__.': The return value of a controller has to of type "stream", "string" or has to be a child of \Morrow\Views\AbstractView.');
			}
		}
		
		/* load features
		********************************************************************************************/
		$features_path	= $root_path_absolute . 'features/features.php';
		if ($is_returning_html && is_file($features_path)) {
			$feature	= Factory::load('Core\Features', include($features_path), $page['alias']);
			$handle		= $feature->run($handle);
		}

		/* trigger an event so others are able to modify the generated content at the end
		********************************************************************************************/
		if ($master) {
			$handle = Factory::load('Event')->trigger('core.after_view_creation', $handle);
		}

		rewind($handle);
		return $handle;
	}
}

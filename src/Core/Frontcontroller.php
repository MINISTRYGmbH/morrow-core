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
	 * Will be set by the Constructor as default error handler, and throws an exception to normalize the handling of errors and exceptions.
	 *
	 * @param	int $errno Contains the level of the error raised, as an integer.
	 * @param	string $errstr Contains the error message, as a string.
	 * @param	string $errfile The third parameter is optional, errfile, which contains the filename that the error was raised in, as a string.
	 * @param	string $errline The fourth parameter is optional, errline, which contains the line number the error was raised at, as an integer.
	 * @return	null
	 * @hidden
	 */
	public function errorHandler($errno, $errstr, $errfile, $errline) {
		// get actual error_reporting
		$error_reporting = error_reporting();

		// request for @ error-control operator
		if ($error_reporting == 0) return;

		// return if error should not get processed
		if (($errno & $error_reporting) === 0) return;

		throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
	}

	/**
	 * Will be set by the Constructor as global exception handler.
	 * @param	object	$exception	The thrown exception.
	 * @return null
	 * @hidden
	 */
	public function exceptionHandler($exception) {
		try {
			// load errorhandler
			$debug = Factory::load('Debug');
			$debug->errorhandler($exception);
		} catch (\Exception $e) {
			echo "<pre>$exception</pre>\n\n";

			// useful if the \Exception handler itself contains errors
			echo "<pre>The Debug class threw an exception:\n$e</pre>";
		}
	}

	/**
	 * This function contains the main application flow.
	 * @hidden
	 */
	public function __construct() {
		/* global settings
		********************************************************************************************/
		// compress the output
		if (!ob_start("ob_gzhandler")) ob_start();

		// include E_STRICT in error_reporting
		error_reporting(E_ALL | E_STRICT);

		/* declare errorhandler (needs config class)
		********************************************************************************************/
		set_error_handler(array($this, 'errorHandler'));
		set_exception_handler(array($this, 'exceptionHandler'));

		/* load all config files
		********************************************************************************************/
		$this->config	= Factory::load('Config');
		$config = $this->config->load(APP_PATH . 'configs/');

		/* extract important variables
		********************************************************************************************/
		// map cli parameters to $_GET
		if (php_sapi_name() === 'cli') {
			global $argc, $argv;
			if (isset($argv[2])) parse_str($argv[2], $_GET);
			$_GET['morrow_path_info'] = isset($argv[1]) ? $argv[1] : '';
		}

		$morrow_path_info	= $_GET['morrow_path_info'];
		$basehref_depth		= isset($_GET['morrow_basehref_depth']) ? $_GET['morrow_basehref_depth'] : 0;
		unset($_GET['morrow_path_info']);
		unset($_GET['morrow_basehref_depth']);

		/* load some necessary classes
		********************************************************************************************/
		$this->input	= Factory::load('Input');
		$this->page		= Factory::load('Page');

		/* set timezone 
		********************************************************************************************/
		if (!date_default_timezone_set($config['locale']['timezone'])) {
			throw new \Exception(__METHOD__.'<br>date_default_timezone_set() failed.');
		}

		/* load page class and set nodes
		********************************************************************************************/
		$url		= $morrow_path_info;
		$url		= (preg_match('~[a-z0-9\-/]~i', $url)) ? trim($url, '/') : '';
		$nodes		= explode('/', $url);
		$this->page->set('nodes', $nodes);

		/* load languageClass and define alias
		********************************************************************************************/
		$lang['possible']		= $config['languages'];
		$lang['language_path']	= APP_PATH .'languages/';
		$lang['search_paths']	= array(
			APP_PATH			.'templates/*',
			APP_PATH			.'*.php'
		);
		$this->language = Factory::load('Language', $lang);

		// language via path
		if (isset($nodes[0]) && $this->language->isValid($nodes[0])) {
			$input_lang_nodes = array_shift($nodes);
			$this->page->set('nodes', $nodes);
		}
		
		// language via input
		$lang['actual'] = $this->input->get('language');

		if ($lang['actual'] === null && isset($input_lang_nodes)) {
			$lang['actual'] = $input_lang_nodes;
		}

		if ($lang['actual'] !== null) $this->language->set($lang['actual']);

		/* url routing
		********************************************************************************************/
		$routes	= $config['routing'];
		$url	= implode('/', $this->page->get('nodes'));

		// iterate all rules
		foreach ($routes as $rule => $new_url) {
			$rule		= trim($rule, '/');
			$new_url	= trim($new_url, '/');
			$regex		= '=^'.$rule.'$=';

			// rebuild route to a preg pattern
			if (preg_match($regex, $url, $matches)) {
				$url = preg_replace($regex, $new_url, $url);
				unset($matches[0]);
				foreach ($matches as $key => $value) {
					$this->input->set('routed.' . $key, $value);	
				}
			}
		}

		// set nodes in page class
		$nodes = explode('/', $url);
		$nodes = array_map('strtolower', $nodes);

		/* prepare some internal variables
		********************************************************************************************/
		$alias					= implode('_', $nodes);
		$controller_file	= APP_PATH .'_default.php';
		$page_controller_file	= APP_PATH . $alias .'.php';
		$path					= implode('/', $this->page->get('nodes'));
		$query					= $this->input->getGet();
		$fullpath				= $path . (count($query) > 0 ? '?' . http_build_query($query, '', '&') : '');
		
		/* load classes we need anyway
		********************************************************************************************/
		$this->view	= Factory::load('View');
		$this->url	= Factory::load('Url', $this->language->get(), $lang['possible'], $fullpath, $basehref_depth);
		
		/* prepare classes so the user has less to pass
		********************************************************************************************/
		Factory::prepare('Cache', STORAGE_PATH .'codecache/');
		Factory::prepare('Db', $config['db']);
		Factory::prepare('Debug', $config['debug']);
		Factory::prepare('Image', PUBLIC_STORAGE_PATH . 'thumbs/');
		Factory::prepare('Log', $config['log']);
		Factory::prepare('MessageQueue', $config['messagequeue'], $this->input);
		Factory::prepare('Navigation', Factory::load('Language')->getTree(), $alias);
		Factory::prepare('Pagesession', 'page.' . $alias);
		Factory::prepare('Session', $config['session']);
		Factory::prepare('Security', new Factory('Session'), $this->view, $this->input, $this->url);

		/* define page params
		********************************************************************************************/
		$base_href = $this->url->getBaseHref();
		$this->page->set('base_href', $base_href);
		$this->page->set('alias', $alias);
		$this->page->set('path.relative', $path);
		$this->page->set('path.relative_with_query', $fullpath);
		$this->page->set('path.absolute', $base_href . $path);
		$this->page->set('path.absolute_with_query', $base_href . $fullpath);

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

		// assign the content to the view
		$this->view->setContent('page', $this->page->get());

		$view		= $this->view->get();
		$headers	= $view['headers'];
		$handle		= $view['content'];
		
		// output headers
		foreach ($headers as $h) header($h);
		
		rewind($handle);
		fpassthru($handle);
		fclose($handle);

		ob_end_flush();
	}
}

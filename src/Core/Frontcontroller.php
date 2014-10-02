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


namespace Morrow;

// Define paths for the Morrow namespace
// Because this file is in the Core namespace we have to use a temporary namespace 
define('PUBLIC_PATH', getcwd() . '/');
define('PUBLIC_STORAGE_PATH', PUBLIC_PATH . 'storage/');
define('APP_PATH', realpath(PUBLIC_PATH . '../app/') . '/');
define('STORAGE_PATH', APP_PATH . 'storage/');
define('VENDOR_PATH', PUBLIC_PATH . '../vendor/');


namespace Morrow\Core;

use Morrow\Factory;

/**
 * This class is the entry point to the Morrow framework.
 * 
 * It does some necessary configuration like setting of the top level exception handler, preparing of classes, url routing ...
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
		$config = Factory::load('Config')->load(APP_PATH . 'configs/');

		/* set timezone 
		********************************************************************************************/
		if (!date_default_timezone_set($config['locale']['timezone'])) {
			throw new \Exception(__METHOD__.'<br>date_default_timezone_set() failed. Timezone not valid.');
		}

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
		$input	= Factory::load('Input');
		$page	= Factory::load('Page');

		/* set nodes
		********************************************************************************************/
		$url	= (preg_match('~[a-z0-9\-/]~i', $morrow_path_info)) ? trim($morrow_path_info, '/') : '';
		$nodes	= explode('/', $url);

		/* load languageClass and define alias
		********************************************************************************************/
		$language = Factory::load('Language', $config['languages']);

		// language via path
		if (isset($nodes[0]) && $language->isValid($nodes[0])) {
			$input_lang_nodes = array_shift($nodes);
		}
		
		// language via input
		$actual = $input->get('language');

		if ($actual === null && isset($input_lang_nodes)) {
			$actual = $input_lang_nodes;
		}

		if ($actual !== null) $language->set($actual);

		/* url routing
		********************************************************************************************/
		$routes	= $config['routing'];

		// iterate all rules
		foreach ($routes as $rule => $new_url) {
			$rule		= trim($rule, '/');
			$new_url	= trim($new_url, '/');
			$regex		= '=^'.$rule.'$=';

			// rebuild route to a preg pattern
			if (preg_match($regex, $url, $matches)) {
				$url = trim(preg_replace($regex, $new_url, $url), '/');
				unset($matches[0]);
				foreach ($matches as $key => $value) {
					$input->set('routed.' . $key, $value);	
				}
			}
		}

		// set nodes in page class
		$routed_nodes = explode('/', $url);
		$routed_nodes = array_map('strtolower', $routed_nodes);
				
		/* prepare some internal variables
		********************************************************************************************/
		$alias		= str_replace('-', '', implode('_', $routed_nodes));
		$path		= implode('/', $nodes);
		$query		= $input->getGet();
		$fullpath	= $path . (count($query) > 0 ? '?' . http_build_query($query, '', '&') : '');
		
		/* prepare classes so the user has less to pass
		********************************************************************************************/
		Factory::prepare('Cache', $config['cache']['save_path']);
		Factory::prepare('Db', $config['db']);
		Factory::prepare('Debug', $config['debug'], new Factory('Event'));
		Factory::prepare('Image', $config['image']['save_path']);
		Factory::prepare('Log', $config['log']);
		Factory::prepare('MessageQueue', $config['messagequeue'], $input);
		Factory::prepare('Navigation', Factory::load('Language')->getTree(), $alias);
		Factory::prepare('Pagesession', 'pagesession.' . $alias, $config['session']);
		Factory::prepare('Session', 'main', $config['session']);

		/* load classes we need anyway
		********************************************************************************************/
		$url		= Factory::load('Url', $language->get(), $config['languages']['possible'], $fullpath, $basehref_depth);
		$header		= Factory::load('Header');
		$view		= Factory::load('View');
		$security	= Factory::load('Security', $config['security'], $header, $url, $input->get('csrf_token'));
		
		/* define page params
		********************************************************************************************/
		$base_href = $url->getBaseHref();
		$page->set('nodes', $nodes);
		$page->set('base_href', $base_href);
		$page->set('alias', $alias);
		$page->set('path.relative', $path);
		$page->set('path.relative_with_query', $fullpath);
		$page->set('path.absolute', $base_href . $path);
		$page->set('path.absolute_with_query', $base_href . $fullpath);

		/* process MVC
		********************************************************************************************/
		$handle	= (new \Morrow\Core\Feature)->run('\\app\\' . ucfirst(strtolower($alias)), array(), true);
		
		// output headers
		$handler	= $view->getDisplayHandler();
		$headers	= $header->get($handle, $handler->mimetype, $handler->charset);
		foreach ($headers as $h) header($h);
		
		rewind($handle);
		fpassthru($handle);
		fclose($handle);

		ob_end_flush();
	}
}

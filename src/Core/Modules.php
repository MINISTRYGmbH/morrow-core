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
 * This class executes all defined Features.
 *
 * It is heavily used by the framework to allow every \Morrow\Core\Features to be executed as single MVC triad.
 */
class Modules {

	private $_dom;
	private $_global_config;
	private $_module_queue;

	/**
	 * Executes all defined modules as MVC-triads.
	 * @param  string $class_name The controller class name which should be executed.
	 * @return stream Returns the generated content stream.
	 */
	public function run($class_name){
		$this->_global_config = Factory::load('Config');
		$this->_rel_path = Factory::load('Page')->get('path.relative');

		/* get modules config array: '$modules'
		********************************************************************************************/
		$modules_path = $this->_global_config->get('modules._config_path');
		if(is_file($modules_path)){
			$modules_config_array = include($modules_path);
		}

		/* transform module config array structure into the module queue array
		 * insert main module between 'pre' and 'post' modules
		********************************************************************************************/
		$temp = [];
		foreach($modules_config_array as $regex => $page_modules){
			if(isset($page_modules['pre'])){
				foreach($page_modules['pre'] as $page_module){
					$temp[] = array_merge($page_module, ['controller_regex' => $regex]);
				}
			}
		}
		$temp[] = [
			'action'           => 'replace',
			'class'            => $class_name,
			'controller_regex' => '~.*~',
			'selector'         => 'html'
		];
		foreach($modules_config_array as $regex => $page_modules){
			if(isset($page_modules['post'])){
				foreach($page_modules['post'] as $page_module){
					$temp[] = array_merge($page_module, ['controller_regex' => $regex]);
				}
			}
		}
		$this->_module_queue = $temp;

		/* put module configs into global config
		********************************************************************************************/
		foreach($this->_module_queue as $module){
			$this->_insertModuleConfig($module['class'], isset($module['config']) ? $module['config'] : []);
		}


		/* remove modules from the module queue array that wont be executed
		********************************************************************************************/
		foreach($this->_module_queue as $key => $module){
			if(!preg_match($module['controller_regex'], $this->_rel_path)){
				unset($this->_module_queue[$key]);
			}
		}


		/* execute module queue
		********************************************************************************************/
		$this->_dom = new \Morrow\DOM;
		$this->_dom->set('<!doctype html><html></html>');
		foreach($this->_module_queue as $key => &$module){
			// remove this item from queue
			unset($this->_module_queue[$key]);
			// execute module
			$handle = $this->runFeature($module);
			// close returned handle
			if(is_resource($handle)){
				fclose($handle);
			}
		}
		$handle_full_content = fopen('php://memory', 'r+');
		fwrite($handle_full_content, $this->_dom->get());


		/* trigger an event so others are able to modify the generated content at the end
		********************************************************************************************/
		$handle_full_content = Factory::load('Event')->trigger('core.after_view_creation', $handle_full_content);

		// return handle
		rewind($handle_full_content);
		return $handle_full_content;
	}

	/**
	 * Get function for the module queue member
	 * @return	array	the module queue member
	 */
	public function getQueue(){
		return $this->_module_queue;
	}

	/**
	 * Removes a module from the module queue either by key or by a regex
	 * matched against the module controller namespace.
	 * @param 	string	$key_or_regex	The numeric key of the queue item or a regex matched against the controller namespace
	 * @return 	array					An array containing all removed queue items
	 */
	public function removeQueueItem($key_or_regex){
		$removed_queue_items = [];

		// user passed the numeric key of queue item
		if(is_numeric($key_or_regex)){
			$removed_queue_items[] = $this->_module_queue[$key_or_regex];
			unset($this->_module_queue[$key_or_regex]);
		// user passed a regex that will be matched against the module namespace
		}else{
			foreach($this->_module_queue as $key => $queueItem){
				if(preg_match($key_or_regex, $queueItem['class'])){
					$removed_queue_items[] = $this->_module_queue[$key];
					unset($this->_module_queue[$key]);
				}
			}
		}
		return $removed_queue_items;
	}

	/**
	 * Inserts a module's config params into the global config instance.
	 * @param  	string	$namespace	namespace of called module controller
	 * @param  	array	$overwrite	config overwrite array
	 */
	private function _insertModuleConfig($namespace, $overwrite){
		// get module name from namespace
		$namespace_array = explode('\\', trim($namespace,'\\'));
		$path_array = array_slice($namespace_array, 1, 2);
		$module_config_path = ROOT_PATH . implode('/', $path_array) . '/configs/';
		$module_config_path = str_replace('\\', '/', $module_config_path);
		$module_name = $path_array[1];

		// create module config instance and load default params
		$module_config = Factory::load('Config:' . $module_name);
		$module_config->load($module_config_path);

		// overwrite default params with specified params in modules.php
		foreach($overwrite as $key => $value){
			$module_config->set($key, $value);
		}

		// insert into modules global config
		$this->_global_config->set('modules.' . $module_name, $module_config->get());
	}

	/**
	 * Execute any module.
	 * @param	array	$page_module	the module config array:
	 *                           		[
	 *                             			'action'   => 'append'|'replace'|'prepend', (optional)
	 *                                		'class'    => '\\app\\modules\\Foo\\Bar',
	 *			                            'config'   => ['anyConfigKey' => 'anyConfigValue'], (optional)
	 *			                            'selector' => '#anyCssSelector', (optional)
	 *                           		]
	 * @return 	stream	$handle			handle of executed module data
	 */
	public function runFeature($page_module){
		// execute module controller
		$view = (new $page_module['class'])->run($this->_dom);

		// set the handle variable according to this module's controller return value
		if(is_resource($view) && get_resource_type($view) == 'stream'){
			$handle = $view;
		}elseif(is_object($view) && is_subclass_of($view, '\Morrow\Views\AbstractView')){
			$view->init($page_module['class']);
			$handle = $view->getOutput();
		}elseif(is_string($view)) {
			$handle = fopen('php://temp/maxmemory:'.(1*1024*1024), 'r+'); // 1MB
			fwrite($handle, $view);
		}elseif(is_null($view)){
			return;
		}else{
			throw new \Exception(__CLASS__.': The return value of a controller has to be of type "stream", "string" or a child of \Morrow\Views\AbstractView.');
		}

		// if any action is availabe, put new content into DOM instance
		if(isset($page_module['action'])){
			rewind($handle);
			$this->_dom->{$page_module['action']}($page_module['selector'], stream_get_contents($handle));
		}

		rewind($handle);
		return $handle;
	}
}

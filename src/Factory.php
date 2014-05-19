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

/**
 * This class handles instances of classes (e.g. Singletons) and is heavily used by the Morrow framework internally.
 * 
 * The first parameter you always pass is an instance identifier which consists of a class name (case sensitive) and an instance name (case insensitive and optional) divided by a colon.
 * All following parameters will be passed to the constructor of the class.
 *
 * Handling multiple instances
 * ---------------------------
 * 
 * ~~~{.php}
 * // inits the class "Dummy" with the internal instance name "dummy" (left out because it is optional)
 * // Usually used for instantiating a Singleton.
 * $first_instance  = Factory::load('Dummy');
 *
 * // inits the class "Dummy" with the internal instance name "dummy1" and returns a new instance.
 * $second_instance = Factory::load('Dummy:dummy1');
 *
 * // inits the class "Dummy" with the internal instance name "dummy2" and passes constructor parameters.
 * $third_instance  = Factory::load('Dummy:dummy2', 'foo', 'bar');
 * ~~~
 *
 * Lazy loading
 * ------------
 * 
 * The Factory has the ability to prepare the initialization of an object with predefined constructor parameters.
 * This allows to lazyload classes while defining their constructor parameters at the beginning of the webpage lifecycle.
 * It also doesn't use resources if a class instance is not used.
 *
 * If the constructor doesn't need parameters you don't have to prepare its loading of course.
 * 
 * ~~~{.php}
 * // Deposit parameters for the instances which are passed when they are instantiated.
 * Factory::prepare('Dummy', 'parameter1');
 * Factory::prepare('Dummy:dummy1');
 * Factory::prepare('Dummy:dummy2', 'parameter2');
 *
 * // some other code ...
 *
 * // The instance "dummy2" was prepared but is not loaded. So the class isn't instantiated.
 * $dummy	= Factory::load('Dummy');
 * $dummy1	= Factory::load('Dummy:dummy1');
 * ~~~
 *
 * A typical real life example would be the initialization of a database connection.
 * You would define the connection in the DefaultController but use it in the PageController.
 *
 * ~~~{.php}
 * // in the GlobalController
 * Factory::prepare('Db', Factory::load('Config')->get('db'));
 *
 * // in the PageController
 * $query = Factory::load('Db')->result("
 *     SELECT * FROM table
 * ");
 * ~~~
 * 
 * PSR-0 standard and namespaces
 * -----------------------------
 *
 * The Morrow autoloader respects the [PSR-0 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md).
 * So it is e.g. possible to put the Zend Library into your PROJECT/_libs/ folder and use it out of the box as documented in the Zend documentation.
 *
 * ~~~{.php}
 * $figlet = new \Zend\Text\Figlet\Figlet();
 * $text = $figlet->render('Morrow');
 * ~~~
 *
 * Your advantage using the Factory is that you have access to this instance from everywhere.
 * 
 * ~~~{.php}
 * $figlet = Factory::load('\Zend\Text\Figlet\Figlet');
 * $text = $figlet->render('Morrow');
 * ~~~
 *
 * So as you can see the Factory does also take account of namespaces.
 * For Morrow classes you don't have to specify the full namespace because relative namespaces are supposed to be in the Morrow namespace.
 * 
 * ~~~{.php}
 * $instance  = Factory::load('Dummy');
 * // is the same as
 * $instance  = Factory::load('\Morrow\Dummy');
 * ~~~
 * 
 * Shortcuts by extending the Factory
 * ----------------------
 *
 * You can extend this class to gain handling advantages.
 * The Morrow DefaultController extends the Factory by default so the following examples will work in Morrow controller files.
 *
 * If you access a not defined class member it will be understood as you want to use the class with the same name. 
 * So it will get instantiated and returned. This way you can use a method of a class and instantiating it in one line.
 * 
 * ~~~{.php}
 * <?php
 *
 * namespace Morrow;
 *
 * class PageController extends DefaultController { // the DefaultController extends the Factory
 * 		public function run() {
 *     		// Controller code
 *
 * 			$result = $this->dummy->get();
 * 			
 * 			// Controller code
 * 		}
 * }
 * ~~~
 *
 * Preparation of parameters is also possible as known by the Factory examples above.
 * 
 * ~~~{.php}
 * // Controller code
 *
 * $this->prepare('Dummy:dummy2', 'foobar');
 * $result = $this->dummy2->get();
 * 			
 * // Controller code
 * ~~~
 *
 * Allows to create a lightweight proxy object which is useful for Dependency Injection.
 * --------------------------------------------------------------------------------------
 *
 * Create an instance of this class if you want to prepare a class A which depends on another class B, but you are not sure if you will use the class A.
 * The generated proxy object is really lightweight and this way you can save memory and initialization time.
 *
 * ### Example
 * We have a class A which depends on Class B
 *
 * ~~~{.php}
 * class Class A {
 *     public function __construct($class_b) {
 * 	       $class_b->get();
 *     }	
 * }
 * ~~~
 * 
 * #### The problem
 * We have to initialize Class B because we need it in the constructor of Class A.
 * Class A is prepared because we don't know if we will use it.
 * 
 * ~~~{.php}
 * $class_b = Factory::load('Class_B');
 * Factory::prepare('Class_A', $class_b);
 * ~~~
 *
 * #### The solution
 * We create the proxy object of Class B which is a very lightweight object.
 * In Class A we can use Class B as if we have really passed it.
 * 
 * ~~~{.php}
 * $class_b = new Factory('Class_B');
 * Factory::prepare('Class_A', $class_b);
 * ~~~
 */
class Factory {
	/**
	 * All instances of classes the Factory manages.
	 * @var array $_instances
	 */
	protected static $_instances;
	
	/**
	 * Holds the constructor parameters for use at initialization time of a class.
	 * @var array $_params
	 */
	protected static $_params;
	
	/**
	 * Holds the parameters which are later passed to the Factory.
	 * @var array $_parameters
	 */
	protected $_proxy_params;

	/**
	 * Pass the same parameters as you would do for Factory::load(). This creates the lightweight proxy object.
	 */
	public function __construct() {
		$this->_proxy_params = func_get_args();
	}

	/**
	 * Initializes a class with optionally prepared constructor parameters and returns the instance.
	 * @param	string	$instance_identifier The instance identifier.
	 * @return	object
	 */
	public static function load($instance_identifier) {
		$args = func_get_args();

		$instance_identifier	= array_shift($args);
		$factory_args			= $args;

		// get factory config
		$instance_identifier = explode(':', $instance_identifier);
		
		// create a fully namespaced class path
		$classname = $instance_identifier[0];
		if ($classname{0} != '\\') {
			$classname = '\\Morrow\\' . $classname;
		}

		//create the instancename we need to get possible parameters from prepare
		$instancename = (isset($instance_identifier[1])) ? $instance_identifier[1] : substr(strrchr($classname, '\\'), 1);
		if ($instancename == false) $instancename = $classname;
		$instancename = strtolower($instancename);

		// if there were no constructor parameters passed look for prepared parameters
		if (count($factory_args) === 0) {
			if (isset(self::$_params[$instancename])) {
				$classname = self::$_params[$instancename]['classname'];
				$factory_args = self::$_params[$instancename]['args'];
			} else {
				$factory_args = array();
			}
		}
		
		// if the instance was already instantiated return it, otherwise create it
		$instance =& self::$_instances[$instancename];
		
		if (isset($instance)) {
			if ($instance instanceof $classname) return $instance;
			else {
				throw new \Exception('instance "'.$instancename.'" already defined of class "'.get_class($instance).'"');
				return false;
			}
		}

		// create object
		if (empty($factory_args)) {
			$instance = new $classname;
		} else {
			// check the parameters for instances of class Factoryproxy
			$factory_args = array_map(function($param){
				if (!is_object($param) || get_class($param) !== 'Morrow\\Factory') return $param;
				return call_user_func_array('\\Morrow\\Factory::load', $param->_getProxyParameters());
			}, $factory_args);

			// use reflection class to inject the args as single parameters
			$ref = new \ReflectionClass($classname);
			$instance = $ref->newInstanceArgs($factory_args);
		}
		return $instance;
	}

	/**
	 * Handles the preparation of class instantiation by deposit the constructor parameters. That allows the lazy loading functionality.
	 * 
	 * @param	string	$instance_identifier The instance identifier.
	 * @param	mixed	$parameters Any number of constructor parameters.
	 * @return	null
	 */
	public static function prepare($instance_identifier, $parameters = null) {
		$args = func_get_args();
		
		// get instance name in params string
		$params = explode(':', $args[0]);
		$classname = $params[0];
		
		// we always have to create a fully namespaced class path
		if ($classname{0} !== '\\') {
			$classname = '\\Morrow\\' . $classname;
		}

		// use the instancename or the last part of the classname for saving the args
		$instancename = (isset($params[1])) ? $params[1] : substr(strrchr($classname, '\\'), 1);
		if ($instancename == false) $instancename = $classname;
		$instancename = strtolower($instancename);
		
		// save params for later
		self::$_params[$instancename] = array(
			'classname'	=> $classname,
			'args'		=> array_slice($args, 1),
		);
	}

	/**
	 * Allows to access and instantiating a class by accessing an object member.
	 * @param	string	$instance_identifier The instance identifier.
	 * @return	object  Returns the created instance.
	 */
	public function __get($instance_identifier) {
		$this->$instance_identifier = Factory::load(ucfirst($instance_identifier));
		return $this->$instance_identifier;
	}

	/**
	 * Returns the parameters which are later passed to the Factory (Used by the Factory).
	 * @return	null All the parameters which are later passed to the Factory.
	 */
	protected function _getProxyParameters() {
		return $this->_proxy_params;
	}
}

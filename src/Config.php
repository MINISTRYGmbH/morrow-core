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
* Handles the access to the framework configuration.
* 
* It automatically loads the config files in the configuration folder `APP_PATH/configs`. A file named `_default.php` will always be loaded. But you can override the default config with files which include either the IP address or the hostname of the server in their filename. Use for example a file `localhost.php` or `127.0.0.1.php` to override parameters for your local development server.
* You just have to include those parameters which differ from the defaults.
*
* Dot Syntax
* ----------
* 
* This class works with the extended dot syntax. So if you use keys like `mailer.host` and `mailer.smtp` as identifiers in your config, you can call `$this->config->get("mailer")` to receive an array with the keys `host` and `smtp`. 
* 
* Examples
* ---------
*
* ~~~{.php}
* // ... Controller code
*  
* // show full framework configuration
* Debug::dump($this->config->get());
* 
* // overwrite a debug parameter
* $this->config->set('debug.output.screen', false);
* 
* // get the debug output configuration
* $this->config->get('debug.output');
* 
* // ... Controller code
* ~~~
*/
class Config extends Core\Base {
	/**
	* The data array which does not have dotted keys anymore
	* @var array $data
	*/
	protected $data = array(); // The array with parsed data

	/**
	 * Retrieves configuration parameters. If `$identifier` is not passed, it returns an array with the complete configuration. Otherwise only the parameters below `$identifier`. 
	 * 
	 * @param string $identifier Config data to be retrieved
	 * @return mixed
	 */
	public function get($identifier = null) {
		return $this->arrayGet($this->data, $identifier);
	}

	/**
	 * Sets registered config parameters below $identifier. $value can be of type string or array. 
	 * 
	 * @param string $identifier Config data path to be set
	 * @param mixed $value The value to be set
	 * @return null
	 */
	public function set($identifier, $value) {
		return $this->arraySet($this->data, $identifier, $value);
	}
	
	/**
	 * Loads config files in an array.
	 * First it searches for a file _default.php then it tries to load the config for the current HOST and then for the Server IP address.
	 * @param	string	$directory	The directory path where the config files are.
	 * @return	array	An array with the config.
	 */
	public function load($directory) {
		// load main config
		$config = include ($directory.'_default.php');

		$file0 = $directory.'_default_app.php';
		if (is_file($file0)) $config = array_merge($config, include($file0));

		// overwrite with server specific config
		if (php_sapi_name() === 'cli') {
			$file1 = $directory.gethostname().'.php';
			if (is_file($file1)) $config = array_merge($config, include($file1));
		} else {
			$file1 = $directory.$_SERVER['HTTP_HOST'].'.php';
			$file2 = $directory.(isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR']).'.php'; // On Windows IIS 7 you must use $_SERVER['LOCAL_ADDR'] rather than $_SERVER['SERVER_ADDR'] to get the server's IP address.
			if (is_file($file1)) $config = array_merge($config, include($file1));
			elseif (is_file($file2)) $config = array_merge($config, include($file2));
		}

		foreach ($config as $key => $value) {
			$this->arraySet($this->data, $key, $value);
		}

		return $this->data;
	}
}

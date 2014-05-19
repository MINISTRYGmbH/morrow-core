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
 * This class is a simple solution to log various variables to a file for controlling issues.
 *
 * Example
 * --------
 * 
 * ~~~{.php}
 * // ... Controller code
 *
 * // variables to log
 * $integer = 1;
 * $string  = 'foobar';
 * $boolean = false;
 * $null    = null;
 * $array   = array('foo'=>'bar', 'foo2'=>'bar2');
 * $object  = new stdClass();
 *
 * // now log
 * $this->log->set($integer, $string, $boolean, $null, $array, $object);
 *
 * // ... Controller code
 * ~~~
 */
class Log {
	/**
	 * The path to the log file.
	 * @var string $_logfile
	 */
	protected $_logfile;

	/**
	 * Initializes the log class.
	 * @param	array	$config	All config parameters.
	 */
	public function __construct($config) {
		$this->_logfile = $config['file']['path'];

		// create save_path if it does not exist
		if (!is_dir(dirname($this->_logfile))) mkdir(dirname($this->_logfile));
	}

	/**
	 * Logs data to the log file.
	 * @param  mixed $args	 You can pass as many variables of any type as you want.
	 */
	public function set($args) {
		$data['args']				= func_get_args();
		$caller						= debug_backtrace();
		$data['caller']				= $caller[0];
		$data['formated_time']		= strftime('%Y-%m-%d %H:%M:%S', time());
		$data['formated_microtime']	= sprintf("%'03d", microtime()*1000);

		return $this->_getOutputPlain($data);
	}

	/**
	 * Formates the output to write it to the file.
	 * @param	array	$data	The data array produced by set().
	 * @return	string	The string that will be written to the logfile.
	 */
	protected function _getOutputPlain($data) {
		// create output string
		$output = 'date: '.$data['formated_time'].' .'.$data['formated_microtime']."\n";
		$output .= 'call: '.$data['caller']['file'].' (line '.$data['caller']['line'].')'."\n";

		foreach ($data['args'] as $arg) {
			$output .= '---'."\n";

			// get dump
			ob_start();
			var_dump($arg);
			$dump = ob_get_clean();
			
			$output .= $dump;
		}

		$output .= '=============================='."\n";

		$result =  file_put_contents($this->_logfile, $output, FILE_APPEND);
		if (is_integer($result)) return true;
		else return false;
	}
}

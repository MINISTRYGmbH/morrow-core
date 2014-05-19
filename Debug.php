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
* This class helps you to debug your application.
*
* You are able to change the behaviour of these methods with the following parameters you should set in your configuration files:
*
* Type   | Keyname                | Default    | Description                                                              
* -----  | ---------              | ---------  | ------------                                                             
* bool   | `debug.output.screen`  | `true`     | Defines if errors should be displayed on screen                          
* bool   | `debug.output.file`    | `true`     | Defines if errors should be logged to the file system
* string | `debug.file.path`      | `STORAGE_PATH .'logs/error_'. date('Y-m-d') .'.txt'` | Defines the path where to save the errors
*
* Examples
* ---------
*
* ~~~{.php}
* // ... Controller code
* 
* // set at the beginning of the DefaultController 
* $this->debug->setAfterException(function(){
*     $this->url->redirect('error/');	
* });
*  
* // ... Controller code
* ~~~ 
*/
class Debug {
	/**
	 * The path to the log file.
	 * @var string $_logfile
	 */
	protected $_logfile;

	/**
	 * The config object
	 * @var object $config
	 */
	protected $config;

	/**
	 * The last error which occured.
	 * @var string $lasterror
	 */
	protected $lasterror;

	/**
	 * An errorcounter to output the css for displaying the error only once
	 * @var int $errorcounter
	 */
	protected $errorcounter = 0;

	/**
	 * Contains all error types PHP is able to throw
	 * @var array $errortypes
	 */
	protected $errortypes;
	
	/**
	 * Stores the actual count of sent http debug headers
	 * @var int $x_debug_count
	 */
	protected $x_debug_count = 0; 
	
	/**
	 * Stores the closure function to execute after default exception handling
	 * @var	function $_after_exception
	 */
	protected $_after_exception = null;

	/**
	 * Initializes the class. This is done internally.
	 * @param	array	$config	All config parameters.
	 */
	public function __construct($config) {
		$this->_logfile	= $config['file']['path'];

		// create save_path if it does not exist
		if (!is_dir(dirname($this->_logfile))) mkdir(dirname($this->_logfile));

		$this->config	= $config;

		// error types
		$this->errortypes[1]		= 'E_ERROR';
		$this->errortypes[2]		= 'E_WARNING';
		$this->errortypes[4]		= 'E_PARSE';
		$this->errortypes[8]		= 'E_NOTICE';
		$this->errortypes[16]		= 'E_CORE_ERROR';
		$this->errortypes[32]		= 'E_CORE_WARNING';
		$this->errortypes[64]		= 'E_COMPILE_ERROR';
		$this->errortypes[128]		= 'E_COMPILE_WARNING';
		$this->errortypes[256]		= 'E_USER_ERROR';
		$this->errortypes[512]		= 'E_USER_WARNING';
		$this->errortypes[1024]		= 'E_USER_NOTICE';
		$this->errortypes[2048]		= 'E_STRICT';
		$this->errortypes[4096]		= 'E_RECOVERABLE_ERROR';
		$this->errortypes[8192]		= 'E_DEPRECATED';
		$this->errortypes[16384]	= 'E_USER_DEPRECATED';
	}

	/**
	 * Writes an error to a logfile.
	 *
	 * @param	string	$errstr	The error which occured recently
	 * @param	array	$backtrace	The backtrace array created via debug_backtrace()
	 * @param	string	$errortype	The errortype that occured
	 */
	protected function _errorhandler_file($errstr, $backtrace, $errortype) {
		$body  = '############################## '.$errortype."\n";
		$body .= 'Datum:      '.date("d.m.Y - H:i:s")."\n";
		
		if (isset($_SERVER['REQUEST_URI'])) {
			$body .= 'URL:        '.$_SERVER['REQUEST_URI']."\n";
			$body .= 'IP:         '.$_SERVER['REMOTE_ADDR']."\n";
			$body .= 'User-Agent: '.(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '-') ."\n";
		} else {
			$body .= 'CLI:        '.(implode(' ', $_SERVER['argv']))."\n";
		}

		$body .= 'Meldung:    '.$errstr."\n";
		$body .= 'Backtrace:  '."\n\n";

		foreach ($backtrace as $value) {
			$body .= '       File: '.$value['file']." (Line ".$value['line'].")\n";
			$body .= '   Function: '.$value['class'].$value['type'].$value['function'].'()'."\n\n";
		}
		$body .= "\n";

		file_put_contents($this->_logfile, $body, FILE_APPEND);
	}
	
	/**
	 * Outputs an error to the screen.
	 *
	 * @param	string	$errstr	The error which occured recently
	 * @param	array	$backtrace	The backtrace array created via debug_backtrace()
	 * @param	string	$errortype	The errortype that occured
	 */
	protected function _errorhandler_output($errstr, $backtrace, $errortype) {
		// output error
		$error = '<div class="dp_container">';
		$error .= '<div class="skyline">'.$errortype.'</div>';
		$error .= '<h1 class="exception">'.htmlspecialchars($errstr, ENT_QUOTES, 'UTF-8').'</h1>';

		$count = 0;
		foreach ($backtrace as $key => $value) {
			// only if file is available
			if (!empty($value['file']) && is_file($value['file'])) {
				$id_file = '_errorhandler_file_'.$this->errorcounter.'_'.$count;
				$id_args = 'errorhandler_args_'.$this->errorcounter.'_'.$count;
				$show = ($count === 0) ? 'style="display: block;"' : '';

				if ($count>0) {
					$call = $value['class'].$value['type'].$value['function'].'()';
					$error .= '<h2>'.$call.'</h2>';
				}

				// highlight the file name
				$file = preg_replace('=[^/]+$=', '<strong>$0</strong>', $value['file']);
				
				$error .= '<h3>'.$file.' (Line '.$value['line'].')</h3>';
				$error .= '<div class="file" id="'.$id_file.'" '.$show.'>'.$this->_getContent($value['file'], $value['line']).'<div style="clear: both;"></div></div>';

				if (count($value['args'])>0 and $count > 0) {
					$dump = htmlspecialchars(print_r($value['args'], true));
					$error .= '<h3>Arguments ('.count($value['args']).')</h3>';
					$error .= '<div class="args" id="'.$id_args.'">'.$dump.'</div>';
				}

				$count++;
			}
		}

		$error .= '</div>';

		// output css and js only at first call
		if ($this->errorcounter === 1) $error .= $this->_debug_styles();

		return $error;
	}
	
	/**
	 * Return the CSS styling for dumps and errors
	 * @return string
	 */
	protected function _debug_styles() {
		return '
			<style>
			// css reset
			.dp_container,
			.dp_container .lineerror,
			.dp_container .file,
			.dp_container .args,
			.dp_container .skyline,
			
			.dp_container h1,
			.dp_container h2,
			.dp_container h3,
			
			.dp_container ol,
			.dp_container ol li,
			.dp_container ol li code { 
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				font-size: 100% !important;
				font: inherit !important;
				vertical-align: baseline !important;
				line-height: 100% !important;
				list-style: none !important;
			}
			
			.dp_container { font-size: 11px; color: #333; background: #f3f3f3; font: normal 11px/14px Verdana,sans-serif; }
			.dp_container .lineerror { background: #fdd; display: inline; }
			.dp_container .file { font: normal 12px/16px "Courier New"; margin: 1px 0px 10px 0px; background: #fff; border: 1px solid #999; border-left: 0; border-right: 0; overflow: hidden; }
			.dp_container .args { font: normal 12px/16px "Courier New"; padding: 2px 5px; margin: 1px 0px 10px 0px; background: #fff; border: 1px solid #999; border-left: 0; border-right: 0; overflow: hidden; white-space: pre; }
			.dp_container .skyline { float: right; padding: 4px 5px 2px 5px; color: #fff; font-size: 9px; }

			.dp_container h1 { margin: 0 0 10px 0; padding: 4px 5px; color: #fff; font: bold 13px/16px Verdana,sans-serif; }
			.dp_container h1.exception { background: #c44; }
			.dp_container h1.dump { background: #06c; }
			.dp_container h2 { background: #ddd; margin: 10px 0px 2px 0px; padding: 4px 5px; font: bold 13px/16px Verdana,sans-serif; }
			.dp_container h3 { margin: 0; padding: 4px 5px; font: normal 11px/14px Verdana,sans-serif; }

			.dp_container ol { background: #eee; margin-top: 0; margin-bottom: 1px; }
			.dp_container ol li { background: #fff; padding: 0px 0px 0px 5px; }
			.dp_container ol li code { background: none; display: inline; }
			</style>
		';
	}
	
	/**
	 * Set the method which is executed after the default exception handling
	 * @param	function	$after_exception	A closure to execute.
	 */
	public function setAfterException($after_exception) {
		$this->_after_exception = $after_exception;
	}

	/**
	 * This method is called when an exception occurs
	 * @param  object $exception The incoming Exception object
	 * @return null
	 * @hidden
	 */
	public function errorhandler($exception) {
		header("HTTP/1.1 500 Internal Server Error");

		$errstr		= $exception->getMessage();
		$errcode	= $exception->getCode();
		$backtrace	= $exception->getTrace();
		
		// the same should not be outputted more than once
		if ($errstr == $this->lasterror) return;
		$this->lasterror = $errstr;

		// count errors to produce unique ids
		$this->errorcounter++;

		// if it is an error throw away the first backtrace item
		if ($exception instanceof ErrorException) array_shift($backtrace);
		
		// add actual error to backtrace
		$bt['file'] = $exception->getFile();
		$bt['line'] = $exception->getLine();
		array_unshift($backtrace, $bt);
		
		// clean array
		$backtrace_keys = array('file'=>'', 'line'=>'', 'class'=>'', 'object'=>'', 'type'=>'', 'function'=>'', 'args'=>array());
		foreach ($backtrace as $key => $value) {
			$backtrace[$key] = array_merge($backtrace_keys, $value);
		}
		
		// set the error code string
		if ($exception instanceof ErrorException) $errordescription = $this->errortypes[ $exception->getSeverity() ];
		elseif ($errcode == 0) $errordescription = 'EXCEPTION';
		else $errordescription = 'EXCEPTION (Code '.$errcode.')';

		// show error on screen
		if ($this->config['output']['screen'] == true) {
			$error = $this->_errorhandler_output($errstr, $backtrace, $errordescription);
			echo $error;
		}

		// log error in logfile
		if ($this->config['output']['file'] == true) {
			$this->_errorhandler_file($errstr, $backtrace, $errordescription);
		}

		// "execute after exception" function
		if ($this->_after_exception !== null) {
			$call = $this->_after_exception;
			$call($exception);
		}
	}

	/**
	 * Retrieves a code excerpt from a file.
	 * 
	 * @param	string	$errfile_or_string	The path to the file or the content directly.
	 * @param	int		$errline In which line did the error occur.
	 * @param	string	$file_or_string	If the input a file path or a string.
	 * @return	string	Returns a HTML string with the highlighted line.
	 */
	protected function _getContent($errfile_or_string, $errline, $file_or_string = 'file') {
		$show_lines = 10;

		// Ausschnitt aus der Datei holen
		if ($file_or_string === 'file') $file = highlight_file($errfile_or_string, true);
		else $file = highlight_string($errfile_or_string, true);
		
		$file = explode("<br />", $file);

		foreach ($file as $key => $value) {
			$temp = strip_tags($value);
			if (empty($temp)) $value = '&nbsp;';
			$value = '<span>'.$value.'</span>';
			if ($key == $errline-1) $value = '<div class="lineerror">'.$value.'</div>';
			$value = '<li><code>'.$value.'</code></li>';
			$file[$key] = $value;
		}

		if ($errline-$show_lines < 0) $linestart = 0; else $linestart = $errline-$show_lines;
		$file = array_slice($file, $linestart, $show_lines*2, true);
		$file = implode('', $file);
		return '<ol start="'.($linestart+1).'">'.$file.'</ol>';
	}

	/* main method
	********************************************************************************************/
	/**
	 * Dumps any number of variables to the screen.
	 * @param mixed $variable Pass a variable number of arguments to this method
	 * @return null
	 */
	public static function dump($variable) {
		$args = func_get_args();
		echo Factory::load('Debug')->_dump($args);
	}
		
	/**
	 * Returns the HTML for dumped variables.
	 * @param	mixed	$args	Variables of any type in an array.
	 * @return	string	The HTML for the dumped variable.
	 */
	protected function _dump($args) {
		// get function call position
		$backtrace = debug_backtrace();
		$backtrace = $backtrace[1];

		// get calling file
		if (isset($backtrace['file'])) {
			$file = file($backtrace['file']);
			$function = trim($file[$backtrace['line']-1]);
		}

		$output = '';
		foreach ($args as $arg) {
			// count errors to produce unique ids
			$this->errorcounter++;

			// create headline
			$output .= '<div class="dp_container">';
			$output .= '<h1 class="dump">'.htmlspecialchars($function).'</h1>';

			// highlight the file name
			$file = preg_replace('=[^/]+$=', '<strong>$0</strong>', $backtrace['file']);
			$output .= '<h3>'.$file.' (Line '.$backtrace['line'].')</h3>';

			// add var content
			$output .= $this->_dump_php($arg);
			$output .= '</div>';
			
			// add styles
			$output .= $this->_debug_styles();
		}
		
		// output to screen		
		if ($this->config['output']['screen'] == false) return '';

		echo $output;
	}


	////////////////////////////////////////////////////////
	// Inspired from:     PHP.net Contributions
	// Description: Helps with php debugging

	/**
	 * Returns the HTML for the args of dumped variables.
	 * @param	mixed	$var	A variable of any type.
	 * @return	string	The HTML for the dumped variable.
	 */
	protected function _dump_php(&$var) {
		$scope = false;
		$prefix = 'unique';
		$suffix = 'value';

		if ($scope) $vals = $scope;
		else $vals = $GLOBALS;

		$old = $var;
		$var = $new = $prefix.rand().$suffix; $vname = false;
		foreach ($vals as $key => $val) {
			if ($val === $new) $vname = $key;
		}
		$var = $old;

		$output = '<div class="args">';
		$output .= $this->_dump_php_recursive($var, '$'.$vname);
		$output .= "</div>";
		
		return $output;
	}

	/**
	 * Returns the inner HTML for a dumped variable.
	 * @param	mixed	$var	A variable of any type.
	 * @param	string	$var_name	The name of the variable to output
	 * @param	string	$indent	The indentation level
	 * @param	string	$reference	WTF?
	 * @param	integer	$depth	The maximum recursion level to examine the variable
	 * @return	string	HTML string
	 */
	protected function _dump_php_recursive(&$var, $var_name = null, $indent = null, $reference = null, $depth = 0) {
		$colors = array(
			'String' => 'green',
			'Integer' => 'red',
			'Float' => '#0099c5',
			'Boolean' => '#92008d',
			'NULL' => 'black',
			'Resource' => 'black',
		);
		$grey = '#a2a2a2';

		$do_dump_indent = "<span style='color:#ccc;'>|</span> &nbsp;&nbsp; ";
		$reference = $reference.$var_name;
		$keyvar = 'the_do_dump_recursion_protection_scheme'; $keyname = 'referenced_object_name';
		
		$output = '';

		$maxdepth = 8;
		$depth++;

		if (is_array($var) && isset($var[$keyvar])) {
			$real_var = &$var[$keyvar];
			$real_name = &$var[$keyname];
			$type = ucfirst(gettype($real_var));
			$output .= "$indent$var_name <span style='color:$grey'>$type</span> = <span style='color:#e87800;'>&amp;$real_name</span><br />";
		} else {
			$var = array($keyvar => $var, $keyname => $reference);
			$avar = &$var[$keyvar];
			$type = ucfirst(gettype($avar));

			// (for historical reasons "double" is returned in case of a float, and not simply "float") 
			if ($type == "Double") $type = "Float";
			if ($type == "Unknown type") $type = "String";
			
			if (is_array($avar)) {
				$count = count($avar);
				$output .= "$indent" . ($var_name ? "$var_name => ":"") . "<span style='color:$grey'>$type ($count)</span><br />$indent(<br />";
				$keys = array_keys($avar);
				foreach ($keys as $name) {
					$value = &$avar[$name];
					if ($depth > $maxdepth ) $output .= "$indent$do_dump_indent<b style='color:red'>Too much recursion ...</b><br />";
					else $output .= $this->_dump_php_recursive($value, "['$name']", $indent.$do_dump_indent, $reference, $depth);
				}
				$output .= "$indent)<br />";
			} elseif (is_object($avar)) {
				$parent_class = get_parent_class($avar) ? ' extends '.get_parent_class($avar) : '';
				
				$output .= "$indent$var_name <span style='color:$grey'>$type(".get_class($avar).$parent_class.")</span><br />$indent{<br />";

				// output methods
				$reflectionClass = new \ReflectionClass(get_class($avar));
				foreach ($reflectionClass->getMethods() as $method) {
					$output .= "$indent$do_dump_indent";
					$output .= "<span style='color: #0099c5'>";
					$output .=  '-> '.$method->getName().'(';
					$params = array();
					foreach ($method->getParameters() as $param) {
						$temp = ($param->isPassedByReference() ? '&' : '') . '$'.$param->getName();
						if ($param->isOptional()) $temp = "[".$temp."]";
						$params[] = $temp;
					}
					$output .= implode(', ', $params).')</span><br />';
				}

				// output members
				$members = get_object_vars($avar);
				if (count($members) > 0) $output .= "$indent$do_dump_indent<br />";
				foreach ($members as $name => $value) {
					if ($depth > $maxdepth ) $output .= "$indent$do_dump_indent<b style='color:red'>Too much recursion ...</b><br />";
					else $output .= $this->_dump_php_recursive($value, "$${name}", $indent.$do_dump_indent, $reference, $depth);
				}
				$output .= "$indent}<br />";
			} elseif (is_resource($avar)) {
				$output .= "$indent$var_name <span style='color:$grey'>$type(".get_resource_type($avar).")</span><br />$indent{<br />";
				try {
					$meta_data = stream_get_meta_data($avar);
				} catch (\Exception $e) {
					$meta_data = array();
				}
				foreach ($meta_data as $key => $value) {
					$output .= $this->_dump_php_recursive($value, "['$key']", $indent.$do_dump_indent, $reference);
				}
				$output .= "$indent)<br />";
			} else { // for boolean, string, integer, float
				$output .= "$indent$var_name = <span style='color:$grey'>$type(".strlen((string)$avar).")</span> <span style='color: ${colors[$type]}'>";
				
				// rewrite output
				if (is_string($avar)) $output .= "\"".htmlspecialchars($avar)."\"</span><br />";
				elseif (is_bool($avar)) $output .= ($avar == 1 ? "TRUE":"FALSE")."</span><br />";
				elseif (is_null($avar)) $output .= "NULL</span><br />";
				else $output .= "$avar</span><br />";
			}
			
			$var = $var[$keyvar];
		}
		
		return $output;
	}
}

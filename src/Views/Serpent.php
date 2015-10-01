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


namespace Morrow\Views;

use Morrow\Factory;

/**
 * With this view handler it is possible to output with plain PHP.
 *
 * This handler uses the [Serpent Template Engine](https://github.com/McSodbrenner/serpent-php-template-engine) which improves PHP a little bit to have more comfort when writing templates.
 */
class Serpent extends AbstractView {
	/**
	 * Set to true if your view handler returns HTML. This flag allows to use Morrows Features.
	 * @var	boolean $is_returning_html
	 */
	public $is_returning_html = true;

	/**
	 * a
	 * @var string $template_path
	 */
	public $template_path	= '';

	/**
	 * a
	 * @var string $template_path
	 */
	public $compile_path	= '';

	/**
	 * a
	 * @var string $template
	 */
	public $template		= '';

	/**
	 * The default extension that will Serpent use for template names.
	 * @var string $template_suffix
	 */
	public $template_suffix	= '.htm';

	/**
	 * a
	 * @var boolean $force_compile
	 */
	public $force_compile	= false;

	/**
	 * a
	 * @var array $mappings
	 */
	public $mappings		= [];

	/**
	 * a
	 * @var array $resources
	 */
	public $resources		= [];

	/**
	 * Handles the cycles for cycles()
	 * @var array $cycles
	 */
	public static $cycles = [];

	/**
	 * The view handler could extend this method to set some parameters.
	 * @param  string  $namespace	The MVC controller class with namespace that inits this instance.
	 */
	public function init($namespace) {
		// extract template path from class namespace
		$namespace = trim($namespace, '\\');
		$namespace_array = explode('\\', $namespace);
		$this->template_path = implode('/', array_slice($namespace_array, 1, 2));
		$this->template_path .= '/templates/';

		// get template name from template router
		$this->template = call_user_func(Factory::load('Config')->get('router.template'), $namespace);

		// pass the page variables to the template
		$this->setContent('page', Factory::load('Page')->get(), true);

		// cerate template compile path fron namespace array
		$module_name = implode('/', array_slice($namespace_array, 2, 1));
		$this->compile_path = STORAGE_PATH .'serpent_templates_compiled/' . $module_name . '/';
	}

	/**
	 * You always have to define this method.
	 * @return  string  Should return the rendered content.
	 * @hidden
	 */
	public function getOutput() {
		// assign template and frame_template to page
		$this->_content['page']['template'] = $this->template;

		if (!is_dir($this->compile_path)) mkdir($this->compile_path, 0777, true); // create temp dir if it does not exist

		// init serpent
		$_engine = new \McSodbrenner\Serpent\Serpent($this->compile_path, 'utf-8', $this->force_compile);

		// handle mappings
		$mappings = [
			'dump'			=> '\\Morrow\\Debug::dump',
			'url'			=> '\\Morrow\\Factory::load("Url")->create',
			'securl'		=> '\\Morrow\\Factory::load("Security")->createCSRFUrl',
			'cycle'			=> '\\Morrow\\Views\\Serpent::cycle',
			'mailto'		=> '\\Morrow\\Views\\Serpent::mailto',
			'hidelink'		=> '\\Morrow\\Views\\Serpent::hidelink',
			'thumb'			=> '\\Morrow\\Views\\Serpent::thumb',
			'truncate'		=> '\\Morrow\\Views\\Serpent::truncate',
			'strip'			=> 'ob_start(["\\Morrow\\Views\\Serpent::strip"]) //',
			'endstrip'		=> 'ob_end_flush',
			'loremipsum'	=> '\\Morrow\\Views\\Serpent::loremipsum',
			'_'				=> '\\Morrow\\Factory::load("Language")->_',
			'module'		=> 'stream_get_contents(\\Morrow\\Factory::load("Core\\Feature")->run',
		];
		// add user mappings
		foreach ($this->mappings as $key => $value) {
			$mappings[$key] = $value;
		}

		$_engine->addMappings($mappings);

		// handle resources
		$_engine->addResource('file',
			new \McSodbrenner\Serpent\ResourceFile($this->template_path, $this->template_suffix, Factory::load('Language')->get())
		);

		foreach ($this->resources as $resource) {
			call_user_func_array([$_engine, 'addResource'], $resource);
		}

		// create stream handle for the output
		$handle = fopen('php://temp/maxmemory:'.(1*1024*1024), 'r+'); // 1MB


		// write source to stream
		$_engine->pass($this->_content);
		fwrite($handle, $_engine->render($this->template));

		return $handle;
	}

	/**
	 * Used for the Serpent mapping `:cycle`. Every call of cycle will return the next of the parameters you have passed initially. All function paramters will be used to cycle.
	 * @return  mixed Returns the next cyvle value.
	 */
	public static function cycle() {
		$values = func_get_args();
		$name = array_shift($values);

		if (!isset(self::$cycles[$name])) self::$cycles[$name] = -1;
		$index =& self::$cycles[$name];
		if (!isset($values[++$index])) $index = 0;
		return $values[ $index ];
	}

	/**
	 * Used for the Serpent block `:strip`. Removes unnecessary whitespace from an html string.
	 * @param   string $buffer The content to work with.
	 * @return  string Returns the edited buffer.
	 */
	public static function strip($buffer) {
		$pat = ["=^\s+=", "=\s{2,}=", "=\s+\$=", "=>\s*<([a-z])="];
		$rep = ["", " ", "", "><$1"];
		$buffer = preg_replace($pat, $rep, $buffer);
		return $buffer;
	}

	/**
	 * Used for the Serpent mapping `:mailto`. Obfuscates an email address with javascript and returns the necessary html.
	 * @param   string $address The email address to obfuscate.
	 * @param   string $text If set it will used as text for the link instead of the email address.
	 * @param   string $html If set you can pass an html string that will be embedded into the link.
	 * @return  string Returns the html that shows a linked email address.
	 */
	public static function mailto($address, $text = '', $html = '') {
		if (empty($text)) $text = $address;
		$address = str_replace('@', '--', $address);
		$id = uniqid('scrambled_');

		$link = '<a href="mailto:'.$address.'" '.$html.' rel="nofollow">'.$text.'</a>';
		$link = strrev($link);
		$returner = '<span id="'.$id.'">'.htmlspecialchars($link).'</span>';
		$returner .= '<script>';
		$returner .= 'var el_'.$id.' = document.getElementById("'.$id.'");';
		$returner .= 'var content_'.$id.' = el_'.$id.'.textContent ? el_'.$id.'.textContent : el_'.$id.'.innerText;'; // innerText = IE
		$returner .= 'el_'.$id.'.innerHTML = content_'.$id.'.split("").reverse().join("").replace(/--/g, "@");';
		$returner .= '</script>';
		return $returner;
	}

	/**
	 * Used for the Serpent mapping `:hidelink`. Obfuscates an URL with javascript and returns the necessary html.
	 * @param   string $url The URL to obfuscate.
	 * @param   string $text If set it will used as text for the link instead of the URL.
	 * @param   string $html If set you can pass an html string that will be embedded into the link.
	 * @return  string Returns the html that shows a linked URL.
	 */
	public static function hidelink($url, $text = '', $html = '') {
		if (empty($text)) $text = $url;
		$id = uniqid('scrambled_');
		$link = '<a href="'.$url.'" '.$html.' rel="nofollow">'.htmlspecialchars($text).'</a>';
		$link = strrev($link);
		$returner = '<span id="'.$id.'">'.htmlspecialchars($link).'</span>';
		$returner .= '<script>';
		$returner .= 'var el_'.$id.' = document.getElementById("'.$id.'");';
		$returner .= 'var content_'.$id.' = el_'.$id.'.textContent ? el_'.$id.'.textContent : el_'.$id.'.innerText;'; // innerText = IE
		$returner .= 'el_'.$id.'.innerHTML = content_'.$id.'.split("").reverse().join("");';
		$returner .= '</script>';
		return $returner;
	}

	/**
	 * Used for the Serpent mapping `:loremipsum`. Output some placeholder text.
	 * @param   integer $word_count Defines the count of words that should be shown.
	 * @param   boolean $random Randomizes the words.
	 * @return  string Returns the placeholder text.
	 */
	public static function loremipsum($word_count = 200, $random = true) {
		$text = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi. Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi. Nam liber tempor cum soluta nobis eleifend option congue nihil imperdiet doming id quod mazim placerat facer possim assum.';
		$count = str_word_count($text);

		$multiplier = ceil($word_count/$count);
		$text = str_repeat($text, $multiplier);

		$text = explode(' ', $text);
		if ($random) shuffle($text);
		$returner = array_slice($text, 0, $word_count);

		$returner = ucfirst(implode(' ', $returner)).'.';
		return $returner;
	}

	/**
	 * Used for the Serpent mapping `:thumb`. Outputs the path to a thumb of a passed image path.
	 * @param   string $filepath The path to the image.
	 * @param   array $params THe parameters to edit the image. Explained in \Morrow\Image.
	 * @return  string Returns the path to the thumbnail in the temp folder.
	 */
	public static function thumb($filepath, $params = []) {
		try {
			$path = Factory::load('Image')->get($filepath, $params);
			$path = str_replace(PUBLIC_PATH, '', $path);
		} catch (\Exception $e) {
			if (isset($params['fallback'])) {
				$path = Factory::load('Image')->get($params['fallback'], $params);
				$path = str_replace(PUBLIC_PATH, '', $path);
			} else {
				throw new \Exception (__CLASS__ . ': ' . $e);
			}
		}
		return $path;
	}

	/**
	 * Used for the Serpent mapping `:truncate`. Truncates a string to a character `$length`.
	 * @param   string $string The string you want to truncate.
	 * @param   integer $length The length of the truncated string.
	 * @param   string $etc The text at the end of the truncated string.
	 * @param   boolean $break_words Set to `true` if you want the function not to respect word boundaries.
	 * @param   boolean $middle Set to `true` if you want to see the middle of the original string.
	 * @return  string Returns the truncated text.
	 */
	public static function truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false) {
		if ($length == 0) return '';

		if (strlen($string) > $length) {
			$length -= min($length, strlen($etc));
			if (!$break_words && !$middle) {
				$string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length+1));
			}
			if (!$middle) {
				return substr($string, 0, $length) . $etc;
			} else {
				return substr($string, 0, $length/2) . $etc . substr($string, -$length/2);
			}
		} else {
			return $string;
		}
	}
}

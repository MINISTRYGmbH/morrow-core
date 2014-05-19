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
 * The Language class provides an interface to the the data that is stored in arrays in the _i18n folder.
 * The Language class is instanced by Morrow and relies on the following configuration variable:
 *
 * Type  | Keyname     | Default                                             | Description                                                                                      
 * ----- | ---------   | ---------                                           | ------------                                                                                     
 * array | `languages` | `array("en")` as defined in `_configs/_default.php` | An array of possible language keys (the first array entry is automatically the default language) 
 *
 * Example
 * -------
 * 
 * ~~~{.php}
 * // ... Controller code
 *  
 * $alias = $this->page->get('alias');
 *  
 * // passing language content to the template
 * $language_content = $this->language->getContent($alias);
 * $this->view->setContent('language_content', $language_content);
 *  
 * // passing the list of available translations to the template
 * $translations = $this->language->getTranslations($alias);
 * $this->view->setContent('translations', $translations);
 *  
 * // ... Controller code
 * ~~~
 */
class Language extends Core\Base {
	/**
	 * The current active language.
	 * @var string $_language
	 */
	protected $_language		= '';

	/**
	 * The default language.
	 * @var string $_default
	 */
	protected $_default			= '';

	/**
	 * All possible languages.
	 * @var array $_possible
	 */
	protected $_possible		= array();

	/**
	 * The path to the language files.
	 * @var string $_language_path
	 */
	protected $_language_path	= '';

	/**
	 * The content from the l10n.php file.
	 * @var array $_l10n
	 */
	protected $_l10n			= array();

	/**
	 * The content from the i18n.php file.
	 * @var array $_content
	 */
	protected $_content			= array();

	/**
	 * `True` if it was already checked for new to translated strings.
	 * @var boolean $_i18n_checked
	 */
	protected $_i18n_checked	= false;
	
	/**
	 * The paths to look for new to translated strings.
	 * @var array $_search_paths
	 */
	protected $_search_paths		= array();

	/**
	 * Initializes the class and takes an array with four keys as configuration.
	 *
	 * Type | Keyname | Default | Description
	 * -----|---------|---------|------------
	 * array	| `possible`		| `null`	| Required. An array with valid language keys.
	 * string	| `language_path`	| `""`		| Required. The path to all language files
	 * array	| `search_paths`	| `null`	| Required. An array of paths the class should use for strings to translate
	 * string	| `language`		| possible[0] |	The actual language.
	 *  
	 * @param	array	$settings	An array that configures the language class
	 */
	public function __construct($settings) {
		// check for required setting keys
		$required = array('possible', 'language_path', 'search_paths');
		if (count(array_diff($required, array_keys($settings))) > 0) {
			throw new \Exception("Missing key(s). Required params are : " . implode(", ", $required));
		}

		// set default parameters
		$this->_possible		= $settings['possible'];
		$this->_default			= $settings['possible'][0];
		$this->_language_path	= rtrim($settings['language_path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$this->_search_paths	= $settings['search_paths'];
		
		// set language
		// default language was provided
		if (isset($settings['language']) && $this->isValid($settings['language'])) {
			$lang = $settings['language'];
		} else {
			$lang = $this->_default;
		}
		
		// now change the language
		$this->set($lang);
	}

	/**
	 * Checks if the given language is a valid one.
	 * @param  string  $lang A language string.
	 * @return boolean
	 */
	public function isValid($lang) {
		return in_array($lang, $this->_possible);
	}

	/**
	 * Gets the current language.
	 * @return  string The current language.
	 */
	public function get() {
		return $this->_language;
	}

	/**
	 * Gets all possible languages.
	 * @return array An array of all possible languages.
	 */
	public function getPossible() {
		return $this->_possible;
	}

	/**
	 * Gets the default language.
	 * @return	string	The default language.
	 */
	public function getDefault() {
		return $this->_default;
	}

	/**
	 * Gets the config for the current language (or a passed language) from the `l10n.php` file.
	 * @param	string	$lang	A valid language string.
	 * @return	`array`
	 */
	public function getL10n($lang = null) {
		if (is_null($lang)) $lang = $this->_language;

		$file = $this->_language_path . $lang . '/l10n.php';
		return $this->_loadFile($file, true);
	}

	/**
	 * Gets the content for the current language (or a passed language) from the `i18n.php` file.
	 * @param	string	$lang	A valid language string.
	 * @return	`array`
	 */
	public function getContent($lang = null) {
		if (is_null($lang)) $lang = $this->_language;

		if ($this->_content == null) {
			$file = $this->_language_path . $lang . '/i18n.php';
			$this->_content = $this->_loadFile($file, false);
		}

		return $this->_content;
	}

	/**
	 * Gets the navigation tree for the current language (or a passed language) from the `tree.php` file.
	 * @param	string	$lang	A valid language string.
	 * @return	`array`
	 */
	public function getTree($lang = null) {
		if (is_null($lang)) $lang = $this->_language;

		$file = $this->_language_path . $lang . '/tree.php';
		return $this->_loadFile($file);
	}

	/**
	 * Tries to set the locales via setlocale. If no array items matches locales installed on the system you will get an overview of all installed locales.
	 * @param 	array	$keys	An array of keys to try.
	 * @return	`null`
	 */
	public function setLocale($keys) {
		if (!setlocale(LC_ALL, $keys)) {
			exec('locale -a', $locales);
			$locales = implode("<br />", $locales);
			throw new \Exception(__METHOD__.'<br>setLocale() failed. These are the locales installed on this system:<br />'.$locales);
		}
	}

	/**
	 * Checks in which languages the given page alias is available.
	 * @param	string	$alias	A page alias.
	 * @return	`array`	Returns an array where the key is the language key like "en" or "de", and the value is the language title like "English".
	 */
	public function getTranslations($alias) {
		$translations = array();
		foreach ($this->_possible as $possible) {
			if ($this->translationExists($possible, $alias)) {
				$config = $this->getL10n($possible);
				$translations[$possible] = $config['title'];
			}
		}
		return $translations;
	}

	/**
	 * Checks whether a page alias exists in the given language or not.
	 * @param	string	$lang	A language key.
	 * @param	string	$alias	A page alias.
	 * @return	`bool`
	 */
	public function translationExists($lang, $alias) {
		$tree = $this->getTree($lang);

		foreach ($tree as $branch) {
			if (isset($branch[$alias])) return true;
		}
		return false;
	}

	/**
	 * Sets the current language. Has to be one of the `possible` languages.
	 * @param	string	$lang	The language to set.
	 * @return	`bool` Returns `true` if the language could be changed or `false` if not
	 */
	public function set($lang) {
		if ($this->isValid($lang)) {
			$this->_language = $lang;
			$this->_l10n = $this->getL10n();

			// switch the locales
			$this->setLocale($this->_l10n['keys']);

			// clear content because language has changed
			$this->_content = null;
			return true;
		}
		return false;
	}

	/**
	 * Determines the best language according to the users browser settings.
	 * @return `string` A valid language key.
	 */
	public function getBestFromClient() {
		// get all language keys from client
		$client_langs = $this->getFromClient();
		
		foreach ($client_langs as $client_lang) {
			foreach ($this->_possible as $possible) {
				$l10n = $this->getL10n($possible);

				if (in_array($client_lang, $l10n['keys'])) {
					return $client_lang;
				}
			}
		}
	}

	/**
	 * Parses the Accept-Language HTTP header sent by the browser.
	 * It will return an array with the languages the user accepts, sorted from most preferred to least preferred.
	 * @return	`array`	Key is the importance, value is the language code.
	 */
	public function getFromClient() {
		$returner = array();

		// Example string
		// en-ca,en;q=0.8,en-us;q=0.6,de-de;q=0.4,de;q=0.2

		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$parts = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

			foreach ($parts as $part) {
				preg_match("|^
					(.*?)		# the language part
					([-].*?)?	# the country part (optional)
					(;q=(.*))?	# the quality part (optional)
				$|x", $part, $matches);

				// set quality to 1.0 if not given
				$quality	= isset($matches[4]) ? $matches[4] : '1.0';
				
				$returner[$quality] = strtolower(trim($matches[1]));
			}
			krsort($returner);
		}
		return $returner;
	}

	/**
	 * Tries to find a translation in the current language for the given string.
	 * @param	string	$string	A string in the default language to get translated.
	 * @return	string	Returns the translated string if a translation is available otherwise the passed string.
	 */
	public function _($string) {
		if ($this->_language == $this->_possible[0]) return $string;

		// search in language file
		if ($this->_content == null) {
			$this->getContent();
		}
		if (isset($this->_content[$string]) && !empty($this->_content[$string])) {
			return $this->_content[$string];
		}

		// oh not found, better check all languages
		if (!$this->_i18n_checked) {
			$this->checkLanguageFiles();
		}

		return $string;
	}

	/**
	 * Checks all paths given at class initialization via `search_paths` for translation calls with _(...) and creates the i18n.php files for all not default languages.
	 * Is automatically called if a language string in the function `_()` was not found.
	 * @return	`null`
	 */
	public function checkLanguageFiles() {
		$this->_i18n_checked = true;

		// search for all translation patterns
		$files = array();
		foreach ($this->_search_paths as $path) {
			$files = array_merge($files, $this->_globRecursive($path));
		}
		
		$catalog = array();
		foreach ($files as $file) {
			$content = file_get_contents($file);

			// handle double quotes
			preg_match_all('-_\(("(\\\.|[^"])*")\)-', $content, $matches);
			$catalog = array_merge($catalog, $matches[1]);

			// handle single quotes
			preg_match_all("-_\(('(\\\.|[^'])*')\)-", $content, $matches);
			$catalog = array_merge($catalog, $matches[1]);
		}

		
		// handle string escape sequences
		foreach ($catalog as $i => $v) {
			eval("\$catalog[\$i] = ".$v.";");
		}

		$catalog = array_fill_keys($catalog, '');
		ksort($catalog);

		// check the difference between the catalog and the existing language files
		foreach ($this->_possible as $i => $al) {
			if ($i === 0) continue;

			$path = $this->_language_path . $al . '/i18n.php';
			
			$current = include($path);
			// only keep not empty values
			if (!is_array($current)) $current = array();
			$current = array_filter($current);
			ksort($current);

			// new entries
			$new = array_diff_key($catalog, $current);
			
			// old entries
			$old = array_diff_key($current, $catalog);

			// valid entries
			$valid = array_intersect_key($current, $catalog);

			$export = "<?php\n/* This is an automatically created file */\n\nreturn array(";
			$export .= $this->_varExport($new, 'entries to translate');
			$export .= $this->_varExport($old, 'unknown entries');
			$export .= $this->_varExport($valid, 'translated entries');
			$export .= ");";

			file_put_contents($path, $export);
		}
	}

	/**
	 * Loads a config file and extracts dot syntax keys.
	 * @param	string	$path	The path to the config file.
	 * @param	boolean	$dot_syntax_explode	Explode keys or not.
	 * @return	array	Returns the loaded config.
	 */
	protected function _loadFile($path, $dot_syntax_explode = true) {
		if(!is_file($path)) return array();
		if ($dot_syntax_explode) return $this->arrayExplode(include($path));
		return include($path);
	}

	/**
	 * Like var_export from php but creates a code styled array.  Called by checkLanguageFiles to create the i18n files.
	 * @param	array	$array	The array to create the output string from.
	 * @param	string	$section	A section name to use as comment 
	 * @return	`string`
	 */
	protected function _varExport($array, $section) {
		if (empty($array)) return '';

		$returner = "\n\t/* $section */\n";
		foreach ($array as $k => $v) {
			$key = str_replace("'", "\'", $k);
			$value = str_replace("'", "\'", $v);

			$returner .= str_pad("\t'$key'", 40, ' ');
			$returner .= '=> ';
			$returner .= "'$value',\n";
		}

		return $returner;
	}

	/**
	 * The well known glob() but recursive.
	 * @param	string	$pattern	The pattern. No tilde expansion or parameter substitution is done.
	 * @param	integer	$flags	The same flags glob() accepts.
	 * @return	array	Returns all matches.
	 */
	protected function _globRecursive($pattern, $flags = 0) {
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern).'/*', GLOB_NOSORT) as $dir) {
			$files = array_merge($files, $this->_globRecursive($dir.'/'.basename($pattern), $flags));
		}

		return $files;	
	}
}

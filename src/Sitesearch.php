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


namespace Morrow\Filters;

/**
 * Sitesearch is a simple web page spider.
 *
 * It works with a SQLite backend and should therefore work with every website up to an intermediate size.
 * The access to the indexed data and the search calls are done via the \Morrow\Sitesearch class.
 * 
 * 
* You are able to change the behaviour of these methods with the following parameters in your configuration files:
*
* Type    | Keyname             | Default                    | Description                                                              
* -----   | ---------           | ---------                  | ------------                                                             
* bool    | `buildindex`        | `true`                     | Defines whether to index the actual viewed page or not.
* array   | `exclude_patterns`  | `empty`                    | Defines an array patterns to decide whether to index the actual viewed page or not. The key of the pattern array defines the field to apply the pattern to (possible values are "url", "title", "searchdata" and "bytes"). The value is a regular expression. When this regex hits in the defined field, then the actual page will not get indexed. Useful to exclude error pages or similar pages.
* string  | `tag_include_start` | `<!-- include_start -->`   | Defines the beginning string of a region to index.
* string  | `tag_include_end`   | `<!-- include_end -->`     | Defines the end string of a region to index.
* string  | `tag_exclude_start` | `<!-- exclude_start -->`   | Defines the start string of a region to exclude from indexing. Makes only sense inside the include tags.
* string  | `tag_exclude_end`   | `<!-- exclude_end -->`     | Defines the end string of a region to exclude from indexing. Makes only sense inside the include tags.
* integer | `check_divisor`     | `10`                       | Defines the frequency this filter will be applied to the actual viewed page.
* integer | `gc_divisor`        | `100`                      | Defines the frequency the garbage collection will get started. On default the garbage collection will be used every 1000 times because of the check_divisor.
* string  | `entry_lifetime`    | `+1 month`                 | When a page is not recrawled for the entry_lifetime it will be deleted from the index.
* array   | `db`                | see `configs/_default.php` | The default config for the used database. Usually you do not have to change those parameters. The driver has to be "sqlite". "mysql" and other drivers will not work.
* 
 * Example
 * --------
 * 
 * Controller code:
 * ~~~{.php}
 * // ... Controller code
 * 
 * $this->view->setFilter('Sitesearch', $this->config->get('sitesearch'), 'serpent');
 *
 * // ... Controller code
 * ~~~
 *
 * Example page
 * ~~~{.htm}
 * <html>
 * <head>
 *         <title>Just an example page</title>
 * </head>
 * <body>
 * This will not get indexed.
 * <!-- include_start -->
 *         This will get indexed.
 *         <!-- exclude_start -->
 *                 This will not get indexed.
 *         <!-- exclude_end -->
 * <!-- include_end -->
 *  
 * </body>
 * </html>
 * ~~~
 * 
 * If you work on a linux system you can index your whole site with the following shell command:
 * 
 * `wget --recursive --level=inf --no-parent --delete-after -nv --no-directories http://path-to-your-homepage.com`
 *
 * If you work on a mac os system you could download wget for mac and index your whole site with the same shell command.
 * 
 */
class Sitesearch extends AbstractFilter {
	/**
	 * Stores the passed configuration.
	 * @var array $_config
	 */
	protected $_config = array();

	/**
	 * An instance of the \Morrow\Db class
	 * @var object $_searchdb
	 */
	protected $_searchdb;

	/**
	 * The constructor which handles the passed parameters set in the second parameter of $this->view->setFilter().
	 * @param   array  $config  The content the view class has created.
	 */
	public function __construct($config) {
		$this->_config = $config;

		// load needed classes
		$this->_searchdb = \Morrow\Factory::load('Db:searchdb', $this->_config['db']);
	}
	
	/**
	 * This function indexes the passed content.
	 * @param   string  $content  The content the view class has created.
	 * @return  string  Returns the modified content.
	 */
	public function get($content) {
		// get output from standard handler
		$original_output = $content;

		// deactivate index building
		if (!$this->_config['buildindex']) return $original_output;

		// check only sometimes
		if (rand(1, $this->_config['check_divisor']) !== 1) return $original_output;
		
		$output = $original_output;

		// connect to DB and do maintenance
		$this->_searchdb->connect();
		$this->_createTableIfNotExists($this->_config['db_tablename']);
		if (rand(1, $this->_config['gc_divisor']) === 1) $this->_deleteOldEntries($this->_config['db_tablename']);
				
		// create url for current page
		$url =\Morrow\Factory::load('Url')->create('');

		// \Morrow\Debug::dump($this->checkUrl($url));
		
		
		// ##### check checksum #####
		// now check if we have to refresh the database entry
		$md5 = md5($output);
		$result = $this->_searchdb->result("
			SELECT checksum
			FROM ".$this->_config['db_tablename']."
			WHERE url = ?
		", $url);
		if (isset($result['RESULT'][0]['checksum'])) {
			$checksum = $result['RESULT'][0]['checksum'];
		}
		else $checksum = 1;
		if ($checksum === $md5) {
			// touch the current entry
			$this->_touchEntry($this->_config['db_tablename'], $url);
			return $original_output;
		}

		// ##### create Index #####
		
		// strip the excludes
		$regex = preg_quote($this->_config['tag_exclude_start']).'(.+?)'.preg_quote($this->_config['tag_exclude_end']);
		$output = preg_replace('='.$regex.'=s', '', $output);
		
		// now we take all content between the include tags
		$regex = preg_quote($this->_config['tag_include_start']).'(.+?)'.preg_quote($this->_config['tag_include_end']);
		$found = preg_match_all('='.$regex.'=s', $output, $matches, PREG_PATTERN_ORDER);
		if ($found === 0) return $original_output;

		// put all results in a string
		$output = implode(' ', $matches[1]);
		
		// create text version of content
		$output = strip_tags($output);

		// Replace useless space
		$output = preg_replace('=([ \t\r\n]|&nbsp;)+=i', ' ', $output);
		
		// replace entities for better search results
		$output = html_entity_decode($output);

		// first we take the title tag
		$found = preg_match('=<title>(.+?)</title>=is', $original_output, $match);
		if ($found === 0) $title = '';
		else $title = html_entity_decode($match[1]);

		// save to database
		$replace['url']			= $url;
		$replace['checksum']	= $md5;
		$replace['title']		= $title;
		$replace['searchdata']	= $output;
		$replace['bytes']		= strlen($original_output);
		$replace['changed']		= array('FUNC' => "datetime('now')");
		
		// execute exclude patterns
		$save_to_db = true;
		foreach ($this->exclude_patterns as $patterns) {
			foreach ($patterns as $field => $pattern) {
				if (preg_match($pattern, $replace[$field])) {
					$save_to_db = false;
					break;
				}
			}
		}
		
		// now save
		if ($save_to_db) {
			$this->_searchdb->replace($this->_config['db_tablename'], $replace);
			$this->_searchdb->query("VACUUM");
		}
		
		// output the original content
		return $original_output;
	}

	/**
	 * Check the saved data for an URL.
	 * @param   string  $url  The content the view class has created.
	 */
	public function checkUrl($url) {
		$sql = $this->_searchdb->result("
			SELECT *
			FROM {$this->_config['db_tablename']}
			WHERE url = ?
		", $url);
		
		if ($sql['NUM_ROWS'] > 0) return $sql['RESULT'][0];
		return false;
	}

	/**
	 * Creates the SQLite table if it does not exist already.
	 * @param   string  $table  The content the view class has created.
	 */
	protected function _createTableIfNotExists($table) {
		$this->_searchdb->query("
			CREATE TABLE IF NOT EXISTS `".$table."` (
			`url` char(255) NOT NULL,
			`checksum` char(32) NOT NULL,
			`title` char(255) NOT NULL,
			`searchdata` text NOT NULL default CURRENT_TIMESTAMP,
			`touched` timestamp NOT NULL default CURRENT_TIMESTAMP,
			`changed` timestamp NOT NULL,
			`bytes` int(11) NOT NULL,
			PRIMARY KEY  (`url`)
			);
		");
	}
	
	/**
	 * Deletes entries that are older than `entry_lifetime`.
	 * @param   string  $table  The content the view class has created.
	 */
	protected function _deleteOldEntries($table) {
		$sql = $this->_searchdb->exec("
			DELETE FROM {$table}
			WHERE datetime(touched, '{$this->_config['entry_lifetime']}') < datetime('now');
		");
		$this->_searchdb->query("VACUUM");
	}
		
	/**
	 * Touches an entry if it was reindexed.
	 * @param   string  $table  The content the view class has created.
	 * @param   string  $url  The content the view class has created.
	 */
	protected function _touchEntry($table, $url) {
		$updates['touched'] = array('FUNC' => "datetime('now')");
		$this->_searchdb->update($table, $updates, "WHERE url = '{$url}'");
	}
}



namespace Morrow;

class Sitesearch {
	protected $contextradius = 50;
	protected $limit = 10;
	
	public function __construct($config = array()) {
		$this->db_config = array(
			'driver' => 'sqlite',
			'file' => STORAGE_PATH .'sitesearch.sqlite',
			'host' => 'localhost',
			'db' => 'sitesearch_searchengine',
			'user' => 'root',
			'pass' => ''
		);

		// apply config
		foreach ($config as $key => $value) {
			$this->$key = $value;
		}

		$this->db = Factory::load('Db:dbsitesearch', $this->db_config);
		$this->bm = Factory::load('Benchmark:benchmarksitesearch');
	}

	public function getAll($where = '') {
		$results = $this->db->get("
			SELECT url,title,searchdata,bytes,strftime('%s', changed) as changed, *
			FROM searchdata ".$where
		);
		$returner = $results['RESULT'];
		return $returner;
	}
		
	public function get($q) {
		// start timer
		$start = microtime(true);
		
		// clean query
		$q = $this->_cleanQuery($q);
		$q2 = explode(' ', $q);
		$q_count = count($q2);
		
		foreach ($q2 as $key => $value) {
			$q2[$key] = '%'.$value.'%';
		}

		$replacements = array();
		for ($i=0; $i<3; $i++) {
			$replacements = array_merge($replacements, $q2);
		}
		array_push($replacements, $this->limit);
		
		$results = $this->db->get("
			SELECT url,title,searchdata,bytes,strftime('%s', changed) as changed
			FROM searchdata
			WHERE
				".str_repeat('title LIKE ? OR ', $q_count)."
				".str_repeat('url LIKE ? OR ', $q_count)."
				".str_repeat('searchdata LIKE ? OR ', $q_count)."
			1=0
			LIMIT ?
		", $replacements);
		
		$returner['time'] = microtime(true)-$start;
		$returner['data'] = $this->_prepare($q, $results);
		return $returner;
	}
		
	protected function _prepare($q, $results) {
		if (!isset($results['RESULT'][0])) return;

		$phrases = explode(' ', $q);
		foreach ($results['RESULT'] as $key => $result) {
			$new =& $results['RESULT'][$key];
			
			$raw = $this->excerpt($result['searchdata'], $q, $this->contextradius);
			$new['searchdata']	= htmlspecialchars($raw['excerpt']);
			$new['relevance']	= $raw['weight'];

			$new['url']			= htmlspecialchars($result['url']);
			$new['title']		= htmlspecialchars($result['title']);
			
			// hits in url and url should lead to higher relevance
			$extract = strtolower($result['title'].' '.$result['url']);
			$weight = array();
			foreach ($phrases as $phrase) {
				$phrase = strtolower($phrase);
				$weight[$phrase] = substr_count($extract, $phrase)+1;
			}
			$weight = array_product($weight)*1.5;

			$new['relevance']	+= $weight;
		}

		// sort after weight
		$weight = array();
		foreach ($results['RESULT'] as $key => $row) {
			$weight[$key]  = $row['relevance'];
		}

		array_multisort($weight, SORT_DESC, $results['RESULT']);
		return $results;
	}

	// stopword lists: http://www.ranks.nl/tools/stopwords.html
	protected function _cleanQuery($q) {
		$q = trim($q);
		$q = preg_replace('|\s+|', ' ', $q); // strip whitespace
		$words = explode(' ', $q);

		// exclude all words shorter than 2 chars
		foreach ($words as $key => $word) {
			if (strlen($word) < 3) unset ($words[$key]);
		}

		// stopwords longer than 2 chars
		$stopwords = array('about', 'and', 'are', 'com', 'for', 'from', 'how', 'she', 'that', 'the', 'this', 'was', 'what', 'when', 'where', 'who', 'will', 'with', 'the', 'www');
		$words = array_diff($words, $stopwords);
		
		$q = implode(' ', $words);
		return $q;
	}

	// nur Sitesearch
	public function excerpt($text, $phrase, $radius = 100, $etc = "...") {
		$textlength = strlen($text);

		// find the positions of all phrase words
		$phrases = explode(' ', $phrase);
		$phrases_regex = implode('|', array_map('preg_quote', $phrases));

		// get all the positions of the search words in the text
		$found = preg_match_all('='.$phrases_regex.'=i', $text, $matches, PREG_OFFSET_CAPTURE );
		$matches = $matches[0];
		
		// if phrase words were not found return the start of the page
		// useful on search results if you have a match in the url but no in the page text you want to show
		if (!$found) {
			$output = array();
			$output['excerpt'] = substr($text, 0, $radius*2);
			$output['weight'] = 0;
			return $output;
		}
				
		// get all positions and counts of search words within the text
		$tmp_positions = array();
		foreach ($matches as $match) {
			$tmp_positions[] = $match[1];
			$word = strtolower($match[0]);
		}
		
		// include all positions within the radius
		// take care that smaller radius has to be added to the other site
		$positions = array();
		foreach ($tmp_positions as $pos) {
			$start = ($pos-$radius < 0) ? 0 : $pos-$radius;
			$end = ($pos+$radius >= $textlength-1) ? $textlength-1 : $pos+$radius;
			$positions[] = array( 'start' => $start, 'end' => $end );
		}
		
		// combine overlapping ranges
		$newpositions = array();
		$count = count($positions);
		
		for ($i=0; $i<$count; $i++) {
			$curr =& $positions[$i];
			$next =& $positions[$i+1];
			
			if (!is_null($next) && $curr['end'] > $next['start']) {
				$next['start'] = $curr['start'];
				unset($curr);
			} else {
				$newpositions[] = $curr;
			}
		}
		$positions = $newpositions;
		
		$output = array();

		// calc weight of full text to return the relevance
		$text_lower = strtolower($text);
		foreach ($phrases as $phrase) {
			$phrase = strtolower($phrase);
			$weight[$phrase] = substr_count($text_lower, $phrase);
		}
		$output['weight'] = array_product($weight);
		
		
		// iterate all excerpts
		$highest_weight = 0;
		foreach ($positions as $pos) {
			$string = substr($text, $pos['start'], ($pos['end']-$pos['start']));
			if ($pos['start'] !== 0) $string = $etc.$string;
			if ($pos['end'] !== $textlength-1) $string .= $etc;
			
			// calc weight of this excerpt
			$weight = array();
			$extract = strtolower($string);
			foreach ($phrases as $phrase) {
				$phrase = strtolower($phrase);
				$weight[$phrase] = substr_count($extract, $phrase)+1;
			}
			$weight = array_product($weight);
			if ($weight > $highest_weight) $output['excerpt'] = $string;
		}
		return $output;
	}
}

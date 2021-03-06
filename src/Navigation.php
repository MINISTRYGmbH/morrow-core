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
 * Improves the handling with common navigational tasks.
 *
 * The navigation data has to follow a strict scheme but can be passed from different sources.
 * The default way is to store the data in an array in `"/languages/LANGUAGE/tree.php"`.
 *
 * Because paths can exist in more than one navigation branch (f.e. meta and main) you have to specify the branch you want to work with.
 *
 * Example
 * -------
 *
 * tree.php
 * ~~~{.php}
 * return [
 * 	'main' => [
 * 		'home'	=> 'Homepage',
 * 		'products' => ['title' => 'Products', 'foo' => 'bar'],
 * 		'products/boxes' => 'Boxes',
 * 		'products/things' => 'Things',
 * 	],
 * 	'meta' => [
 * 		'imprint'	=> 'Imprint',
 * 	],
 * ];
 * ~~~
 *
 * Default controller
 * ~~~{.php}
 * // ... Controller code
 *
 * // the complete navigation tree
 * $navi = $this->Navigation->get();
 * Debug::dump($navi);
 *
 * // the breadcrumb
 * $breadcrumb = $this->Navigation->getBreadcrumb();
 * Debug::dump($breadcrumb);
 *
 * // the current page
 * $active = $this->Navigation->getActive();
 * Debug::dump($active);
 *
 * // find a page by its title
 * $homepage = $this->Navigation->find('title', 'Homepage');
 * Debug::dump($homepage);
 *
 * // ... Controller code
 * ~~~
 *
 * Pager example
 * -------
 *
 * ~~~{.php}
 * // Controller code
 *
 * $total_results    = 41;
 * $results_per_page = 5;
 * $page             = $this->input->get('page');
 *
 * $pager_data = $this->Navigation->getPager($total_results, $results_per_page, $page);
 * Debug::dump($pager_data);
 *
 * // Controller code
 * ~~~
 *
 * ### Result for `$page = 2`
 * ~~~
 * Array
 * (
 *     [page_prev]          => 1
 *     [page_current]       => 2
 *     [page_next]          => 3
 *     [pages_total]        => 9
 *     [results_total]      => 41
 *     [results_per_page]   => 5
 *     [offset_start]       => 5
 *     [offset_end]         => 9
 *     [mysql_limit]        => 5,5
 * )
 * ~~~
 */
class Navigation {
	/**
	 * Contains all references to the tree nodes in a flat associative array.
	 * @var	array $_nodes
	 */
	protected $_nodes = [];

	/**
	 * Contains all references to the nodes in a tree array.
	 * @var	array $_tree
	 */
	protected $_tree = [];

	/**
	 * Stores the currently active node.
	 * @var	string $_active_path
	 */
	protected $_active_path = null;

	/**
	 * Creates the internal structure with the given data. Usually you don't have to call it yourself.
	 *
	 * @param	string	$data	An array as described in the examples above.
	 * @param	string	$active	The node that should be flagged as active.
	 */
	public function __construct($data, $active = null) {
		// fill $nodes and $tree
		$this->add($data);

		// set the active node
		if (isset($this->_nodes[$active])) {
			$this->setActive($active);
		}
	}

	/**
	 * Adds nodes to the current tree.
	 *
	 * @param	string	$data	An array as described in the examples above.
	 * @param	string	$branch	The branch to add the new nodes to. If left out you have to specify the branch in your input data.
	 * @return	null
	 */
	public function add($data, $branch = null) {
		if (!is_null($branch)) $data = [$branch => $data];

		foreach ($data as $branch => $tree) {
			// first create the flat tree
			foreach ($tree as $path => $node) {
				// its ok just to pass a string as title
				if (is_string($node)) $node = ['title' => $node];

				if (!isset($node['title']) or empty($node['title'])) {
					throw new \Exception(__CLASS__ . "': You have to define a title for path '{$path}'.");
				}

				// add other information
				$node['active'] = false;

				$node['path']	= $path;
				$parts			= explode('/', $node['path']);
				$node['node']	= array_pop($parts);
				$node['parent']	= implode('_', $parts);

				// add to nodes collection
				$this->_nodes[$node['path']] = $node;

				// add to nested tree
				if (empty($node['parent'])) {
					$this->_tree[$branch][$path] =& $this->_nodes[$path];
				}
			}
		}

		$nodes =& $this->_nodes;

		// now create the references in between
		foreach ($nodes as $path => $node) {
			// add as child to parent
			if (isset($nodes[$node['parent']])) {
				$nodes[$node['parent']]['children'][$path] =& $nodes[$path];
			}
		}
	}

	/**
	 * Adds nodes to the current tree.
	 *
	 * @param	string	$path	The node to set active.
	 * @return	array	The set node or throws an Exception if the `$path` is not known.
	 */
	public function setActive($path) {
		if (!isset($this->_nodes[$path])) {
			throw new \Exception(__METHOD__.': path "'.$path.'" does not exist.');
			return;
		}

		// set active path to retrieve the breadcrumb
		$this->_active_path = $path;

		// set all nodes to inactive
		foreach ($this->_nodes as $key => $item) {
			$this->_nodes[$key]['active'] = false;
		}

		// set actual node to active
		$actual =& $this->_nodes[$path];
		$actual['active'] = true;

		// loop to the top and set to active
		while (isset($this->_nodes[$actual['parent']])) {
			$actual =& $this->_nodes[$actual['parent']];
			$actual['active'] = true;
		}

		// return actual node
		return $this->_nodes[$path];
	}

	/**
	 * Gets the currently active node.
	 *
	 * @return	array	The currently active node.
	 */
	public function getActive() {
		return $this->get($this->_active_path);
	}

	/**
	 * Gets the tree below the passed node path.
	 *
	 * @param	string	$path  A node path.
	 * @return	array The full tree or a subtree if `$path` was passed.
	 */
	public function get($path = null) {
		// return full tree
		if (is_null($path)) return $this->_tree;

		if (!isset($this->_nodes[$path])) {
			throw new \Exception(__METHOD__.': path "'.$path.'" does not exist.');
			return;
		}
		return $this->_nodes[$path];
	}

	/**
	 * Find a specific node.
	 *
	 * @param	string	$field The field to search for like "title", "path" and so on.
	 * @param	string	$path The search string.
	 * @return	array The subtree with the found node and its children.
	 */
	public function find($field, $path) {
		// return node by user defined field
		foreach ($this->_nodes as $node) {
			if (isset($node[$field]) && $node[$field] == $path) return $node;
		}
		return null;
	}

	/**
	 * Get the tree up from currently active page to the actual page or ... the breadcrumb.
	 *
	 * @return	array The active nodes.
	 */
	public function getBreadcrumb() {
		$breadcrumb = [];

		// handle not set active node
		if (!isset($this->_nodes[$this->_active_path])) {
			throw new \Exception(__METHOD__.': you did not set an active node so you cannot retrieve a breadcrumb.');
			return;
		}

		// get actual node
		$actual = $this->_nodes[$this->_active_path];
		array_unshift($breadcrumb, $actual);

		// loop to the top
		while (isset($this->_nodes[$actual['parent']])) {
			$actual =& $this->_nodes[$actual['parent']];
			array_unshift($breadcrumb, $actual);
		}

		return $breadcrumb;
	}

	/**
	 * Returns environment variables which helps you to build a pager.
	 *
	 * @param	integer	$total_results The total number of your results.
	 * @param	integer	$results_per_page The number of results you want to show per page.
	 * @param	integer	$current_page The page you want the environment variables for.
	 * @return	array	Returns an associative array with the pager environment variables.
	 */
	public function getPager($total_results, $results_per_page = 20, $current_page = 1) {
		$total_pages	= intval(max(1, ceil($total_results / $results_per_page)));
		$current_page	= intval(min(max(1, $current_page), $total_pages));
		$offset_start	= $results_per_page * ($current_page - 1);
		$offset_end		= min($offset_start + $results_per_page - 1, $total_results);
		$mysql_limit	= $offset_start . ',' . $results_per_page;

		return [
			'page_prev'			=> $current_page > 1 ? $current_page - 1 : false,
			'page_current'		=> $current_page,
			'page_next'			=> $current_page < $total_pages ? $current_page + 1 : false,
			'pages_total'		=> $total_pages,
			'results_total'		=> $total_results,
			'results_per_page'	=> $results_per_page,
			'offset_start'		=> $offset_start,
			'offset_end'		=> $offset_end,
			'mysql_limit'		=> $mysql_limit,
		];
	}
}

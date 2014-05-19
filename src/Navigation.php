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
 * The default way is to store the data in an array in `APP_PATH . "/languages/LANGUAGE/tree.php"`.
 * 
 * Because aliases can exist in more than one navigation branch (f.e. meta and main) you have to specify the branch you want to work with. 
 *
 * Example
 * -------
 *
 * tree.php
 * ~~~{.php}
 * return array(
 * 	'main' => array(
 * 		'home'	=> 'Homepage',
 * 		'products' => array('title' => 'Products', 'foo' => 'bar'),
 * 		'products_boxes' => 'Boxes',
 * 		'products_things' => 'Things',
 * 	),
 * 	'meta' => array(
 * 		'imprint'	=> 'Imprint',
 * 	),
 * );
 * ~~~
 * 
 * DefaultController
 * ~~~{.php}
 * // ... Controller code
 *
 * // the complete navigation tree
 * $navi = $this->navigation->get();
 * Debug::dump($navi);
 *
 * // the breadcrumb
 * $breadcrumb = $this->navigation->getBreadcrumb();
 * Debug::dump($breadcrumb);
 *
 * // the current page
 * $active = $this->navigation->getActive();
 * Debug::dump($active);
 *
 * // find a page by its title
 * $homepage = $this->navigation->find('title', 'Homepage');
 * Debug::dump($homepage);
 *
 * // ... Controller code
 * ~~~
 */
class Navigation {
	/**
	 * Contains all references to the tree nodes in a flat associative array.
	 * @var	array $_nodes
	 */
	protected $_nodes = array();

	/**
	 * Contains all references to the nodes in a tree array.
	 * @var	array $_tree
	 */
	protected $_tree = array();

	/**
	 * Stores the currently active node.
	 * @var	string $_active_id
	 */
	protected $_active_id = null;
	
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
		if (!is_null($branch)) $data = array($branch => $data);
		
		foreach ($data as $branch => $tree) {
			// first create the flat tree
			foreach ($tree as $id => $node) {
				// its ok just to pass a string as title
				if (is_string($node)) $node = array('title' => $node);
				
				if (!isset($node['title']) or empty($node['title'])) {
					throw new \Exception(__CLASS__ . "': You have to define a title for id '{$id}'.");
				}
				
				// add other information
				$node['active'] = false;
				
				// set alias if not already set (maybe data came from db)
				if (!isset($node['alias'])) $node['alias'] = $id;
				
				$parts = explode('_', $node['alias']);
				$node['path'] = implode('/', $parts) . '/';
				$node['node'] = array_pop($parts);
				$node['parent'] = implode('_', $parts);
				
				// add to nodes collection
				$this->_nodes[$node['alias']] = $node;

				// add to nested tree
				if (empty($node['parent'])) {
					$this->_tree[$branch][$id] =& $this->_nodes[$id];
				}
			}
		}

		$nodes =& $this->_nodes;
		
		// now create the references in between
		foreach ($nodes as $id => $node) {
			// add as child to parent
			if (isset($nodes[$node['parent']])) {
				$nodes[$node['parent']]['children'][$id] =& $nodes[$id];
			}
		}
	}

	/**
	 * Adds nodes to the current tree.
	 *
	 * @param	string	$id	The node to set active.
	 * @return	array	The set node or throws an Exception if the `$id` is not known.
	 */
	public function setActive($id) {
		if (!isset($this->_nodes[$id])) {
			throw new \Exception(__METHOD__.': id "'.$id.'" does not exist.');
			return;
		}
		
		// set active id to retrieve the breadcrumb
		$this->_active_id = $id;
		
		// set all nodes to inactive
		foreach ($this->_nodes as $key => $item) {
			$this->_nodes[$key]['active'] = false;
		}
		
		// set actual node to active
		$actual =& $this->_nodes[$id];
		$actual['active'] = true;
		
		// loop to the top and set to active
		while (isset($this->_nodes[$actual['parent']])) {
			$actual =& $this->_nodes[$actual['parent']];
			$actual['active'] = true;
		}
		
		// return actual node
		return $this->_nodes[$id];
	}

	/**
	 * Gets the currently active node.
	 *
	 * @return	array	The currently active node.
	 */
	public function getActive() {
		return $this->get($this->_active_id);
	}

	/**
	 * Gets the tree below the passed node id.
	 *
	 * @param	string	$id  A node id.
	 * @return	array The full tree or a subtree if `$id` was passed.
	 */
	public function get($id = null) {
		// return full tree
		if (is_null($id)) return $this->_tree;

		if (!isset($this->_nodes[$id])) {
			throw new \Exception(__METHOD__.': id "'.$id.'" does not exist.');
			return;
		}
		return $this->_nodes[$id];
	}

	/**
	 * Find a specific node.
	 *
	 * @param	string	$field The field to search for like "title", "path", "alias" and so on.
	 * @param	string	$id The search string.
	 * @return	array The subtree with the found node and its children.
	 */
	public function find($field, $id) {
		// return node by user defined field
		foreach ($this->_nodes as $node) {
			if (isset($node[$field]) && $node[$field] == $id) return $node;
		}
		return null;
	}

	/**
	 * Get the tree up from currently active page to the actual page or ... the breadcrumb.
	 *
	 * @return	array The active nodes.
	 */
	public function getBreadcrumb() {
		$breadcrumb = array();
		
		// handle not set active node
		if (!isset($this->_nodes[$this->_active_id])) {
			throw new \Exception(__METHOD__.': you did not set an active node so you cannot retrieve a breadcrumb.');
			return;
		}
		
		// get actual node
		$actual = $this->_nodes[$this->_active_id];
		array_unshift($breadcrumb, $actual);
		
		// loop to the top
		while (isset($this->_nodes[$actual['parent']])) {
			$actual =& $this->_nodes[$actual['parent']];
			array_unshift($breadcrumb, $actual);
		}
		
		return $breadcrumb;
	}
}

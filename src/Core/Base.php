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

/**
* This class contains the functionality for working with the array dot syntax.
* 
* It is used by many classes in the framework.
*/
abstract class Base {
	/**
	 * Orders an array like in a SQL query.
	 * 
	 * Usage example:
	 *
	 * ~~~{.php}
	 * class Dummy extends \Morrow\Core\Base {
	 * 	public function sort() {
	 *		$data = array(
	 *			0 => array(
	 *				'title' => 'Foo',
	 * 				'position' => 1,
	 * 			),
	 *			1 => array(
	 * 				'title' => 'Bar',
	 * 				'position' => 0,
	 * 			),
	 * 		);
	 * 
	 *	 	return $this->arrayOrderBy($data, 'position ASC, title ASC');
	 * 	}
	 * }
	 * ~~~
	 *
	 * @param  array $data The multidimensional array that should get sorted.
	 * @param  string $orderby An SQL like string to define the ordering.
	 * @return array Returns the sorted array.
	 */
	public function arrayOrderBy($data, $orderby) {
		// all the references are part of a workaround which only occurs with array_multisort and call_user_func_array in PHP >= 5.3
		$asc = SORT_ASC; 
		$desc = SORT_DESC;
		
		// the array we pass to array_multisort at the end
		$params = [];

		// explode the orderby to use it for array_multisort
		$orderbys = explode(',', $orderby);
		$orderbys = array_map('trim', $orderbys);
		foreach ($orderbys as &$orderby) {
			$parts = explode(" ", $orderby);
			if (!isset($parts[1])) $parts[1] = 'asc';
			$parts[1] = strtolower($parts[1]);
			if (!in_array($parts[1], array('asc', 'desc'))) $parts[1] = 'asc';
			
			// add field name
			$params[] = $parts[0];

			// add sort flag
			if ($parts[1] == 'asc') $params[] =& $asc;
			else $params[] =& $desc;
		}

		// create temp arrays for multisort
		$temp = [];
		$count = count($params)-1;
		for ($i=0; $i<=$count; $i=$i+2) {
			$field = $params[$i];
			$params[$i] = [];
			foreach ($data as $ii => $row) {
				$temp[$field][] = strtolower($row[$field]);
			}
			
			$params[$i] =& $temp[$field];
		}
		
		//now sort
		$params[] =& $data;
		call_user_func_array('array_multisort', $params);
		return $data;
	}

	/**
	 * Returns a branch or a value of a multidimensional array tree by use of the dot syntax to define subnodes.
	 * 
	 * Usage example:
	 *
	 * ~~~{.php}
	 * class Dummy extends \Morrow\Core\Base {
	 * 	public function foo() {
	 *		$data = array(
	 *			0 => array(
	 *				'title' => 'Foo',
	 * 				'position' => 1,
	 * 			),
	 *			1 => array(
	 * 				'title' => 'Bar',
	 * 				'position' => 0,
	 * 			),
	 * 		);
	 * 
	 *	 	return $this->arrayGet($data, '1.title');
	 * 	}
	 * }
	 * ~~~
	 *
	 * @param  array $array The input array which should be searched.
	 * @param  string $identifier A string of array keys separated by dots.
	 * @param  mixed $fallback Will be returned if the `$identifier` could not be found.
	 * @return array Returns the subnode.
	 */
	public function arrayGet(array &$array, $identifier = '', $fallback = null) {
		if (empty($identifier)) return $array;

		// create reference
		$parts = explode('.', $identifier);
		$returner =& $array;

		foreach ($parts as $part) {
			if (isset($returner[$part])) {
				// if the array key is set expand the reference
				$returner =& $returner[$part];
			} else {
				// delete the reference
				unset($returner);
				break;
			}
		}

		if (isset($returner)) return $returner;
		else return $fallback;
	}

	/**
	 * Sets a branch or a value of a multidimensional array tree by use of the dot syntax to define subnodes.
	 * 
	 * Usage example:
	 *
	 * ~~~{.php}
	 * class Dummy extends \Morrow\Core\Base {
	 * 	public function foo() {
	 *		$data = array(
	 *			0 => array(
	 *				'title' => 'Foo',
	 * 				'position' => 1,
	 * 			),
	 *			1 => array(
	 * 				'title' => 'Bar',
	 * 				'position' => 0,
	 * 			),
	 * 		);
	 * 
	 *	 	return $this->arraySet($data, '1.children', array(0 => array('title' => 'FooBar', 'position' => 0)));
	 * 	}
	 * }
	 * ~~~
	 *
	 * @param  array $array The input array which should be extended.
	 * @param  string $identifier A string of array keys separated by dots.
	 * @param  mixed $value The data to be set.
	 * @return null
	 */
	public function arraySet(array &$array, $identifier, $value) {
		// create reference
		$returner =& $array;
		
		foreach (explode('.', $identifier) as $part) {
			if (!isset($returner[$part])) {
				$returner[$part] = '';
			}
			$returner =& $returner[$part];
		}
		
		$returner = $value;
	}

	/**
	 * Deletes a branch or a key of a multidimensional array tree by use of the dot syntax to define subnodes.
	 * 
	 * Usage example:
	 *
	 * ~~~{.php}
	 * class Dummy extends \Morrow\Core\Base {
	 * 	public function foo() {
	 *		$data = array(
	 *			0 => array(
	 *				'title' => 'Foo',
	 * 				'position' => 1,
	 * 			),
	 *			1 => array(
	 * 				'title' => 'Bar',
	 * 				'position' => 0,
	 * 			),
	 * 		);
	 * 
	 *	 	return $this->arrayDelete($data, '0.title');
	 * 	}
	 * }
	 * ~~~
	 *
	 * @param  array $array The input array which should be extended.
	 * @param  string $identifier A string of array keys separated by dots.
	 * @return null
	 */
	public function arrayDelete(array &$array, $identifier) {
		// create reference
		$parts = explode('.', $identifier);
		$returner =& $array;
		$parent =& $array;

		foreach ($parts as $part) {
			// if the array key is set expand the reference
			if (isset($returner[$part]) && !empty($part)) {
				$parent =& $returner;
				$rkey = $part;
				$returner =& $returner[$part];
			} else {
				// delete the reference
				unset($returner);
				break;
			}
		}

		if (isset($returner)) {
			unset($parent[$rkey]);
		} else {
			throw new \Exception(__CLASS__.': identifier "'.$identifier.'" does not exist.');
		}
	}

	/**
	 * Explodes an array with dotted keys to a normal array.
	 * 
	 * Usage example:
	 *
	 * ~~~{.php}
	 * class Dummy extends \Morrow\Core\Base {
	 * 	public function foo() {
	 *		$data = array(
	 *			'0.title' => 'Foo',
	 * 			'0.position' => 1,
	 * 			'1.title' => 'Bar',
	 * 			'1.position' => 0,
	 * 		);
	 * 
	 *	 	return $this->arrayExplode($data);
	 * 	}
	 * }
	 * ~~~
	 *
	 * @param  array $array The input array that should be exploded.
	 * @return null
	 */
	public function arrayExplode(array $array) {
		$data = [];

		// iterate keys
		foreach ($array as $rkey => $row) {
			$parent =& $data;
			$parts = explode('.', $rkey);

			// iterate key parts
			foreach ($parts as $part) {
				// build values
				if (!isset($parent[$part]) || !is_array($parent[$part])) {
					if ($part === end($parts)) {
						if (!is_array($row)) $parent[$part] = $row;
						else $parent[$part] = $this->arrayExplode($row);
					}
					else $parent[$part] = [];
				}
				$parent = &$parent[$part];
			}
		}
		return $data;
	}
}

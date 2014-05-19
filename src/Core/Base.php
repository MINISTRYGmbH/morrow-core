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

abstract class Base {
	// often used, where to put?
	// This function orders an array like in a sql query
	public function arrayOrderBy($data, $orderby) {
		// all the references are part of a workaround which only occurs with array_multisort and call_user_func_array in PHP >= 5.3
		$asc = SORT_ASC; 
		$desc = SORT_DESC;
		
		// the array we pass to array_multisort at the end
		$params = array();

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
		$temp = array();
		$count = count($params)-1;
		for ($i=0; $i<=$count; $i=$i+2) {
			$field = $params[$i];
			$params[$i] = array();
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

	public function arrayExplode(array $array) {
		$data = array();

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
					else $parent[$part] = array();
				}
				$parent = &$parent[$part];
			}
		}
		return $data;
	}
}

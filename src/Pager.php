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
 * Helps with typical paging issues in your database queries and your templates.
 *
 * Example
 * -------
 *
 * ~~~{.php}
 * // Controller code
 *
 * $total_results    = 41;
 * $results_per_page = 5;
 * $page             = $this->input->get('page');
 * 
 * $pager_data = $this->pager->get($total_results, $results_per_page, $page);
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
class Pager {
	/**
	 * Returns environment variables which helps you to build your pager.
	 *
	 * @param	integer	$total_results The total number of your results.
	 * @param	integer	$results_per_page The number of results you want to show per page.
	 * @param	integer	$current_page The page you want the environment variables for.
	 * @return	array	Returns an associative array with the pager environment variables.
	 */
	public function get($total_results, $results_per_page = 20, $current_page = 1) {
		$total_pages	= intval(max(1, ceil($total_results / $results_per_page)));
		$current_page	= intval(min(max(1, $current_page), $total_pages));
		$offset_start	= $results_per_page * ($current_page - 1);
		$offset_end		= min($offset_start + $results_per_page - 1, $total_results);
		$mysql_limit	= $offset_start . ',' . $results_per_page;

		return array(
			'page_prev'			=> $current_page > 1 ? $current_page - 1 : false,
			'page_current'		=> $current_page,
			'page_next'			=> $current_page < $total_pages ? $current_page + 1 : false,
			'pages_total'		=> $total_pages,
			'results_total'		=> $total_results,
			'results_per_page'	=> $results_per_page,
			'offset_start'		=> $offset_start,
			'offset_end'		=> $offset_end,
			'mysql_limit'		=> $mysql_limit,
		);
	}
}

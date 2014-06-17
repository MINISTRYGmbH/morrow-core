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

use Morrow\Factory;
use Morrow\Debug;

/**
 * The main class which defines the cycle of a request.
 */
class Features {
	public function execute($namespace, $classname, $master = false) {


		/* load features
		********************************************************************************************/
		$features = include($root_path_absolute . 'features/features.php');

		// create DOM object
		libxml_use_internal_errors(true);
		$data		= $view->get();
		$content	= stream_get_contents($data['content']);
		$doc		= new \DOMDocument();
		$doc->loadHtml('<?xml encoding="UTF-8">' . $content);
		libxml_use_internal_errors(false);
		
		$xpath = new \DOMXPath($doc);

		foreach ($features as $controller_regex => $page_features) {
			if (!preg_match('~^'.$controller_regex.'$~', $classname)) continue;

			foreach ($page_features as $xpath_query => $section_features) {
				$nodelist = $xpath->query($xpath_query);

				foreach ($nodelist as $node) {
					foreach ($section_features as $actions) {
						foreach ($actions as $action => $class) {
							$namespace = preg_replace('~[^\\\\]+$~', '', $class);
							$classname = preg_replace('~.+\\\\~', '', $class);

							$frontcontroller = new Frontcontroller;
							$data = $frontcontroller->execute($namespace, $classname);

							$fragment = $doc->createDocumentFragment();
							$fragment->appendXML(stream_get_contents($data['content']));
							
							if ($action === 'prepend') {
								$node->insertBefore($fragment, $node->firstChild);
							} elseif ($action === 'append') {
								$node->appendChild($fragment);
							}
						}
					}
				}
			}
		}

		// remove XML prolog
		foreach ($doc->childNodes as $item) {
			if ($item->nodeType == XML_PI_NODE) {
				$doc->removeChild($item);
				break;
			}
		}

		$handle = fopen('php://memory', 'r+');
		$view_data['content'] = $handle;
		fwrite($view_data['content'], $doc->saveHtml());

		return $view_data;
	}
}

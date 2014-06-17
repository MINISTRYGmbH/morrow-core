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

use Morrow\Factory;
use Morrow\Debug;

/**
 * The main class which defines the cycle of a request.
 */
class Features {
	protected $_data;
	protected $_classname;

	public function __construct($features_path, $classname) {
		$this->_data		= include($features_path);
		$this->_classname	= $classname;
	}

	public function delete($feature_name) {
		foreach ($this->_data as $controller_regex => $page_features) {
			if (!preg_match('~^'.$controller_regex.'$~', $this->_classname)) continue;

			foreach ($page_features as $ii => $section_features) {
				foreach ($section_features as $iii => $actions) {
					if (strpos(current($actions), $feature_name . '\\') === 0) {
						unset($this->_data[$controller_regex][$ii][$iii]);
					} 
				}
			}
		}
	}

	public function run($view_data) {
		// create DOM object
		libxml_use_internal_errors(true);
		$content	= stream_get_contents($view_data['content']);
		$doc		= new \DOMDocument();
		// workaround to force DOMDocument to work with UTF-8
		$doc->loadHtml('<?xml encoding="UTF-8">' . $content);
		libxml_use_internal_errors(false);
		
		$xpath = new \DOMXPath($doc);

		// we have to use $page_references as a reference here so it shows changes on this->_data if we have modified it with delete()
		// http://nikic.github.io/2011/11/11/PHP-Internals-When-does-foreach-copy.html
		foreach ($this->_data as $controller_regex => &$page_features) {
			if (!preg_match('~^'.$controller_regex.'$~', $this->_classname)) continue;

			foreach ($page_features as $xpath_query => $section_features) {
				$nodelist = $xpath->query($xpath_query);

				foreach ($nodelist as $node) {
					foreach ($section_features as $actions) {
						foreach ($actions as $action => $class) {
							$namespace = preg_replace('~[^\\\\]+$~', '', $class);
							$classname = preg_replace('~.+\\\\~', '', $class);

							$frontcontroller = new Core\Frontcontroller;
							$data = $frontcontroller->run('\\app\\features\\' . $namespace, $classname, false);

							$fragment = $doc->createDocumentFragment();
							$fragment->appendXML(stream_get_contents($data['content']));
							
							if ($action === 'prepend') {
								$node->insertBefore($fragment, $node->firstChild);
							} elseif ($action === 'append') {
								$node->appendChild($fragment);
							} elseif ($action === 'before') {
								$node->parentNode->insertBefore($fragment, $node);
							} elseif ($action === 'after') {
								$node->parentNode->appendChild($fragment);
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

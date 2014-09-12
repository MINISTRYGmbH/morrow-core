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
* This class allows to simply modify an HTML source.
* 
* It allows the use of CSS Selectors so it should be really simple to specify an HTML element.
* Kepp in mind that your HTML markup has to be valid otherwise you will get errors.
*
* Example
* -------
* 
* ~~~{.php}
* // ... Default controller code
*
* $dom = Factory::load('DOM', $html_source);
* 
* // Add a headline to the beginning of the page
* $dom->prepend('html', '<h1>Headline</h1>');
*
* // Add an arrow to all links in the sidebar
* $dom->prepend('#sidebar a', '<span>&#187;</span> ');
* 
* // Delete all links starting with "http://"
* $dom->delete('a[href^="http://"]');
*
* $new_source = $dom->get();
*
* // ... Default controller code
* ~~~
*
* It is also possible to use XPATH 1.0 selectors for more specific purposes.
* Just add "xpath:" in front of your selector.
*
* ~~~{.php}
* // ... Default controller code
*
* $dom = Factory::load('DOM', $html_source);
* 
* // Delete all Links with the text "Sitemap"
* $dom->delete('xpath://a[text()="Sitemap"]');
*
* // Delete all "li" which have a link inside
* $dom->delete('xpath://li[a]');
*
* $new_source = $dom->get();
*
* // ... Default controller code
* ~~~
*/
class DOM extends \DOMDocument {
	/**
	 * The currently requested URL.
	 * @var instance $_xpath
	 */
	protected $_xpath;

	/**
	 * Set the HTML source you want to work with.
	 *
	 * @param  string $html_string The HTML source to modify.
	 * @return null
	 */
	public function set($html_string) {
		$libxml_use_internal_errors = libxml_use_internal_errors(true);
		// workaround to handle UTF-8
		$this->loadHtml('<?xml encoding="UTF-8">' . $html_string);
		libxml_use_internal_errors($libxml_use_internal_errors);
		$this->_xpath = new \DOMXPath($this);
	}

	/**
	 * Insert content to the beginning of each element in the set of elements specified by the `$css_selector`.
	 *
	 * @param  string $css_selector The CSS selector used to get the desired elements.
	 * @param  string $content The HTML source that should be added.
	 * @return integer Returns how many existing elements were changed.
	 */
	public function prepend($css_selector, $content) {
		return $this->_modify('prepend', $css_selector, $content);
	}

	/**
	 * Insert content to the end of each element in the set of elements specified by the `$css_selector`.
	 *
	 * @param  string $css_selector The CSS selector used to get the desired elements.
	 * @param  string $content The HTML source that should be added.
	 * @return integer Returns how many existing elements were changed.
	 */
	public function append($css_selector, $content) {
		return $this->_modify('append', $css_selector, $content);
	}

	/**
	 * Insert content before each element in the set of elements specified by the `$css_selector`.
	 *
	 * @param  string $css_selector The CSS selector used to get the desired elements.
	 * @param  string $content The HTML source that should be added.
	 * @return integer Returns how many existing elements were changed.
	 */
	public function before($css_selector, $content) {
		return $this->_modify('before', $css_selector, $content);
	}

	/**
	 * Insert content after each element in the set of elements specified by the `$css_selector`.
	 *
	 * @param  string $css_selector The CSS selector used to get the desired elements.
	 * @param  string $content The HTML source that should be added.
	 * @return integer Returns how many existing elements were changed.
	 */
	public function after($css_selector, $content) {
		return $this->_modify('after', $css_selector, $content);
	}

	/**
	 * Removes all matched elements from the HTML source.
	 *
	 * @param  string $css_selector The CSS selector used to get the desired elements.
	 * @return integer Returns how many existing elements were deleted.
	 */
	public function delete($css_selector) {
		$xpath_selector	= $this->_css_to_xpath_selector($css_selector);
		$nodelist		= $this->_xpath->query($xpath_selector);
		if ($nodelist->item(0) === null) return 0;

		$counter = 0;
		foreach ($nodelist as $node) {
			$counter++;
			$node->parentNode->removeChild($node);
		}

		return $counter;
	}

	/**
	 * Returns the current state of the HTML source.
	 *
	 * @return string The HTML source.
	 */
	public function get() {
		// remove XML prolog
		foreach ($this->childNodes as $item) {
			if ($item->nodeType == XML_PI_NODE) {
				$this->removeChild($item);
				break;
			}
		}

		return $this->saveHtml();
	}

	/**
	 * Modifies content in the set of elements specified by the `$css_selector`. Used by `append()`, `prepend()`, `after()` and `before()`.
	 *
	 * @param  string $action The action that should be processed (`append`, `prepend`, `after` or `before`).
	 * @param  string $css_selector The CSS selector used to get the desired elements.
	 * @param  string $content The HTML source that should be added.
	 * @return integer Returns how many existing elements were deleted.
	 */
	protected function _modify($action, $css_selector, $content) {
		if (empty($content)) return 0;

		$xpath_selector	= $this->_css_to_xpath_selector($css_selector);
		$nodelist		= $this->_xpath->query($xpath_selector);
		if ($nodelist->item(0) === null) return 0;

		$counter = 0;
		foreach ($nodelist as $node) {
			$counter++;
			$fragment = $this->createDocumentFragment();
			$fragment->appendXML($content);

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

		return $counter;
	}

	/**
	 * Converts a CSS selector to a XPATH 1.0 selector. It does not support all CSS selectors, but the most.
	 *
	 * @param  string $css_selector The CSS selector to convert.
	 * @return string The corresponding XPATH selector.
	 */
	protected function _css_to_xpath_selector($css_selector) {
		$xpath = $css_selector;
		
		// if there was passed a xpath expression
		if (strpos($xpath, 'xpath:') !== false) return substr($xpath, 6);

		// child elements
		$xpath = preg_replace('/([a-z0-9_-])\s+([a-z0-9_-])/', '$1//$2', $xpath);	

		// direct child elements ( > )
		$xpath = preg_replace('/\s*>\s*/', '/', $xpath);

		// element with attribute
		$xpath = preg_replace('/\[([^\]]+)\]/', '[@$1]', $xpath);

		// element with starts-with selector
		$xpath = preg_replace('/\[@(.+)\^=(.+)\]/i', '[starts-with(@$1, $2)]', $xpath);

		// element with contains selector
		$xpath = preg_replace('/\[@(.+)\*=(.+)\]/i', '[contains(@$1, $2)]', $xpath);

		// element by id
		$xpath = preg_replace('/#([a-z0-9_-]+)/', '*[@id="$1"]', $xpath);

		// elements by class
		$xpath = preg_replace('/\.([a-z0-9_-]+)/', '*[contains(concat(" ", @class, " ")," $1 ")]', $xpath);

		// cleanup such elements div*
		$xpath = preg_replace('/([a-z0-9_-]+)\*/', '$1', $xpath);

		// first-child
		$xpath = preg_replace('/:first-child/', '[1]', $xpath);

		// following elements ( + )
		$xpath = preg_replace('/\s*\+\s*/', '/following-sibling::*[1]/self::', $xpath);

		return '//' . $xpath;
	}
}
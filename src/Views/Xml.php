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


namespace Morrow\Views;

/**
 * With this view handler it is possible to generate and output valid XML files.
 * 
 * There are some special things you should keep in mind (take a look at the example):
 * 
 *   * **Equal named tags:** Use a blank to create equal named tags. All characters behind the blank will get stripped.
 *   * **Attributes:** add attributes by prefixing the target tag with a colon.
 *   * **Numeric indices:** Numeric Indices will be prefixed by "entry" to generate a valid tag.
 *
 * All public members of a view handler are changeable in the Controller by `\Morrow\View->setProperty($member, $value)`;
 *
 * Example
 * --------
 * 
 * ~~~{.php}
 * // ... Controller code
 * 
 * // Equal named tags
 * $data['frame']['section 1']['headline']  = 'Example';
 * $data['frame']['section 2']['copy']      = 'Example text';
 *  
 * // Numeric indices
 * $data['frame'][0]['headline']            = 'Example';
 * $data['frame'][0]['copy']                = 'Example text';
 *  
 * // Attributes
 * $data['frame']['section2']['copy1']      = 'This is a "<a>-link</a>';
 * $data['frame'][':section2']['param_key'] = 'param_value';
 *  
 * $this->view->setHandler('Xml');
 * $this->view->setContent('content', $data);
 *
 * // ... Controller code
 * ~~~
 */
class Xml extends AbstractView {
	/**
	 * Changes the standard mimetype of the view handler. Possible values are `text/html`, `application/xml` and so on.
	 * @var string $mimetype
	 */
	public $mimetype	= 'application/xml';
	
	/**
	 * Changes the standard mimetype of the view handler. Possible values are `text/html`, `application/xml` and so on.
	 * @var string $mimetype
	 */
	public $numeric_prefix	= 'entry';

	/**
	 * The parameter used to create equal named tags. All characters behind this parameter will get stripped.
	 * @var string $strip_tag
	 */
	public $strip_tag		= ' ';

	/**
	 * The parameter used to create attributes. Prefix the target node with this parameter.
	 * @var string $attribute_tag
	 */
	public $attribute_tag	= ':';

	/**
	 * You always have to define this method.
	 * @param   array $content Parameters that were passed to \Morrow\View->setContent().
	 * @param   handle $handle  The stream handle you have to write your created content to.
	 * @return  string  Should return the rendered content.
	 * @hidden
	 */
	public function getOutput($content, $handle) {
		fwrite($handle, '<?xml version="1.0" encoding="'.$this->charset.'"?>');
		
		// count subkeys. if there is more than one we have to generate an auto container
		// we have to take care of attributes
		$count = 0;
		foreach ($content['content'] as $key => $item) {
			if ($key{0} != ':') $count++;
		}
		
		if ($count != 1) $content['content'] = array('auto-container' => $content['content']);
		fwrite($handle, $this -> _outputXML($content['content']));
		return $handle;
	}

	/**
	 * You always have to define this method.
	 * @param   array $input Parameters that were passed to \Morrow\View->setContent().
	 * @return  string  Returns the rendered XML.
	 */
	protected function _outputXML($input) {
		$attributes = '';
		$output = '';
		
		// get attributes
		foreach ($input as $key => $value) {
			$attribute = array();

			if ($key[0] == $this->attribute_tag) {
				$newkey = substr($key, 1);
				foreach ($value as $pkey => $pvalue) {
					$attribute[] = $pkey.'="'.htmlspecialchars($pvalue).'"';
				}
				$attributes[$newkey] = implode(' ', $attribute);
				unset($input[$key]);
			}
		}

		// run through all keys
		foreach ($input as $key => $value) {
			// set attribs
			if (isset($attributes[$key])) $attr = ' '.$attributes[$key]; else $attr = '';

			// process numeric indices
			if (is_numeric($key)) $key = $this->numeric_prefix.$key;

			if (is_array($value) && count($value) > 0) {
				$body = $this -> _outputXML($value);
			} else {
				// process value
				if (is_array($value)) $value = '';
				elseif ($value === true) $value = 'true';
				elseif ($value === false) $value = 'false';
				elseif ($value === null) $value = 'null';
				else {
					$value = trim(stripslashes($value));

					if (strpos($value, '<') !== false OR strpos($value, '>') !== false OR strpos($value, '&') !== false) {
						$value = '<![CDATA['.$value.']]>';
					}
				}

				$body = $value;
			}

			// process equal named tags
			$key = explode($this->strip_tag, $key);
			$key = $key[0];

			// Start-Tag setzen
			if (!isset($output)) $output = '';
			$output .= '<'.$key.$attr.'>';
			$output .= $body;
			// End-Tag setzen
			$output .= '</'.$key.'>';
		}
		return $output;
	}
}

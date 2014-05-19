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
 * The Form class provides several methods to output the HTML of form elements.
 * 
 * It works together with the validator class.
 * 
 * Examples
 * --------
 * 
 * ~~~{.php}
 * // ... Controller code
 * 
 *
 * // ... Controller code
 * ~~~
 */
class Form {
	/**
	 * Contains all user form input.
	 * @var array $_input
	 */
	protected $_input;

	/**
	 * Contains the errors for all fields in the input `$_input` array.
	 * @var array $_errors
	 */
	protected $_errors;

	/**
	 * The default CSS class for errors.
	 * @var string $error_class
	 */
	public static $error_class = 'error';
	
	/**
	 * Contains how often this class was initialized.
	 * @var integer $form_counter
	 */
	public static $form_counter = 0;

	/**
	 * The prefix that is used for the ids if the form elements.
	 * @var string $_form_prefix
	 */
	protected $_form_prefix;

	/**
	 * Validates an array of input data against a set of rules.
	 * @param	array	$input	An associative array with the data the user entered on last submit.
	 * @param	array	$errors	An associative array with the errors for the `$input`.
	 */
	public function __construct($input, $errors) {
		$this->_input	= $input;
		$this->_errors	= $errors;

		$this->_form_prefix = 'form' . ++self::$form_counter . '_';
	}

	/**
	 * Outputs the HTML for a &lt;label&gt;.
	 * @param	string	$name	The name of the HTML field.
	 * @param	string	$value	The content of the label.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @return	string	The HTML string.
	 */
	public function label($name, $value, $attributes = array()) {
		list($attributes) = $this->_prepare($name, $attributes);
		
		$attributes['for'] = $attributes['id'];
		unset($attributes['id']);
		unset($attributes['name']);
		$attributes = $this->_arrayToAttributesString($attributes);
		$value = $this->_escape($value);

		return "<label{$attributes}>{$value}</label>";
	}

	/**
	 * Outputs the HTML for an error &lt;span&gt;.
	 * @param	string	$name	The name of the HTML field this error is for.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @return	string	The HTML string.
	 */
	public function error($name, $attributes = array()) {
		if (!isset($this->_errors[$name])) return '';

		list($attributes) = $this->_prepare($name, $attributes);

		$attributes['data-error-for'] = $attributes['id'];
		unset($attributes['id']);
		$attributes = $this->_arrayToAttributesString($attributes);

		// show only the first error. The others could be consequential error
		$value = $this->_escape(current($this->_errors[$name]));

		return "<span{$attributes}>{$value}</span>";
	}

	/**
	 * Outputs the HTML for a &lt;hidden&gt; field.
	 * @param	string	$name	The name of the HTML field.
	 * @param	string	$value	The value of the HTML field.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @return	string	The HTML string.
	 */
	public function hidden($name, $value, $attributes = array()) {
		return $this->_getDefaultInputHtml('hidden', $name, $attributes, $value);
	}

	/**
	 * Outputs the HTML for an &lt;input&gt; field.
	 * @param	string	$name	The name of the HTML field.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @return	string	The HTML string.
	 */
	public function input($name, $attributes = array()) {
		return $this->_getDefaultInputHtml('text', $name, $attributes);
	}

	/**
	 * Outputs the HTML for an &lt;input type="text"&gt; field.
	 * @param	string	$name	The name of the HTML field.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @return	string	The HTML string.
	 */
	public function text($name, $attributes = array()) {
		return $this->_getDefaultInputHtml('text', $name, $attributes);
	}

	/**
	 * Outputs the HTML for an &lt;input type="password"&gt; field.
	 * @param	string	$name	The name of the HTML field.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @return	string	The HTML string.
	 */
	public function password($name, $attributes = array()) {
		return $this->_getDefaultInputHtml('password', $name, $attributes);
	}

	/**
	 * Outputs the HTML for an &lt;input type="file"&gt; field.
	 * @param	string	$name	The name of the HTML field.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @return	string	The HTML string.
	 */
	public function file($name, $attributes = array()) {
		return $this->_getDefaultInputHtml('file', $name, $attributes);
	}

	/**
	 * Outputs the HTML for a &lt;textarea&gt; field.
	 * @param	string	$name	The name of the HTML field.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @return	string	The HTML string.
	 */
	public function textarea($name, $attributes = array()) {
		list($attributes, $value)	= $this->_prepare($name, $attributes);
		$attributes					= $this->_arrayToAttributesString($attributes);
		$value						= $this->_escape($value);
		return "<textarea{$attributes}>{$value}</textarea>";
	}

	/**
	 * Outputs the HTML for an &lt;input type="checkbox"&gt; field.
	 * @param	string	$name	The name of the HTML field.
	 * @param	string	$value	The value of the HTML field.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @return	string	The HTML string.
	 */
	public function checkbox($name, $value, $attributes = array()) {
		return $this->_getDefaultInputHtml('checkbox', $name, $attributes, $value);
	}

	/**
	 * Outputs the HTML for an &lt;input type="radio"&gt; field.
	 * @param	string	$name	The name of the HTML field.
	 * @param	string	$value	The value of the HTML field.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @return	string	The HTML string.
	 */
	public function radio($name, $value, $attributes = array()) {
		if (isset($this->_input[$name])) {
			if ($this->_input[$name] === $value) $attributes['checked'] = 'checked';
			else unset($attributes['checked']);
		}

		return $this->_getDefaultInputHtml('radio', $name, $attributes, $value);
	}
	
	/**
	 * Outputs the HTML for a &lt;select&gt; field.
	 * @param	string	$name	The name of the HTML field.
	 * @param	string	$values	An associative array with the <option>s.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @return	string	The HTML string.
	 */
	public function select($name, $values, $attributes = array()) {
		list($attributes, $selected_value) = $this->_prepare($name, $attributes);
		$attributes = $this->_arrayToAttributesString($attributes);

		$content = "<select{$attributes}>";
		$content .= $this->select_option($values, $selected_value);
		$content .= "</select>";
		return $content;
	}

	/**
	 * Outputs the HTML for an &lt;option&gt; field.
	 * @param	array	$values	The options to output.
	 * @param	mixed	$selected_value	A string or an array with the selected values that should be selected.
	 * @return	string	The HTML string.
	 */
	public function select_option($values, $selected_value) {
		$content = '';

		foreach ($values as $value => $title) {
			if (is_array($title)) {
				$label		= $this->_escape($value);
				$content	.= "<optgroup label=\"{$label}\">";
				$content	.= $this->select_option($title, $selected_value);
				$content	.= "</optgroup>";
			} else {
				$value		= $this->_escape($value);
				$title		= $this->_escape($title);
				
				$selected = '';
				if (is_scalar($selected_value) && $value == $selected_value) {
					$selected = ' selected="selected"';
				} elseif (is_array($selected_value) && in_array($value, $selected_value)) {
					$selected = ' selected="selected"';
				}

				$content	.= "<option value=\"{$value}\"{$selected}>{$title}</option>";
			}
		}

		return $content;
	}

	/**
	 * Sets the name of the form. Used as form prefix.
	 * @param	string	$name	The name of the form.
	 */
	public function setName($name) {
		$this->_form_prefix = $name . '_';
	}

	/**
	 * Method to output an &lt;input&gt; field. Used by many other field methods.
	 * @param	string	$type	The input type like "text", "radio" etc.
	 * @param	string	$name	The name of the HTML field.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @param	mixed	$value_fixed	A string that overrides the value of the user.
	 * @return	string	The HTML string.
	 */
	protected function _getDefaultInputHtml($type, $name, $attributes, $value_fixed = null) {
		list($attributes, $value) = $this->_prepare($name, $attributes);
		
		$attributes['type']		= !isset($attributes['type']) ? $type : $attributes['type'];
		
		// on type "file" we would have an array (and "file" does not need a value)
		if (is_scalar($value))		$attributes['value'] = $this->_escape($value);
		if ($value_fixed !== null)	$attributes['value'] = $this->_escape($value_fixed);

		// for checkboxes
		if ($type === 'checkbox') {
			if (is_array($value) && in_array($value_fixed, $value)) {
				$attributes['checked'] = 'checked';
			} elseif ($value_fixed == $value) {
				$attributes['checked'] = 'checked';
			}
		}

		$attributes = $this->_arrayToAttributesString($attributes);

		return "<input{$attributes} />";
	}

	/**
	 * Used to create attributes all input fields must have.
	 * @param	string	$name	The name of the HTML field.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @return	string	An array with the prepared attributes and the value.
	 */
	protected function _prepare($name, $attributes) {
		// add error class ...
		if (isset($this->_errors[$name])) {
			if (isset($attributes['class'])) {
				$attributes['class'] .= ' ' . self::$error_class;
			} else {
				$attributes['class'] = self::$error_class;
			}
		}

		// add name
		if (!isset($attributes['name'])) {
			$attributes['name'] = $name;
		}

		// add id
		$attributes['id']	= $this->_form_prefix . (isset($attributes['id']) ? $attributes['id'] : str_replace(array('[', ']'), '', $name));

		// add value
		// the name could be in array syntax like "field[]" but in the input array the field has the name "field"
		$input_key	= preg_replace('/\[[^]]*\]/', '', $attributes['name']);
		$value		= isset($this->_input[$input_key]) ? $this->_input[$input_key] : '';
		
		return array($attributes, $value);
	}

	/**
	 * Escaped string for use in an html form field.
	 * @param	string	$string	The value to escape.
	 * @return	string	The escaped string.
	 */
	protected function _escape($string) {
		return htmlspecialchars($string, ENT_QUOTES, 'utf-8');
	}

	/**
	 * Creates an attribute string from an associative array.
	 * @param	array	$attributes	An associative array with attributes that should be used with the element.
	 * @return	string	The attributes string for use in an HTML form field.
	 */
	protected function _arrayToAttributesString(array $attributes) {
		$attributes_string = '';
		foreach ($attributes as $key => $value) {
			$attributes_string .= ' ' . $key . '="' . $this->_escape($value) . '"';
		}
		return $attributes_string;
	}
}

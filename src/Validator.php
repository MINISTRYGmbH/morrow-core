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
 * The Validator class provides several rules for validating data.
 * 
 * If you write a validator keep in mind that the validator should never throw an exception if `$input` is a scalar or an array.
 * Because if you validate data coming from a web client, data can only be a string or an array.
 *
 * Dot Syntax
 * ----------
 * 
 * This class works with the extended dot syntax. So it is possible to use keys like `email.0` and `email.1` as keys in your rules.
 * This way it is possible to validate e.g. image uploads or multi select fields.
 * 
 * Examples
 * --------
 * 
 * ~~~{.php}
 * // ... Controller code
 * 
 * // optional: we add a integer validator
 * $this->validator->add('equal', function($input, $value, $compare_value) {
 * 	return is_integer($value) ? $value === $compare_value : false;
 * }, 'This field must be an integer with the value "%s"');
 * 
 * // optional: we want a different error message for the "minlength" validator
 * $this->validator->setMessages(array(
 * 	'minlength'			=> 'This field should have at least %s characters.',
 * ));
 * 
 * // optional: we want always the same validator rules for a username
 * // so do this in the DefaultController
 * $this->validator->addComposition('username', array(
 * 	'regex'			=> '/[a-z0-9-_]{8,}/i',
 * ));
 * 
 * // now let us validate the input data
 * $rules =  array(
 * 	'username'			=> array('reauired', 'composition' => 'username'),
 * 	'email'				=> array('email'),
 * 	'password'			=> array('required', 'minlength' => 8),
 * 	'repeat_password'	=> array('required', 'same' => 'password'),
 * 	'captcha'			=> array('required', 'equal' => $this->session->get('captcha')),
 * );
 * 
 * $input = $this->validator->filter($this->input->get(), $rules, $errors);
 *
 * // ... Controller code
 * ~~~
 *
 * Be creative with the existing validators
 * ----------------------------------------
 * 
 * If you want to check if a person is at least 18 years old, you could use this:
 * 
 * ~~~{.php}
 * $rules =  array(
 * 	'birthdate' => array('required', 'date' => '%Y-%m-%d', 'before' => '-18 years'),
 * );
 * ~~~
 * 
 * Or think of validating an image upload:
 * 
 * ~~~{.php}
 * $rules =  array(
 * 	'file'			=> array('upload'),
 * 	'file.tmp_name'	=> array('invalidates' => 'file', 'image' => array('jpg')),			// the image has to be a JPG
 * 	'file.name'		=> array('invalidates' => 'file', 'regex' => '/^[a-z0-9_.]+$/'),	// only specific characters allowed
 * 	'file.size'		=> array('invalidates' => 'file', 'number', 'max' => 1024 * 2000),	// no more than 2 MB
 * );
 * ~~~
 * 
 * Or think of a `<select>` box with groups. You can throw the same nested array into the `in_keys` validator as you do with `$form->select(...)` in your template.
 * 
 * ~~~{.php}
 *
 * $states = array(
 * 	'States with A' = array(
 * 		'alabama'	=> 'Alabama',
 * 		'arkansas'	=> 'Arkansas',
 * 	),
 * 	'States with T' = array(
 * 		'tennessee'	=> 'Tennessee',
 * 		'texas'		=> 'Texas',
 * 	),
 * );
 * 
 * $rules =  array(
 * 	'states'		=> array('in_keys' => $states),
 * );
 * ~~~
 * 
 * ### Be careful
 * 
 * An array sub key invalidates the complete array. If you validate array sub keys you will find the error messages in the main key, so in the example above it will be the key `file`.
 * This is also important if you validate checkboxes that have an array style name like `name[]` in the HTML source.
 * 
 * Shipped validators
 * -------------------
 *
 * Validator     | Prameter                | type    | Description
 * ---------     | ---------               | -----   | ------------
 * `composition` | `$name`                 | string  | Check against all fields which are defined for the given composition name added by `addComposition()`.
 * `invalidates` | `$field`                | string  | Use this if you want the error be appended to the errors of another field.
 * `required`    | `$fields = array()`     | array   | Defines the field as required. If you pass the optional associative array `$fields`, the field will only get required if all fields (keys) in the array have the stated values.
 * `equal`       | `$compare_value`        | mixed   | Compares the field to a given value to compare.
 * `same`        | `$compare_field`        | string  | Compares the field to the value of a different field in the input array.
 * `array`       | `$validators = array()` |         | Returns `true` if the field is an array. If you pass `$validators` all values are checked against them. If just one value fails the `array` validator fails. Useful if you have a `<select>` with the attribute `multiple="multiple"`.
 * `number`      |                         |         | Returns `true` if the field is decimal number.
 * `numeric`     |                         |         | Returns `true` if the field is a number or a decimal number.
 * `min`         | `$min`                  | numeric | Returns `true` if the field is greater than `$min`.
 * `max`         | `$max`                  | numeric | Returns `true` if the field is lower than `$max`.
 * `minlength`   | `$minlength`            | integer | Returns `true` if the length of the field is greater than `$min`.
 * `maxlength`   | `$maxlength`            | integer | Returns `true` if the length of the field is lower than `$min`.
 * `regex`       | `$regex`                | string  | Returns `true` if the regex matches the field. 
 * `in`          | `$in`                   | array   | Returns `true` if the field is in the set of values.
 * `in_keys`     | `$in`                   | array   | Returns `true` if the field is one of the keys in the given array. The validator iterates the `$in` array recursively and does not accept keys whose value is itself an array. This is useful to validate nested arrays like those you can use for a `<select>` form element with the \Morrow\Form class.
 * `upload`      |                         |         | Returns `true` if the fields contains a valid file upload array.
 * `image`       | `$types`                | array   | Returns `true` if the imagetype is one of the given (`jpg`, `gif` or `png`).
 * `width`       | `$width`                | integer | Returns `true` if the image has the given width.
 * `height`      | `$height`               | integer | Returns `true` if the image has the given height.
 * `email`       |                         |         | Returns `true` if the email address is valid.
 * `url`         | `$schemes`              | array   | Returns `true` if the scheme of the url is one of the given, eg. `array('http', 'https')`.
 * `ip`          | `$flags = array()`      | array   | Returns `true` if the IP is valid. You can pass the following parameters to specify the requirements: `ipv4` (IP must be an IPV4 address), `ipv6` (IP must be an IPV6 address), `private` (IP can be a private address like 192.168.*) and `reserved` (IP can be a reserved address like 100.64.0.0/10). Default is `ipv4`.
 * `date`        | `$date_format`          | string  | Returns `true` if the date is valid and the date could be successfully checked against `$date_format` (in `\DateTime::createFromFormat` format).
 * `before`      | `$before`               | string  | Returns `true` if the date is before the given date. Both dates has to be passed in a format `strtotime` is able to read. 
 * `after`       | `$after`                | string  | Returns `true` if the date is after the given date. Both dates has to be passed in a format `strtotime` is able to read. 
 */
class Validator extends Core\Base {
	/**
	 * Contains all user defined validators set by add().
	 * @var array $_callbacks
	 */
	protected $_callbacks = array();

	/**
	 * Contains all user defined compositions set by addComposition().
	 * @var array $_compositions
	 */
	protected $_compositions = array();

	/**
	 * Contains the error messages for all validators.
	 * @var array $_messages
	 */
	protected $_messages = array();
	
	/**
	 * Initializes the class and sets default error messages.
	 */
	public function __construct() {
		$this->_messages = array(
			'required'	=> 'VALDIDATOR_REQUIRED',
			'equal'		=> 'VALDIDATOR_EQUAL%s',
			'same'		=> 'VALDIDATOR_SAME%s',
			'array'		=> 'VALDIDATOR_ARRAY',
			'number'	=> 'VALDIDATOR_NUMBER',
			'numeric'	=> 'VALDIDATOR_NUMERIC',
			'min'		=> 'VALDIDATOR_MIN%s',
			'max'		=> 'VALDIDATOR_MAX%s',
			'minlength'	=> 'VALDIDATOR_MINLENGTH%s',
			'maxlength'	=> 'VALDIDATOR_MAXLENGTH%s',
			'regex'		=> 'VALDIDATOR_REGEX',
			'in'		=> 'VALDIDATOR_IN',
			'in_keys'	=> 'VALDIDATOR_INKEYS',
			'image'		=> 'VALDIDATOR_IMAGE',
			'width'		=> 'VALDIDATOR_WIDTH%s',
			'height'	=> 'VALDIDATOR_HEIGHT%s',
			'email'		=> 'VALDIDATOR_EMAIL',
			'url'		=> 'VALDIDATOR_URL',
			'ip'		=> 'VALDIDATOR_IP',
			'date'		=> 'VALDIDATOR_DATE%s',
			'before'	=> 'VALDIDATOR_BEFORE%s',
			'after'		=> 'VALDIDATOR_AFTER%s',
			'upload'	=> 'VALDIDATOR_UPLOAD',
		);
	}

	/**
	 * Validates an array of input data against a set of rules.
	 * @param	array	$input	An associative array with the data that should be validated.
	 * @param	array	$rules	The validation rules the the input data should be validated against.
	 * @param	array	$errors	Errors are written to this parameter.
	 * @param	boolean	$strict	If set to `true`, the function will return false if at least one field is not valid.
	 * @return 	mixed	Returns an array with all valid fields or `false` if `$strict` was set and at least one field was not valid.
	 */
	public function filter(array $input, array $rules, &$errors = array(), $strict = false) {
		$output		= array();
		$errors			= array();
		$return_null	= false;

		// iterate all fields
		foreach ($rules as $identifier => $rules_array) {
			// get value from input array
			$value = $this->arrayGet($input, $identifier);

			// extract compositions
			if (isset($rules_array['composition'])) {
				$rules_array = array_merge($this->_compositions[$identifier], $rules_array);
				unset($rules_array['composition']);
			}

			// handle invalidates
			$invalidates = null;
			if (isset($rules_array['invalidates'])) {
				$invalidates = $rules_array['invalidates'];
				unset($rules_array['invalidates']);
			}

			// rewrite rules to normal form (validator => value)
			foreach ($rules_array as $unknown_key => $unknown_value) {
				if (is_numeric($unknown_key)) {
					unset($rules_array[$unknown_key]);
					$rules_array[$unknown_value] = null;
				} else {
					$rules_array[$unknown_key] = array($unknown_value);
				}
			}

			// if we get an empty file upload array or an empty field we need not to check the validators if the field is also not required
			if (!array_key_exists('required', $rules_array) && ($value == '' || is_array($value) && isset($value['error']) && $value['error'] == 4)) {
				$this->arraySet($output, $identifier, $value);
				continue;
			}

			// only if all rules are true we add the value to the $output array
			$is_valid = true;

			// iterate all rules
			foreach ($rules_array as $name => $params) {
				$method	= '_validator_' . $name;

				$callback = null;
				// does the user wants to use a predefined validator
				if (method_exists($this, $method)) $callback = array($this, $method);
				// or his own closure
				if (!isset($callback) && isset($this->_callbacks[$name])) $callback = $this->_callbacks[$name];
				// we did not found any callable validator
				if (!isset($callback)) throw new \Exception(__CLASS__ . ': Validator "'.$name.'" does not exist.');

				if (isset($params)) $result		= call_user_func_array($callback, array_merge(array($input, $value), $params));
				else $result					= call_user_func($callback, $input, $value);

				if ($result === false) {
					// add the error message
					if (!is_scalar($params)) $params	= json_encode($params);

					// this error should be appended to a different field
					if ($invalidates !== null) $identifier = $invalidates;
					
					$errors[$identifier][$name]			= vsprintf($this->_messages[$name], (string)$params);

					// one false result is enough to set a field as not valid
					$is_valid = false;
					
					// if there is at least one error we will return null in strict mode
					// but we don't return null immediately because we want all errors for all fields 
					if ($strict) $return_null = true;
				}
			}

			// we don't have to add the field to the output if we already know that we will return null
			if ($is_valid && !$return_null) $this->arraySet($output, $identifier, $value);
		}

		if ($return_null) return null;
		return $output;
	}

	/**
	 * Adds a user defined validator.
	 * @param	string	$name	The name of the validator.
	 * @param	callable	$callback	A valid PHP callback like `array('OBJECT', 'METHOD')`, `array($obj, 'METHOD')` or a n anonymous function like `function($input, $value){}`. The method gets at least two parameters passed. An array of all `$input` parameters and the `$value` to validate. Other parameters are the passed parameters.
	 * @param	string	$error_message	The error message that get all parameters passed via `sprintf`, so you can use `%s` and other `sprintf` replacements.
	 */
	public function add($name, $callback, $error_message) {
		$this->_callbacks[$name] = $callback;
		$this->_messages[$name] = $error_message;
	}

	/**
	 * Adds a composition of already existing validators.
	 * @param	string	$name	The name of the validator.
	 * @param	array	$composition	An array of validator rules.
	 */
	public function addComposition($name, array $composition) {
		$this->_compositions[$name] = $composition;
	}

	/**
	 * Adds user defined error messages.
	 * @param	array	$messages	An associative array with the error messages. Parameters are passed via `sprintf`, so you can use `%s` and other `sprintf` replacements.
	 */
	public function setMessages($messages) {
		foreach ($messages as $name => $message) {
			$this->_messages[$name] = $message;
		}
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	array	$fields	If the fields in this array have the same values as in the input array this validator checks for required.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_required($input, $value, $fields = array()) {
		// check for values in fields to make this field required 
		$required = true;
		foreach ($fields as $key => $value) {
			if (!isset($input[$key]) || $input[$key] != $value) {
				$required = false;
				break;
			}
		}

		if ($required) return !is_null($value) && !empty($value);
		else return true;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	mixed	$compare_value	The value to compare `$value` with.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_equal($input, $value, $compare_value) {
		return $value == $compare_value;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	string	$compare_field	The field to compare `$value` with.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_same($input, $value, $compare_field) {
		if (!isset($input[$compare_field])) return false;
		return $value == $input[$compare_field];
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_array($input, $value, $validators = array()) {
		if (count($validators) === 0) return is_array($value);
		if (!is_array($value)) return false;

		foreach ($value as $item) {
			$result = $this->filter(array('item' => $item), array('item' => $validators), $errors, true);
			if ($result === null) return false;
		}
		return true;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_number($input, $value) {
		if (!is_string($value) && !is_integer($value) && !is_float($value)) return false;
		if (is_string($value)) $value = (int)$value;
		return is_integer($value);
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_numeric($input, $value) {
		return is_numeric($value);
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	integer	$min	The min value of `$value`.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_min($input, $value, $min) {
		if (!is_string($value) && !is_integer($value) && !is_float($value)) return false;
		if (is_string($value)) $value = (int)$value;
		return $value >= $min;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	integer	$max	The max value of `$value`.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_max($input, $value, $max) {
		if (!is_string($value) && !is_integer($value) && !is_float($value)) return false;
		if (is_string($value)) $value = (int)$value;
		return $value <= $max;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	integer	$min	The min length of `$value`.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_minlength($input, $value, $min) {
		if (!is_string($value)) return false;
		if (strlen($value) < $min) return false;
		return true;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	integer	$max	The max length of `$value`.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_maxlength($input, $value, $max) {
		if (!is_string($value)) return false;
		if (strlen($value) > $max) return false;
		return true;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	string	$regex	The regex to validate against.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_regex($input, $value, $regex) {
		if (!is_string($value) && !is_integer($value) && !is_float($value)) return false;
		if (!preg_match($regex, $value)) return false;
		return true;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	array	$in	An array of values `$value` has to exist in.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_in($input, $value, $in) {
		return in_array($value, $in);
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	array	$in	An array of keys `$value` has to exist in.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_in_keys($input, $value, $in) {
		$in_flat = array();
		array_walk_recursive($in, function($item, $key) use (&$in_flat) {
			$in_flat[] = $key;
		});

		return in_array($value, $in_flat);
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	array	$types	An array of filetypes the filetype of `$value` has to exist in.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_image($input, $value, $types) {
		if (!is_string($value)) return false;

		$types = array_map('strtolower', $types);
		$types		= array_intersect_key( array(
			'jpg'	=> IMAGETYPE_JPEG,
			'png'	=> IMAGETYPE_PNG,
			'gif'	=> IMAGETYPE_GIF,
		), array_flip($types));

		try {
			$imagesize = getimagesize($value);
			if (in_array($imagesize[2], $types)) {
				return true;
			}
		} catch (\Exception $e) {
			return false;
		}
		return false;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	integer	$width	The width the image must have.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_width($input, $value, $width) {
		if (!is_string($value)) return false;

		try {
			$imagesize = getimagesize($value);
			if ($imagesize[0] == $width) return true;
			return false;
		} catch (\Exception $e) {
			return false;
		}
		return false;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	integer	$height	The height the image must have.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_height($input, $value, $height) {
		if (!is_string($value)) return false;

		try {
			$imagesize = getimagesize($value);
			if ($imagesize[1] == $height) return true;
			return false;
		} catch (\Exception $e) {
			return false;
		}
		return false;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_email($input, $value) {
		if (!is_string($value)) return false;
		
		// syntax check
		$localpart = "[a-z0-9!#$%&'*+-/\=?^_`{|}~]"; // RFC 2822
		$domainpart = "[\.a-z0-9-]";
		if (!preg_match("=^$localpart+(\.$localpart+)*@($domainpart+\.$domainpart+)$=i", $value, $match)) {
			return false;
		}
		
		// dnscheck
		$host = $match[2];
		if (!(checkdnsrr($host, 'MX') || checkdnsrr($host, 'A'))) {
			return false;
		}
		
		return true;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	array	$schemes	An array of schemes the scheme of `$value` has to exist in.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_url($input, $value, $schemes) {
		if (!is_string($value)) return false;

		$schemes = array_map('strtolower', $schemes);

		$domainpart = "[\.a-z0-9-]";
		$regex = '~^(' . implode(':|', $schemes) . ':)?//(' . $domainpart . '+)~i';
		if (!preg_match($regex, $value, $match)) return false;

		// dnscheck
		$host = $match[2];
		if (!checkdnsrr($host, 'A')) {
			return false;
		}

		return true;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @return 	booolean	The result of the validation.
	 * @param	array	$flags	An array of falgs to control the behaviour of the validator.
	 */
	protected function _validator_ip($input, $value, $flags) {
		if (!is_string($value)) return false;

		$flags = array_flip(array_map('strtolower', $flags));

		// by default we don't want to have private or reserved ips
		$options = 0;
		if (!isset($flags['private'])) $options		|= FILTER_FLAG_NO_PRIV_RANGE;
		if (!isset($flags['reserved'])) $options	|= FILTER_FLAG_NO_RES_RANGE;

		// check for ipv4 and ipv6 compability
		if (isset($flags['ipv4']) && isset($flags['ipv6'])) $result = filter_var($value, FILTER_VALIDATE_IP, $options);
		elseif (isset($flags['ipv4'])) $result = filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | $options);
		elseif (isset($flags['ipv6'])) $result = filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | $options);
		else throw new \Exception(__METHOD__ . ': You have define the IP version ("ipv4" or "ipv6").');

		if ($result === false) return false;
		return true;
	}
		
	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	string	$date_format	The format to validate against.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_date($input, $value, $date_format) {
		if (!is_string($value)) return false;
		$date = \DateTime::createFromFormat($date_format, $value);
		return $date && str_replace('0', '', $date->format($date_format)) === str_replace('0', '', $value);
	}
	
	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	string	$before	The date (readable by `strtotime`) that has to be before `$value`.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_before($input, $value, $before) {
		if (!is_string($value) && !is_integer($value)) return false;
		$timestamp = is_string($value) ? strtotime($value) : $value;

		if (!is_string($before) && !is_integer($before)) return false;
		$timestamp2 = is_string($before) ? strtotime($before) : $before;

		if ($timestamp >= $timestamp2) return false;
		return true;
	}
	
	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @param	string	$after	The date (readable by `strtotime`) that has to be after `$value`.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_after($input, $value, $after) {
		if (!is_string($value) && !is_integer($value)) return false;
		$timestamp = is_string($value) ? strtotime($value) : $value;

		if (!is_string($after) && !is_integer($after)) return false;
		$timestamp2 = is_string($after) ? strtotime($after) : $after;

		if ($timestamp <= $timestamp2) return false;
		return true;
	}

	/**
	 * Look at the validator list.
	 * @param	array	$input	All input parameters that were passed to `filter()`.
	 * @param	mixed	$value	The input data to validate.
	 * @return 	booolean	The result of the validation.
	 */
	protected function _validator_upload($input, $value) {
		if (!is_array($value)) return false;
		if (count($value) !== 5) return false;

		$fields = array('name', 'type', 'tmp_name', 'error', 'size');
		if (array_keys($value) !== $fields) return false;

		if ($value['error'] !== '0') return false;
		
		if (!is_uploaded_file($value['tmp_name'])) return false;

		return true;
	}
}

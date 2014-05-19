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
* Allows to manipulate images with the GDlib.
*
* It is usually used to create thumbnails. For performance reasons there is a caching mechanism integrated. 
* 
* Examples
* ---------
*
* ~~~{.php}
* // ... Controller code
*  
* $params = array(
*     'height' => 100,
*     'width' => 100,
*     'sharpen' => true,
*     'type' => 'jpg',
* );
* $filename = 'very_big_image.jpg';
*
* // get path for the thumbed image
* $thumb = $this->image->get($filename, $params);
* Debug::dump($thumb);
*  
* // ... Controller code
* ~~~ 
* 
* ~~~{.php}
* // ... Controller code
*  
* $params = array(
*     'height' => 100,
*     'width' => 100,
*     'sharpen' => true,
*     'type' => 'png',
* );
* $filename = 'very_big_image.jpg';
*
* // get path for the thumbed image
* $thumb = $this->image->get($filename, $params);
*
* // get image object
* $img_obj = imagecreatefrompng($thumb);
*
* // ... work with image object
*
* // pass path to temporary image to save the new created image with the same path
* $thumb = $this->image->get($filename, $params, $thumb);
* Debug::dump($thumb);
*  
* // ... Controller code
* ~~~
*
* Possible parameters:
* 
* Type    | Keyname            | Default     | Description                                                                                                                                                                                                                         
* ------  | -----------        | ----------- | ---------------                                                                                                                                                                                                                     
* string  | `type`             | `jpg`       | The output file format. Possible values are `gif`, `jpg`, `png` and `png8`. `png8` only provides other results than `png` if `pngquant` or `pngnq` is installed.                                                                    
* integer | `width`            | `100`       | The width of your thumbnail. The height (if not set) will be automatically calculated.                                                                                                                                              
* integer | `height`           | `null`      | The height of your thumbnail. The width (if not set) will be automatically calculated.                                                                                                                                              
* integer | `shortside`        | `null`      | Set the shortest side of the image if width, height and longside is not set.                                                                                                                                                        
* integer | `longside`         | `null`      | Set the longest side of the image if width, height and shortside is not set.                                                                                                                                                        
* boolean | `sharpen`          | `true`      | Set to false if you do not want to sharpen the image.                                                                                                                                                                               
* boolean | `crop`             | `true`      | If set to true, image will be cropped in the center to destination width and height params, while keeping aspect ratio. Otherwise the image will get resized.                                                                       
* integer | `crop_position`    | `2`         | The cutout position if you use the crop function: Here are the positions:<br />&nbsp; 1<br />1 2 3<br />&nbsp; 3                                                                                                                    
* boolean | `trim`             | `false`     | If set to true, the image will get trimmed. The top left corner is used as whitespace reference.                                                                                                                                    
* integer | `trim_tolerance`   | `10`        | Defines how big the difference to the reference whitespace can be. Useful for JPGs with artefacts. Use 0 for high quality input or 20 and higher for heavily compressed input. 10 should work for most images with light artefacts. 
* boolean | `extrapolate`      | `true`      | Set to false if for example your source image is smaller than the calculated thumb and you do not want the image to get extraploated.                                                                                               
* string  | `overlay`          | `null`      | A PNG image which is used to create an overlay image. The position will be determined by "overlay_position". For performance reasons the overlay image will not be checked for modification.                                        
* integer | `overlay_position` | `9`         | The position of the overlay image. Possible is an integer from 1 to 9. Here are the positions:<br />1 2 3<br />4 5 6<br />7 8 9                                                                                                     
*
*/
class Image {
	/**
	 * The path to the cached images
	 * @var string $_cache_dir
	 */
	protected $_cache_dir;
	
	/**
	 * Checks the path to the cache dir and creates subdirectories.
	 * For every month a subdirectory is created. Old months will be deleted.
	 * 
	 * @param string $cache_dir The path to the cache dir.
	 * @return null
	 */
	public function __construct($cache_dir) {
		// params
		$counter = strftime('%m');

		// create cache dir if it does not exist
		if (!is_dir($cache_dir)) mkdir($cache_dir);
		
		// delete all folders that are not the current
		$files = scandir($cache_dir);
		foreach ($files as $file) {
			if ($file{0} == '.') continue;
			if (is_dir($cache_dir.$file) && $file != $counter) {
				$this->_rmdir_recurse( $cache_dir.$file );
			}
		}

		// create cache dir for the current cache counter
		$this->_cache_dir = $cache_dir . $counter . '/';
		if (!file_exists( $this->_cache_dir )) mkdir( $this->_cache_dir );
	}
	
	/**
	 * A function to sharpen an image.
	 * 
	 * @param resource $img_obj A GD image object.
	 * @return resource The sharpened image object. 
	 */
	protected function _sharpen($img_obj) { 
		imagesavealpha($img_obj, true); // preserve full alpha transparency for png files
		$matrix = array(array( -1, -1, -1 ), array( -1, 16, -1 ), array( -1, -1, -1 ));
		$divisor = array_sum(array_map('array_sum', $matrix));
		imageconvolution($img_obj, $matrix, $divisor, 0);
		return $img_obj;
	} 

	/**
	 * Validates the parameters the user passed in to define the image changes in get().
	 * 
	 * @param array $params Image parameters as associative array.
	 * @return array Returns the sanitized array.
	 */
	protected function _validateParams($params) {
		// all params with type and default_value
		$defaults['sharpen']			= array('boolean', true);
		$defaults['extrapolate']		= array('boolean', true);
		$defaults['crop']				= array('boolean', true);
		$defaults['crop_position']		= array('integer', 2);
		$defaults['overlay_position']	= array('integer', 9);
		$defaults['background']			= array('string', '#fff');
		$defaults['transparent']		= array('boolean', false);
		$defaults['trim']				= array('boolean', false);
		$defaults['trim_tolerance']		= array('integer', 10);
		$defaults['overlay']			= array('string', null);
		$defaults['shortside']			= array('integer', null);
		$defaults['longside']			= array('integer', null);
		$defaults['width']				= array('integer', null);
		$defaults['height']				= array('integer', null);

		// validate params
		foreach ($defaults as $key => $value) {
			if (isset($params[$key])) {
				// check type
				$type = gettype($params[$key]);
				if ($type !== $value[0]) {
					throw new \Exception(__CLASS__.': "'.$key.'" has to be of type "'.$value[0].'"');
					return;
				}
			} else {
				// set default
				if (!is_null($value[1])) $params[$key] = $value[1];
			}
		}
		
		// set background color
		$params['background']	= $this->_hex2RGB($params['background']);

		// set default width
		if (!isset($params['width']) AND
			!isset($params['height']) AND
			!isset($params['longside']) AND
			!isset($params['shortside'])
			)
			$params['width'] = 100;
		
		return $params;
	}
	
	/**
	 * Adds an image at a specified position. Useful to watermark an image or to show a zoom image.
	 * 
	 * @param resource $img_obj A GD image resource.
	 * @param resource $overlay_file The path to the image file that should be used as watermark image.
	 * @param resource $overlay_position The position (1-9) of the watermark image.
	 * @return resource The image object with the added watermark image. 
	 */
	protected function _addOverlayImage($img_obj, $overlay_file, $overlay_position) {
		$img_width  = imagesx($img_obj);
		$img_height = imagesy($img_obj);

		$overlay = $this->load($overlay_file);

		// preserve image transparancy
		imagealphablending($img_obj, true);

		$overlay_size = getimagesize($overlay_file);

		if ($overlay_position == '1') imagecopy($img_obj, $overlay, 0, 0, 0, 0, $overlay_size[0], $overlay_size[1]); // top left
		elseif ($overlay_position == '2') imagecopy($img_obj, $overlay, $img_width/2-$overlay_size[0]/2, 0, 0, 0, $overlay_size[0], $overlay_size[1]); // top center
		elseif ($overlay_position == '3') imagecopy($img_obj, $overlay, $img_width-$overlay_size[0], 0, 0, 0, $overlay_size[0], $overlay_size[1]); // top right
		elseif ($overlay_position == '4') imagecopy($img_obj, $overlay, 0, $img_height/2-$overlay_size[1]/2, 0, 0, $overlay_size[0], $overlay_size[1]); // center left
		elseif ($overlay_position == '5') imagecopy($img_obj, $overlay, $img_width/2-$overlay_size[0]/2, $img_height/2-$overlay_size[1]/2, 0, 0, $overlay_size[0], $overlay_size[1]); // center center
		elseif ($overlay_position == '6') imagecopy($img_obj, $overlay, $img_width-$overlay_size[0], $img_height/2-$overlay_size[1]/2, 0, 0, $overlay_size[0], $overlay_size[1]); // center right
		elseif ($overlay_position == '7') imagecopy($img_obj, $overlay, 0, $img_height-$overlay_size[1], 0, 0, $overlay_size[0], $overlay_size[1]); // bottom left
		elseif ($overlay_position == '8') imagecopy($img_obj, $overlay, $img_width/2-$overlay_size[0]/2, $img_height-$overlay_size[1], 0, 0, $overlay_size[0], $overlay_size[1]); // bottom center
		elseif ($overlay_position == '9') imagecopy($img_obj, $overlay, $img_width-$overlay_size[0], $img_height-$overlay_size[1], 0, 0, $overlay_size[0], $overlay_size[1]); // bottom right
		
		return $img_obj;
	}
	
	/**
	 * Removes whitespace around an image with a given tolerance.
	 * 
	 * @param resource $im A GD image resource.
	 * @param integer $tolerance The tolerance value a pixel can differ from the reference point (top left corner).
	 * @return resource The trimmed image object. 
	 */
	protected function _trim($im, $tolerance) {
		// grab the colour from the top left corner and use that as default
		$rgb = imagecolorat($im, 0, 0); // 2 pixels in to avoid messy edges
		$ref = array(
			'a' => ($rgb >> 24) & 0xFF,
			'r' => ($rgb >> 16) & 0xFF,
			'g' => ($rgb >> 8) & 0xFF,
			'b' => $rgb & 0xFF,
		);

		$w = imagesx($im); // image width
		$h = imagesy($im); // image height
		
		// get top border
		for ($top = 0; $top < $h; ++$top) {
			for ($x = 0; $x < $w; ++$x) {
					$rgb = imagecolorat($im, $x, $top);
					$a = ($rgb >> 24) & 0xFF;
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;

				if (
					$r < $ref['r']-$tolerance || $r > $ref['r']+$tolerance || // red not within tolerance of trim colour
					$g < $ref['g']-$tolerance || $g > $ref['g']+$tolerance || // green not within tolerance of trim colour
					$b < $ref['b']-$tolerance || $b > $ref['b']+$tolerance || // blue not within tolerance of trim colour
					$a < $ref['a']-$tolerance || $a > $ref['a']+$tolerance // alpha not within tolerance of trim colour
					) {
						break 2;
				}
			}
		}

		// get bottom border
		for ($bottom = $h-1; $bottom >= 0; --$bottom) {
			for ($x = 0; $x < $w; ++$x) {
					$rgb = imagecolorat($im, $x, $bottom);
					$a = ($rgb >> 24) & 0xFF;
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;

				if (
					$r < $ref['r']-$tolerance || $r > $ref['r']+$tolerance ||
					$g < $ref['g']-$tolerance || $g > $ref['g']+$tolerance ||
					$b < $ref['b']-$tolerance || $b > $ref['b']+$tolerance ||
					$a < $ref['a']-$tolerance || $a > $ref['a']+$tolerance
					) {
						break 2;
				}
			}
		}

		// get left border
		for ($left = 0; $left < $w; ++$left) {
			for ($y = $top; $y <= $bottom; ++$y) {
					$rgb = imagecolorat($im, $left, $y);
					$a = ($rgb >> 24) & 0xFF;
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;

				if (
					$r < $ref['r']-$tolerance || $r > $ref['r']+$tolerance ||
					$g < $ref['g']-$tolerance || $g > $ref['g']+$tolerance ||
					$b < $ref['b']-$tolerance || $b > $ref['b']+$tolerance ||
					$a < $ref['a']-$tolerance || $a > $ref['a']+$tolerance
					) {
						break 2;
				}
			}
		}

		// get right border
		for ($right = $w-1; $right > 0; --$right) {
			for ($y = $top; $y <= $bottom; ++$y) {
					$rgb = imagecolorat($im, $right, $y);
					$a = ($rgb >> 24) & 0xFF;
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;

				if (
					$r < $ref['r']-$tolerance || $r > $ref['r']+$tolerance ||
					$g < $ref['g']-$tolerance || $g > $ref['g']+$tolerance ||
					$b < $ref['b']-$tolerance || $b > $ref['b']+$tolerance ||
					$a < $ref['a']-$tolerance || $a > $ref['a']+$tolerance
					) {
						break 2;
				}
			}
		}

		$width	= $right-$left;
		$height	= $bottom-$top;

		// copy the contents, excluding the border
		$temp_image = imagecreatetruecolor($width, $height);
		//Debug::dump($width, $height); die();
		imagesavealpha($temp_image, true); // preserve full alpha transparency for png files
		imagealphablending($temp_image, false);

		imagecopyresampled($temp_image, $im, 0, 0, $left, $top, $width, $height, $width, $height);
		return $temp_image;
	}
	
	/**
	 * Creates the path of the cache file for the passed parameters.
	 * 
	 * @param string $file_path The path to the original file.
	 * @param array $params The params the user passed in.
	 * @return string The calculated file path.
	 */
	protected function _getCacheFilenameByPath($file_path, &$params) {
		if (!is_readable($file_path)) throw new \Exception(__CLASS__.': file "'.$file_path.'" does not exist or is not readable');

		$types = array('jpg', 'gif', 'png', 'png8');
		if (!isset($params['type']) || !in_array($params['type'], $types)) {
			$params['type'] = 'jpg';
		}
		
		// create hash for caching
		$filemtime = filemtime($file_path);
		$hash = md5($file_path.$filemtime.implode('', $params));
		// the substr to set type "png8" to extension "png"
		$filename = $this->_cache_dir.$hash.'.'.substr($params['type'], 0, 3);
		return $filename;
	}

	/**
	 * Returns the saved parameters for the given cache filename.
	 * 
	 * @param string $cache_filename The path to the cached file you want to retrieve parameters for.
	 * @return array The parameters for the given filename.
	 */
	protected function _getParamsFromHash($cache_filename) {
		$cache_params_filename = $this->_cache_dir . $cache_filename . '_params';
		
		if (!file_exists($cache_params_filename)) return false;

		$data = unserialize( file_get_contents( $cache_params_filename ));
		unlink( $cache_params_filename );
		return $data;
	}
		
	/**
	 * Converts a file readable by imagemagick to a 1000x1000 PNG, so this class can also handle PNGs, EPS and so on.
	 * 
	 * @param string $file The path to the original file.
	 * @return string The path to the converted file.
	 */
	protected function _im_get($file) {
		$params['density'] = '96';
		$params['strip'] = '';
		$params['thumbnail'] = '"1000x1000"';
		$params['fill'] = '"#999"';
		$params['font'] = 'Arial';
		$params['pointsize '] = '60';
		
		// create parameters from $_GET
		foreach ($params as $key => $value) $params[$key] = "-$key $value";

		$params = implode(' ', $params);
		$command = "convert $params '{$file}[0]'";

		// create target filename
		$target = tempnam('', 'im');
		unlink($target);
		$command .= " 'PNG:$target'";
		$returner = shell_exec($command);

		if (!is_file($target)) {
			return false;
		}

		return $target;
	}

	/**
	 * Returns an image GD resource for a passed file path or throws an exception on error.
	 * 
	 * @param string $file_path The path to the original file.
	 * @return resource The GD image resource.
	 */
	public function load($file_path) {
		if (!is_readable($file_path)) throw new \Exception(__CLASS__.': file "'.$file_path.'" does not exist or is not readable');

		$data = getimagesize($file_path);

		if ($data[2] == 1)	$img_obj = imagecreatefromgif($file_path);
		elseif ($data[2] == 2)	$img_obj = imagecreatefromjpeg($file_path);
		elseif ($data[2] == 3)	$img_obj = imagecreatefrompng($file_path);
		else {
			// it is not a valid file format
			// try imagemagick to preprocess
			// im creates a temporary image which should be deleted after gd processing
			$tmp_file = $this->_im_get($file_path);
			
			// if preprocessing was successful use the new image for gd processing
			if (is_file($tmp_file)) {
				$img_obj = imagecreatefrompng($tmp_file);
				unlink($tmp_file);
			} else {
				throw new \Exception(__CLASS__ . ': File '.$file_path.' not readable');
			}
		}
		
		return $img_obj;
	}
	
	/**
	 * Returns the file path for the cached image but does not create it.
	 * Useful if you want to create the image at another request.
	 * 
	 * @param string $file_path The path to the original file.
	 * @param array $params The params the user passed in.
	 * @return string The calculated file path.
	 */
	public function prepareGet($file_path, $params) {
		$cache_filepath = $this->_getCacheFilename($file_path, $params);
		
		// only create params file if thumb was not created so far
		if (file_exists($cache_filepath)) return basename($cache_filepath);

		// save params and file path to params file
		$data = array(
			'filename' => $file_path,
			'params' => $params
		);
		file_put_contents($cache_filepath . '_params', serialize($data));
		
		return basename($cache_filepath);
	}
		
	/**
	 * Creates the image for the params you passed in with prepareGet().
	 * 
	 * @param string $hash The hash you got from prepareGet().
	 * @return string The path to the created image file.
	 */
	public function getFromHash($hash) {
		$cache_filename = $this->_cache_dir . $hash;
		if (file_exists($cache_filename)) return $cache_filename;

		$data = $this->_getParamsFromHash($hash);
		$thumb = $this->get($data['filename'], $data['params'], false, $cache_filename);

		if (!is_file($thumb)) return $data;
		return $thumb;
	}

	/**
	 * Creates the image for given params and return the path to the new image.
	 * 
	 * @param string $file_path The path to the source file.
	 * @param array $params The params the user passed in.
	 * @param string $filename Pass a filename to force the name of the cache file.
	 * @return string The path to the created image file or a GD image resource if you have set `return_resource` to `true`.
	 */
	public function get($file_path, $params, $filename = null) {
		// get cache filename if not passed
		if (is_null($filename)) {
			$filename = $this->_getCacheFilenameByPath($file_path, $params);
		}
		
		// if there is a cache file return it
		if (file_exists($filename)) return $filename;

		// load ressource
		$img_obj = $this->load($file_path);
		
		// validate params
		$params = $this->_validateParams($params);

		// render params
		$img_obj = $this->_render($img_obj, $params);
		
		// save data to file
		if ($params['type'] === 'jpg') imagejpeg($img_obj, $filename, 80);
		elseif ($params['type'] === 'gif') imagegif($img_obj, $filename);
		elseif ($params['type'] === 'png') imagepng($img_obj, $filename);
		elseif ($params['type'] === 'png8') {
			imagepng($img_obj, $filename);

			// try pngnq (the improved pngquant) or pngquant to minify the png
			if (`which pngnq` !== null) {
				`pngnq -n 256 $filename -Q f`;
				rename(str_replace('.png', '-nq8.png', $filename), $filename);
			} elseif (`which pngquant` !== null) {
				`pngquant 256 $filename`;
				rename(str_replace('.png', '-fs8.png', $filename), $filename);
			}
		}
		
		return $filename;
	}

	/**
	 * Converts a hex string to an array with the three color channels.
	 * 
	 * @param string $hex The hex string like `#ffffff` or `#fff`.
	 * @return string The array with the three color channels R, G, and B.
	 */
	protected function _hex2RGB($hex) {
		$hex = preg_replace("/[^0-9a-f]/i", '', $hex);
		
		if (strlen($hex) == 3) $hex = $hex{0} . $hex{0}. $hex{1}. $hex{1}. $hex{2}. $hex{2};
		if (strlen($hex) !== 6) return false;

		$dec = hexdec($hex);
		$rgb = array(
			0xFF & ($dec >> 0x10),
			0xFF & ($dec >> 0x8),
			0xFF & $dec
		);

		return $rgb;
	}

	/**
	 * The function that processes the image.
	 * 
	 * @param resource $img_obj The GD image resource.
	 * @param array $params The params the user passed in.
	 * @return resource The processed GD image resource.
	 */
	protected function _render($img_obj, $params) {
		// trim the image
		if ($params['trim'] === true) $img_obj = $this->_trim($img_obj, $params['trim_tolerance']);

		// get info of img_obj
		$_SRC['width']		= imagesx($img_obj);
		$_SRC['height']		= imagesy($img_obj);

		// calculate ratio
		if (isset($params['longside'])) {
			if ($_SRC['width'] < $_SRC['height']) {
				$_DST['height']	= $params['longside'];
				$_DST['width']	= round($params['longside']/($_SRC['height']/$_SRC['width']));
			} else {
				$_DST['width']	= $params['longside'];
				$_DST['height']	= round($params['longside']/($_SRC['width']/$_SRC['height']));
			}
		} elseif (isset($params['shortside'])) {
			if ($_SRC['width'] < $_SRC['height']) {
				$_DST['width']	= $params['shortside'];
				$_DST['height']	= round($params['shortside']/($_SRC['width']/$_SRC['height']));
			} else {
				$_DST['height']	= $params['shortside'];
				$_DST['width']	= round($params['shortside']/($_SRC['height']/$_SRC['width']));
			}
		} else {
			// calculate destination dimension
			if (isset($params['width'])) $_DST['width'] = $params['width'];
			else $_DST['width'] = round($params['height']/($_SRC['height']/$_SRC['width']));

			if (isset($params['height'])) $_DST['height']	= $params['height'];
			else $_DST['height'] = round($params['width']/($_SRC['width']/$_SRC['height']));
		}

		// should the image get cropped
		$_DST['offset_w'] = 0;
		$_DST['offset_h'] = 0;
				
		if ($params['crop'] === true) {
			$width_ratio = $_SRC['width']/$_DST['width'];
			$height_ratio = $_SRC['height']/$_DST['height'];

			// crop on width
			if ($width_ratio > $height_ratio) {
				switch ($params['crop_position']) {
					case '1': $_DST['offset_w'] = 0;
						break;
					case '2': $_DST['offset_w'] = round(($_SRC['width']-$_DST['width']*$height_ratio)/2);
						break;
					case '3': $_DST['offset_w'] = round(($_SRC['width']-$_DST['width']*$height_ratio));
						break;
				}
				$_SRC['width'] = round($_DST['width']*$height_ratio);
			} elseif ($width_ratio < $height_ratio) {
				// crop on height
				switch ($params['crop_position']) {
					case '1': $_DST['offset_h'] = 0;
						break;
					case '2': $_DST['offset_h'] = round(($_SRC['height']-$_DST['height']*$width_ratio)/2);
						break;
					case '3': $_DST['offset_h'] = round(($_SRC['height']-$_DST['height']*$width_ratio));
						break;
				}
				$_SRC['height'] = round($_DST['height']*$width_ratio);
			}
		}

		// if the source image is smaller than the destination image, all calculations before were crap
		if ($params['extrapolate'] === false && $_DST['height'] > $_SRC['height'] && $_DST['width'] > $_SRC['width']) {
			$_DST['width'] = $_SRC['width'];
			$_DST['height'] = $_SRC['height'];
		}

		// now create the image
		$_SRC['image'] = $img_obj;

		// if the source image is too big first scale down linear to an image four times bigger than the target image 
		if ($_DST['width']*4 < $_SRC['width'] AND $_DST['height']*4 < $_SRC['height']) {
			// multiplier of target dimension
			$_TMP['width'] = round($_DST['width']*4);
			$_TMP['height'] = round($_DST['height']*4);
			$_TMP['image'] = imagecreatetruecolor($_TMP['width'], $_TMP['height']);
			
			// preserve image transparancy
			imagealphablending($_TMP['image'], false);

			imagecopyresized($_TMP['image'], $_SRC['image'], 0, 0, $_DST['offset_w'], $_DST['offset_h'], $_TMP['width'], $_TMP['height'], $_SRC['width'], $_SRC['height']);
			$_SRC['image'] = $_TMP['image'];
			$_SRC['width'] = $_TMP['width'];
			$_SRC['height'] = $_TMP['height'];

			// when the image is prescaled there must not be cropped a specific region
			$_DST['offset_w'] = 0;
			$_DST['offset_h'] = 0;
			unset($_TMP['image']);
		}

		// create destination image
		$_DST['image'] = imagecreatetruecolor($_DST['width'], $_DST['height']);

		$background_color	= imagecolorallocatealpha($_DST['image'], $params['background'][0], $params['background'][1], $params['background'][2], 0);

		if ($params['transparent'] === true && $params['type'] !== 'jpg') {
			if ($params['type'] === 'gif') {
				imagealphablending($_DST['image'], true);
				imagecolortransparent($_DST['image'], $background_color);
				imagefill($_DST['image'], 0, 0, $background_color);
			}

			if ($params['type'] === 'png' || $params['type'] === 'png8') {
				imagesavealpha($_DST['image'], true); // preserve full alpha transparency for png files
				imagealphablending($_DST['image'], false);
			}
		} else {
			// otherwise add background color
			imagealphablending($_DST['image'], true);
			imagefill($_DST['image'], 0, 0, $background_color);	
		}

		imagecopyresampled(
			$_DST['image'],
			$_SRC['image'],
			0,
			0,
			$_DST['offset_w'],
			$_DST['offset_h'],
			$_DST['width'],
			$_DST['height'],
			$_SRC['width'],
			$_SRC['height']
		);

		// sharpen the image
		if ($params['sharpen'] === true) {
			$_DST['image'] = $this->_sharpen($_DST['image']);
		}

		// add an overlay image
		if (!empty($params['overlay'])) {
			$_DST['image'] = $this->_addOverlayImage($_DST['image'], $params['overlay'], $params['overlay_position']);
		}

		if ($params['type'] === 'gif') {
			imagetruecolortopalette($_DST['image'], true, 255);
		}

		return $_DST['image'];
	}
	
	/**
	 * Removes recursively all files and folders for a given path.
	 * 
	 * @param string $path The path to delete.
	 */
	protected function _rmdir_recurse($path) {
		if (!file_exists($path)) return;
		
		$path = rtrim($path, '/').'/';
		$handle = opendir($path);
		for (; false !== ($file = readdir($handle));) {
			if($file == "." or $file == ".." ) continue;
			
			$fullpath = $path.$file;
			if (!is_link($fullpath) && is_dir($fullpath)) {
				self::rmdir_recurse($fullpath);
			} else {
				unlink($fullpath);
			}
		}
		closedir($handle);
		rmdir($path);
	}	
}

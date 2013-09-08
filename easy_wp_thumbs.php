<?php
/**
 * Easy WP thumbs v1.2
 * NOTE: Designed for use with PHP version 5 and up. Requires at least WP 3.0
 * 
 * @author Luca Montanari
 * @copyright 2013 Luca Montanari - http://projects.lcweb.it
 *
 * Licensed under the MIT license
 */
 
 
// be sure ewpt has not been initialized yet
if(! defined('EWPT_VER')  ) { 
 
define ('EWPT_VER', '1.2'); // script version
define ('EWPT_DEBUG_VAL', ''); // wp filesystem debug value - use 'ftp' or 'ssh' - on production must be left empty
define ('EWPT_BLOCK_LEECHERS', true); // block thumb loading on other websites
define ('EWPT_ALLOW_ALL_EXTERNAL', false);	// allow fetching from any website - set to false to avoid security issues

define ('EWPT_ALLOW_EXTERNAL', serialize(array( // array of allowed websites where the script can fetch images
	'flickr.com',
	'staticflickr.com',
	'picasa.com',
	'googleusercontent.com', // new picasa
	'img.youtube.com',
	'upload.wikimedia.org',
	'photobucket.com',
	'imgur.com',
	'imageshack.us',
	'tinypic.com',
	'pinterest.com',
	'pinimg.com', // new pinterest
	'fbcdn.net', // fb
	'amazonaws.com',  // instagram
	'instagram.com',
	'500px.net'
))); 
	
	
// WP CHMOD constants check
if(!defined('FS_CHMOD_DIR') || !FS_CHMOD_DIR) {$dir_chmod = 0755;}
else {$dir_chmod = FS_CHMOD_DIR;}

if(!defined('FS_CHMOD_FILE') || !FS_CHMOD_FILE) {$file_chmod = 0644;}
else {$file_chmod = FS_CHMOD_FILE;}

define('EWPT_CHMOD_DIR', $dir_chmod);
define('EWPT_CHMOD_FILE', $file_chmod);


// forcing via REQUEST parameter
if(isset($_REQUEST['ewpt_force']) && !defined('FS_METHOD')) {
	define('FS_METHOD', 'direct');	
}


$error_prefix = 'Easy WP Thumbs v'.EWPT_VER.' - '; 

//////////////////////////////////////////////////////////////
// if not exist - load WP functions
if(!function_exists('get_option')) {
	$curr_path = dirname(__FILE__);
	$curr_path_arr = explode('/', $curr_path);
	
	$true_path_arr = array();
	foreach($curr_path_arr as $part) {
		if($part == 'wp-content') {break;}
		$true_path_arr[] = $part;
	}	
	$true_path = implode('/', $true_path_arr);

	// main functions
	ob_start();
	if(!file_exists($true_path . '/wp-load.php')) {die('<p>'.$error_prefix. 'wp-load.php file not found</p>');}
	else {require_once($true_path . '/wp-load.php');}
}
if(!function_exists('get_filesystem_method')) {
	// wp-admin/includes/file.php - for wp_filesys
	if(!file_exists(ABSPATH . 'wp-admin/includes/file.php')) {die('<p>'.$error_prefix. 'file.php file not found</p>');}
	else {require_once(ABSPATH . 'wp-admin/includes/file.php');}	
}
/////////////////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////
// UTILITIES /////////////////////////////////////////
//////////////////////////////////////////////////////

// block external leechers - for remote thumb creation
function ewpt_block_external_leechers() {
	if(!EWPT_BLOCK_LEECHERS) {return true;}
	
	$my_host = preg_replace('/^www\./i', '', $_SERVER['HTTP_HOST']);
	if(EWPT_BLOCK_LEECHERS && array_key_exists('HTTP_REFERER', $_SERVER) && (! preg_match('/^https?:\/\/(?:www\.)?' . $my_host  . '(?:$|\/)/i', $_SERVER['HTTP_REFERER']))){
		// base64 encoded "stop hotlinking" png image
		$imgData = base64_decode( "iVBORw0KGgoAAAANSUhEUgAAAGYAAABkCAMAAABDybVbAAADAFBMVEUAAACsl5dnJSTp3Nt+fn4A///Ov74fFhXAUE7OmJa5b23a0M/y7Oz///9UVFM1NTW+gX+wq6vOr66rW1onBQXlv76TPTv08vLt5OTTg4Ho0dHjras+GBfCXlujcnHAq6rBlpXMb20NBwfdmZgkJCQlJSUmJiYnJycoKCgpKSkqKiorKyssLCwtLS0uLi4vLy8wMDAxMTEyMjIzMzM0NDQ1NTU2NjY3Nzc4ODg5OTk6Ojo7Ozs8PDw9PT0+Pj4/Pz9AQEBBQUFCQkJDQ0NERERFRUVGRkZHR0dISEhJSUlKSkpLS0tMTExNTU1OTk5PT09QUFBRUVFSUlJTU1NUVFRVVVVWVlZXV1dYWFhZWVlaWlpbW1tcXFxdXV1eXl5fX19gYGBhYWFiYmJjY2NkZGRlZWVmZmZnZ2doaGhpaWlqampra2tsbGxtbW1ubm5vb29wcHBxcXFycnJzc3N0dHR1dXV2dnZ3d3d4eHh5eXl6enp7e3t8fHx9fX1+fn5/f3+AgICBgYGCgoKDg4OEhISFhYWGhoaHh4eIiIiJiYmKioqLi4uMjIyNjY2Ojo6Pj4+QkJCRkZGSkpKTk5OUlJSVlZWWlpaXl5eYmJiZmZmampqbm5ucnJydnZ2enp6fn5+goKChoaGioqKjo6OkpKSlpaWmpqanp6eoqKipqamqqqqrq6usrKytra2urq6vr6+wsLCxsbGysrKzs7O0tLS1tbW2tra3t7e4uLi5ubm6urq7u7u8vLy9vb2+vr6/v7/AwMDBwcHCwsLDw8PExMTFxcXGxsbHx8fIyMjJycnKysrLy8vMzMzNzc3Ozs7Pz8/Q0NDR0dHS0tLT09PU1NTV1dXW1tbX19fY2NjZ2dna2trb29vc3Nzd3d3e3t7f39/g4ODh4eHi4uLj4+Pk5OTl5eXm5ubn5+fo6Ojp6enq6urr6+vs7Ozt7e3u7u7v7+/w8PDx8fHy8vLz8/P09PT19fX29vb39/f4+Pj5+fn6+vr7+/v8/Pz9/f3+/v7///+qzvBvAAABAHRSTlP///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////8AU/cHJQAAAAlwSFlzAAALEQAACxEBf2RfkQAABZJJREFUaN69mu2aoyoMgHHoQ7WEaescF8R1qO39X+Me1LbLlwg65+SHbUflnYQQSAD9+V8EpT+KZdOWbxm6RsIPY0TT9WVIHp38KQw8hjIi/QPz3RjeRBkvEuzC8LYv06SHzRhoywzpxSaMeJSZMuB8TFdukAfPw+TZy7CczMF0fblVHumYR7lDgj4XwECkif70/V0Uxfepj+grUzBi4eXLRYDAUsqGECmxAF5fFljNOkYGX20Jp40iBNhLgBBKQVRfSRwX0wRe+qo5VVQwXwQlQC+BV7o4RvpvnChXhhauAMWiWuWgtX4R/EZZXDTotGI3FPcxBWoNMgoR0Ef9zcL4Q0DWLE0k97pILGD8ANNxliqCet4DQUwoWHYsXTB2DNeGMOHBn8MRol9yt7+Yhck4Sx++ZDa0Or/kcIDb+rTgYHAktOdwHMeWDiY2jWVxcNBsaDX4T3NvOofLUDBAgf7vXY8pqxx97HHKDYztIUBgD8d2g8bAWMoUVHDe7+kff85GIWX0f7SHw1vP2ZA3ZgiZHoVhO8dSZ4Anhlst8nma9Dltur89XCdArjc36j3SPE56dLPc54l5BJTZyTGbHMSMMVurjXlM8M0c6UQC5NhMmHOy2N4/pqPWE8ac9U7CWsMI3m7Ux7RaRUeM6c434kb2jRxhj1CN6R2bnc/seTmczwfefhy1fHwcf+v7+vr1un/Qn1R/nj9fr5DplatntQlj9Zb2M4TYfEGjnOE8fnz8Rv/o+/r6C6HrdP9TP1Lo7+g8Pjn+6cru0zsjxrTCaDRTvYGyv5gDOnI4ok9oEZoAbwyCF2ZqfsLQEXPWr7AzHjFmVzQUWevZi2RPJfT7Y2uMaHW4g7nf0YzRzRZPzP0+Yo4oGHA6ZWMaG8MmDPMwv9Bhwsz2mTAMHTXmPv7+nFhmOD5hZPnzGJzfRjujA5svCFUWRn9MGH6Y7k6YAk3aHN4Y0wc4skINN/tGzFrx8QdUumXtb09MOWNGPeGJ0f8QumL0dgEXY7oE5waGsSO6H9n8Az5f/jZhNIfPnnZ4YbSXXRncp24aZbAx5k9InuLHFUN8gDqYPg3D4OblC/8FhoGXlPEMzJCKYSKLM0RcgP0cx8F0rqfFODcvVUparYEzPMXa2qj2Ur+lJ0sbYwWb1XwWUjlWsJFO6Fyfg30OrM5rY+i0JgKRMC+mccwY1lzdaS1l/m1SOKYDS4Ls9ZPgmzgBI9jrTmfJUSUtkAR1OdjLds3SErgLqD5tHSa8EpKMLaCItxyUiam5jOtjDU6ipsVtuyHNpHF9Gn9x+6cq08OaUTqJcVprHfPMb6zEI7mkIZY51r9Qk1AaBakc4WX5TTiNKkJJYbI6uhS0wLH4J/lOcfstvaM5OMyxrIPVO2GvNuaYvj6dm0P14rpUfsjImQMcuzFcG8UUa3XUZ5RowPODzs6IoFgsDbU5paB4VUnWVqGr2lFCi21/8MLCwMII2NQ/hjLKKUI28Ygbtdsip8JurdPd5oIsuy3ssMz9b1VuebndbiLMIcQvEHtT1WMv54aLQFXdWxq1sIvz9TaZvUcA7vZIjzP6x6v9gkreWEkdqNyvlmO1uE0UGGlN0pKqj3S/jyGB59dBMrC9UOMIJswpu0gfQWjTT1Ou0Z1CEh4BQwcBnThvwkV/IouVfU/CvxaiYKtRrzxLf9GIpYMRlKjVXVzML7Hd4mGW2N64UElb334KkyMXUIk77Fhs38hXokg+L0Cg2gYZgBQ5hyzoJoXqkMGiZzkIJ7mQmyAq/wAMgSzQRchi0zkbvbEuU013A1yoraeGCBHiss44UZAF2XXUSlGg0dMwreKyLvaf6Cq08UQdPE1xqSmnpChW20g8Bqc0SjfY1JevSU5V01DgWCn106ftyFU32ggYRdb6e7w7LPkXOpgdqGeVOLEAAAAASUVORK5CYII="); 
		header('Content-Type: image/png');
		header('Content-Length: ' . sizeof($imgData));
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header("Pragma: no-cache");
		header('Expires: ' . gmdate ('D, d M Y H:i:s', time()));
		echo $imgData;
		die();
	}
	
	return true;	
}


// manage browser cache for remote thumbs
function ewpt_manage_browser_cache($img_path, $method, $fresh_img = false){

	if(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $method == 'direct'){
		// file last modification date
		$mtime = @filemtime($img_path);
		
		// cache last modification date
		$iftime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']); // cache 
		
		// ccache exists - serve a 304
		if($iftime > 1 && $mtime && $mtime <= $iftime && !$fresh_img){ 
			ewpt_standard_caching_headers();
			header ($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
			die();
		}
		else {
			ewpt_standard_caching_headers();	
		}
	}
	else { ewpt_standard_caching_headers();	}

	return true;
}


// standard browser caching
function ewpt_standard_caching_headers() {
	$gmdate_expires = gmdate ('D, d M Y H:i:s', strtotime ('now +10 days')) . ' GMT';
	$gmdate_modified = gmdate ('D, d M Y H:i:s') . ' GMT';

	header('Last-Modified: ' . $gmdate_modified);
	header('Cache-Control: public');
	header('Expires: ' . $gmdate_expires);
}


/////////////////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////
// INTEGRATIVE CLASSES ///////////////////////////////
//////////////////////////////////////////////////////

// extend the WP 3.5 editor classes to get the image resource directly without headers
if( (float)substr(get_bloginfo('version'), 0, 3) >= 3.5) {
	$editor = _wp_image_editor_choose();
	
	if($editor == 'WP_Image_Editor_Imagick') {
		class ewpt_editor_extension extends WP_Image_Editor_Imagick {
			
			/**
			 * Given the mime-type returns the last parameter for the imagick functions 
			 */
			private function ewpt_mime_to_ext($mime) {
				$arr = explode('/', $mime);
				return end($arr);	
			}
			
			
			/**
			 * Manage the resize and/or crop using the Timthumb v2.8.10 structure with imagick functions
			 * © Luca Montanari
			 *
			 * @param int $width width of the resized image
			 * @param int $height height of the resized image
			 * @param int $rs resize method
			 * @param string $a cropping alignment 
			 * @param string $mime mime-type of the image
			 * @param string $canvas_color canvas background color 
			 */
			public function ewpt_tt_management($width, $height, $rs, $a, $mime, $canvas_color) {

				// get standard input properties		
				$new_width =  (int) abs ($width);
				$new_height = (int) abs ($height);
				$zoom_crop = (int) $rs;
				$align = $a;
		
				// Get original width and height
				$size = $this->get_size();
				$width = $size['width'];
				$height = $size['height'];
				$origin_x = 0;
				$origin_y = 0;
				
				// set the canvas background color
				if($mime != 'image/png') {
					$pattern = '/^#[a-f0-9]{6}$/i';
					if (strlen($canvas_color) == 3) { //if is 3-char notation, edit string into 6-char notation
						$canvas_color = str_repeat(substr($canvas_color, 0, 1), 2) . str_repeat(substr($canvas_color, 1, 1), 2) . str_repeat(substr($canvas_color, 2, 1), 2); 
					} 
					$canvas_color = '#' . $canvas_color;
					
					if (strlen($canvas_color) != 7 || !preg_match($pattern, $canvas_color)) {
						$canvas_color = '#FFFFFF'; // on error return default canvas color
					}	

					$this->image->setimagebackgroundcolor($canvas_color);	
				}
				else {$canvas_color = 'transparent';}

				// stretch the image to the size
				if($zoom_crop == 0) {
					$this->image->resizeimage($new_width, $new_height, imagick::FILTER_POINT, 0, FALSE ); 
				}
				
				// only scale image
				if ($zoom_crop == 3) {
					$this->image->resizeimage($new_width, $new_height, imagick::FILTER_POINT, 0, true);	
				}
				
				// scale and add borders
				if($zoom_crop == 2) {
					//scale 
					$ratio = min($new_width/$width, $new_height/$height);
					$_new_w = round($width * $ratio);
					$_new_h = round($height * $ratio); 
					$this->image->resizeimage($new_width, $new_height, imagick::FILTER_POINT, 0, true);
					
					if($_new_w == $new_width) {$border_w = 0;}
					else {$border_w = ceil(($new_width - $_new_w) / 2);}
					
					if($_new_h == $new_height) {$border_h = 0;}
					else {$border_h = ceil(($new_height - $_new_h) / 2);}
					
					$this->image->borderImage($canvas_color, $border_w, $border_h);
				}
				
				// scale and crop
				else {
					//scale 
					$ratio = max($new_width/$width, $new_height/$height);
					$_new_w = ceil($width * $ratio);
					$_new_h = ceil($height * $ratio); 
					$this->image->scaleimage($_new_w, $_new_h, true);


					// coordinates to cut from center
					if($_new_w == $new_width) {$src_x = 0;}
					else {$src_x = floor(($_new_w - $new_width) / 2);}
					
					if($_new_h == $new_height) {$src_y = 0;}
					else {$src_y = floor(($_new_h - $new_height) / 2);}
					
					// positional cropping!
					if ($align) {
						if (strpos ($align, 't') !== false) {
							$src_y = 0;
						}
						if (strpos ($align, 'b') !== false) {
							$src_y = $_new_h - $new_height;
						}
						if (strpos ($align, 'l') !== false) {
							$src_x = 0;
						}
						if (strpos ($align, 'r') !== false) {
							$src_x = $_new_w - $new_width;
						}
					}

					$this->crop($src_x, $src_y, $new_width, $new_height);
				}


				$this->update_size();
				
				return true;
			}

			
			/**
			 * Apply efects to the image
			 * @param array $fx_array array of effects
			 */
			public function ewpt_img_fx($fx_array) {
				if(!is_array($fx_array)) {return false;}
				
				foreach($fx_array as $fx) {
					switch($fx) {
						case 'blur'		: $this->image->blurImage(1,3); break;
						case 'grayscale': $this->image->modulateImage(100,0,100); break;	
					}
				}
				return true;
			}
			
			
			/**
			 * Returns stream of current image.
			 */
			public function ewpt_img_contents($mime) {
				$this->image->setImageFormat($this->ewpt_mime_to_ext($mime));
				echo $this->image->getImageBlob();
				return true;
			}
		}
	}
	else {
		class ewpt_editor_extension extends WP_Image_Editor_GD {
			
			/**
			 * Apply efects to the image
			 * @param array $fx_array array of effects
			 */
			public function ewpt_img_fx($fx_array) {
				if(!is_array($fx_array)) {return false;}
				
				foreach($fx_array as $fx) {
					switch($fx) {
						case 'blur'		: imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR); break;
						case 'grayscale': imagefilter($this->image, IMG_FILTER_GRAYSCALE); break;	
					}
				}
				return true;
			}
			
			
			/**
			 * Manage the resize and/or crop using the Timthumb v2.8.10 script
			 * © Ben Gillbanks and Mark Maunder
			 *
			 * @param int $width width of the resized image
			 * @param int $height height of the resized image
			 * @param int $rs resize method
			 * @param string $a cropping alignment 
			 * @param string $mime mime-type of the image
			 * @param string $canvas_color background color of the image
			 */
			public function ewpt_tt_management($width, $height, $rs, $a, $mime, $canvas_color) {
				// get standard input properties		
				$new_width =  (int) abs ($width);
				$new_height = (int) abs ($height);
				$zoom_crop = (int) $rs;
				$align = $a;
		
				// existing image resource
				$image = $this->image;
		
				// Get original width and height
				$size = $this->get_size();
				$width = $size['width'];
				$height = $size['height'];
				$origin_x = 0;
				$origin_y = 0;
		
		
				// only scale image
				if ($zoom_crop == 3) {
		
					$final_height = $height * ($new_width / $width);
		
					if ($final_height > $new_height) {
						$new_width = $width * ($new_height / $height);
					} else {
						$new_height = $final_height;
					}
		
				}
		
				// create a new true color image
				$canvas = imagecreatetruecolor ($new_width, $new_height);
				imagealphablending ($canvas, false);
		
				if (strlen($canvas_color) == 3) { //if is 3-char notation, edit string into 6-char notation
					$canvas_color =  str_repeat(substr($canvas_color, 0, 1), 2) . str_repeat(substr($canvas_color, 1, 1), 2) . str_repeat(substr($canvas_color, 2, 1), 2); 
				} else if (strlen($canvas_color) != 6) {
					$canvas_color = 'FFFFFF'; // on error return default canvas color
				}
				
				$canvas_color_R = hexdec (substr ($canvas_color, 0, 2));
				$canvas_color_G = hexdec (substr ($canvas_color, 2, 2));
				$canvas_color_B = hexdec (substr ($canvas_color, 4, 2));
		
				// Create a new transparent color for image
				if($mime == 'image/png'){ 
					$color = imagecolorallocatealpha ($canvas, $canvas_color_R, $canvas_color_G, $canvas_color_B, 127);		
				}else{
					$color = imagecolorallocatealpha ($canvas, $canvas_color_R, $canvas_color_G, $canvas_color_B, 0);
				}
		
				// Completely fill the background of the new image with allocated color.
				imagefill ($canvas, 0, 0, $color);
		
		
				// scale down and add borders
				if ($zoom_crop == 2) {
		
					$final_height = $height * ($new_width / $width);
		
					if ($final_height > $new_height) {
		
						$origin_x = $new_width / 2;
						$new_width = $width * ($new_height / $height);
						$origin_x = round ($origin_x - ($new_width / 2));
		
					} else {
		
						$origin_y = $new_height / 2;
						$new_height = $final_height;
						$origin_y = round ($origin_y - ($new_height / 2));
					}
				}
		
		
				// Restore transparency blending
				imagesavealpha ($canvas, true);
		
				if ($zoom_crop > 0) {
		
					$src_x = $src_y = 0;
					$src_w = $width;
					$src_h = $height;
		
					$cmp_x = $width / $new_width;
					$cmp_y = $height / $new_height;
		
					// calculate x or y coordinate and width or height of source
					if ($cmp_x > $cmp_y) {
		
						$src_w = round ($width / $cmp_x * $cmp_y);
						$src_x = round (($width - ($width / $cmp_x * $cmp_y)) / 2);
		
					} else if ($cmp_y > $cmp_x) {
		
						$src_h = round ($height / $cmp_y * $cmp_x);
						$src_y = round (($height - ($height / $cmp_y * $cmp_x)) / 2);
		
					}
		
					// positional cropping!
					if ($align) {
						if (strpos ($align, 't') !== false) {
							$src_y = 0;
						}
						if (strpos ($align, 'b') !== false) {
							$src_y = $height - $src_h;
						}
						if (strpos ($align, 'l') !== false) {
							$src_x = 0;
						}
						if (strpos ($align, 'r') !== false) {
							$src_x = $width - $src_w;
						}
					}
					
					imagecopyresampled ($canvas, $image, $origin_x, $origin_y, $src_x, $src_y, $new_width, $new_height, $src_w, $src_h);
					
				} else {
					imagecopyresampled ($canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				}
				
				//Straight from Wordpress core code. Reduces filesize by up to 70% for PNG's
				if ( ($mime == 'image/png' || $mime == 'image/gif') && function_exists('imageistruecolor') && !imageistruecolor( $image ) && imagecolortransparent( $image ) > 0 ){
					imagetruecolortopalette( $canvas, false, imagecolorstotal( $image ) );
				}
				
				$this->image = $canvas;
				$this->update_size();
				
				return true;
			}	
			
			
			/**
			 * Returns stream of current image.
			 */
			public function ewpt_img_contents($mime) {
				switch ($mime) {
					case 'image/png':	return imagepng($this->image);
					case 'image/gif': 	return imagegif($this->image);
					default: 			return imagejpeg($this->image, null, $this->quality);
				}
			}	
		}	
	}
}


// GD image editor class for WP < 3.5 (methods taken from WP 3.5)
class ewpt_old_wp_img_editor {
	
	// image source
	private $file = false;
	
	// GD image object
	public $image = false; 
	
	protected $size = null;
	protected $mime_type = null;
	protected $default_mime_type = 'image/jpeg';
	protected $quality = 80;

	
	// create a GD resource
	public function __construct($img_src) {
		$this->file = $img_src;
		
		if ( $this->image )
			return true;

		if ( ! is_file( $this->file ) && ! preg_match( '|^https?://|', $this->file ) )
			return new WP_Error( 'error_loading_image', __('File doesn&#8217;t exist?'), $this->file );

		// Set artificially high because GD uses uncompressed images in memory
		@ini_set( 'memory_limit', '256M');
		$this->image = @imagecreatefromstring( file_get_contents( $this->file ) );

		if ( ! is_resource( $this->image ) )
			return new WP_Error( 'invalid_image', __('File is not an image.'), $this->file );

		$size = @getimagesize( $this->file );
		if ( ! $size )
			return new WP_Error( 'invalid_image', __('Could not read image size.'), $this->file );

		$this->update_size( $size[0], $size[1] );
		$this->mime_type = $size['mime'];

		return true;
	}
	
	
	/**
	 * Gets dimensions of image.
	 *
	 * @return array {'width'=>int, 'height'=>int}
	 */
	public function get_size() {
		return $this->size;
	}
	
	
	/**
	 * Sets or updates current image size.
	 *
	 * @param int $width
	 * @param int $height
	 */
	protected function update_size( $width = false, $height = false ) {
		if ( ! $width )
			$width = imagesx( $this->image );

		if ( ! $height )
			$height = imagesy( $this->image );

		$this->size = array(
			'width' => (int) $width,
			'height' => (int) $height
		);
		return true;
	}
	
	
	/**
	 * Sets Image Compression quality on a 1-100% scale.
	 *
	 * @param int $quality Compression Quality. Range: [1,100]
	 * @return boolean
	 */
	public function set_quality($quality) {
		if(!$quality) {return false;}
		else {$this->quality = (int)$quality;}
		
		return true;
	}
	
	
	/**
	 * Convert JPG 1/100 quality to the PNG one
	 * @LCweb
	 */
	private function png_quality() {
		$val = (int)$this->quality;
		if($val < 10) {$val = 90;}
		
		$png_val = (int) (floor(($val * -1)) / 10 + 10);
		return $png_val;
	}
	
	
	/**
	 * Apply efects to the image
	 * @param array $fx_array array of effects
	 */
	public function ewpt_img_fx($fx_array) {
		if(!is_array($fx_array)) {return false;}
		
		foreach($fx_array as $fx) {
			switch($fx) {
				case 'blur'		: imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR); break;
				case 'grayscale': imagefilter($this->image, IMG_FILTER_GRAYSCALE); break;	
			}
		}
		return true;
	}

	
	/**
	 * Manage the resize and/or crop using the Timthumb v2.8.10 script
	 * © Ben Gillbanks and Mark Maunder
	 *
	 * @param int $width width of the resized image
	 * @param int $height height of the resized image
	 * @param int $rs resize method
	 * @param string $a cropping alignment 
	 * @param string $mime mime-type of the image
	 * @param string $canvas_color canvas background color 
	 */
	public function ewpt_tt_management($width, $height, $rs, $a, $mime, $canvas_color) {
		// get standard input properties		
		$new_width =  (int) abs ($width);
		$new_height = (int) abs ($height);
		$zoom_crop = (int) $rs;
		$align = $a;

		// existing image resource
		$image = $this->image;

		// Get original width and height
		$size = $this->get_size();
		$width = $size['width'];
		$height = $size['height'];
		$origin_x = 0;
		$origin_y = 0;


		// only scale image
		if ($zoom_crop == 3) {

			$final_height = $height * ($new_width / $width);

			if ($final_height > $new_height) {
				$new_width = $width * ($new_height / $height);
			} else {
				$new_height = $final_height;
			}

		}

		// create a new true color image
		$canvas = imagecreatetruecolor ($new_width, $new_height);
		imagealphablending ($canvas, false);

		if (strlen($canvas_color) == 3) { //if is 3-char notation, edit string into 6-char notation
			$canvas_color =  str_repeat(substr($canvas_color, 0, 1), 2) . str_repeat(substr($canvas_color, 1, 1), 2) . str_repeat(substr($canvas_color, 2, 1), 2); 
		} else if (strlen($canvas_color) != 6) {
			$canvas_color = 'FFFFFF'; // on error return default canvas color
		}

		$canvas_color_R = hexdec (substr ($canvas_color, 0, 2));
		$canvas_color_G = hexdec (substr ($canvas_color, 2, 2));
		$canvas_color_B = hexdec (substr ($canvas_color, 4, 2));

		// Create a new transparent color for image
		if($mime == 'image/png'){ 
			$color = imagecolorallocatealpha ($canvas, $canvas_color_R, $canvas_color_G, $canvas_color_B, 127);		
		}else{
			$color = imagecolorallocatealpha ($canvas, $canvas_color_R, $canvas_color_G, $canvas_color_B, 0);
		}

		// Completely fill the background of the new image with allocated color.
		imagefill ($canvas, 0, 0, $color);


		// scale down and add borders
		if ($zoom_crop == 2) {

			$final_height = $height * ($new_width / $width);

			if ($final_height > $new_height) {

				$origin_x = $new_width / 2;
				$new_width = $width * ($new_height / $height);
				$origin_x = round ($origin_x - ($new_width / 2));

			} else {

				$origin_y = $new_height / 2;
				$new_height = $final_height;
				$origin_y = round ($origin_y - ($new_height / 2));
			}
		}


		// Restore transparency blending
		imagesavealpha ($canvas, true);

		if ($zoom_crop > 0) {

			$src_x = $src_y = 0;
			$src_w = $width;
			$src_h = $height;

			$cmp_x = $width / $new_width;
			$cmp_y = $height / $new_height;

			// calculate x or y coordinate and width or height of source
			if ($cmp_x > $cmp_y) {

				$src_w = round ($width / $cmp_x * $cmp_y);
				$src_x = round (($width - ($width / $cmp_x * $cmp_y)) / 2);

			} else if ($cmp_y > $cmp_x) {

				$src_h = round ($height / $cmp_y * $cmp_x);
				$src_y = round (($height - ($height / $cmp_y * $cmp_x)) / 2);

			}

			// positional cropping!
			if ($align) {
				if (strpos ($align, 't') !== false) {
					$src_y = 0;
				}
				if (strpos ($align, 'b') !== false) {
					$src_y = $height - $src_h;
				}
				if (strpos ($align, 'l') !== false) {
					$src_x = 0;
				}
				if (strpos ($align, 'r') !== false) {
					$src_x = $width - $src_w;
				}
			}

			imagecopyresampled ($canvas, $image, $origin_x, $origin_y, $src_x, $src_y, $new_width, $new_height, $src_w, $src_h);
			
		} else {

			// copy and resize part of an image with resampling
			imagecopyresampled ($canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		}
		
		//Straight from Wordpress core code. Reduces filesize by up to 70% for PNG's
		if ( ($mime == 'image/png' || $mime == 'image/gif') && function_exists('imageistruecolor') && !imageistruecolor( $image ) && imagecolortransparent( $image ) > 0 ){
			imagetruecolortopalette( $canvas, false, imagecolorstotal( $image ) );
		}
		
		$this->image = $canvas;
		$this->update_size();
		
		return true;
	}	


	/**
	 * Returns stream of current image.
	 * @lcweb
	 */
	public function ewpt_img_contents($mime) {
		switch ($mime) {
			case 'image/png':	return imagepng($this->image);
			case 'image/gif': 	return imagegif($this->image);
			default: 			return imagejpeg($this->image, null, $this->quality);
		}
	}	
	
	
	/**
	 * Returns stream of current image.
	 * @param string $mime_type
	 */
	public function stream($mime_type) {
		switch ( $mime_type ) {
			case 'image/png':
				header( 'Content-Type: image/png');
				return imagepng( $this->image, null, $this->png_quality());
			case 'image/gif':
				header( 'Content-Type: image/gif' );
				return imagegif( $this->image );
			default:
				header( 'Content-Type: image/jpeg' );
				return imagejpeg( $this->image, null, $this->quality );
		}
	}
	
	
	function __destruct() {
		if ( $this->image ) {
			// we don't need the original in memory anymore
			imagedestroy( $this->image );
		}
	}	
}

/////////////////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////
// WP FILESYSTEM CHECK FOR THE ADMIN PANEL ///////////
//////////////////////////////////////////////////////

// Connection fields - SHOULD be inserted in a form
function ewpt_wpf_form() {
	// hide the icon and reduce the title size
	echo '
	<style type="text/css">
	#ewpt_wrapper .icon32 {display: none;}
	#ewpt_wrapper h2 {
		font-size: 20px;
		height: 24px;
    	line-height: 20px;
		margin-bottom: 5px;
    	padding-bottom: 0;
	}
	</style>';
	
	echo '<div id="ewpt_wrapper">';

		// nonce for the credentials
		// get the admin page string for the nonce 
		$pos = strpos($_SERVER["REQUEST_URI"], 'wp-admin/');
		$pag_url = substr($_SERVER["REQUEST_URI"], $pos + 9);
		$nonce_url = wp_nonce_url($pag_url);
		echo '<input type="hidden" name="ewpt_nonce_url" value="'.$nonce_url.'" />';
		
		// init flag
		echo '<input type="hidden" name="ewpt_init" value="1" />';
		
		wp_nonce_field('ewpt-settings');
	echo '</div>';
	
	?>
	<script type="text/javascript" charset="utf8" >
	jQuery(document).ready(function($) {
		
		// style and customize the form
		function ewpt_style_form() {
			jQuery('#ewpt_wrapper .wrap > table').addClass('widefat');
			if(jQuery('#ewpt_wrapper .wrap > table').size() > 0) {jQuery('#ewpt_wrapper').css('border-bottom', '1px solid #DFDFDF');}
			
			jQuery('#ewpt_wrapper .wrap > h2').prepend('Easy WP Thumbs - ');
		}
		
		// setup the loader
		function ewpt_show_loader() {
			jQuery('#ewpt_wrapper').html('<img alt="loading .." style="padding-bottom: 20px;" src="data:image/gif;base64,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" />');
			return true;	
		}
		
		// show the form or the status - ajax
		function ewpt_setup(step) {
			var err_mess = '<div id="ewpt_message" class="error"><p><?php _e('<strong>ERROR:</strong> There was an error connecting to the server, Please verify the settings are correct.') ?></p></div>';
			var fdata = 'action=ewpt_wpf_check&' + jQuery('#ewpt_wrapper input').serialize();
			ewpt_show_loader();
			
			jQuery.post(ajaxurl, fdata, function(response) {
				jQuery('#ewpt_wrapper').html(response);
				ewpt_style_form();
				
				if(step == 'send' && jQuery('#ewpt_wrapper input[type=submit]').size() > 0) {
					jQuery('#ewpt_wrapper').prepend(err_mess);	
				}
			});
		}
		ewpt_setup('init');
		
		// setup form click
		jQuery('body').delegate('#ewpt_wrapper #upgrade', 'click', function(e) {
			e.preventDefault();
			ewpt_setup('send');
		});
		
		// erase cache
		jQuery('body').delegate('#ewpt_clean_cache_trig', 'click', function(e) {
			e.preventDefault();
			
			if(confirm("<?php _e('Confirm the cache file deletion?') ?>")) {
				ewpt_show_loader();	
				
				var fdata = {
					action: 'ewpt_erase_cache'
				};
				ewpt_show_loader();
				
				jQuery.post(ajaxurl, fdata, function(response) {
					jQuery('#ewpt_wrapper').html(response);
					ewpt_style_form();
					
					if(jQuery('#ewpt_wrapper input[type=submit]').size() > 0) {
						jQuery('#ewpt_wrapper').prepend(err_mess);	
					}
				});
			}
		});
	});
	</script>
	<?php
}


// correct setup message
function ewpt_wpf_ok_mess($has_cache_files = false) {
	echo '<div class="wrap">';
	$clean_cache_string = ($has_cache_files) ? '<a id="ewpt_clean_cache_trig" href="#"> ('. __('Clean cache') .')</a>' : '';  
	
	echo '<h2>'. __('Connection Information'). '</h2>
	<p>'. __('Your system is set up properly') . $clean_cache_string .'</p><br/>';
	die();
}


// erase the cache and the cache folder
function ewpt_erase_cache() {
	// Force direct Flag
	if(get_option('ewpt_force_ftp') && !defined('FS_METHOD')) {define('FS_METHOD', 'direct');} 
	
	$ewpt = new ewpt_connect(EWPT_DEBUG_VAL);
	
	// check if is ready to operate
	if(!$ewpt->is_ready()) {die('Cache folder not found');}
	
	global $wp_filesystem;
	if(!$ewpt->cache_dir || strpos($ewpt->cache_dir, 'ewpt') === false) {die('wrong cache directory');}
	
	if( !$wp_filesystem->rmdir( $ewpt->cache_dir, true)) {die('Error deleting the cache files');}
	
	$_POST['ewpt_init'] = true;
	ewpt_wpf_check(false, true);
	die();
}
add_action('wp_ajax_ewpt_erase_cache', 'ewpt_erase_cache');


// check with the wp filesystem - executed via AJAX
function ewpt_wpf_check($force_direct = false) {
	// set a fake screen type
	$GLOBALS['hook_suffix'] = 'page';
	set_current_screen();
	
	$method = EWPT_DEBUG_VAL;
	$ewpt = new ewpt_connect($method);
	
	// FTP issue fix
	if( ($force_direct || get_option('ewpt_force_ftp')) && !defined('FS_METHOD')) {define('FS_METHOD', 'direct');} 

	// check if is ready to work - if the server allows to manage directly files and cache dir doesn't exists, create it
	if($ewpt->is_ready()) {
		// check for existing cache images
		global $wp_filesystem;
		$existing_files = $wp_filesystem->dirlist( $ewpt->cache_dir );
		$has_cache_files = (is_array($existing_files) && count($existing_files) > 0) ? true : false;
		
		ewpt_wpf_ok_mess($has_cache_files);
	}
	
	//// request_filesystem_credentials part (for restricted servers)

	// print the nonces and screen fields anyway
	wp_nonce_field('ewpt-settings');
	if(isset($_POST['ewpt_nonce_url'])) {
		echo '<input type="hidden" name="ewpt_nonce_url" value="'.$_POST['ewpt_nonce_url'].'" />';
	}
	
	// context
	($ewpt->cache_dir_exists()) ? $context = $ewpt->cache_dir : $context = $ewpt->basedir;
	
	// get url
	$nonce_url = $_POST['ewpt_nonce_url'];
	
	// basic display
	if(isset($_POST['ewpt_init'])) {
		request_filesystem_credentials($nonce_url, $method, false, $context);
		die();
	}
	
	//// handling the data 
	// check the nonce
	check_admin_referer('ewpt-settings');
	
	// check
	if (false === ($creds = request_filesystem_credentials($nonce_url, $method, false, $context) ) ) {
		die();
	}
		
	// check the wp_filesys with the given credentials
	if ( !WP_Filesystem($creds, $context) ) {
		request_filesystem_credentials($url, $method, false, $context);
		die();
	}

	// connected succesfully - proceed with cache directory and demo file creation 
	global $wp_filesystem;
	
	// chache dir creation
	if(!file_exists($ewpt->cache_dir)) {
		if( !$wp_filesystem->mkdir($ewpt->cache_dir, EWPT_CHMOD_DIR) ) {
			
			// try forcing the direct creation
			if(!$force_direct) {
				ewpt_wpf_check($force_direct = true);
				die();
			} else {
				die( __('Error creating the cache directory') . '<br/><br/>' );
			}
		}
	}
	
	// create the test file and remove it
	$filename = $ewpt->cache_dir. '/test_file.txt';
	if ( !file_exists($filename)) {
		if(!$wp_filesystem->put_contents($filename, 'Testing ..', EWPT_CHMOD_FILE)) {
			
			// try forcing the direct creation
			if(!$force_direct) {
				ewpt_wpf_check($force_direct = true);
				die();
			} else {
				die( __('Error creating the test file') . '<br/><br/>' );
			}
		}
	}
	$wp_filesystem->delete($filename);

	//// everything is ok
	
	// if is forcing - save the flag
	if($force_direct || (defined('FS_METHOD') && FS_METHOD == 'direct') ) {
		// save the flag to use the direct method
		if(!get_option('ewpt_force_ftp')) { add_option('ewpt_force_ftp', '255', '', 'yes'); }
		update_option('ewpt_force_ftp', 1);		
	}
	
	// save the credentials
	$raw_creds = base64_encode( json_encode($creds));
	if(!get_option('ewpt_creds')) { add_option('ewpt_creds', '255', '', 'yes'); }
	update_option('ewpt_creds', $raw_creds);
	
	ewpt_wpf_ok_mess();
	die();
}
add_action('wp_ajax_ewpt_wpf_check', 'ewpt_wpf_check');

/////////////////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////
// MAIN CLASSES //////////////////////////////////////
//////////////////////////////////////////////////////

// wp filesystem part
class ewpt_connect {
	
	// debug flag - for ftp testing
	public $debug = false;
	
	// array - errors collector
	public $errors = array();
	
	// base "wp uploads" directory
	public $basedir;

	// ewpt cache directory
	public $cache_dir;
	
	// ewpt cache url
	public $cache_url; 

	// WP_filesys saved credentials
	public $creds = false;

	
	/////////////////////////////////
	
	// @param bool $debug_flag (optional) use 'ftp' or 'ssh' for debug
	public function __construct($debug_flag = false) {
		if(!function_exists('wp_upload_dir')) {die('WP functions not initialized');}
		
		// set the directories
		$upload_dirs = wp_upload_dir();
		$this->basedir = $upload_dirs['basedir'];		
		$this->cache_dir = $this->basedir . '/ewpt_cache';
		$this->cache_url = $upload_dirs['baseurl'] . '/ewpt_cache';
		
		if($debug_flag) {$this->debug = $debug_flag;}
		
		// if restricted server - get the saved credentials  
		if($this->get_method() != 'direct' || $this->debug) {
			$raw_creds = get_option('ewpt_creds');
			if($raw_creds) { $this->creds = json_decode( base64_decode( $raw_creds )); }
		}
			
		return true;	
	}


	/**
	  * Check if the ewpt cache directory exists
	  */
	public function cache_dir_exists() {
		return (file_exists($this->cache_dir)) ? $this->cache_dir : false;
	}
	
	
	/**
	  * Return the filesystem method required for a path
	  *
	  * @param string $path (optional) the path to use as context
	  * returned values: 'direct', 'ssh', 'ftpext' or 'ftpsockets' 
	  */
	public function get_method($path = false) {	
		if(!$path) {
			if($this->cache_dir_exists()) {$path = $this->cache_dir;}
			else {$path = $this->basedir;}
		}
		
		if(!file_exists($path)) {
			$this->errors[] = 'get_method - the path does not exists';
			return false;	
		}
		
		if($this->debug) {return $this->debug;}
		else {return get_filesystem_method(array(), $path);}
	}
	
	
	/**
	  * Check if everything has been set up to start creating thumbnails
	  */
	public function is_ready() {
		// check if the cache directory has been set
		if(!$this->cache_dir_exists()) {
			
			// if method = direct create it and check again
			if($this->get_method() == 'direct') {
				mkdir($this->cache_dir, EWPT_CHMOD_DIR);	
				return $this->is_ready();
			}
			
			$this->errors[] = __("Cache folder doesn't exists");
			return false;
		}
		
		// force flag
		if(get_option('ewpt_force_ftp') && !defined('FS_METHOD')) {define('FS_METHOD', 'direct');} 
		
		// if has "direct access" 
		if($this->get_method( $this->cache_dir ) == 'direct') {
			WP_Filesystem(false, $this->cache_dir);
			return true;
		}
		
		// check saved credentials against WP_filesys
		else {
			if(!$this->creds || !WP_Filesystem($this->creds, $this->cache_dir)) {
				$this->errors[] = __("01 - WP_filesystem - connection failed");
				return false;
			}
			else {return true;}	
		}
	}
	
	
	/**
	  * Save a file into the cache directory
	  *
	  * @param string $filename file name with extension 
	  * @param string $filename file contents
	  * @return (bool) true if file is saved correctly
	  */
	public function create_file($filename, $contents) {
		if(trim($filename) == '' || trim($contents) == '') {
			$this->errors[] = __('Filename or contents are missing');
			return false;	
		}
		
		// connect
		if(!$this->is_ready()) {
			
			// try another time forcing
			if(!defined('FS_METHOD')) {
				define('FS_METHOD', 'direct');
				
				if(!$this->is_ready()) {
					$this->errors[] = __('02 - WP_filesystem - connection failed');
					return false;		
				}
			}
			else {
				$this->errors[] = __('02 - WP_filesystem - connection failed');
				return false;	
			}
		}
		global $wp_filesystem;
		
		// create file
		$fullpath = $this->cache_dir.'/'.$filename; 
		if(!$wp_filesystem->put_contents($fullpath, $contents, EWPT_CHMOD_FILE)) {
			$this->errors[] = __('Error creating the file') . ' ' .$filename;
			return false;	
		}
		else {return true;}
	}

	
	/**
	  * Returns the errors
	  */
	public function get_errors() {
		if(count($this->errors) > 0){
			$html = '<h2>Easy WP Thumbs - error occurred</h2><ul>';
			
			foreach($this->errors as $error) {
				$html .= '<li>'.$error.'</li>';	
			}
			
			$html .= '</ul>
			<hr/><small>version '.EWPT_VER.'</small>';
			
			return $html ;
		}
		else {return false;}
	}
}


///////////////////////////////////////////////////////////////
// image editor class
class easy_wp_thumbs extends ewpt_connect {
	
	// cache image name
	public $cache_img_name;
	
	// image mime type
	public $mime = false;
	
	// WP image editor object
	private $editor = false;
	
	// cache filename part for the effects
	private $fx_filename = '';
	
	// associative array of thumb parameters
	private $params = array(
		'w'		=> false,	// (int)width
		'h'		=> false,	// (int)height
		'q' 	=> 80, 		// (int)quality
		'a'		=> 'c',		// alignment
		'cc'	=> 'FFFFFF', // canvas color for resizing with borders 
		'fx'	=> array(),	// effects
		'rs'	=> 1	// (bool) resize/crop
	);

	/**
	  * @param int/string $img_src could be the image ID or the image path/url
	  * @param array $params (optional) thumbnail parameters
	  * @param bool $stream (optional) true to stream the image insead of returning the URL (TimThumb-like usage)
	  */
	public function get_thumb($img_src, $params = false, $stream = false) {
		// clean url parameters
		$img_src = preg_replace('/\\?.*/', '', $img_src);
		
		// connect to WP filesystem
		if(!$this->is_ready()) {return false;}
		global $wp_filesystem;
		
		// setup the parameters
		$this->setup_params($params);
		
		// check the source
		if(!$this->check_source($img_src)) {return false;}
		
		// get the correct path/url
		$img_src = $this->img_id_to_path($img_src);
		if(!filter_var($img_src, FILTER_VALIDATE_URL)) {
			if(!$img_src || !file_exists($img_src)) {
				$this->errors[] = __('WP image not found or invalid image path');
				return false;
			}	
		}
		
		// setup mime type and filenames
		$this->manage_filename($img_src); 
		$cache_fullpath = $this->cache_dir . '/' . $this->cache_img_name;
		
		// check for the image type
		$supported_mime = array('image/jpeg', 'image/png', 'image/gif');
		if(in_array($this->mime, $supported_mime) === false) {
			$this->errors[] = __('File extension not supported');
			return false;	
		}
		
		// check for existing cache files
		if($wp_filesystem->exists($cache_fullpath)) {
			return $this->return_image($this->cache_img_name, $stream);
		}
		
		//// use the wp image editor
		if( !$this->load_image($img_src) ) {return false;}
		
		// crop/resize the image
		$this->resize_from_position();
		
		// apply effects
		$this->editor->ewpt_img_fx( $this->params['fx'] );
		
		// save the image
		$img_content = $this->image_contents();
		if( !$this->create_file($this->cache_img_name, $img_content) ) {return false;}
		
		// return image	
		return $this->return_image($this->cache_img_name, $stream, $img_content);
	}
	
	
	/**
	  * Load the image into the editor
	  * @param string $img_src image path/url
	  */
	private function load_image($img_src) {
		if( (float)substr(get_bloginfo('version'), 0, 3) < 3.5) {
			$this->editor = new ewpt_old_wp_img_editor($img_src);
		} else {
			$this->editor = new ewpt_editor_extension($img_src);
			$this->editor->load();
		}	
		
		if(is_wp_error( $this->editor )) {
			$this->errors[] = 'WP image editor - ' . $this->editor->get_error_message();
			return false;
		} else {
			return true;	
		}
	}
	
	
	/**
	  * setup and sanitize the thumbnail parameters
	  * @param array $params associative array of parameters
	  */
	private function setup_params($params) {
		if($params && is_array($params) && count($params) > 0) {
			foreach($this->params as $key => $val) {
				if(isset($params[$key])) {
					
					// sanitize and save
					if(in_array($key, array('w', 'h', 'q', 'rs'))) {
						$this->params[$key] = (int)$params[$key];	
					}
					elseif($key == 'fx') {
						if(is_array($params[$key])) $params[$key] = implode(',', $params[$key]);
						$this->params[$key] = $this->ewpt_fx_array($params[$key]);
					}
					else {$this->params[$key] = $params[$key];}	
					
					// if there is no quality - set to 70
					if(!$this->params['q']) {$this->params['q'] = 70;}
					
					// if resizing parameter is wrong - set the default one
					if($this->params['rs'] > 3) {$this->params['rs'] = 1;}
					
					// canvas control 
					if(!preg_match('/^#[a-f0-9]{6}$/i', '#' . $this->params['cc'])) {
						$this->params['cc'] = 'FFFFFF';
					}
					
					// if there is no alignment - set it to the center
					$positions = array('tl','t','tr','l','c','r','bl','b','br');
					if(in_array($this->params['a'], $positions) === false) {$this->params['a'] = 'c';}
				}
			}
		}
		return true;
	}
	
	
	/**
	  * Convert the numeric string into an fx array
	  *
	  * @param string $fx_string numeric effects string
	  * @return array $pos array with the fx names
	  */
	private function ewpt_fx_array($fx_string) {
		$fx_string = str_replace(' ', '', $fx_string);
		if(strlen($fx_string) > 0) {$this->fx_filename = $fx_string . '_';}
		
		$fx_raw_arr = explode(',', $fx_string);
		$fx_arr = array();
		
		foreach($fx_raw_arr as $raw_fx) {
			if		($raw_fx == '1') {$fx_arr[] = 'grayscale';}	
			elseif	($raw_fx == '2') {$fx_arr[] = 'blur';}	
		}
		
		return $fx_arr;
	}
	
	

	/**
	  * Given the $img_src - set the cache filename and mime type 
	  * @param string $img_src image path/url
	  */
	private function manage_filename($img_src) {

		// remove the extension
		$pos = strrpos($img_src, '.');
		$clean_path = substr($img_src, 0, $pos);
		
		// get mime type
		$this->mime = $this->ewpt_mime_type($img_src, $pos);
		
		$cache_filename = $this->cache_filename($clean_path);
		
		// extension 
		switch ( $this->mime ) {
			case 'image/png': $ext = '.png'; break;
			case 'image/gif': $ext = '.gif'; break;
			default			: $ext = '.jpg'; break;
		}	
		
		$this->cache_img_name = $cache_filename . $ext;
		return $this->cache_img_name;
	}
	
	
	/**
	  * Given the $img_src - get the mime type 
	  *
	  * @param string $img_src image path/url
	  * @param int $pos position of the latest dot (to retrieve the file extension)
	  */
	private function ewpt_mime_type($img_name, $pos) {
		$ext = substr($img_name, ($pos + 1));
		$mime_types = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif' => 'image/gif',
			'png' => 'image/png',
		);
		$extensions = array_keys( $mime_types );

		foreach( $extensions as $_extension ) {
			if ( preg_match( "/{$ext}/i", $_extension ) ) {
				return $mime_types[$_extension];
			}
		}
		return false;
	}
	
	
	/**
	  * Return the cache filename without extension
	  */
	private function cache_filename($img_name) {
		$crypt_name = md5($img_name);
		$fx_val = (is_array($this->params['fx'])) ? 1 : 0;

		$cache_name = 
			$this->params['w'] . 'x' .
			$this->params['h'] . '_' .
			$this->params['q'] . '_' .
			$this->params['rs'] . '_' .
			$this->params['a'] . '_' .
			$this->params['cc'] . '_' .
			$this->fx_filename .
			$crypt_name;
		
		return $cache_name;	 
	}
	
	
	/**
	  * Convert an image id to its WP path 
	  *
	  * @param int/string $img_src could be the image ID or the image path/url
	  * @return (string) image path or false if not existent
	  */
	public function img_id_to_path($img_src) {
		if(is_numeric($img_src)) {
			$wp_img_data = wp_get_attachment_metadata((int)$img_src);
			if($wp_img_data) {
				$img_src = $this->basedir . '/' . $wp_img_data['file'];
			}
		}
		
		return $img_src;
	}

	
	/**
	  * Check the image source against allowed websites
	  *
	  * @param int/string $img_src could be the image ID or the image path/url
	  * @return (bool) true if allowed - false if not
	  */
	private function check_source($img_src) {
		if(EWPT_ALLOW_ALL_EXTERNAL || !filter_var($img_src, FILTER_VALIDATE_URL)) {return true;}
		
		$src_params = parse_url($img_src);
		
		$sites = unserialize(EWPT_ALLOW_EXTERNAL);
		if(!is_array($sites)) {$sites = array();}

		// add the current URL
		$sites[] = str_replace('www.', '', $_SERVER['HTTP_HOST']);
		
		// if is third livel - the main domain
		$arr = explode('.', $_SERVER['HTTP_HOST']);
		$el_num = count($arr);
		if($el_num > 2) {
			$sites[] = $arr[ $el_num - 2 ] . '.' . $arr[ $el_num - 1 ];		
		}

		$allowed = false;
		foreach($sites as $site){
			if ((strtolower( substr($src_params['host'],-strlen($site)-1)) === strtolower(".$site")) || (strtolower($src_params['host'])===strtolower($site))) {
				$allowed = true;
			}
		}
		
		if(!$allowed){
			$this->errors[] = 'The image source is not in the allowed websites';
			return false;
		} else {
			return true;	
		}
	}


	/**
	  * Resizing function using the position
	  */
	public function resize_from_position() {
		$size = $this->editor->get_size();
		
		$width = $this->params['w'];
		$height = $this->params['h'];
		
		// check and set sizes
		if(!$width && !$height) {
			$width = $size['width'];
			$height = $size['height'];
		}
		
		// generate new width or height if not provided
		else if($width && !$height) {
			$height = floor ($size['height'] * ($width / $size['width']));
		}
		elseif(!$width && $height) {
			$width = floor ($size['width'] * ($height / $size['height']));
		}

		// timthumb like management
		$this->editor->ewpt_tt_management($width, $height, $this->params['rs'], $this->params['a'], $this->mime, $this->params['cc']);

		return true;
	}
	
	
	/**
	  * Save the image contents into a variable
	  */
	private function image_contents() {
		ob_start();

		$this->editor->set_quality( $this->params['q'] );
		$this->editor->ewpt_img_contents( $this->mime );
		
		$contents = ob_get_contents();
		ob_end_clean();
		
		return $contents;
	}
	
	
	/**
	  * Stream the image 
	  */
	private function ewpt_stream_img() {
		$this->editor->set_quality( $this->params['q'] );
		return $this->editor->stream( $this->mime );	
	}
	
	
	/**
	  * Return the image URL or stream it
	  * @param string $filename cache filename of the image
	  * @param bool $stream flag to stream the image or not
	  * @param string $img_contents resource used to create the file
	  */
	private function return_image($filename, $stream = false, $img_contents = false) {
		if(!$stream) {
			return $this->cache_url . '/' . $filename;
		}
		else {
			// browser cache
			$cache_fullpath = $this->cache_dir . '/' . $this->cache_img_name;
			$method = $this->get_method();
			ewpt_manage_browser_cache($cache_fullpath, $method, $img_contents);

			if($img_contents === false) {
				$this->load_image($cache_fullpath);
				return $this->ewpt_stream_img();
			} 
			
			// display image resource that has just been created
			else { 
				$this->editor->stream( $this->mime );
			}
				
			die();
		}
	}	
	
}

/////////////////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////
// DIRECT FUNCTIONS TO CREATE THUMBS /////////////////
//////////////////////////////////////////////////////

// standard thumb creation - to use in wp pages
function easy_wp_thumb($img_src, $width = false, $height = false, $quality = 80, $alignment = 'c', $resize = 1, $canvas_col = 'FFFFFF', $fx = array()) {
	$params = array(
		'w'		=> $width,
		'h'		=> $height,
		'q' 	=> $quality,
		'a'		=> $alignment,
		'cc'	=> $canvas_col,
		'fx'	=> $fx,
		'rs'	=> $resize
	);
	
	$ewpt = new easy_wp_thumbs(EWPT_DEBUG_VAL);
	$thumb = $ewpt->get_thumb($img_src, $params);
	
	if(!$thumb) { return __('thumb creation failed');}
	else {return $thumb;}  
}


// remote thumb creation - timthumb-like solution
if( stristr($_SERVER['REQUEST_URI'], "easy_wp_thumbs.php") !== false && isset($_REQUEST['src']) ) {
	if (ob_get_level()) {ob_end_clean();}
	
	// check for external leechers
	ewpt_block_external_leechers();
	
	// clean url and get args
	$pos = strpos('?', $_SERVER['REQUEST_URI']) + 1;
	$clean_uri = substr($_SERVER['REQUEST_URI'], $pos); 
	$params = wp_parse_args($clean_uri);
	
	$ewpt = new easy_wp_thumbs(EWPT_DEBUG_VAL);
	$thumb = $ewpt->get_thumb($_REQUEST['src'], $params, $stream = true);
	
	if(!$thumb) {
		header ($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
		die( $ewpt->get_errors() );
	} 
}

///////////////////////////////////////////////////////////////


} // ewpt existing check end
?>
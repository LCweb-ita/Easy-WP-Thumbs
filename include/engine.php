<?php
// CONTAINS TWO CLASSES



///////////////////////////////////////////////////////////////
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

	
    
    
	// @param bool $debug_flag (optional) use 'ftp' or 'ssh' for debug
	public function __construct($debug_flag = false) {
		require_once('wp_func_includes.php');
  
        if(!function_exists('wp_upload_dir') || !function_exists('get_filesystem_method')) {
            die('WP functions not initialized');
        }

		// set the directories
		$upload_dirs = wp_upload_dir();
		$this->basedir = $upload_dirs['basedir'];		
		$this->cache_dir = $this->basedir . '/ewpt_cache';
		$this->cache_url = $upload_dirs['baseurl'] . '/ewpt_cache';
		
		if($debug_flag) {
            $this->debug = $debug_flag;
        }
		
		// if restricted server - get the saved credentials  
		if($this->get_method() != 'direct' || $this->debug) {
			$raw_creds = get_option('ewpt_creds');
			if($raw_creds) {
                $this->creds = json_decode( base64_decode( $raw_creds ));
            }
		}
			
        // optimization option
        $GLOBALS['ewpt_optim_format'] = get_option('ewpt_optimization_mode', '');
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
			$path = ($this->cache_dir_exists()) ? $this->cache_dir : $this->basedir;
		}
		
		if(!file_exists($path)) {
			$this->errors[] = 'get_method - '. __("path does not exist", 'ewpt_ml');
			return false;	
		}
		
		return ($this->debug) ? $this->debug : get_filesystem_method(array(), $path);
	}
	
	
    
	/**
	  * Check if everything has been set up to start creating thumbnails
	  */
	public function is_ready($recursing = false) {
		// check if the cache directory has been set
		if(!$this->cache_dir_exists()) {
			
			// if method = direct create it and check again
			if($this->get_method() == 'direct' && !$recursing) {
				mkdir($this->cache_dir, EWPT_CHMOD_DIR);	
				return $this->is_ready(true);
			}
			
			$this->errors[] = __("Cache folder doesn't exist", 'ewpt_ml');
			return false;
		}
		
		// force flag
		if(get_option('ewpt_force_ftp') && !defined('FS_METHOD')) {
            define('FS_METHOD', 'direct');
        } 
		
		// if has "direct access" 
		if($this->get_method( $this->cache_dir ) == 'direct') {
			WP_Filesystem(false, $this->cache_dir);
			return true;
		}
		
		// check saved credentials against WP_filesys
		else {
			if(!$this->creds || !WP_Filesystem($this->creds, $this->cache_dir)) {
				$this->errors[] = '01 - WP_filesystem - '. __("connection failed", 'ewpt_ml');
				return false;
			}
			
            return true;	
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
		if(empty($filename) || empty($contents)) {
			$this->errors[] = __('Filename or contents are missing', 'ewpt_ml');
			return false;	
		}
		
		// connect
		if(!$this->is_ready()) {
			
			// try another time forcing
			if(!defined('FS_METHOD')) {
				define('FS_METHOD', 'direct');
				
				if(!$this->is_ready()) {
					$this->errors[] = '02 - WP_filesystem - '. __('connection failed', 'ewpt_ml');
					return false;		
				}
			}
			else {
				$this->errors[] = '02 - WP_filesystem - '. __('connection failed', 'ewpt_ml');
				return false;	
			}
		}
		global $wp_filesystem;
		
		// create file
		$fullpath = $this->cache_dir .'/'. $filename; 
        
        if($GLOBALS['ewpt_optim_format']) {
            $fp_arr = explode('.', $fullpath);
            array_pop($fp_arr);
            
            $fullpath = implode('.', $fp_arr) . '.'. $GLOBALS['ewpt_optim_format'];
        }

		if(!$wp_filesystem->put_contents($fullpath, $contents, EWPT_CHMOD_FILE)) {
			$this->errors[] = __('Error creating the file', 'ewpt_ml') .' '. $filename;
			return false;	
		}
        
		return true;
	}

	
    
	/**
	  * Returns the errors
	  */
	public function get_errors() {
		if(count($this->errors) > 0){
			$html = '
            <h2>Easy WP Thumbs - '. __('errors occurred', 'ewpt_ml') .'</h2>
            <ul>';
			
			foreach($this->errors as $error) {
				$html .= '<li>'.$error.'</li>';	
			}
			
			$html .= '
            </ul>
			<hr/>
            <small>version '.EWPT_VER.'</small>';
			
			return $html ;
		}
		
        return false;
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
	  * @param (int/string) $img_src could be the image ID or the image path/url
	  * @param (array) $params (optional) thumbnail parameters
	  * @param (bool) $stream (optional) true to stream the image insead of returning the URL (TimThumb-like usage)
	  */
	public function get_thumb($img_src, $params = false, $get_url_if_not_cached = false, $stream = false) {
		@ini_set('memory_limit','768M');

		// connect to WP filesystem
		if(!$this->is_ready()) {
            return false;
        }
		global $wp_filesystem;
		
		// setup the parameters 
		$this->setup_params($params);
		
		// check the source
		if(!$this->check_source($img_src)) {
            return false;
        }
		
		// get the correct path/url
		$img_src = $this->img_id_to_path($img_src);
        
		if(!filter_var($img_src, FILTER_VALIDATE_URL)) {
			if(!$img_src || !file_exists($img_src)) {
				$this->errors[] = __('WP image not found or invalid image path', 'ewpt_ml');
				return false;
			}	
		}
		
		// setup mime type and filenames
		$this->manage_filename($img_src); 
		$cache_fullpath = $this->cache_dir .'/'. $this->cache_img_name;
		
		// check for the image type
		$supported_mimes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        if(function_exists('imageavif')) {
            $supported_mimes[] = 'image/avif';   
        }
        
		if(in_array($this->mime, $supported_mimes) === false) {
			$this->errors[] = __('File extension not supported', 'ewpt_ml');
			return false;	
		}
        
        
		// check for existing cache files
		if($wp_filesystem->exists($cache_fullpath)) {
            return $this->return_image($this->cache_img_name, $stream);
		}
        
        // no cache, want to return remote URL to not weight on server?
        else {
            if($get_url_if_not_cached && filter_var($get_url_if_not_cached, FILTER_VALIDATE_URL)) {
                return $get_url_if_not_cached .'?'.
                    'src='. urlencode($img_src) .'&'.
                    'w='. $this->params['w'] .'&'.
                    'h='. $this->params['h'] .'&'.
                    'q='. (int)$this->params['q'] .'&'.
                    'a='. $this->params['a'] .'&'.
                    'rs='. $this->params['rs'] .'&'.
                    'cc='. urlencode($this->params['cc']);        
            }
        }
		
		//// use the wp image editor
		if(!$this->load_image($img_src)) {
            $this->errors[] = __('Error loading image', 'ewpt_ml');
            return false;
        }
        
		// crop/resize the image
		$this->resize_from_position();
		
		// apply effects
		$this->editor->ewpt_img_fx( $this->params['fx'] );
		
		// save the image
		$img_contents = $this->image_contents();
        
		if( !$this->create_file($this->cache_img_name, $img_contents) ) {
            $this->errors[] = __('Error creating thumbnail file', 'ewpt_ml');
            return false;
        }
		
		// return image	
		return $this->return_image($this->cache_img_name, $stream, $img_contents);
	}
    
    
    
	/**
	  * Load the image into the editor
	  * @param string $img_src image path/url
	  */
	private function load_image($img_src) {
		
		// get data - url case
		if(filter_var($img_src, FILTER_VALIDATE_URL) || strpos( str_replace('https://', 'http://', strtolower($img_src)), 'http://') !== false) {
			$data = wp_remote_get($img_src, array('timeout' => 3, 'redirection' => 3));

			// nothing got - use cURL 
	        if(is_wp_error($data) || 200 != wp_remote_retrieve_response_code($data) || empty($data['body'])) {
				$followlocation = (!ini_get('open_basedir') && !ini_get('safe_mode')) ? true : false;
                
                $ch = curl_init();
				curl_setopt($ch, CURLOPT_AUTOREFERER, true);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_USERAGENT, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
				curl_setopt($ch, CURLOPT_URL, $img_src);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followlocation);
				
				$data = curl_exec($ch);
				$mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
				curl_close($ch);	
	        }
			else {
				$data = $data['body'];	
			}
		}
        
        // get data - local case
		else {
			$data = @file_get_contents($img_src);		
		}
        
		$this->editor = new ewpt_editor_extension($data, $img_src);
		
		// check the resource and eventually uses the GD library
		if($this->editor->ewpt_is_valid_resource() ) {
            
			$this->editor->ewpt_setup_img_data();	
			$this->mime = $this->editor->pub_mime_type; // safe mime
            
            if(method_exists($this->editor, 'maybe_exif_rotate')) {
                $this->editor->maybe_exif_rotate();
            }
            return true;	
		}
		else {
			$this->errors[] = 'WP image editor - '. __('Invalid image data', 'ewpt_ml');
			return false;
		}
	}
	

    
	/**
	  * setup and sanitize the thumbnail parameters
	  * @param array $params associative array of parameters
	  */
	private function setup_params($params) {
		if(!is_array($params) || empty($params)) {
            return true;
        }
            
        foreach($this->params as $key => $val) {
            if(!isset($params[$key])) {
                continue;    
            }

            // sanitize and save
            if(in_array($key, array('w', 'h', 'q', 'rs'))) {
                $this->params[$key] = (int)$params[$key];	
            }
            elseif($key == 'fx') {
                if(is_array($params[$key])) {
                    $params[$key] = implode(',', $params[$key]);
                }
                $this->params[$key] = $this->ewpt_fx_array($params[$key]);
            }
            else {
                $this->params[$key] = $params[$key];
            }	

            // if there is no quality - set to 70
            if(!$this->params['q']) {
                $this->params['q'] = 70;
            }

            // if resizing parameter is wrong - set the default one
            if($this->params['rs'] > 3) {
                $this->params['rs'] = 1;
            }

            // canvas control 
            if(!preg_match('/^#[a-f0-9]{6}$/i', '#'. $this->params['cc'])) {
                $this->params['cc'] = 'FFFFFF';
            }

            // if there is no alignment - set it to the center
            $positions = array('tl','t','tr','l','c','r','bl','b','br');
            if(in_array($this->params['a'], $positions) === false) {
                $this->params['a'] = 'c';
            }
        }
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
			if($raw_fx == '1') {
                $fx_arr[] = 'grayscale';
            }	
			elseif($raw_fx == '2') {
                $fx_arr[] = 'blur';
            }	
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
		
		$this->mime = $this->mime_type($img_src, $pos);

		// extension 
		switch($this->mime) {
			case 'image/webp' : 
                $ext = '.webp'; 
                break;
                
            case 'image/avif' : 
                $ext = '.avif'; 
                break;
            
            case 'image/png' : 
                $ext = '.png'; 
                break;
                
			case 'image/gif' : 
                $ext = '.gif'; 
                break;
                
			default	: 
                $ext = '.jpg'; 
                break;
		}	
        
        if($GLOBALS['ewpt_optim_format'] == 'webp') {
            $ext = '.webp';
        }
        elseif($GLOBALS['ewpt_optim_format'] == 'avif') {
            $ext = '.avif';
        }

		$this->cache_img_name = $this->cache_filename($img_src) . $ext;
		return $this->cache_img_name;
	}
	
    
	
	/**
	  * Given the $img_src - get the mime type 
	  *
	  * @param string $img_src image path/url
	  * @param int $pos position of the latest dot (to retrieve the file extension)
	  */
	private function mime_type($img_name, $pos) {
		$ext = substr($img_name, ($pos + 1), 3);
		
        $mime_types = array(
			'jpg|jpe'    => 'image/jpeg',
			'gif'        => 'image/gif',
			'png'        => 'image/png',
            'avif'       => 'image/avif',
            'webp'       => 'image/webp',
		);
		$extensions = array_keys( $mime_types );
		
		if($ext) {
			foreach( $extensions as $_extension ) {
				if(preg_match( "/{$ext}/i", $_extension ) ) {
					return $mime_types[$_extension];
				}
			}
		}
		
		return 'image/jpeg'; // nothing found, guess it's a jpg
	}
	
    
	
	/**
	  * Return the cache filename without extension
	  */
	private function cache_filename($img_name) {
		$crypt_name = md5($img_name);
		$fx_val = (is_array($this->params['fx'])) ? 1 : 0;
		
		// seo filename part
		if(EWPT_SEO_CACHE_FILENAME && strlen($img_name) < 50) {
			$arr = explode('#', $img_name);		$img_name = $arr[0];
			$arr = explode('?', $img_name);		$img_name = $arr[0];	
			$arr = explode('/', $img_name);		$img_name = end($arr);
			
			$exts = array('.jpg', '.JPG', '.jpeg', '.JPEG', '.png', '.PNG', '.gif', '.GIF', '.webp', '.WEBP', '.avif', 'AVIF');
			$img_name = str_replace($exts, '', $img_name);
			
			$seo_fn = '_'. sanitize_title(str_replace('%', '', urlencode($img_name)));
		}
		else {
            $seo_fn = '';
        }
		
		$cache_name = 
			$this->params['w'] . 'x' .
			$this->params['h'] . '_' .
			$this->params['q'] . '_' .
			$this->params['rs'] . '_' .
			$this->params['a'] . '_' .
			$this->params['cc'] . '_' .
			$this->fx_filename .
			$crypt_name .
			$seo_fn;

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
                // mar-2023 - WP doesn't return data for avif
				$img_src = (isset($wp_img_data['file'])) ? $this->basedir . '/' . $wp_img_data['file'] : get_attached_file($img_src);
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
		if(EWPT_ALLOW_ALL_EXTERNAL || !filter_var($img_src, FILTER_VALIDATE_URL)) {
            return true;
        }
		
		$src_params = parse_url($img_src);		
		$sites = unserialize(EWPT_ALLOW_EXTERNAL);

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
			if ((strtolower( substr($src_params['host'],-strlen($site)-1)) === strtolower(".$site")) || (strtolower($src_params['host']) === strtolower($site))) {
				$allowed = true;
			}
		}
		
		if(!$allowed){
			$this->errors[] = __('Image source is not among allowed websites', 'ewpt_ml');
			return false;
		}
        
        return true;	
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
		$this->editor->ewpt_img_contents();
		
		$contents = ob_get_contents();
		ob_end_clean();
		
		return $contents;
	}
	
	
    
	/**
	  * Stream the image 
	  */
	private function stream_img() {
		$this->editor->set_quality( $this->params['q'] );
        
        $stream_mime = $this->mime;
        if($GLOBALS['ewpt_optim_format'] == 'webp') {
            $stream_mime = 'image/webp';
        }
        elseif($GLOBALS['ewpt_optim_format'] == 'avif') {
            $stream_mime = 'image/avif';
        }
        
		return $this->editor->stream( $stream_mime );	
	}
	
    
	
	/**
	  * Return the image URL or stream it
      *
	  * @param string $filename cache filename of the image
	  * @param bool $stream flag to stream the image or not
	  * @param string $img_contents resource used to create the file
	  */
	private function return_image($filename, $stream = false, $img_contents = false) {
        if(!$stream) {
			return str_replace(array('http:', 'https:', 'HTTP:', 'HTTPS:'), '', $this->cache_url) .'/'. $filename;
		}
        
		else {
            $cache_fullpath = $this->cache_dir .'/'. $this->cache_img_name;
            
			if(!$img_contents) {
                $this->load_image($cache_fullpath); 
            }
            
            // set filename to avoid WP editor issues
            $this->editor->ewpt_setup_filename( $this->cache_img_name );
            
            $this->stream_img();
            die();
		}
	}
}
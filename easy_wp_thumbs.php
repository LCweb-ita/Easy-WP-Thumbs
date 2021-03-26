<?php
/**
 * Easy WP thumbs v3.0
 * NOTE: Designed for use with PHP version 5.2 and up. Requires at least WP 3.5
 * 
 * @author Luca Montanari aka LCweb
 * @copyright 2021 Luca Montanari - https://lcweb.it
 *
 * Licensed under the MIT license
 */
 


// be sure ewpt has not been initialized yet
if(defined('EWPT_VER')) { 
    return false;
}
define('EWPT_VER', '3.0');
define('EWPT_ERROR_PREFIX', 'Easy WP Thumbs v'.EWPT_VER.' - '); 





// MAIN CONFIGURATIONN DEFINES
define('EWPT_FS_DEBUG_VAL', ''); 			// (string) wp filesystem debug value - use 'ftp' or 'ssh' - on production must be left empty
define('EWPT_BLOCK_LEECHERS', false); 		// (bool) block thumb loading on other websites
define('EWPT_ALLOW_ALL_EXTERNAL', true);	// (bool) allow fetching from any website - set to false to avoid security issues
define('EWPT_SEO_CACHE_FILENAME', true);	// (bool) whether to add original image name to cache file in order to help SEO

// forcing via REQUEST parameter
if(isset($_REQUEST['ewpt_force']) && !defined('FS_METHOD')) {
	define('FS_METHOD', 'direct');	
}


$allowed_external = array(
    'flickr.com',
	'staticflickr.com',
	'google.com', // google drive direct link
	'img.youtube.com',
	'upload.wikimedia.org',
	'photobucket.com',
	'imgur.com',
	'imageshack.us',
	'tinypic.com',
	'pinterest.com',
	'pinimg.com', // new pinterest
	'fbcdn.net', // fb,
	'akamaihd.net', // new fb
	'amazonaws.com',  // instagram
	'cdninstagram.com', // new instagram
	'instagram.com',
	'dropboxusercontent.com',
	'tumblr.com',
	'500px.net',
	'500px.org'    
);
define('EWPT_ALLOW_EXTERNAL', $allowed_external); // (array) array of allowed websites where the script can fetch images



// WP CHMOD constants check
$dir_chmod  = (!defined('FS_CHMOD_DIR') || !FS_CHMOD_DIR) ? 0755 : FS_CHMOD_DIR;
$file_chmod = (!defined('FS_CHMOD_FILE') || !FS_CHMOD_FILE) ? 0644 : FS_CHMOD_FILE;

define('EWPT_CHMOD_DIR', $dir_chmod);
define('EWPT_CHMOD_FILE', $file_chmod);





//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////





// ENGINE PARTS INCLUDE
require_once('include/engine.php');
require_once('include/helpers.php');
require_once('include/status_panel.php');






//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////





// SHORTCUT FUNCTION TO CREATE THUMB (to be used in WP pages)
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

	$ewpt = new easy_wp_thumbs(EWPT_FS_DEBUG_VAL);
	$thumb = $ewpt->get_thumb($img_src, $params);
	
	return (!$thumb) ? __('thumb creation failed', 'ewpt_ml') : $thumb;  
}





// REMOTE URL THUMB CREATION (TIMTHUMB-LIKE SOLUTION)
if(stristr($_SERVER['REQUEST_URI'], "easy_wp_thumbs.php") !== false && isset($_REQUEST['src']) ) {
	if(ob_get_level()) {
        ob_end_clean();
    }
    
    ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);	
	
    
	// check for external leechers
	ewpt_helpers::block_external_leechers();
    
    // browser cache based onn URL
    ewpt_helpers::manage_browser_cache($_SERVER['REQUEST_URI']);
    
    
	// clean url and get args
    $url_arr = explode('?', $_SERVER['REQUEST_URI']);
	parse_str($url_arr[1], $params);
	
	$ewpt  = new easy_wp_thumbs(EWPT_FS_DEBUG_VAL);
	$url   = urldecode(trim($_REQUEST['src']));
	
    
	$thumb = $ewpt->get_thumb($url, $params, $stream = true);
	if(!$thumb) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
		die($ewpt->get_errors());
	} 
}

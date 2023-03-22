<?php
/**
 * Easy WP thumbs v3.2.0
 * NOTE: Designed for use with PHP version 5.2 and up. Requires at least WP 3.5
 * 
 * @author Luca Montanari (LCweb)
 * @copyright 2023 Luca Montanari - https://lcweb.it
 *
 * Licensed under the MIT license
 */
 


// be sure ewpt has not been initialized yet
if(!defined('EWPT_VER')) { 
    define('EWPT_VER', '3.2.0');
    define('EWPT_ERROR_PREFIX', 'Easy WP Thumbs v'.EWPT_VER.' - '); 





    // MAIN CONFIGURATIONN DEFINES
    define('EWPT_FS_DEBUG_VAL', ''); 			// (string) wp filesystem debug value - use 'ftp' or 'ssh' - on production must be left empty
    define('EWPT_BLOCK_LEECHERS', false); 		// (bool) block thumb loading on other websites
    define('EWPT_ALLOW_ALL_EXTERNAL', false);	// (bool) allow fetching from any website - set to false to avoid security issues
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
    define('EWPT_ALLOW_EXTERNAL', serialize($allowed_external)); // (string) serialized array of allowed websites where the script can fetch images (to serialize against bad PHP versions)



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


    
    
    
    // allow AVIF upload
    if(function_exists('add_filter')) {
        add_filter('upload_mimes', function($mimes) {
            if(!isset($mimes['avif'])) {
                $mimes['avif'] = 'image/avif';    
            }
            return $mimes;    
        }, 999);   
    }
    




    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////




    
    /* SHORTCUT FUNCTION TO CREATE THUMB (to be used in WP pages)
     *
     * @param (string|int) $img_src - image source: could be the URL, a local path or a WP image ID
     * @param (array) $w_jolly - thumb parameters definition. Array keys:
     *
        (int|bool)  w  - thumbnail's width. False to auto-calculate while using scaling function
        (int|bool)  h  - thumbnail's height. False to auto-calculate while using scaling function
        (int)       q  - thumbnail's quality: 1 to 100
        (string)    a  - thumbnail's cropping center. Possible values: tl, t, tr, l, c, r, bl, b, br. c = center, t = top, b = bottom, r = right, l = left
        (int)       rs - resizing method: 1 = Resize and crop, 2 = Resize and add borders, 3 = Only resize 
        (string)    cc - background / borders color – use hexadecimal values
        (array)     fx - effects applied to the image (1 = grayscale, 2 = blur)
        
        (string|bool) get_url_if_not_cached - whether to return remote thumb URL if image is not cached, to avoid page's opening slowdowns. Use false of the easy_wp_thumbs.php file URL       
     * @return (string) thumbnail URL or error message
     */
    function easy_wp_thumb($img_src, $w_jolly = false, $h = false, $quality = 80, $align = 'c', $resize = 1, $canvas_col = 'FFFFFF', $fx = array(), $get_url_if_not_cached = false) {
        
        // old retrocompatibility definition way
        if(!is_array($w_jolly)) {
            $params = array(
                'w'		=> $w_jolly,
                'h'		=> $h,
                'q' 	=> $quality,
                'a'		=> $align,
                'cc'	=> $canvas_col,
                'fx'	=> $fx,
                'rs'	=> $resize
            );
        }
        
        // quick params definition via array
        else {
            $params = $w_jolly;
            $get_url_if_not_cached = (isset($param['get_url_if_not_cached'])) ? $param['get_url_if_not_cached'] : false;
        }

        $ewpt = new easy_wp_thumbs;
        $thumb = $ewpt->get_thumb($img_src, $params, $get_url_if_not_cached);

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

        $ewpt  = new easy_wp_thumbs;
        $url   = urldecode(trim($_REQUEST['src']));


        $thumb = $ewpt->get_thumb($url, $params, false, $stream = true);
        if(!$thumb) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
            die($ewpt->get_errors());
        } 
    }
}

<?php
if(!function_exists('wp_upload_dir') || !function_exists('get_filesystem_method')) {
    
    if(!function_exists('get_option')) {
        $curr_path = dirname(__FILE__);
        $curr_path_arr = explode(DIRECTORY_SEPARATOR, $curr_path);

        $true_path_arr = array();
        foreach($curr_path_arr as $part) {
            if($part == 'wp-content') {
                break;
            }
            $true_path_arr[] = $part;
        }	
        $true_path = implode('/', $true_path_arr);

        // main functions
        ob_start();
        if(!@file_exists($true_path .'/wp-load.php')) {
            die('<p>'.$error_prefix. 'wp-load.php file not found</p>');
        }
        else {
            require_once($true_path . '/wp-load.php');
        }
    }


    if(!function_exists('get_filesystem_method')) {

        // wp-admin/includes/file.php - for wp_filesys
        if(!file_exists(ABSPATH . 'wp-admin/includes/file.php')) {
            die('<p>'.$error_prefix. 'file.php file not found</p>');
        }
        else {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }	
    }
}



/* Check minimum WP version - 3.5 */
global $wp_version;
if(version_compare($wp_version, '3.5', '<=')) {
    die('Easy WP Thumbs - minimum requirement WordPress v3.5');
}




// extend WP editor classes to get image resources directly
if(_wp_image_editor_choose() == 'WP_Image_Editor_Imagick') {
    require_once('imagick_editor_extension.php');
}
else {	
    include_once(ABSPATH .'wp-includes/class-wp-image-editor.php');
    include_once(ABSPATH .'wp-includes/class-wp-image-editor-gd.php');
    
    require_once('gd_editor_extension.php');
}
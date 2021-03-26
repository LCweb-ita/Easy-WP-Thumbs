Easy WP Thumbs - by LCweb
==============

Easy WP Thumbs is a PHP script created with the purpose to make **WordPress thumbnails** creation process as easy and smooth as possible.<br/>
Inspired by TimThumb, is based on WP systems for the maximum compatibility. 

Requires at least WP 3.5 and PHP 5.2

Supports WP 3.5 WP_Image_Editor class, using **Imagick** where available.

<br/>


## How to use

1 - include easy_wp_thumbs.php in the main plugin/theme file
<br/><br/> 

2 - on common servers you are ready to go, move to step 3.<br/>While on hostings preventing direct files access you must check script status and, eventually, insert FTP/SSH credentials

    <?php
    // (optional) setup WP multilang functions to target your multilang domain 
    ewpt_status_panel::$multilang_key = 'your_multilanguage_key'; 
    
    // print status panel. $wrap_with_form_tag = false if you are putting it in an existing form 
    echo ewpt_status_panel::get($wrap_with_form_tag = false); 
    ?>
<br/>


3 - Create thumbnails (check [documentation](http://www.lcweb.it/easy-wp-thumbs-php-script)  to know more about parameters)

    // inline PHP function returning static image URL
    <php
    easy_wp_thumb($img_src, $width, $height, $quality, $alignment, $resize, $canvas_col, $fx);
    ?>
    
    // Timthumb-like async thumb creation (useful to not weight tons of processes on a single page)
    <img src="<?php echo 'SCRIPT-URL/easy_wp_thumbs.php?src= &w= &h= &q= &a= &rs= &cc= &fx=' ?>" />
    
    


<br/>

## Documentation

To know more about parameters check [this page](http://www.lcweb.it/easy-wp-thumbs-php-script) 




* * *

Copyright &copy; Luca Montanari - LCweb
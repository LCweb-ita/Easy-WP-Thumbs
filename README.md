Easy WP Thumbs - by LCweb
==============

Easy WP Thumbs is a PHP script created with the purpose to make **WordPress thumbnails** creation process as easy and smooth as possible.
Inspired by TimThumb, is based on WP systems for the maximum compatibility. 

Requires at least WP 3.5 and PHP 5.2

Supports WP 3.5 WP_Image_Editor class, using **Imagick** where available.<br/>
Supports also **WEBP** and **AVIF** (_whenever WP will be ready_) thumbnails creation.<br/>
Creates thumbnails also from **PDF files!** (_requires Imagick_)


## How to use

Include `easy_wp_thumbs.php` in the main plugin/theme file.  
On common servers you are ready to go.  
While on hostings preventing direct files access you must check script status and, eventually, insert FTP/SSH credentials:

```php
<?php
// print status panel. $wrap_with_form_tag = false if you are putting it in an existing form 
echo ewpt_status_panel::get($wrap_with_form_tag = false); 
?>
```

Now you can create thumbnails:

```php
<?php 
/*
 * @param (string|int) $img_src - image source: could be the URL, a local path or a WP image ID
 * @param (array) $params - thumb parameters definition. Array keys:
 *
    (int|bool)  w  - thumbnail's width. False to auto-calculate while using scaling function
    (int|bool)  h  - thumbnail's height. False to auto-calculate while using scaling function
    (int)       q  - thumbnail's quality: 1 to 100
    (string)    a  - thumbnail's cropping center. Possible values: tl, t, tr, l, c, r, bl, b, br. 
                      c = center, t = top, b = bottom, r = right, l = left
    (int)       rs - resizing method: 1 = Resize and crop, 
                                          2 = Resize and add borders, 
                                          3 = Only resize 
    (string)    cc - background / borders color – use hexadecimal values
    (array)     fx - effects applied to the image (1 = grayscale, 2 = blur)
    (string|bool) get_url_if_not_cached - whether to return remote thumb URL if image is not cached, 
                                          to avoid page's opening slowdowns. 
                                          Use false or the easy_wp_thumbs.php file URL
 * @return (string) thumbnail URL or error message
 */
$params = array(
    'w' => (int|bool),
    'h' => (int|bool),
    'q' => (int),
    'a' => (string),
    'rs' => (int),
    'cc' => (string),
    'fx' => (array),
    'get_url_if_not_cached' => (string|bool)
);
echo '<img src="'. easy_wp_thumb($img_src, $params) .'" alt="my-thumb" />';
?>
```

Timthumb-like, async thumbnail creation (useful for Javascript integrations):

```html
<img src="SCRIPT-URL/easy_wp_thumbs.php?src=&w=&h=&q=&a=&rs=&cc=&fx=" />
```

---

## License

Copyright &copy; Luca Montanari - LCweb
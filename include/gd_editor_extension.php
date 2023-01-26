<?php
class ewpt_editor_extension extends WP_Image_Editor_GD {	
    
    public $pub_mime_type; // public var to get true mime type
    private $img_binary_data; // image binary data used to get mime type


    /* setup GD object  */
    public function __construct($data) {
         $this->img_binary_data = $data;
         $this->image = imagecreatefromstring($data);
    }

    

    /**
     * Apply efects to the image
     * @param array $fx_array array of effects
     */
    public function ewpt_img_fx($fx_array) {
        if(!is_array($fx_array)) {
            return false;
        }

        foreach($fx_array as $fx) {
            switch($fx) {
                case 'blur' :
                    imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR); 
                    break;
                    
                case 'grayscale': 
                    imagefilter($this->image, IMG_FILTER_GRAYSCALE); 
                    break;	
            }
        }
        
        return true;
    }


    
    /**
     * Check if a valid GD resource exists
     */
    public function ewpt_is_valid_resource() {
         return (!is_object($this->image)) ? false : true;
    }


    
    /**
     * setup image data 
     */
    public function ewpt_setup_img_data($guessed_mime = 'image/jpeg') {
        if(!function_exists('getimagesizefromstring')) {
            $uri = 'data://application/octet-stream;base64,'. base64_encode($this->img_binary_data);
            $data = @getimagesize($uri);
        }
        else {
            $data = getimagesizefromstring($this->img_binary_data);	
        }

        parent::update_size($data[0], $data[1]);

        $this->mime_type = $data['mime'];
        $this->pub_mime_type = $this->mime_type;
    }

    
    
    /**
     * Setup filename - usaful to fix issues with streaming functions
     */
    public function ewpt_setup_filename($path) {	
        $this->file = $path;
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
        if($mime == 'image/jpeg' || $zoom_crop == 2) {
            $color = imagecolorallocatealpha ($canvas, $canvas_color_R, $canvas_color_G, $canvas_color_B, 127);		
        }else{
            $color = imagecolorallocatealpha ($canvas, $canvas_color_R, $canvas_color_G, $canvas_color_B, 0);
        }

        // Completely fill the background of the new image with allocated color.
        imagefill($canvas, 0, 0, $color);


        // scale down and add borders
        if($zoom_crop == 2) {
            $final_height = $height * ($new_width / $width);

            if($final_height > $new_height) {

                $origin_x = $new_width / 2;
                $new_width = $width * ($new_height / $height);
                $origin_x = round ($origin_x - ($new_width / 2));
            } 
            else {

                $origin_y = $new_height / 2;
                $new_height = $final_height;
                $origin_y = round ($origin_y - ($new_height / 2));
            }
        }


        // Restore transparency blending
        imagesavealpha($canvas, true);

        if($zoom_crop > 0) {
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

            @imagecopyresampled ($canvas, $image, $origin_x, $origin_y, $src_x, $src_y, $new_width, $new_height, $src_w, $src_h);

        } else {
            @imagecopyresampled ($canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
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
    public function ewpt_img_contents() {
        switch($this->mime_type) {
            case 'image/png' :	
                return imagepng($this->image);
                break;
                
            case 'image/gif' :
                return imagegif($this->image);
                break;
                
            default : 
                return imagejpeg($this->image, null, $this->quality);
                break;
        }
    }	
}	
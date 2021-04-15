<?php
class ewpt_editor_extension extends WP_Image_Editor_Imagick {
		
    public $pub_mime_type; // public var to get true mime type
    private $img_binary_data; // image binary data used to get mime type


    
    /* setup Imagick object  */
    public function __construct($data) {
        $this->img_binary_data = $data;

        try{
            $imagick = new Imagick();
            $response = $imagick->readImageBlob($data);
            $this->image = $imagick;
        }
        catch(Exception $e) {
            echo $e; //debug	
            $this->image = false;
        }
    }


    
    /**
     * Given the mime-type returns the last parameter for the imagick functions 
     */
    private function ewpt_mime_to_ext($mime) {
        $arr = explode('/', $mime);
        return end($arr);	
    }


    
    /**
     * Check if a valid imagick resource exists
     */
    public function ewpt_is_valid_resource() {
        return (!is_object($this->image) || !$this->image->valid()) ? false : true;
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
     * Manage the resize and/or crop using the Timthumb v2.8.10 structure with imagick functions
     * © Luca Montanari aka LCweb
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
        $new_width =  (int)abs($width);
        $new_height = (int)abs($height);
        $zoom_crop = (int)$rs;
        $align = $a;

        // Get original width and height
        $size = $this->get_size();
        $width = $size['width'];
        $height = $size['height'];
        $origin_x = 0;
        $origin_y = 0;

        // set the canvas background color
        if($mime == 'image/jpeg' || $zoom_crop == 2) {
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
        else {
            $canvas_color = 'transparent';
        }

        // if GIF - take first frame
        if($mime == 'image/gif') {
            $this->image = $this->image->coalesceImages();
        } 

        // stretch the image to the size
        if(!$zoom_crop) {
            $this->image->resizeimage($new_width, $new_height, imagick::FILTER_POINT, 0, false); 
        }

        // scale and add borders
        else if($zoom_crop == 2) {
            //scale 
            $ratio = min($new_width/$width, $new_height/$height);
            $_new_w = round($width * $ratio);
            $_new_h = round($height * $ratio); 
            $this->image->resizeimage($new_width, $new_height, imagick::FILTER_POINT, 0, true);

            $border_w = ($_new_w == $new_width) ? 0 : ceil(($new_width - $_new_w) / 2);
            $border_h = ($_new_h == $new_height) ? 0 : ceil(($new_height - $_new_h) / 2);
            
            $this->image->borderImage($canvas_color, $border_w, $border_h);
        }

        // only scale image
        else if($zoom_crop == 3) {
            $this->image->resizeimage($new_width, $new_height, imagick::FILTER_POINT, 0, true);	
        }

        // case #1 - scale and crop
        else {
            //scale 
            $ratio = max($new_width/$width, $new_height/$height);
            $_new_w = ceil($width * $ratio);
            $_new_h = ceil($height * $ratio); 
            $this->image->scaleimage($_new_w, $_new_h, true);


            // coordinates to cut from center
            $src_x = ($_new_w == $new_width) ? 0 : floor(($_new_w - $new_width) / 2);
            $src_y = ($_new_h == $new_height) ? 0 : floor(($_new_h - $new_height) / 2);

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
        if(!is_array($fx_array)) {
            return false;
        }

        foreach($fx_array as $fx) {
            switch($fx) {
                case 'blur' : 
                    $this->image->blurImage(1,3); 
                    break;
                    
                case 'grayscale': 
                    $this->image->modulateImage(100,0,100); 
                    break;	
            }
        }
        
        return true;
    }


    /**
     * Returns stream of current image.
     */
    public function ewpt_img_contents() {
        $this->image->setImageFormat($this->ewpt_mime_to_ext($this->mime_type));
        
        echo $this->image->getImageBlob();
        return true;
    }
}
<?php
// WP FILESYSTEM CHECK FOR THE ADMIN PANEL

/*
    usage:
    
    ewpt_status_panel::$multilang_key = '...'; (optional)
    echo ewpt_status_panel::get($wrap_with_form = false); // prints HTML code  
*/



class ewpt_status_panel {
    public static $multilang_key = 'ewpt_ml';
        
    
    /*
     * Returns status panel code
     * @param $wrap_with_form - whether to wrap the code into a FORM HTML tag
     */
    public static function get($wrap_with_form = false) {
        $code = '';
        
        if($wrap_with_form) {
            $code .= '<form class="ewpt_form">';
        }
        $code .= '
        <div id="ewpt_ajax_response"></div>
        <div id="ewpt_wrapper"></div>';
        
        if($wrap_with_form) {
            $code .= '</form>';
        }

        $code .= '
        <script type="text/javascript">
        (function() { 
	       "use strict";
           
            // setup the loader
            const show_loader = function() {
                document.getElementById("ewpt_wrapper").innerHTML = \'<img alt="loading .." style="padding: 0 15px 15px; width: 30px;" src="'. get_site_url() .'/wp-includes/images/spinner-2x.gif" />\';	
            };
            


            // erase cache
            const erase_cache = async function() {
                if(!confirm("'. esc_attr__('Confirm cache files deletion?', self::$multilang_key) .'")) {
                    return false;    
                } 
                
                
                show_loader();	
                document.getElementById("ewpt_ajax_response").innerHTML = "";
                
                    
                const data = {
                    action      : "ewpt_erase_cache",
                    ewpt_nonce  : "'. wp_create_nonce('ewpt_nonce') .'"
                };
                
                await fetch(ajaxurl, format_for_wp_ajax(data)).then(async response => {
                    
                    if(response.ok) {
                        const html = (await response.text());
                        document.getElementById("ewpt_wrapper").innerHTML = html;

                        if(document.querySelector("#ewpt_wrapper input[type=submit]")) {
                            document.getElementById("ewpt_ajax_response").innerHTML = err_mess;	
                        }
                    }
                    else {
                        document.getElementById("ewpt_ajax_response").innerHTML = "Easy WP Thumbs '. esc_attr__('error') .' "+ response.status +": "+ response.statusText;     
                        document.getElementById("ewpt_wrapper").innerHTML = "";
                    }
                })
                .catch(error => {
                    console.error(error);
                    
                    document.getElementById("ewpt_ajax_response").innerHTML = "Easy WP Thumbs '. esc_attr__('error') .' "+ JSON.stringify(error);
                    document.getElementById("ewpt_wrapper").innerHTML = "";
                });   
            };
            
            
            
            // show the form or the status - ajax
            const setup = async function(step) {
                const err_mess = 
                    `<div id="ewpt_message" class="error">
                        <p>'. esc_html__('<strong>Server connection error</strong>, please check inserted values', self::$multilang_key) .'</p>
                    </div>`;
                
                
                // retrieve form data
                let data = {
                    action      : "ewpt_status_check",
                    ewpt_nonce  : "'. wp_create_nonce('ewpt_nonce') .'"
                };
                
                const btn       = recursive_parent( document.querySelector("#ewpt_wrapper #upgrade"), "form"),
                      wrap      = document.getElementById("ewpt_wrapper"),
                      form_vals = (btn) ? Object.fromEntries(new FormData(btn)) : {};    
                    
                Object.keys(form_vals).forEach((fname) => {
                    
                    if(wrap.contains( document.querySelector(\'*[name="\'+ fname +\'"]\') )) {
                        data[fname] = form_vals[fname];
                    }
                });
                
                
                // perform ajax
                show_loader();
                document.getElementById("ewpt_ajax_response").innerHTML = "";
                
                await fetch(ajaxurl, format_for_wp_ajax(data)).then(async response => {
                    if(response.ok) {
                    
                        const html = (await response.text());
                        document.getElementById("ewpt_wrapper").innerHTML = html;

                        if(step == "send" && document.querySelector("#ewpt_wrapper input[type=submit]")) {
                            document.getElementById("ewpt_ajax_response").innerHTML = err_mess;	
                        }
                        
                        
                        const heading = document.getElementById("request-filesystem-credentials-title");
                        if(heading) {
                            heading.innerHTML = "Easy WP thumbs - "+ heading.innerHTML;        
                        }


                        // setup form click
                        if(document.querySelector("#ewpt_wrapper #upgrade")) {
                            document.querySelector("#ewpt_wrapper #upgrade").addEventListener("click", (e) => {
                                e.preventDefault();
                                setup("send");
                            });
                        }


                        // erase cache
                        if(document.getElementById("ewpt_clean_cache_trig")) {
                            document.getElementById("ewpt_clean_cache_trig").addEventListener("click", (e) => {
                                e.preventDefault();        
                                erase_cache();
                            });
                        }
                        
                        
                        // update optimization mode 
                        if(document.querySelector("select[name=ewpt_optimization_mode]")) {
                            document.querySelector("select[name=ewpt_optimization_mode]").addEventListener("change", async (e) => {

                                const data = {
                                    action          : "ewpt_update_optim_mode",
                                    ewpt_optim_mode : e.target.value,
                                    ewpt_nonce      : "'. esc_js(wp_create_nonce('ewpt_nonce')) .'"
                                };

                                await fetch(ajaxurl, format_for_wp_ajax(data)).then(async response => {
                                    if(!response.ok) {
                                        alert(response.status +": "+ response.statusText);
                                    }
                                    const resp = await response.text();

                                    if(resp.trim() && resp.trim() != "success") {
                                        alert("Easy WP Thumbs - "+ resp);
                                    }
                                })
                                .catch(error => {
                                    console.error(error);
                                    alert("Easy WP Thumbs '. esc_attr__('error') .' "+ JSON.stringify(error));
                                });
                            })
                        }
                    }
                    else {
                        document.getElementById("ewpt_ajax_response").innerHTML = "Easy WP Thumbs '. esc_attr__('error') .' "+ response.status +": "+ response.statusText;     
                        document.getElementById("ewpt_wrapper").innerHTML = "";
                    }
                })
                .catch(error => {
                    if(error) {
                        console.error(error);
                        document.getElementById("ewpt_ajax_response").innerHTML = "Easy WP Thumbs '. esc_attr__('error') .' "+ JSON.stringify(error);
                        document.getElementById("ewpt_wrapper").innerHTML = "";
                    }
                });   
            };
            
            
            
            // format object data to be passed to wordpress AJAX handler
            const format_for_wp_ajax = function(obj) {
                let to_return = [];
                
                Object.keys(obj).forEach((key) => {
                    to_return.push( encodeURIComponent(key) +"="+ encodeURIComponent(obj[key]) );
                });
                
                
                return {
                    method      : "POST",
                    credentials : "same-origin",
                    headers     : {
                        "Content-Type"  : "application/x-www-form-urlencoded",
                        "Cache-Control" : "no-cache",
                    },
                    body        : to_return.join("&")
                };
            };
            
            
            
            // pure-JS equivalent to parents()
            const recursive_parent = (element, target) => {
                if(!element) {
                    return element;    
                }
                let node = element;

                while(node.parentNode != null && !node.matches(target) ) {
                    node = node.parentNode;
                }
                return node;
            };
    
            
            
            setup("init"); // initialize
        })();
        </script>';
        
        return $code;    
    }
    
    

    // check with the wp filesystem - executed via AJAX
    public static function status_check($force_direct = false) {
        if(!isset($_POST['ewpt_nonce']) || !wp_verify_nonce($_POST['ewpt_nonce'], 'ewpt_nonce')) {
            wp_die('Cheating?');
        };

        // set a fake screen type
        $GLOBALS['hook_suffix'] = 'page';
        set_current_screen();

        $method = EWPT_FS_DEBUG_VAL;
        $ewpt = new ewpt_connect($method);

        // FTP issue fix
        if( ($force_direct || get_option('ewpt_force_ftp')) && !defined('FS_METHOD')) {
            define('FS_METHOD', 'direct');
        } 

        // check if is ready to work - if the server allows to manage directly files and cache dir doesn't exists, create it
        if($ewpt->is_ready()) {
            global $wp_filesystem;
            
            // check for existing cache images
            $existing_files = $wp_filesystem->dirlist( $ewpt->cache_dir );
            $has_cache_files = (is_array($existing_files) && count($existing_files) > 0) ? true : false;

            self::success_message($has_cache_files);
        }

        
        //// request_filesystem_credentials part (for restricted servers)

        // print the nonces and screen fields anyway
        if(isset($_POST['ewpt_nonce_url'])) {
            echo '<input type="hidden" name="ewpt_nonce_url" value="'. esc_attr($_POST['ewpt_nonce_url']) .'" />';
        }

        // context
        $context = ($ewpt->cache_dir_exists()) ? $ewpt->cache_dir : $ewpt->basedir;

        // get url
        $nonce_url = $_POST['ewpt_nonce_url'];

        // basic display
        if(isset($_POST['ewpt_init'])) {
            request_filesystem_credentials($nonce_url, $method, false, $context);
            wp_die();
        }
        

        //// handling data 

        // check
        if(($creds = request_filesystem_credentials($nonce_url, $method, false, $context)) === false) {
            wp_die();
        }

        // check the wp_filesys with the given credentials
        if(!WP_Filesystem($creds, $context)) {
            request_filesystem_credentials($url, $method, false, $context);
            wp_die();
        }

        
        // connected succesfully - proceed with cache directory and demo file creation 
        global $wp_filesystem;

        // chache dir creation
        if(!file_exists($ewpt->cache_dir)) {
            if(!$wp_filesystem->mkdir($ewpt->cache_dir, EWPT_CHMOD_DIR)) {

                // try forcing through direct creation
                if(!$force_direct) {
                    self::status_check(true);
                    wp_die();
                } 
                else {
                    wp_die( esc_html__('Error creating the cache directory', self::$multilang_key) .'<br/><br/>');
                }
            }
        }

        
        // create the test file and remove it
        $filename = $ewpt->cache_dir. '/test_file.txt';
        if(!@file_exists($filename)) {
            if(!$wp_filesystem->put_contents($filename, 'Testing ..', EWPT_CHMOD_FILE)) {

                // try forcing through direct creation
                if(!$force_direct) {
                    self::status_check(true);
                    wp_die();
                } 
                else {
                    wp_die( esc_html__('Error creating the test file', self::multilang_key) .'<br/><br/>');
                }
            }
        }
        $wp_filesystem->delete($filename);

        
        //// everything is ok

        // if is forcing - save the flag
        if($force_direct || (defined('FS_METHOD') && FS_METHOD == 'direct')) {
            // save the flag to use the direct method
            update_option('ewpt_force_ftp', 1);		
        }

        // save the credentials
        $raw_creds = base64_encode( json_encode($creds));
        update_option('ewpt_creds', $raw_creds);

        self::success_message();
        wp_die();
    }
    
    
    
    
    // successful setup message
    private static function success_message($has_cache_files = false) {
        $clean_cache_string = ($has_cache_files) ? ' <a id="ewpt_clean_cache_trig" href="javascript:void(0)">('. esc_html__('Clean cache', self::$multilang_key) .')</a>' : ''; 
        $optimization = get_option('ewpt_optimization_mode', '');
        
        echo '
        <div class="wrap">
            <small class="alignright" data-ref="'. esc_attr(__FILE__) .'">v'. esc_html(EWPT_VER) .'</small>
            <h2>Easy WP Thumbs - '. esc_html__('Connection Information', self::$multilang_key). '</h2>
            
            <p>'. esc_html__('System properly set up!', self::$multilang_key) . $clean_cache_string .'</p>

            <p>
                <br/>
                <select name="ewpt_optimization_mode" autocomplete="off">
                    <option value="">'. esc_html__('No optimization', self::$multilang_key). '</option>
                    <option value="webp" '. selected('webp', $optimization, false) .'>'. esc_html__('Create thumbnails in WEBP format', self::$multilang_key). '</option>';
        
                    if(ewpt_helpers::supports_avif()) {
                        echo '<option value="avif" '. selected('avif', $optimization, false) .'>'. esc_html__('Create thumbnails in AVIF format', self::$multilang_key). '</option>';   
                    }
        
                echo '
                </select>
            </p>
        </div>';
        
        wp_die();
    }
    
    
    
    
    // emptyes cache folder - ajax handler
    public static function erase_cache($fs_method = 'auto') {
        if(!isset($_POST['ewpt_nonce']) || !wp_verify_nonce($_POST['ewpt_nonce'], 'ewpt_nonce')) {
            wp_die('Cheating?');
        };

        // Force direct Flag
        if(get_option('ewpt_force_ftp') && !defined('FS_METHOD')) {
            define('FS_METHOD', 'direct');
        } 

        $ewpt = new ewpt_connect(EWPT_FS_DEBUG_VAL);

        // check if is ready to operate
        if(!$ewpt->is_ready()) {
            wp_die( esc_html__('Cache folder not found', self::$multilang_key));
        }

        global $wp_filesystem;
        if(!$ewpt->cache_dir || strpos($ewpt->cache_dir, 'ewpt') === false) {
            wp_die( esc_html__('wrong cache directory', self::$multilang_key));
        }

        if(!$wp_filesystem->rmdir( $ewpt->cache_dir, true)) {
            wp_die( esc_html__('Error deleting the cache files', self::$multilang_key));
        }

        $_POST['ewpt_init'] = true;
        self::status_check(false);
        
        wp_die('success');
    }
    
    
    
    
    // emptyes cache folder - ajax handler
    public static function update_optim_mode() {
        if(!isset($_POST['ewpt_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ewpt_nonce'])), 'ewpt_nonce')) {
            wp_die('Cheating?');
        };

        if(!isset($_POST['ewpt_optim_mode'])) {
            wp_die('Missing value');
        }
        
        if(!in_array((string)$_POST['ewpt_optim_mode'], array('', 'webp', 'avif'))) {
            wp_die('Wrong value');
        }
        
        update_option('ewpt_optimization_mode', sanitize_text_field($_POST['ewpt_optim_mode']));
        wp_die();
    }
}





// AJAX HOOKS REGISTER
if(function_exists('add_action')) {
    add_action('wp_ajax_ewpt_status_check', 'ewpt_status_panel::status_check');
    add_action('wp_ajax_ewpt_erase_cache', 'ewpt_status_panel::erase_cache');
    add_action('wp_ajax_ewpt_update_optim_mode', 'ewpt_status_panel::update_optim_mode');
}



// v2 retrocompatibility
function ewpt_wpf_form($ml_key = 'ewpt_ml') {
    ewpt_status_panel::$multilang_key = $ml_key;
    echo ewpt_status_panel::get(false); 
}
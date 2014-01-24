<?php

/*
Plugin Name: PromoSimple
Plugin URI: http://blog.promosimple.com/features-tools/wordpress-plugin-for-giveaways/
Description: A simple plugin that allows the insertion of PromoSimple embed code via PromoSimple promo IDs used in a shortcode. Version 1.2 also adds
an option in the settings menu that allows the insertion of a PromoBar.
Version: 1.23
Author: Sam Brodie / Blas Asenjo
Author URI: http://promosimple.com
License: GPL2

Copyright 2013  Sam Brodie  (email : sambrodie01@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

if ( ! class_exists( 'Promosimple' ) ) {
    class Promosimple {
        //Domain to use
        protected $promoSimpleDomain = 'https://promosimple.com';
        //Options and data
        protected $option_name = 'promosimple';
        protected $data = array(
            'promo_bar_id' => '',
        );
    
        public static $help_url = 'http://blog.promosimple.com/new-features/wordpress-plug-in-for-giveaways';
        public function image_url(){
            return plugins_url( '/images/WordPressID-on-Publish.png', __FILE__ );
        }

        /**
         * @author: Sam Brodie
         * @since: 1.0
         */
        public function __construct() {

            add_shortcode( 'promosimple', array( $this, 'shortcode' ) );
            add_action( 'init', array( $this, 'tinymce_buttons' ) );
            add_action( 'wp_ajax_promosimple_tinymce_thickbox', array( $this, 'promosimple_tinymce_thickbox' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_style' ) );
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_menu', array($this, 'add_page'));
            //Attach an activation hook and create options with default values
            register_activation_hook(__FILE__, array($this, 'activate'));
            
            add_action( 'init', array( $this, 'showPromoBar' ));
        }
        
        /*
         * Whitelist our options using the Settings API
         */
        public function admin_init() {
            register_setting('promosimple_options', $this->option_name, array($this, 'validate'));
        }

        /*
         * Validate inputs
         * 
         * @param array $input The options to validate
         * 
         * @return array with validation results for each option
         */
        public function validate($input) {
            $valid = array();
            
            /*** Validate PromoBar ID ***/
            $valid['promo_bar_id'] = sanitize_text_field($input['promo_bar_id']);
            //If something was inputted, validate
            if (strlen($valid['promo_bar_id']) > 0) {
                //Validate against public section
                $xml = @file_get_contents($this->promoSimpleDomain . '/public/validate-key/key/' . $valid['promo_bar_id']);
                
                //If an XML was obtained...
                if ($xml) {
                    //Get the validation result from the xml
                    $sxe = new SimpleXMLElement($xml);
                    $validPromoBarId = $sxe->validKey;
                }
                
                //if not valid, show the error and set to default value
                if (@$validPromoBarId != true) {
                    add_settings_error(
                            'promo_bar_id',
                            'promo_bar_id_texterror',
                            'Please enter a valid ID, or leave the box empty to remove the PromoBar from your Wordpress site.',
                            'error' 
                    );

                    // Set it to the default value
                    $valid['promo_bar_id'] = $this->data['promo_bar_id'];
                }
            }
            /*** End PromoBar ID validation ***/

            return $valid;
        }

        /*
         * Activate event for the activation hook.
         */
        public function activate() {
            update_option($this->option_name, $this->data);
        }

        /*
         * Removes the options when the plugin is deactivated
         */
        public function deactivate() {
            delete_option($this->option_name);
        }

        /*
         * Add page action. Adds the options page to the settings menu.
         */
        public function add_page() {
            add_options_page('PromoSimple', 'PromoSimple', 'manage_options', 'promosimple_options', array($this, 'options_do_page'));
        }

        /*
         * Print the options page
         */
        public function options_do_page() {
            $options = get_option($this->option_name);
            ?>
            <div class="wrap">
                <div id="icon-options-general" class="icon32">
                    <br/>
                </div>
                <h2>PromoSimple Giveaways</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('promosimple_options'); ?>
                    <div class="settings-box">
                        <div class="settings-title">General Settings:</div>
                        <div class="settings-options">
                            <span>PromoBar ID:</span>
                            <input class="long-input" type="text" name="<?php echo $this->option_name?>[promo_bar_id]" value="<?php echo $options['promo_bar_id']; ?>" />
                        </div>
                    </div>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                    </p>
                </form>
            </div>
        <?php }
        
        /**
         * Displays the PromoSimple form from the shortcode
         *
         * @param $atts
         * @return string
         * @author: Sam Brodie
         * @modified by Blas Asenjo
         * @since: 1.0
         */
        public function shortcode( $atts ) {
            extract( shortcode_atts( array(
                'id' => false,
            ), $atts ) );

            $id = sanitize_text_field( $id );

            if ( $id != false ) {

                ob_start(); ?>

                <div align="center">
                    <a href="<?php echo $this->promoSimpleDomain ?>/ps/<?php echo $id; ?>" data-campaign="<?php echo $id; ?>" class="promosimple">
                        <?php _e( 'Click here to view this promotion.', 'promosimple' ); ?>
                    </a>
                    <script type="text/javascript" src="<?php echo $this->promoSimpleDomain ?>/api/1.0/campaign/<?php echo $id; ?>/iframe-loader"></script>
                    <noscript>
                        <?php printf( __( 'You need to enable javascript to enter this campaign! %1$s Powered by %2$s.', 'promosimple' ), '<br />', '<a href="http://www.promosimple.com/">PromoSimple</a>' ); ?>
                    </noscript>
                </div>
                
                <?php 
                $output = ob_get_clean();
            } else {

                $output = '<div align="center">' . __( 'Please enter a PromoSimple ID within your shortcode (e.g. [promosimple id="yourid"])', 'promosimple' ) . '</div>';

            }

            return $output;
        }
        
        /*
         * Show the PromoBar, if available
         */
        public function showPromoBar() {
            //If not admin only
            if(!is_admin()) { 
                //Show PromoBar, if available
                $promosimple= get_option('promosimple');
                $promoBarId = $promosimple['promo_bar_id'];
                if (!empty($promoBarId)): ?> 
                    <div id="promolayer-<?php echo $promoBarId ?>" class="promolayer"></div>
                    <script type="text/javascript" src="<?php echo $this->promoSimpleDomain ?>/api/1.0/layer"></script>
                <?php endif;
            }          
        }

        /**
         * @author: Sam Brodie
         * @since: 1.0
         */
        public function admin_style() {

            wp_register_style( 'promosimple_admin_css', plugins_url( '/css/style.css' , __FILE__ ), false, '1.0.0' );
            wp_enqueue_style( 'promosimple_admin_css' );

        }

        /**
         * @author: Sam Brodie
         * @since: 1.0
         */
        public function tinymce_buttons() {

            add_filter( 'mce_external_plugins', array( $this, 'add_tinymce_button' ) );
            add_filter( 'mce_buttons', array( $this, 'register_tinymce_button' ) );

        }

        /**
         * @param $plugin_array
         * @return mixed
         * @author: Sam Brodie
         * @since: 1.0
         */
        public function add_tinymce_button( $plugin_array ) {

            $plugin_array['promosimple'] = plugins_url( '/js/script.js' , __FILE__ );
            return $plugin_array;

        }

        /**
         * @param $buttons
         * @return mixed
         * @author: Sam Brodie
         * @since: 1.0
         */
        public function register_tinymce_button( $buttons ) {

            array_push( $buttons, 'promosimple_button' );
            return $buttons;

        }

        /**
         * Ajax function that returns a form for the PromoSimple thickbox triggered by the tinyMCE button
         *
         * @author: Sam Brodie
         * @since: 1.0
         */
        public function promosimple_tinymce_thickbox(){

            //Include javascript inline since it's brief and in all likelihood will not be reused

            ?>

            <script type="text/javascript">

                jQuery('#promosimple-reveal-instructions').click(function(e){
                    e.preventDefault();
                    jQuery('.promosimple-instructions').slideToggle();
                });

                jQuery('#promosimple-submit').click(function(){
                    var id, shortcode;

                    id = jQuery('#promosimple-id').val();

                    if (id != null && id != undefined && id != false) {

                        shortcode = '[promosimple id="' + id + '"]';

                    }else {

                        alert('<?php _e( 'Please enter an ID', 'promosimple' ); ?>');

                    }

                    tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);
                    tb_remove();
                });

                jQuery('#promosimple-cancel').click(function(){
                    tb_remove();
                });

            </script>

            <div class="promosimple-thickbox-box">

                <p><?php printf( __( 'Please enter your %1$sPromoSimple promo ID%2$s in the input below and click "OK".', 'promosimple' ), '<a id="promosimple-reveal-instructions" href="http://www.promosimple.com/" target="_blank">', '</a>' ); ?></p>
                
                <div class="promosimple-instructions">
                    <img src="<?php echo $this->image_url(); ?>">
                    <p><a href="<?php echo self::$help_url; ?>" target="_blank"><?php _e( 'I\'m still confused', 'promosimple' ); ?></a></p>
                </div>

                <?php _e( 'PromoSimple ID', 'promosimple' ); ?>
                <br />
                <input id="promosimple-id" type="text" />

            </div>

            <button class="promosimple-save promosimple-thickbox-button button-primary" id="promosimple-submit">OK</button>
            <button class="promosimple-cancel promosimple-thickbox-button button-secondary" id="promosimple-cancel">Cancel</button>

            <?php

            die();

        }

    }

    $Promosimple = new Promosimple();

}
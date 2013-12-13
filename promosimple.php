<?php

/*
Plugin Name: PromoSimple
Plugin URI: http://blog.promosimple.com/features-tools/wordpress-plugin-for-giveaways/
Description: A simple plugin that allows the insertion of PromoSimple embed code via PromoSimple promo IDs used in a shortcode.
Version: 1.0
Author: Sam Brodie
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

        }

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
                    <a href="https://promosimple.com/ps/<?php echo $id; ?>" data-campaign="<?php echo $id; ?>" class="promosimple">
                        <?php _e( 'Click here to view this promotion.', 'promosimple' ); ?>
                    </a>
                    <script type="text/javascript" src="https://promosimple.com/api/1.0/campaign/<?php echo $id; ?>/iframe-loader"></script>
                    <noscript>
                        <?php printf( __( 'You need to enable javascript to enter this campaign! %1$s Powered by %2$s.', 'promosimple' ), '<br />', '<a href="http://www.promosimple.com/">PromoSimple</a>' ); ?>
                    </noscript>
                </div>

                <?php
                //Get account key from the campaign id
                $xml = @file_get_contents('http://www.promosimple.com/public/account-key/campaign_id/' . $id);
                //If an XML was obtained...
                if ($xml) {
                    //Get the account key, and if there is one, show the PromoBar
                    $sxe = new SimpleXMLElement($xml);
                    if ($sxe->accountKey): ?>
                        <div id="promolayer-<?php echo $sxe->accountKey; ?>" class="promolayer"></div>
                        <script type="text/javascript" src="https://promosimple.com/api/1.0/layer"></script>
                    <?php endif; ?>
                <?php }
                
                 $output = ob_get_clean();

            } else {

                $output = '<div align="center">' . __( 'Please enter a PromoSimple ID within your shortcode (e.g. [promosimple id="yourid"])', 'promosimple' ) . '</div>';

            }

            return $output;

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
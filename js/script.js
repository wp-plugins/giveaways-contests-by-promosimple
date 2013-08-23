(function() {
    tinymce.create('tinymce.plugins.PromoSimple', {

        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         * This call is done before the editor instance has finished it's initialization so use the onInit event
         * of the editor instance to intercept that event.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init : function(ed, url) {

            ed.addCommand('promosimple_button', function() {
                /*

                // Simple Alert Box Option
                // This can be used instead of the Thickbox (tb_show()) if desired

                var id = prompt("Please enter your PromoSimple Promo ID and click OK"),
                    shortcode;
                if (id !== null) {

                    shortcode = '[promosimple id="' + id + '"]';
                    ed.execCommand('mceInsertContent', 0, shortcode);

                }

                */

                tb_show('PromoSimple', 'admin-ajax.php?action=promosimple_tinymce_thickbox&height=600')

            });
            
            ed.addButton('promosimple_button', {
                title : 'Promosimple',
                cmd : 'promosimple_button',
                image : url + '/images/promosimple.png'
            });
        },

        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo : function() {
            return {
                    longname : 'PromoSimple Buttons',
                    author : 'Sam Brodie',
                    authorurl : 'http://wiirl.com',
                    infourl : 'http://wiirl.com/plugins',
                    version : "1.0"
            };
        }
    });

    // Register plugin
    tinymce.PluginManager.add('promosimple', tinymce.plugins.PromoSimple);
})();
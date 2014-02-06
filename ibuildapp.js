/**
 * iBuildApp:Plugin Wordpress
 *
 * @uses jQuery
 * @version 0.1.0
 */
/*
- Version 0.1.0
First implementation
*/
(function(window, $, undefined) {
    var WPIbuildapp = function(defaultOptions) {
        var API,
            options = {
            },
            post;

        if (typeof defaultOptions != 'undefined') {
            $.extend(options, defaultOptions);
        }

        /**
         * AJAX shortcuts
         */
        post = function(action, data, callback) {
            $.post(
                options.ajaxurl,
                {
                    action: action + '_ajax',
                    _wpnonce: options[action + '_nonce'],
                    data: data
                },
                function(response) {
                    callback(response);
                },
                'json'
            );
        };

        /**
         * Public API
         */

        API = this;

        /* System functions */
        API.system = {};

        API.system.validateInstallation = function(callback) {
            post(
                'ibuildapp_conf_quickSetup_validateInstallation',
                {},
                function(response) {
                    callback(response);
                }
            );
        };

        /* Parser functions */
        API.parser = {};

        API.parser.check = function(callback) {
            post(
                'ibuildapp_app_create_stepParse',
                {action: 'check'},
                function(response) {
                    callback(response);
                }
            );
        };
    };

    /**
     * Global mapping
     */

    var defaultOptions = {};
    if (window.WPIbuildapp) {
        defaultOptions = window.WPIbuildapp;
    }
    defaultOptions.ajaxurl = window.ajaxurl;
    window.WPIbuildapp = new WPIbuildapp(defaultOptions);
})(window, jQuery);
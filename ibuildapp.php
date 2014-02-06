<?php
/*
Plugin Name: iBuildApp
Plugin URI: http://ibuildapp.com/
Description: Create Android and iPhone, iPad App for free and no Coding Required. iBuildApp tool has been used to create over 400 000 apps
Version: 0.1.0
Text Domain: ibuildapp
Author: iBuildApp
Author URI: http://ibuildapp.com/
License: GPLv2 or later
*/

/*  Copyright 2013  iBuildApp  (email: contacts@ibuildapp.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit();
}

define('IBUILDAPP_PLUGIN_TYPE', 'WP');
define('IBUILDAPP_VERSION', '0.1.0');
define('IBUILDAPP_PLUGIN_DIR', dirname(__FILE__));
define('IBUILDAPP_PLUGIN_BASENAME', dirname(plugin_basename(__FILE__)));
define('IBUILDAPP_PLUGIN_URL', plugin_dir_url(__FILE__));

global $wp_version;
if (version_compare($wp_version, '3.0', '<')) {
    printf(__('iBuildApp WordPress plugin %s requires WordPress 3.0 or higher.'), IBUILDAPP_VERSION);
    exit();
}
if (function_exists('is_multisite') && is_multisite()) {
    printf(__('iBuildApp WordPress plugin %s doesn\'t support multisite WordPress installation.'), IBUILDAPP_VERSION);
    exit();
}

if (is_admin()) {
    require_once IBUILDAPP_PLUGIN_DIR . '/classes/Ibuildapp.class.php';
    require_once IBUILDAPP_PLUGIN_DIR . '/classes/IbuildappApp.class.php';
    require_once IBUILDAPP_PLUGIN_DIR . '/classes/IbuildappApiClient.class.php';
    Ibuildapp::init();

    require_once IBUILDAPP_PLUGIN_DIR . '/admin.php';
}

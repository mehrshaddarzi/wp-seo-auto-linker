<?php
/*
Plugin Name: SEO Auto Linker
Plugin URI: http://pmg.co/seo-auto-linker
Description: Allows you to automatically link terms with in your post, page or custom post type content.
Version: 1.0.0
Author: Mehrshad Darzi
Author URI: https://realwp.net
Text Domain: seoal
Domain Path: /lang

    Copyright 2012 Christopher Davis, Performance Media Group <seo@pmg.co>

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

define('SEOAL_PATH', plugin_dir_path(__FILE__));
define('SEOAL_URL', plugin_dir_url(__FILE__));

/*
 * Loads the plugin's text domain for translation
 *
 * @uses load_plugin_textdomain
 * @since 0.7
 */
add_action('init', 'seoal_load_textdomain');
function seoal_load_textdomain()
{
    load_plugin_textdomain(
        'seoal',
        false,
        dirname(plugin_basename(__FILE__)) . '/lang/'
    );
}

/**
 * Load plugin File
 */
require_once(SEOAL_PATH . 'inc/base.php');
require_once(SEOAL_PATH . 'inc/post-type.php');
if (is_admin()) {
    require_once(SEOAL_PATH . 'inc/admin.php');
} else {
    require_once(SEOAL_PATH . 'inc/front.php');
}

/**
 * Provides an always safe action into which to hook for plugins that extend
 * SEO Auto Linker.
 *
 * @return  null
 * @uses    do_action
 * @since   0.8.2
 */
add_action('plugins_loaded', 'seoal_loaded');
function seoal_loaded()
{
    do_action('seoal_loaded');
}

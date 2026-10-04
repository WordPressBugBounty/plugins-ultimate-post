<?php

/**
 * Plugin Name: Post Grid Gutenberg Blocks - PostX
 * Plugin URI:  https://www.wpxpo.com/postx/
 * Description: Gutenberg Blocks plugin with 38+ free blocks that include post grid, post list, post slider, carousel, news ticker, and more. Dynamic site building and design variations make it a fit for News Magazine sites and any kind of blog, such as Personal, Travel, Fashion, Food Review and Recipe blogs.
 * Version:     5.1.0
 * Author:      Post Grid Team by WPXPO
 * Author URI:  https://www.wpxpo.com/postx/?utm_source=db-postx-plugin&utm_medium=details&utm_campaign=postx-dashboard
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: ultimate-post
 * License:     GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) || exit;

// Define
define( 'ULTP_VER', '5.1.0' );
define( 'ULTP_URL', plugin_dir_url( __FILE__ ) );
define( 'ULTP_BASE', plugin_basename( __FILE__ ) );
define( 'ULTP_PATH', plugin_dir_path( __FILE__ ) );
define( 'ULTP_HELLOBAR', '510' );


// Language Load
add_action( 'init', 'ultp_language_load' );
function ultp_language_load() {
	// template section wrapped inside a hook for netwrok site compatibility
	// Template
	require_once ULTP_PATH . 'classes/Templates.php';
	new \ULTP\Templates();
}

// Common Function
if ( ! function_exists( 'ultimate_post' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Public API used by PostX Pro and themes; renaming breaks them.
	function ultimate_post() {
		require_once ULTP_PATH . 'classes/Functions.php';
		return \ULTP\Functions::get_instance();
	}
}

// Plugin Initialization
if ( ! class_exists( 'ULTP_Initialization' ) ) {
	require_once ULTP_PATH . 'classes/Initialization.php';
	new \ULTP\ULTP_Initialization();
}

<?php

/**
 * Plugin Name: Post Grid Blocks & Templates for News, Magazine, & Blog Sites - PostX
 * Plugin URI:  https://www.wpxpo.com/postx/
 * Description: Gutenberg Blocks plugin with 35 free blocks that include post grid, post list, post slider, carousel, news ticker, and more. Dynamic site building and design variations make it a fit for News Magazine sites and any kind of blog, such as Personal, Travel, Fashion, Food Review and Recipe blogs.
 * Version:     5.1.7
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
// Server-generated block style contract, independent of editor/frontend API levels.
define( 'ULTP_BLOCK_STYLE_API', 1 );
define( 'ULTP_VER', '5.1.7' );
define( 'ULTP_URL', plugin_dir_url( __FILE__ ) );
define( 'ULTP_BASE', plugin_basename( __FILE__ ) );
define( 'ULTP_PATH', plugin_dir_path( __FILE__ ) );
define( 'ULTP_HELLOBAR', '510' );

// Level of the editor extension contract (the `window.ultpEditor` API and the `ultp.editor.*`
// filters). Raised only when a hook or slot is added; PostX Pro loads its editor bundle when this
// is at least the level it was built for, so no version numbers are compared.
// Level 7: the Custom Taxonomy options of the filter block come from the `ultp.editor.filterSelectSource` filter and
// the type is listed through `ultp.editor.filterListTypes`.
// Level 8: the layout picker, the layout attributes, the preview classes and the preview parts of the layout and style
// variants are asked through `ultp.editor.previewLayout`, `ultp.editor.layoutAttributes`, `ultp.editor.previewClass` and
// `ultp.editor.layoutPart`; the editor draws only its own default when nothing answers.
define( 'ULTP_EDITOR_API', 8 );

// Lowest editor contract level PostX Pro must implement (`ULTP_PRO_EDITOR_API`, defined by PostX Pro)
// for its editor features to work here. A lower one, or none, shows an "Update PostX Pro" notice.
define( 'ULTP_PRO_MIN_EDITOR_API', 8 );

// Level of the front-end extension hooks (see classes/Extended_Compat.php). Raised when a hook is added, or when
// PostX takes over a part an add-on drew: level 3 draws the inline filter bar, pagination and Load More of the
// grid, list and module blocks itself and no longer fires `ultp_block_filter_navigation` / `ultp_block_after_items` for them.
// Level 4: the search box of the filter block is driven through the `ultp_filter_request` and `ultp_filter_reset` events
// of assets/js/ultp.js.
// Level 5: the custom taxonomy part of a block's default filter values is built through the
// `ultp_advanced_filter_default_tax_query` filter (see classes/Functions.php).
// Level 6: layout, style and option variations of the blocks are offered as slots (value filters, fragment filters and
// actions) that the block prints as returned.
// Level 7: the responsive show/hide rules, the title hover effects, the date formats of the post date and advanced meta
// blocks, and the time badge of the news ticker are offered as filters and style slots. Without an answer, the block
// prints its default only.
define( 'ULTP_FRONTEND_API', 7 );

// Lowest hook level the add-on plugin must report (`ULTP_PRO_FRONTEND_API`); a lower one, or none, shows an update notice.
define( 'ULTP_PRO_MIN_FRONTEND_API', 7 );


// Register class loading before any plugin services are initialized.
require_once ULTP_PATH . 'classes/Autoloader.php';
\ULTP\Autoloader::register();

// Language Load
add_action( 'init', 'ultp_language_load' );
function ultp_language_load() {
	// template section wrapped inside a hook for netwrok site compatibility
	// Template
	new \ULTP\Templates();
}

// Common Function
if ( ! function_exists( 'ultimate_post' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Public API used by PostX Pro and themes; renaming breaks them.
	function ultimate_post() {
		return \ULTP\Functions::get_instance();
	}
}

// Plugin Initialization
new \ULTP\ULTP_Initialization();

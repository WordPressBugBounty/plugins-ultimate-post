<?php

/**
 * Initialization Action.
 *
 * @package ULTP\ULTP_Initialization
 * @since v.1.1.0
 */

namespace ULTP;

defined( 'ABSPATH' ) || exit;

use ULTP\Includes\Helper\Xpo;
use ULTP\Includes\Notice\Notice;

/**
 * Initialization class.
 *
 * Handles plugin initialization for Ultimate Post.
 *
 * @package ULTP\Initialization
 * @since 4.0.0
 */
class ULTP_Initialization {

	/**
	 * Setup class.
	 *
	 * @since 4.0.0
	 * @return void No return value.
	 */
	public function __construct() {

		$this->compatibility_check();
		$this->requires();
		$this->include_addons();

		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );  // add body class in editor.
		add_filter( 'body_class', array( $this, 'add_body_class' ) );  // add body class in front end.

		add_action( 'wp', array( $this, 'popular_posts_tracker_callback' ) );
		add_action( 'after_setup_theme', array( $this, 'add_image_size' ) );
		add_filter( 'block_categories_all', array( $this, 'register_category_callback' ), 999999999, 2 );  // Block Category Register.

		add_filter( 'safe_style_css', array( $this, 'ultp_handle_safe_style_css' ) );  // support for css used in svg icon.
		add_filter( 'wp_kses_allowed_html', array( $this, 'ultp_handle_allowed_html' ), 99, 2 );   // support for svg icon used in list, row, icon block.
		add_filter( 'wp_kses_uri_attributes', array( $this, 'ultp_kses_uri_attributes' ) );   // protocol check for the Social Share link's url attribute.
		add_filter( 'safecss_filter_attr_allow_css', array( $this, 'ultp_safecss_allow_css' ), 10, 2 );   // colour, filter and timing functions in style="".
		add_filter( 'pre_kses', array( $this, 'ultp_pre_kses_rendered_content' ), 10, 2 );   // no <style>/<script> code shown as text in rendered content.

		add_action( 'enqueue_block_editor_assets', array( $this, 'register_block_scripts_editor_area' ) );    // Only editor.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_option_panel_scripts_callback' ) );    // Option Panel.
		register_activation_hook( ULTP_PATH . 'ultimate-post.php', array( $this, 'plugin_activation_hook' ) );
		add_action( 'activated_plugin', array( $this, 'ultp_plugin_activation' ) ); // Plugin Activation Call.
		// new Notice();
	}

	/**
	 * Check plugin compatibility.
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function compatibility_check() {
		new \ULTP\Compatibility();
	}

	/**
	 * Initialize plugin services; their classes are loaded on demand.
	 *
	 * @since v.1.0.0
	 * @return void No return value.
	 */
	public function requires() {
		new \ULTP\Legacy_Keys();
		new \ULTP\REST_API();
		new \ULTP\Options();
		new \ULTP\Caches();
		new \ULTP\Styles();
		new \ULTP\Importer();
		new \ULTP\Dashboard();
		new \ULTP\Blocks();
		new \ULTP\Includes\Notice\Notice();
	}

	/**
	 * Include all addons from the addons directory.
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function include_addons() {
		$addons_dir = array_filter( glob( ULTP_PATH . 'addons/*' ), 'is_dir' );
		if ( count( $addons_dir ) > 0 ) {
			foreach ( $addons_dir as $key => $value ) {
				$addon_dir_name = str_replace( dirname( $value ) . '/', '', $value );
				$file_name      = ULTP_PATH . 'addons/' . $addon_dir_name . '/init.php';
				if ( file_exists( $file_name ) ) {
					include_once $file_name;
				}
			}
		}
	}


	/**
	 * Add admin body class in editor.
	 *
	 * @since v.3.1.6
	 * @param STRING $classes The existing admin body classes.
	 * @return STRING Modified admin body classes.
	 */
	public function add_admin_body_class( $classes ) {
		$classes .= ' postx-admin-page ';
		return $classes;
	}

	/**
	 * Add body class in front end.
	 *
	 * @since v.3.1.6
	 * @param ARRAY $classes The existing body classes.
	 * @return ARRAY Modified body classes.
	 */
	public function add_body_class( $classes ) {
		$classes[] = 'postx-page';
		return $classes;
	}

	/**
	 * Post view counter for every post.
	 *
	 * @since v.1.0.0
	 * @param INT $post_id The post ID.
	 * @return NULL No return value.
	 */
	public function popular_posts_tracker_callback( $post_id ) {
		if ( ! is_single() ) {
			return;
		}
		global $post;
		$post_id  = isset( $post->ID ) ? $post->ID : '';
		$isEnable = apply_filters( 'ultp_view_cookies', true );
		// add_filter( 'ultp_view_cookies', '__return_false' );
		$cookies_disable = ultimate_post()->get_setting( 'disable_view_cookies' );
		if ( $post_id && $isEnable && $cookies_disable != 'yes' ) {
			$has_cookie = isset( $_COOKIE[ 'ultp_view_' . $post_id ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ 'ultp_view_' . $post_id ] ) ) : false;
			if ( ! $has_cookie ) {
				$count = (int) get_post_meta( $post_id, '_ultp_post_views_count', true );
				update_post_meta( $post_id, '_ultp_post_views_count', $count ? (int) $count + 1 : 1 );
				setcookie( 'ultp_view_' . $post_id, 1, time() + 86400, COOKIEPATH ); // 1 days cookies
			}
		}
	}

	/**
	 * Set image sizes for the plugin.
	 *
	 * @since v.1.0.0
	 * @return void No return value.
	 */
	public function add_image_size() {
		$size_disable = ultimate_post()->get_setting( 'disable_image_size' );
		if ( $size_disable != 'yes' ) {
			add_image_size( 'ultp_layout_landscape_large', 1200, 800, true );
			add_image_size( 'ultp_layout_landscape', 870, 570, true );
			add_image_size( 'ultp_layout_portrait', 600, 900, true );
			add_image_size( 'ultp_layout_square', 600, 600, true );
		}
	}

	/**
	 * Block categories initialization.
	 *
	 * @since v.1.0.0
	 * @param ARRAY $categories The block categories.
	 * @param ARRAY $post The post object.
	 * @return ARRAY Modified block categories.
	 */
	public function register_category_callback( $categories, $post ) {
		$attr = array(
			array(
				'slug'  => 'ultimate-post',
				'title' => __( 'PostX - Gutenberg Post Blocks', 'ultimate-post' ),
			),
			array(
				'slug'  => 'postx-site-builder',
				'title' => __( 'PostX Site Builder', 'ultimate-post' ),
			),
		);
		return array_merge( $attr, $categories );
	}

	/**
	 * Add support for CSS to use SVG.
	 *
	 * @since 4.0.0
	 * @param ARRAY $styles The allowed CSS styles.
	 * @return ARRAY Modified allowed CSS styles.
	 */
	public function ultp_handle_safe_style_css( $styles ) {
		// Properties in block markup that core does not list: transitions, the News Ticker's and the
		// loading spinner's animation-delay, and the Tabs block's order. Allowed for everyone, so they are
		// kept when rendered content is filtered by wp_kses() for a visitor.
		$styles = array_merge(
			$styles,
			array(
				'transition',
				'transition-property',
				'transition-duration',
				'transition-timing-function',
				'transition-delay',
				'animation-delay',
				'order',
			)
		);
		if ( ! is_multisite() && ! current_user_can( 'edit_posts' ) ) {
			return $styles;
		}
		return array_merge(
			$styles,
			array(
				'opacity',
				// for SVG gradients.
				// 'stop-opacity',
				// 'stop-color',
			)
		);
	}

	/**
	 * Treat an attribute named `url` as a URL in wp_kses(), like `href`.
	 *
	 * The Social Share block's links carry the share URL in a `url` attribute, which ultp.js passes
	 * to window.open(). Listed here, wp_kses() removes a javascript: or other disallowed protocol
	 * from it, as it does for href.
	 *
	 * @since 5.1.4
	 * @param string[] $attributes Attribute names whose values are URLs.
	 * @return string[]
	 */
	public function ultp_kses_uri_attributes( $attributes ) {
		if ( is_array( $attributes ) && ! in_array( 'url', $attributes, true ) ) {
			$attributes[] = 'url';
		}
		return $attributes;
	}

	/**
	 * Allow colour, filter and timing functions in style="" values.
	 *
	 * Core's safecss_filter_attr() rejects any `(` outside var(), calc() and a few others, so it drops
	 * `background-color:rgba(0, 0, 0, 0)`, which core's text colour format writes on <mark>, as well as
	 * `filter:blur(4px)` and `transition:all .3s cubic-bezier(…)`. Each of these functions is removed
	 * from the test string when its arguments are only numbers, units, names, `#`, `,`, `/` and spaces,
	 * innermost first (drop-shadow(… rgba(…))); then core's own check runs on what is left.
	 *
	 * @since 5.1.4
	 * @param bool   $allow_css       Whether core considers the CSS safe.
	 * @param string $css_test_string The CSS declaration, with url(), var(), calc() etc. removed.
	 * @return bool
	 */
	public function ultp_safecss_allow_css( $allow_css, $css_test_string ) {
		if ( $allow_css || ! is_string( $css_test_string ) ) {
			return $allow_css;
		}

		$functions = 'rgba?|hsla?|hwb|lab|lch|oklab|oklch'
			. '|blur|brightness|contrast|drop-shadow|grayscale|hue-rotate|invert|opacity|saturate|sepia'
			. '|cubic-bezier|steps';
		for ( $i = 0; $i < 3; $i++ ) {
			$stripped = preg_replace( '/\b(?:' . $functions . ')\([\w\s.,%#\/+-]*\)/i', '', $css_test_string );
			if ( null === $stripped || $stripped === $css_test_string ) {
				break;
			}
			$css_test_string = $stripped;
		}

		// The same test as safecss_filter_attr().
		return 0 === preg_match( '%[\\\(&=}]|/\*%', $css_test_string );
	}

	/**
	 * How many <noscript> bodies are being filtered at once (ultp_filter_rendered_element() filters them recursively).
	 *
	 * @var int
	 */
	private $noscript_depth = 0;

	/**
	 * Take the <style>, <script> and <noscript> elements out of rendered content before wp_kses() filters it.
	 *
	 * Runs only for wp_kses() calls with rendered_content_allowed_html(). That list has <script> and
	 * <noscript> only in the forms below, so wp_kses() would otherwise print their code as text or drop
	 * their tags:
	 * - A <script> is kept only when it is the embed code of a known provider: an empty body and an https
	 *   src that is one of the exact script files in ultp_embed_script_sources(). Any other <script> is
	 *   removed whole, so inline JavaScript in content is still never printed.
	 * - A <noscript> is kept with its markup. Any "</noscript" inside it is removed, so the browser cannot
	 *   leave the noscript's raw-text state early and read the rest as markup.
	 * - The CSS of a <style>, such as the one the Pagination block saves from the Site Editor's Patterns
	 *   screen, is added with wp_add_inline_style() instead, so it still applies.
	 *
	 * If the filtering cannot finish, nothing is returned.
	 *
	 * @since 5.1.4
	 * @param string       $content      Content passed to wp_kses().
	 * @param array|string $allowed_html Allowed tags, or a context name.
	 * @return string
	 */
	public function ultp_pre_kses_rendered_content( $content, $allowed_html ) {
		if ( ! is_string( $content ) || ! is_array( $allowed_html ) || ! preg_match( '/<\/?(?:script|noscript)\b|<style\b/i', $content ) ) {
			return $content;
		}
		if ( ultimate_post()->rendered_content_allowed_html() !== $allowed_html ) {
			return $content;
		}

		// Removing an element can join the text on either side into a new tag, such as
		// `<scr<script>x</script>ipt>`, so repeat the filter until a pass changes nothing.
		$pattern = '@'
			. '(?P<style><style\b[^>]*>)(?P<css>.*?)</style\b[^>]*>'
			. '|<style\b[^>]*>'
			. '|(?P<embed><script\b[^>]*>)\s*</script\b[^>]*>'
			. '|<script\b[^>]*>.*?</script\b[^>]*>'
			. '|<script\b[^>]*>'
			. '|</script\b[^>]*>'
			. '|(?P<noscript><noscript\b[^>]*>)(?P<body>.*?)</noscript\b[^>]*>'
			. '|<noscript\b[^>]*>'
			. '|</noscript\b[^>]*>'
			. '@is';

		for ( $pass = 0; $pass < 50; $pass++ ) {
			$filtered = preg_replace_callback(
				$pattern,
				function ( $element ) {
					return $this->ultp_filter_rendered_element( $element );
				},
				$content
			);
			if ( null === $filtered ) {
				return '';
			}
			if ( $filtered === $content ) {
				return $content;
			}
			$content = $filtered;
		}
		return '';
	}

	/**
	 * Replacement for one match of the pattern in ultp_pre_kses_rendered_content().
	 *
	 * @since 5.1.4
	 * @param array $element preg match, with the named groups style, css, embed, noscript and body.
	 * @return string Text to keep in place of the match.
	 */
	private function ultp_filter_rendered_element( $element ) {
		if ( ! empty( $element['style'] ) ) {
			$this->enqueue_content_css( $element['css'] );
			return '';
		}
		if ( ! empty( $element['embed'] ) ) {
			return $this->ultp_is_embed_script_tag( $element['embed'] ) ? $element[0] : '';
		}
		if ( ! empty( $element['noscript'] ) ) {
			// The body is filtered as content of its own, so its scripts and styles are handled as well. The
			// closing tag is added after that output, so it cannot end up inside an attribute of the body.
			$body = preg_replace( '@</noscript@i', '', $element['body'] );
			if ( null === $body || $this->noscript_depth >= 3 ) {
				return '';
			}
			++$this->noscript_depth;
			$clean = wp_kses( $body, ultimate_post()->rendered_content_allowed_html() );
			--$this->noscript_depth;
			return '<noscript>' . $clean . '</noscript>';
		}
		// A <script> or <noscript> with a body, or an opening or closing tag on its own.
		return '';
	}

	/**
	 * Script files an embed may load from rendered content, by host.
	 *
	 * Each value is a list of regular expressions for the path. Only these exact files are kept: no query
	 * string, so a provider's callback parameter (JSONP) cannot be used. Add a file only after checking
	 * the provider's embed code. Facebook's SDK is served in the visitor's locale, such as en_US or de_DE.
	 *
	 * @since 5.1.4
	 * @return array Host => array of path patterns.
	 */
	private function ultp_embed_script_sources() {
		return array(
			'platform.twitter.com' => array( '#^/widgets\.js$#' ),
			'www.instagram.com'    => array( '#^/embed\.js$#' ),
			'www.tiktok.com'       => array( '#^/embed\.js$#' ),
			'cdn.embedly.com'      => array( '#^/widgets/platform\.js$#' ),
			'connect.facebook.net' => array( '#^/(?:[a-z]{2}_[a-zA-Z]{2}/)?sdk\.js$#' ),
			'www.youtube.com'      => array( '#^/iframe_api$#' ),
			'player.vimeo.com'     => array( '#^/api/player\.js$#' ),
		);
	}

	/**
	 * Whether a <script> opening tag is an embed script of a known provider.
	 *
	 * Only the attributes wp_kses() keeps on a <script> may be present, once each, and src must be an https
	 * (or protocol-relative) URL on a host in ultp_embed_script_sources() with a path that matches one of its patterns.
	 *
	 * @since 5.1.4
	 * @param string $open_tag The opening tag, such as `<script async src="https://platform.twitter.com/widgets.js">`.
	 * @return bool
	 */
	private function ultp_is_embed_script_tag( $open_tag ) {
		if ( ! preg_match( '#^<script((?:\s+[a-z-]+(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+))?)*)\s*>$#i', $open_tag, $tag ) ) {
			return false;
		}
		preg_match_all(
			'#([a-z-]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+)))?#i',
			$tag[1],
			$attributes,
			PREG_SET_ORDER
		);

		$allowed_names = array( 'src', 'async', 'defer', 'charset', 'type', 'crossorigin', 'referrerpolicy' );
		$seen          = array();
		$src           = null;
		foreach ( $attributes as $attribute ) {
			$name = strtolower( $attribute[1] );
			if ( ! in_array( $name, $allowed_names, true ) || isset( $seen[ $name ] ) ) {
				return false;
			}
			$seen[ $name ] = true;
			if ( 'src' === $name ) {
				$src = ( $attribute[2] ?? '' ) . ( $attribute[3] ?? '' ) . ( $attribute[4] ?? '' );
			}
		}
		if ( null === $src || ! preg_match( '#^(?:https:)?//([a-z0-9.-]+)(/[a-z0-9/._-]*)$#i', $src, $url ) ) {
			return false;
		}

		$sources = $this->ultp_embed_script_sources();
		$host    = strtolower( $url[1] );
		if ( ! isset( $sources[ $host ] ) ) {
			return false;
		}
		foreach ( $sources[ $host ] as $path_pattern ) {
			if ( preg_match( $path_pattern, $url[2] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add the CSS of a <style> taken out of rendered content as an inline stylesheet.
	 *
	 * One handle per distinct CSS, so a style already printed in the <head> is not added to again, and
	 * the same CSS is added once. Styles added after the <head> are printed in the footer. Nothing is
	 * printed for an AJAX response.
	 *
	 * @since 5.1.4
	 * @param string $css CSS from the <style> element.
	 * @return void
	 */
	private function enqueue_content_css( $css ) {
		// wp_kses() has already turned each lone `&` into `&amp;` (wp_kses_normalize_entities()). In CSS
		// that is a literal `&`, such as a nesting selector.
		$css = trim( str_replace( '&amp;', '&', $css ) );
		if ( '' === $css || ! did_action( 'init' ) ) {
			return;
		}
		$handle = 'ultp-content-css-' . md5( $css );
		if ( wp_style_is( $handle, 'registered' ) ) {
			return;
		}
		wp_register_style( $handle, false, array(), ULTP_VER );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, CSS_Sanitizer::ultp_esc_css( $css ) );
	}

	/**
	 * Add support for HTML tags to use SVG.
	 *
	 * SVG is added to the 'post' context (saved post content and rendered blocks, which
	 * rendered_content_allowed_html() builds on) for everyone, and to other contexts only for users who
	 * can edit posts. Multisite no longer widens other contexts, so e.g. pre_comment_content stays as core sets it.
	 *
	 * @since 4.0.0
	 * @param ARRAY  $tags The allowed HTML tags.
	 * @param STRING $context The context for allowed HTML.
	 * @return ARRAY Modified allowed HTML tags.
	 */
	public function ultp_handle_allowed_html( $tags, $context ) {
		if ( 'post' !== $context && ! current_user_can( 'edit_posts' ) ) {
			return $tags;
		}
		if ( ! isset( $tags['svg'] ) ) {
			$tags['svg'] = array_merge(
				array(
					'xmlns'               => true,
					// 'xmlns:xlink'   => true,
					// 'xlink:href'     => true,
					// 'xml:id'     => true,
					// 'xlink:title'    => true,
					// 'xml:space'  => true,
					'viewbox'             => true,
					'enable-background'   => true,
					'version'             => true,
					'preserveaspectratio' => true,
					'fill'                => true,
				)
			);
		}
		if ( ! isset( $tags['path'] ) || ! is_array( $tags['path'] ) ) {
			$tags['path'] = array();
		}

		$tags['path'] = array_merge(
			$tags['path'],
			array(
				'd'                 => true,
				'stroke'            => true,
				'stroke-miterlimit' => true,
				'data-original'     => true,
				'class'             => true,
				'transform'         => true,
				'style'             => true,
				'opacity'           => true,
				'fill'              => true,
				'stroke-width'      => true,
				'stroke-linecap'    => true,
				'stroke-linejoin'   => true,
			)
		);

		if ( ! isset( $tags['g'] ) ) {
			$tags['g'] = array(
				'transform' => true,
				'clip-path' => true,
			);
		}
		if ( ! isset( $tags['clippath'] ) ) {
			$tags['clippath'] = array();
		}
		if ( ! isset( $tags['defs'] ) ) {
			$tags['defs'] = array();
		}
		if ( ! isset( $tags['rect'] ) ) {
			$tags['rect'] = array(
				'rx'        => true,
				'height'    => true,
				'width'     => true,
				'transform' => true,
				'x'         => true,
				'fill'      => true,
			);
		}
		if ( ! isset( $tags['circle'] ) ) {
			$tags['circle'] = array(
				'cx'        => true,
				'cy'        => true,
				'transform' => true,
				'r'         => true,
			);
		}
		if ( ! isset( $tags['polygon'] ) ) {
			$tags['polygon'] = array(
				'points' => true,
			);
		}
		if ( ! isset( $tags['lineargradient'] ) ) {
			$tags['lineargradient'] = array(
				'gradienttransform' => true,
				'id'                => true,
			);
		}
		if ( ! isset( $tags['stop'] ) ) {
			$tags['stop'] = array(
				'offset'       => true,
				'stop-color'   => true,
				'style'        => true,
				'stop-opacity' => true,
			);
		}
		return $tags;
	}

	/**
	 * Plugin activation callback.
	 *
	 * @since v.1.1.0
	 * @param STRING $plugin The plugin file path.
	 * @return NULL No return value.
	 */
	public function ultp_plugin_activation( $plugin ) {
		if ( wp_doing_ajax() ) {
			return;
		}
		if ( $plugin == ULTP_BASE ) {
			if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) || isset( $_POST['action'] ) && 'activate-selected' == $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.NonceVerification.Missing -- Read-only check for bulk/multi activation.
				return;
			}
			$first_run = ultimate_post()->get_setting( 'init_setup' ) != 'yes';
			if ( $first_run ) {
				ultimate_post()->set_setting( 'init_setup', 'yes' );
			}
			// Only a single activation from plugins.php?action=activate redirects. CLI, cron and programmatic activations continue.
			if ( ! $this->is_single_admin_activation( $plugin ) ) {
				return;
			}
			if ( $first_run ) {
				wp_safe_redirect( admin_url( 'admin.php?page=ultp-setup-wizard' ) );
				exit;
			} else {
				wp_safe_redirect( admin_url( 'admin.php?page=ultp-settings#home' ) );
				exit;
			}
		}
	}

	/**
	 * Whether the current request is the admin single-plugin activation link for this plugin.
	 *
	 * @since v.1.1.0
	 * @param STRING $plugin The plugin file path.
	 * @return BOOLEAN
	 */
	private function is_single_admin_activation( $plugin ) {
		if ( ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_cron() || ! is_admin() ) {
			return false;
		}
		if ( ! isset( $_GET['action'], $_GET['plugin'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check of the request type; core verifies the nonce before activate_plugin().
			return false;
		}
		$action = sanitize_key( wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- See above.
		$target = sanitize_text_field( wp_unslash( $_GET['plugin'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- See above.
		return 'activate' === $action && $plugin === $target;
	}

	/**
	 * Enqueue option panel CSS and JS scripts.
	 *
	 * @since v.1.0.0
	 * @return void No return value.
	 */
	public function register_option_panel_scripts_callback() {
		// Menu visibility and plugin links are shared by all site-admin screens.
		wp_enqueue_style( 'ultp-admin-shared-style', ULTP_URL . 'assets/css/ultp-admin-shared.css', array(), ULTP_VER );
		wp_enqueue_script( 'ultp-admin-shared-script', ULTP_URL . 'assets/js/ultp-admin-shared.js', array( 'jquery' ), ULTP_VER, true );
		wp_localize_script( 'ultp-admin-shared-script', 'ultp_admin_menu', array( 'addon_submenus' => Options::get_addon_submenu_visibility() ) );

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		$is_settings       = 'toplevel_page_ultp-settings' === $screen->id;
		$is_wizard         = 'postx_page_ultp-setup-wizard' === $screen->id;
		$post_type         = $screen->post_type;
		$is_postx_post     = in_array( $screen->base, array( 'post', 'edit' ), true ) && in_array( $post_type, array( 'ultp_builder', 'ultp_templates', 'ultp_custom_font' ), true );
		$has_video_metabox = 'post' === $screen->base && 'post' === $post_type && 'true' === ultimate_post()->get_setting( 'ultp_builder' );
		if ( ! $is_settings && ! $is_wizard && ! $is_postx_post && ! $has_video_metabox && ! $screen->is_block_editor() ) {
			return;
		}

		$show_upsell = Xpo::show_upsell();

		$is_woocommerce_active = class_exists( 'WooCommerce' ) ? true : false;

		$option_dependencies = array( 'jquery', 'ultp-admin-shared-script' );
		if ( $is_settings ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			$option_dependencies[] = 'wp-color-picker';
		}

		wp_enqueue_script( 'ultp-option-script', ULTP_URL . 'assets/js/ultp-option.js', $option_dependencies, ULTP_VER, true );
		wp_enqueue_style( 'ultp-option-style', ULTP_URL . 'assets/css/ultp-option.css', array( 'ultp-admin-shared-style' ), ULTP_VER );
		wp_localize_script(
			'ultp-option-script',
			'ultp_option_panel',
			array(
				'url'                => ULTP_URL,
				'version'            => ULTP_VER,
				'show_upsell'      => $show_upsell,
				'security'           => wp_create_nonce( 'ultp-admin-nonce' ),
				'ajax'               => admin_url( 'admin-ajax.php' ),
				'settings'           => ultimate_post()->get_client_settings(),
				'post_type'          => $post_type,
				'saved_template_url' => admin_url( 'admin.php?page=ultp-settings#saved-templates' ),
				'builder_url'        => admin_url( 'admin.php?page=ultp-settings#builder' ),
				'woocommerce'        => $is_woocommerce_active,
			)
		);

		if ( $post_type == 'ultp_custom_font' ) {
			$font_settings = ultimate_post()->get_setting( 'ultp_custom_font' );
			if ( $font_settings == 'true' ) {
				// Pass the font post so uploads send its ID; Custom_Font allows SVG only for that post.
				wp_enqueue_media( array( 'post' => get_the_ID() ) );
			}
		}

		/* === Installation Wizard === */
		if ( $is_wizard ) {
			wp_enqueue_script( 'ultp-initial-setup-script', ULTP_URL . 'assets/js/ultp_initial_setup_min.js', array( 'wp-i18n', 'wp-element', 'wp-api-fetch', 'wp-api-request' ), ULTP_VER, true );
			wp_set_script_translations( 'ultp-initial-setup-script', 'ultimate-post', ULTP_PATH . 'languages/' );
		}

		/* === Builder And Setting Pannel === */
		if ( 'post' === $screen->base && 'ultp_builder' === $post_type ) {
			wp_enqueue_script( 'ultp-conditions-script', ULTP_URL . 'addons/builder/assets/js/conditions.js', array( 'wp-i18n', 'wp-element', 'wp-api-fetch', 'wp-components', 'wp-i18n', 'wp-blocks' ), ULTP_VER, true );
			wp_localize_script(
				'ultp-conditions-script',
				'ultp_condition',
				array(
					'url'         => ULTP_URL,
					'show_upsell'      => $show_upsell,
					'builder_url' => admin_url( 'admin.php?page=ultp-settings#builder' ),
				)
			);
			wp_set_script_translations( 'ultp-conditions-script', 'ultimate-post', ULTP_PATH . 'languages/' );
		}

		/* === Dashboard === */
		if ( $is_settings ) {
			wp_enqueue_script( 'ultp-dashboard-script', ULTP_URL . 'assets/js/ultp_dashboard_min.js', array( 'wp-i18n', 'wp-element', 'wp-api-fetch', 'wp-api-request', 'wp-components', 'wp-blocks', 'updates' ), ULTP_VER, true );
			wp_localize_script(
				'ultp-dashboard-script',
				'ultp_dashboard_pannel',
				array_merge(
					array(
						'ajax'              => admin_url( 'admin-ajax.php' ),
						'security'          => wp_create_nonce( 'ultp-admin-nonce' ),
						'url'               => ULTP_URL,
						'show_upsell'      => $show_upsell,
						// The menu capability is filterable; restrict saved secrets to administrators.
						'settings'          => current_user_can( 'manage_options' ) ? ultimate_post()->get_setting() : ultimate_post()->get_client_settings(),
						'addons_settings'   => apply_filters( 'ultp_settings', array() ),
						'builder_url'       => admin_url( 'admin.php?page=ultp-settings#builder' ),
						'version'           => ULTP_VER,
						'setup_wizard_link' => admin_url( 'admin.php?page=ultp-setup-wizard' ),
						'pro_outdated'      => defined( 'ULTP_PRO_VER' ) && ( ! defined( 'ULTP_PRO_EDITOR_API' ) || ULTP_PRO_EDITOR_API < ULTP_PRO_MIN_EDITOR_API || Extended_Compat::addon_outdated() ),
						'plugins_url'       => admin_url( 'plugins.php' ),
						'home_url'          => home_url(),
						'generalDiscount'   => get_transient( 'ultp_generalDiscount' ),
						'helloBar'          => Notice::get_hellobar_config(),
					),
					Xpo::get_wow_products_details()
				)
			);
			wp_set_script_translations( 'ultp-dashboard-script', 'ultimate-post', ULTP_PATH . 'languages/' );
		}
	}

	/**
	 * Enqueue backend CSS and JS scripts for the block editor.
	 *
	 * @since v.1.0.0
	 * @return void No return value.
	 */
	public function register_block_scripts_editor_area() {
		ultimate_post()->register_scripts_common();
		global $pagenow;
		$depends = 'wp-editor';
		if ( $pagenow === 'widgets.php' ) {
			$depends = 'wp-edit-widgets';
		}
		wp_enqueue_script( 'ultp-blocks-editor-script', ULTP_URL . 'assets/js/editor.blocks.js', array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', $depends ), ULTP_VER, true );
		wp_enqueue_style( 'ultp-blocks-editor-css', ULTP_URL . 'assets/css/blocks.editor.css', array(), ULTP_VER );
		if ( is_rtl() ) {
			wp_enqueue_style( 'ultp-blocks-editor-rtl-css', ULTP_URL . 'assets/css/rtl.css', array(), ULTP_VER );
		}
		$show_upsell = Xpo::show_upsell();
		$post_type   = get_post_type();

		// Custom Font Support Added
		$font_settings = ultimate_post()->get_setting( 'ultp_custom_font' );
		$custom_fonts  = array();
		if ( $font_settings == 'true' ) {
			$args  = array(
				'post_type'   => 'ultp_custom_font',
				'post_status' => 'publish',
				'numberposts' => -1,
				'order'       => 'ASC',
			);
			$posts = get_posts( $args );
			if ( $posts ) {
				foreach ( $posts as $post ) {
					setup_postdata( $post );
					$font = get_post_meta( $post->ID, '_ultp_font_settings', true );

					if ( $font ) {
						array_push(
							$custom_fonts,
							array(
								'title' => $post->post_title,
								'font'  => $font,
							)
						);
					}
				}
				wp_reset_postdata();
			}
		}

		wp_localize_script(
			'ultp-blocks-editor-script',
			'ultp_data',
			array(
				'url'                => ULTP_URL,
				'ajax'               => admin_url( 'admin-ajax.php' ),
				'security'           => wp_create_nonce( 'ultp-admin-nonce' ),
				'hide_import_btn'    => ultimate_post()->get_setting( 'hide_import_btn' ),
				'premium_link'       => Xpo::generate_utm_link(),
				'show_upsell'      => $show_upsell,
				'archive'            => ultimate_post()->is_archive_builder(),
				'settings'           => ultimate_post()->get_client_settings(),
				'post_type'          => $post_type == 'premade' ? 'ultp_builder' : $post_type, // premade used for ultp.wpxpo.com
				'date_format'        => get_option( 'date_format' ),
				'time_format'        => get_option( 'time_format' ),
				'blog'               => get_current_blog_id(),
				'affiliate_id'       => apply_filters( 'ultp_affiliate_id', false ),
				'category_url'       => admin_url( 'edit-tags.php?taxonomy=category' ),
				'disable_image_size' => ultimate_post()->get_setting( 'disable_image_size' ),
				'dark_logo'          => get_option( 'ultp_site_dark_logo' ) ? get_option( 'ultp_site_dark_logo' ) : false,
				'builder_url'        => admin_url( 'admin.php?page=ultp-settings#builder' ),
				'custom_fonts'       => $custom_fonts,
				'editor_api'         => ULTP_EDITOR_API,
				'block_style_api'    => ULTP_BLOCK_STYLE_API,
				'plugins_url'        => admin_url( 'plugins.php' ),
			)
		);
		wp_set_script_translations( 'ultp-blocks-editor-script', 'ultimate-post', ULTP_PATH . 'languages/' );
	}


	/**
	 * Fire when plugin is first installed.
	 *
	 * @since v.1.0.0
	 * @return void No return value.
	 */
	public function plugin_activation_hook() {
		$data        = get_option( 'ultp_options', array() );
		$currentDate = new \DateTime();
		$currentDate->setTime( 0, 0, 0, 0 );
		$init_data = array(
			'preloader_style'             => 'style1',
			'preloader_color'             => '#037fff',
			'container_width'             => '1140',
			'hide_import_btn'             => '',
			'disable_image_size'          => '',
			'disable_view_cookies'        => '',
			'enable_google_fonts'         => '',
			'ultp_templates'              => 'true',
			'ultp_elementor'              => 'true',
			'ultp_table_of_content'       => 'true',
			'ultp_builder'                => 'true',
			'ultp_dynamic_content'        => 'true',
			'ultp_custom_font'            => 'true',
			'post_grid_1'                 => 'yes',
			'post_grid_2'                 => 'yes',
			'post_grid_3'                 => 'yes',
			'post_grid_4'                 => 'yes',
			'post_grid_5'                 => 'yes',
			'post_grid_6'                 => 'yes',
			'post_grid_7'                 => 'yes',
			'post_list_1'                 => 'yes',
			'post_list_2'                 => 'yes',
			'post_list_3'                 => 'yes',
			'post_list_4'                 => 'yes',
			'post_module_1'               => 'yes',
			'post_module_2'               => 'yes',
			'post_slider_1'               => 'yes',
			'post_slider_2'               => 'yes',
			'heading'                     => 'yes',
			'image'                       => 'yes',
			'taxonomy'                    => 'yes',
			'wrapper'                     => 'yes',
			'news_ticker'                 => 'yes',
			'accordion'                   => 'yes',
			'star_rating'                 => 'yes',
			'youtube_gallery'             => 'yes',
			'builder_advance_post_meta'   => 'yes',
			'builder_archive_title'       => 'yes',
			'builder_author_box'          => 'yes',
			'builder_post_next_previous'  => 'yes',
			'builder_post_author_meta'    => 'yes',
			'builder_post_breadcrumb'     => 'yes',
			'builder_post_category'       => 'yes',
			'builder_post_comment_count'  => 'yes',
			'builder_post_comments'       => 'yes',
			'builder_post_content'        => 'yes',
			'builder_post_date_meta'      => 'yes',
			'builder_post_excerpt'        => 'yes',
			'builder_post_featured_image' => 'yes',
			'builder_post_reading_time'   => 'yes',
			'builder_post_social_share'   => 'yes',
			'builder_post_tag'            => 'yes',
			'builder_post_title'          => 'yes',
			'builder_post_view_count'     => 'yes',
			'save_version'                => wp_rand( 1, 1000 ),
			'activated_date'              => $currentDate->getTimestamp(),
		);
		if ( empty( $data ) ) {
			update_option( 'ultp_options', $init_data );
			$GLOBALS['ultp_settings'] = $init_data;
		} else {
			foreach ( $init_data as $key => $single ) {
				if ( ! isset( $data[ $key ] ) ) {
					$data[ $key ] = $single;
				}
			}
			update_option( 'ultp_options', $data );
			$GLOBALS['ultp_settings'] = $data;
		}
		if ( ! get_transient( 'wpxpo_installation_date' ) ) {
			set_transient( 'wpxpo_installation_date', 'yes', 5 * DAY_IN_SECONDS ); // 5 Days Notice
		}
	}
}

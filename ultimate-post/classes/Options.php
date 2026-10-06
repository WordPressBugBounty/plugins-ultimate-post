<?php

/**
 * Options Action.
 *
 * @package ULTP\Notice
 * @since v.1.0.0
 */

namespace ULTP;

use ULTP\Includes\Durbin\Xpo;

defined( 'ABSPATH' ) || exit;

/**
 * Options class.
 *
 * Handles plugin options and settings for Ultimate Post.
 *
 * @package ULTP\Options
 * @since 4.0.0
 */
class Options {

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_external_redirects' ) );
		add_action( 'admin_menu', array( $this, 'menu_page_callback' ) );
		add_action( 'admin_footer', array( $this, 'sync_addon_submenu_visibility' ) );
		add_action( 'in_admin_header', array( $this, 'remove_all_notices' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_settings_meta' ), 10, 2 );
		add_filter( 'plugin_action_links_' . ULTP_BASE, array( $this, 'plugin_action_links_callback' ) );
	}


	/**
	 * Adds extra links to the plugin row meta on the plugins page.
	 *
	 * @param array  $links Existing plugin meta links.
	 * @param string $file  Plugin file path.
	 * @return array Modified plugin meta links.
	 */
	public function plugin_settings_meta( $links, $file ) {
		if ( strpos( $file, 'ultimate-post.php' ) !== false ) {
			$new_links = array(
				'ultp_docs'     => '<a href="https://wpxpo.com/docs/" target="_blank">' . esc_html__( 'Docs', 'ultimate-post' ) . '</a>',
				'ultp_tutorial' => '<a href="https://www.youtube.com/watch?v=JZxIflYKOuM&list=PLPidnGLSR4qcAwVwIjMo1OVaqXqjUp_s4" target="_blank">' . esc_html__( 'Tutorials', 'ultimate-post' ) . '</a>',
				'ultp_support'  => '<a href="' . esc_url(
					Xpo::generate_utm_link(
						array(
							'url'    => 'https://www.wpxpo.com/contact/',
							'utmKey' => 'plugin_dir_support',
						)
					)
				) . '" target="_blank">' . esc_html__( 'Support', 'ultimate-post' ) . '</a>',
			);
			$links     = array_merge( $links, $new_links );
		}
		return $links;
	}

	/**
	 * Adds quick action links below the plugin name.
	 *
	 * @param array $links Default plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function plugin_action_links_callback( $links ) {
		$setting_link = array(
			'ultp_options' => '<a href="' . esc_url( admin_url( 'admin.php?page=ultp-settings#startersites' ) ) . '">' . esc_html__( 'Starter Sites', 'ultimate-post' ) . '</a>',
		);

		// The "Upgrade to Pro" / "Renew License" link is added after this by
		// includes/notice/class-promo-links.php.
		return array_merge( $setting_link, $links );
	}


	/**
	 * Add admin menu option page and submenus.
	 *
	 * @since v.1.0.0
	 * @return void No return value.
	 */
	public static function menu_page_callback() {
		$ultp_menu_icon = 'data:image/svg+xml;base64,PHN2ZyBpZD0iTGF5ZXJfMSIgZGF0YS1uYW1lPSJMYXllciAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA1MCA0OC4zIj4NCiAgPGRlZnM+DQogICAgPHN0eWxlPg0KICAgICAgLmNscy0xIHsNCiAgICAgICAgZmlsbDogI2E3YWFhZDsNCiAgICAgIH0NCiAgICA8L3N0eWxlPg0KICA8L2RlZnM+DQogIDx0aXRsZT5Qb3N0eCBJY29uIGNtcHJzc2QgU1ZHPC90aXRsZT4NCiAgPGc+DQogICAgPHBhdGggY2xhc3M9ImNscy0xIiBkPSJNMTguODEsOXY4LjlIOC4xOUE2LjE5LDYuMTksMCwwLDAsMiwyMy43N2EzLjE2LDMuMTYsMCwwLDEtMi0yLjk0VjRBMy4xNiwzLjE2LDAsMCwxLDMuMTUuODVIMjBhMy4xOCwzLjE4LDAsMCwxLDMsMi4zMUE2LjIxLDYuMjEsMCwwLDAsMTguODEsOVoiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAgLTAuODUpIi8+DQogICAgPHBhdGggY2xhc3M9ImNscy0xIiBkPSJNNDUsOVYyM0gzMS4xYTYuMjMsNi4yMywwLDAsMC00LjkzLTQuOTNBNS41NCw1LjU0LDAsMCwwLDI1LDE3Ljk0SDIxLjg1VjlBMy4xNSwzLjE1LDAsMCwxLDIzLjEzLDYuNWEzLjEyLDMuMTIsMCwwLDEsMS40My0uNThsLjA5LDBINDEuODNBMy4xNCwzLjE0LDAsMCwxLDQ1LDlaIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwIC0wLjg1KSIvPg0KICAgIDxwYXRoIGNsYXNzPSJjbHMtMSIgZD0iTTUwLDI5LjE3VjQ2YTMuMTYsMy4xNiwwLDAsMS0zLjE1LDMuMTVIMzBhMy4xOCwzLjE4LDAsMCwxLTMtMi4zMUE2LjIyLDYuMjIsMCwwLDAsMzEuMjEsNDFWMjZINDYuODVhMy4zLDMuMywwLDAsMSwxLjE0LjIxQTMuMTYsMy4xNiwwLDAsMSw1MCwyOS4xN1oiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAgLTAuODUpIi8+DQogICAgPHBhdGggY2xhc3M9ImNscy0xIiBkPSJNMjguMTYsMjQuMTNWNDFhMy4xMywzLjEzLDAsMCwxLTEuMjksMi41NCwzLDMsMCwwLDEtMS40Ny41OGwwLDBIOC4xOUEzLjE1LDMuMTUsMCwwLDEsNSw0MVYyNGEzLjE3LDMuMTcsMCwwLDEsMy4xNS0zSDI1YTMuMTIsMy4xMiwwLDAsMSwxLjE0LjIyLDMuMjQsMy4yNCwwLDAsMSwxLjksMi4xQTMuNjMsMy42MywwLDAsMSwyOC4xNiwyNC4xM1oiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAgLTAuODUpIi8+DQogIDwvZz4NCjwvc3ZnPg0K';
		$menupage_cap   = apply_filters( 'ultp_menu_page_capability', 'manage_options' );
		add_menu_page(
			__( 'PostX', 'ultimate-post' ),
			__( 'PostX', 'ultimate-post' ),
			$menupage_cap,
			'ultp-settings',
			array( self::class, 'ultp_dashboard' ),
			$ultp_menu_icon,
			58.5
		);

		add_submenu_page(
			'ultp-settings',
			__( 'PostX Dashboard', 'ultimate-post' ),
			__( 'Getting Started', 'ultimate-post' ),
			$menupage_cap,
			'ultp-settings'
		);

		$menu_lists = array(
			'startersites'    => esc_html__( 'Starter Sites', 'ultimate-post' ),
			'builder'         => esc_html__( 'Site Builder', 'ultimate-post' ),
			'saved-templates' => esc_html__( 'Saved Templates', 'ultimate-post' ),
			'custom-font'     => esc_html__( 'Custom Font', 'ultimate-post' ),
			'addons'          => esc_html__( 'Add-ons', 'ultimate-post' ),
			'blocks'          => esc_html__( 'Blocks', 'ultimate-post' ),
			'settings'        => esc_html__( 'Settings', 'ultimate-post' ),
		);
		if ( defined( 'ULTP_PRO_VER' ) ) {
			$menu_lists['license'] = esc_html__( 'License ', 'ultimate-post' );
		}

		foreach ( $menu_lists as $key => $val ) {
			add_submenu_page(
				'ultp-settings',
				$val,
				$val,
				$menupage_cap,
				'ultp-settings#' . $key,
				array( __CLASS__, 'render_main' )
			);
		}
		add_submenu_page(
			'ultp-settings',
			esc_html__( 'Initial Setup', 'ultimate-post' ),
			esc_html__( 'Initial Setup', 'ultimate-post' ),
			$menupage_cap,
			'ultp-setup-wizard',
			array( __CLASS__, 'ultp_wizard_page' )
		);

		// The "Upgrade to Pro" / "Renew License" item is added last by includes/notice/class-promo-links.php.

		add_theme_page(
			__( 'Starter Sites', 'ultimate-post' ),
			__( 'Starter Sites', 'ultimate-post' ),
			$menupage_cap,
			'ultp-startersites',
			array( self::class, 'handle_external_redirects' )
		);
	}

	/**
	 * Sync addon-backed WordPress submenu items with addon enabled status.
	 *
	 * @since v.4.1.7
	 * @return void No return value.
	 */
	public function sync_addon_submenu_visibility() {
		$settings = ultimate_post()->get_setting();
		$submenus = array(
			'builder'         => 'ultp_builder',
			'saved-templates' => 'ultp_templates',
			'custom-font'     => 'ultp_custom_font',
		);

		foreach ( $submenus as $slug => $setting_key ) {
			$submenus[ $slug ] = isset( $settings[ $setting_key ] ) && $settings[ $setting_key ] !== 'false';
		}
		?>
		<script>
			( function() {
				const postxAddonSubmenus = <?php echo wp_json_encode( $submenus ); ?>;

				Object.keys( postxAddonSubmenus ).forEach( function( slug ) {
					const link = document.querySelector( '#adminmenu a[href*="page=ultp-settings#' + slug + '"]' );
					const item = link ? link.closest( 'li' ) : null;

					if ( ! item ) {
						return;
					}

					item.id = 'postx-submenu-' + slug;
					item.style.display = postxAddonSubmenus[ slug ] ? '' : 'none';
				} );
			}() );
		</script>
		<?php
	}

	/**
	 * Handle external redirects for menu and pro links.
	 *
	 * @since v.1.0.0
	 * @return void No return value.
	 */
	public function handle_external_redirects() {
		if (empty($_GET['page'])) {     // @codingStandardsIgnoreLine
			return;
		}
		$_page = sanitize_key( $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page slug.
		if ('ultp-startersites' === $_page) {   // @codingStandardsIgnoreLine
			wp_safe_redirect( admin_url( 'admin.php?page=ultp-settings#startersites' ) );
			exit;
		} else if ('go_postx_pro' === $_page) {   // @codingStandardsIgnoreLine
			wp_redirect( // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Intentional redirect to the fixed wpxpo.com pricing URL.
				Xpo::generate_utm_link(
					array(
						'utmKey' => 'dashboard_go_pro',
					)
				)
			);
			die();
		}
	}

	/**
	 * Render the wizard page.
	 *
	 * @since v.2.4.4
	 */
	public static function ultp_wizard_page() {
		?>
		<div class="ultp-wizard-page-wrap" id="ultp-wizard-page"></div>
		<?php
	}

	/**
	 * Render the dashboard page.
	 *
	 * @since v.1.0.0
	 * @return void No return value.
	 */
	public static function ultp_dashboard() {
		echo '<div id="ultp-dashboard"></div>';
	}

	/**
	 * Remove all notifications from menu page.
	 *
	 * @since v.1.0.0
	 * @return void No return value.
	 */
	public static function remove_all_notices() {
		if (isset($_GET['page'])) {   // @codingStandardsIgnoreLine
			$page = sanitize_key($_GET['page']);    // @codingStandardsIgnoreLine
			if (
				$page === 'ultp-settings' ||
				$page === 'ultp-license' ||
				$page === 'ultp-setup-wizard'
			) {
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );
			}
		}
	}
}

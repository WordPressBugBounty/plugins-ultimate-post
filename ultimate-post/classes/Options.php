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
		add_action( 'in_admin_header', array( $this, 'remove_all_notices' ) );
		add_filter( 'plugin_action_links_' . ULTP_BASE, array( $this, 'plugin_action_links_callback' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_settings_meta' ), 10, 2 );
	}


	/**
	 * Plugin Page Menu Add
	 *
	 * @since v.1.0.0
	 * @return ARRAY
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
	 * Settings Pro Update Link
	 *
	 * @since v.1.0.0
	 * @return ARRAY
	 */
	public function plugin_action_links_callback( $links ) {
		$setting_link                 = array();
		$setting_link['ultp_options'] = '<a href="' . esc_url( admin_url( 'admin.php?page=ultp-settings#startersites' ) ) . '">' . esc_html__( 'Starter Sites', 'ultimate-post' ) . '</a>';
		$upgrade_link                 = array();
		if ( ! defined( 'ULTP_PRO_VER' ) || Xpo::is_lc_expired() ) {
			$url = ! defined( 'ULTP_PRO_VER' ) ? Xpo::generate_utm_link(
				array(
					'utmKey' => 'plugin_dir_pro',
				)
			) : 'https://account.wpxpo.com/checkout/?edd_license_key=' . Xpo::get_lc_key();

			$text                     = ! defined( 'ULTP_PRO_VER' ) ? esc_html__( 'Switch to Pro', 'ultimate-post' ) : esc_html__( 'Renew License', 'ultimate-post' );
			$upgrade_link['ultp_pro'] = '<a style="color: #e83838; font-weight: bold;" target="_blank" href="' . esc_url( $url ) . '">' . $text . '</a>';
		}
		return array_merge( $setting_link, $links, $upgrade_link );
	}


	/**
	 * Admin Menu Option Page
	 *
	 * @since v.1.0.0
	 * @return NULL
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
			'builder'      => esc_html__( 'Site Builder', 'ultimate-post' ),
			'startersites' => esc_html__( 'Starter Sites', 'ultimate-post' ),
			'blocks'       => esc_html__( 'Blocks', 'ultimate-post' ),
			'integrations' => esc_html__( 'Integrations', 'ultimate-post' ),
		);
		if ( defined('ULTP_PRO_VER') ) {
			$menu_lists['license'] = esc_html__( 'License', 'ultimate-post' );
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

		$pro_link      = '';
		$pro_link_text = '';
		if ( ! Xpo::is_lc_active() ) {
			$pro_link      = Xpo::generate_utm_link(
				array(
					'utmKey' => 'sub_menu',
				)
			);
			$pro_link_text = __( 'Upgrade to Pro', 'ultimate-post' );
		} elseif ( Xpo::is_lc_expired() ) {
			$license_key   = Xpo::get_lc_key();
			$pro_link      = 'https://account.wpxpo.com/checkout/?edd_license_key=' . $license_key;
			$pro_link_text = __( 'Renew License', 'ultimate-post' );
		}

		if ( ! empty( $pro_link ) ) {
			ob_start();
			?>
				<a href="<?php echo esc_url( $pro_link ); ?>" target="_blank" class="ultp-go-pro">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M2.86 6.553a.5.5 0 01.823-.482l3.02 2.745c.196.178.506.13.64-.098L9.64 4.779a.417.417 0 01.72 0l2.297 3.939a.417.417 0 00.64.098l3.02-2.745a.5.5 0 01.823.482l-1.99 8.63a.833.833 0 01-.813.646H5.663a.833.833 0 01-.812-.646L2.86 6.553z" stroke="currentColor" stroke-width="1.5"></path>
					</svg>
					<span><?php echo esc_html( $pro_link_text ); ?></span>
				</a>
			<?php
			$submenu_content = ob_get_clean();

			add_submenu_page(
				'ultp-settings',
				'',
				$submenu_content,
				'manage_options',
				'ultp-pro',
				array( self::class, 'handle_external_redirects' )
			);

		}

		add_theme_page(
			__( 'Starter Sites', 'ultimate-post' ),
			__( 'Starter Sites', 'ultimate-post' ),
			$menupage_cap,
			'ultp-startersites',
			array( self::class, 'handle_external_redirects' )
		);
	}

	public function handle_external_redirects() {
        if ( empty( $_GET['page'] ) ) {     // @codingStandardsIgnoreLine
			return;
		}
		$_page = sanitize_key( $_GET['page'] );
        if ( 'ultp-startersites' === $_page  ) {   // @codingStandardsIgnoreLine
			exit( wp_safe_redirect( admin_url( 'admin.php?page=ultp-settings#startersites' ) ) );
        } else if ( 'go_postx_pro' === $_page ) {   // @codingStandardsIgnoreLine
			wp_redirect(
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
	 * wizard page
	 *
	 * * @since v.2.4.4
	 *
	 * @return STRING
	 */
	public static function ultp_wizard_page() {
		?>
		<div class="ultp-wizard-page-wrap" id="ultp-wizard-page"></div>
		<?php
	}

	public static function ultp_dashboard() {
		echo '<div id="ultp-dashboard"></div>';
	}

	/**
	 * Remove All Notification From Menu Page
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public static function remove_all_notices() {
        if ( isset($_GET['page']) ) {   // @codingStandardsIgnoreLine
            $page = sanitize_key($_GET['page']);    // @codingStandardsIgnoreLine
			if ( $page === 'ultp-settings' ||
				$page === 'ultp-license' ||
				$page === 'ultp-setup-wizard' ) {
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );
			}
		}
	}
}

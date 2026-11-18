<?php
/**
 * Upgrade links outside the notices.
 *
 * @package ULTP\Notice
 */

namespace ULTP\Includes\Notice;

defined( 'ABSPATH' ) || exit;

use ULTP\Includes\Helper\Xpo;

/**
 * The "Upgrade to Pro" link on the Plugins page and the "Upgrade to Pro" item in the PostX menu.
 *
 * Both switch to a sale's text during a sale, and neither shows while PostX Pro is active.
 * The sale texts and dates live in Campaigns: plugin_link_offers() and menu_offers().
 */
class Promo_Links {

	/**
	 * Slug of the menu item.
	 */
	const MENU_SLUG = 'ultp-pro';

	/**
	 * Register the hooks.
	 */
	public function __construct() {
		add_filter( 'plugin_action_links_' . ULTP_BASE, array( $this, 'add_plugin_action_link' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_link' ), 11 ); // After Options adds the rest of the menu, so it stays last.
		add_action( 'admin_init', array( $this, 'redirect_menu_link' ) );
	}

	/**
	 * Append the upgrade link to PostX's row on the Plugins page.
	 *
	 * @param array $links Plugin action links.
	 * @return array
	 */
	public function add_plugin_action_link( $links ) {
		if ( ! Xpo::show_upsell() ) {
			return $links;
		}

		$text = esc_html__( 'Upgrade to Pro', 'ultimate-post' );
		$url  = Xpo::generate_utm_link(
			array(
				'utmKey' => 'sub_menu',
			)
		);

		$offer = self::live_offer( self::campaign( 'plugin_link_offers', array() ) );
		if ( $offer ) {
			$url  = Xpo::generate_utm_link(
				array(
					'utmKey' => $offer['utm_key'],
				)
			);
			$text = $offer['text'];
		}

		$links['ultp_pro'] = '<a style="color: #0062ff; font-weight: bold;" target="_blank" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
		return $links;
	}

	/**
	 * Add the upgrade item at the end of the PostX menu.
	 *
	 * @return void
	 */
	public function add_menu_link() {
		$link = $this->get_menu_link();
		if ( empty( $link ) ) {
			return;
		}

		ob_start();
		try {
			?>
		<a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" class="ultp-go-pro">
			<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M2.86 6.553a.5.5 0 01.823-.482l3.02 2.745c.196.178.506.13.64-.098L9.64 4.779a.417.417 0 01.72 0l2.297 3.939a.417.417 0 00.64.098l3.02-2.745a.5.5 0 01.823.482l-1.99 8.63a.833.833 0 01-.813.646H5.663a.833.833 0 01-.812-.646L2.86 6.553z" stroke="currentColor" stroke-width="1.5"></path>
			</svg>
			<span><?php echo esc_html( $link['text'] ); ?></span>
		</a>
		<?php
		} finally {
			$submenu_content = ob_get_clean();
		}

		add_submenu_page(
			'ultp-settings',
			'',
			$submenu_content,
			'manage_options',
			self::MENU_SLUG,
			'__return_null' // Never rendered: redirect_menu_link() leaves first.
		);
	}

	/**
	 * Send admin.php?page=ultp-pro to the same link the menu item shows.
	 *
	 * The item's visible link already opens it directly; this covers the item's own menu link
	 * (reachable by keyboard or by URL), which used to end in a fatal error.
	 *
	 * @return void
	 */
	public function redirect_menu_link() {
		if ( empty( $_GET['page'] ) || self::MENU_SLUG !== sanitize_key( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page slug.
			return;
		}
		$link = current_user_can( 'manage_options' ) ? $this->get_menu_link() : array();
		if ( empty( $link ) ) {
			return;
		}
		wp_redirect( $link['url'] ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Fixed wpxpo.com pricing or checkout URL.
		exit;
	}

	/**
	 * The first offer whose `start`–`end` window includes now.
	 *
	 * @param array $offers Offers from Campaigns.
	 * @return array|null
	 */
	private static function live_offer( $offers ) {
		$current_time = gmdate( 'U' );
		foreach ( $offers as $offer ) {
			if ( ! isset( $offer['start'], $offer['end'], $offer['text'] ) ) {
				continue;
			}
			$offer_start = gmdate( 'U', strtotime( $offer['start'] ) );
			$offer_end   = gmdate( 'U', strtotime( $offer['end'] ) );
			if ( $current_time >= $offer_start && $current_time <= $offer_end ) {
				return $offer;
			}
		}
		return null;
	}

	/**
	 * Read sale settings from Campaigns. class-campaigns.php is edited for every sale, so it is loaded
	 * here inside a try, as Notice does: a mistake in it drops the sale text, never the page.
	 *
	 * @param string $method   Campaigns method to call.
	 * @param array  $fallback Returned when the file or method fails.
	 * @return array
	 */
	private static function campaign( $method, $fallback ) {
		try {
			$value = call_user_func( array( Campaigns::class, $method ) );
		} catch ( \Throwable $e ) {
			return $fallback;
		}
		return is_array( $value ) ? $value : $fallback;
	}

	/**
	 * The menu item's link and text, or nothing when PostX Pro is active.
	 *
	 * @return array{url: string, text: string}|array Empty when no item should show.
	 */
	private function get_menu_link() {
		$offer = self::live_offer( self::campaign( 'menu_offers', array() ) );

		if ( Xpo::show_upsell() ) {
			return array(
				'url'  => Xpo::generate_utm_link(
					array(
						'utmKey' => 'sub_menu',
					)
				),
				'text' => $offer ? $offer['text'] : __( 'Upgrade to Pro', 'ultimate-post' ),
			);
		}

		return array();
	}
}

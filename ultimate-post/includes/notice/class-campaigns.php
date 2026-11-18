<?php
/**
 * Campaign registry.
 *
 * @package ULTP\Notice
 */

namespace ULTP\Includes\Notice;

defined( 'ABSPATH' ) || exit;

/**
 * Every dated campaign notice PostX can show. This is the only file to edit for a new sale.
 *
 * To add a campaign, copy an entry from the matching list and change it:
 * - Use a new `key` every time. Reusing an old key brings back a dismissal the user made last time.
 * - Write dates as 'YYYY-MM-DD HH:MM Asia/Dhaka'. Start at 00:00, end at 23:59.
 * - Only list the settings that differ from the defaults in the notice class
 *   (Banner_Notice, Content_Notice, Image_Notice).
 *
 * Past campaigns stay here as examples. They have ended, so they never display.
 *
 * When two campaigns are live at once they all show, in this order: banners, content notices, image banners.
 */
final class Campaigns {

	/**
	 * All campaigns, in display order.
	 *
	 * @return Abstract_Campaign_Notice[]
	 */
	public static function all() {
		return array_merge( self::banners(), self::content_notices(), self::image_banners() );
	}

	/**
	 * Hello bar ids the dashboard may dismiss. Every `id` in src/dashboard/hellobar/index.js
	 * must be listed here, or its bar can't be hidden: add the id here when you add a hello bar there.
	 *
	 * @return string[]
	 */
	public static function hellobar_keys() {
		return array(
			'ultp_helloBar_summer_sale_2026_v1',
			'ultp_helloBar_summer_sale_2026_v1_2',
			'ultp_helloBar_base_price_2026',
			'ultp_helloBar_birthday_sale_2026_v1',
		);
	}

	/**
	 * Sales for the "Upgrade to Pro" link on the Plugins page. The first live one replaces the link's
	 * text and UTM key; outside them it reads "Upgrade to Pro". Dates as for campaigns.
	 *
	 * @return array[]
	 */
	public static function plugin_link_offers() {
		return array(
			array(
				'start'   => '2026-07-06 00:00 Asia/Dhaka',
				'end'     => '2026-08-01 23:59 Asia/Dhaka',
				'text'    => __( 'Summer Sale - Up to 50% OFF', 'ultimate-post' ),
				'utm_key' => 'summer_sale_meta',
			),
			array(
				'start'   => '2026-10-04 00:00 Asia/Dhaka',
				'end'     => '2026-10-15 23:59 Asia/Dhaka',
				'text'    => __( 'Enjoy 60% off', 'ultimate-post' ),
				'utm_key' => 'birthday',
			),
		);
	}

	/**
	 * Sales for the "Upgrade to Pro" item in the PostX menu. The first live one replaces the item's
	 * text; outside them it reads "Upgrade to Pro". Dates as for campaigns.
	 *
	 * @return array[]
	 */
	public static function menu_offers() {
		return array(
			array(
				'start' => '2026-01-01 00:00 Asia/Dhaka',
				'end'   => '2026-02-15 23:59 Asia/Dhaka',
				'text'  => __( 'New Year Offer!', 'ultimate-post' ),
			),
		);
	}

	/**
	 * Countdown banners. Images come from assets/img/dashboard_banner/{image_dir}/.
	 *
	 * @return Banner_Notice[]
	 */
	private static function banners() {
		return array(
			new Banner_Notice(
				array(
					'key'       => 'ultp_banner_flash_sale_2026_1',
					'start'     => '2026-05-18 00:00 Asia/Dhaka',
					'end'       => '2026-05-21 23:59 Asia/Dhaka',
					'image_dir' => 'flash_sale',
					'text'      => 'Deal ending soon',
					'utm_key'   => 'flash_sale_content',
				)
			),
			new Banner_Notice(
				array(
					'key'       => 'ultp_banner_surprise_sale_2026_1',
					'start'     => '2026-05-29 00:00 Asia/Dhaka',
					'end'       => '2026-06-01 23:59 Asia/Dhaka',
					'image_dir' => 'surprise_sale',
					'text'      => 'Limited Time Offer',
					'utm_key'   => 'surprise_sale_content',
				)
			),
			new Banner_Notice(
				array(
					'key'       => 'ultp_banner_massive_sale_2026_1',
					'start'     => '2026-06-17 00:00 Asia/Dhaka',
					'end'       => '2026-06-20 23:59 Asia/Dhaka',
					'image_dir' => 'massive_sale',
					'text'      => 'Deal ending soon',
					'utm_key'   => 'massive_sale_content',
				)
			),
			new Banner_Notice(
				array(
					'key'       => 'ultp_banner_final_hours_sale_2026_1',
					'start'     => '2026-06-28 00:00 Asia/Dhaka',
					'end'       => '2026-06-30 23:59 Asia/Dhaka',
					'image_dir' => 'final_hours_sale',
					'text'      => 'Hurry Before It Ends!',
					'utm_key'   => 'final_hours_content',
				)
			),
		);
	}

	/**
	 * Text and button notices.
	 *
	 * @return Content_Notice[]
	 */
	private static function content_notices() {
		return array(
			new Content_Notice(
				array(
					'key'                => 'ultp_dashboard_content_notice_summer_sale_2026_vv1',
					'start'              => '2026-07-06 00:00 Asia/Dhaka',
					'end'                => '2026-07-12 23:59 Asia/Dhaka',
					'utm_key'            => 'summer_db',
					'content_heading'    => __( 'Summer Sale:', 'ultimate-post' ),
					/* translators: %s: discount percentage */
					'content_subheading' => __( 'Enjoy up to %s on PostX Pro.', 'ultimate-post' ),
					'discount_content'   => '  50% OFF',
					'icon'               => ULTP_URL . 'assets/img/dashboard_banner/brand_logo.svg',
					'is_discount_logo'   => true,
				)
			),
			new Content_Notice(
				array(
					'key'                => 'ultp_dashboard_content_notice_birthday_sale_2026_v1',
					'start'              => '2026-10-04 00:00 Asia/Dhaka',
					'end'                => '2026-10-16 23:59 Asia/Dhaka',
					'utm_key'            => 'birthday',
					'content_heading'    => __( '7th Anniversary Offer:', 'ultimate-post' ),
					/* translators: %s: discount percentage */
					'content_subheading' => __( 'Enjoy up to %s on PostX Pro.', 'ultimate-post' ),
					'discount_content'   => '  60% OFF',
					'icon'               => ULTP_URL . 'assets/img/dashboard_banner/60-percent.png',
					'is_discount_logo'   => true,
				)
			),
		);
	}

	/**
	 * Full-image banners.
	 *
	 * @return Image_Notice[]
	 */
	private static function image_banners() {
		return array(
			new Image_Notice(
				array(
					'key'        => 'ultp_summer_sale_2612',
					'start'      => '2026-07-20 00:00 Asia/Dhaka',
					'end'        => '2026-08-01 23:59 Asia/Dhaka',
					'banner_src' => ULTP_URL . 'assets/img/dashboard_banner/dashboard_banner_summer_sale.png',
					'utm_key'    => 'sub_menu_offer',
				)
			),
			new Image_Notice(
				array(
					'key'        => 'ultp_summer_sale_2613',
					'start'      => '2026-08-09 00:00 Asia/Dhaka',
					'end'        => '2026-08-16 23:59 Asia/Dhaka',
					'banner_src' => ULTP_URL . 'assets/img/dashboard_banner/dashboard_banner_summer_sale.png',
					'utm_key'    => 'sub_menu_offer',
				)
			),
			new Image_Notice(
				array(
					'key'         => 'ultp_preco_sale_campaign_262_12',
					'start'       => '2026-08-23 00:00 Asia/Dhaka',
					'end'         => '2026-08-29 23:59 Asia/Dhaka',
					'banner_src'  => ULTP_URL . 'assets/img/dashboard_banner/preco_banner_insider_deal.png',
					'url'         => 'https://www.wpxpo.com/product/postx/?utm_source=db-postx-notice&utm_medium=insider-deal&utm_campaign=postx-dashboard/#pricing',
					'close_color' => '#ffffff',
				)
			),
			new Image_Notice(
				array(
					'key'         => 'ultp_preco_sale_campaign_262_22',
					'start'       => '2026-08-30 00:00 Asia/Dhaka',
					'end'         => '2026-09-19 23:59 Asia/Dhaka',
					'banner_src'  => ULTP_URL . 'assets/img/dashboard_banner/preco_banner_early_bird.png',
					'url'         => 'https://www.wpxpo.com/product/postx/?utm_source=db-postx-notice&utm_medium=early-bird&utm_campaign=postx-dashboard/#pricing',
					'close_color' => '#ffffff',
				)
			),
		);
	}
}

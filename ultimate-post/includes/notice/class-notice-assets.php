<?php
/**
 * Notice assets.
 *
 * @package ULTP\Notice
 */

namespace ULTP\Includes\Notice;

defined( 'ABSPATH' ) || exit;

/**
 * The stylesheet and script shared by every notice. Queue them only when a notice will show.
 */
final class Notice_Assets {

	/**
	 * Handle of the stylesheet shared by every notice.
	 */
	const STYLESHEET = 'ultp-notice';

	/**
	 * Handle of the countdown script.
	 */
	const COUNTDOWN_SCRIPT = 'ultp-notice-countdown';

	/**
	 * Queue assets/css/ultp-notice.css.
	 *
	 * @return void
	 */
	public static function enqueue_stylesheet() {
		wp_enqueue_style( self::STYLESHEET, ULTP_URL . 'assets/css/ultp-notice.css', array(), ULTP_VER );
	}

	/**
	 * Queue the countdown script in the footer.
	 *
	 * @return void
	 */
	public static function enqueue_countdown_script() {
		wp_enqueue_script( self::COUNTDOWN_SCRIPT, ULTP_URL . 'assets/js/ultp-notice-countdown.js', array( 'jquery' ), ULTP_VER, true );
	}
}

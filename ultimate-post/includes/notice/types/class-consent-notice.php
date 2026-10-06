<?php
/**
 * Data-collection consent notice.
 *
 * @package ULTP\Notice
 */

namespace ULTP\Includes\Notice;

defined( 'ABSPATH' ) || exit;

use ULTP\Includes\Durbin\Xpo;
use ULTP\Includes\Durbin\DurbinClient;

/**
 * Asks the site owner to share diagnostic data. Shows until they accept or close it.
 *
 * Only users who can manage the site see it or answer it: the answer applies to the whole site.
 * Not a campaign: it has no dates and ignores the shared notice priority.
 */
class Consent_Notice implements Notice_Type {

	/**
	 * Notice key. Don't change it: the dismissal transient is named after it.
	 */
	const KEY = 'ultp_durbin_dc1';

	/**
	 * Transient prefix that marks the notice as answered.
	 */
	const DISMISS_PREFIX = 'ultp_durbin_notice_';

	/**
	 * Capability needed to see and answer the notice.
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Nonce for the two links, created once per page.
	 *
	 * @var string
	 */
	private $nonce = '';

	/**
	 * Notice key.
	 *
	 * @return string
	 */
	public function get_key() {
		return self::KEY;
	}

	/**
	 * Show to site managers until answered, and not on the page load right after clicking one of its links.
	 *
	 * @return bool
	 */
	public function should_display() {
		return current_user_can( self::CAPABILITY )
			&& ! isset( $_GET['ultp_durbin_key'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; hides the notice on the page load after it is dismissed.
			&& 'off' !== Xpo::get_transient_without_cache( self::DISMISS_PREFIX . self::KEY );
	}

	/**
	 * Queue the notice stylesheet.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		Notice_Assets::enqueue_stylesheet();
	}

	/**
	 * Print the notice. The template views/consent.php reads this object as `$notice`.
	 *
	 * @return void
	 */
	public function render() {
		$view = dirname( __DIR__ ) . '/views/consent.php';
		if ( ! is_readable( $view ) ) {
			return;
		}
		$this->enqueue_assets();
		$notice = $this; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Used by the template.
		include $view;
	}

	/**
	 * Link that closes the notice without sharing data.
	 *
	 * @return string Unescaped URL.
	 */
	public function get_close_url() {
		return add_query_arg(
			array(
				'ultp_durbin_key' => self::KEY,
				'wpnonce'         => $this->get_nonce(),
			)
		);
	}

	/**
	 * Link that accepts data sharing and closes the notice.
	 *
	 * @return string Unescaped URL.
	 */
	public function get_accept_url() {
		return add_query_arg(
			array(
				'ultp_durbin_key' => self::KEY,
				'ultp_get_durbin' => 'get',
				'wpnonce'         => $this->get_nonce(),
			)
		);
	}

	/**
	 * Act on a click of either link. Notice::set_dismiss_notice_callback() checks the nonce first.
	 *
	 * @return void
	 */
	public static function handle_dismiss_request() {
		$durbin_key = sanitize_text_field( wp_unslash( $_GET['ultp_durbin_key'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified by the caller.
		if ( empty( $durbin_key ) || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		Xpo::set_transient_without_cache( self::DISMISS_PREFIX . $durbin_key, 'off' );

		if ( 'get' === sanitize_text_field( wp_unslash( $_GET['ultp_get_durbin'] ?? '' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified by the caller.
			DurbinClient::send( DurbinClient::ACTIVATE_ACTION );
		}
	}

	/**
	 * Nonce for the two links, so both carry the same value.
	 *
	 * @return string
	 */
	private function get_nonce() {
		if ( '' === $this->nonce ) {
			$this->nonce = wp_create_nonce( 'ultp-nonce' );
		}
		return $this->nonce;
	}
}

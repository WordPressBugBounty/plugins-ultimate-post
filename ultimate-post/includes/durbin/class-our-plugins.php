<?php

namespace ULTP\Includes\Durbin;

defined( 'ABSPATH' ) || exit;
use ULTP\Includes\Durbin\Xpo;
class OurPlugins {


	/**
	 * Constructor. Hooks into various WordPress actions.
	 */
	public function __construct() {
		// Our Plugin Activation Hooks.
		add_action( 'wp_ajax_ultp_install_plugin', array( $this, 'ultp_install_plugin_callback' ) );
	}

	/**
	 * Handles plugin installation and activation via AJAX.
	 *
	 * @return void
	 */
	public function ultp_install_plugin_callback() {

		$nonce  = isset( $_POST['wpnonce'] ) ? sanitize_key( wp_unslash( $_POST['wpnonce'] ) ) : '';
		$plugin = isset( $_POST['plugin'] ) ? $_POST['plugin'] : '';

		if ( ! wp_verify_nonce( $nonce, 'ultp-nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'No plugin specified' ) );

		}

		$res = array( 'message' => 'false' );

		if ( $plugin ) {
			$res = Xpo::install_and_active_plugin( $plugin );
		}

		wp_send_json_success( array( 'message' => $res ) );

		die();
	}
}

<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ultp_templates_init' );
function ultp_templates_init() {
	if ( ultimate_post()->get_setting( 'ultp_templates' ) == 'true' ) {
		new \ULTP\Saved_Templates();
		new \ULTP\Shortcode();
	}
}

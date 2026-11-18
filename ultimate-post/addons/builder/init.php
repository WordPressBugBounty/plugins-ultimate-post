<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ultp_new_builder_init' );
function ultp_new_builder_init() {
	if ( ultimate_post()->get_setting( 'ultp_builder' ) == 'true' ) {
		new \ULTP\Builder();
		new \ULTP\RequestAPI();
	}
}

<?php
/**
 * Compatibility Action.
 *
 * @package ULTP\Compatibility
 * @since v.1.1.0
 */

namespace ULTP;

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility class.
 *
 * Handles compatibility with other plugins and themes for Ultimate Post.
 *
 * @package ULTP\Compatibility
 * @since 4.0.0
 */
class Compatibility {

	/**
	 * Setup class.
	 *
	 * @since v.1.1.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_front_page_builder' ) );

		// PublishPress Revisions Plugin Compatibility Add
		add_action( 'revisionary_copy_postmeta', array( $this, 'ultp_revisionary_copy_postmeta_callback' ), 10, 3 );
	}

	/**
	 * Compatibility for Front Page Builder
	 *
	 * @since 4.1.12
	 */
	public function handle_front_page_builder() {

		if ( get_option( 'ultp_frontpage_builder_comp' ) != 'yes' ) {
			$builder_condition = ultimate_post()->normalize_builder_conditions( get_option( 'ultp_builder_conditions', array() ) );
			if ( ! empty( $builder_condition ) &&
				! empty( $builder_condition['singular'] )
			) {
				$f_pages = array();
				foreach ( $builder_condition['singular'] as $id => $paths ) {
					if ( in_array( 'include/singular/front_page', $paths ) ) {
						$f_pages[ $id ] = array( 'include/front_page' ); // Add the ID to the list
						update_post_meta( $id, '_ultp_builder_type', 'front_page' );
						unset( $builder_condition['singular'][ $id ] );
					}
				}
				if ( empty( $builder_condition['front_page'] ) ) {
					$builder_condition['front_page'] = $f_pages;
				} else {
					$builder_condition['front_page'] = $builder_condition['front_page'] + $f_pages;
				}
				update_option( 'ultp_builder_conditions', $builder_condition );
				if ( get_option( 'ultp_builder_conditions', array() ) === $builder_condition ) {
					update_option( 'ultp_frontpage_builder_comp', 'yes' );
				}
			}
		}
	}

	/**
	 * Compatibility for PublishPress Revisions Plugin
	 *
	 * @url https://wordpress.org/plugins/revisionary/
	 *
	 * @since v.2.9.8
	 *
	 * @param MIXED $from_post The source post object or ID.
	 * @param INT   $to_post_id The destination post ID.
	 * @param ARRAY $args Additional arguments.
	 */
	public function ultp_revisionary_copy_postmeta_callback( $from_post, $to_post_id, $args ) {
		return Styles::regenerate_block_css( $to_post_id );
	}
}

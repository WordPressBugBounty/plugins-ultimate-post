<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PostX_WPBakery_Widget' ) ) {

	class PostX_WPBakery_Widget {

		/**
		 * Main constructor
		 */
		public function __construct() {
			add_shortcode( 'postx_wpbakery_widget', __CLASS__ . '::output' );
			if ( function_exists( 'vc_lean_map' ) ) {
				vc_lean_map( 'postx_wpbakery_widget', __CLASS__ . '::map' );
			}
		}

		/**
		 * Shortcode output
		 */
		public static function output( $atts, $content = null ) {
			$output    = '';
			$atts      = vc_map_get_attributes( 'postx_wpbakery_widget', $atts );
			$templates = $atts['saved_template'];

			if ( $templates && $templates != 'empty' ) {
				if ( isset( $_GET['vc_editable'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only WPBakery editor detection.
					// The <style> element is printed by WordPress (build_css_for_inline_print()). It stays outside
					// wp_kses(), which would turn the `>` of a child selector into `&gt;`.
					$output .= ultimate_post()->build_css_for_inline_print( $templates, true, true );
				}

				$args      = array(
					'p'         => $templates,
					'post_type' => 'ultp_templates',
				);
				$the_query = new \WP_Query( $args );

				if ( $the_query->have_posts() ) {
					while ( $the_query->have_posts() ) {
						$the_query->the_post();
						// The same steps as the_content(), returned instead of echoed, so no output buffer is needed.
						$post_content = str_replace( ']]>', ']]&gt;', apply_filters( 'the_content', get_the_content() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
						// The template's rendered content, filtered by wp_kses().
						$output .= wp_kses( $post_content, ultimate_post()->rendered_content_allowed_html() );
					}
					wp_reset_postdata();
				}
			} elseif ( isset( $_GET['vc_editable'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only WPBakery editor detection.
                // @codingStandardsIgnoreLine
					$output .= '<p style="text-align:center;">' . sprintf( esc_html__( 'Pick a Template from your saved ones. Or create a template from: %s.', 'ultimate-post' ) . ' ', '<strong><i>' . esc_html__( 'Dashboard > PostX > Saved Templates', 'ultimate-post' ) . '</i></strong>' ) . '</p>';
			}

			return $output;
		}




		public static function map() {
			return array(
				'name'        => esc_html__( 'PostX Template', 'ultimate-post' ),
				'description' => esc_html__( 'PostX Templates for WPBakery.', 'ultimate-post' ),
				'base'        => 'postx_wpbakery_widget',
				'icon'        => ULTP_URL . '/addons/wpbakery/wpbakery.png',
				'params'      => array(
					array(
						'type'        => 'dropdown',
						'heading'     => esc_html__( 'Select Your Template', 'ultimate-post' ),
						'param_name'  => 'saved_template',
						'value'       => array_flip( ultimate_post()->get_all_lists( 'ultp_templates', 'empty' ) ),
						'description' => esc_html__( 'Pick a Template from your saved ones. Or create a template from: "Dashboard > PostX > Saved Templates"', 'ultimate-post' ),
					),
				),
			);
		}
	}

}

new PostX_WPBakery_Widget();

<?php
defined( 'ABSPATH' ) || exit;

add_action( 'et_builder_ready', 'ultp_postx_template_divi_modules' );

function ultp_postx_template_divi_modules() {

	if ( ! class_exists( 'ET_Builder_Module' ) ) {
		return; }

	// init() writes $name and $icon_path, which ET_Builder_Module may not declare. The attribute stops
	// PHP 8.2+ deprecation notices without redeclaring a parent property.
	#[\AllowDynamicProperties]
	class PostX_Template_Module extends ET_Builder_Module {

		public $slug       = 'ultp_postx_template';
		public $vb_support = 'partial';

		function init() {
			$this->name      = esc_html__( 'PostX Template', 'ultimate-post' );
			$this->icon_path = plugin_dir_path( __FILE__ ) . 'icon.svg';
		}

		function get_fields() {
			return array(
				'templates' => array(
					'label'       => esc_html__( 'Select Your Template', 'ultimate-post' ),
					'type'        => 'select',
					'options'     => ultimate_post()->get_all_lists( 'ultp_templates', 'none' ),
					'default'     => 'none',
					'description' => esc_html__( 'Pick a Template from your saved ones. Or create a template from: <strong><i>Dashboard > PostX > Saved Templates</i></strong>', 'ultimate-post' ),
				),
			);
		}

		function render( $attrs, $render_slug, $content = null ) {
			$templates = $this->props['templates'];

			$output     = '';
			$content    = '';
			$body_class = get_body_class();
			if ( $templates && $templates != 'none' ) {
				$args      = array(
					'p'         => $templates,
					'post_type' => 'ultp_templates',
				);
				$the_query = new \WP_Query( $args );
				if ( $the_query->have_posts() ) {
					while ( $the_query->have_posts() ) {
						$the_query->the_post();
						// Builder preview only. The <style> element is printed by WordPress (build_css_for_inline_print()).
						$css = in_array( 'et-fb', $body_class ) ? ultimate_post()->build_css_for_inline_print( $templates, true, true ) : '';
						// The same steps as the_content(), returned instead of echoed, so no output buffer is needed.
						$post_content = str_replace( ']]>', ']]&gt;', apply_filters( 'the_content', get_the_content() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
						// The template's CSS (Divi builder preview only), printed by WordPress and outside
						// wp_kses(), which would turn the `>` of a child selector into `&gt;`; then the
						// template's rendered content, filtered by wp_kses().
						$content = $css . wp_kses( $post_content, ultimate_post()->rendered_content_allowed_html() );
					}
					wp_reset_postdata();
				}
			} elseif ( in_array( 'et-fb', $body_class ) ) {
					/* translators: %s: Navigation path to the Saved Templates page. */
					$content = '<p style="text-align:center;">' . sprintf( esc_html__( 'Pick a Template from your saved ones. Or create a template from: %s.', 'ultimate-post' ) . ' ', '<strong><i>' . esc_html__( 'Dashboard > PostX > Saved Templates', 'ultimate-post' ) . '</i></strong>' ) . '</p>';
			}

			// Render module content
			$output = sprintf(
				'<div class="ultp-shortcode" data-postid="%1$s">%2$s</div>',
				esc_attr( $templates ),
				et_sanitized_previously( $content )
			);

			return $this->_render_module_wrapper( $output, $render_slug );
		}
	}

	new PostX_Template_Module();
}

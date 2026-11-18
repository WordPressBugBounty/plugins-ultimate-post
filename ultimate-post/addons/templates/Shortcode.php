<?php
namespace ULTP;

defined( 'ABSPATH' ) || exit;

class Shortcode {
	/**
	 * Maximum number of templates expanded inside one another at the same time.
	 *
	 * Real layouts nest a template two or three levels deep at most. The limit only stops pathological
	 * chains of distinct templates; a template that embeds itself is caught by $expanding_templates first.
	 */
	const MAX_TEMPLATE_DEPTH = 5;

	/**
	 * Template IDs currently being expanded in this request, keyed by the (WPML-translated) ID.
	 * An entry is added before rendering and always removed in a finally block, so sequential reuses
	 * of one template on the same page are not mistaken for a cycle.
	 *
	 * @var array
	 */
	private static $expanding_templates = array();

	public function __construct() {
		add_shortcode( 'postx_template', array( $this, 'shortcode_callback' ) );
		// Deprecated name of [postx_template], kept only so pages saved with it keep rendering. The editor and
		// the dashboard only offer [postx_template].
		add_shortcode( 'gutenberg_post_blocks', array( $this, 'shortcode_callback' ) );
	}

	// Shortcode Callback
	function shortcode_callback( $atts = array(), $content = null ) {
		$atts = shortcode_atts(
			array(
				'id' => '',
			),
			$atts
		);

		$id = is_numeric( $atts['id'] ) ? (float) $atts['id'] : false;

		if ( $id ) {
			if ( class_exists( 'SitePress' ) ) {
				$id = apply_filters( 'wpml_object_id', $id, 'ultp_templates', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML hook.
			}
			$content      = '';
			$css_html     = '';
			$content_post = get_post( $id );
			// Only saved templates may be rendered. Publication alone does not make another post type (for example
			// a non-public CPT) safe to output through a shortcode.
			if ( $content_post && 'ultp_templates' === $content_post->post_type ) {
				if ( $content_post->post_status == 'publish' && $content_post->post_password == '' ) {
					// $id is already the WPML-translated ID, so the cycle check compares the IDs that are actually rendered.
					$expand_key = (int) $id;
					if ( isset( self::$expanding_templates[ $expand_key ] ) || count( self::$expanding_templates ) >= self::MAX_TEMPLATE_DEPTH ) {
						// This template is already being expanded (a self or A→B→A cycle), or the chain is too deep.
						return '';
					}
					self::$expanding_templates[ $expand_key ] = true;
					try {
						do_action(
							'ultp_enqueue_postx_block_css',
							array(
								'post_id' => $id,
								'css'     => '',
							)
						);
						// Breakdance builder support for its shortcode render
						$current_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
						if ( ! empty( $_GET['_breakdance_doing_ajax'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only Breakdance editor detection.
							strpos( $current_url, 'bricks/v1/render_element' ) !== false
						) {

							if ( ! empty( $_GET['_breakdance_doing_ajax'] ) && get_template() == 'bricks' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only Breakdance editor detection.
								get_header();
							}
							// WordPress's style printer writes the <style> element, so it is captured here and returned
							// ahead of the content, in the same place as before.
							$css_html = ultimate_post()->build_css_for_inline_print( $id, true, true );
						}

						$content .= $content_post->post_content;
						$content  = do_blocks( $content );
						$content  = do_shortcode( $content );
						$content  = str_replace( ']]>', ']]&gt;', $content );
						$content  = preg_replace( '%<p>&nbsp;\s*</p>%', '', $content );
						$content  = preg_replace( '/^(?:<br\s*\/?>\s*)+/', '', $content );

						// The template's CSS stays outside wp_kses(), which would turn the `>` of a child selector
						// into `&gt;`. It comes from build_css_for_inline_print(), which sanitises it with CSS_Sanitizer::ultp_esc_css().
						return $css_html . '<div class="ultp-shortcode" data-postid="' . esc_attr( $id ) . '">' . wp_kses( $content, ultimate_post()->rendered_content_allowed_html() ) . '</div>';
					} finally {
						// Runs on every exit, including an exception thrown while rendering, so the ID is never left on the stack.
						unset( self::$expanding_templates[ $expand_key ] );
					}
				}
			}
		}
		return '';
	}
}

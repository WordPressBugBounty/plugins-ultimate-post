<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// set_controls() writes $this->controls. If Bricks\Element does not declare it, PHP 8.2+ raises a
// "Creation of dynamic property" deprecation. The attribute suppresses that without redeclaring a parent
// property, because redeclaring a typed parent property would be a fatal error.
#[\AllowDynamicProperties]
class PostX_Bricks_Integration extends \Bricks\Element {
	// Element properties
	public $category = 'general';
	public $name     = 'postx-addon';
	public $icon     = 'ti-layout-grid2';

	// Return localised element label
	public function get_label() {
		return esc_html__( 'PostX Template', 'ultimate-post' );
	}

	// Set builder controls
	public function set_controls() {
		$this->controls['template']  = array(
			'type'        => 'select',
			'label'       => esc_html__( 'Select Template', 'ultimate-post' ),
			'options'     => ultimate_post()->get_all_lists( 'ultp_templates' ),
			'clearable'   => false,
			'default'     => '',
			'pasteStyles' => true,
			'inline'      => true,
		);
		$this->controls['separator'] = array(
			'type' => 'separator',
		);
		$this->controls['help']      = array(
			'type'    => 'info',
			'content' => 'Pick a Template from your saved ones. Or create a template from: <strong><i>Dashboard > PostX > Saved Templates</i></strong>',
		);
	}

	// Render element HTML
	public function render() {
		$id             = $this->settings['template'];
		$root_classes[] = 'bricks-postx-wrapper';
		$this->set_attribute( '_root', 'class', $root_classes );

		// Only read by the two strpos() tests below, never printed.
		$current_url = isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		// Render element HTML
		// Bricks builds the root attributes (its element id, classes and any custom attributes).
		echo wp_kses( '<div ' . $this->render_attributes( '_root' ) . '>', ultimate_post()->rendered_content_allowed_html() );
		if ( $id ) {
			ultimate_post()->print_inline_css( $id, true );
			echo '<div class="ultp-shortcode" data-postid="' . esc_attr( $id ) . '">';
				$args      = array(
					'p'         => $id,
					'post_type' => 'ultp_templates',
				);
				$the_query = new \WP_Query( $args );
				if ( $the_query->have_posts() ) {
					while ( $the_query->have_posts() ) {
						$the_query->the_post();
						the_content();
					}
					wp_reset_postdata();
				}
				echo '</div>';
		} elseif ( strpos( $current_url, 'bricks=run' ) !== false || strpos( $current_url, 'bricks/v1/render_element' ) !== false ) {
				/* translators: %s: Menu path to the Saved Templates screen. */
				echo '<p style="text-align:center; font-size: 20px;">' . sprintf( esc_html__( 'Pick a Template from your saved ones. Or create a template from: %s.', 'ultimate-post' ) . ' ', '<strong><i>' . esc_html__( 'Dashboard > PostX > Saved Templates', 'ultimate-post' ) . '</i></strong>' ) . '</p>';
		}
		echo '</div>';
	}
}

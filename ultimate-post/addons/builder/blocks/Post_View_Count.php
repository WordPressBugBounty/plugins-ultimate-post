<?php
namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Post_View_Count {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}
	public function get_attributes() {

		return array(
			'blockId'        => '',

			/*
			============================
				Post View Settings
			============================*/
			'viewLabel'      => true,
			'viewIconShow'   => true,
			/*
			============================
				Post View Label Style
			============================*/

			'viewLabelText'  => 'View',
			'viewLabelAlign' => 'after',
			/*
			============================
				Post View Icon Style
			============================*/
			'viewIconStyle'  => 'viewCount1',

			// --------------------------
			// Advanced Settings
			// --------------------------
			'advanceId'      => '',
			'advanceZindex'  => '',
			'hideExtraLarge' => false,
			'hideDesktop'    => false,
			'hideTablet'     => false,
			'hideMobile'     => false,
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/post-view-count',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr, $noAjax ) {
		$attr           = wp_parse_args( $attr, $this->get_attributes() );
		$block_name     = 'post-view-count';
		$wrapper_before = $wrapper_after = $content = '';

		$count = get_post_meta( get_the_ID(), '_ultp_post_views_count', true );

		$attr['className']     = isset( $attr['className'] ) && $attr['className'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
		$attr['align']         = isset( $attr['align'] ) && $attr['align'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
		$attr['advanceId']     = isset( $attr['advanceId'] ) ? sanitize_html_class( $attr['advanceId'] ) : '';
		$attr['blockId']       = isset( $attr['blockId'] ) ? sanitize_html_class( $attr['blockId'] ) : '';
		$attr['viewLabelText'] = wp_kses( $attr['viewLabelText'], ultimate_post()->ultp_allowed_html_tags() );

		$wrapper_before     .= '<div ' . ( $attr['advanceId'] ? 'id="' . esc_attr( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( $attr['blockId'] ) . ( $attr['className'] ? ' ' . esc_attr( $attr['className'] ) : '' ) . '' . ( $attr['align'] ? ' align' . esc_attr( $attr['align'] ) : '' ) . '">';
			$wrapper_before .= '<div class="ultp-block-wrapper">';
				$content    .= '<span class="ultp-view-count">';
		if ( $attr['viewIconShow'] && $attr['viewIconStyle'] ) {
			$content .= wp_kses( ultimate_post()->get_svg_icon( $attr['viewIconStyle'] ), ultimate_post()->rendered_content_allowed_html() );
		}
					$content     .= '<span class="ultp-view-count-number">';
						$content .= esc_html( $count ? $count : 0 );
					$content     .= '</span>';
		if ( $attr['viewLabel'] ) {
			$content .= '<span class="ultp-view-label"> ' . wp_kses( $attr['viewLabelText'], ultimate_post()->ultp_allowed_html_tags() ) . '</span>';
		}
				$content   .= '</span>';
			$wrapper_after .= '</div>';
		$wrapper_after     .= '</div>';

		return $wrapper_before . $content . $wrapper_after;
	}
}

<?php
namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Post_Author_Meta {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}
	public function get_attributes() {

		return array(
			'blockId'           => '',

			/*
			============================
				Post Author Meta  Settings
			============================*/
			'authMetAvatar'     => true,
			'authMetaIconShow'  => false,

			/*
			============================
				Post Author Avatar Style
			============================*/
			'authMetaLabel'     => true,

			/*
			============================
				Post Author Icon Style
			============================*/
			'authMetaIconStyle' => 'author1',

			/*
			============================
				Post Author Label Style
			============================*/
			'authMetaLabelText' => 'By',

			/*
			============================
				Advance Settings
			============================*/
			'advanceId'         => '',
			'advanceZindex'     => '',
			'hideExtraLarge'    => false,
			'hideDesktop'       => false,
			'hideTablet'        => false,
			'hideMobile'        => false,
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/post-author-meta',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr, $noAjax ) {
		$attr           = wp_parse_args( $attr, $this->get_attributes() );
		$block_name     = 'post-author-meta';
		$wrapper_before = $wrapper_after = $content = '';
		$author_id      = get_post_field( 'post_author', get_the_ID() );

		$attr['className']         = isset( $attr['className'] ) && $attr['className'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
		$attr['align']             = isset( $attr['align'] ) && $attr['align'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
		$attr['advanceId']         = isset( $attr['advanceId'] ) ? sanitize_html_class( $attr['advanceId'] ) : '';
		$attr['blockId']           = isset( $attr['blockId'] ) ? sanitize_html_class( $attr['blockId'] ) : '';
		$attr['authMetaLabelText'] = wp_kses( $attr['authMetaLabelText'], ultimate_post()->ultp_allowed_html_tags() );

		if ( $author_id ) {
			$wrapper_before     .= '<div ' . ( $attr['advanceId'] ? 'id="' . esc_attr( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( $attr['blockId'] ) . ( $attr['className'] ? ' ' . esc_attr( $attr['className'] ) : '' ) . '' . ( $attr['align'] ? ' align' . esc_attr( $attr['align'] ) : '' ) . '">';
				$wrapper_before .= '<div class="ultp-block-wrapper">';
					$content    .= '<span class="ultp-authMeta-count">';
			if ( $attr['authMetaIconShow'] && ( $attr['authMetaIconStyle'] != '' ) ) {
				$content .= wp_kses( ultimate_post()->get_svg_icon( $attr['authMetaIconStyle'] ), ultimate_post()->rendered_content_allowed_html() );
			}
			if ( $attr['authMetAvatar'] ) {
				$content     .= '<div class="ultp-authMeta-avatar">';
					$content .= wp_kses( get_avatar( $author_id, 32 ), ultimate_post()->rendered_content_allowed_html() );
				$content     .= '</div>';
			}
			if ( $attr['authMetaLabel'] ) {
				$content .= '<span class="ultp-authMeta-label">' . wp_kses( $attr['authMetaLabelText'], ultimate_post()->ultp_allowed_html_tags() ) . '</span>';
			}
						$content     .= '<a class="ultp-authMeta-name" href="' . esc_url( get_author_posts_url( $author_id ) ) . '">';
							$content .= esc_html( get_the_author_meta( 'display_name', $author_id ) );
						$content     .= '</a>';
					$content         .= '</span>';
				$wrapper_after       .= '</div>';
			$wrapper_after           .= '</div>';
		}

		return $wrapper_before . $content . $wrapper_after;
	}
}

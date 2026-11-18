<?php
namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Post_Featured_Image {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}
	public function get_attributes() {

		return array(
			'blockId'            => '',
			/*
			============================
				Post Featured Image Setting
			============================*/
			'defImgShow'         => false,
			'altText'            => 'Image',
			'imgCrop'            => 'full',
			'imgSrcset'          => false,

			/*
			============================
				Dynamic Caption
			============================*/
			'enableCaption'      => false,

			/*
			============================
				Video Settings
			============================*/
			'enableVideoCaption' => false,
			'videoWidth'         => (object) array( 'lg' => '100' ),

			/*
			============================
				Advanced Settings
			============================*/
			'advanceId'          => '',
			'advanceZindex'      => '',
			'hideExtraLarge'     => false,
			'hideDesktop'        => false,
			'hideTablet'         => false,
			'hideMobile'         => false,
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/post-featured-image',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr, $noAjax ) {
		$attr           = wp_parse_args( $attr, $this->get_attributes() );
		$block_name     = 'post-image';
		$wrapper_before = $wrapper_after = $content = '';

		$attr['className'] = isset( $attr['className'] ) && $attr['className'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
		$attr['align']     = isset( $attr['align'] ) && $attr['align'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
		$attr['advanceId'] = isset( $attr['advanceId'] ) ? sanitize_html_class( $attr['advanceId'] ) : '';
		$attr['blockId']   = isset( $attr['blockId'] ) ? sanitize_html_class( $attr['blockId'] ) : '';
		$attr['altText']   = wp_kses( $attr['altText'], ultimate_post()->ultp_allowed_html_tags() );

		$post_video = get_post_meta( get_the_ID(), '_ultp_builder_feature_video', true );
		$caption    = get_post_meta( get_the_ID(), '_ultp_builder_feature_caption', true );

		$embeded       = $post_video ? ultimate_post()->get_embeded_video( $post_video, false, true, false, true, true, false, true, array( 'width' => array( 'width' => $attr['videoWidth'] ) ), 'from-block' ) : '';
		$post_thumb_id = get_post_thumbnail_id( get_the_ID() );
		$img_content   = ultimate_post()->get_image( $post_thumb_id, $attr['imgCrop'], '', $attr['altText'], $attr['imgSrcset'] );
		$img_caption   = wp_get_attachment_caption( $post_thumb_id );

		if ( $embeded || has_post_thumbnail() ) {
			$isvideo                     = $embeded ? ( isset( $attr['defImgShow'] ) && $attr['defImgShow'] && $post_thumb_id ? false : true ) : false;
			// Extra class and closing markup of the media box; an add-on may supply them.
			$media_class = apply_filters( 'ultp_post_image_media_class', '', $attr, array( 'is_video' => $isvideo ) );
			$media_class = is_string( $media_class ) ? esc_attr( $media_class ) : '';
			$media_after = apply_filters( 'ultp_post_image_media_after', '', $attr, array( 'is_video' => $isvideo ) );
			$media_after = is_string( $media_after ) ? wp_kses_post( $media_after ) : '';
			$wrapper_before             .= '<div ' . ( $attr['advanceId'] ? 'id="' . esc_attr( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( $attr['blockId'] ) . ( $attr['className'] ? ' ' . esc_attr( $attr['className'] ) : '' ) . '' . ( $attr['align'] ? ' align' . esc_attr( $attr['align'] ) : '' ) . '">';
				$wrapper_before         .= '<div class="ultp-block-wrapper">';
					$wrapper_before     .= '<div class="ultp-image-wrapper">';
						$wrapper_before .= '<div  class="ultp-builder-' . ( $isvideo ? 'video' : 'image' ) . '">';
							$content    .= '<div class="ultp-' . ( $isvideo ? 'video' : 'image' ) . '-block' . $media_class . '">';
							$content    .= wp_kses( $isvideo ? $embeded : $img_content, ultimate_post()->rendered_content_allowed_html() );
						$wrapper_after  .= $media_after . '</div>';
						$wrapper_after  .= '</div>';
					$wrapper_after      .= '</div>';

			if ( $attr['enableCaption'] && $img_caption || $caption && $attr['enableVideoCaption'] ) {
				$wrapper_after .= '<div class="ultp-featureImg-caption">';
				$wrapper_after .= wp_kses_post( $isvideo ? $caption : $img_caption );
				$wrapper_after .= '</div>';
			}
				$wrapper_after .= '</div>';
			$wrapper_after     .= '</div>';
		}
		return $wrapper_before . $content . $wrapper_after;
	}
}

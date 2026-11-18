<?php

namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Youtube_Gallery {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function get_attributes() {

		return array(
			'blockId'                   => '',
			'previewImg'                => '',
			'advanceId'                 => '',
			'className'                 => '',
			'playlistIdOrUrl'           => 'PLPidnGLSR4qcAwVwIjMo1OVaqXqjUp_s4',
			'youTubeApiKey'             => '',
			'layout'             => 'inline',
			'videosPerPage'             => (object) array( 'lg' => 6 ),
			'showVideoTitle'            => true,
			'videoTitleLength'          => (object) array( 'lg' => 50, 'md' => 50, 'sm' => 50 ),
			'loadMoreEnable'            => true,
			'moreButtonLabel'           => 'More Videos',
			'autoplay'                  => true,
			'autoplayPlaylist'          => false,
			'loop'                      => false,
			'mute'                      => false,
			'showPlayerControl'         => true,
			'hideYoutubeLogo'           => false,
			'showDescription'           => false,
			'videoDescriptionLength'    => (object) array( 'lg' => 100, 'md' => 100, 'sm' => 100 ),
			'imageHeightRatio'          => 'maxres',
			'galleryColumn'             => (object) array( 'lg' => 3, 'md' => 2, 'sm' => 1 ),
			'displayType'               => 'grid',
			'enableListView'            => false,
			'playIcon'                  => 'youtube_logo_icon_solid',
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/youtube-gallery',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			'ultp-youtube-gallery-block',
			ULTP_URL . 'assets/js/ultp-youtube-gallery-block.js',
			array( 'jquery' ),
			ULTP_VER,
			true
		);
	}

	public function content( $attr, $noAjax ) {
		$attr = wp_parse_args( $attr, $this->get_attributes() );
		$content = '';
		$attr['className']      = isset( $attr['className'] ) && $attr['className'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
		$attr['align']          = isset( $attr['align'] ) && $attr['align'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
		$attr['advanceId']      = isset( $attr['advanceId'] ) ? sanitize_html_class( $attr['advanceId'] ) : '';
		$attr['blockId']        = isset( $attr['blockId'] ) ? sanitize_html_class( $attr['blockId'] ) : '';

		$block_class = 'ultp-post-grid-block  ultp-block-' . $attr['blockId'];
		if ( $attr['align'] ) {
			$block_class .= ' align' . esc_attr( $attr['align'] );
		}
		if ( $attr['className'] ) {
			$block_class .= ' ' . esc_attr( $attr['className'] );
		}

		$videos_per_page = isset( $attr['videosPerPage'] ) ? $attr['videosPerPage'] : array( 'lg' => 9, 'md' => 6, 'sm' => 3 );
		$video_title_length = isset( $attr['videoTitleLength'] ) ? $attr['videoTitleLength'] : array( 'lg' => 50, 'md' => 50, 'sm' => 50 );
		$video_description_length = isset( $attr['videoDescriptionLength'] ) ? $attr['videoDescriptionLength'] : array( 'lg' => 100, 'md' => 100, 'sm' => 100 );
		$gallery_column = isset( $attr['galleryColumn'] ) ? $attr['galleryColumn'] : array( 'lg' => 3, 'md' => 2, 'sm' => 1 );

		$block_name = 'youtube-gallery';

		// The layout is inline unless ultp_youtube_gallery_layout gives another.
		$attr['layout'] = apply_filters( 'ultp_youtube_gallery_layout', 'inline', $attr );

		// Data attributes read by the gallery script, in order. Values are escaped here; the
		// ultp_youtube_gallery_data filter may change or add entries (escaping its own values).
		$data_attributes = array(
			'playlist'                 => esc_attr( $attr['playlistIdOrUrl'] ),
			'api-key'                  => esc_attr( $attr['youTubeApiKey'] ),
			'gallery-layout'           => esc_attr( $attr['layout'] ),
			'videos-per-page'          => esc_attr( wp_json_encode( $videos_per_page ) ),
			'show-video-title'         => ( $attr['showVideoTitle'] ? '1' : '0' ),
			'video-title-length'       => esc_attr( wp_json_encode( $video_title_length ) ),
			'load-more-enable'         => ( $attr['loadMoreEnable'] ? '1' : '0' ),
			'more-button-label'        => esc_attr( $attr['moreButtonLabel'] ),
			'autoplay'                 => ( $attr['autoplay'] ? '1' : '0' ),
			'loop'                     => ( $attr['loop'] ? '1' : '0' ),
			'mute'                     => ( $attr['mute'] ? '1' : '0' ),
			'show-player-control'      => ( $attr['showPlayerControl'] ? '1' : '0' ),
			'hide-youtube-logo'        => ( $attr['hideYoutubeLogo'] ? '1' : '0' ),
			'show-description'         => ( $attr['showDescription'] ? '1' : '0' ),
			'video-description-length' => esc_attr( wp_json_encode( $video_description_length ) ),
			'image-height-ratio'       => esc_attr( $attr['imageHeightRatio'] ),
			'gallery-column'           => esc_attr( wp_json_encode( $gallery_column ) ),
			'display-type'             => esc_attr( $attr['displayType'] ),
			'enable-list-view'         => ( $attr['enableListView'] ? '1' : '0' ),
			'img-height'               => ( $attr['imageHeightRatio'] ),
		);
		$data_attributes = apply_filters( 'ultp_youtube_gallery_data', $data_attributes, $attr );

		$content .= '<div '. ( $attr['advanceId'] ? 'id="' . esc_attr( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( $attr['blockId'] ) . '' . ( $attr['align'] ? ' align' . esc_attr( $attr['align'] ) : '' ) . '' . ( $attr['className'] ? ' ' . esc_attr( $attr['className'] ) : '' ) . '"';
		foreach ( $data_attributes as $data_key => $data_value ) {
			if ( ! is_string( $data_key ) || ! is_scalar( $data_value ) ) {
				continue;
			}
			$content .= ' data-' . sanitize_key( $data_key ) . '="' . esc_attr( $data_value ) . '"';
		}
		$content .= '>';

		$grid_class = 'ultp-ytg-view-grid ultp-layout-' . esc_attr( $attr['layout'] );
		if ( $attr['enableListView'] ) {
			$grid_class .= ' ultp-ytg-view-list';
		}
		if ( $attr['imageHeightRatio'] !== 'custom' ) {
			$grid_class .= ' ultp-ratio-height';
		}
		$grid_class .= ' ultp-ytg-' . esc_attr( $attr['displayType'] );

		$content .= '<div class="ultp-block-wrapper ultp-ytg-block ultp-wrapper-block">';
		$content .= '<div class="ultp-ytg-container ' . esc_attr( $grid_class ) . '">';
		$content .= '<div class=""></div>';
		$content .= '</div>';
		// The "load more" button when loadMoreEnable is on. The ultp_youtube_gallery_load_more_html filter can replace it.
		$load_more_default = '';
		if ( $attr['loadMoreEnable'] ) {
			$load_more_default = '<div class="ultp-ytg-loadmore-btn">' . esc_html( $attr['moreButtonLabel'] ) . '</div>';
		}
		$content .= ultimate_post()->esc_filtered_markup( apply_filters( 'ultp_youtube_gallery_load_more_html', $load_more_default, $attr ), $load_more_default );
		if($attr['playIcon']) {
			$content .= '<div class="ultp-ytg-play__icon" style="display:none;" >';
			// The YouTube logo. The ultp_youtube_gallery_play_icon filter can replace it.
			$default_icon = '<svg
										xmlns="http://www.w3.org/2000/svg"
										viewBox="0 0 28.57 20"
										preserveAspectRatio="xMidYMid meet"
									>
										<g>
											<path
												d="M27.9727 3.12324C27.6435 1.89323 26.6768 0.926623 25.4468 0.597366C23.2197 2.24288e-07 14.285 0 14.285 0C14.285 0 5.35042 2.24288e-07 3.12323 0.597366C1.89323 0.926623 0.926623 1.89323 0.597366 3.12324C2.24288e-07 5.35042 0 10 0 10C0 10 2.24288e-07 14.6496 0.597366 16.8768C0.926623 18.1068 1.89323 19.0734 3.12323 19.4026C5.35042 20 14.285 20 14.285 20C14.285 20 23.2197 20 25.4468 19.4026C26.6768 19.0734 27.6435 18.1068 27.9727 16.8768C28.5701 14.6496 28.5701 10 28.5701 10C28.5701 10 28.5677 5.35042 27.9727 3.12324Z"
												fill="#FF0000"
											/>
											<path
												d="M11.4253 14.2854L18.8477 10.0004L11.4253 5.71533V14.2854Z"
												fill="white"
											/>
										</g>
									</svg>';
			$content .= wp_kses( ultimate_post()->esc_filtered_markup( apply_filters( 'ultp_youtube_gallery_play_icon', $default_icon, $attr ), $default_icon ), ultimate_post()->rendered_content_allowed_html() );
			$content .= '</div>';
		}
		
		$content .= '</div>';
		$content .= '</div>';
		return $content;
	}
}

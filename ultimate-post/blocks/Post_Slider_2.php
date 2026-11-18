<?php

namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Post_Slider_2 {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {

		return array(
			'blockId'                   => '',
			'previewImg'                => '',
			// General Setting
			'slidesToShow'              => (object) array(
				'lg' => '1',
				'sm' => '1',
				'xs' => '1',
			),
			'autoPlay'                  => true,
			'height'                    => (object) array(
				'lg'   => '550',
				'unit' => 'px',
			),
			'slidesCenterPadding'       => (object) array(
				'lg' => '160',
				'sm' => '100',
				'xs' => '50',
			),
			'slidesTopPadding'          => '0',
			'allItemScale'              => (object) array( 'lg' => '.9' ),
			'centerItemScale'           => (object) array( 'lg' => '1.12' ),
			'slideSpeed'                => '3000',
			'sliderGap'                 => '',
			'dots'                      => true,
			'arrows'                    => true,
			'preLoader'                 => false,
			'fade'                      => false,
			'titleShow'                 => true,
			'titleStyle'                => 'none',
			'headingShow'               => false,
			'excerptShow'               => false,
			'catShow'                   => true,
			'metaShow'                  => true,
			'readMore'                  => false,
			'contentTag'                => 'div',
			'openInTab'                 => false,
			'notFoundMessage'           => 'No Post Found',
			// Query Setting
			'layout'                    => 'slide1',
			'queryQuick'                => '',
			'queryNumPosts'             => (object) array( 'lg' => 5 ),
			'queryNumber'               => 5,
			'queryType'                 => 'post',
			'queryTax'                  => 'category',
			'queryTaxValue'             => '[]',
			'queryRelation'             => 'OR',
			'queryOrderBy'              => 'date',
			'metaKey'                   => 'custom_meta_key',
			'queryOrder'                => 'desc',
			// Include Remove from Version 2.5.4
			'queryInclude'              => '',
			'queryExclude'              => '[]',
			'queryAuthor'               => '[]',
			'queryOffset'               => '0',
			'querySticky'               => true,
			// Arrow Style
			'arrowStyle'                => 'leftAngle2#rightAngle2',

			// Heading Style
			'headingText'               => 'Post Slider #1',
			'headingURL'                => '',
			'headingBtnText'            => 'View More',
			'headingStyle'              => 'style1',
			'headingTag'                => 'h2',
			'headingAlign'              => 'left',
			'subHeadingShow'            => false,
			'subHeadingText'            => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ut sem augue. Sed at felis ut enim dignissim sodales.',
			'contentVerticalPosition'   => 'bottomPosition',
			'contentHorizontalPosition' => 'centerPosition',
			'slideBgBlur'               => '',

			// Image Style
			'imageShow'                 => true,
			'imgCrop'                   => 'full',
			'imgOverlay'                => false,
			'imgBgbrightness'           => '',
			'imgBgBlur'                 => '',
			'imgOverlayType'            => 'default',
			'fallbackEnable'            => true,
			'fallbackImg'               => '',

			// Read more Setting/Style
			'readMoreText'              => '',
			'readMoreIcon'              => 'rightArrowLg',

			// Title Setting/Style
			'titleTag'                  => 'h3',
			'titlePosition'             => true,
			'titleLength'               => 0,

			// Meta Setting/Style
			'metaPosition'              => 'top',
			'metaStyle'                 => 'noIcon',
			'authorLink'                => true,
			'metaSeparator'             => 'dot',
			'metaList'                  => '["metaAuthor","metaDate"]',
			'metaMinText'               => 'min read',
			'metaAuthorPrefix'          => '',
			'metaDateFormat'            => 'j M Y',

			// Category Setting/Style
			'maxTaxonomy'               => '30',
			'taxonomy'                  => 'category',
			'catStyle'                  => 'classic',
			'catPosition'               => 'aboveTitle',
			'seperatorLink'             => admin_url( 'edit-tags.php?taxonomy=category' ),

			// Excerpt Style
			'showSeoMeta'               => false,
			'showFullExcerpt'           => false,
			'excerptLimit'              => 40,

			// Wrapper Style
			'advanceId'                 => '',
			'advanceZindex'             => '',
			'hideExtraLarge'            => false,
			'hideTablet'                => false,
			'hideMobile'                => false,

			/*
			============================
				Dynamic Content
			============================*/
			'dcEnabled'                 => false,
			'dcFields'                  => array(),
			'dcSize'                    => 8,
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/post-slider-2',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr, $noAjax ) {
		$attr = wp_parse_args( $attr, $this->get_attributes() );

		if ( ! $noAjax ) {
			$paged         = is_front_page() ? get_query_var( 'page' ) : get_query_var( 'paged' );
			$attr['paged'] = $paged ? $paged : 1;
		}

		$block_name          = 'post-slider-2';
		$img_opts            = ultimate_post()->image_options( $attr, $block_name );
		$wraper_before       = $wraper_after = $post_loop = '';
		$attr['queryNumber'] = ultimate_post()->get_post_number( 5, $attr['queryNumber'], $attr['queryNumPosts'] );
		$recent_posts        = new \WP_Query( ultimate_post()->get_query( $attr ) );
		$pageNum             = ultimate_post()->get_page_number( $attr, $recent_posts->found_posts );
		// Dummy Img Url
		$dummy_url = ULTP_URL . 'assets/img/ultp-fallback-img.png';

		$slides = is_object( $attr['slidesToShow'] ) ? json_decode( wp_json_encode( $attr['slidesToShow'] ), true ) : $attr['slidesToShow'];

		$centerPadd = is_object( $attr['slidesCenterPadding'] ) ? json_decode( wp_json_encode( $attr['slidesCenterPadding'] ), true ) : $attr['slidesCenterPadding'];
		if ( $recent_posts->have_posts() ) {
			$attr['className']                 = isset( $attr['className'] ) && $attr['className'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
			$attr['align']                     = isset( $attr['align'] ) && $attr['align'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
			$attr['advanceId']                 = isset( $attr['advanceId'] ) ? sanitize_html_class( $attr['advanceId'] ) : '';
			$attr['blockId']                   = isset( $attr['blockId'] ) ? sanitize_html_class( $attr['blockId'] ) : '';
			$attr['contentTag']                = in_array( $attr['contentTag'], ultimate_post()->ultp_allowed_block_tags() ) ? $attr['contentTag'] : 'div';
			$attr['layout']                    = sanitize_html_class( $attr['layout'] );
			// Layout name, used in the wrapper class and in data-layout. The default is slide1; another name can be set through ultp_post_slider_2_layout_class.
			$layout = apply_filters( 'ultp_post_slider_2_layout_class', 'slide1', $attr );
			$layout = sanitize_html_class( $layout );
			// Extra data attributes of the wrapper, added through ultp_post_slider_2_items_data. The default is none.
			$items_data = apply_filters( 'ultp_post_slider_2_items_data', array(), $attr, $layout );
			$items_html = '';
			foreach ( $items_data as $data_key => $data_value ) {
				$data_key = sanitize_key( (string) $data_key );
				if ( '' !== $data_key && is_scalar( $data_value ) ) {
					$items_html .= ' data-' . $data_key . '="' . esc_attr( (string) $data_value ) . '"';
				}
			}
			$attr['imgOverlayType']          = sanitize_html_class( $attr['imgOverlayType'] );
			$attr['slideSpeed']                = sanitize_html_class( $attr['slideSpeed'] );
			$attr['arrows']                    = $attr['arrows'] == true;
			$attr['dots']                      = $attr['dots'] == true;
			$attr['autoPlay']                  = $attr['autoPlay'] == true;
			$attr['fade']                      = $attr['fade'] == true;
			$attr['readMoreText']              = wp_kses( $attr['readMoreText'], ultimate_post()->ultp_allowed_html_tags() );
			$attr['contentVerticalPosition']   = sanitize_html_class( $attr['contentVerticalPosition'] );
			$attr['contentHorizontalPosition'] = sanitize_html_class( $attr['contentHorizontalPosition'] );

			$wraper_before .= '<div ' . ( $attr['advanceId'] ? 'id="' . esc_attr( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( $attr['blockId'] ) . '' . ( $attr['align'] ? ' align' . esc_attr( $attr['align'] ) : '' ) . '' . ( $attr['className'] ? ' ' . esc_attr( $attr['className'] ) : '' ) . '">';
			$wraper_before .= '<div class="ultp-block-wrapper">';
			if ( $attr['headingShow'] ) {
				$wraper_before .= '<div class="ultp-heading-filter">';
				$wraper_before .= '<div class="ultp-heading-filter-in">';
				include ULTP_PATH . 'blocks/template/heading.php';
				$wraper_before .= '</div>';
				$wraper_before .= '</div>';
			}
			$wraper_before .= '<div class="ultp-block-items-wrap ultp-slide-' . esc_attr( $layout ) . '" data-layout="' . esc_attr( $layout ) . '"' . $items_html . '  data-arrows="' . esc_attr( $attr['arrows'] ) . '" data-dots="' . esc_attr( $attr['dots'] ) . '" data-autoplay="' . esc_attr( $attr['autoPlay'] ) . '" data-slidespeed="' . esc_attr( $attr['slideSpeed'] ) . '" data-fade="' . esc_attr( $attr['fade'] ) . '" data-slidelg="' . ( isset( $slides['lg'] ) ? esc_attr( sanitize_html_class( $slides['lg'] ) ) : 1 ) . '" data-slidesm="' . ( isset( $slides['sm'] ) ? esc_attr( sanitize_html_class( $slides['sm'] ) ) : 1 ) . '" data-slidexs="' . ( isset( $slides['xs'] ) ? esc_attr( sanitize_html_class( $slides['xs'] ) ) : 1 ) . '" 
                        data-paddlg="' . ( isset( $centerPadd['lg'] ) ? esc_attr( sanitize_html_class( $centerPadd['lg'] ) ) : 100 ) . '" data-paddsm="' . ( isset( $centerPadd['sm'] ) ? esc_attr( sanitize_html_class( $centerPadd['sm'] ) ) : 100 ) . '" data-paddxs="' . ( isset( $centerPadd['xs'] ) ? esc_attr( sanitize_html_class( $centerPadd['xs'] ) ) : 50 ) . '">';
			$idx            = $noAjax ? 1 : 0;
			while ( $recent_posts->have_posts() ) :
				$recent_posts->the_post();

				$dcContent = array_fill( 0, $attr['dcSize'], '' );

				if ( ultimate_post()->is_dc_active( $attr ) ) {
					$dcContent = \ULTP\DCService::get_dc_content_for_block( $attr, $dcContent );
				}

				include ULTP_PATH . 'blocks/template/data.php';

				// Lets an add-on note each post the block shows.
				do_action( 'ultp_block_loop_post', $post_id, $attr );

				$post_loop .= '<' . tag_escape( $attr['contentTag'] ) . ' class="ultp-block-item post-id-' . esc_attr( $post_id ) . '">';
				if ( $attr['preLoader'] ) {
					$post_loop .= '<div class="ultp-post-slider-loader-container">';
					$post_loop .= wp_kses( ultimate_post()->postx_loading(), ultimate_post()->rendered_content_allowed_html() );
					$post_loop .= '</div>';
				}
				$post_loop .= '<div>';
				$post_loop .= '<div class="ultp-block-slider-wrap">';

				$post_loop .= '<div class="ultp-block-image-inner">';
				if ( $post_thumb_id || $attr['fallbackEnable'] ) {
					$post_loop .= '<div class="ultp-block-image ' . ( $attr['imgOverlay'] ? ' ultp-block-image-overlay ultp-block-image-' . esc_attr( $attr['imgOverlayType'] ) . ' ultp-block-image-' . esc_attr( $attr['imgOverlayType'] ) . esc_attr( $idx ) : '' ) . '">';
					$post_loop .= '<a href="' . esc_url( $titlelink ) . '" ' . ( $attr['openInTab'] ? 'target="_blank"' : '' ) . '>';
					// Post Image Id
					$block_img_id = $post_thumb_id ? $post_thumb_id : ( $attr['fallbackEnable'] && isset( $attr['fallbackImg']['id'] ) ? $attr['fallbackImg']['id'] : '' );
					// Post Image
					if ( $post_thumb_id || ( $attr['fallbackEnable'] && $block_img_id ) ) {
						$post_loop .= wp_kses( ultimate_post()->get_image( $block_img_id, $attr['imgCrop'], '', $title, $img_opts['srcset'], $img_opts['lazy'] ), ultimate_post()->rendered_content_allowed_html() );
					} else {
						$post_loop .= '<img  src="' . esc_url( $dummy_url ) . '" alt="dummy-img" />';
					}
					$post_loop .= '</a></div>'; // .ultp-block-image
				}
				$post_loop .= '</div>'; // .ultp-block-image-inner
				$post_loop .= '<div class="ultp-block-content ultp-block-content-' . esc_attr( $attr['contentVerticalPosition'] ) . ' ultp-block-content-' . esc_attr( $attr['contentHorizontalPosition'] ) . '">';
				$post_loop .= '<div class="ultp-block-content-inner">';

				$post_loop .= wp_kses( $dcContent[7], ultimate_post()->rendered_content_allowed_html() );

				include ULTP_PATH . 'blocks/template/category.php';
				$post_loop .= wp_kses( $category, ultimate_post()->rendered_content_allowed_html() );

				$post_loop .= wp_kses( $dcContent[6], ultimate_post()->rendered_content_allowed_html() );

				if ( $title && $attr['titleShow'] && $attr['titlePosition'] ) {
					include ULTP_PATH . 'blocks/template/title.php';
				}

				$post_loop .= wp_kses( $dcContent[5], ultimate_post()->rendered_content_allowed_html() );

				if ( $attr['metaPosition'] == 'top' ) {
					include ULTP_PATH . 'blocks/template/meta.php';
				}

				$post_loop .= wp_kses( $dcContent[4], ultimate_post()->rendered_content_allowed_html() );

				if ( $title && $attr['titleShow'] && ! $attr['titlePosition'] ) {
					include ULTP_PATH . 'blocks/template/title.php';
				}

				$post_loop .= wp_kses( $dcContent[3], ultimate_post()->rendered_content_allowed_html() );

				if ( $attr['excerptShow'] ) {
					$post_loop .= '<div class="ultp-block-excerpt">' . wp_kses_post( ultimate_post()->get_excerpt( $post_id, $attr['showSeoMeta'], $attr['showFullExcerpt'], $attr['excerptLimit'] ) ) . '</div>';
				}

				$post_loop .= wp_kses( $dcContent[2], ultimate_post()->rendered_content_allowed_html() );

				if ( $attr['readMore'] ) {
					$post_loop .= '<div class="ultp-block-readmore"><a aria-label="' . esc_attr( $title ) . '" href="' . esc_url( $titlelink ) . '" ' . ( $attr['openInTab'] ? 'target="_blank"' : '' ) . '>' . ( $attr['readMoreText'] ? wp_kses( $attr['readMoreText'], ultimate_post()->ultp_allowed_html_tags() ) : esc_html__( 'Read More', 'ultimate-post' ) ) . wp_kses( ultimate_post()->get_svg_icon( $attr['readMoreIcon'] ), ultimate_post()->rendered_content_allowed_html() ) . '</a></div>';
				}

				$post_loop .= wp_kses( $dcContent[1], ultimate_post()->rendered_content_allowed_html() );

				if ( $attr['metaPosition'] == 'bottom' ) {
					include ULTP_PATH . 'blocks/template/meta.php';
				}

				$post_loop .= wp_kses( $dcContent[0], ultimate_post()->rendered_content_allowed_html() );

				$post_loop .= '</div>';
				$post_loop .= '</div>';

				$post_loop .= '</div>';
				$post_loop .= '</div>';
				$post_loop .= '</' . tag_escape( $attr['contentTag'] ) . '>';
			endwhile;
			$wraper_after .= '</div>';

			if ( $attr['arrows'] ) {
				$wraper_after .= '<div class="ultp-slick-nav" style="display:none">';
				$nav           = explode( '#', $attr['arrowStyle'] );
				$wraper_after .= '<div class="ultp-slick-prev"><div class="slick-arrow slick-prev">' . wp_kses( ultimate_post()->get_svg_icon( $nav[0] ), ultimate_post()->rendered_content_allowed_html() ) . '</div></div>';
				$wraper_after .= '<div class="ultp-slick-next"><div class="slick-arrow slick-next">' . wp_kses( ultimate_post()->get_svg_icon( $nav[1] ), ultimate_post()->rendered_content_allowed_html() ) . '</div></div>';
				$wraper_after .= '</div>';
			}
			$wraper_after .= '</div>';
			$wraper_after .= '</div>';
			wp_reset_postdata();
		} else {
			$wraper_before .= wp_kses( ultimate_post()->get_no_result_found_html( $attr['notFoundMessage'] ), ultimate_post()->rendered_content_allowed_html() );
		}

		return $noAjax ? $post_loop : $wraper_before . $post_loop . $wraper_after;
	}
}

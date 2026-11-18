<?php
namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Post_Slider_1 {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {

		return array(
			'blockId'                   => '',
			'previewImg'                => '',
			// --------------------------
			// Query Setting
			// --------------------------
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
			// --------------------------
			// General Setting
			// --------------------------
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
			'slideSpeed'                => '3000',
			'sliderGap'                 => '10',
			'dots'                      => true,
			'arrows'                    => true,
			'preLoader'                 => false,
			'fade'                      => true,
			'titleShow'                 => true,
			'titleStyle'                => 'none',
			'headingShow'               => false,
			'excerptShow'               => true,
			'catShow'                   => true,
			'metaShow'                  => true,
			'readMore'                  => true,
			'contentTag'                => 'div',
			'openInTab'                 => false,
			'notFoundMessage'           => 'No Post Found',

			// --------------------------
			// Heading Setting/Style
			// --------------------------
			'headingText'               => 'Post Slider #1',
			'headingURL'                => '',
			'headingBtnText'            => 'View More',
			'headingStyle'              => 'style1',
			'headingTag'                => 'h2',
			'headingAlign'              => 'left',
			'subHeadingShow'            => false,
			'subHeadingText'            => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ut sem augue. Sed at felis ut enim dignissim sodales.',

			// --------------------------
			// Title Setting/Style
			// --------------------------
			'titleTag'                  => 'h3',
			'titlePosition'             => true,
			'titleLength'               => 0,
			// 'titleBackground' => '',

			// --------------------------
			// Content Setting/Style
			// --------------------------
			'showSeoMeta'               => false,
			'showFullExcerpt'           => false,
			'excerptLimit'              => 40,

			// --------------------------
			// Content Wrap Setting/Style
			// --------------------------
			'contentVerticalPosition'   => 'middlePosition',
			'contentHorizontalPosition' => 'centerPosition',

			// --------------------------
			// Arrow Setting/Style
			// --------------------------
			'arrowStyle'                => 'leftAngle2#rightAngle2',

			// --------------------------
			// Category Setting/Style
			// --------------------------
			'maxTaxonomy'               => '30',
			'taxonomy'                  => 'category',
			'catStyle'                  => 'classic',
			'catPosition'               => 'aboveTitle',
			'seperatorLink'             => admin_url( 'edit-tags.php?taxonomy=category' ),

			// --------------------------
			// Image Style
			// --------------------------
			'imageShow'                 => true,
			'imgCrop'                   => 'full',
			'imgOverlay'                => false,
			'imgOverlayType'            => 'default',
			'fallbackEnable'            => true,
			'fallbackImg'               => '',

			// --------------------------
			// Read more Setting/Style
			// --------------------------
			'readMoreText'              => '',
			'readMoreIcon'              => 'rightArrowLg',

			// --------------------------
			// Meta Setting/Style
			// --------------------------
			'metaPosition'              => 'top',
			'metaStyle'                 => 'icon',
			'authorLink'                => true,
			'metaSeparator'             => 'dash',
			'metaList'                  => '["metaAuthor","metaDate","metaRead"]',
			'metaMinText'               => 'min read',
			'metaAuthorPrefix'          => 'By',
			'metaDateFormat'            => 'M j, Y',

			// --------------------------
			// Wrapper Style
			// --------------------------
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
			'dcSize'                    => 7,
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/post-slider-1',
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

		$block_name          = 'post-slider-1';
		$img_opts            = ultimate_post()->image_options( $attr, $block_name );
		$wraper_before       = $wraper_after = $post_loop = '';
		$attr['queryNumber'] = ultimate_post()->get_post_number( 5, $attr['queryNumber'], $attr['queryNumPosts'] );
		$recent_posts        = new \WP_Query( ultimate_post()->get_query( $attr ) );
		$pageNum             = ultimate_post()->get_page_number( $attr, $recent_posts->found_posts );
		// Dummy Img Url
		$dummy_url = ULTP_URL . 'assets/img/ultp-fallback-img.png';

		$slides = is_object( $attr['slidesToShow'] ) ? json_decode( wp_json_encode( $attr['slidesToShow'] ), true ) : $attr['slidesToShow'];

		if ( $recent_posts->have_posts() ) {

			$attr['className']                 = isset( $attr['className'] ) && $attr['className'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
			$attr['align']                     = isset( $attr['align'] ) && $attr['align'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
			$attr['advanceId']                 = isset( $attr['advanceId'] ) ? sanitize_html_class( $attr['advanceId'] ) : '';
			$attr['blockId']                   = isset( $attr['blockId'] ) ? sanitize_html_class( $attr['blockId'] ) : '';
			$attr['contentTag']                = in_array( $attr['contentTag'], ultimate_post()->ultp_allowed_block_tags() ) ? $attr['contentTag'] : 'div';
			$attr['imgOverlayType']            = sanitize_html_class( $attr['imgOverlayType'] );
			$attr['slideSpeed']                = sanitize_html_class( $attr['slideSpeed'] );
			$attr['arrows']                    = $attr['arrows'] == true;
			$attr['dots']                      = $attr['dots'] == true;
			$attr['autoPlay']                  = $attr['autoPlay'] == true;
			$attr['fade']                      = $attr['fade'] == true;
			$attr['readMoreText']              = wp_kses( $attr['readMoreText'], ultimate_post()->ultp_allowed_html_tags() );
			$attr['contentVerticalPosition']   = sanitize_html_class( $attr['contentVerticalPosition'] );
			$attr['contentHorizontalPosition'] = sanitize_html_class( $attr['contentHorizontalPosition'] );

			$wraper_before     .= '<div ' . ( $attr['advanceId'] ? 'id="' . esc_attr( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( $attr['blockId'] ) . '' . ( $attr['align'] ? ' align' . esc_attr( $attr['align'] ) : '' ) . '' . ( $attr['className'] ? ' ' . esc_attr( $attr['className'] ) : '' ) . '">';
				$wraper_before .= '<div class="ultp-block-wrapper">';
			if ( $attr['headingShow'] ) {
				$wraper_before     .= '<div class="ultp-heading-filter">';
					$wraper_before .= '<div class="ultp-heading-filter-in">';
						include ULTP_PATH . 'blocks/template/heading.php';
					$wraper_before .= '</div>';
				$wraper_before     .= '</div>';
			}

					$wraper_before .= '<div class="ultp-block-items-wrap" data-arrows="' . esc_attr( $attr['arrows'] ) . '" data-dots="' . esc_attr( $attr['dots'] ) . '" data-autoplay="' . esc_attr( $attr['autoPlay'] ) . '" data-slidespeed="' . esc_attr( $attr['slideSpeed'] ) . '" data-fade="' . esc_attr( $attr['fade'] ) . '" data-slidelg="' . ( isset( $slides['lg'] ) ? esc_attr( sanitize_html_class( $slides['lg'] ) ) : 1 ) . '" data-slidesm="' . ( isset( $slides['sm'] ) ? esc_attr( sanitize_html_class( $slides['sm'] ) ) : 1 ) . '" data-slidexs="' . ( isset( $slides['xs'] ) ? esc_attr( sanitize_html_class( $slides['xs'] ) ) : 1 ) . '">';
						$idx        = $noAjax ? 1 : 0;
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
							$post_loop     .= '<div class="ultp-post-slider-loader-container">';
								$post_loop .= wp_kses( ultimate_post()->postx_loading(), ultimate_post()->rendered_content_allowed_html() );
							$post_loop     .= '</div>';
				}

					$post_loop .= '<div>';
					$post_loop .= '<div class="ultp-block-slider-wrap">';

						$post_loop .= '<div class="ultp-block-image-inner">';
				if ( ( $post_thumb_id || $attr['fallbackEnable'] ) && $attr['imageShow'] ) {
					$post_loop     .= '<div class="ultp-block-image ' . ( $attr['imgOverlay'] ? ' ultp-block-image-overlay ultp-block-image-' . esc_attr( $attr['imgOverlayType'] ) . ' ultp-block-image-' . esc_attr( $attr['imgOverlayType'] ) . esc_attr( $idx ) : '' ) . '">';
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

						$post_loop     .= '<div class="ultp-block-content ultp-block-content-' . esc_attr( $attr['contentVerticalPosition'] ) . ' ultp-block-content-' . esc_attr( $attr['contentHorizontalPosition'] ) . '">';
							$post_loop .= '<div class="ultp-block-content-inner">';

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

													$post_loop .= '</div>'; // .ultp-block-content-inner
													$post_loop .= '</div>'; // .ultp-block-content

													$post_loop .= '</div>'; // .ultp-block-slider-wrap
													$post_loop .= '</div>'; // div
													$post_loop .= '</' . tag_escape( $attr['contentTag'] ) . '>'; // .ultp-block-item

						endwhile;

					$wraper_after .= '</div>'; // .ultp-block-items-wrap

			if ( $attr['arrows'] ) {
				$wraper_after     .= '<div class="ultp-slick-nav" style="display:none">';
					$nav           = explode( '#', $attr['arrowStyle'] );
					$wraper_after .= '<div class="ultp-slick-prev"><div class="slick-arrow slick-prev">' . wp_kses( ultimate_post()->get_svg_icon( $nav[0] ), ultimate_post()->rendered_content_allowed_html() ) . '</div></div>';
					$wraper_after .= '<div class="ultp-slick-next"><div class="slick-arrow slick-next">' . wp_kses( ultimate_post()->get_svg_icon( $nav[1] ), ultimate_post()->rendered_content_allowed_html() ) . '</div></div>';
				$wraper_after     .= '</div>';
			}

				$wraper_after .= '</div>'; // .ultp-block-wrapper
			$wraper_after     .= '</div>'; // .wp-block-ultimate-post-post-slider-1

			wp_reset_postdata();
		} else {
			$wraper_before .= wp_kses( ultimate_post()->get_no_result_found_html( $attr['notFoundMessage'] ), ultimate_post()->rendered_content_allowed_html() );
		}

		return $noAjax ? $post_loop : $wraper_before . $post_loop . $wraper_after;
	}
}

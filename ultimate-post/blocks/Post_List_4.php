<?php

namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Post_List_4 {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {

		return array(
			'blockId'             => '',
			'previewImg'          => '',
			// --------------------------
			// Layout
			// --------------------------
			'layout'              => 'layout1',

			// --------------------------
			// Query Setting
			// --------------------------
			'queryQuick'          => '',
			'queryNumPosts'       => (object) array( 'lg' => 5 ),
			'queryNumber'         => 5,
			'queryType'           => 'post',
			'queryTax'            => 'category',
			'queryTaxValue'       => '[]',
			'queryRelation'       => 'OR',
			'queryOrderBy'        => 'date',
			'metaKey'             => 'custom_meta_key',
			'queryOrder'          => 'desc',
			// Include Remove from Version 2.5.4
			'queryInclude'        => '',
			'queryExclude'        => '[]',
			'queryAuthor'         => '[]',
			'queryOffset'         => '0',
			'querySticky'         => true,
			// --------------------------
			// General Setting
			// --------------------------
			'titleShow'           => true,
			'titleStyle'          => 'none',
			'headingShow'         => true,
			'excerptShow'         => true,
			'catShow'             => true,
			'metaShow'            => true,
			'showImage'           => true,
			'filterShow'          => false,
			'paginationShow'      => true,
			'readMore'            => true,
			'contentTag'          => 'div',
			'openInTab'           => false,
			'notFoundMessage'     => 'No Post Found',

			// --------------------------
			// Heading Setting/Style
			// --------------------------
			'headingText'         => 'Post List #4',
			'headingURL'          => '',
			'headingBtnText'      => 'View More',
			'headingStyle'        => 'style1',
			'headingTag'          => 'h2',
			'headingAlign'        => 'left',
			'subHeadingShow'      => false,
			'subHeadingText'      => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ut sem augue. Sed at felis ut enim dignissim sodales.',

			// --------------------------
			// Title Setting/Style
			// --------------------------
			'titleTag'            => 'h3',
			'titlePosition'       => true,
			'titleLength'         => 0,

			// --------------------------
			// Content Setting/Style
			// --------------------------

			'varticalAlign'       => 'middle',
			'showSeoMeta'         => false,
			'showFullExcerpt'     => false,
			'showFullExcerptLg'   => false,
			'excerptLimitLg'      => 40,
			'showSmallExcerpt'    => true,
			'excerptLimit'        => 30,

			// --------------------------
			// Category Setting/Style
			// --------------------------
			'maxTaxonomy'         => '30',
			'taxonomy'            => 'category',
			'showSmallCat'        => false,
			'catStyle'            => 'classic',
			'catPosition'         => 'aboveTitle',
			'seperatorLink'       => admin_url( 'edit-tags.php?taxonomy=category' ),

			// --------------------------
			// Meta Setting/Style
			// --------------------------
			'showSmallMeta'       => true,
			'metaPosition'        => 'top',
			'metaStyle'           => 'icon',
			'authorLink'          => true,
			'metaSeparator'       => 'dot',
			'metaList'            => '["metaAuthor","metaDate","metaRead"]',
			'metaMinText'         => 'min read',
			'metaAuthorPrefix'    => 'By',
			'metaDateFormat'      => 'M j, Y',
			'metaListSmall'       => '["metaAuthor","metaDate","metaRead"]',

			// --------------------------
			// Image Setting/Style
			// --------------------------
			'mobileImageTop'      => false,
			'imgFlip'             => false,
			'imgCrop'             => 'full',
			'imgCropSmall'        => ( ultimate_post()->get_setting( 'disable_image_size' ) == 'yes' ? 'full' : 'ultp_layout_square' ),
			'imgAnimation'        => 'opacity',
			'imgOverlay'          => false,
			'imgOverlayType'      => 'default',
			'fallbackEnable'      => true,
			'fallbackImg'         => '',

			// --------------------------
			// Video Setting/Style
			// --------------------------
			'vidIconEnable'       => true,
			'popupAutoPlay'       => true,
			'iconSize'            => (object) array(
				'lg'   => '40',
				'sm'   => '30',
				'xs'   => '30',
				'unit' => 'px',
			),
			// by default should be off

			// --------------------------
			// Separator
			// --------------------------
			'separatorShow'       => true,

			// --------------------------
			// Read more Setting/Style
			// --------------------------
			'showSmallBtn'        => false,
			'readMoreText'        => '',
			'readMoreIcon'        => '',

			// --------------------------
			// Filter Setting/Style
			// --------------------------
			'filterBelowTitle'    => false,
			'filterType'          => 'category',
			'filterText'          => 'all',
			'filterValue'         => '[]',
			'filterMobile'        => true,
			'filterMobileText'    => 'More',

			// --------------------------
			// Pagination Setting/Style
			// --------------------------
			'paginationType'      => 'pagination',
			'loadMoreText'        => 'Load More',
			'paginationText'      => 'Previous|Next',
			'paginationNav'       => 'textArrow',
			'paginationAjax'      => true,
			'navPosition'         => 'topRight',

			// --------------------------
			// Wrapper Style
			// --------------------------
			'advanceId'           => '',
			'advanceZindex'       => '',
			'hideExtraLarge'      => false,
			'hideTablet'          => false,
			'hideMobile'          => false,
			'currentPostId'       => '',

			// --------------------------------
			// Advance Filter Block Compatibility
			// --------------------------------
			'advFilterEnable'     => false,
			'querySearch'         => '',

			// --------------------------------
			// Pagination block compatibility
			// --------------------------------
			'advPaginationEnable' => false,
			'defQueryTax'         => array(),
			'advRelation'         => 'AND',
			// 'paginationAjax' => true,
			// 'paginationText' =>  'Previous|Next',
			// 'loadMoreText' =>  'Load More',
			// 'queryNumPosts' =>  (object)['lg'=>5],
			// 'queryNumber2' => 6,
			// 'notFirstLoad' => false,

			/*
			============================
				Dynamic Content
			============================*/
			'dcEnabled'           => false,
			'dcFields'            => array(),
			'dcSize'              => 8,
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/post-list-4',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	/**
	 * Shared display settings of this block, retaining saved choices without an add-on.
	 *
	 * @param array $attr Block attributes.
	 * @return array
	 */
	public function addon_attributes( $attr ) {
		// Retain saved Free choices while allowing add-ons to filter them.
		$attr = \ULTP\Extended_Compat::meta_and_category_options( $attr, $this->get_attributes(), 'post-list-4' );

		return $attr;
	}

	public function content( $attr, $noAjax ) {
		$attr = wp_parse_args( $attr, $this->get_attributes() );
		$attr = $this->addon_attributes( $attr );

		if ( ! $noAjax ) {
			$paged         = is_front_page() ? get_query_var( 'page' ) : get_query_var( 'paged' );
			$attr['paged'] = $paged ? $paged : 1;
		}
		// Lets an add-on prepare for the query of this block.
		do_action( 'ultp_block_before_query', $attr );

		$block_name          = 'post-list-4';
		$img_opts            = ultimate_post()->image_options( $attr, $block_name );
		$vid_icon_redirect   = true; // Its used for video icon do not remove it
		$wraper_before       = $wraper_after = $post_loop = '';
		$attr['queryNumber'] = ultimate_post()->get_post_number( 5, $attr['queryNumber'], $attr['queryNumPosts'] );
		$recent_posts        = new \WP_Query( ultimate_post()->get_query( $attr ) );
		$pageNum             = ultimate_post()->get_page_number( $attr, $recent_posts->found_posts );
		// Dummy Img Url
		$dummy_url = ULTP_URL . 'assets/img/ultp-fallback-img.png';

		// Lets an add-on act once the query of this block has run.
		do_action( 'ultp_block_after_query', $attr );

		$attr['className']      = isset( $attr['className'] ) && $attr['className'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
		$attr['align']          = isset( $attr['align'] ) && $attr['align'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
		$attr['advanceId']      = isset( $attr['advanceId'] ) ? sanitize_html_class( $attr['advanceId'] ) : '';
		$attr['blockId']        = isset( $attr['blockId'] ) ? sanitize_html_class( $attr['blockId'] ) : '';
		$attr['contentTag']     = in_array( $attr['contentTag'], ultimate_post()->ultp_allowed_block_tags() ) ? $attr['contentTag'] : 'div';
		$attr['layout']         = sanitize_html_class( $attr['layout'] );
		$attr['imgAnimation']   = sanitize_html_class( $attr['imgAnimation'] );
		$attr['imgOverlayType'] = sanitize_html_class( $attr['imgOverlayType'] );
		$attr['popupAutoPlay']  = $attr['popupAutoPlay'] == true;
		$attr['readMoreText']   = wp_kses( $attr['readMoreText'], ultimate_post()->ultp_allowed_html_tags() );
		$attr['varticalAlign']  = sanitize_html_class( $attr['varticalAlign'] );

		// Wrapper class and data attributes an add-on may supply (empty by default). See classes/Extended_Compat.php.
		$infinite  = \ULTP\Extended_Compat::infinite_scroll( $attr, isset( $paged ) ? $paged : null, $pageNum, $block_name, isset( $curr_post_id ) ? $curr_post_id : '' );
		$iscroll   = $infinite['class'];
		$wrap_data = $infinite['wrap_data'];

		if ( $recent_posts->have_posts() ) {

			// Pagination Block Html
			include ULTP_PATH . 'blocks/template/pagination_block.php';

			$wraper_before .= '<div ' . ( $attr['advanceId'] ? 'id="' . esc_attr( $attr['advanceId'] ) . '" ' : '' ) . ' class="ultp-post-grid-block wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( $attr['blockId'] ) . '' . ( $attr['align'] ? ' align' . esc_attr( $attr['align'] ) : '' ) . '' . ( $attr['className'] ? ' ' . esc_attr( $attr['className'] ) : '' ) . ' ' . esc_attr( $iscroll ) . '">';
			$wraper_before .= wp_kses( '<div class="ultp-block-wrapper" ' . $wrap_data . '>', ultimate_post()->rendered_content_allowed_html() );

			// Loading
			$wraper_before .= wp_kses( ultimate_post()->postx_loading(), ultimate_post()->rendered_content_allowed_html() );

			// Inline filter bar and top navigation, next to the heading. Each one only when the block shows it (see Functions::inline_filter_on()).
			$inline_filter = ultimate_post()->inline_filter_on( $attr );
			$top_nav       = ultimate_post()->inline_navigation_on( $attr, true );
			if ( $attr['headingShow'] || $inline_filter || $top_nav ) {
				$wraper_before .= '<div class="ultp-heading-filter' . ( empty( $attr['headingShow'] ) ? ' ultp-no-heading' : '' ) . '">';
				$wraper_before .= '<div class="ultp-heading-filter-in">';
				// Heading
				include ULTP_PATH . 'blocks/template/heading.php';
				if ( $inline_filter || $top_nav ) {
					$wraper_before .= '<div class="ultp-filter-navigation">';
					// Filter
					if ( $inline_filter ) {
						include ULTP_PATH . 'blocks/template/filter.php';
					}
					// Navigation
					if ( $top_nav ) {
						include ULTP_PATH . 'blocks/template/navigation-before.php';
					}
					$wraper_before .= '</div>';
				}
				$wraper_before .= '</div>';
				$wraper_before .= '</div>';
			}
			$wraper_before .= '<div class="ultp-block-items-wrap ultp-block-content-' . esc_attr( $attr['varticalAlign'] ) . ( apply_filters( 'ultp_post_block_flip', false, $attr, array( 'block' => $block_name ) ) ? ' ultp-block-content-true' : '' ) . ' ' . esc_attr( sanitize_html_class( apply_filters( 'ultp_post_list_4_layout_class', 'ultp-layout1', $attr ) ) ) . '">';
			$idx            = 0; // $idx = $noAjax ? 1 : 0;
			while ( $recent_posts->have_posts() ) :
				$recent_posts->the_post();

				$dcContent = array_fill( 0, $attr['dcSize'], '' );

				if ( ultimate_post()->is_dc_active( $attr ) ) {
					$dcContent = \ULTP\DCService::get_dc_content_for_block( $attr, $dcContent );
				}

				include ULTP_PATH . 'blocks/template/data.php';

				include ULTP_PATH . 'blocks/template/category.php';

				// Lets an add-on note each post the block shows.
				do_action( 'ultp_block_loop_post', $post_id, $attr );

				$post_loop .= '<' . tag_escape( $attr['contentTag'] ) . ' class="ultp-block-item ultp-block-media post-id-' . esc_attr( $post_id ) . '">';
				$post_loop .= '<div class="ultp-block-content-wrap' . ( ( ( $idx === 0 && $noAjax == false ) || ( $idx == 0 && $noAjax && $attr['paginationType'] != 'loadMore' ) ) ? ' ultp-first-postlist-2' : ' ultp-all-postlist-2' ) . '">';
				if ( ( $post_thumb_id || $attr['fallbackEnable'] ) && $attr['showImage'] ) {
					if ( $idx == 0 && $noAjax == false ) {
						$post_loop .= '<div class="ultp-block-image ultp-block-image-' . esc_attr( $attr['imgAnimation'] ) . ( $attr['imgOverlay'] ? ' ultp-block-image-overlay ultp-block-image-' . esc_attr( $attr['imgOverlayType'] ) . ' ultp-block-image-' . esc_attr( $attr['imgOverlayType'] ) . esc_attr( $idx ) : '' ) . '">';
						$post_loop .= '<a href="' . esc_url( $titlelink ) . '" ' . ( $attr['openInTab'] ? 'target="_blank"' : '' ) . '>';
						// Post Image Id
						$block_img_id = $post_thumb_id ? $post_thumb_id : ( $attr['fallbackEnable'] && isset( $attr['fallbackImg']['id'] ) ? $attr['fallbackImg']['id'] : '' );
						// Post Image
						if ( $post_thumb_id || ( $attr['fallbackEnable'] && $block_img_id ) ) {
							$post_loop .= wp_kses( ultimate_post()->get_image( $block_img_id, $attr['imgCrop'], '', $title, $img_opts['srcset'], $img_opts['lazy'] ), ultimate_post()->rendered_content_allowed_html() );
						} else {
							$video      = ultimate_post()->get_youtube_id( $post_video );
							$post_loop .= '<img  src="' . esc_url( $video ? 'https://img.youtube.com/vi/' . $video . '/0.jpg' : $dummy_url ) . '" alt="dummy-img" />';
						}
						$post_loop .= '</a>';
						if ( ( $attr['catPosition'] != 'aboveTitle' ) && ( $noAjax == false ) && $attr['catShow'] ) {
							$post_loop .= '<div class="ultp-category-img-grid">' . wp_kses( $category, ultimate_post()->rendered_content_allowed_html() ) . '</div>';
						}
						$post_loop .= '</div>';
						if ( $post_video ) {
							include ULTP_PATH . 'blocks/template/video_icon.php';
						}
					} else {
						// Image of a small post. Built apart from the post markup, so the slot below can take it whole.
						$post_before_small = $post_loop;
						$post_loop         = '';
						$post_loop .= '<div class="ultp-block-image ultp-block-image-' . esc_attr( $attr['imgAnimation'] ) . ( $attr['imgOverlay'] ? ' ultp-block-image-overlay ultp-block-image-' . esc_attr( $attr['imgOverlayType'] ) . ' ultp-block-image-' . esc_attr( $attr['imgOverlayType'] ) . esc_attr( $idx ) : '' ) . '">';
						$post_loop .= '<a href="' . esc_url( $titlelink ) . '" ' . ( $attr['openInTab'] ? 'target="_blank"' : '' ) . '>';
						// Image
						if ( $post_thumb_id ) {
							$post_loop .= wp_kses( ultimate_post()->get_image( $post_thumb_id, $attr['imgCropSmall'], '', $title, $img_opts['srcset'], $img_opts['lazy'] ), ultimate_post()->rendered_content_allowed_html() );
						} elseif ( $attr['fallbackEnable'] ) {
							if ( isset( $attr['fallbackImg']['id'] ) ) {
								// User Define Fallback Image
								$post_loop .= wp_kses( ultimate_post()->get_image( $attr['fallbackImg']['id'], $attr['imgCropSmall'], '', $title, $img_opts['srcset'], $img_opts['lazy'] ), ultimate_post()->rendered_content_allowed_html() );
							} else {
								// Default Fallback Image
								$video      = ultimate_post()->get_youtube_id( $post_video );
								$post_loop .= '<img  src="' . esc_url( $video ? 'https://img.youtube.com/vi/' . $video . '/0.jpg' : $dummy_url ) . '" alt="dummy-img" />';
							}
						}
						$post_loop .= '</a>';
						if ( $post_video ) {
							include ULTP_PATH . 'blocks/template/video_icon.php';
						}
						if ( ( $attr['catPosition'] != 'aboveTitle' ) && ( ( $idx == 0 && $noAjax == false ) || $attr['showSmallCat'] ) && $attr['catShow'] ) {
							$post_loop .= '<div class="ultp-category-img-grid">' . wp_kses( $category, ultimate_post()->rendered_content_allowed_html() ) . '</div>';
						}
						$post_loop .= '</div>';
						// The default is the small image block; it can be changed through ultp_post_list_4_small_image_html.
						$small_default = $post_loop;
						$post_loop     = $post_before_small;
						$small_ctx     = array(
							'idx'           => $idx,
							'post_id'       => $post_id,
							'post_thumb_id' => $post_thumb_id,
							'titlelink'     => $titlelink,
							'title'         => $title,
							'post_video'    => $post_video,
							'dummy_url'     => $dummy_url,
							'category'      => $category,
							'img_opts'      => $img_opts,
						);
						$post_loop .= ultimate_post()->esc_filtered_markup( apply_filters( 'ultp_post_list_4_small_image_html', $small_default, $attr, $small_ctx ), $small_default );
					}
				}
				$post_loop .= '<div class="ultp-block-content">';

				$post_loop .= wp_kses( $dcContent[7], ultimate_post()->rendered_content_allowed_html() );

				// Category
				if ( ( $attr['catPosition'] == 'aboveTitle' ) && ( ( $idx == 0 && $noAjax == false ) || $attr['showSmallCat'] ) && $attr['catShow'] ) {
					$post_loop .= wp_kses( $category, ultimate_post()->rendered_content_allowed_html() );
				}

				$post_loop .= wp_kses( $dcContent[6], ultimate_post()->rendered_content_allowed_html() );

				// Title
				if ( $title && $attr['titleShow'] && $attr['titlePosition'] == 1 ) {
					include ULTP_PATH . 'blocks/template/title.php';
				}

				$post_loop .= wp_kses( $dcContent[5], ultimate_post()->rendered_content_allowed_html() );

				// Meta Top
				if ( $attr['metaShow'] && ( ( $idx == 0 && $noAjax == false ) || $attr['showSmallMeta'] ) && $attr['metaPosition'] == 'top' ) {
					include ULTP_PATH . 'blocks/template/meta.php';
				}

				$post_loop .= wp_kses( $dcContent[4], ultimate_post()->rendered_content_allowed_html() );

				// Title
				if ( $title && $attr['titleShow'] && $attr['titlePosition'] == 0 ) {
					include ULTP_PATH . 'blocks/template/title.php';
				}

				$post_loop .= wp_kses( $dcContent[3], ultimate_post()->rendered_content_allowed_html() );

				// Excerpt
				if ( ( ( $idx == 0 && $noAjax == false ) || $attr['showSmallExcerpt'] ) && $attr['excerptShow'] ) {
					$showFullexcerpt = $idx == 0 ? $attr['showFullExcerptLg'] : $attr['showFullExcerpt'];

					$excptLimit = $idx == 0 ? $attr['excerptLimitLg'] : $attr['excerptLimit'];
					$post_loop .= '<div class="ultp-block-excerpt">' . wp_kses_post( ultimate_post()->get_excerpt( $post_id, $attr['showSeoMeta'], $showFullexcerpt, $excptLimit ) ) . '</div>';
				}

				$post_loop .= wp_kses( $dcContent[2], ultimate_post()->rendered_content_allowed_html() );

				// Read More
				if ( $attr['readMore'] && ( ( $idx == 0 && $noAjax == false ) || $attr['showSmallBtn'] ) ) {
					$post_loop .= '<div class="ultp-block-readmore"><a aria-label="' . esc_attr( $title ) . '" href="' . esc_url( $titlelink ) . '" ' . ( $attr['openInTab'] ? 'target="_blank"' : '' ) . '>' . ( $attr['readMoreText'] ? wp_kses( $attr['readMoreText'], ultimate_post()->ultp_allowed_html_tags() ) : esc_html__( 'Read More', 'ultimate-post' ) ) . wp_kses( ultimate_post()->get_svg_icon( $attr['readMoreIcon'] ), ultimate_post()->rendered_content_allowed_html() ) . '</a></div>';
				}

				$post_loop .= wp_kses( $dcContent[1], ultimate_post()->rendered_content_allowed_html() );

				// Meta Bottom
				if ( $attr['metaShow'] && ( ( $idx == 0 && $noAjax == false ) || $attr['showSmallMeta'] ) && $attr['metaPosition'] == 'bottom' ) {
					include ULTP_PATH . 'blocks/template/meta.php';
				}

				$post_loop .= wp_kses( $dcContent[0], ultimate_post()->rendered_content_allowed_html() );

				$post_loop .= '</div>';
				$post_loop .= '</div>';
				if ( $post_video ) {
					// Popup of the post. The default is the popup of every post with a video; it can be changed through ultp_post_list_4_video_popup_html.
					$popup_default = wp_kses( \ULTP\Extended_Compat::video_popup( $attr, $post_video, $title, $titlelink ), ultimate_post()->rendered_content_allowed_html() );
					$post_loop    .= ultimate_post()->esc_filtered_markup( apply_filters( 'ultp_post_list_4_video_popup_html', $popup_default, $attr, array( 'idx' => $idx, 'post_video' => $post_video, 'title' => $title, 'titlelink' => $titlelink ) ), $popup_default );
				}
				$post_loop .= '</' . tag_escape( $attr['contentTag'] ) . '>';
				++$idx;
			endwhile;
			// Markup an add-on may add after the posts. Empty by default.
			$loop_end   = apply_filters( 'ultp_block_loop_end', '', $attr );
			$post_loop .= wp_kses( ultimate_post()->esc_filtered_markup( $loop_end, '' ), ultimate_post()->rendered_content_allowed_html() );
			// if ( ( $attr['paginationShow'] || $attr['advPaginationEnable'] ) && ($attr['paginationType'] == 'loadMore')) {
			// $wraper_after .= '<span class="ultp-loadmore-insert-before"></span>';
			// }

			$wraper_after .= '</div>'; // ultp-block-items-wrap

			// Inline Load More, navigation below the posts, and pagination. Each one only when the block shows it.
			if ( ultimate_post()->inline_pagination_on( $attr, 'loadMore' ) ) {
				include ULTP_PATH . 'blocks/template/loadmore.php';
			}
			if ( ultimate_post()->inline_navigation_on( $attr, false ) ) {
				include ULTP_PATH . 'blocks/template/navigation-after.php';
			}
			if ( ultimate_post()->inline_pagination_on( $attr, 'pagination' ) ) {
				include ULTP_PATH . 'blocks/template/pagination.php';
			}


			$wraper_after .= '</div>';
			$wraper_after .= $pagi_block_html;
			$wraper_after .= '</div>';

			wp_reset_postdata();
		} else {
			$wraper_before .= wp_kses( ultimate_post()->get_no_result_found_html( $attr['notFoundMessage'] ), ultimate_post()->rendered_content_allowed_html() );
		}

		return $noAjax ? $post_loop : $wraper_before . $post_loop . $wraper_after;
	}
}

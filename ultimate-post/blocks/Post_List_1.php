<?php
namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Post_List_1 {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {

		return array(
			'blockId'             => '',
			'previewImg'          => '',

			/*
			============================
				General Setting
			============================*/
			'layout'              => 'layout1',
			'gridStyle'           => 'style1',
			'columns'             => (object) array(
				'lg' => '2',
				'xs' => '2',
			),
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

			/*
			============================
				Query Setting
			============================*/
			'queryQuick'          => '',
			'queryNumPosts'       => (object) array( 'lg' => 6 ),
			'queryNumber'         => 6,
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

			/*
			============================
				Heading Style
			============================*/
			'headingText'         => 'Post List #1',
			'headingURL'          => '',
			'headingBtnText'      => 'View More',
			'headingStyle'        => 'style1',
			'headingTag'          => 'h2',
			'headingAlign'        => 'left',
			'subHeadingShow'      => false,
			'subHeadingText'      => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ut sem augue. Sed at felis ut enim dignissim sodales.',

			/*
			============================
				Title Style
			============================*/
			'titleTag'            => 'h3',
			'titlePosition'       => true,
			'titleLength'         => 0,

			/*
			============================
				Meta Style
			============================*/
			'metaPosition'        => 'top',
			'metaStyle'           => 'icon',
			'metaSeparator'       => '',
			'authorLink'          => true,
			'metaList'            => '["metaAuthor","metaDate","metaRead"]',
			'metaMinText'         => 'min read',
			'metaAuthorPrefix'    => 'By',
			'metaDateFormat'      => 'M j, Y',

			/*
			============================
				Category Style
			============================*/
			'maxTaxonomy'         => '30',
			'taxonomy'            => 'category',
			'catStyle'            => 'classic',
			'catPosition'         => 'aboveTitle',
			'seperatorLink'       => admin_url( 'edit-tags.php?taxonomy=category' ),

			/*
			============================
				Image Style
			============================*/
			'imgCrop'             => 'full',
			'imgCropSmall'        => ( ultimate_post()->get_setting( 'disable_image_size' ) == 'yes' ? 'full' : 'ultp_layout_square' ),
			'imgAnimation'        => 'none',
			'imgOverlay'          => false,
			'imgOverlayType'      => 'default',
			'fallbackEnable'      => true,
			'fallbackImg'         => '',

			/*
			============================
				Video Style
			============================*/
			'vidIconEnable'       => true,
			'popupAutoPlay'       => true,
			'iconSize'            => (object) array(
				'lg'   => '70',
				'sm'   => '50',
				'xs'   => '50',
				'unit' => 'px',
			),
			// by default should be off

			/*
			============================
				Filter Style
			============================*/
			'filterBelowTitle'    => false,
			'filterType'          => 'category',
			'filterText'          => 'all',
			'filterValue'         => '[]',
			'filterMobile'        => true,
			'filterMobileText'    => 'More',

			/*
			============================
				Pagination Style
			============================*/
			'paginationType'      => 'pagination',
			'loadMoreText'        => 'Load More',
			'paginationText'      => 'Previous|Next',
			'paginationNav'       => 'textArrow',
			'paginationAjax'      => true,
			'navPosition'         => 'topRight',

			/*
			============================
				Excerpt Style
			============================*/
			'showSeoMeta'         => false,
			'showFullExcerpt'     => false,
			'fullExcerptLg'       => false,
			'excerptLimit'        => 25,
			'excerptLimitLg'      => '70',

			/*
			============================
				Read More Style
			============================*/
			'readMoreText'        => '',
			'readMoreIcon'        => 'rightArrowLg',

			/*
			============================
				Separator Style
			============================*/
			'separatorShow'       => false,

			/*
			============================
				Advance Wrapper Style
			============================*/
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

			/*
			============================
				Dynamic Content
			============================*/
			'dcEnabled'           => false,
			'dcFields'            => array(),
			'dcSize'              => 10,
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/post-list-1',
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
		// Lets an add-on prepare for the query of this block.
		do_action( 'ultp_block_before_query', $attr );
		$block_name          = 'post-list-1';
		$img_opts            = ultimate_post()->image_options( $attr, $block_name );
		$wraper_before       = $wraper_after = $post_loop = '';
		$attr['queryNumber'] = ultimate_post()->get_post_number( 6, $attr['queryNumber'], $attr['queryNumPosts'] );
		$recent_posts        = new \WP_Query( ultimate_post()->get_query( $attr ) );
		$pageNum             = ultimate_post()->get_page_number( $attr, $recent_posts->found_posts );
		$vid_icon_redirect   = false; // Its used for video icon do not remove it
		// Dummy Img Url
		$dummy_url = ULTP_URL . 'assets/img/ultp-fallback-img.png';
		// Current Post Id For Pagination
		$curr_post_id = '';
		if ( is_single() ) {
			$curr_post_id = get_the_ID();
		}
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
		$attr['gridStyle']      = sanitize_html_class( $attr['gridStyle'] );

		// Wrapper class and data attributes an add-on may supply (empty by default). See classes/Extended_Compat.php.
		$infinite  = \ULTP\Extended_Compat::infinite_scroll( $attr, isset( $paged ) ? $paged : null, $pageNum, $block_name, isset( $curr_post_id ) ? $curr_post_id : '' );
		$iscroll   = $infinite['class'];
		$wrap_data = $infinite['wrap_data'];

		if ( $recent_posts->have_posts() ) {
			// Pagination Block Html
			include ULTP_PATH . 'blocks/template/pagination_block.php';
			$wraper_before     .= '<div ' . ( $attr['advanceId'] ? 'id="' . esc_attr( $attr['advanceId'] ) . '" ' : '' ) . ' class="ultp-post-grid-block wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( $attr['blockId'] ) . '' . ( $attr['align'] ? ' align' . esc_attr( $attr['align'] ) : '' ) . '' . ( $attr['className'] ? ' ' . esc_attr( $attr['className'] ) : '' ) . ' ' . esc_attr( $iscroll ) . ' ">';
				$wraper_before .= wp_kses( '<div class="ultp-block-wrapper" ' . $wrap_data . '>', ultimate_post()->rendered_content_allowed_html() );

					// Loading
					$wraper_before .= wp_kses( ultimate_post()->postx_loading(), ultimate_post()->rendered_content_allowed_html() );
			if ( $attr['headingShow'] || ultimate_post()->inline_filter_on( $attr ) || ultimate_post()->inline_pagination_on( $attr ) ) {
				$wraper_before     .= '<div class="ultp-heading-filter' . ( empty( $attr['headingShow'] ) ? ' ultp-no-heading' : '' ) . '">';
					$wraper_before .= '<div class="ultp-heading-filter-in">';

						// Heading
						include ULTP_PATH . 'blocks/template/heading.php';

				if ( ultimate_post()->inline_filter_on( $attr ) || ultimate_post()->inline_pagination_on( $attr ) ) {
					$wraper_before .= '<div class="ultp-filter-navigation">';

						// Filter
					if ( ultimate_post()->inline_filter_on( $attr ) ) {
						include ULTP_PATH . 'blocks/template/filter.php';
					}

						// Navigation
					if ( ultimate_post()->inline_navigation_on( $attr, true ) ) {
									include ULTP_PATH . 'blocks/template/navigation-before.php';
					}
									$wraper_before .= '</div>';
				}

					$wraper_before     .= '</div>';
						$wraper_before .= '</div>';
			}

					$wraper_before .= '<div class="ultp-block-items-wrap ultp-block-row ultp-block-column-' . esc_attr( sanitize_html_class( json_decode( wp_json_encode( $attr['columns'] ), true )['lg'] ) ) . ' ' . esc_attr( sanitize_html_class( apply_filters( 'ultp_post_list_1_style_class', 'ultp-pl1a-style1', $attr ) ) ) . ' ' . esc_attr( sanitize_html_class( apply_filters( 'ultp_post_list_1_layout_class', 'ultp-post-list1-layout1', $attr ) ) ) . '">';
						$idx        = ( ultimate_post()->inline_pagination_on( $attr, 'loadMore' ) ) ? ( $noAjax ? 1 : 0 ) : 0;
						$index      = 0;
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

				$post_loop         .= '<' . tag_escape( $attr['contentTag'] ) . ' class="ultp-block-item post-id-' . esc_attr( $post_id ) . '">';
					$post_loop     .= '<div class="ultp-block-content-wrap">';
						$post_loop .= '<div class="ultp-block-entry-content">';

						$post_loop .= wp_kses( $dcContent[9], ultimate_post()->rendered_content_allowed_html() );

				// Context the image slots of the post share.
				$image_ctx = array(
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
				// Image above the heading of the first post (empty by default). It can be changed through ultp_post_list_1_top_image_html.
				if ( $idx == 0 && ( $post_thumb_id || $attr['fallbackEnable'] ) && $attr['showImage'] ) {
					$post_loop .= wp_kses(
						ultimate_post()->esc_filtered_markup(
							apply_filters( 'ultp_post_list_1_top_image_html', '', $attr, $image_ctx ),
							''
						),
						ultimate_post()->rendered_content_allowed_html()
					);
				}

						$post_loop .= '<div class="ultp-block-entry-heading">';

							$post_loop .= wp_kses( $dcContent[8], ultimate_post()->rendered_content_allowed_html() );

							// Category
				if ( ( $attr['catPosition'] == 'aboveTitle' ) && $attr['catShow'] ) {
					$post_loop .= wp_kses( $category, ultimate_post()->rendered_content_allowed_html() );
				}

							$post_loop .= wp_kses( $dcContent[7], ultimate_post()->rendered_content_allowed_html() );

							// Title
				if ( $title && $attr['titleShow'] && $attr['titlePosition'] == 1 ) {
					include ULTP_PATH . 'blocks/template/title.php';
				}

							$post_loop .= wp_kses( $dcContent[6], ultimate_post()->rendered_content_allowed_html() );

							// Meta
				if ( $attr['metaPosition'] == 'top' ) {
					include ULTP_PATH . 'blocks/template/meta.php';
				}

							$post_loop .= wp_kses( $dcContent[5], ultimate_post()->rendered_content_allowed_html() );

							// Title
				if ( $title && $attr['titleShow'] && $attr['titlePosition'] == 0 ) {
					include ULTP_PATH . 'blocks/template/title.php';
				}

							$post_loop .= wp_kses( $dcContent[4], ultimate_post()->rendered_content_allowed_html() );

						$post_loop .= '</div>';

				// The image block of the post. The default block can be changed through ultp_post_list_1_image_html.
				if ( ( $post_thumb_id || $attr['fallbackEnable'] ) && $attr['showImage'] ) {
					$post_before_image = $post_loop;
					$post_loop         = '';
					$post_loop        .= '<div class="ultp-block-image ultp-block-image-' . esc_attr( $attr['imgAnimation'] ) . ( $attr['imgOverlay'] ? ' ultp-block-image-overlay ultp-block-image-' . esc_attr( $attr['imgOverlayType'] ) . ' ultp-block-image-' . esc_attr( $attr['imgOverlayType'] ) . esc_attr( $idx ) : '' ) . '">';
					$post_loop        .= '<a href="' . esc_url( $titlelink ) . '" ' . ( $attr['openInTab'] ? 'target="_blank"' : '' ) . '>';
					// Post Image Size. The large crop by default; the size of a post can be changed through ultp_post_list_1_image_size.
					$imgSize = apply_filters( 'ultp_post_list_1_image_size', $attr['imgCrop'], $attr, $idx );
					// Post Image Id
					$block_img_id = $post_thumb_id ? $post_thumb_id : ( $attr['fallbackEnable'] && isset( $attr['fallbackImg']['id'] ) ? $attr['fallbackImg']['id'] : '' );
					// Post Image
					if ( $post_thumb_id || ( $attr['fallbackEnable'] && $block_img_id ) ) {
						$post_loop .= wp_kses( ultimate_post()->get_image( $block_img_id, $imgSize, '', $title, $img_opts['srcset'], $img_opts['lazy'] ), ultimate_post()->rendered_content_allowed_html() );
					} else {
						$video      = ultimate_post()->get_youtube_id( $post_video );
						$post_loop .= '<img  src="' . esc_url( $video ? 'https://img.youtube.com/vi/' . $video . '/0.jpg' : $dummy_url ) . '" alt="dummy-img" />';
					}
					$post_loop .= '</a>';
					if ( $post_video ) {
						$inline_default = '<div class="ultp-block-video-content" style="display: none;" >' . wp_kses( ultimate_post()->get_embeded_video( $post_video, false, true, false, true, true, false, true, array() ), ultimate_post()->rendered_content_allowed_html() ) . '</div>';
						// The inline player of the post. The default is the player PostX draws; it can be changed through ultp_post_list_1_video_inline_html.
						$post_loop .= ultimate_post()->esc_filtered_markup(
							apply_filters( 'ultp_post_list_1_video_inline_html', $inline_default, $attr, array( 'post_id' => $post_id, 'post_video' => $post_video, 'idx' => $idx ) ),
							$inline_default
						);
					}
					if ( $post_video ) {
						include ULTP_PATH . 'blocks/template/video_icon.php';
					}
					if ( ( $attr['catPosition'] != 'aboveTitle' ) && $attr['catShow'] ) {
						$post_loop .= '<div class="ultp-category-img-grid">' . wp_kses( $category, ultimate_post()->rendered_content_allowed_html() ) . '</div>';
					}
					$post_loop .= '</div>';
					$image_block = $post_loop;
					$post_loop   = $post_before_image;
					$post_loop  .= ultimate_post()->esc_filtered_markup( apply_filters( 'ultp_post_list_1_image_html', $image_block, $attr, $image_ctx ), $image_block );
				}

										$post_loop .= '</div>';
										$post_loop .= '<div class="ultp-block-content">';

										$post_loop .= wp_kses( $dcContent[3], ultimate_post()->rendered_content_allowed_html() );

										// Excerpt
				if ( $attr['excerptShow'] ) {
					// Limit and full-excerpt flag of the post. The defaults can be changed through ultp_post_list_1_excerpt_options.
					$excerpt_options = apply_filters( 'ultp_post_list_1_excerpt_options', array( 'limit' => $attr['excerptLimit'], 'full' => $attr['showFullExcerpt'] ), $attr, $index );
					$post_loop      .= '<div class="ultp-block-excerpt">' . wp_kses_post( ultimate_post()->get_excerpt( $post_id, $attr['showSeoMeta'], $excerpt_options['full'], $excerpt_options['limit'] ) ) . '</div>';
				}

										$post_loop .= wp_kses( $dcContent[2], ultimate_post()->rendered_content_allowed_html() );
				if ( $attr['readMore'] ) {
					$post_loop .= '<div class="ultp-block-readmore"><a aria-label="' . esc_attr( $title ) . '" href="' . esc_url( $titlelink ) . '" ' . ( $attr['openInTab'] ? 'target="_blank"' : '' ) . '>' . ( $attr['readMoreText'] ? wp_kses( $attr['readMoreText'], ultimate_post()->ultp_allowed_html_tags() ) : esc_html__( 'Read More', 'ultimate-post' ) ) . wp_kses( ultimate_post()->get_svg_icon( $attr['readMoreIcon'] ), ultimate_post()->rendered_content_allowed_html() ) . '</a></div>';
				}

										$post_loop .= wp_kses( $dcContent[1], ultimate_post()->rendered_content_allowed_html() );

										// Meta
				if ( $attr['metaPosition'] == 'bottom' ) {
					include ULTP_PATH . 'blocks/template/meta.php';
				}

										$post_loop .= wp_kses( $dcContent[0], ultimate_post()->rendered_content_allowed_html() );

										$post_loop .= '</div>';
										$post_loop .= '</div>';
				if ( $post_video ) {
					$post_loop .= wp_kses( \ULTP\Extended_Compat::video_popup( $attr, $post_video, $title, $titlelink ), ultimate_post()->rendered_content_allowed_html() );
				}
										$post_loop .= '</' . tag_escape( $attr['contentTag'] ) . '>';
										++$idx;
										++$index;
						endwhile;
			// Markup an add-on may add after the posts. Empty by default.
			$loop_end   = apply_filters( 'ultp_block_loop_end', '', $attr );
			$post_loop .= wp_kses( ultimate_post()->esc_filtered_markup( $loop_end, '' ), ultimate_post()->rendered_content_allowed_html() );
						// if ( ($attr['paginationShow'] || $attr['advPaginationEnable'] )  && ($attr['paginationType'] == 'loadMore')) {
						// $wraper_after .= '<span class="ultp-loadmore-insert-before"></span>';
						// }
					$wraper_after .= '</div>';// ultp-block-items-wrap

					// Load More
			if ( ultimate_post()->inline_pagination_on( $attr, 'loadMore' ) ) {
				include ULTP_PATH . 'blocks/template/loadmore.php';
			}

					// Navigation
			if ( ultimate_post()->inline_navigation_on( $attr, false ) ) {
				include ULTP_PATH . 'blocks/template/navigation-after.php';
			}

					// Pagination
			if ( ultimate_post()->inline_pagination_on( $attr, 'pagination' ) ) {
				include ULTP_PATH . 'blocks/template/pagination.php';
			}

				$wraper_after .= '</div>';
				$wraper_after .= $pagi_block_html;
			$wraper_after     .= '</div>';

			wp_reset_postdata();
		} else {
			$wraper_before .= wp_kses( ultimate_post()->get_no_result_found_html( $attr['notFoundMessage'] ), ultimate_post()->rendered_content_allowed_html() );
		}

		return $noAjax ? $post_loop : $wraper_before . $post_loop . $wraper_after;
	}
}

<?php
// phpcs:ignore
// @codingStandardsIgnoreFile
// @ignoreWarnings
/**
 * Require All Blocks and handle block ajax action
 *
 * @package ULTP\Blocks
 * @since 4.1.11
 */

namespace ULTP;
defined( 'ABSPATH' ) || exit;

/**
 * Blocks class.
 */
class Blocks {

	/**
	 * IDs of the saved templates filter_content_return() is expanding, to stop cycles.
	 *
	 * @var array
	 */
	private $expanding_templates = array();

	/**
	 * Setup class.
	 *
	 * Registers AJAX actions and includes all block classes.
	 *
	 * @since 4.1.11
	 */
	public function __construct() {
		$this->include_all_blocks(); // Include Blocks .
		add_action( 'wp_ajax_ultp_next_prev', array( $this, 'ultp_next_prev_callback' ) ); // Next Previous AJAX Call .
		add_action( 'wp_ajax_nopriv_ultp_next_prev', array( $this, 'ultp_next_prev_callback' ) ); // Next Previous AJAX Call Logout User .

		add_action( 'wp_ajax_ultp_filter', array( $this, 'ultp_filter_callback' ) ); // Next Previous AJAX Call .
		add_action( 'wp_ajax_nopriv_ultp_filter', array( $this, 'ultp_filter_callback' ) ); // Next Previous AJAX Call Logout User .

		add_action( 'wp_ajax_ultp_adv_filter', array( $this, 'ultp_adv_filter_callback' ) );
		add_action( 'wp_ajax_nopriv_ultp_adv_filter', array( $this, 'ultp_adv_filter_callback' ) );

		add_action( 'wp_ajax_ultp_pagination', array( $this, 'ultp_pagination_callback' ) ); // Page Number AJAX Call .
		add_action( 'wp_ajax_nopriv_ultp_pagination', array( $this, 'ultp_pagination_callback' ) ); // Page Number AJAX Call Logout User .

		add_action( 'wp_ajax_ultp_share_count', array( $this, 'ultp_share_count_callback' ) ); // share Count save .
		add_action( 'wp_ajax_nopriv_ultp_share_count', array( $this, 'ultp_share_count_callback' ) ); // share Count save .
		// Public by design — see ultp_get_nonce_callback() for why, and for what
		// 'ultp-public-nonce' does and does not protect.
		add_action( 'wp_ajax_ultp_get_nonce', array( $this, 'ultp_get_nonce_callback' ) );
		add_action( 'wp_ajax_nopriv_ultp_get_nonce', array( $this, 'ultp_get_nonce_callback' ) );
	}

	/**
	 * Require Blocks
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function include_all_blocks() {
		// Read-only: tells the Divi and Elementor editor requests apart, to decide whether to load the
		// block classes in wp-admin. Nothing is changed, so there is no nonce to check.
		$request    = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only routing check, see above.
		$get_action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing check, see above.

		if (
			is_admin() &&
			$request != 'et_fb_ajax_render_shortcode' && // Divi Module Check .
			$get_action != 'elementor' && // Elementor Widget Check .
			$request != 'elementor_ajax' // Elementor Widget Check .
		) {
			return;
		}

		$settings = ultimate_post()->get_setting();
		$blocks   = array(
			'post_list_1'     => 'Post_List_1',
			'post_list_2'     => 'Post_List_2',
			'post_list_3'     => 'Post_List_3',
			'post_list_4'     => 'Post_List_4',
			'post_grid_1'     => 'Post_Grid_1',
			'post_grid_2'     => 'Post_Grid_2',
			'post_grid_3'     => 'Post_Grid_3',
			'post_grid_4'     => 'Post_Grid_4',
			'post_grid_5'     => 'Post_Grid_5',
			'post_grid_6'     => 'Post_Grid_6',
			'post_grid_7'     => 'Post_Grid_7',
			'post_slider_1'   => 'Post_Slider_1',
			'post_slider_2'   => 'Post_Slider_2',
			'post_module_1'   => 'Post_Module_1',
			'post_module_2'   => 'Post_Module_2',
			'heading'         => 'Heading',
			'image'           => 'Image',
			'taxonomy'        => 'Taxonomy',
			'news_ticker'     => 'News_Ticker',
			'advanced_search' => 'Advanced_Search',
			'advanced_filter' => 'Advanced_Filter',
			'dark_Light'      => 'Dark_Light',
			'advanced_list'   => 'Advanced_List',
			'button'          => 'Button',
			'youtube_gallery' => 'Youtube_Gallery',
		);

		foreach ( $blocks as $id => $block ) {
			if ( isset( $settings[ $id ] ) && $settings[ $id ] != 'yes' ) {
			} else {
				$obj = '\ULTP\blocks\\' . $block;
				new $obj();
			}
		}

		if ( isset( $settings['ultp_builder'] ) && $settings['ultp_builder'] == 'true' ) {
			$builder_blocks = array(
				'builder_archive_title'       => 'Archive_Title',
				'builder_post_title'          => 'Post_Title',
				'builder_post_content'        => 'Post_Content',
				'builder_post_featured_image' => 'Post_Featured_Image',
				'builder_post_breadcrumb'     => 'Post_Breadcrumb',
				'builder_post_tag'            => 'Post_Tag',
				'builder_post_category'       => 'Post_Category',
				'builder_post_next_previous'  => 'Next_Previous',
				'builder_post_excerpt'        => 'Post_Excerpt',
				'builder_author_box'          => 'Author_Box',
				'builder_post_comments'       => 'Post_Comments',
				'builder_post_view_count'     => 'Post_View_Count',
				'builder_post_reading_time'   => 'Post_Reading_Time',
				'builder_post_comment_count'  => 'Post_Comment_Count',
				'builder_post_author_meta'    => 'Post_Author_Meta',
				'builder_post_date_meta'      => 'Post_Date_Meta',
				'builder_post_social_share'   => 'Post_Social_Share',
				'builder_advance_post_meta'   => 'Advance_Post_Meta',
			);
			foreach ( $builder_blocks as $id => $block ) {
				if ( isset( $settings[ $id ] ) && $settings[ $id ] != 'yes' ) {
				} else {
					$obj = '\ULTP\blocks\\' . $block;
					new $obj();
				}
			}
		}
	}


	/**
	 * Blocks Content Start.
	 *
	 * @since v.1.0.0
	 *
	 * @param ARRAY        $blocks         The blocks array.
	 * @param STRING       $paged          The current page number.
	 * @param STRING       $blockId        The block ID.
	 * @param STRING       $blockRaw       The raw block name.
	 * @param STRING       $blockName      The block name.
	 * @param STRING       $builder        The builder flag.
	 * @param STRING       $postId         The post ID.
	 * @param STRING|ARRAY $filterValue    The filter value.
	 * @param STRING       $filterType     The filter type.
	 * @param ARRAY        $unused_a       Not used. Kept so callers of the old signature keep working.
	 * @param ARRAY        $unused_b       Not used. Kept so callers of the old signature keep working.
	 * @param STRING       $widgetBlockId  The widget block ID.
	 * @param STRING       $exclude_post_id The post ID to exclude.
	 * @param ARRAY        $adv_filter_data Advanced filter data.
	 * @return STRING The AJAX response.
	 */
	public function pagination_content_return( $blocks, $paged, $blockId, $blockRaw, $blockName, $builder, $postId, $filterValue, $filterType, $unused_a = array(), $unused_b = array(), $widgetBlockId = '', $exclude_post_id = '', $adv_filter_data = array() ) {
		foreach ( $blocks as $key => $value ) {
			if ( $blockName == $value['blockName'] ) {
				// Freeform entries have no attrs; ?? gives the same null comparison as before, without the notice.
				if ( ( $value['attrs']['blockId'] ?? null ) == $blockId ) {
					$objectBlock = $this->ajax_block_object( $blockName );
					if ( ! $objectBlock ) {
						continue;
					}
					$attr        = $objectBlock->get_attributes( true );

					// Fix for grid blocks that do not support load more by default (pagination block)
					if ( isset( $adv_filter_data['notFirstLoad'] ) && $adv_filter_data['notFirstLoad'] ) {
						$attr['notFirstLoad'] = $adv_filter_data['notFirstLoad'];
					}

					$value['attrs']['paged'] = $paged;
					if ( $builder ) {
						$value['attrs']['builder'] = $builder;
					}
					if ( $postId ) {
						$attr['current_post'] = $postId;
						if ( get_post_type( $postId ) == 'ultp_builder' && ! $builder ) {
							$attr['current_post'] = $exclude_post_id;
						}
					}
					$value['attrs'] = $this->ajax_block_attrs( $value['attrs'], 'pagination', $blockName );

					if ( $filterValue ) {
						$value['attrs']['queryTaxValue'] = $adv_filter_data['is_adv'] ? wp_json_encode( $filterValue ) : wp_json_encode( array( $filterValue ) );
						$value['attrs']['queryTax']      = $filterType;
						$value['attrs']['checkFilter']   = true;
						$value['attrs']['filterShow']    = $adv_filter_data['filterShow'];
						$value['attrs']['queryAuthor']   = $adv_filter_data['author'];
						$value['attrs']['queryOrderBy']  = $adv_filter_data['orderby'];
						$value['attrs']['queryOrder']    = $adv_filter_data['order'];
						$value['attrs']['querySearch']   = $adv_filter_data['search'];
						$value['attrs']['queryQuick']    = $adv_filter_data['adv_sort'];

						if ( $adv_filter_data['is_adv'] ) {
							$value['attrs']['queryRelation'] = 'AND';
						}
					}
					// Exclude Current Post From Pagination
					if ( $exclude_post_id ) {
						$queryArr                       = ultimate_post()->decode_json_attr( isset( $value['attrs']['queryExclude'] ) ? $value['attrs']['queryExclude'] : '', false, array() );
						$queryArr                       = is_array( $queryArr ) ? $queryArr : array();
						$queryArr[]                     = array(
							'value' => $exclude_post_id,
							'title' => '',
						);
						$value['attrs']['queryExclude'] = wp_json_encode( $queryArr );
					}
					$attr = array_merge( $attr, $value['attrs'] );
					echo wp_kses( $objectBlock->content( $attr, true ), ultimate_post()->rendered_content_allowed_html() );
					die();
				}
			}
			if ( ! empty( $value['innerBlocks'] ) ) {
				$this->pagination_content_return( $value['innerBlocks'], $paged, $blockId, $blockRaw, $blockName, $builder, $postId, $filterValue, $filterType, $unused_a, $unused_b, $widgetBlockId, $exclude_post_id, $adv_filter_data );
			}
		}
	}

	/**
	 * Attributes of the block an AJAX request renders, after an add-on has had its say.
	 *
	 * Filter `ultp_ajax_block_attrs`: `$attrs` the block's saved attributes, `$context` array with
	 * `request` (`pagination` for the page and next/previous requests, `filter` for the filter
	 * requests) and `block_name`. The default is the saved attributes, unchanged.
	 *
	 * @param array  $attrs      Saved attributes of the block.
	 * @param string $request    `pagination` or `filter`.
	 * @param string $block_name Block name, e.g. `ultimate-post/post-grid-1`.
	 * @return array
	 */
	private function ajax_block_attrs( $attrs, $request, $block_name ) {
		$filtered = apply_filters(
			'ultp_ajax_block_attrs',
			$attrs,
			array(
				'request'    => $request,
				'block_name' => $block_name,
			)
		);
		return is_array( $filtered ) ? $filtered : $attrs;
	}


	/**
	 * Next Preview Callback of the Blocks.
	 *
	 * @since v.1.0.0
	 *
	 * @return STRING The AJAX response.
	 */
	public function ultp_next_prev_callback() {
		if ( ! check_ajax_referer( 'ultp-public-nonce', 'wpnonce', false ) ) {
			$this->refuse_public_nonce();
			return;
		}

		$paged           = isset( $_POST['paged'] ) ? sanitize_text_field( wp_unslash( $_POST['paged'] ) ) : '';
		if ( $paged ) {
			$paged = $this->sanitize_paged( $paged );
		}
		$blockId         = isset( $_POST['blockId'] ) ? sanitize_text_field( wp_unslash( $_POST['blockId'] ) ) : '';
		$postId          = isset( $_POST['postId'] ) ? sanitize_text_field( wp_unslash( $_POST['postId'] ) ) : '';
		$blockRaw        = isset( $_POST['blockName'] ) ? sanitize_text_field( wp_unslash( $_POST['blockName'] ) ) : '';
		$builder         = isset( $_POST['builder'] ) ? sanitize_text_field( wp_unslash( $_POST['builder'] ) ) : '';
		$blockName       = str_replace( '_', '/', $blockRaw );
		$widgetBlockId   = isset( $_POST['widgetBlockId'] ) ? sanitize_text_field( wp_unslash( $_POST['widgetBlockId'] ) ) : '';
		$exclude_post_id = isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '';

		$is_adv     = isset( $_POST['isAdv'] ) ? map_deep( wp_unslash( $_POST['isAdv'] ), 'sanitize_text_field' ) : false;
		$filterValue = isset( $_POST['filterValue'] ) ?
			(
				is_array( $_POST['filterValue'] ) ?
					map_deep( wp_unslash( $_POST['filterValue'] ), 'sanitize_text_field' ) :
					sanitize_text_field( wp_unslash( $_POST['filterValue'] ) )
			) :
			'';

		$filterType  = isset( $_POST['filterType'] ) ? sanitize_text_field( wp_unslash( $_POST['filterType'] ) ) : '';
		$filterShow  = isset( $_POST['filterShow'] ) ? sanitize_text_field( wp_unslash( $_POST['filterShow'] ) ) : false;
		$checkFilter = isset( $_POST['checkFilter'] ) ? sanitize_text_field( wp_unslash( $_POST['checkFilter'] ) ) : false;
		$author      = isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : false;
		$orderby     = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'date';
		$order       = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC';
		$search      = isset( $_POST['search'] ) ? wp_slash( sanitize_text_field( wp_unslash( $_POST['search'] ) ) ) : ''; // Kept slashed: WP_Query unslashes "s" itself.
		$adv_sort    = isset( $_POST['adv_sort'] ) ? sanitize_text_field( wp_unslash( $_POST['adv_sort'] ) ) : '';

		$adv_filter_data = array(
			'is_adv'       => filter_var( $is_adv, FILTER_VALIDATE_BOOLEAN ),
			'filterShow'   => filter_var( $filterShow, FILTER_VALIDATE_BOOLEAN ),
			'checkFilter'  => filter_var( $checkFilter, FILTER_VALIDATE_BOOLEAN ),
			'author'       => $author ? wp_json_encode( $author ) : false,
			'orderby'      => $orderby,
			'order'        => $order,
			'search'       => $search,
			'adv_sort'     => $adv_sort,
			'notFirstLoad' => true,
		);

		if ( $widgetBlockId ) {
			$blocks = parse_blocks( $this->widget_block_content( $widgetBlockId ) );
			$this->pagination_content_return( $blocks, $paged, $blockId, $blockRaw, $blockName, $builder, '', $filterValue, $filterType, array(), array(), $widgetBlockId, '', $adv_filter_data );
		} elseif ( $paged && $blockId && $postId && $blockName ) {
			$post = $this->readable_source_post( $postId );
			if ( ! $post ) {
				$this->refuse_source_post();
			}
			if ( has_blocks( $post->post_content ) ) {
				$blocks = parse_blocks( $post->post_content );
				$this->pagination_content_return( $blocks, $paged, $blockId, $blockRaw, $blockName, $builder, $post->ID, $filterValue, $filterType, array(), array(), '', $exclude_post_id, $adv_filter_data );
			}
		}
	}

	/**
	 * Filter Callback of the Blocks.
	 *
	 * @since v.1.0.0
	 *
	 * @return STRING The AJAX response.
	 */
	public function ultp_filter_callback() {
		if ( ! check_ajax_referer( 'ultp-public-nonce', 'wpnonce', false ) ) {
			$this->refuse_public_nonce();
			return;
		}

		$taxtype = isset( $_POST['taxtype'] ) ? sanitize_text_field( wp_unslash( $_POST['taxtype'] ) ) : '';
		if ( $taxtype ) {
			$blockId                   = isset( $_POST['blockId'] ) ? sanitize_text_field( wp_unslash( $_POST['blockId'] ) ) : '';
			$postId                    = isset( $_POST['postId'] ) ? sanitize_text_field( wp_unslash( $_POST['postId'] ) ) : '';
			$taxonomy                  = isset( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : '';
			$blockRaw                  = isset( $_POST['blockName'] ) ? sanitize_text_field( wp_unslash( $_POST['blockName'] ) ) : '';
			$blockName                 = str_replace( '_', '/', $blockRaw );
			$widgetBlockId             = isset( $_POST['widgetBlockId'] ) ? sanitize_text_field( wp_unslash( $_POST['widgetBlockId'] ) ) : '';
			$toReturn                  = array();

			if ( ! $widgetBlockId ) {
				$post = $this->readable_source_post( $postId );
				if ( ! $post ) {
					$this->refuse_source_post();
				}
			}

			if ( $widgetBlockId ) {
				$blocks = parse_blocks( $this->widget_block_content( $widgetBlockId ) );
				$data   = $this->filter_content_return( $blocks, $blockId, $blockRaw, $blockName, $taxtype, $taxonomy, $postId, $toReturn, $widgetBlockId, array() );
			} elseif ( has_blocks( $post->post_content ) ) {
				$blocks = parse_blocks( $post->post_content );
				$data   = $this->filter_content_return( $blocks, $blockId, $blockRaw, $blockName, $taxtype, $taxonomy, $postId, $toReturn, '', array() );
			}
			return wp_send_json_success(
				array(
					'filteredData' => $data,
				)
			);
		}
	}

	/**
	 * Advanced Filter Callback of the Blocks.
	 *
	 * @since v.3.2.4
	 *
	 * @return STRING The AJAX response.
	 */
	public function ultp_adv_filter_callback() {
		if ( ! check_ajax_referer( 'ultp-public-nonce', 'wpnonce', false ) ) {
			$this->refuse_public_nonce();
			return;
		}

		$blockId = isset( $_POST['blockId'] ) ? sanitize_text_field( wp_unslash( $_POST['blockId'] ) ) : '';
		$postId  = isset( $_POST['postId'] ) ? sanitize_text_field( wp_unslash( $_POST['postId'] ) ) : '';

		$taxonomy   = isset( $_POST['taxonomy'] ) ? map_deep( wp_unslash( $_POST['taxonomy'] ), 'sanitize_text_field' ) : '[]';
		$builder_id = isset( $_POST['builder_id'] ) ? sanitize_text_field( wp_unslash( $_POST['builder_id'] ) ) : '';
		$author     = isset( $_POST['author'] ) ? map_deep( wp_unslash( $_POST['author'] ), 'sanitize_text_field' ) : false;
		$orderby    = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : ''; // default orderbyt title requested from support
		$order      = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC';
		$search     = isset( $_POST['search'] ) ? wp_slash( sanitize_text_field( wp_unslash( $_POST['search'] ) ) ) : ''; // Kept slashed: WP_Query unslashes "s" itself.
		$adv_sort   = isset( $_POST['adv_sort'] ) ? sanitize_text_field( wp_unslash( $_POST['adv_sort'] ) ) : '';

		$adv_filter_data = array(
			'is_adv'      => true,
			'filterShow'  => true,
			'checkFilter' => true,
			'author'      => $author ? wp_json_encode( $author ) : false,
			'orderby'     => $orderby,
			'order'       => $order,
			'search'      => $search,
			'adv_sort'    => $adv_sort,
			'builder'     => $builder_id,
		);

		$blockRaw      = isset( $_POST['blockName'] ) ? sanitize_text_field( wp_unslash( $_POST['blockName'] ) ) : '';
		$blockName     = str_replace( '_', '/', $blockRaw );
		$widgetBlockId = isset( $_POST['widgetBlockId'] ) ? sanitize_text_field( wp_unslash( $_POST['widgetBlockId'] ) ) : '';
		$toReturn      = array();

		if ( ! $widgetBlockId ) {
			$post = $this->readable_source_post( $postId );
			if ( ! $post ) {
				$this->refuse_source_post();
			}
		}

		if ( $widgetBlockId ) {
			$blocks = parse_blocks( $this->widget_block_content( $widgetBlockId ) );
			$data   = $this->filter_content_return( $blocks, $blockId, $blockRaw, $blockName, 'multiTaxonomy', $taxonomy, $postId, $toReturn, $widgetBlockId, $adv_filter_data );
		} elseif ( has_blocks( $post->post_content ) ) {
			$blocks = parse_blocks( $post->post_content );
			$data   = $this->filter_content_return( $blocks, $blockId, $blockRaw, $blockName, 'multiTaxonomy', $taxonomy, $postId, $toReturn, '', $adv_filter_data );
		}
		return wp_send_json_success(
			array(
				'filteredData' => $data,
			)
		);
	}

	/**
	 * Pagination of the Blocks.
	 *
	 * @since v.1.0.0
	 *
	 * @return STRING The AJAX response.
	 */
	public function ultp_pagination_callback() {
		if ( ! check_ajax_referer( 'ultp-public-nonce', 'wpnonce', false ) ) {
			$this->refuse_public_nonce();
			return;
		}

		$paged = isset( $_POST['paged'] ) ? sanitize_text_field( wp_unslash( $_POST['paged'] ) ) : '';
		if ( $paged ) {
			$paged = $this->sanitize_paged( $paged );
			$blockId         = isset( $_POST['blockId'] ) ? sanitize_text_field( wp_unslash( $_POST['blockId'] ) ) : '';
			$postId          = isset( $_POST['postId'] ) ? sanitize_text_field( wp_unslash( $_POST['postId'] ) ) : '';
			$blockRaw        = isset( $_POST['blockName'] ) ? sanitize_text_field( wp_unslash( $_POST['blockName'] ) ) : '';
			$builder         = isset( $_POST['builder'] ) ? sanitize_text_field( wp_unslash( $_POST['builder'] ) ) : '';
			$blockName       = str_replace( '_', '/', $blockRaw );
			$widgetBlockId   = isset( $_POST['widgetBlockId'] ) ? sanitize_text_field( wp_unslash( $_POST['widgetBlockId'] ) ) : '';
			$exclude_post_id = isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '';

			if ( ! $widgetBlockId ) {
				$post = $this->readable_source_post( $postId );
				if ( ! $post ) {
					$this->refuse_source_post();
				}
			}

			$is_adv = isset( $_POST['isAdv'] ) ? map_deep( wp_unslash( $_POST['isAdv'] ), 'sanitize_text_field' ) : false;

			$filterValue = array();

			if ( isset( $_POST['filterValue'] ) ) {
				// The frontend sends an array (jQuery parses a JSON data attribute), or a single
				// value such as a term slug. A JSON array is decoded; any other non-empty string
				// (a plain slug is not valid JSON) is kept as the sanitized string, as ultp_next_prev_callback() does.
				if ( is_array( $_POST['filterValue'] ) ) {
					$filterValue = map_deep( wp_unslash( $_POST['filterValue'] ), 'sanitize_text_field' );
				} elseif ( is_string( $_POST['filterValue'] ) ) {
					$raw_filter = sanitize_text_field( wp_unslash( $_POST['filterValue'] ) );
					$decoded    = json_decode( $raw_filter, true );
					if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
						$filterValue = ultimate_post()->ultp_rest_sanitize_params( $decoded );
					} elseif ( '' !== $raw_filter ) {
						$filterValue = $raw_filter;
					}
				}
			}

			$filterType  = isset( $_POST['filterType'] ) ? sanitize_text_field( wp_unslash( $_POST['filterType'] ) ) : '';
			$filterShow  = isset( $_POST['filterShow'] ) ? sanitize_text_field( wp_unslash( $_POST['filterShow'] ) ) : false;
			$checkFilter = isset( $_POST['checkFilter'] ) ? sanitize_text_field( wp_unslash( $_POST['checkFilter'] ) ) : false;
			$author      = isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : false;
			$orderby     = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'title'; // default orderbyt title requested from support
			$order       = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC';
			$search      = isset( $_POST['search'] ) ? wp_slash( sanitize_text_field( wp_unslash( $_POST['search'] ) ) ) : ''; // Kept slashed: WP_Query unslashes "s" itself.
			$adv_sort    = isset( $_POST['adv_sort'] ) ? sanitize_text_field( wp_unslash( $_POST['adv_sort'] ) ) : '';

			$adv_filter_data = array(
				'is_adv'      => filter_var( $is_adv, FILTER_VALIDATE_BOOLEAN ),
				'filterShow'  => filter_var( $filterShow, FILTER_VALIDATE_BOOLEAN ),
				'checkFilter' => filter_var( $checkFilter, FILTER_VALIDATE_BOOLEAN ),
				'author'      => $author ? wp_json_encode( $author ) : false,
				'orderby'     => $orderby,
				'order'       => $order,
				'search'      => $search,
				'adv_sort'    => $adv_sort,
			);

			if ( $widgetBlockId ) {
				$blocks = parse_blocks( $this->widget_block_content( $widgetBlockId ) );
				$this->pagination_content_return( $blocks, $paged, $blockId, $blockRaw, $blockName, $builder, '', $filterValue, $filterType, array(), array(), $widgetBlockId, '', $adv_filter_data );
			} elseif ( has_blocks( $post->post_content ) ) {
				$blocks = parse_blocks( $post->post_content );
				$this->pagination_content_return( $blocks, $paged, $blockId, $blockRaw, $blockName, $builder, $post->ID, $filterValue, $filterType, array(), array(), '', $exclude_post_id, $adv_filter_data );
			}
		}
	}

	/**
	 * Share Count callback.
	 *
	 * @since v.1.0.0
	 *
	 * @return void
	 */
	public function ultp_share_count_callback() {
		if ( ! check_ajax_referer( 'ultp-public-nonce', 'wpnonce', false ) ) {
			$this->refuse_public_nonce();
			return;
		}

		$post_id = isset( $_POST['postId'] ) && is_scalar( $_POST['postId'] ) ? absint( $_POST['postId'] ) : 0; // An array (postId[]=1) is no post ID.

		// Validate post ID
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => 'Invalid post ID' ) );
			return;
		}

		// Check if post exists
		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => 'Post not found' ) );
			return;
		}

		// Block private posts
		if ( $post->post_status === 'private' && ! current_user_can( 'read_private_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Private post' ) );
			return;
		}

		// Block draft/pending posts
		if ( in_array( $post->post_status, array( 'draft', 'pending' ), true )
		&& ! current_user_can( 'edit_post', $post->ID ) ) {
			wp_send_json_error( array( 'message' => 'Not allowed' ) );
			return;
		}

		// Block password protected posts
		if ( post_password_required( $post ) ) {
			wp_send_json_error( array( 'message' => 'Password protected' ) );
			return;
		}

		// Only allow published posts to have share count updated
		if ( $post->post_status !== 'publish' ) {
			wp_send_json_error( array( 'message' => 'Post not published' ) );
			return;
		}

		// Rate limiting - 60 second cooldown per user per post
		$user_identifier = is_user_logged_in() ? 'user_' . get_current_user_id() : 'ip_' . sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$transient_key   = 'ultp_share_' . $post_id . '_' . md5( $user_identifier );

		if ( get_transient( $transient_key ) ) {
			return; // Already shared within last 60 seconds
		}

		set_transient( $transient_key, true, 60 ); // 60 seconds cooldown

		// Always increment by 1 from database - ignore POST shareCount
		$current_count = get_post_meta( $post_id, '_ultp_share_count', true );
		$current_count = $current_count ? absint( $current_count ) : 0;
		$new_count     = $current_count + 1;

		update_post_meta( $post_id, '_ultp_share_count', $new_count );
	}

	/**
	 * Filter Callback of the Blocks.
	 *
	 * @since v.1.0.0
	 *
	 * @param ARRAY        $blocks         The blocks array.
	 * @param STRING       $blockId        The block ID.
	 * @param STRING       $blockRaw       The raw block name.
	 * @param STRING       $blockName      The block name.
	 * @param STRING       $taxtype       The taxonomy type.
	 * @param STRING|ARRAY $taxonomy      The taxonomy.
	 * @param STRING       $postId       The post ID.
	 * @param ARRAY        $toReturn     The array to return.
	 * @param STRING       $widgetBlockId The widget block ID.
	 * @param ARRAY        $adv_filter_data The advanced filter data.
	 * @param ARRAY        $unused_a      Not used. Kept so callers of the old signature keep working.
	 * @param ARRAY        $unused_b      Not used. Kept so callers of the old signature keep working.
	 * @return ARRAY
	 */
	public function filter_content_return( $blocks, $blockId, $blockRaw, $blockName, $taxtype, $taxonomy, $postId, &$toReturn, $widgetBlockId = '', $adv_filter_data = array(), $unused_a = array(), $unused_b = array() ) {
		foreach ( $blocks as $key => $value ) {
			if ( ( $value['blockName'] ?? '' ) === 'core/shortcode' ) {
				if ( preg_match( '/id="([^"]+)"/', $value['innerHTML'] ?? '', $matches ) ) {
					$template_post = $this->readable_source_post( $matches[1] );
					// A template that embeds itself, directly or through another, would recurse forever.
					if ( $template_post && ! empty( $template_post->post_content ) && ! isset( $this->expanding_templates[ $template_post->ID ] ) ) {
						$template_blocks = parse_blocks( $template_post->post_content );
						// recurse into the expanded blocks instead
						$this->expanding_templates[ $template_post->ID ] = true;
						$this->filter_content_return( $template_blocks, $blockId, $blockRaw, $blockName, $taxtype, $taxonomy, $postId, $toReturn, $widgetBlockId, $adv_filter_data, $unused_a, $unused_b );
						unset( $this->expanding_templates[ $template_post->ID ] );
						continue; // skip the shortcode block itself
					}
				}
        	}
			if ( $blockName == $value['blockName'] ) {
				// Freeform entries have no attrs; ?? gives the same null comparison as before, without the notice.
				if ( ( $value['attrs']['blockId'] ?? null ) == $blockId ) {
					$objectBlock = $this->ajax_block_object( $blockName );
					if ( ! $objectBlock ) {
						continue;
					}
					$attr        = $objectBlock->get_attributes( true );
					if ( $taxonomy ) {

						if ( isset( $adv_filter_data['is_adv'] ) && $adv_filter_data['is_adv'] ) {
							$value['attrs']['queryTaxValue'] = wp_json_encode( $taxonomy );
							$value['attrs']['queryRelation'] = 'AND';
							$value['attrs']['queryAuthor']   = $adv_filter_data['author'];
							$value['attrs']['queryOrder']    = $adv_filter_data['order'];
							$value['attrs']['querySearch']   = $adv_filter_data['search'];
							$value['attrs']['queryQuick']    = $adv_filter_data['adv_sort'];
							if ( ! empty( $adv_filter_data['orderby'] ) ) {
								$value['attrs']['queryOrderBy'] = $adv_filter_data['orderby'];
							}
						} else {
							$value['attrs']['queryTaxValue'] = wp_json_encode( array( $taxonomy ) );
						}

						$value['attrs']['queryTax'] = $taxtype;
						$value['attrs']['ajaxCall'] = true;
					}
					if ( isset( $value['attrs']['queryNumber'] ) ) {
						$value['attrs']['queryNumber'] = $value['attrs']['queryNumber'];
					}

					$value['attrs'] = $this->ajax_block_attrs( $value['attrs'], 'filter', $blockName );

					if ( ! empty( $adv_filter_data['builder'] ) ) {
						$value['attrs']['builder'] = $adv_filter_data['builder'];
					}

					$attr = array_merge( $attr, $value['attrs'] );
					// Settings an add-on may supply for this block (see the block's addon_attributes()).
					if ( method_exists( $objectBlock, 'addon_attributes' ) ) {
						$attr = $objectBlock->addon_attributes( $attr );
					}

					$filter_attributes = array();

					$filter_attributes['isAdv']         = isset( $adv_filter_data['is_adv'] ) ? $adv_filter_data['is_adv'] : false;
					$filter_attributes['queryTaxValue'] = $filter_attributes['isAdv'] ? wp_json_encode( $taxonomy ) : $taxonomy;
					$filter_attributes['queryTax']      = $taxtype;

					if ( $filter_attributes['isAdv'] ) {
						$filter_attributes['queryAuthor']  = $adv_filter_data['author'];
						$filter_attributes['queryOrder']   = $adv_filter_data['order'];
						$filter_attributes['querySearch']  = $adv_filter_data['search'];
						$filter_attributes['queryQuick']   = $adv_filter_data['adv_sort'];
						$filter_attributes['queryOrderBy'] = 
						! empty( $adv_filter_data['orderby'] ) ? $adv_filter_data['orderby'] : ( $value['attrs']['queryOrderBy'] ?? '' );
					}
					$pagination = $this->pagination_for_filter( $attr, $postId, $blockRaw, $filter_attributes );
					$toReturn = array(
						'blocks'         => $objectBlock->content( $attr, true ),
						'notFound'       => isset( $attr['notFoundMessage'] ) ? $attr['notFoundMessage'] : '',
						'pagination'     => $pagination['pagination_content'],
						'data_attrs'     => $pagination['data_attrs'],
						'data_all_attrs'     => $pagination['data_attrs'],
						'paginationType' => $attr['paginationType'],
						'paginationShow' => $attr['paginationShow'],
					);
				}
			}
			if ( ! empty( $value['innerBlocks'] ) ) {
				$this->filter_content_return( $value['innerBlocks'], $blockId, $blockRaw, $blockName, $taxtype, $taxonomy, $postId, $toReturn, $widgetBlockId, $adv_filter_data, $unused_a, $unused_b );
			}
		}
		return $toReturn;
	}

	/**
	 * The PostX block class an AJAX request names, or null when there is none to render.
	 *
	 * The name comes from a public request: a block without its own render class (such as
	 * post-pagination), or one without content(), would otherwise be a fatal error.
	 *
	 * @since v.5.1.2
	 *
	 * @param STRING $block_name Block name, e.g. ultimate-post/post-grid-1.
	 * @return object|null
	 */
	private function ajax_block_object( $block_name ) {
		$class = '\ULTP\blocks\\' . str_replace( ' ', '_', ucwords( str_replace( array( 'ultimate-post/', '-' ), array( '', ' ' ), (string) $block_name ) ) );
		if ( ! class_exists( $class ) || ! method_exists( $class, 'get_attributes' ) || ! method_exists( $class, 'content' ) ) {
			return null;
		}
		return new $class();
	}

	/**
	 * A page number from an AJAX request, as a whole number from 1 up.
	 *
	 * Anything that is not a number counts as page 1. The upper limit keeps the SQL offset
	 * (page x posts per page) inside an integer; no site has that many pages.
	 *
	 * @since v.5.1.2
	 *
	 * @param MIXED $paged Page number from the request.
	 * @return int
	 */
	private function sanitize_paged( $paged ) {
		return min( max( 1, absint( $paged ) ), 1000000 );
	}

	/**
	 * Content of a widget block, or an empty string when the widget is not there.
	 *
	 * @since v.5.1.2
	 *
	 * @param STRING $widget_block_id Widget block ID from the request.
	 * @return STRING
	 */
	private function widget_block_content( $widget_block_id ) {
		$widgets = get_option( 'widget_block' );
		if ( is_array( $widgets ) && isset( $widgets[ $widget_block_id ]['content'] ) && is_string( $widgets[ $widget_block_id ]['content'] ) ) {
			return $widgets[ $widget_block_id ]['content'];
		}
		return '';
	}

	/**
	 * Pagination for filter callback
	 *
	 * @since v.2.8.9
	 *
	 * @param ARRAY  $attr .
	 * @param STRING $postId .
	 * @param STRING $blockRaw .
	 * @param ARRAY  $filter_attributes .
	 * @return STRING
	 */
	public function pagination_for_filter( $attr, $postId, $blockRaw, $filter_attributes ) {
		$attr['queryNumber'] = ultimate_post()->get_post_number( 4, $attr['queryNumber'], $attr['queryNumPosts'] );
		$recent_posts        = new \WP_Query( ultimate_post()->get_query( $attr ) );
		$pageNum             = ultimate_post()->get_page_number( $attr, $recent_posts->found_posts );

		$datasets  = ultimate_post()->get_adv_data_attrs( null, $filter_attributes );
		$datasets .= ' data-for="ultp-block-' . sanitize_html_class( $attr['blockId'] ) . '" ';

		$wraper_after = '';
		$style        = $pageNum == 1 ? 'style="display:none"' : '';

		if ( $attr['paginationType'] == 'loadMore' ) {
			$wraper_after     .= '<div ' . $style . ' class="ultp-loadmore "' . '>';
				$wraper_after .= wp_kses( '<span class="ultp-loadmore-action" tabindex="0" role="button" data-pages="' . esc_attr( $pageNum ) . '" data-pagenum="1" data-blockid="' . esc_attr( $attr['blockId'] ) . '" data-blockname="' . esc_attr( $blockRaw ) . '" data-postid="' . esc_attr( $postId ) . '" ' . ultimate_post()->get_builder_attr( $attr['queryType'] ) . $datasets . '>', ultimate_post()->rendered_content_allowed_html() ) . esc_html( isset( $attr['loadMoreText'] ) ? $attr['loadMoreText'] : 'Load More' ) . ' <span class="ultp-spin">' . wp_kses( ultimate_post()->get_svg_icon( 'refresh' ), ultimate_post()->rendered_content_allowed_html() ) . '</span></span>';
			$wraper_after     .= '</div>';
		} elseif ( $attr['paginationType'] == 'navigation' ) {
			$wraper_after     .= wp_kses( '<div ' . $style . '  class="ultp-next-prev-wrap" data-pages="' . esc_attr( $pageNum ) . '" data-pagenum="1" data-blockid="' . esc_attr( $attr['blockId'] ) . '" data-blockname="' . esc_attr( $blockRaw ) . '" data-postid="' . esc_attr( $postId ) . '" ' . ultimate_post()->get_builder_attr( $attr['queryType'] ) . $datasets . '>', ultimate_post()->rendered_content_allowed_html() );
				$wraper_after .= wp_kses( ultimate_post()->next_prev(), ultimate_post()->rendered_content_allowed_html() );
			$wraper_after     .= '</div>';
		} elseif ( $attr['paginationType'] == 'pagination' ) {
			$wraper_after .= wp_kses( '<div class="ultp-pagination-wrap' . ( $attr['paginationAjax'] ? ' ultp-pagination-ajax-action' : '' ) . '" data-paged="1" data-blockid="' . esc_attr( $attr['blockId'] ) . '" data-postid="' . esc_attr( $postId ) . '" data-pages="' . esc_attr( $pageNum ) . '" data-blockname="' . esc_attr( $blockRaw ) . '" ' . ultimate_post()->get_builder_attr( $attr['queryType'] ) . $datasets . '>', ultimate_post()->rendered_content_allowed_html() );

			$wraper_after .= wp_kses(
				ultimate_post()->pagination(
					$pageNum,
					$attr['paginationNav'],
					$attr['paginationText'],
					$attr['paginationAjax'],
					isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
					$attr['blockId']
				),
				ultimate_post()->rendered_content_allowed_html()
			);

			$wraper_after .= '</div>';
		}
		wp_reset_postdata();

		// queryQuick
		return array(
			'pagination_content' => $wraper_after,
			'data_attrs'        => wp_json_encode( array(
				'paged'       => 1,
				'blockid'     => $attr['blockId'],
				'postid'      => $postId,
				'pages'       => $pageNum,
				'blockname'   => $blockRaw,
				'filter_value' => isset( $filter_attributes['queryTaxValue'] ) ? $filter_attributes['queryTaxValue'] : '',
				'filter_type'  => isset( $filter_attributes['queryTax'] ) ? $filter_attributes['queryTax'] : '',
				'filter_author' => isset( $filter_attributes['queryAuthor'] ) ? $filter_attributes['queryAuthor'] : '',
				'filter_order'  => isset( $filter_attributes['queryOrder'] ) ? $filter_attributes['queryOrder'] : 'DESC',
				'filter_orderby'  => isset( $filter_attributes['queryOrderBy'] ) ? $filter_attributes['queryOrderBy'] : '',
				'filter_search'  => isset( $filter_attributes['querySearch'] ) ? $filter_attributes['querySearch'] : '',
				'filter_queryQuick'  => isset( $filter_attributes['queryQuick'] ) ? $filter_attributes['queryQuick'] : '',
				'filter_for'    => 'ultp-block-' . $attr['blockId'],
			)),
		);
	}

	/**
	 * The post a public block AJAX request may read blocks from.
	 *
	 * The pagination and filter handlers are open to visitors and take the source
	 * post ID from the request, so the ID is untrusted: without this check any
	 * draft, private, scheduled or trashed post could be rendered by guessing its
	 * ID and a block ID. Neither the public nonce nor the block ID authorises the
	 * read.
	 *
	 * Allowed:
	 *  - published posts of a viewable post type, unless password protected and the
	 *    visitor has not entered the password;
	 *  - published PostX builder and saved templates, block templates, template
	 *    parts and synced patterns, which are not viewable types but are rendered
	 *    into public pages;
	 *  - anything else only to a user who may read (non-published) or edit
	 *    (non-viewable type) that post.
	 *
	 * A revision stands in for its parent: the block-theme lookup in
	 * get_page_post_id() can return one. The parent is checked and its current
	 * content used, so an autosave never leaks unpublished edits.
	 *
	 * @since v.5.1.2
	 *
	 * @param MIXED $post_id Post ID from the request.
	 * @return \WP_Post|null The post, or null when this request may not read it.
	 */
	private function readable_source_post( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return null;
		}

		$post = get_post( $post_id );
		if ( $post && 'revision' === $post->post_type ) {
			$post = $post->post_parent ? get_post( $post->post_parent ) : null;
		}
		if ( ! $post || post_password_required( $post ) ) {
			return null;
		}

		if ( 'publish' !== $post->post_status ) {
			return $this->current_user_can_post( 'read_post', $post ) ? $post : null;
		}

		/**
		 * Filter `ultp_ajax_source_post_types`: post types that are not publicly viewable
		 * but whose published posts are rendered into public pages, such as a theme
		 * builder's layout type. Visitors may page and filter PostX blocks stored in them.
		 *
		 * @param array $types Post type names.
		 */
		$rendered_types = (array) apply_filters( 'ultp_ajax_source_post_types', array( 'ultp_builder', 'ultp_templates', 'wp_template', 'wp_template_part', 'wp_block' ) );
		if ( is_post_type_viewable( $post->post_type ) || in_array( $post->post_type, $rendered_types, true ) ) {
			return $post;
		}

		return $this->current_user_can_post( 'edit_post', $post ) ? $post : null;
	}

	/**
	 * Whether the current user has a post capability on one post.
	 *
	 * map_meta_cap() cannot answer for an unregistered post type (an addon's type while the
	 * addon is off) and raises _doing_it_wrong, so for those it asks for edit_others_posts,
	 * which is what core falls back to.
	 *
	 * @since v.5.1.2
	 *
	 * @param string   $cap  'read_post' or 'edit_post'.
	 * @param \WP_Post $post The post.
	 * @return bool
	 */
	private function current_user_can_post( $cap, $post ) {
		if ( ! post_type_exists( $post->post_type ) ) {
			return current_user_can( 'edit_others_posts' );
		}
		return current_user_can( $cap, $post->ID );
	}

	/**
	 * End a block AJAX request whose source post the visitor may not read.
	 *
	 * 403 so the front-end AJAX error path runs; a 200 would be inserted into the
	 * block as markup. The code differs from 'ultp_invalid_nonce' so the client
	 * does not fetch a new nonce and retry.
	 *
	 * @since v.5.1.2
	 *
	 * @return void
	 */
	private function refuse_source_post() {
		wp_send_json_error(
			array(
				'code'    => 'ultp_source_forbidden',
				'message' => __( 'This content is not available.', 'ultimate-post' ),
			),
			403
		);
	}

	/**
	 * End a front-end AJAX request whose nonce failed.
	 *
	 * Each handler checks its own nonce with check_ajax_referer( 'ultp-public-nonce', 'wpnonce', false )
	 * and calls this on failure. It fails with a machine-readable 403 rather than the bare `return` these
	 * handlers used to do. A bare return lets admin-ajax emit "0" at HTTP 200,
	 * which the client cannot tell apart from a real response — ultp_next_prev's
	 * success handler treated that "0" as markup and rendered it into the block.
	 *
	 * The 'ultp_invalid_nonce' code is a contract with assets/js/ultp.js: on
	 * seeing it the client discards its cached nonce, fetches a fresh one and
	 * retries once. A nonce legitimately expires while a tab is open (WordPress
	 * nonces last 12-24h, tabs last longer), so this is a routine condition and
	 * not an attack signal. See ultp_get_nonce_callback() for what this nonce
	 * does and does not protect.
	 *
	 * @since v.5.1.0 As verify_public_nonce(); since 5.1.2 the check itself sits in each handler.
	 *
	 * @return void The request ends.
	 */
	private function refuse_public_nonce() {
		wp_send_json_error(
			array(
				'code'    => 'ultp_invalid_nonce',
				'message' => __( 'Security token expired. Please try again.', 'ultimate-post' ),
			),
			403
		);
	}

	/**
	 * Vends the front-end AJAX nonce.
	 *
	 * Public by design: full-page caching makes a nonce embedded in HTML stale for
	 * most visitors, so front-end blocks fetch one at runtime instead.
	 *
	 * SECURITY: because this endpoint is public, 'ultp-public-nonce' is NOT a CSRF
	 * control on the front end — anyone can request one. It is a request-shape
	 * check only. Any nopriv handler that changes state MUST carry its own
	 * authorisation; see ultp_share_count_callback() for the pattern (post status,
	 * capability and rate-limit checks, none of which depend on the nonce).
	 *
	 * This endpoint vends the PUBLIC nonce only. Admin routes verify the separate
	 * 'ultp-admin-nonce', which is never vended here and is always paired with a
	 * capability check. Do not merge the two.
	 *
	 * @since v.5.0.6
	 *
	 * @return STRING The AJAX response.
	 */
	public function ultp_get_nonce_callback() {
		nocache_headers();
		wp_send_json_success(
			array(
				'nonce' => wp_create_nonce( 'ultp-public-nonce' ),
			)
		);
	}
}

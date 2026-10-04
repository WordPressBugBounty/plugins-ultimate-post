<?php
namespace ULTP\blocks;

use WP_Taxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Class Advanced_Filter
 */
class Advanced_Filter {
	/**
	 * Select filter types rendered by PostX itself.
	 *
	 * Further types are added through the `ultp_advanced_filter_select_types` filter.
	 *
	 * @var array
	 */
	private $select_types = array(
		'category',
		'tags',
		'author',
		'order',
		'order_by',
	);

	/**
	 * Order Value for select
	 *
	 * @var array
	 */
	public function get_order_options() {
		return array(
			array(
				'id'   => 'DESC',
				'name' => __( 'DESC', 'ultimate-post' ),
			),
			array(
				'id'   => 'ASC',
				'name' => __( 'ASC', 'ultimate-post' ),
			),
		);
	}

	/**
	 * Order by Value for select
	 *
	 * @var array
	 */
	public $order_by = array(
		array(
			'id'   => 'date',
			'name' => 'Created Date',
		),
		array(
			'id'   => 'modified',
			'name' => 'Date Modified',
		),
		array(
			'id'   => 'title',
			'name' => 'Title',
		),
		array(
			'id'   => 'menu_order',
			'name' => 'Menu Order',
		),
		array(
			'id'   => 'rand',
			'name' => 'Random',
		),
		// array(
		// 'id'   => 'post__in',
		// 'name' => 'Post In',
		// ),
		array(
			'id'   => 'comment_count',
			'name' => 'Number of Comments',
		),
	);

	/**
	 * Filter_select_options
	 *
	 * @param MIXED | $options (ARRAY).
	 * @param  mixed | $filter (ARRAY).
	 * @return array
	 */
	private function filter_select_options( $options, $filter ) {
		$res = array();
		$map = array();

		foreach ( $options as $option ) {
			$map[ $option['id'] ] = $option['name'];
		}

		foreach ( $filter as $f ) {

			if ( isset( $map[ $f ] ) ) {
				$res[] = array(
					'id'   => strtolower( $f ),
					'name' => $map[ $f ],
				);
			}
		}

		return $res;
	}

	/**
	 * Advanced_Filter constructor.
	 */
	public function __construct() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		add_action( 'init', array( $this, 'register' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get_select_attributes
	 *
	 * @return array
	 */
	public function get_select_attributes() {
		return array(
			'advanceId'           => '',
			'blockId'             => '',
			'advanceCss'          => '',
			'filterStyle'         => 'dropdown',
			'filterValues'        => '["_all"]',
			'dropdownOptionsType' => 'all',
			'dropdownOptions'     => '["_all"]',
			'allText'             => 'All',
			'sPlaceholderText'    => 'Search...',
			'searchEnabled'       => false,
			'inlineMultiSelect'   => true,
		);
	}

	/**
	 * Get_clear_attributes
	 *
	 * @return array
	 */
	public function get_clear_attributes() {
		return array(
			'advanceId'           => '',
			'blockId'             => '',
			'clearButtonText'     => 'Clear Filters',
			'clearButtonPosition' => 'normal',
			'cAlign'              => 'margin-bottom: inherit',
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/filter-select',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'select_content' ),
			)
		);

		register_block_type(
			'ultimate-post/filter-search-adv',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'search_content' ),
			)
		);

		register_block_type(
			'ultimate-post/filter-clear',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'clear_content' ),
			)
		);
	}

	/**
	 * Get_button_data
	 *
	 * @param  mixed $type string.
	 * @param  mixed $ids string.
	 * @param  mixed $post_types string.
	 * @param  mixed $allText string.
	 * @param  mixed $option string.
	 * @param  mixed $order array.
	 * @return array
	 */
	public function get_button_data( $type, $data_ids, $post_types = '', $allText = 'All', $option = '', $order = array() ) {

		$res = array();

		if ( in_array( '_all', $data_ids ) ) {
			$res['_all'] = $allText;
			// $ids = array_filter($ids, function ($item) {
			// return $item != '_all';
			// });
		}

		$ids = implode( ',', $data_ids );

		switch ( $type ) {
			case 'category':
				$args = array(
					'taxonomy'   => 'category',
					'hide_empty' => false,
				);

				if ( 'all' !== $option && ! empty( $ids ) && '_all' !== $ids ) {
					$args['include'] = wp_parse_id_list( $ids );
					$args['orderby'] = 'include';
				}

				$categories = get_terms( $args );

				if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
					foreach ( $categories as $category ) {
						$res['_all']            = $allText;
						$res[ $category->slug ] = $category->name;
					}
				}
				break;
			case 'tags':
				$args = array(
					'taxonomy'   => 'post_tag',
					'hide_empty' => false,
				);
				if ( 'all' !== $option && ! empty( $ids ) && $ids !== '_all' ) {
					$args['include'] = wp_parse_id_list( $ids );
					$args['orderby'] = 'include';
				}

				$tags = get_terms( $args );
				if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
					foreach ( $tags as $tag ) {
						$res[ $tag->slug ] = $tag->name;
					}
				}
				break;
			case 'author':
				if ( ! empty( $ids ) && $ids !== '_all' ) {
					$authors = get_users(
						array(
							'per_page' => -1,
							'role__in' => array( 'administrator', 'editor', 'author', 'contributor' ),
							'include'  => $ids,
						)
					);

					foreach ( $authors as $author ) {
						$res[ $author->ID ] = $author->display_name;
					}
				}

				break;
			case 'order':
				foreach ( $this->order as $order ) {
					$res[ $order['id'] ] = $order['name'];
				}
				break;
			case 'order_by':
				$orders = explode( ',', $ids );
				foreach ( $this->order_by as $order_by ) {
					foreach ( $orders as $order ) {
						if ( $order_by['id'] === $order ) {
							$res[ $order ] = $order_by['name'];
						}
					}
				}
				break;
			default:
				/**
				 * Inline (button) options for a select filter type PostX does not render itself.
				 *
				 * @param array        $res        Options keyed by value. Holds '_all' when that button is enabled.
				 * @param string       $type       Filter type.
				 * @param array        $data_ids   Values chosen in the block settings.
				 * @param array|string $post_types Post types the filter applies to, or ''.
				 * @param string       $option     'all' or 'specific'.
				 */
				$res = apply_filters( 'ultp_advanced_filter_button_data', $res, $type, $data_ids, $post_types, $option );
				break;
		}

		return is_array( $res ) ? $res : array();
	}

	/**
	 * Inserts all option depending on condition
	 *
	 * @param array  $arr array.
	 * @param array  $item all item.
	 * @param int    $idx all item idx.
	 * @param string $mode mode.
	 * @return void
	 */
	private function maybe_insert_all_option( &$arr, &$item, $idx, $mode ) {
		if ( 'all' === $mode ) {
			array_unshift( $arr, $item );
		} elseif ( ! empty( $item ) && false !== $idx ) {
			array_splice( $arr, $idx, 0, array( $item ) );
		}
	}

	public function get_select_data( $type, $all_text, $post_types = '', $specific = array(), $mode = 'all', $order = array() ) {
		$all               = null;
		$all_idx           = array_search( '_all', $specific, true );
		$filtered_specific = $specific;

		if ( false !== $all_idx || 'all' === $mode ) {
			$all = array(
				'id'   => '_all',
				'name' => $all_text,
			);

			$filtered_specific = array_filter(
				$specific,
				function ( $item ) {
					return '_all' !== $item;
				}
			);
		}

		switch ( $type ) {

			case 'category':
				$categories = array();

				if ( 'all' === $mode || ( 'specific' === $mode && count( $filtered_specific ) > 0 ) ) {
					$categories = get_categories(
						array(
							'per_page' => -1,

							'include'  => $filtered_specific,
							'orderby'  => 'include',
						)
					);

					$categories = array_map(
						function ( $category ) {
							return array(
								'id'   => $category->slug,
								'name' => $category->name,
							);
						},
						$categories
					);
				}

				$this->maybe_insert_all_option( $categories, $all, $all_idx, $mode );

				return $categories;

			case 'tags':
				$tags = array();

				if ( 'all' === $mode || ( 'specific' === $mode && count( $filtered_specific ) > 0 ) ) {
					$tags = get_tags(
						array(
							'per_page' => -1,
							'include'  => $filtered_specific,
						)
					);
					$tags = array_map(
						function ( \WP_Term $tag ) {
							return array(
								'id'   => $tag->slug,
								'name' => $tag->name,
							);
						},
						$tags
					);
				}

				$this->maybe_insert_all_option( $tags, $all, $all_idx, $mode );

				return $tags;

			case 'author':
				$authors = array();

				if ( 'all' === $mode || ( 'specific' === $mode && count( $filtered_specific ) > 0 ) ) {

					$authors = get_users(
						array(
							'per_page'     => -1,
							'include'      => $filtered_specific,
							'role__not_in' => array( 'subscriber', 'translator' ),
						)
					);

					$authors = array_map(
						function ( $author ) {
							return array(
								'id'   => $author->ID,
								'name' => $author->display_name,
							);
						},
						$authors
					);
				}

				$this->maybe_insert_all_option( $authors, $all, $all_idx, $mode );

				return $authors;

			case 'order':
				$filtered_specific = array_map( 'strtoupper', $filtered_specific );

				return 'specific' === $mode ?
				$this->filter_select_options( $order, $filtered_specific )
				: $order;

			case 'order_by':
				return 'specific' === $mode ?
				$this->filter_select_options( $this->order_by, $filtered_specific )
				: $this->order_by;

			default:
				/**
				 * Dropdown options for a select filter type PostX does not render itself.
				 *
				 * Return a list of array( 'id' => ..., 'name' => ... ) items, without the
				 * '_all' item: PostX inserts that. Return null to render no options at all.
				 *
				 * @param array|null   $data       Options. Null when no callback handles the type.
				 * @param string       $type       Filter type.
				 * @param array|string $post_types Post types the filter applies to, or ''.
				 * @param array        $specific   Values chosen in the block settings, without '_all'.
				 * @param string       $mode       'all' or 'specific'.
				 */
				$data = apply_filters( 'ultp_advanced_filter_select_data', null, $type, $post_types, $filtered_specific, $mode );

				if ( ! is_array( $data ) ) {
					return array();
				}

				$this->maybe_insert_all_option( $data, $all, $all_idx, $mode );

				return $data;
		}
	}

	public function select_content( $attr ) {
		$block_name = 'filter-select';
		$attr       = wp_parse_args( $attr, $this->get_select_attributes() );

		/**
		 * Select filter types that render on the front end.
		 *
		 * @param array $types Types PostX renders itself.
		 */
		$types = apply_filters( 'ultp_advanced_filter_select_types', $this->select_types );

		if ( ! empty( $attr['type'] ) && ! in_array( $attr['type'], (array) $types, true ) ) {
			return '';
		}

		$post_types = isset( $attr['postTypes'] ) ? $attr['postTypes'] : '';

		if ( is_string( $post_types ) ) {
			$post_types = json_decode( $post_types, true );
		}

		if ( is_array( $post_types ) && in_array( 'customPostType', $post_types, true ) ) {
			$post_types = json_decode( $attr['queryPostType'][0] ?? '[]', true );
			if ( is_string( $post_types ) ) {
				$post_types = json_decode( $post_types, true );
			}
		}

		$attr['blockId']      = ultimate_post()->sanitize_attr( $attr, 'blockId', 'sanitize_html_class', 'missing_block_id' );
		$attr['allText']      = ultimate_post()->sanitize_attr( $attr, 'allText', 'sanitize_text_field', 'missing_block_id' );
		$current_page_post_id = ( isset( $attr['currentPostId'] ) && $attr['currentPostId'] ) ? sanitize_html_class( $attr['currentPostId'] ) : ultimate_post()->get_page_post_id( $attr['blockId'] );

		if ( 'inline' === $attr['filterStyle'] ) {
			$inline_values = json_decode( $attr['filterValues'], true );

			if ( ! is_array( $inline_values ) ) {
				return '';
			}

			$data = $this->get_button_data( $attr['type'], $inline_values, $post_types, $attr['allText'], $attr['dropdownOptionsType'], $this->get_order_options() );

			$btn_wrapper_attrs = get_block_wrapper_attributes(
				array(
					'class'               => 'ultp-block-' . $attr['blockId'] . ' ultp-filter-button',
					'role'                => 'button',
					'data-blockId'        => $attr['blockId'],
					'data-is-active'      => 'false',
					'data-builder'        => ultimate_post()->get_builder_attr( 'archiveBuilderFilter' ),
					'data-current-postid' => $current_page_post_id,
					'data-filter-label'   => isset( $attr['allText'] ) ? $attr['allText'] : 'All',
				)
			);

			ob_start();
			?>
			<div class="ultp-block-<?php echo esc_attr( $attr['blockId'] ); ?>-wrapper" data-multiselect="<?php echo isset( $attr['inlineMultiSelect'] ) && $attr['inlineMultiSelect'] ? 'true' : 'false'; ?>"	>

				<?php

				foreach ( $data as $key => $value ) :
					?>
					<?php
					if ( is_array( $value ) ) {
						$name = $value['name'];
						$tax  = isset( $value['taxonomy'] ) ? $value['taxonomy'] : null;
					} else {
						$name = $value;
						$tax  = null;
					}
					?>
				<div <?php echo $btn_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns pre-escaped attribute HTML; escaping it again would corrupt it. ?> <?php echo null !== $tax ? 'data-tax="' . esc_attr( $tax ) . '"' : ''; ?> data-selected="<?php echo esc_attr( $key ); ?>" data-type="<?php echo esc_attr( $attr['type'] ); ?>">
					<?php echo esc_html( $name ); ?>
				</div>
				<?php endforeach ?>
			</div>
				<?php

				$content = ob_get_clean();
				return $content;
		} elseif ( 'dropdown' === $attr['filterStyle'] ) {

			$mode     = 'all';
			$specific = array();

			if ( 'specific' === $attr['dropdownOptionsType'] && isset( $attr['dropdownOptions'] ) ) {
				$specific_data = json_decode( stripslashes( $attr['dropdownOptions'] ) );
				$mode          = 'specific';
				if ( is_array( $specific_data ) ) {
					$specific = array_map(
						function ( $item ) {
							return sanitize_text_field( $item );
						},
						$specific_data
					);
				}
			}

			$data = $this->get_select_data( $attr['type'], $attr['allText'], $post_types, $specific, $mode, $this->get_order_options() );

			$def_value = ! empty( current( $data ) ) ? current( $data ) : null;

			$wrapper_attrs = get_block_wrapper_attributes(
				array(
					'class'               => 'ultp-block-' . $attr['blockId'] . ' ultp-filter-select',
					'data-selected'       => isset( $def_value['id'] ) ? $def_value['id'] : 0,
					'data-type'           => $attr['type'],
					'data-blockId'        => $attr['blockId'],
					'aria-expanded'       => 'false',
					'aria-label'          => 'Select Filter (' . $attr['type'] . ')',
					'data-builder'        => ultimate_post()->get_builder_attr( 'archiveBuilderFilter' ),
					'data-current-postid' => $current_page_post_id,
					'data-filter-label'   => isset( $def_value['name'] ) ? $def_value['name'] : 'All',
				)
			);

			ob_start();
			?>
			<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns pre-escaped attribute HTML; escaping it again would corrupt it. ?>>
				<div class="ultp-filter-select-field ultp-filter-select__parent">
					<span class="ultp-filter-select-field-selected ultp-filter-select__parent-inner">
				<?php echo esc_html( isset( $def_value['name'] ) ? $def_value['name'] : '' ); ?>
					</span>
					<span class="ultp-filter-select-field-icon">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34.1 19.95"><path d="M17.05 19.949.601 3.499a2.05 2.05 0 0 1 2.9-2.9l13.551 13.55L30.603.599a2.05 2.05 0 0 1 2.9 2.9Z"/></svg>
					</span>
				</div>
				<ul style="display: none;" class="ultp-filter-select-options ultp-filter-select__dropdown">

				<?php
				if ( isset( $attr['searchEnabled'] ) && $attr['searchEnabled'] ) :
					?>
						<input 
							type="search" 
							class="ultp-filter-select-search" 
							placeholder="<?php echo esc_attr( isset( $attr['sPlaceholderText'] ) ? $attr['sPlaceholderText'] : '' ); ?>" 
						/>
					<?php endif; ?>

						<?php foreach ( $data as $item ) : ?>
						<li class="ultp-filter-select__dropdown-inner" <?php echo isset( $item['taxonomy'] ) ? 'data-tax="' . esc_attr( $item['taxonomy'] ) . '"' : ''; ?> data-id="<?php echo esc_attr( $item['id'] ); ?>">
							<?php echo esc_html( $item['name'] ); ?>
						</li>
					<?php endforeach; ?>

				</ul>
			</div>

				<?php
				$content = ob_get_clean();
				return $content;
		}

		return '';
	}

		/**
		 * Search_content
		 *
		 * The search filter is rendered by PostX Pro through the
		 * `ultp_advanced_filter_search_content` filter; PostX itself outputs nothing.
		 *
		 * @param  mixed $attr array.
		 * @return string
		 */
	public function search_content( $attr ) {
		/**
		 * Front-end markup for the Advanced Filter search block.
		 *
		 * @param string $content Markup. Empty by default.
		 * @param array  $attr    Block attributes, unsanitized.
		 */
		$content = apply_filters( 'ultp_advanced_filter_search_content', '', (array) $attr );

		return is_string( $content ) ? $content : '';
	}

		/**
		 * Clear_content
		 *
		 * @param  mixed $attr array.
		 * @return array
		 */
	public function clear_content( $attr ) {
		$block_name = 'filter-clear';
		$attr = wp_parse_args( $attr, $this->get_clear_attributes() );

		$attr['blockId']         = ultimate_post()->sanitize_attr( $attr, 'blockId', 'sanitize_html_class', 'missing_block_id' );
		$attr['clearButtonText'] = ultimate_post()->sanitize_attr( $attr, 'clearButtonText', 'sanitize_text_field' );

		$wrapper_attrs = get_block_wrapper_attributes(
			array(
				'class'        => 'ultp-block-' . $attr['blockId'] . ' ultp-filter-clear ultp-filter-clear-button ',
				'data-blockid' => $attr['blockId'],
			)
		);

		$selected_filter_wrapper_attr = get_block_wrapper_attributes(
			array(
				'class' => 'ultp-block-' . $attr['blockId'] . ' ultp-filter-clear ultp-filter-clear-template',
				'style' => 'display: none;',
			)
		);

		ob_start();
		?>

		<div class="ultp-block-<?php echo sanitize_html_class( $attr['blockId'] ); ?>-wrapper">
			<div <?php echo $selected_filter_wrapper_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns pre-escaped attribute HTML; escaping it again would corrupt it. ?>>
				<div class="ultp-selected-filter">
					<span class="ultp-selected-filter-icon" role="button">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 31.1 31.25"><path stroke="rgba(0,0,0,0)" strokeMiterlimit="10" d="M27.1 30.153 15.549 18.601 4 30.153a2.05 2.05 0 0 1-2.9-2.9l11.551-11.55L1.1 4.153a2.05 2.05 0 0 1 2.9-2.9l11.549 11.552L27.1 1.253a2.05 2.05 0 0 1 2.9 2.9l-11.553 11.55L30 27.253a2.05 2.05 0 1 1-2.9 2.9Z"/></svg>
					</span>
					<span class="ultp-selected-filter-text">
					</span>
				</div>
			</div>

			<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns pre-escaped attribute HTML; escaping it again would corrupt it. ?>>
				<button class="ultp-clear-button">
				<?php echo esc_html( $attr['clearButtonText'] ); ?>
				</button>
			</div>
		</div>

			<?php
			$content = ob_get_clean();
			return $content;
	}
}

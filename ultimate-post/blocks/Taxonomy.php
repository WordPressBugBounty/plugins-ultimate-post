<?php
namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Taxonomy {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'blockId'               => '',
			'previewImg'            => '',
			// Layout
			'layout'                => '1',
			// Query Setting
			'taxType'               => 'regular',
			'taxSlug'               => 'category',
			'taxValue'              => '[]',
			'queryNumber'           => 6,
			// General Setting
			'columns'               => (object) array( 'lg' => '1' ),
			'rowGap'                => (object) array(
				'lg'   => '20',
				'unit' => 'px',
			),
			'titleShow'             => true,
			'headingShow'           => true,
			'excerptShow'           => false,
			'countShow'             => true,
			'openInTab'             => false,
			'notFoundMessage'       => 'No Taxonomy Found.',

			// Heading Setting/Style
			'headingText'           => 'Post Taxonomy',
			'headingURL'            => '',
			'headingBtnText'        => 'View More',
			'headingStyle'          => 'style9',
			'headingTag'            => 'h2',
			'headingAlign'          => 'left',
			'subHeadingShow'        => false,
			'subHeadingText'        => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ut sem augue. Sed at felis ut enim dignissim sodales.',
			'titleTag'              => 'span',
			'contentTitleAlign'     => 'left',
			'titlePosition'         => true,
			'seperatorTaxTitleLink' => admin_url( 'edit-tags.php?taxonomy=category' ),

			// Content Setting/Style
			'excerptLimit'          => 30,

			// Image Setting/Style
			'imgCrop'               => ( ultimate_post()->get_setting( 'disable_image_size' ) == 'yes' ? 'full' : 'ultp_layout_landscape' ),

			// Separator
			'separatorShow'         => false,

			// Custom Wrapper Style
			'seperatorTaxLink'      => admin_url( 'edit-tags.php?taxonomy=category' ),
			'TaxAnimation'          => 'none',
			'advanceId'             => '',
			'advanceZindex'         => '',
			'hideExtraLarge'        => false,
			'hideTablet'            => false,
			'hideMobile'            => false,
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/ultp-taxonomy',
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

		$block_name    = 'ultp-taxonomy';
		$wraper_before = $wraper_after = $post_loop = '';
		$recent_posts  = ultimate_post()->get_category_data( ultimate_post()->decode_json_attr( $attr['taxValue'], false, array() ), $attr['queryNumber'], $attr['taxType'], $attr['taxSlug'] );

		if ( ! empty( $recent_posts ) ) {

			$attr['className']    = isset( $attr['className'] ) && $attr['className'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
			$attr['imgCrop']      = isset( $attr['imgCrop'] ) && $attr['imgCrop'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['imgCrop'] ) : '';
			$attr['advanceId']    = isset( $attr['advanceId'] ) ? sanitize_html_class( $attr['advanceId'] ) : '';
			$attr['blockId']      = isset( $attr['blockId'] ) ? sanitize_html_class( $attr['blockId'] ) : '';
			$attr['titleTag']     = in_array( $attr['titleTag'], ultimate_post()->ultp_allowed_block_tags() ) ? $attr['titleTag'] : 'span';
			// The layout is layout 1 unless ultp_taxonomy_layout gives another.
			$attr['layout']       = sanitize_html_class( apply_filters( 'ultp_taxonomy_layout', '1', $attr ) );
			$attr['TaxAnimation'] = sanitize_html_class( $attr['TaxAnimation'] );

			$wraper_before         .= '<div ' . ( $attr['advanceId'] ? 'id="' . esc_attr( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( $attr['blockId'] ) . ' ' . ( $attr['className'] ? esc_attr( $attr['className'] ) : '' ) . '">';
				$wraper_before     .= '<div class="ultp-block-wrapper">';
					$wraper_before .= wp_kses( ultimate_post()->postx_loading(), ultimate_post()->rendered_content_allowed_html() ); // Loading

			if ( $attr['headingShow'] ) {
				$wraper_before     .= '<div class="ultp-heading-filter">';
					$wraper_before .= '<div class="ultp-heading-filter-in">';
						include ULTP_PATH . 'blocks/template/heading.php'; // Heading
					$wraper_before .= '</div>';
				$wraper_before     .= '</div>';
			}

					$wraper_before     .= '<div class="ultp-block-items-wrap">';
						$wraper_before .= '<ul class="ultp-taxonomy-items ' . ( isset( $attr['TaxAnimation'] ) ? ' ultp-taxonomy-animation-' . esc_attr( $attr['TaxAnimation'] ) : '' ) . ' ultp-taxonomy-column-' . esc_attr( sanitize_html_class( json_decode( wp_json_encode( $attr['columns'] ), true )['lg'] ) ) . ' ultp-taxonomy-layout-' . esc_attr( $attr['layout'] ) . '">';

			foreach ( $recent_posts as $value ) {
				$post_loop .= '<li class="ultp-block-item ultp-taxonomy-item">';

				// Colour of the name ("ultp_taxonomy_name_style") and of the count ("ultp_taxonomy_count_style").
				$name_style  = ultimate_post()->filtered_style_value( apply_filters( 'ultp_taxonomy_name_style', '', $attr, $value ) );
				$count_style = ultimate_post()->filtered_style_value( apply_filters( 'ultp_taxonomy_count_style', '', $attr, $value ) );
				$name        = ( $attr['titleShow'] && $value['name'] ) ? '<' . tag_escape( $attr['titleTag'] ) . ' class="ultp-taxonomy-name" ' . ( null !== $name_style ? 'style="' . esc_attr( $name_style ) . '"' : '' ) . '>' . esc_html( $value['name'] ) . '</' . tag_escape( $attr['titleTag'] ) . '>' : '';
				$count       = ( $attr['countShow'] && $value['count'] ) ? '<span class="ultp-taxonomy-count" ' . ( null !== $count_style ? 'style="' . esc_attr( $count_style ) . '"' : '' ) . '>' . esc_html( $value['count'] ) . '</span>' : '';
				$excerpt    = ( $attr['excerptShow'] && $value['desc'] ) ? '<div class="ultp-taxonomy-desc">' . wp_kses_post( $value['desc'] ) . '</div>' : '';

				// Other layouts are drawn through ultp_taxonomy_item_html; layout 1 is below.
				$item = ultimate_post()->esc_filtered_markup(
					apply_filters(
						'ultp_taxonomy_item_html',
						'',
						$attr,
						$value,
						array(
							'name'    => $name,
							'count'   => $count,
							'excerpt' => $excerpt,
						)
					),
					''
				);
				if ( '' === $item ) {
					$item = '<a href="' . esc_url( $value['url'] ) . '" >' . $name . $count . $excerpt . '</a>';
				}
				$post_loop .= wp_kses( $item, ultimate_post()->rendered_content_allowed_html() );
				$post_loop .= '</li>';
			}

						$wraper_after .= '</ul>';
					$wraper_after     .= '</div>';
				$wraper_after         .= '</div>';
			$wraper_after             .= '</div>';
			wp_reset_postdata();
		} else {
			$wraper_before .= wp_kses( ultimate_post()->get_no_result_found_html( $attr['notFoundMessage'] ), ultimate_post()->rendered_content_allowed_html() );
		}

		return $noAjax ? $post_loop : $wraper_before . $post_loop . $wraper_after;
	}
}

<?php
namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Next_Previous {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}
	public function get_attributes() {

		return array(
			'blockId'            => '',

			/*
			============================
				Next Preview Settings
			============================*/
			'layout'             => 'style1',
			'headingEnable'      => true,
			'imageShow'          => true,
			'titleShow'          => true,
			'dateShow'           => true,
			'navDivider'         => false,
			'iconShow'           => true,

			/*
			============================
				Navigation
			============================*/
			'titlePosition'      => true,
			// Previous
			'prevHeadText'       => 'Previous Post',
			// Next
			'nextHeadText'       => 'Next Post',

			/*
			============================
				Divider Setting/Style
			============================*/
			'dividerBorderShape' => true,

			/*
			============================
				Arrow Icon
			============================*/
			'arrowIconStyle'     => 'Angle2',

			/*
			============================
				Advance Setting
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
			'ultimate-post/next-previous',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}
	public function content( $attr, $noAjax ) {
		$attr = wp_parse_args( $attr, $this->get_attributes() );

		$block_name     = 'next-previous';
		$wrapper_before = $wrapper_after = $content = $next_prev_img = '';

		if ( $attr['imageShow'] ) {
			$next_prev_img .= 'next-prev-img';
		}

		$attr['className']      = isset( $attr['className'] ) && $attr['className'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
		$attr['align']          = isset( $attr['align'] ) && $attr['align'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
		$attr['advanceId']      = isset( $attr['advanceId'] ) ? sanitize_html_class( $attr['advanceId'] ) : '';
		$attr['blockId']        = isset( $attr['blockId'] ) ? sanitize_html_class( $attr['blockId'] ) : '';
		$attr['arrowIconStyle'] = sanitize_html_class( $attr['arrowIconStyle'] );
		$attr['layout']         = sanitize_html_class( $attr['layout'] );
		$allowed_html_tags      = ultimate_post()->ultp_allowed_html_tags();
		$attr['prevHeadText']   = wp_kses( $attr['prevHeadText'], $allowed_html_tags );
		$attr['nextHeadText']   = wp_kses( $attr['nextHeadText'], $allowed_html_tags );

		$arrowLeft  = '<span class="ultp-icon ultp-icon-' . esc_attr( $attr['arrowIconStyle'] ) . '">' . wp_kses( ultimate_post()->get_svg_icon( 'left' . $attr['arrowIconStyle'] ), ultimate_post()->rendered_content_allowed_html() ) . '</span>';
		$arrowRight = '<span class="ultp-icon ultp-icon-' . esc_attr( $attr['arrowIconStyle'] ) . '">' . wp_kses( ultimate_post()->get_svg_icon( 'right' . $attr['arrowIconStyle'] ), ultimate_post()->rendered_content_allowed_html() ) . '</span>';

		$wrapper_before      .= '<div ' . ( $attr['advanceId'] ? 'id="' . esc_attr( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( $attr['blockId'] ) . ( $attr['className'] ? ' ' . esc_attr( $attr['className'] ) : '' ) . '' . ( $attr['align'] ? ' align' . esc_attr( $attr['align'] ) : '' ) . '">';
			$wrapper_before  .= '<div class="ultp-block-wrapper">';
				$content     .= '<div class="ultp-block-nav ' . esc_attr( $next_prev_img ) . '">';
					$content .= wp_kses( $this->renderHtml( $attr, $arrowLeft, $arrowRight, true ), ultimate_post()->rendered_content_allowed_html() );
		if ( $attr['navDivider'] && $attr['dividerBorderShape'] ) {
			$content .= '<span class="ultp-divider"></span>';
		}
					$content .= wp_kses( $this->renderHtml( $attr, $arrowLeft, $arrowRight, false ), ultimate_post()->rendered_content_allowed_html() );
				$content     .= '</div>';
			$wrapper_after   .= '</div>';
		$wrapper_after       .= '</div>';

		return $wrapper_before . $content . $wrapper_after;
	}

	public function renderHtml( $attr, $arrowLeft, $arrowRight, $left ) {
		$output    = '';
		$post_data = $left ? get_previous_post() : get_next_post();
		if ( $post_data ) {
			// Pieces of the side link, built here so that a replacement (ultp_next_previous_html) gets the same values as the default.
			$ctx = array(
				'left'        => $left,
				'post'        => $post_data,
				'layout'      => $attr['layout'],
				'arrow_left'  => $arrowLeft,
				'arrow_right' => $arrowRight,
				'heading'     => wp_kses( $left ? $attr['prevHeadText'] : $attr['nextHeadText'], ultimate_post()->ultp_allowed_html_tags() ),
				'image'       => '',
				'date'        => '',
				'title'       => '',
			);
			if ( $attr['imageShow'] && has_post_thumbnail( $post_data->ID ) ) {
				$ctx['image'] = wp_kses( get_the_post_thumbnail( $post_data->ID ), ultimate_post()->rendered_content_allowed_html() );
			}
			if ( $attr['dateShow'] ) {
				$ctx['date'] = esc_html( get_the_date( get_option( 'date_format' ), $post_data->ID ) );
			}
			if ( $attr['titleShow'] ) {
				$ctx['title'] = wp_kses_post( get_the_title( $post_data->ID ) );
			}

			// Whole side link of another arrangement. Null means "not answered": the default below is drawn.
			$answer = apply_filters( 'ultp_next_previous_html', null, $attr, $ctx );
			if ( null !== $answer ) {
				return ultimate_post()->esc_filtered_markup( $answer, '' );
			}

			$image_data = '<div class="ultp-nav-img">' . $ctx['image'] . '</div>';
			$output    .= '<a class="' . esc_attr( $left ? 'ultp-nav-block-prev ultp-nav-prev-style1' : 'ultp-nav-block-next ultp-nav-next-style1' ) . '" href="' . esc_url( get_permalink( $post_data->ID ) ) . '">';
			if ( $left && $attr['iconShow'] ) {
				$output .= $arrowLeft;
			}
			$output .= '<div class="ultp-nav-inside">';
			if ( $attr['headingEnable'] && ! $attr['titlePosition'] ) {
				$output .= '<div class=' . ( $left ? 'ultp-prev-title' : 'ultp-next-title' ) . ' >' . $ctx['heading'] . '</div>';
			}
			$output .= '<div class="ultp-nav-inside-container">';
			if ( $left ) {
				$output .= $image_data;
			}
			$output .= '<div class="ultp-nav-text-content">';
			if ( $attr['headingEnable'] && $attr['titlePosition'] ) {
				$output .= '<div class=' . ( $left ? 'ultp-prev-title' : 'ultp-next-title' ) . ' >' . $ctx['heading'] . '</div>';
			}
			if ( $attr['dateShow'] ) {
				$output .= '<div class="ultp-nav-date">' . $ctx['date'] . '</div>';
			}
			if ( $attr['titleShow'] ) {
				$output .= '<div class="ultp-nav-title">' . $ctx['title'] . '</div>';
			}
			$output .= '</div>';
			if ( ! $left ) {
				$output .= '<span>' . $image_data . '</span>';
			}
			$output .= '</div>';
			$output .= '</div>';
			if ( ! $left && $attr['iconShow'] ) {
				$output .= $arrowRight;
			}
			$output .= '</a>';
		}
		return $output;
	}
}

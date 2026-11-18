<?php

namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Advanced_Search {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'advanceId'               => '',
			'blockId'                 => '',
			// General Content Setting
			'searchFormStyle'         => 'input1',
			'searchPopup'             => false,
			'searchPopupIconStyle'    => 'popup-icon1',
			'searchAjaxEnable'        => true,
			'searchResultLayout'      => 'res',
			'searchnoresult'          => 'No Results Found',

			// Popup Canvas
			'popupAnimation'          => 'popup',

			'searchPopupPosition'     => 'right',
			'popupCloseIconSeparator' => 'Close Icon Style',
			'windowpopupHeading'      => true,
			'windowpopupText'         => 'Search The Query',

			// Search Button Style
			'searchBtnEnable'         => false,
			'btnNewTab'               => false,
			'enableSearchPage'        => true,
			'searchButtonText'        => 'Search',
			'searchBtnText'           => true,
			'searchBtnIcon'           => true,
			'searchIconAfterText'     => false,

			// Search Form Style
			'searchInputPlaceholder'  => 'Search...',

			// Search Result Settings
			'resExcerptEnable'        => true,
			'resCatEnable'            => true,
			'resAuthEnable'           => true,
			'resDateEnable'           => true,
			'resImageEnable'          => true,
			'resExcerptLimit'         => '25',

			// Search Result Settings
			'moreResultsbtn'          => true,
			'moreResultPosts'         => 3,
			'moreResultsText'         => 'View More Results',
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/advanced-search',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr, $noAjax ) {
		$wraper_before = $wraper_after = $content = $popup_content = '';
		$block_name    = 'advanced-search';

		$attr = wp_parse_args( $attr, $this->get_attributes() );

		$attr['blockId']              = sanitize_html_class( $attr['blockId'] );
		$attr['className']            = isset( $attr['className'] ) && $attr['className'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
		$attr['align']                = isset( $attr['align'] ) && $attr['align'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
		$attr['popupAnimation']       = sanitize_html_class( $attr['popupAnimation'] );
		$attr['btnNewTab']            = $attr['btnNewTab'] == true;
		$attr['searchAjaxEnable']     = $attr['searchAjaxEnable'] == true;
		$attr['enableSearchPage']     = $attr['enableSearchPage'] == true;
		$attr['resImageEnable']       = $attr['resImageEnable'] == true;
		$attr['resAuthEnable']        = $attr['resAuthEnable'] == true;
		$attr['resDateEnable']        = $attr['resDateEnable'] == true;
		$attr['resExcerptEnable']     = $attr['resExcerptEnable'] == true;
		$attr['moreResultsbtn']       = $attr['moreResultsbtn'] == true;
		$attr['resCatEnable']         = $attr['resCatEnable'] == true;
		$attr['resExcerptLimit']      = sanitize_html_class( $attr['resExcerptLimit'] );
		$attr['moreResultPosts']      = sanitize_html_class( $attr['moreResultPosts'] );
		$attr['searchPopupPosition']  = sanitize_html_class( $attr['searchPopupPosition'] );
		$attr['searchFormStyle']      = sanitize_html_class( $attr['searchFormStyle'] );
		$attr['searchPopupIconStyle'] = sanitize_html_class( $attr['searchPopupIconStyle'] );

		$allowed_html_tags              = ultimate_post()->ultp_allowed_html_tags();
		$attr['windowpopupText']        = wp_kses( $attr['windowpopupText'], $allowed_html_tags );
		$attr['searchnoresult']         = wp_kses( $attr['searchnoresult'], $allowed_html_tags );
		$attr['moreResultsText']        = wp_kses( $attr['moreResultsText'], $allowed_html_tags );
		$attr['searchButtonText']       = wp_kses( $attr['searchButtonText'], $allowed_html_tags );
		$attr['searchInputPlaceholder'] = wp_kses( $attr['searchInputPlaceholder'], $allowed_html_tags );

		// Extra data attributes of the search form; an add-on may supply them (escaped by the callback).
		$data_var       = apply_filters( 'ultp_advanced_search_data', '', $attr );
		$data_var       = is_string( $data_var ) ? $data_var : '';
		$wraper_before .= '<div ' . ( $attr['advanceId'] ? 'id="' . esc_attr( sanitize_html_class( $attr['advanceId'] ) ) . '" ' : '' ) . ' class="wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( sanitize_html_class( $attr['blockId'] ) ) . '' . ( $attr['align'] ? ' align' . esc_attr( $attr['align'] ) : '' ) . '' . ( $attr['className'] ? ' ' . esc_attr( $attr['className'] ) : '' ) . '">';
		$wraper_before .= '<div class="ultp-block-wrapper">';
		$content       .= wp_kses(
			'<div class="ultp-search-container ultp-search-frontend' . ( $attr['searchPopup'] ? ' ultp-search-animation-' . esc_attr( $attr['popupAnimation'] ) : '' ) . '"  data-ajax="' . esc_attr( $attr['searchAjaxEnable'] ) . '" data-gosearch="' . esc_attr( $attr['enableSearchPage'] ) . '" data-enablenewtab="' . esc_attr( $attr['btnNewTab'] ) . '" data-blockId="' . esc_attr( $attr['blockId'] ) . '"
                    data-image="' . esc_attr( $attr['resImageEnable'] ) . '"  data-author="' . esc_attr( $attr['resAuthEnable'] ) . '" data-date="' . esc_attr( $attr['resDateEnable'] ) . '" data-excerpt="' . esc_attr( $attr['resExcerptEnable'] ) . '" data-excerptLimit="' . esc_attr( $attr['resExcerptLimit'] ) . '" data-allresult="' . esc_attr( $attr['moreResultsbtn'] ) . '" data-catEnable="' . esc_attr( $attr['resCatEnable'] ) . '"  data-postno="' . esc_attr( $attr['moreResultPosts'] ) . '" ' . ( $attr['searchPopup'] ? 'data-popuptype="' . esc_attr( $attr['popupAnimation'] ) . '" ' : '' ) . ' ' . ( $attr['searchAjaxEnable'] ? 'data-noresultext="' . esc_attr( $attr['searchnoresult'] ) . '" ' : '' ) . ' ' . ( $attr['moreResultsbtn'] ? 'data-viewmoretext="' . esc_attr( $attr['moreResultsText'] ) . '" ' : '' ) . ' data-popupposition="' . esc_attr( $attr['searchPopupPosition'] ) . '" ' . $data_var . '>',
			ultimate_post()->rendered_content_allowed_html()
		);
		if ( $attr['searchPopup'] ) {
			$content .= wp_kses( $this->renderSearchButton( $attr['searchPopupIconStyle'], $attr['searchBtnText'], $attr['searchBtnIcon'], $attr['searchButtonText'] ), ultimate_post()->rendered_content_allowed_html() );
		}
		if ( $attr['searchPopup'] == false ) {
			$content .= wp_kses( $this->renderSearchForm( $attr['searchFormStyle'], $attr['searchBtnText'], $attr['searchBtnIcon'], $attr ), ultimate_post()->rendered_content_allowed_html() );
		}
		if ( $attr['searchPopup'] ) {
			$popup_content .= '<div class="ultp-search-canvas">';

			$popup_content .= '<div class="ultp-canvas-header">';
			if ( $attr['windowpopupHeading'] ) {
				$popup_content .= '<div class="ultp-search-popup-heading">' . wp_kses( $attr['windowpopupText'], $allowed_html_tags ) . '</div>';
			}
			$popup_content .= wp_kses( $this->renderSearchForm( $attr['searchFormStyle'], $attr['searchBtnText'], $attr['searchBtnIcon'], $attr ), ultimate_post()->rendered_content_allowed_html() );
			$popup_content .= '</div>';
			$popup_content .= '<div class="ultp-popupclose-icon">' . wp_kses( ultimate_post()->get_svg_icon( 'close_line' ), ultimate_post()->rendered_content_allowed_html() ) . '</div>';
			$popup_content .= '</div>';
			if ( $attr['popupAnimation'] == 'popup' ) {
				$content .= $popup_content;
			} else {
				$content .= $popup_content;
			}
		}
		$content      .= '</div>';
		$wraper_after .= '</div>';
		$wraper_after .= '</div>';
		return $wraper_before . $content . $wraper_after;
	}

	public function renderSearchButton( $style, $textEnable = true, $iconEnable = true, $searchButtonText = '' ) {
		$textShow = $textEnable && $style != 'popup-icon1';
		$result   = '';
		$result  .= '<div class=" ' . ( $style ? 'ultp-searchpopup-icon ultp-searchbtn-' . esc_attr( $style ) : 'ultp-search-button' ) . '">';
		$result  .= ( $style || $iconEnable ) ? wp_kses( ultimate_post()->get_svg_icon( 'search_line' ), ultimate_post()->rendered_content_allowed_html() ) : '';
		$result  .= $textShow ? '<span class="ultp-search-button__text"> ' . wp_kses( $searchButtonText, ultimate_post()->ultp_allowed_html_tags() ) . ' </span>' : '';
		$result  .= '</div>';
		return $result;
	}

	public function renderSearchForm( $searchFormStyle, $searchBtnText, $searchBtnIcon, $attr ) {
		$dt          = is_search() ? get_search_query( true ) : '';
		$searchForm  = '';
		$searchForm .= '<div class="ultp-searchform-content ultp-searchform-' . esc_attr( $searchFormStyle ) . '">';
		$searchForm .= '<div class="ultp-search-inputwrap"> <input type="text" value="' . esc_attr( $dt ) . '" class="ultp-searchres-input"  placeholder="' . esc_attr( $attr['searchInputPlaceholder'] ) . '"/> <span class="ultp-search-clear" data-blockid="' . esc_attr( $attr['blockId'] ) . '">' . wp_kses( ultimate_post()->get_svg_icon( 'close_line' ), ultimate_post()->rendered_content_allowed_html() ) . '</span> </div>';
		$searchForm .= wp_kses( $this->renderSearchButton( false, $attr['searchBtnText'], $attr['searchBtnIcon'], $attr['searchButtonText'] ), ultimate_post()->rendered_content_allowed_html() );
		$searchForm .= '</div>';
		return $searchForm;
	}
}

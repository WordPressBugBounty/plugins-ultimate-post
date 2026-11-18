<?php
defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside block render methods; variables are local.

$attr['headingStyle'] = sanitize_html_class( $attr['headingStyle'] );
$attr['headingAlign'] = sanitize_html_class( $attr['headingAlign'] );
$allowed_html_tags    = ultimate_post()->ultp_allowed_html_tags();

$finalHeadingText    = $attr['headingText'];
$finalHeadingURL     = $attr['headingURL'];
$finalSubHeadingText = $attr['subHeadingText'];

// Dynamic Content
if ( ultimate_post()->is_dc_active( $attr ) && isset( $attr['dc'] ) ) {

	$dc_text_val = \ULTP\DCService::get_dc_content_for_rich_text( $attr );
	$text        = isset( $dc_text_val['0'] ) ? $dc_text_val['0'] : '';
	$url         = isset( $dc_text_val['1'] ) ? $dc_text_val['1'] : '';

	// [ $text, $url ] = \ULTP\DCService::get_dc_content_for_rich_text( $attr ); array destructure is not support php 7

	if ( ! empty( $url ) ) {
		$finalHeadingURL = $url;
	}

	$finalHeadingText = \ULTP\DCService::replace( $finalHeadingText, $text );
}

$finalHeadingText    = wp_kses( $finalHeadingText, $allowed_html_tags );
$finalHeadingURL     = esc_url( $finalHeadingURL );
$finalSubHeadingText = wp_kses( $finalSubHeadingText, $allowed_html_tags );

$attr['headingBtnText'] = wp_kses( $attr['headingBtnText'], $allowed_html_tags );
$attr['headingTag']     = in_array( $attr['headingTag'], ultimate_post()->ultp_allowed_block_tags() ) ? $attr['headingTag'] : 'h2';

if ( $attr['headingShow'] ) {
	$new_tab        = isset( $attr['openInTab'] ) && $attr['openInTab'] == true ? 'target="_blank"' : '';
	$wraper_before .= '<div class="ultp-heading-wrap ultp-heading-' . esc_attr( $attr['headingStyle'] ) . ' ultp-heading-' . esc_attr( $attr['headingAlign'] ) . '">';
	if ( $finalHeadingURL ) {
		$wraper_before .= '<' . tag_escape( $attr['headingTag'] ) . ' class="ultp-heading-inner"><a href="' . esc_url( $finalHeadingURL ) . '" ' . $new_tab . '><span>' . wp_kses( $finalHeadingText, $allowed_html_tags ) . '</span></a></' . tag_escape( $attr['headingTag'] ) . '>';
	} else {
		$wraper_before .= '<' . tag_escape( $attr['headingTag'] ) . ' class="ultp-heading-inner"><span>' . wp_kses( $finalHeadingText, $allowed_html_tags ) . '</span></' . tag_escape( $attr['headingTag'] ) . '>';
	}
	if ( $attr['headingStyle'] == 'style11' && $finalHeadingURL && $attr['headingBtnText'] ) {
		$wraper_before .= '<a class="ultp-heading-btn" href="' . esc_url( $finalHeadingURL ) . '" ' . $new_tab . '>' . wp_kses( $attr['headingBtnText'], $allowed_html_tags ) . wp_kses( ultimate_post()->get_svg_icon( 'rightAngle2' ), ultimate_post()->rendered_content_allowed_html() ) . '</a>';
	}
	if ( $attr['subHeadingShow'] ) {
		$wraper_before .= '<div class="ultp-sub-heading"><div class="ultp-sub-heading-inner">' . wp_kses( $finalSubHeadingText, $allowed_html_tags ) . '</div></div>';
	}
	$wraper_before .= '</div>';
}

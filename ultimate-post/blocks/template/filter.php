<?php
defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside block render methods; variables are local.

$attr['filterType']       = sanitize_html_class( $attr['filterType'] );
$attr['blockId']          = sanitize_html_class( $attr['blockId'] );
$allowed_html_tags        = ultimate_post()->ultp_allowed_html_tags();
$attr['filterText']       = isset( $attr['filterText'] ) && $attr['filterText'] ? wp_kses( $attr['filterText'], $allowed_html_tags ) : '';
$attr['filterMobileText'] = isset( $attr['filterMobileText'] ) && $attr['filterMobileText'] ? esc_html( $attr['filterMobileText'] ) : '';

$page_post_id       = ( isset( $attr['currentPostId'] ) && $attr['currentPostId'] ) ? sanitize_html_class( $attr['currentPostId'] ) : ultimate_post()->get_page_post_id( $attr['blockId'] );
$self_post_id       = 'data-selfpostid="' . ( ( isset( $attr['currentPostId'] ) && $attr['currentPostId'] ) ? 'yes' : 'no' ) . '"';
// Whether the filter items fold into a menu on narrow screens ("Enable Dropdown"): the saved setting, on by default.
// An add-on may still answer; it is asked with the same default.
$filter_mobile_menu = (bool) apply_filters( 'ultp_filter_mobile_menu', ! isset( $attr['filterMobile'] ) || (bool) $attr['filterMobile'], $attr );
$wraper_before     .= '<div class="ultp-filter-wrap" data-taxtype="' . esc_attr( $attr['filterType'] ) . '" data-blockid="' . esc_attr( $attr['blockId'] ) . '" data-blockname="ultimate-post_' . esc_attr( $block_name ) . '" data-postid="' . esc_attr( $page_post_id ) . '"' . $self_post_id . '>';
	$wraper_before .= wp_kses( ultimate_post()->filter_html( $attr['filterText'], $attr['filterType'], $attr['filterValue'], $attr['filterMobileText'], $filter_mobile_menu ), ultimate_post()->rendered_content_allowed_html() );
$wraper_before     .= '</div>';

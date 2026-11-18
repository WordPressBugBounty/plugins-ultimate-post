<?php
defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside block render methods; variables are local.

// Adv Filter Integration
$adv_filter_dataset = ultimate_post()->get_adv_data_attrs( $attr );

$attr['blockId']      = sanitize_html_class( $attr['blockId'] );
$allowed_html_tags    = ultimate_post()->ultp_allowed_html_tags();
$attr['loadMoreText'] = isset( $attr['loadMoreText'] ) ? wp_kses( $attr['loadMoreText'], $allowed_html_tags ) : 'Load More';

$page_post_id  = ( isset( $attr['currentPostId'] ) && $attr['currentPostId'] ) ? sanitize_html_class( $attr['currentPostId'] ) : ultimate_post()->get_page_post_id( $attr['blockId'] );
$self_post_id  = 'data-selfpostid="' . ( ( isset( $attr['currentPostId'] ) && $attr['currentPostId'] ) ? 'yes' : 'no' ) . '"';
$exclude_id    = isset( $curr_post_id ) ? $curr_post_id : '';
$wraper_after .= '<div class="ultp-loadmore">';
if ( 1 != $pageNum ) {
	$wraper_after .= wp_kses( '<span class="ultp-loadmore-action" tabindex="0" role="button" data-pages="' . esc_attr( $pageNum ) . '" data-pagenum="1"  data-expost="' . esc_attr( $exclude_id ) . '" data-blockid="' . esc_attr( $attr['blockId'] ) . '" data-blockname="ultimate-post_' . esc_attr( $block_name ) . '" data-postid="' . esc_attr( $page_post_id ) . '" ' . ultimate_post()->get_builder_attr( $attr['queryType'] ) . $self_post_id . $adv_filter_dataset . '>', ultimate_post()->rendered_content_allowed_html() ) . wp_kses( $attr['loadMoreText'], $allowed_html_tags ) . ' <span class="ultp-spin">' . wp_kses( ultimate_post()->get_svg_icon( 'refresh' ), ultimate_post()->rendered_content_allowed_html() ) . '</span></span>';
}
$wraper_after .= '</div>';

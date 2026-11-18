<?php
defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside block render methods; variables are local.

// Adv Filter Integration
$adv_filter_dataset = ultimate_post()->get_adv_data_attrs( $attr );

$page_post_id      = ( isset( $attr['currentPostId'] ) && $attr['currentPostId'] ) ? sanitize_html_class( $attr['currentPostId'] ) : ultimate_post()->get_page_post_id( $attr['blockId'] );
$exclude_id        = isset( $curr_post_id ) ? $curr_post_id : '';
$self_post_id      = 'data-selfpostid="' . ( ( isset( $attr['currentPostId'] ) && $attr['currentPostId'] ) ? 'yes' : 'no' ) . '"';
$wraper_after     .= wp_kses( '<div class="ultp-pagination-wrap' . ( $attr['paginationAjax'] ? ' ultp-pagination-ajax-action' : '' ) . '" data-paged="1" data-expost="' . esc_attr( $exclude_id ) . '"  data-blockid="' . esc_attr( sanitize_html_class( $attr['blockId'] ) ) . '" data-postid="' . esc_attr( $page_post_id ) . '" data-pages="' . esc_attr( $pageNum ) . '" data-blockname="ultimate-post_' . esc_attr( $block_name ) . '" ' . ultimate_post()->get_builder_attr( $attr['queryType'] ) . $self_post_id . $adv_filter_dataset . '>', ultimate_post()->rendered_content_allowed_html() );
	$wraper_after .= wp_kses( ultimate_post()->pagination( $pageNum, $attr['paginationNav'], $attr['paginationText'], $attr['paginationAjax'], '', $attr['blockId'] ), ultimate_post()->rendered_content_allowed_html() );
$wraper_after     .= '</div>';

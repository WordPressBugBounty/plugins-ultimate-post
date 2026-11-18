<?php
defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside block render methods; variables are local.

// Adv Filter Integration
$adv_filter_dataset = ultimate_post()->get_adv_data_attrs( $attr );

$pagi_block_html = '';

$paged = ! wp_doing_ajax() &&
		isset( $_GET[ $attr['blockId'] . '_page' ] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page number for block pagination.
		is_numeric( $_GET[ $attr['blockId'] . '_page' ] ) ? // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page number for block pagination.
			intval( $_GET[ $attr['blockId'] . '_page' ] ) : '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page number for block pagination.

// Pagination
if ( $attr['advPaginationEnable'] && ( $attr['paginationType'] == 'pagination' ) ) {
	$pagi_datasets = array(
		'for'        => 'ultp-block-' . $attr['blockId'],
		'blockid'    => sanitize_html_class( $attr['blockId'] ),
		'expost'     => isset( $curr_post_id ) ? $curr_post_id : '',
		'paged'      => $paged,
		'pages'      => $pageNum,
		'blockname'  => 'ultimate-post_' . $block_name,
		'postid'     => ( isset( $attr['currentPostId'] ) && $attr['currentPostId'] ) ? sanitize_html_class( $attr['currentPostId'] ) : ultimate_post()->get_page_post_id( $attr['blockId'] ),
		'selfpostid' => ( ( isset( $attr['currentPostId'] ) && $attr['currentPostId'] ) ? 'yes' : 'no' ),
	);

	$f_pagi_datasets = ultimate_post()->get_formatted_datasets( $pagi_datasets );

	$pagi_class = ' class="ultp-pagination-wrap' . ( $attr['paginationAjax'] ? ' ultp-pagination-ajax-action' : '' ) . '" ';


	$pagi_block_html .= wp_kses( '<div' . $pagi_class . ultimate_post()->get_builder_attr( $attr['queryType'] ) . $adv_filter_dataset . $f_pagi_datasets . '>', ultimate_post()->rendered_content_allowed_html() );

	$pagi_block_html .= wp_kses( ultimate_post()->pagination( $pageNum, $attr['paginationNav'], $attr['paginationText'], $attr['paginationAjax'], '', $attr['blockId'] ), ultimate_post()->rendered_content_allowed_html() );

	$pagi_block_html .= '</div>';
}

// Navigation bottom
if ( $attr['advPaginationEnable'] && ( $attr['paginationType'] == 'navigation' ) ) {
	$pagi_datasets = array(
		'for'        => 'ultp-block-' . $attr['blockId'],
		'blockid'    => sanitize_html_class( $attr['blockId'] ),
		'expost'     => isset( $curr_post_id ) ? $curr_post_id : '',
		'pagenum'    => $paged,
		'pages'      => $pageNum,
		'blockname'  => 'ultimate-post_' . $block_name,
		'postid'     => ( isset( $attr['currentPostId'] ) && $attr['currentPostId'] ) ? sanitize_html_class( $attr['currentPostId'] ) : ultimate_post()->get_page_post_id( $attr['blockId'] ),
		'selfpostid' => ( ( isset( $attr['currentPostId'] ) && $attr['currentPostId'] ) ? 'yes' : 'no' ),
	);

	$f_pagi_datasets = ultimate_post()->get_formatted_datasets( $pagi_datasets );
	$pagi_class      = ' class="ultp-next-prev-wrap" ';

	$pagi_block_html .= wp_kses( '<div' . $pagi_class . ultimate_post()->get_builder_attr( $attr['queryType'] ) . $adv_filter_dataset . $f_pagi_datasets . '>', ultimate_post()->rendered_content_allowed_html() );

	if ( 1 != $pageNum ) {
		$pagi_block_html .= wp_kses( ultimate_post()->next_prev(), ultimate_post()->rendered_content_allowed_html() );
	}

	$pagi_block_html .= '</div>';
}

// Load More
if ( $attr['advPaginationEnable'] && ( $attr['paginationType'] == 'loadMore' ) ) {
	$allowed_html_tags = ultimate_post()->ultp_allowed_html_tags();

	$attr['loadMoreText'] = isset( $attr['loadMoreText'] ) ? wp_kses( $attr['loadMoreText'], $allowed_html_tags ) : 'Load More';

	$page_post_id = ( isset( $attr['currentPostId'] ) && $attr['currentPostId'] ) ? sanitize_html_class( $attr['currentPostId'] ) : ultimate_post()->get_page_post_id( $attr['blockId'] );

	$self_post_id = 'data-selfpostid="' . ( ( isset( $attr['currentPostId'] ) && $attr['currentPostId'] ) ? 'yes' : 'no' ) . '"';

	$exclude_id = isset( $curr_post_id ) ? $curr_post_id : '';

	$pagi_class = ' class="ultp-loadmore-action" ';

	$pagi_block_html .= '<div class="ultp-loadmore">';

	if ( 1 != $pageNum ) {
		$pagi_block_html .= wp_kses( '<span' . $pagi_class . ' tabindex="0" role="button" data-for="ultp-block-' . esc_attr( sanitize_html_class( $attr['blockId'] ) ) . '" data-pages="' . esc_attr( $pageNum ) . '" data-pagenum="' . esc_attr( $paged ) . '"  data-expost="' . esc_attr( $exclude_id ) . '" data-blockid="' . esc_attr( sanitize_html_class( $attr['blockId'] ) ) . '" data-blockname="ultimate-post_' . esc_attr( $block_name ) . '" data-postid="' . esc_attr( $page_post_id ) . '" ' . ultimate_post()->get_builder_attr( $attr['queryType'] ) . $self_post_id . $adv_filter_dataset . '>', ultimate_post()->rendered_content_allowed_html() ) . wp_kses( $attr['loadMoreText'], $allowed_html_tags ) . ' <span class="ultp-spin">' . wp_kses( ultimate_post()->get_svg_icon( 'refresh' ), ultimate_post()->rendered_content_allowed_html() ) . '</span></span>';
	}

	$pagi_block_html .= '</div>';
}

// $pagi_block_html = json_encode( array( 'html' => $pagi_block_html ) );
$pagi_block_html = '<div class="pagination-block-html" aria-hidden="true" style="display: none;">' . $pagi_block_html . '</div>';

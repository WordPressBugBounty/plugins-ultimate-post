<?php
defined( 'ABSPATH' ) || exit;

$adv_filter_dataset = ultimate_post()->get_adv_data_attrs( $attr );

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


$wrap_data = ultimate_post()->get_builder_attr( $attr['queryType'] ) . $adv_filter_dataset . $f_pagi_datasets;

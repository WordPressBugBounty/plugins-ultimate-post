<?php
defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside block render methods; variables are local.

if ( $attr['metaShow'] ) {
	// Include contract: a block that lays some posts out as lead posts sets $is_lead (bool) for each post before it
	// includes this file. A block that does not set it gets the first post (index 0) as the lead post.
	$metaCondition = isset( $is_lead ) ? $is_lead : $idx == 0;

	$attr['metaAuthorPrefix'] = wp_kses( $attr['metaAuthorPrefix'], ultimate_post()->ultp_allowed_html_tags() );
	$attr['metaSeparator']    = sanitize_html_class( $attr['metaSeparator'] );
	$attr['metaStyle']        = sanitize_html_class( $attr['metaStyle'] );
	$attr['className']        = isset( $attr['className'] ) ? sanitize_html_class( $attr['className'] ) : '';


	$avatar = get_avatar_url( $user_id );
	$meta   = $metaCondition ? $attr['metaList'] : ( isset( $attr['metaListSmall'] ) ? $attr['metaListSmall'] : $attr['metaList'] );
	$meta   = json_decode( $meta );
	$meta   = is_array( $meta ) ? $meta : array();

	$authorImgOnly = in_array( 'metaAuthor', $meta ) ? '<span class="ultp-block-author ultp-block-meta-element"><img loading="lazy" class="ultp-meta-author-img" src="' . esc_url( $avatar ) . '" alt="' . esc_attr__( 'By', 'ultimate-post' ) . '" /></span>' : '';

	$authorImg = in_array( 'metaAuthor', $meta ) ? '<span class="ultp-block-author ultp-block-meta-element"><img loading="lazy" class="ultp-meta-author-img" src="' . esc_url( $avatar ) . '" alt="' . esc_attr__( 'By', 'ultimate-post' ) . '" />' . wp_kses( $attr['metaAuthorPrefix'], ultimate_post()->ultp_allowed_html_tags() ) . '<a class="' . ( empty( $author_link ) ? 'ultp-meta-author-no-link' : '' ) . '"' . ( $attr['authorLink'] ? ' href="' . esc_url( $author_link ) . '"' : '' ) . '>' . esc_html( $display_name ) . '</a></span>' : '';

	$authorIcon = in_array( 'metaAuthor', $meta ) ? '<span class="ultp-block-author ultp-block-meta-element">' . wp_kses( ultimate_post()->get_svg_icon( 'user' ), ultimate_post()->rendered_content_allowed_html() ) . '<a class="' . ( empty( $author_link ) ? 'ultp-meta-author-no-link' : '' ) . '"' . ( $attr['authorLink'] ? ' href="' . esc_url( $author_link ) . '"' : '' ) . '>' . esc_html( $display_name ) . '</a></span>' : '';

	$authorBy = in_array( 'metaAuthor', $meta ) ? '<span class="ultp-block-author ultp-block-meta-element">' . wp_kses( $attr['metaAuthorPrefix'], ultimate_post()->ultp_allowed_html_tags() ) . '<a class="' . ( empty( $author_link ) ? 'ultp-meta-author-no-link' : '' ) . '"' . ( $attr['authorLink'] ? ' href="' . esc_url( $author_link ) . '"' : '' ) . '>' . esc_html( $display_name ) . '</a></span>' : '';

	// Author Filter Hook
	$authorImgOnly = ultimate_post()->esc_filtered_markup( apply_filters( 'postx_loop_author_image_only', $authorImgOnly, $user_id, $post_id, isset( $attr['className'] ) ? $attr['className'] : '' ), $authorImgOnly );
	$authorImg     = ultimate_post()->esc_filtered_markup( apply_filters( 'postx_loop_author_image', $authorImg, $user_id, $post_id, isset( $attr['className'] ) ? $attr['className'] : '' ), $authorImg );
	$authorIcon    = ultimate_post()->esc_filtered_markup( apply_filters( 'postx_loop_author_icon', $authorIcon, $user_id, $post_id, isset( $attr['className'] ) ? $attr['className'] : '' ), $authorIcon );
	$authorBy      = ultimate_post()->esc_filtered_markup( apply_filters( 'postx_loop_author_by', $authorBy, $user_id, $post_id, isset( $attr['className'] ) ? $attr['className'] : '' ), $authorBy );

	$date             = in_array( 'metaDate', $meta ) ? '<span class="ultp-block-date ultp-block-meta-element">' . esc_html( $time ) . '</span>' : '';
	$dateIcon         = in_array( 'metaDate', $meta ) ? '<span class="ultp-block-date ultp-block-meta-element">' . wp_kses( ultimate_post()->get_svg_icon( 'calendar' ), ultimate_post()->rendered_content_allowed_html() ) . esc_html( $time ) . '</span>' : '';
	$dateModified     = in_array( 'metaDateModified', $meta ) ? '<span class="ultp-block-date ultp-block-meta-element">' . esc_html( $timeModified ) . '</span>' : '';
	$dateModifiedIcon = in_array( 'metaDateModified', $meta ) ? '<span class="ultp-block-date ultp-block-meta-element">' . wp_kses( ultimate_post()->get_svg_icon( 'calendar' ), ultimate_post()->rendered_content_allowed_html() ) . esc_html( $timeModified ) . '</span>' : '';
	$comments         = in_array( 'metaComments', $meta ) ? '<span class="ultp-post-comment ultp-block-meta-element">' . esc_html( $comment ) . esc_html__( ' comments', 'ultimate-post' ) . '</span>' : '';
	$commentsIcon     = in_array( 'metaComments', $meta ) ? '<span class="ultp-post-comment ultp-block-meta-element">' . wp_kses( ultimate_post()->get_svg_icon( 'comment' ), ultimate_post()->rendered_content_allowed_html() ) . esc_html( $comment ) . '</span>' : '';
	$views            = in_array( 'metaView', $meta ) ? '<span class="ultp-post-view ultp-block-meta-element">' . esc_html( $view ) . esc_html__( ' views', 'ultimate-post' ) . '</span>' : '';
	$viewIcon         = in_array( 'metaView', $meta ) ? '<span class="ultp-post-view ultp-block-meta-element">' . wp_kses( ultimate_post()->get_svg_icon( 'eye' ), ultimate_post()->rendered_content_allowed_html() ) . esc_html( $view ) . '</span>' : '';
	$postTime         = in_array( 'metaTime', $meta ) ? '<span class="ultp-post-time ultp-block-meta-element">' . esc_html( $post_time ) . esc_html__( ' ago', 'ultimate-post' ) . '</span>' : '';
	$postTimeIcon     = in_array( 'metaTime', $meta ) ? '<span class="ultp-post-time ultp-block-meta-element">' . wp_kses( ultimate_post()->get_svg_icon( 'clock' ), ultimate_post()->rendered_content_allowed_html() ) . esc_html( $post_time ) . esc_html__( ' ago', 'ultimate-post' ) . '</span>' : '';
	$reading          = in_array( 'metaRead', $meta ) ? '<span class="ultp-post-read ultp-block-meta-element">' . wp_kses( $reading_time, ultimate_post()->ultp_allowed_html_tags() ) . '</span>' : '';
	$readingIcon      = in_array( 'metaRead', $meta ) ? '<span class="ultp-post-read ultp-block-meta-element">' . wp_kses( ultimate_post()->get_svg_icon( 'book' ), ultimate_post()->rendered_content_allowed_html() ) . wp_kses( $reading_time, ultimate_post()->ultp_allowed_html_tags() ) . '</span>' : '';

	$post_loop .= '<div class="ultp-block-meta ultp-block-meta-' . esc_attr( $attr['metaSeparator'] ) . ' ultp-block-meta-' . esc_attr( $attr['metaStyle'] ) . '">';
	if ( $attr['metaStyle'] == 'noIcon' ) {
		$post_loop .= wp_kses( $authorBy . $date . $dateModified . $comments . $views . $reading . $postTime, ultimate_post()->rendered_content_allowed_html() ); }
	if ( $attr['metaStyle'] == 'icon' ) {
		$post_loop .= wp_kses( $authorIcon . $dateIcon . $dateModifiedIcon . $commentsIcon . $viewIcon . $postTimeIcon . $readingIcon, ultimate_post()->rendered_content_allowed_html() ); }
	if ( $attr['metaStyle'] == 'style2' ) {
		$post_loop .= wp_kses( $authorBy . $dateIcon . $dateModifiedIcon . $commentsIcon . $viewIcon . $postTimeIcon . $readingIcon, ultimate_post()->rendered_content_allowed_html() ); }
	if ( $attr['metaStyle'] == 'style3' ) {
		$post_loop .= wp_kses( $authorImg . $dateIcon . $dateModifiedIcon . $commentsIcon . $viewIcon . $postTimeIcon . $readingIcon, ultimate_post()->rendered_content_allowed_html() ); }
	if ( $attr['metaStyle'] == 'style4' ) {
		$post_loop .= wp_kses( $authorIcon . $dateIcon . $dateModifiedIcon . $commentsIcon . $viewIcon . $postTimeIcon . $readingIcon, ultimate_post()->rendered_content_allowed_html() ); }
	if ( $attr['metaStyle'] == 'style5' ) {
		$post_loop .= wp_kses( '<div class="ultp-meta-media">' . $authorImgOnly . '</div> <div class="ultp-meta-body">' . $authorBy . $dateIcon . $dateModifiedIcon . $commentsIcon . $viewIcon . $postTimeIcon . $readingIcon . '</div>', ultimate_post()->rendered_content_allowed_html() ); }
	if ( $attr['metaStyle'] == 'style6' ) {
		$post_loop .= wp_kses( $authorImgOnly . $authorBy . $dateIcon . $dateModifiedIcon . $commentsIcon . $viewIcon . $postTimeIcon . $readingIcon, ultimate_post()->rendered_content_allowed_html() ); }
	$post_loop .= '</div>';
}

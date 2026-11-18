<?php
defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside block render methods; variables are local.

// The default is a button without a link when the block sets $vid_icon_redirect to false, otherwise a link to the post.
// The markup can be changed through ultp_video_icon_html.
if ( isset( $vid_icon_redirect ) && ! $vid_icon_redirect ) {
	$icon_default = '<div enableVideoPopup="" enableAutoPlay="' . esc_attr( $attr['popupAutoPlay'] ) . '" class="ultp-video-icon">' . wp_kses( ultimate_post()->get_svg_icon( 'play_line' ), ultimate_post()->rendered_content_allowed_html() ) . '</div>';
} else {
	$icon_default = '<a href="' . esc_url( $titlelink ) . '" enableAutoPlay="' . esc_attr( $attr['popupAutoPlay'] ) . '" class="ultp-video-icon">' . wp_kses( ultimate_post()->get_svg_icon( 'play_line' ), ultimate_post()->rendered_content_allowed_html() ) . '</a>';
}
$post_loop .= ultimate_post()->esc_filtered_markup( apply_filters( 'ultp_video_icon_html', $icon_default, $attr, array( 'titlelink' => $titlelink ) ), $icon_default );

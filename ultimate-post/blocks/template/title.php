<?php
defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside block render methods; variables are local.

$attr['titleTag'] = in_array( $attr['titleTag'], ultimate_post()->ultp_allowed_block_tags() ) ? $attr['titleTag'] : 'h3';
// Hover class of the title, from the `ultp_title_hover_class` filter (free adds none).
$post_loop       .= '<' . tag_escape( $attr['titleTag'] ) . ' class="ultp-block-title ' . esc_attr( apply_filters( 'ultp_title_hover_class', '', $attr, $block_name ?? '' ) ) . '"><a href="' . esc_url( $titlelink ) . '" ' . ( $attr['openInTab'] ? 'target="_blank"' : '' ) . '>' . esc_html( $title ) . '</a></' . tag_escape( $attr['titleTag'] ) . '>';

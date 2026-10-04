<?php
defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside block render methods; variables are local.

$attr['catStyle']    = sanitize_html_class( $attr['catStyle'] );
$attr['catPosition'] = sanitize_html_class( $attr['catPosition'] );

$category = '';
if ( $attr['catShow'] ) {
	$category     .= '<div class="ultp-category-grid ultp-category-' . $attr['catStyle'] . ' ultp-category-' . $attr['catPosition'] . '">';
		$category .= '<div class="ultp-category-in' . ( $attr['customCatColor'] == true ? ' ultp-cat-color-' . $attr['customCatColor'] : '' ) . '">';
			$cat   = get_the_terms( $post_id, $attr['taxonomy'] );
	if ( ! empty( $cat ) ) {
		$max_tax = isset( $attr['maxTaxonomy'] ) && $attr['maxTaxonomy'] ? ( $attr['maxTaxonomy'] == '0' ? 0 : $attr['maxTaxonomy'] ) : 30;

		foreach ( $cat as $k => $val ) {
			if ( $k >= $max_tax ) {
				break; }
			$color = '';
			if ( isset( $attr['customCatColor'] ) ) {
				if ( $attr['customCatColor'] ) {
					$color = ultimate_post()->get_term_color( $val );
					$color = $color == 1 ? '#CE2746' : $color;
					if ( isset( $attr['onlyCatColor'] ) ) {
						if ( $attr['onlyCatColor'] ) {
							$color = 'style="color: ' . ( $color ? $color : '#CE2746' ) . '"';
						} else {
							$color = 'style="background-color: ' . ( $color ? $color : '#CE2746' ) . ';"';
						}
					} else {
						$color = 'style="background-color: ' . ( $color ? $color : '#CE2746' ) . ';"';
					}
				}
			}
			if ( $attr['onlyCatColor'] ) {
				$category .= '<a class="ultp-cat-' . $val->slug . '" href="' . get_term_link( $val->term_id ) . '" ' . $color . ' ' . ( $attr['openInTab'] ? 'target="_blank"' : '' ) . '>' . $val->name . '</a>';
			} else {
				$category .= '<a class="ultp-cat-' . $val->slug . ( $attr['customCatColor'] == true ? ' ultp-cat-only-color-' . $attr['customCatColor'] : '' ) . '" href="' . get_term_link( $val->term_id ) . '" ' . $color . ' ' . ( $attr['openInTab'] ? 'target="_blank"' : '' ) . '>' . $val->name . '</a>';
			}
		}
	}
		$category .= '</div>';
	$category     .= '</div>';
}

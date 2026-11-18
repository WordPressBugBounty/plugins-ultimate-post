<?php
defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside block render methods; variables are local.

$attr['catStyle']    = sanitize_html_class( $attr['catStyle'] );
$attr['catPosition'] = sanitize_html_class( $attr['catPosition'] );

$category = '';
if ( $attr['catShow'] ) {
	$category     .= '<div class="ultp-category-grid ultp-category-' . esc_attr( $attr['catStyle'] ) . ' ultp-category-' . esc_attr( $attr['catPosition'] ) . '">';
		// An add-on may add classes to the term list, and take the terms from another taxonomy.
		$cat_wrap_class = apply_filters( 'ultp_post_category_class', '', $attr );
		$cat_wrap_class = is_string( $cat_wrap_class ) ? implode( ' ', array_filter( array_map( 'sanitize_html_class', explode( ' ', $cat_wrap_class ) ) ) ) : '';
		$cat_taxonomy   = apply_filters( 'ultp_post_category_taxonomy', 'category', $attr );
		$cat_taxonomy   = is_string( $cat_taxonomy ) && '' !== $cat_taxonomy ? $cat_taxonomy : 'category';
		$category      .= '<div class="ultp-category-in' . ( '' !== $cat_wrap_class ? ' ' . esc_attr( $cat_wrap_class ) : '' ) . '">';
			$cat        = get_the_terms( $post_id, $cat_taxonomy );
	if ( ! empty( $cat ) ) {
		$max_tax = isset( $attr['maxTaxonomy'] ) && $attr['maxTaxonomy'] ? ( $attr['maxTaxonomy'] == '0' ? 0 : $attr['maxTaxonomy'] ) : 30;

		foreach ( $cat as $k => $val ) {
			if ( $k >= $max_tax ) {
				break; }
			// An add-on may add a class and attributes (such as a colour) to each term link.
			$term_link = apply_filters(
				'ultp_post_category_term',
				array(
					'class' => '',
					'attr'  => '',
				),
				$attr,
				$val
			);
			$term_class = is_array( $term_link ) && isset( $term_link['class'] ) && is_string( $term_link['class'] ) ? implode( ' ', array_filter( array_map( 'sanitize_html_class', explode( ' ', $term_link['class'] ) ) ) ) : '';
			// Only a single style="…" attribute is kept from the add-on, with its value escaped.
			$term_attr  = is_array( $term_link ) && isset( $term_link['attr'] ) ? ultimate_post()->filtered_style_value( $term_link['attr'] ) : null;
			$category  .= '<a class="ultp-cat-' . esc_attr( sanitize_html_class( $val->slug ) ) . ( '' !== $term_class ? ' ' . esc_attr( $term_class ) : '' ) . '" href="' . esc_url( ultimate_post()->esc_term_link( $val ) ) . '" ' . ( null !== $term_attr ? 'style="' . esc_attr( $term_attr ) . '"' : '' ) . ' ' . ( $attr['openInTab'] ? 'target="_blank"' : '' ) . '>' . esc_html( $val->name ) . '</a>';
		}
	}
		$category .= '</div>';
	$category     .= '</div>';
}

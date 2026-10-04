<?php
defined( 'ABSPATH' ) || exit;

global $ULTP_HEADER_ID;
global $ULTP_FOOTER_ID;
$ultp_header = '';

if ( wp_is_block_theme() && ! $ULTP_HEADER_ID ) {
	ob_start();
	block_template_part( 'header' );
	$ultp_header = ob_get_clean();
}

if ( wp_is_block_theme() ) {
	?><!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
	<?php
	wp_body_open();

	if ( $ultp_header ) {
		echo '<header class="wp-block-template-part">' . $ultp_header . '</header>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-rendered block template part.
	}
} else {
	get_header();
}

do_action( 'ultp_before_content' );

global $ultp_page_builder_id;
if ( $ultp_page_builder_id ) {
	$ultp_page_id = $ultp_page_builder_id;
} else {
	$ultp_page_id = ultimate_post()->builder_check_conditions( 'return_p' );
}

$ultp_width       = $ultp_page_id ? ( get_post_meta( $ultp_page_id, '__container_width', true ) ? get_post_meta( $ultp_page_id, '__container_width', true ) : '1140' ) : '1140';
$ultp_sidebar     = $ultp_page_id ? get_post_meta( $ultp_page_id, '__builder_sidebar', true ) : '';
$ultp_widget_area = $ultp_page_id ? get_post_meta( $ultp_page_id, '__builder_widget_area', true ) : '';
$ultp_has_widget  = ( $ultp_sidebar && $ultp_widget_area != '' ) ? true : false;

echo '<div class="ultp-builder-container' . ( esc_attr( $ultp_has_widget ? ' ultp-widget-' . $ultp_sidebar : '' ) ) . ( esc_attr( ' ultp-builderid-' . $ultp_page_id ) ) . '" style="margin:0 auto; width: -webkit-fill-available; width: -moz-available; max-width: ' . esc_attr( $ultp_width ) . 'px;">';
if ( $ultp_has_widget && $ultp_sidebar == 'left' ) {
	echo '<div class="ultp-sidebar-left">';
	if ( is_active_sidebar( $ultp_widget_area ) ) {
		dynamic_sidebar( $ultp_widget_area );
	}
		echo '</div>';
}
	echo '<div class="ultp-builder-wrap">';
if ( $ultp_page_id ) {
	/* Content Filtering for elementor & divi */
	$ultp_builder_type  = '';
	$ultp_body_class    = get_body_class();
	$ultp_divi_settings = ultimate_post()->get_setting( 'ultp_divi' );
	$ultp_elem_settings = ultimate_post()->get_setting( 'ultp_elementor' );
	if ( $ultp_divi_settings == 'true' && class_exists( 'ET_Builder_Module' ) && in_array( 'et-fb', $ultp_body_class ) ) {
		$ultp_builder_type = 'divi';
	} elseif ( did_action( 'elementor/loaded' ) && $ultp_elem_settings == 'true' && in_array( 'elementor-editor-active', $ultp_body_class ) && in_array( 'elementor-editor-wp-post', $ultp_body_class ) ) {
		$ultp_builder_type = 'elementor';
	}
	/* Content Filtering for elementor & divi */
	ultimate_post()->get_post_content( $ultp_page_id, $ultp_builder_type );
} else {
	the_content();
}
	echo '</div>';
if ( $ultp_has_widget && $ultp_sidebar == 'right' ) {
	echo '<div class="ultp-sidebar-right">';
	if ( is_active_sidebar( $ultp_widget_area ) ) {
		dynamic_sidebar( $ultp_widget_area );
	}
		echo '</div>';
}
echo '</div>';

do_action( 'ultp_after_content' );


if ( wp_is_block_theme() ) {
	?>
	</body>
	</html>
	<?php
	if ( ! $ULTP_FOOTER_ID ) {
		ob_start();
		block_template_part( 'footer' );
		$ultp_footer = ob_get_clean();
		echo '<footer class="wp-block-template-part">' . $ultp_footer . '</footer>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-rendered block template part.
	}
	// wp_head();
	// wp_head(); all ready wp head declared in header, i can not find any conflict to check that, if you find any conflict for removing then use it
	wp_footer();
} else {
	get_footer();
}

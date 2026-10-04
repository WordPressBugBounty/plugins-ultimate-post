<?php
defined( 'ABSPATH' ) || exit;

$ultp_is_block_theme = wp_is_block_theme();

global $ULTP_HEADER_ID;
global $ULTP_FOOTER_ID;
$ultp_builder_active = ultimate_post()->get_setting( 'ultp_builder' ) != 'false';
$ultp_has_header     = $ultp_builder_active ? $ULTP_HEADER_ID : '';
$ultp_has_footer     = $ultp_builder_active ? $ULTP_FOOTER_ID : '';

if ( $ultp_is_block_theme ) {
	?><!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<?php
		wp_head();
		?>
	</head>
	<body <?php body_class(); ?>>
	<?php
	wp_body_open();

	if ( ! $ultp_has_header ) {
		ob_start();
		block_template_part( 'header' );
		$ultp_header = ob_get_clean();
		echo '<header class="wp-block-template-part">' . $ultp_header . '</header>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-rendered block template part.
	}
} else {
	get_header();
}

do_action( 'ultp_before_content' );

$ultp_width = ultimate_post()->get_setting( 'container_width' );
$ultp_width = $ultp_width ? $ultp_width : '1140';
?>
<div class="ultp-template-container" style="margin:0 auto;max-width:<?php echo esc_attr( $ultp_width ); ?>px; padding: 0 15px; width: -webkit-fill-available; width: -moz-available;">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		endwhile;
	?>
</div>

<?php
do_action( 'ultp_after_content' );
if ( $ultp_is_block_theme ) {
	?>
	</body>
	</html>
	<?php
	if ( ! $ultp_has_footer ) {
		ob_start();
		block_template_part( 'footer' );
		$ultp_footer = ob_get_clean();
		echo '<footer class="wp-block-template-part">' . $ultp_footer . '</footer>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-rendered block template part.
	}
	// wp_head(); // all ready wp head declared in header, i can not find any conflict to check that, if you find any conflict for removing then use it
	wp_footer();
} else {
	get_footer();
}

<?php
/**
 * Text and button notice.
 *
 * @package ULTP\Notice
 *
 * @var \ULTP\Includes\Notice\Content_Notice $notice
 */

defined( 'ABSPATH' ) || exit;

$ultp_url = $notice->get_url();
?>
<div class="ultp-content-notice-wrapper notice data_collection_notice"
style="border-left: 3px solid <?php echo esc_attr( $notice->get( 'border_color' ) ); ?>;"
>
	<?php
	if ( $notice->get( 'is_discount_logo' ) ) {
		?>
		<div class="ultp-content-notice-discout-icon"> <img src="<?php echo esc_url( $notice->get( 'icon' ) ); ?>"/>  </div>
		<?php
	} else {
		?>
		<div class="ultp-content-notice-icon"> <img src="<?php echo esc_url( $notice->get( 'icon' ) ); ?>"/>  </div>
		<?php
	}
	?>

	<div class="ultp-notice-content-wrapper">
		<div class="">
			<strong><?php echo esc_html( $notice->get( 'content_heading' ) ); ?> </strong>
		<?php echo wp_kses_post( $notice->get_subheading_html() ); ?>
		</div>
		<div class="ultp-content-notice-buttons">
	<?php if ( $notice->get( 'is_discount_logo' ) ) : ?>
				<a class="ultp-content-discount_btn" href="<?php echo esc_url( $ultp_url ); ?>" target="_blank">
					<?php echo esc_html( $notice->get( 'button_text' ) ); ?>
				</a>
			<?php else : ?>
				<a class="ultp-content-notice-btn button button-primary" href="<?php echo esc_url( $ultp_url ); ?>" target="_blank" style="background-color: <?php echo ! empty( $notice->get( 'background_color' ) ) ? esc_attr( $notice->get( 'background_color' ) ) : '#86a62c'; ?>;">
				<?php echo esc_html( $notice->get( 'button_text' ) ); ?>

				</a>
			<?php endif; ?>
		</div>
	</div>
	<a href="<?php echo esc_url( $notice->get_dismiss_url() ); ?>" class="ultp-content-notice-close"><span class="ultp-content-notice-close-icon dashicons dashicons-dismiss"> </span></a>
</div>

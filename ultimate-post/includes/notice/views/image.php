<?php
/**
 * Image banner notice.
 *
 * @package ULTP\Notice
 *
 * @var \ULTP\Includes\Notice\Image_Notice $notice
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="ultp-notice-wrapper ultp-image-notice-wrapper notice wc-install ultp-free-notice">
	<div class="wc-install-body ultp-image-banner">
		<a class="wc-dismiss-notice ultp-content-notice-close" href="<?php echo esc_url( $notice->get_dismiss_url() ); ?>"><span class="ultp-content-notice-close-icon dashicons dashicons-dismiss" style="color: <?php echo esc_attr( $notice->get( 'close_color' ) ); ?>;"> </span></a>
		<a class="ultp-btn-image" target="_blank" href="<?php echo esc_url( $notice->get_url() ); ?>">
			<img loading="lazy" src="<?php echo esc_url( $notice->get( 'banner_src' ) ); ?>" alt="Discount Banner"/>
		</a>
	</div>
</div>


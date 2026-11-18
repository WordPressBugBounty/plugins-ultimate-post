<?php
/**
 * Countdown banner notice.
 *
 * @package ULTP\Notice
 *
 * @var \ULTP\Includes\Notice\Banner_Notice $notice
 */

defined( 'ABSPATH' ) || exit;
?>
<div
	class="ultp-notice-wrapper ultp-banner-notice notice"
	style="
		border-left: 3px solid <?php echo esc_attr( $notice->get( 'brand_color' ) ); ?>;
		background-image: url('<?php echo esc_url( $notice->get_image( 'bg' ) ); ?>');
">
	<a
		class="wc-dismiss-notice dashicons dashicons-no-alt"
		style="
			position: absolute;
			top: 1px;
			right: 1px;
			border-radius: 50%;
			background-color: black;
			color: white;
			font-size: 14px;
			display: flex;
			align-items: center;
			justify-content: center;
		"
		aria-label="<?php esc_html_e( 'Close Banner', 'ultimate-post' ); ?>"
		href="<?php echo esc_url( $notice->get_dismiss_url() ); ?>">
	</a>

	<a class="ultp-banner-link" target="_blank" href="<?php echo esc_url( $notice->get_url() ); ?>">
		<div class="ultp-banner-content">
			<img class="ultp-banner-side-image" loading="lazy" src="<?php echo esc_url( $notice->get_image( 'left' ) ); ?>" />
			<div class="ultp-banner-main">
				<span class="ultp-banner-main-text">
					<?php echo esc_html( $notice->get( 'text' ) ); ?>
				</span>
				<div
					class="ultp-notice-countdown"
					style="
						color: <?php echo esc_attr( $notice->get( 'countdown_color' ) ); ?>;
					"
					data-notice-key="<?php echo esc_attr( $notice->get_key() . '-countdown' ); ?>"
					data-end-time="<?php echo esc_attr( $notice->get_end_timestamp() ); ?>"
					data-duration="<?php echo esc_attr( $notice->get( 'countdown_duration' ) ); ?>">
					00:00:00:00
				</div>
			</div>
			<img class="ultp-banner-side-image" loading="lazy" src="<?php echo esc_url( $notice->get_image( 'right' ) ); ?>" />
		</div>
	</a>
</div>

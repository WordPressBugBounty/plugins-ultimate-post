<?php
/**
 * Data-collection consent notice.
 *
 * @package ULTP\Notice
 *
 * @var \ULTP\Includes\Notice\Consent_Notice $notice
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="ultp-consent-box ultp-notice-wrapper notice data_collection_notice">
	<div class="ultp-consent-content">
		<div class="ultp-consent-text">
			<div class="ultp-consent-text-first"><?php esc_html_e( 'Want to help make PostX even more awesome?', 'ultimate-post' ); ?></div>
			<div class="ultp-consent-text-last">
				<?php esc_html_e( 'Allow us to collect diagnostic data and usage information. see ', 'ultimate-post' ); ?>
				<a href="https://www.wpxpo.com/data-collection-policy/" target="_blank" ><?php esc_html_e( 'what we collect.', 'ultimate-post' ); ?></a>
			</div>
		</div>
		<a
			class="ultp-consent-accept"
			href="<?php echo esc_url( $notice->get_accept_url() ); ?>"
		><?php esc_html_e( 'Accept & Close', 'ultimate-post' ); ?></a>
	</div>
	<a href="<?php echo esc_url( $notice->get_close_url() ); ?>"
		class="ultp-notice-close"
		style="
			position: absolute;
			right: 2px;
			top: 5px;
			text-decoration: unset;
			color: #b6b6b6;
			font-family: dashicons;
			font-size: 16px;
			font-style: normal;
			font-weight: 400;
			line-height: 20px;
		"
	>
		<span
		style="font-size: 14px;"
		class="ultp-notice-close-icon dashicons dashicons-dismiss"> </span></a>
</div>

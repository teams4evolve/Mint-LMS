<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\WooCommerce;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {

	public function register(): void {
		add_action(
			'admin_init',
			static function (): void {
				register_setting(
					'mintlms_wc_settings',
					'mintlms_wc_cancel_on_refund',
					array(
						'type'              => 'string',
						'sanitize_callback' => static function ( $value ): string {
							return 'yes' === $value ? 'yes' : 'no';
						},
						'default'           => 'yes',
					)
				);
			}
		);

		add_action(
			'admin_menu',
			array( $this, 'registerMenu' )
		);
	}

	public function registerMenu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Mint LMS', 'mint-lms' ),
			__( 'Mint LMS', 'mint-lms' ),
			'manage_woocommerce',
			'mintlms-woocommerce',
			array( $this, 'render' )
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$cancelOnRefund = get_option( 'mintlms_wc_cancel_on_refund', 'yes' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Mint LMS WooCommerce', 'mint-lms' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'mintlms_wc_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Cancel enrollment on refund', 'mint-lms' ); ?></th>
						<td>
							<label for="mintlms_wc_cancel_on_refund">
								<input
									type="checkbox"
									id="mintlms_wc_cancel_on_refund"
									name="mintlms_wc_cancel_on_refund"
									value="yes"
									<?php checked( 'yes', $cancelOnRefund ); ?>
								/>
								<?php esc_html_e( 'Revoke Mint LMS course access when an order is refunded or cancelled.', 'mint-lms' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

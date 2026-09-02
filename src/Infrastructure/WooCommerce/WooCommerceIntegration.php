<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\WooCommerce;

defined( 'ABSPATH' ) || exit;

final class WooCommerceIntegration {

	private static ?self $instance = null;

	private EnrollmentBridge $bridge;

	private ProductMeta $productMeta;

	private OrderHandler $orderHandler;

	public static function isAvailable(): bool {
		return class_exists( 'WooCommerce' );
	}

	public static function register(): void {
		if ( ! self::isAvailable() ) {
			return;
		}

		if ( null !== self::$instance ) {
			return;
		}

		self::$instance = new self();
		self::$instance->boot();
	}

	private function boot(): void {
		$this->bridge        = new EnrollmentBridge();
		$this->productMeta   = new ProductMeta();
		$this->orderHandler  = new OrderHandler( $this->bridge, $this->productMeta );

		if ( is_admin() ) {
			$this->productMeta->register();
			( new SettingsPage() )->register();
		}

		$this->orderHandler->register();

		/**
		 * Fires when Mint LMS WooCommerce integration has finished loading.
		 *
		 * @param WooCommerceIntegration $integration Integration instance.
		 */
		do_action( 'mintlms_wc_loaded', $this );
	}

	public function bridge(): EnrollmentBridge {
		return $this->bridge;
	}

	public function productMeta(): ProductMeta {
		return $this->productMeta;
	}

	public function orderHandler(): OrderHandler {
		return $this->orderHandler;
	}
}

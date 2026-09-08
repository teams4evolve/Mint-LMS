<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Product subclass for Mint LMS course products.
 *
 * Loaded only when WooCommerce is available.
 */
class CourseProduct extends \WC_Product_Simple {

	public function get_type(): string {
		return CourseProductType::TYPE;
	}

	/**
	 * Course products are always virtual.
	 *
	 * @param string $context View or edit context.
	 */
	public function get_virtual( $context = 'view' ): bool { // phpcs:ignore Squiz.Commenting.FunctionComment.TypeHintMissing -- WC parent signature.
		return true;
	}
}

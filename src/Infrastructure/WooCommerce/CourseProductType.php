<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce product type "Course" (LearnDash-style).
 *
 * Extends simple virtual product behaviour.
 */
final class CourseProductType {

	public const TYPE = 'mintlms_course';

	public function register(): void {
		add_filter( 'product_type_selector', array( $this, 'addProductType' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'mapProductClass' ), 10, 2 );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'showPricingForCourseType' ) );
		add_action( 'admin_footer', array( $this, 'adminScript' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'hideUnusedTabs' ) );
	}

	/**
	 * @param array<string, string> $types Product types.
	 * @return array<string, string>
	 */
	public function addProductType( array $types ): array {
		$types[ self::TYPE ] = __( 'Mint LMS Course', 'mint-lms' );

		return $types;
	}

	/**
	 * @param string $classname Product class name.
	 * @param string $productType Product type slug.
	 */
	public function mapProductClass( string $classname, string $productType ): string {
		if ( self::TYPE === $productType ) {
			return CourseProduct::class;
		}

		return $classname;
	}

	public function showPricingForCourseType(): void {
		// Reuse simple product pricing UI for course products.
		echo '<div class="options_group show_if_' . esc_attr( self::TYPE ) . '">';
		echo '</div>';
	}

	/**
	 * @param array<string, array<string, mixed>> $tabs Product data tabs.
	 * @return array<string, array<string, mixed>>
	 */
	public function hideUnusedTabs( array $tabs ): array {
		if ( isset( $tabs['shipping'] ) ) {
			$tabs['shipping']['class'][] = 'hide_if_' . self::TYPE;
		}
		if ( isset( $tabs['inventory'] ) ) {
			$tabs['inventory']['class'][] = 'show_if_' . self::TYPE;
		}

		return $tabs;
	}

	public function adminScript(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! in_array( $screen->id, array( 'product', 'edit-product' ), true ) ) {
			return;
		}
		?>
		<script type="text/javascript">
		jQuery( function( $ ) {
			$( document.body ).on( 'woocommerce-product-type-change', function( e, type ) {
				if ( type === '<?php echo esc_js( self::TYPE ); ?>' ) {
					$( '.show_if_simple' ).show();
					$( '#_virtual' ).prop( 'checked', true ).trigger( 'change' );
					$( '#_downloadable' ).prop( 'checked', false ).trigger( 'change' );
				}
			} );
			if ( $( '#product-type' ).val() === '<?php echo esc_js( self::TYPE ); ?>' ) {
				$( '.show_if_simple' ).show();
				$( '#_virtual' ).prop( 'checked', true );
			}
		} );
		</script>
		<?php
	}
}

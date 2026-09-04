<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\WooCommerce;

defined( 'ABSPATH' ) || exit;

final class ProductMeta {

	public function register(): void {
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'renderField' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'saveField' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'renderVariationField' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'saveVariationField' ), 10, 2 );
	}

	public function renderField(): void {
		echo '<div class="options_group show_if_simple show_if_variable">';

		woocommerce_wp_text_input(
			array(
				'id'                => EnrollmentBridge::META_COURSE_ID,
				'label'             => __( 'Mint LMS Course ID', 'mint-lms' ),
				'desc_tip'          => true,
				'description'       => __( 'Link this product to a Mint LMS course. Customers are enrolled when the order is completed.', 'mint-lms' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '1',
				),
				'value'             => $this->getCourseIdForProduct( (int) get_the_ID() ),
			)
		);

		echo '</div>';
	}

	/**
	 * @param int     $loop           Variation loop index.
	 * @param array   $variationData  Variation data.
	 * @param \WP_Post $variation     Variation post.
	 */
	public function renderVariationField( int $loop, array $variationData, \WP_Post $variation ): void {
		woocommerce_wp_text_input(
			array(
				'id'                => EnrollmentBridge::META_COURSE_ID . "[{$loop}]",
				'name'              => EnrollmentBridge::META_COURSE_ID . "[{$loop}]",
				'label'             => __( 'Mint LMS Course ID', 'mint-lms' ),
				'desc_tip'          => true,
				'description'       => __( 'Optional course for this variation. Overrides the parent product course ID.', 'mint-lms' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '1',
				),
				'value'             => $this->getCourseIdForProduct( (int) $variation->ID ),
				'wrapper_class'     => 'form-row form-row-full',
			)
		);
	}

	public function saveField( int $productId ): void {
		if ( ! current_user_can( 'edit_post', $productId ) ) {
			return;
		}

		check_admin_referer( 'woocommerce_save_data', 'woocommerce_meta_nonce' );

		$postId = isset( $_POST['post_ID'] ) ? absint( wp_unslash( (string) $_POST['post_ID'] ) ) : 0;
		if ( $postId > 0 && $postId !== $productId ) {
			return;
		}

		$courseId = isset( $_POST[ EnrollmentBridge::META_COURSE_ID ] )
			? absint( wp_unslash( (string) $_POST[ EnrollmentBridge::META_COURSE_ID ] ) )
			: 0;

		$this->updateCourseId( $productId, $courseId );
	}

	public function saveVariationField( int $variationId, int $loop ): void {
		if ( ! current_user_can( 'edit_post', $variationId ) ) {
			return;
		}

		if ( ! empty( $_REQUEST['security'] ) ) {
			check_ajax_referer( 'save-variations', 'security' );
		} elseif ( ! empty( $_POST['woocommerce_meta_nonce'] ) ) {
			check_admin_referer( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
		} else {
			return;
		}

		$posted = isset( $_POST[ EnrollmentBridge::META_COURSE_ID ] )
			&& is_array( $_POST[ EnrollmentBridge::META_COURSE_ID ] )
			? wp_unslash( $_POST[ EnrollmentBridge::META_COURSE_ID ] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array values sanitized via absint() below.
			: array();

		$courseId = isset( $posted[ $loop ] ) ? absint( (string) $posted[ $loop ] ) : 0;

		$this->updateCourseId( $variationId, $courseId );
	}

	public function getCourseIdForProduct( int $productId ): int {
		if ( $productId <= 0 ) {
			return 0;
		}

		$product = wc_get_product( $productId );

		if ( ! $product instanceof \WC_Product ) {
			return 0;
		}

		$courseId = (int) $product->get_meta( EnrollmentBridge::META_COURSE_ID, true );

		if ( $courseId > 0 ) {
			return $courseId;
		}

		if ( $product->is_type( 'variation' ) ) {
			$parentId = $product->get_parent_id();

			if ( $parentId > 0 ) {
				$parent = wc_get_product( $parentId );

				if ( $parent instanceof \WC_Product ) {
					return (int) $parent->get_meta( EnrollmentBridge::META_COURSE_ID, true );
				}
			}
		}

		return 0;
	}

	private function updateCourseId( int $productId, int $courseId ): void {
		$product = wc_get_product( $productId );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( $courseId > 0 ) {
			$product->update_meta_data( EnrollmentBridge::META_COURSE_ID, $courseId );
		} else {
			$product->delete_meta_data( EnrollmentBridge::META_COURSE_ID );
		}

		$product->save();
	}
}

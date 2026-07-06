<?php
/**
 * Editor-scoped sync mode — request-scoped accumulator.
 *
 * Listens to the four editor-driven hooks during a save request and records
 * which variations were touched and which product-level WC properties moved.
 * The Editor-scoped sync gate reads this accumulator at the WCML entry point
 * to decide whether the variation pipeline needs to run, and which variations
 * it needs to run for.
 *
 * Hooks (verified runtime + WC source — see dev-notes/product-save-performance-analysis.md):
 *  - woocommerce_save_product_variation        ($variationId, $i)   — per saved variation
 *  - before_delete_post                        ($postId, $post)     — filtered to product_variation
 *  - woocommerce_admin_process_product_object  ($product)            — captures WC_Product::get_changes()
 *
 * @package WCML\EditorScopedSync
 */

namespace WCML\EditorScopedSync;

class EditorChangeTracker implements \IWPML_Backend_Action {

	// Must fire before WooCommerce's own save handlers so changes are captured before anything processes them.
	const PRIORITY_BEFORE_WC_SAVES = 0;

	/** @var array<int,array<int,bool>> [parent_id => [variation_id => true]] */
	private static $editedVariationIds = [];

	/** @var array<int,array<int,bool>> [parent_id => [variation_id => true]] */
	private static $deletedVariationIds = [];

	/** @var array<int,array> [parent_id => array from $product->get_changes()] */
	private static $productChanges = [];

	public function add_hooks() {
		add_action( 'woocommerce_save_product_variation', [ __CLASS__, 'recordVariationSave' ], self::PRIORITY_BEFORE_WC_SAVES, 2 );
		add_action( 'before_delete_post', [ __CLASS__, 'recordVariationDelete' ], self::PRIORITY_BEFORE_WC_SAVES, 2 );
		add_action( 'woocommerce_admin_process_product_object', [ __CLASS__, 'recordProductChanges' ], self::PRIORITY_BEFORE_WC_SAVES, 1 );
	}

	/**
	 * @param int $variationId
	 * @param int $i  Index in the variations form (unused but part of the hook signature).
	 */
	public static function recordVariationSave( $variationId, $i ) {
		$variationId = (int) $variationId;
		$parent       = (int) wp_get_post_parent_id( $variationId );
		if ( $parent <= 0 ) {
			return;
		}
		self::$editedVariationIds[ $parent ][ $variationId ] = true;
	}

	/**
	 * @param int      $postId
	 * @param \WP_Post $post
	 */
	public static function recordVariationDelete( $postId, $post = null ) {
		if ( ! $post || 'product_variation' !== $post->post_type ) {
			return;
		}
		$parent = (int) $post->post_parent;
		if ( $parent <= 0 ) {
			return;
		}
		self::$deletedVariationIds[ $parent ][ (int) $postId ] = true;
	}

	/**
	 * @param \WC_Product|mixed $product
	 */
	public static function recordProductChanges( $product ) {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_changes' ) || ! method_exists( $product, 'get_id' ) ) {
			return;
		}
		// Copy the array out — get_changes() empties after the data store's update() runs.
		self::$productChanges[ (int) $product->get_id() ] = $product->get_changes();
	}

	/**
	 * @param int $productId
	 * @return int[] Edited variation IDs for this product (zero-based, deduplicated).
	 */
	public static function editedVariationIdsFor( $productId ) {
		$productId = (int) $productId;
		$ids = isset( self::$editedVariationIds[ $productId ] ) ? self::$editedVariationIds[ $productId ] : [];
		return array_keys( $ids );
	}

	/**
	 * @param int $productId
	 * @return int[]
	 */
	public static function deletedVariationIdsFor( $productId ) {
		$productId = (int) $productId;
		$ids = isset( self::$deletedVariationIds[ $productId ] ) ? self::$deletedVariationIds[ $productId ] : [];
		return array_keys( $ids );
	}

	/**
	 * @param int $productId
	 * @return array WC_Data changes for this product, empty array if none captured.
	 */
	public static function productChangesFor( $productId ) {
		$productId = (int) $productId;
		return isset( self::$productChanges[ $productId ] ) ? self::$productChanges[ $productId ] : [];
	}

	/**
	 * Did *any* variation under this product get saved or deleted in this request?
	 *
	 * @param int $productId
	 * @return bool
	 */
	public static function hasAnyVariationActivity( $productId ) {
		$productId = (int) $productId;
		return ! empty( self::$editedVariationIds[ $productId ] )
			|| ! empty( self::$deletedVariationIds[ $productId ] );
	}

	/**
	 * Test/CLI helper — clear all accumulated state.
	 */
	public static function reset() {
		self::$editedVariationIds  = [];
		self::$deletedVariationIds = [];
		self::$productChanges      = [];
	}
}

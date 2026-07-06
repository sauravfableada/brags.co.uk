<?php

namespace WeDevs\DokanPro\Brands;

use Automattic\WooCommerce\Enums\ProductStatus;

/**
 * Class responsible for migrating products from one brand taxonomy to another
 *
 * This class provides functionality to migrate product brands from a source taxonomy
 * to a target taxonomy, copying all term metadata and relationships.
 *
 * @see   https://github.com/getdokan/dokan/pull/2622
 * @see   https://developer.woocommerce.com/2024/10/01/introducing-brands/
 * @see   https://github.com/woocommerce/woocommerce/pull/50165
 *
 * @since 4.0.5
 */
class YithBrandsMigration {

	/**
	 * Source taxonomy name from which to migrate brands
	 *
	 * @var string
	 */
	protected string $source_taxonomy;

	/**
	 * Target taxonomy name to which brands will be migrated
	 *
	 * @var string
	 */
	protected string $target_taxonomy;

	/**
	 * Number of products to process in each batch
	 *
	 * @var int
	 */
	protected int $batch_size = 20;

	/**
	 * Option name to track migration status
	 *
	 * @var string
	 */
	protected string $migration_option = 'dokan_yith_brands_migration_running';

	/**
	 * Constructor
	 *
	 * @param string $source_taxonomy The source taxonomy to migrate from
	 * @param string $target_taxonomy The target taxonomy to migrate to (defaults to 'product_brand')
	 */
	public function __construct( string $source_taxonomy, string $target_taxonomy = 'product_brand' ) {
		$this->source_taxonomy = $source_taxonomy;
		$this->target_taxonomy = $target_taxonomy;
	}

	/**
	 * Register necessary hooks for the migration process
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'dokan_generate_yith_product_brand_migration_queue', [ $this, 'process_brand_migration_queue' ], 10, 2 );
		add_action( 'dokan_individual_yith_product_brand_migration_queue', [ $this, 'migrate_single_product_brands' ] );
	}

	/**
	 * Check if a migration is currently running
	 *
	 * @return bool
	 */
	public function is_migration_running(): bool {
		return (bool) WC()->queue()->get_next( 'dokan_generate_yith_product_brand_migration_queue', null, 'dokan' );
	}

	/**
	 * Start the migration queue process
	 *
	 * This method initiates the migration by adding a queue job, but only if there are products
	 * to migrate and no existing migration is in progress for the source taxonomy.
	 *
	 * @return void
	 * @throws \RuntimeException If source taxonomy is not set
	 */
	public function start_migration_queue(): void {
		if ( empty( $this->source_taxonomy ) ) {
			throw new \RuntimeException( 'Source taxonomy is not set.' );
		}

		// Check if a migration is already in progress
		if ( $this->is_migration_running() ) {
			dokan_log( 'Migration already in progress for source taxonomy: ' . $this->source_taxonomy );

			return;
		}

		// Check if there are any products to migrate
		if ( count( $this->get_products_with_source_taxonomy( 0, 1 ) ) === 0 ) {
			dokan_log( 'No products found with source taxonomy ' . $this->source_taxonomy . '. Skipping migration.' );

			return;
		}

		$queue_params = [
			'offset' => 0,
			'limit'  => $this->batch_size,
		];

		WC()->queue()->add( 'dokan_generate_yith_product_brand_migration_queue', $queue_params, 'dokan' );
		dokan_log( 'Started brand migration from ' . $this->source_taxonomy . ' to ' . $this->target_taxonomy );
	}

	/**
	 * Process a batch of product brand migrations
	 *
	 * @param int $offset The starting position for this batch
	 * @param int $limit  The number of items to process in this batch
	 *
	 * @return void
	 */
	public function process_brand_migration_queue( int $offset, int $limit ): void {
		$products = $this->get_products_with_source_taxonomy( $offset, $limit );
		if ( empty( $products ) ) {
			dokan_log( 'Brand migration completed.' );

			return;
		}

		foreach ( $products as $product_id ) {
			WC()->queue()->add( 'dokan_individual_yith_product_brand_migration_queue', [ 'product_id' => $product_id ], 'dokan' );
		}

		$queue_params = array(
			'offset' => $offset + $limit,
			'limit'  => $limit,
		);

		WC()->queue()->add( 'dokan_generate_yith_product_brand_migration_queue', $queue_params, 'dokan' );
	}

	/**
	 * Migrate brands for a single product
	 *
	 * @param int $product_id Product_id
	 *
	 * @return void
	 */
	public function migrate_single_product_brands( int $product_id ): void {
		if ( ! $product_id ) {
			return;
		}

		$source_terms = wp_get_object_terms( $product_id, $this->source_taxonomy, [ 'fields' => 'ids' ] );
		if ( empty( $source_terms ) || is_wp_error( $source_terms ) ) {
			return;
		}

		$this->migrate_terms_for_product( $product_id, $source_terms );
		dokan_log( "Migrated brands for product ID: {$product_id}" );
	}

	/**
	 * Migrate term associations for a specific product
	 *
	 * @param int   $product_id      The product ID to migrate terms for
	 * @param array $source_term_ids Array of source term IDs to migrate
	 *
	 * @return void
	 */
	protected function migrate_terms_for_product( int $product_id, array $source_term_ids ): void {
		$target_term_ids = [];
		foreach ( $source_term_ids as $source_term_id ) {
			$source_term = get_term( $source_term_id, $this->source_taxonomy );
			if ( ! $source_term || is_wp_error( $source_term ) ) {
				continue;
			}

			$existing_term = get_term_by( 'name', $source_term->name, $this->target_taxonomy );
			if ( $existing_term ) {
				$target_term_ids[] = $existing_term->term_id;
			} else {
				$new_term = wp_insert_term(
					$source_term->name, $this->target_taxonomy, [
						'description' => $source_term->description,
						'slug'        => $source_term->slug,
					]
				);

				if ( ! is_wp_error( $new_term ) ) {
					$target_term_ids[] = $new_term['term_id'];
					$this->copy_term_meta( $source_term_id, $new_term['term_id'] );
				}
			}
		}

		if ( ! empty( $target_term_ids ) ) {
			wp_set_object_terms( $product_id, $target_term_ids, $this->target_taxonomy, true );
			wp_remove_object_terms( $product_id, $source_term_ids, $this->source_taxonomy );
		}
	}

	/**
	 * Copy all metadata from source term to target term
	 *
	 * @param int $source_term_id Source term ID to copy meta from
	 * @param int $target_term_id Target term ID to copy meta to
	 *
	 * @return void
	 */
	protected function copy_term_meta( int $source_term_id, int $target_term_id ): void {
		$term_meta = get_term_meta( $source_term_id );
		foreach ( $term_meta as $meta_key => $meta_values ) {
			foreach ( $meta_values as $meta_value ) {
				update_term_meta( $target_term_id, $meta_key, maybe_unserialize( $meta_value ) );
			}
		}
	}

	/**
	 * Get products that have terms from the source taxonomy
	 *
	 * @param int $offset The starting position for the query
	 * @param int $limit  The maximum number of products to return
	 *
	 * @return array Array of product IDs
	 */
	public function get_products_with_source_taxonomy( int $offset, int $limit ): array {
		return wc_get_products(
            array(
				'limit'        => $limit,
				'offset'       => $offset,
				'tax_query'    => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => $this->source_taxonomy,
						'operator' => 'EXISTS',
					),
				),
				'return'       => 'ids',
				'orderby'      => 'ID',
				'order'        => 'ASC',
            )
        );
	}
}

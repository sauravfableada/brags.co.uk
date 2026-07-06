<?php

namespace WeDevs\DokanPro\Brands;

/**
 * Class responsible for managing hooks for product brands
 *
 * @deprecated 4.0.5 Use WooCommerce native product brands instead. @see OldBrandsMigration
 */
class Hooks {

    /**
     * YITH brand migration instance
     *
     * @since 4.0.5
     *
     * @var YithBrandsMigration
     */
    protected YithBrandsMigration $yith_brands_migration;

    public function __construct() {
        add_action( 'yith_wcbr_init', [ $this, 'init' ], 11 );

        // REST API: filter products by product_brand taxonomy in React vendor dashboard.
        add_filter( 'dokan_rest_pre_product_listing_args', [ $this, 'filter_products_by_brand_for_api' ], 10, 2 );
    }

    /**
     * Load brand after Dokan Pro init classes
     *
     * @since 2.9.7
     * @deprecated 4.0.5 Use WooCommerce native product brands instead. @see OldBrandsMigration
     *
     * @return void
     */
    public function init() {
        // Migrate old brand taxonomies
        $this->yith_brands_migration = new YithBrandsMigration( $this->get_taxonomy() );
        $this->yith_brands_migration->register_hooks();

        add_filter( 'dokan_admin_notices', [ $this, 'deprecated_brands_migration_notice' ] );
        add_action( 'wp_ajax_dokan_do_old_brands_migrate', [ $this, 'handle_brands_migration_ajax' ] );
    }

	/**
	 * Add a notice to admin dashboard about deprecated YITH Brands integration
	 * and provide migration options to WooCommerce brands.
	 *
	 * This method checks if there are any products using the old YITH brand taxonomy
	 * and adds a warning notice with migration instructions if found.
	 *
	 * @since 4.0.5
	 *
	 * @param array $notices Array of existing admin notices
	 *
	 * @return array Modified array of admin notices
	 */
	public function deprecated_brands_migration_notice( array $notices ): array {
		// Check if we need to migrate old brand taxonomies.
		if ( count( $this->yith_brands_migration->get_products_with_source_taxonomy( 0, 1 ) ) === 0 ) {
			return $notices;
		}

		if ( $this->yith_brands_migration->is_migration_running() ) {
			$notices[] = $this->migration_in_progress_notice();
		} else {
			$notices[] = $this->migration_needed_notice();
		}

		return $notices;
	}

	/**
	 * Create a notice for when migration is needed
	 *
	 * @return array Notice configuration
	 */
	private function migration_needed_notice(): array {
		return array(
			'type'        => 'warning',
			'title'       => __( 'YITH Brands Integration Deprecated', 'dokan' ),
			'description' => __( 'The YITH Brands integration in Dokan Pro is now deprecated and will be removed in a future release. Please migrate your existing YITH brand data to WooCommerce brands to continue using brand functionality.', 'dokan' ),
			'priority'    => 1,
			'scope'       => 'global',
			'actions'     => array(
				array(
					'type'            => 'primary',
					'text'            => __( 'Begin Brand Data Migration', 'dokan' ),
					'loading_text'    => __( 'Migrating Brand Data...', 'dokan' ),
					'competed_text'   => __( 'Brand Data Migration Successful', 'dokan' ),
					'reload'          => true,
					'confirm_message' => __( 'Important: Please back up your database before proceeding with this migration. This process will transfer your YITH WooCommerce product brands to the new WooCommerce brand system. All your brand associations will be preserved, but this action cannot be undone. Are you ready to migrate your product brands now?', 'dokan' ),
					'ajax_data'       => array(
						'action'   => 'dokan_do_old_brands_migrate',
						'_wpnonce' => wp_create_nonce( 'dokan_admin' ),
					),
				),
			),
		);
	}

	/**
	 * Create a notice for when migration is in progress
	 *
	 * @return array Notice configuration
	 */
	private function migration_in_progress_notice(): array {
		$pending_actions_url = admin_url( 'admin.php?page=wc-status&tab=action-scheduler&s=dokan_generate_yith_product_brand_migration_queue&status=pending' );
		return array(
			'type'        => 'info',
			'title'       => __( 'YITH Brands Migration in Progress', 'dokan' ),
			'description' => __( 'The migration of YITH Brands to WooCommerce Brands is currently in progress. This may take a while, so please be patient.', 'dokan' ),
			'priority'    => 1,
			'scope'       => 'global',
			'actions'     => array(
				array(
					'type'   => 'secondary',
					'text'   => __( 'View Migration Progress', 'dokan' ),
					'url'    => $pending_actions_url,
					'status' => 'unactioned',
				),
			),
		);
	}

    /**
     * Handle the AJAX request for brand migration
     *
     * Validates the request and initiates the brand migration queue
     *
     * @since 4.0.5
     *
     * @return void
     */
    public function handle_brands_migration_ajax(): void {
        // Verify nonce
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'dokan_admin' ) ) {
            wp_send_json_error( __( 'Invalid nonce. Security check failed.', 'dokan' ), 403 );
        }

        // Check if user has permission
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'You do not have permission to perform this action.', 'dokan' ), 403 );
        }

        try {
            // Start the migration process
            $this->yith_brands_migration->start_migration_queue();

            // Send success response
            wp_send_json_success(
                array(
                    'message' => __( 'Brand migration process has been initiated successfully. It may take some time to complete in the background.', 'dokan' ),
                )
            );
        } catch ( \Exception $e ) {
            dokan_log( 'Failed to start brand migration: ' . $e->getMessage(), 'error' );
            wp_send_json_error(
                array(
                    'message' => __( 'Failed to start brand migration: ', 'dokan' ) . $e->getMessage(),
                ),
                500
            );
        }
    }

    /**
     * Get Brand taxonomy
     *
     * When premium addon is active, admin can switch
     * taxonomy from admin panel settings
     *
     * @since 4.0.5
     *
     * @return string
     */
    public function get_taxonomy(): string {
        $yith_wcbr = \YITH_WCBR();
        $taxonomy  = $yith_wcbr::$brands_taxonomy;

        if ( function_exists( 'YITH_WCBR_Premium' ) ) {
            $yith_wcbr_premium = \YITH_WCBR_Premium();
            $taxonomy          = $yith_wcbr_premium::$brands_taxonomy;
        }

        return $taxonomy;
    }

    /**
     * Load functionalities
     *
     * @since 3.0.2
     * @deprecated 4.0.5 Use WooCommerce native product brands instead. @see OldBrandsMigration
     *
     * @return void
     */
    public function load_dokan_brands() {
        dokan_pro()->brands->set_is_active( true );

        if ( class_exists( 'YITH_WCBR_Premium' ) ) {
            dokan_pro()->brands->set_is_premium_active( true );
        }

        dokan_pro()->brands->set_settings(
            [
				'mode' => dokan_get_option( 'product_brands_mode', 'dokan_selling', 'single' ),
			]
        );
    }

    /**
     * Set brand for duplicate products
     *
     * @deprecated 4.0.5 Use WooCommerce native product brands instead. @see OldBrandsMigration
     *
     * @param Object $duplicate
     * @param Object $product
     */
    public function set_duplicate_product_brands( $clone_product, $product ) {
        $brands_ids = [];
        $brands     = wp_get_object_terms( $product->get_id(), dokan_pro()->brands->get_taxonomy() );

        if ( count( $brands ) > 0 ) {
            foreach ( $brands as $brand ) {
                $brands_ids[] = $brand->term_id;
            }

            wp_set_object_terms( $clone_product->get_id(), $brands_ids, dokan_pro()->brands->get_taxonomy() );
        }
    }

    /**
     * Set brand for Single Product MultiVendor duplicate products
     *
     * @deprecated 4.0.5 Use WooCommerce native product brands instead. @see OldBrandsMigration
     *
     * @param Object $duplicate
     * @param Object $product
     */
    public function set_spmv_duplicate_product_brands( $clone_product_id, $product_id ) {
        $brands_ids = [];
        $brands     = wp_get_object_terms( $product_id, dokan_pro()->brands->get_taxonomy() );

        if ( count( $brands ) > 0 ) {
            foreach ( $brands as $brand ) {
                $brands_ids[] = $brand->term_id;
            }

            wp_set_object_terms( $clone_product_id, $brands_ids, dokan_pro()->brands->get_taxonomy() );
        }
    }

    /**
     * Add brand input in auction product edit form.
     *
     * @deprecated 4.0.5 Use WooCommerce native product brands instead. @see OldBrandsMigration
     *
     * @param $post_id
     *
     * @return void
     */
    public function add_brand_option_in_auction_edit_form( $post_id ) {
        FormFields::product_edit_form_field( get_post( $post_id ), $post_id );
    }

    /**
     * Add product_brand taxonomy filter to the REST API product listing args.
     *
     * @since 5.0.0
     *
     * @param array           $args    WP_Query args.
     * @param WP_REST_Request $request REST request.
     *
     * @return array
     */
    public function filter_products_by_brand_for_api( array $args, $request ): array {
        $brand_id = absint( $request->get_param( 'product_brand' ) );

        if ( ! $brand_id ) {
            return $args;
        }

        $args['tax_query'][] = [
            'taxonomy'         => 'product_brand',
            'field'            => 'term_id',
            'terms'            => $brand_id,
            'include_children' => false,
        ];

        return $args;
    }
}

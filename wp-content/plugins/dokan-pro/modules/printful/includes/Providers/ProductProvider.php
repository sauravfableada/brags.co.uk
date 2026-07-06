<?php

namespace WeDevs\DokanPro\Modules\Printful\Providers;

use Exception;
use WeDevs\DokanPro\Dependencies\Printful\Exceptions\PrintfulException;
use WeDevs\DokanPro\Dependencies\Printful\PrintfulApiClient;
use WeDevs\DokanPro\Modules\Printful\Auth;
use WeDevs\DokanPro\Modules\Printful\Processors\OrderProcessorInterface;
use WeDevs\DokanPro\Modules\Printful\Processors\PrintfulProductProcessor;
use WeDevs\DokanPro\Modules\Printful\Processors\SizeGuideDataProcessor;

/**
 * Class ProductProvider.
 *
 * @since 3.13.0
 */
class ProductProvider {

    /**
     * OrderProcessor instance.
     *
     * @since 3.13.0
     *
     * @var OrderProcessorInterface[] $processors OrderProcessor instance.
     */
    protected array $processors;

    /**
     * Class constructor.
     */
    public function __construct() {
        add_action( 'post_submitbox_misc_actions', [ $this, 'display_printful_product_label_for_admin' ] );
        add_action( 'dokan_after_product_edit_status_label', [ $this, 'display_printful_product_badge' ] );
        add_action( 'dokan_spmv_exclude_cloning', [ $this, 'exclude_spmv_for_printful_product' ], 10, 2 );
        add_action( 'dokan_edit_product_after_view_product_button', [ $this, 'display_printful_add_size_guide_button' ] );
        add_action( 'dokan_printful_product_add_size_guide', [ $this, 'add_printful_product_size_guide' ], 10, 3 );
        add_action( 'woocommerce_after_variations_table', [ $this, 'render_product_size_guide_link' ], 10 );
        add_action( 'woocommerce_after_variations_table', [ $this, 'include_product_size_guide_popup_template' ], 10 );

        add_filter( 'dokan_spmv_product_search_args', [ $this, 'exclude_printful_products_from_spmv_search' ] );
        add_filter( 'dokan_product_listing_product_type', [ $this, 'display_printful_badge_for_product_listing' ], 10, 2 );
	    add_filter( 'dokan_all_vendors_delivery_time_info', [ $this, 'filter_vendor_delivery_time_by_printful_product' ] );
        add_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'handle_exclude_printful_products_query_var' ], 10, 2 );
    }

    /**
     * Display the Printful product label in the admin post submit box.
     *
     * This method adds a Printful label to the WooCommerce product edit screen in the admin dashboard.
     * The label is displayed only if the product is identified as a Printful product.
     *
     * @since 3.13.0
     *
     * @param \WP_Post $post
     *
     * @return void
     */
    public function display_printful_product_label_for_admin( $post ) {
        // Ensure we have a valid post object and it is of type 'product'.
        if ( ! isset( $post->post_type ) || 'product' !== $post->post_type ) {
            return;
        }

        // Get the WooCommerce product object using the product ID.
        $product = wc_get_product( $post->ID );

        // If the product is not valid, exit the function.
        if ( ! $product ) {
            return;
        }

        // Check if the product is a Printful product. If not, exit the function.
        if ( ! self::is_printful_product( $product ) ) {
            return;
        }

        // Render the Printful badge in the WooCommerce product single page admin screen.
        $this->display_printful_product_label();
    }

    /**
     * Display the Printful product label in the admin post submit box.
     *
     * This method adds a Printful label to the WooCommerce product edit screen in the admin dashboard.
     * The label is displayed only if the product is identified as a Printful product.
     *
     * @since 3.13.0
     *
     * @param bool $exclude
     * @param \WC_Product $product
     *
     * @return bool
     */
    public function exclude_spmv_for_printful_product( bool $exclude, \WC_Product $product ) {
        return self::is_printful_product( $product );
    }

    /**
     * Display Printful add size guide button on vendor product edit page.
     *
     * @since 3.13.0
     *
     * @param \WC_Product $product
     *
     * @return void
     */
    public function display_printful_add_size_guide_button( $product ) {
        // Check if the product is not valid.
        if ( ! $product ) {
            return;
        }

        // Check if the product is a Printful product.
        if ( ! $this->is_printful_product( $product ) ) {
            return;
        }

        // Check if product size guide already exists.
        if ( ! $product->meta_exists( PrintfulProductProcessor::META_KEY_PRINTFUL_SIZE_GUIDE_FAILURE_COUNT ) ) {
            return;
        }

        // Set the context for the template.
        $context = [
            'is_printful' => true,
            'product_id'  => $product->get_id(),
            'catalog_id'  => $product->get_meta( PrintfulProductProcessor::META_KEY_CATALOG_PRODUCT_ID ),
            'vendor_id'   => dokan_get_current_user_id(),
        ];

        // Load and display the Printful badge template.
        dokan_get_template_part( 'printful', 'add-size-guide-button', $context );
    }

    /**
     * Add Printful product size guide to the product meta.
     *
     * @since 3.13.0
     *
     * @param int $product_id Product ID
     * @param int $catalog_id Catalog ID
     * @param int $vendor_id  Vendor ID
     *
     * @throws Exception
     * @return bool
     */
    public function add_printful_product_size_guide( int $product_id, int $catalog_id, int $vendor_id ) {
        $auth = new Auth( $vendor_id );
        if ( ! $auth->is_connected() ) {
            throw new Exception( esc_html__( 'Printful is not connected.', 'dokan' ) );
        }

        $product         = wc_get_product( $product_id );
        $printful_client = PrintfulApiClient::createOauthClient( $auth->get_access_token() );

        try {
            // Fetch size guide data from Printful API.
            $size_guide = $printful_client->get(
                'products/' . $catalog_id . '/sizes?unit=inches,cm',
            );

            // Remove size guide fetching failure meta if exists.
            if ( $product->meta_exists( PrintfulProductProcessor::META_KEY_PRINTFUL_SIZE_GUIDE_FAILURE_COUNT ) ) {
                $product->delete_meta_data( PrintfulProductProcessor::META_KEY_PRINTFUL_SIZE_GUIDE_FAILURE_COUNT );
            }

            $product->add_meta_data( PrintfulProductProcessor::META_KEY_PRODUCT_SIZE_GUIDE, $size_guide, true );
            $product->save();

            return true;
        } catch ( PrintfulException $e ) {
            $request_failed_count = $product->get_meta( PrintfulProductProcessor::META_KEY_PRINTFUL_SIZE_GUIDE_FAILURE_COUNT );
            $request_failed_count = ! empty( $request_failed_count ) ? absint( $request_failed_count ) : 0;

            // Retry to fetch size guide data using WC Queue max twice.
            if ( $request_failed_count <= 2 ) {
                WC()->queue()->schedule_single(
                    time() + ( 3 * MINUTE_IN_SECONDS ),
                    'dokan_printful_product_add_size_guide',
                    [
                        'product_id' => $product_id,
                        'catalog_id' => $catalog_id,
                        'vendor_id'  => $vendor_id,
                    ],
                    'dokan_printful'
                );
            }

            // Update size guide failure count to the product meta.
            $product->update_meta_data( PrintfulProductProcessor::META_KEY_PRINTFUL_SIZE_GUIDE_FAILURE_COUNT, ++$request_failed_count );
            $product->save();

            dokan_log(
                sprintf(
                    /* translators: 1) Request failure count, 2) Product id, 3) Attempt failure error */
                    esc_html__( 'Printful size guide data fetching attempt %1$d has been failed for the product id %2$d due to: %3$s', 'dokan' ),
                    $request_failed_count,
                    $product_id,
                    $e->getMessage()
                )
            );
            throw new Exception( esc_html( $e->getMessage() ), $e->getCode() );
        }
    }

    /**
     * Render Size Guide Link.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function render_product_size_guide_link() {
        global $product;

        // Check if the product is not valid.
        if ( ! $product ) {
            return;
        }

        // Check if the product is not a Printful product.
        if ( ! $this->is_printful_product( $product ) ) {
            return;
        }

        // Check if size guide is not available for the product.
        if ( ! $product->meta_exists( PrintfulProductProcessor::META_KEY_PRODUCT_SIZE_GUIDE ) ) {
            return;
        }


        // Set the context for the template.
        $context = [
            'is_printful'     => true,
            'link_label'      => !  empty( trim( dokan_get_option( 'size_guide_button_text', 'dokan_printful' ) ) ) ? dokan_get_option( 'size_guide_button_text', 'dokan_printful' ) : esc_html__( 'Size Guide', 'dokan' ),
            'link_text_color' => dokan_get_option( 'button_text_color', 'dokan_printful' ),
        ];

        // Load Printful product size guide link template.
        dokan_get_template_part( 'printful', 'product-size-guide-link', $context );
    }

    /**
     * Include Product Size Guide Popup Template.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function include_product_size_guide_popup_template() {
        global $product;

        // Check if the product is not valid.
        if ( ! $product ) {
            return;
        }

        // Check if the product is not a Printful product.
        if ( ! $this->is_printful_product( $product ) ) {
            return;
        }

        $size_guide = $product->get_meta( PrintfulProductProcessor::META_KEY_PRODUCT_SIZE_GUIDE );

        // Check if there is no size guide data.
        if ( ! $size_guide ) {
            return;
        }

        $size_guide_processor = new SizeGuideDataProcessor( $size_guide );

        // Set the context for the template.
        $context = [
            'is_printful'          => true,
            'catalog_product_id'   => $size_guide['catalog_product_id'] ?? 0,
            'available_sizes'      => $size_guide['available_sizes'] ?? [],
            'size_guide_processor' => $size_guide_processor,
            'size_guide_data'      => $size_guide_processor->get_data(),
            'size_guide_styles'    => $this->get_product_size_guide_styles(),
        ];

        // Load Printful product size guide template.
        dokan_get_template_part( 'printful', 'product-size-guide-popup', $context );
    }

	/**
     * Display the Printful product badge in the vendor product edit.
     *
     * This method adds a Printful badge to the vendor product edit screen.
     * The label is displayed only if the product is identified as a Printful product.
     *
     * @since 3.13.0
     *
     * @param \WC_Product $product
     *
     * @return void
     */
    public function display_printful_product_badge( $product ) {
        // Check if the product is a Printful product. If not, exit the function.
        if ( ! self::is_printful_product( $product ) ) {
            return;
        }

	    // Set the context for the template.
	    $context = [ 'is_printful' => true ]; // Placeholder value. Replace with actual logic if needed.

	    // Load and display the Printful badge template.
	    dokan_get_template_part( 'printful', 'badge', $context );
    }

	/**
     * Display the Printful product badge in the vendor product listing.
     *
     * This method adds a Printful badge to the vendor product listing screen.
     * The label is displayed only if the product is identified as a Printful product.
     *
     * @since 3.13.0
     *
     * @param string      $badge
     * @param \WC_Product $product
     *
     * @return string|void
     */
    public function display_printful_badge_for_product_listing( $badge, $product ) {
        // Check if the product is a Printful product. If not, exit the function.
        if ( ! self::is_printful_product( $product ) ) {
            return $badge;
        }

	    // Set the context for the template.
	    $context = [
			'is_printful'          => true,
		    'product_listing_page' => true,
	    ];

	    // Load and display the Printful badge template.
	    return dokan_get_template_part( 'printful', 'badge', $context );
    }

    /**
     * Check if the given product or its variation is a Printful product.
     *
     * This method checks if the provided product or its variation is a Printful product.
     * If a product variation ID or product ID is found with Printful metadata, it returns true.
     * Otherwise, it returns false.
     *
     * @since DOKAN_PROD_SINCE
     *
     * @param \WC_Product $product The WooCommerce product object to check.
     *
     * @return bool True if the product or its variation is a Printful product, false otherwise.
     */
    public static function is_printful_product( \WC_Product $product ): bool {
        // Return true if either meta value indicates it is a Printful product or variation.
        return $product->meta_exists( PrintfulProductProcessor::META_KEY_PRODUCT_ID );
    }

    /**
     * Display the Printful product label.
     *
     * This function checks if a product is a Printful product and displays the corresponding label
     * on the product page or vendor dashboard page. It uses the Dokan template system to render the label.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function display_printful_product_label() {
        // Set the context for the template.
        $context = [ 'is_printful' => true ]; // Placeholder value. Replace with actual logic if needed.

        // Load and display the Printful badge template.
        dokan_get_template_part( 'printful', 'label', $context );
    }

    /**
     * Exclude Printful products from SPMV search arguments.
     *
     * @since 3.13.0
     *
     * @param array $args The original search arguments.
     *
     * @return array Modified search arguments.
     */
    public function exclude_printful_products_from_spmv_search( $args ) {
        $args['exclude_printful_product'] = true;

        return $args;
    }

    /**
     * Handle custom query variable to exclude Printful products.
     *
     * @since 3.13.0
     *
     * @param array $query      The original query arguments.
     * @param array $query_vars The query variables.
     *
     * @return array Modified query arguments.
     */
    public function handle_exclude_printful_products_query_var( $query, $query_vars ) {
        if ( ! empty( $query_vars['exclude_printful_product'] ) ) {
            $query['meta_query'][] = [
                'key'     => PrintfulProductProcessor::META_KEY_PRODUCT_ID,
                'compare' => 'NOT EXISTS',
            ];
        }

        return $query;
    }

	/**
	 * Filter vendor delivery time information based on the presence of Printful products in the cart.
	 *
	 * This method checks if any shipping package in the cart is associated with a Printful product.
	 * If a Printful product is found, it removes the corresponding vendor's delivery time information from the list.
	 *
	 * @since 3.13.0
	 *
	 * @param array $vendor_infos Array containing delivery time information for vendors.
	 *
	 * @return array Filtered array of vendor delivery time information.
	 */
    public function filter_vendor_delivery_time_by_printful_product( array $vendor_infos ): array {
	    foreach ( WC()->cart->get_shipping_packages() as $package ) {
		    if ( ! empty( $package['printful_package'] ) ) {
			    $seller_id = $package['seller_id'] ?? 0;

			    // If the vendor's delivery time information exists in the $vendor_infos array, remove it.
			    if ( isset( $vendor_infos[ $seller_id ] ) ) {
				    unset( $vendor_infos[ $seller_id ] );
			    }
		    }
	    }

	    return $vendor_infos; // Return the filtered array of vendor delivery time information.
    }

    /**
     * Get product size guide styles.
     *
     * @since 3.13.0
     *
     * @return array $styles Size Guide Styles
     */
    public function get_product_size_guide_styles(): array {
        $styles = apply_filters(
            'dokan_printful_product_size_guide_styles',
            [
                'popup_text_color'    => dokan_get_option( 'popup_text_color', 'dokan_printful' ),
                'popup_bg_color'      => dokan_get_option( 'popup_bg_color', 'dokan_printful' ),
                'tab_bg_color'        => dokan_get_option( 'tab_bg_color', 'dokan_printful' ),
                'active_tab_bg_color' => dokan_get_option( 'active_tab_bg_color', 'dokan_printful' ),
            ]
        );

        return $styles;
    }
}

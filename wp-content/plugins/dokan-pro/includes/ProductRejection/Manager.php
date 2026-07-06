<?php

namespace WeDevs\DokanPro\ProductRejection;

use WeDevs\DokanPro\Emails\ProductRejected;
use WeDevs\DokanPro\REST\ProductRejectionController;

/**
 * Product Rejection Manager Class
 *
 * @since 3.16.0
 */
class Manager {

    /**
     * ProductStatusService handler instance.
     *
     * @since 3.16.0
     *
     * @var ProductStatusService
     */
    protected ProductStatusService $product_status_service;

    /**
     * Constructor for the Manager class
     *
     * @since 3.16.0
     */
    public function __construct() {
        $this->init_classes();
        $this->register_hooks();
    }

    /**
     * Get product status service
     *
     * @since 3.16.0
     *
     * @return ProductStatusService
     */
    protected function get_product_status_service(): ProductStatusService {
        if ( ! isset( $this->product_status_service ) ) {
            $this->product_status_service = new ProductStatusService();
        }

        return $this->product_status_service;
    }

    /**
     * Initialize required classes
     *
     * @since 3.16.0
     *
     * @return void
     */
    protected function init_classes(): void {
        new Assets();
        new StatusRollback();
        new RejectionNotifier();
        new Admin( $this->get_product_status_service() );
        new Vendor( $this->get_product_status_service() );
    }

    /**
     * Register hooks
     *
     * @since 3.16.0
     *
     * @return void
     */
    protected function register_hooks(): void {
        add_action( 'wp_loaded', [ $this, 'register_status' ] );

        add_filter( 'woocommerce_email_classes', [ $this, 'register_email_classes' ] );
        add_filter( 'dokan_email_list', [ $this, 'register_email_template' ] );
        add_filter( 'dokan_rest_api_class_map', [ $this, 'rest_api_class_map' ] );
    }


    /**
     * Register custom post statuses for product rejection.
     *
     * @since 3.16.0
     *
     * @return void
     *
     * @see   register_post_status()
     */
    public function register_status(): void {
        register_post_status(
            ProductStatusService::STATUS_REJECTED,
            [
                'label'                     => _x( 'Rejected', 'Product status', 'dokan' ),
                'public'                    => false,
                'protected'                 => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                /* translators: %s: number of products */
                'label_count'               => _n_noop(
                    'Rejected <span class="count">(%s)</span>',
                    'Rejected <span class="counts">(%s)</span>',
                    'dokan'
                ),
            ]
        );
    }

    /**
     * Register email classes
     *
     * @since 3.16.0
     *
     * @param array $email_classes Array of WooCommerce email classes
     *
     * @return array Modified array of email classes
     */
    public function register_email_classes( array $email_classes ): array {
        $email_classes['Dokan_Product_Rejected'] = new ProductRejected();

        return $email_classes;
    }

    /**
     * Register email template
     *
     * @since 3.16.0
     *
     * @param array $templates Array of email template filenames
     *
     * @return array Modified array of email templates
     */
    public function register_email_template( array $templates ): array {
        $templates[] = 'product-rejected.php';

        return $templates;
    }

    /**
     * Register REST API class map
     *
     * @since 3.16.0
     *
     * @param array $class_map Array of REST API class maps
     *
     * @return array Modified array of REST API class maps
     */
    public function rest_api_class_map( array $class_map ): array {
        $class_map[ DOKAN_PRO_INC . '/REST/ProductRejectionController.php' ] = ProductRejectionController::class;

        return $class_map;
    }
}

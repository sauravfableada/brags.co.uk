<?php

namespace WeDevs\DokanPro\Modules\Printful\Vendor;

defined( 'ABSPATH' ) || exit;

/**
 * Printful Disconnect class.
 *
 * @since 3.13.0
 */
class Disconnect {

    /**
     * Class Constructor.
     *
     * @since 3.13.0
     */
    public function __construct() {
        add_action( 'dokan_printful_process_products_status_update_queue', [ $this, 'process_products_status_update_queue' ] );
        add_action( 'dokan_printful_single_product_status_update_to_draft_queue', [ $this, 'update_single_product_status_to_draft' ] );
    }

    /**
     * Process Products Status Update Queue.
     *
     * @since 3.13.0
     *
     * @param $page Page number
     *
     * @return void
     */
    public function process_products_status_update_queue( $page = 1 ) {
        $product_query = dokan()->product->all(
            [
                'posts_per_page' => 20,
                'author'         => dokan_get_current_user_id(),
                'paged'          => $page,
                'meta_query'     => [
                    [
                        'key'     => 'dokan_printful_product_id',
                        'compare' => 'EXISTS',
                    ],
                ],
            ]
        );

        if ( ! $product_query->have_posts() ) {
            return;
        }

        foreach ( $product_query->get_posts() as $post ) {
            WC()->queue()->add(
                'dokan_printful_single_product_status_update_to_draft_queue',
                [
                    'product_id' => $post->ID,
                ],
                'dokan_printful'
            );
        }

        WC()->queue()->add(
            'dokan_printful_products_status_update_to_draft_queue',
            [
                'page' => ++$page,
            ],
            'dokan_printful'
        );
    }

    /**
     * Update Single Product Status to Draft.
     *
     * @since 3.13.0
     *
     * @param $product_id Product ID
     *
     * @return void
     */
    public function update_single_product_status_to_draft( $product_id ) {
        $product = wc_get_product( $product_id );

        // Check if the product is not valid.
        if ( ! $product ) {
            return;
        }

        $product->set_status( 'draft' );
        $product->save();
    }
}

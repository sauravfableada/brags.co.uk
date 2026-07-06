<?php

namespace WeDevs\DokanPro\Modules\Printful\Events;

use Exception;
use WeDevs\DokanPro\Modules\Printful\Processors\PrintfulProductProcessor;

/**
 * Product Deleted Class.
 *
 * @since 3.13.0
 */
class ProductDeleted extends ProductSync {

    /**
     * Process the product deleted event.
     *
     * @since 3.13.0
     */
    public function process() {
        $product_data        = $this->event->rawData['sync_product'] ?? [];
        $printful_product_id = $product_data['id'] ?? 0;

        try {
            $associated_product = $this->get_associated_product_or_variation( $printful_product_id ); // Find the associated product.

            if ( $associated_product ) {
                // Set the product stock status to 'outofstock'
                $product = wc_get_product( $associated_product->ID );
                if ( $product ) {
                    // Disassociate & make `outofstock` the Printful from the product.
                    $product->delete_meta_data( PrintfulProductProcessor::META_KEY_PRODUCT_ID );
                    $product->delete_meta_data( PrintfulProductProcessor::META_KEY_CATALOG_PRODUCT_ID );
                    $product->delete_meta_data( PrintfulProductProcessor::META_KEY_STORE_ID );

                    // Set the product stock status to 'outofstock'.
                    $product->set_manage_stock( true );
                    $product->set_stock_status( 'outofstock' );

                    $product->save();
                }

                dokan_log(
                    sprintf(
                        /* translators: %1$d: Printful product ID, %2$d: WooCommerce product ID */
                        esc_html__( 'Printful product deleted. Product ID: %1$d, Dokan Product ID: %2$d', 'dokan' ),
                        $printful_product_id,
                        $associated_product->ID
                    )
                );

                /**
                 * Actions for allow to perform actions after Printful product deleted.
                 *
                 * @since 3.13.0
                 *
                 * @param \WC_Product $product
                 * @param int         $printful_product_id
                 */
                do_action( 'dokan_pro_printful_product_deleted', $product, $printful_product_id );
            } else {
                dokan_log(
                    sprintf(
                        /* translators: %d: Printful product ID */
                        esc_html__( 'No associated Dokan product found for deleted Printful product. Printful Product ID: %d', 'dokan' ),
                        $printful_product_id
                    )
                );
            }
        } catch ( Exception $e ) {
            dokan_log(
                sprintf(
                    /* translators: %s: Error message */
                    esc_html__( 'Error processing product deleted event. Error: %s', 'dokan' ),
                    $e->getMessage()
                )
            );
        }
    }
}

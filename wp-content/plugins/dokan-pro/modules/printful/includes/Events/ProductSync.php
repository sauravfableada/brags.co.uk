<?php

namespace WeDevs\DokanPro\Modules\Printful\Events;

use Exception;
use WeDevs\Dokan\Cache;
use WeDevs\DokanPro\Modules\Printful\Processors\PrintfulProductProcessor;
use WP_Post;

/**
 * Class ProductSync.
 *
 * @since 3.13.0
 *
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */
class ProductSync extends AbstractEvent {

    /**
     * Process the event.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function process() {
        $product_data    = $this->event->rawData['sync_product'];
        $product_id      = $product_data['id'];
        $previous_status = 'publish';
        $external_id     = 0;

        try {
            $associated_product = $this->get_associated_product_or_variation( $product_id );

            if ( $associated_product ) {
                $external_id     = $associated_product->ID;
                $previous_status = $associated_product->post_status;
            }
        } catch ( Exception $e ) {
            dokan_log( $e->getMessage() );
            return;
        }

        $args = [
            'type'   => 'variable',
            'status' => 'draft',
            'author' => $this->vendor_id,
            'name'   => $product_data['name'] . ' ' . esc_html__( '(Processing)', 'dokan' ),
        ];

        try {
            if ( ! empty( $external_id ) ) {
                $args['id'] = $external_id;
                $product    = dokan()->product->update( $args );
            } else {
                $product = dokan()->product->create( $args );
            }
            $product->add_meta_data( PrintfulProductProcessor::META_KEY_PRODUCT_ID, $product_id, true );
        } catch ( Exception $e ) {
            dokan_log(
                sprintf(
                // translators: 1: Product ID 2: External Product ID 3: Vendor ID 4: Error message.
                    esc_html__(
                        'Product Sync failed for webhook processing. Product ID: %1$d, External Product ID: %2$d, Vendor ID: %3$d. Error: %4$s',
                        'dokan'
                    ),
                    $product_id,
                    $external_id,
                    $this->vendor_id,
                    $e->getMessage()
                )
            );

            return;
        }

        $product->save();
        $this->update_author( $product->get_id() );

        $filtered_data = apply_filters(
            'dokan_update_product_post_data', [
				'ID'          => $product->get_id(),
				'post_title'  => $product_data['name'],
				'post_status' => $previous_status,
			]
        );

        $previous_status = $filtered_data['post_status'];

        // Invalidate product data cache from listing before printful product process.
        Cache::invalidate_group( 'seller_product_data_' . $this->vendor_id );

        WC()->queue()->add(
            'dokan_pro_printful_product_processor',
            [
                'printful_product_id' => $product_id,
                'dokan_product_id'    => $product->get_id(),
                'vendor_id'           => $this->vendor_id,
                'previous_status'     => $previous_status,
            ],
            'dokan_pro_printful_product_processor'
        );
    }


    /**
     * Get WC product associated with a Printful product.
     *
     * @since 3.13.0
     *
     * @phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
     *
     * @param int    $product_id Printful product ID.
     * @param string $type       Product type.
     *
     * @return WP_Post|false
     */
    public function get_associated_product_or_variation( int $product_id, string $type = 'product' ) {
        $key = PrintfulProductProcessor::META_KEY_PRODUCT_VARIATION_ID;
        $post_type = 'product_variation';

        if ( 'product' === $type ) {
            $key = PrintfulProductProcessor::META_KEY_PRODUCT_ID;
            $post_type = 'product';
        }

        $product_query = dokan()->product->all(
            [
                'posts_per_page' => 1,
                'post_type'      => $post_type,
                'author'         => $this->vendor_id,
                'meta_query'     => [
                    [
                        'key'   => $key,
                        'value' => absint( $product_id ),
                    ],
                ],
            ]
        );

        if ( $product_query->have_posts() ) {
            return $product_query->get_posts()[0];
        }

        return false;
    }

    /**
     * Update product or variation author.
     *
     *
     * @param int $product Product or Variation ID.
     *
     * @return int|\WP_Error
     */
    protected function update_author( int $product ) {
        return wp_update_post(
            [
                'ID'          => $product,
                'post_author' => $this->vendor_id,
            ]
        );
    }
}

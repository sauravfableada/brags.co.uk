<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders\BackgroundProcesses;

use WeDevs\Dokan\Abstracts\DokanBackgroundProcesses;

/**
 * Dokan V_3_9_0 Upgrade Background Processor Class.
 *
 * @since 3.9.0
 */
class V_3_9_0 extends DokanBackgroundProcesses {

    /**
     * Action
     *
     * Override this action in processor class
     *
     * @since 3.9.0
     *
     * @var string
     */
    protected $action = 'dokan_pro_bg_action_3_9_0';

    /**
     * Perform Updates.
     *
     * @since 3.9.0
     *
     * @param mixed $item
     *
     * @return mixed
     */
    public function task( $item ) {
        if ( empty( $item ) ) {
            return false;
        }

        if ( 'set_product_status_to_publish_from_vacation' === $item['task'] ) {
            return $this->update_product_status_to_publish_from_vacation( $item['paged'] );
        }

        if ( 'add_id_in_woocommerce_product_addon' === $item['task'] ) {
            return $this->add_product_addon_ids( $item['data'] );
        }

        return false;
    }

    /**
     * Update Product Status to Publish from Vacation.
     *
     * @since 3.9.0
     *
     * @return array|bool
     */
    private function update_product_status_to_publish_from_vacation( $paged ) {
        $limit            = 50;
        $offset           = $limit * $paged;
        $wc_product_types = array_keys( wc_get_product_types() );

        $wc_product_types[] = 'booking';

        $args = [
            'limit'  => $limit,
            'offset' => $offset,
            'type'   => $wc_product_types,
            'status' => 'vacation',
        ];

        $products = wc_get_products( $args );

        if ( ! $products ) {
            return false;
        }

        foreach ( $products as $product ) {
            // If the product status isn't "vacation".
            if ( 'vacation' !== $product->get_status() ) {
                continue;
            }

            $product->set_status( 'publish' );
            $product->save();
        }

        return [
            'task' => 'set_product_status_to_publish_from_vacation',
            'paged'    => ++$paged,
        ];
    }

    /**
     * Add id in product addon data.
     *
     * @since 3.9.0
     *
     * @return bool
     */
    public function add_product_addon_ids( $meta_datas ) {
        if ( empty( $meta_datas ) && ! is_array( $meta_datas ) ) {
            return false;
        }

        foreach ( $meta_datas as $meta_data ) {
            if ( ! isset( $meta_data['meta_value'] ) ) {
                continue;
            }

            $post_id    = $meta_data['post_id'];
            $meta_key   = $meta_data['meta_key'];
            $meta_value = $this->process_meta_values( $meta_data['meta_value'] );

            update_post_meta( $post_id, $meta_key, $meta_value );
        }

        return false;
    }

    /**
     * Checks and adds id key in product addon array.
     *
     * @since 3.9.0
     *
     * @param $meta_values
     *
     * @return mixed|string
     */
    public function process_meta_values( $meta_values ) {
        $meta_values = maybe_unserialize( $meta_values );

        foreach ( $meta_values as $key => &$meta_value ) {
            if ( ! isset( $meta_value['id'] ) && isset( $meta_value['name'] ) ) {
                $meta_value['id'] = dokan_get_random_string();
            }
        }

        return $meta_values;
    }
}

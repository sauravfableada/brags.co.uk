<?php

namespace WeDevs\DokanPro\Product;

use WeDevs\Dokan\ProductEditor\Elements;
use WeDevs\Dokan\ProductEditor\PayloadResolver as BasePayloadResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves variation form payload (schema field ids) to WooCommerce REST API shape.
 *
 * @since 5.0.0
 */
class PayloadResolver extends BasePayloadResolver {

    /**
     * Transform request body from schema field ids to WC REST product variation API shape.
     *
     * @since 5.0.0
     *
     * @param array $data Request body.
     *
     * @return array Data suitable for WC REST variation create/update.
     */
    public static function resolve( array $data ): array {
        $out = parent::resolve( $data );
        $resolver = new static();
        $out = $resolver->resolve_others_field( $out );
        return apply_filters( 'dokan_product_editor_variation_payload', $out, $data );
    }

    /**
     * Transform image_id into the WC REST variation image format: { id: int }.
     *
     * @since 5.0.0
     */
    public function resolve_others_field( array $data ): array {
        // parent::resolve() already transformed image_id into images array.
        // Convert to singular image format for variations.
        if ( ! empty( $data['images'] ) && is_array( $data['images'] ) ) {
            $data['image'] = $data['images'][0];
            unset( $data['images'] );
        }

        $data['status'] = ! empty( $data[ Elements::ENABLED ] ) ? 'publish' : 'draft';
        unset( $data[ Elements::ENABLED ] );

        return $data;
    }

    /**
     * Transform attributes to the WC REST API shape.
     *
     * @since 5.0.0
     */
    public function transform_attributes( array $attributes ): array {
        $result = [];
        foreach ( $attributes as $attr ) {
            $option = $attr['option'] ?? '';

            $result[] = [
                'id'     => isset( $attr['id'] ) ? (int) $attr['id'] : 0,
                'name'   => isset( $attr['name'] ) ? (string) $attr['name'] : '',
                'option' => $option,
            ];
        }
        return $result;
    }
}

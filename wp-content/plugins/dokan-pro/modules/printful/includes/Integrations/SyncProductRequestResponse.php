<?php

namespace WeDevs\DokanPro\Modules\Printful\Integrations;

use WeDevs\DokanPro\Dependencies\Printful\Structures\Sync\Responses\SyncProductResponse;

/**
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase,WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */
class SyncProductRequestResponse extends \WeDevs\DokanPro\Dependencies\Printful\Structures\Sync\Responses\SyncProductRequestResponse {
    /**
     * Creates SyncProductResponse from array
     *
     * @phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound
     *
     * @param array $array
     * @return SyncProductRequestResponse
     */
    public static function fromArray( array $array ) {
        $response = new SyncProductRequestResponse();

        $productArray = $array['sync_product'];
        $response->syncProduct = SyncProductResponse::fromArray( $productArray );

        $variantArray = $array['sync_variants'];
        foreach ( $variantArray as $item ) {
            $response->syncVariants[] = SyncVariantResponse::fromArray( $item );
        }

        return $response;
    }
}

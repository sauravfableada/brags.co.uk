<?php

namespace WeDevs\DokanPro\Modules\Printful\Integrations;

use WeDevs\DokanPro\Dependencies\Printful\Structures\File;
use WeDevs\DokanPro\Dependencies\Printful\Structures\Sync\Responses\SyncVariantOptionResponse;
use WeDevs\DokanPro\Dependencies\Printful\Structures\Sync\Responses\SyncVariantProductResponse;

class SyncVariantResponse extends \WeDevs\DokanPro\Dependencies\Printful\Structures\Sync\Responses\SyncVariantResponse {
    public $color;
    public $size;

    public static function fromArray( array $array ) {
        $variant = new SyncVariantResponse();

        $variant->id = (int) $array['id'];
        $variant->externalId = (string) $array['external_id'];
        $variant->syncProductId = (string) $array['sync_product_id'];
        $variant->name = (string) $array['name'];
        $variant->synced = (bool) $array['synced'];
        $variant->variantId = (int) $array['variant_id'];
        $variant->retailPrice = (float) $array['retail_price'];
        $variant->currency = (string) $array['currency'];
        $variant->size = (string) $array['size'];
        $variant->color = (string) $array['color'];
        $variant->product = SyncVariantProductResponse::fromArray( $array['product'] );

        $variantFiles = (array) $array['files'] ?: [];
        foreach ( $variantFiles as $file ) {
            $variant->files[] = File::fromArray( $file );
        }

        $variantOptions = (array) $array['options'] ?: [];
        foreach ( $variantOptions as $option ) {
            $variant->options[] = SyncVariantOptionResponse::fromArray( $option );
        }

        return $variant;
    }
}

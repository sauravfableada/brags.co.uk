<?php

namespace WeDevs\DokanPro\Modules\Printful\Shipping;

use WeDevs\DokanPro\Modules\Printful\Processors\PrintfulOrderProcessor;

/**
 * Split shipping package for only Printful products.
 *
 * @since 3.13.0
 */
class PrintfulShippingPackageSplitter extends ShippingPackageSplitter {

    const PACKAGE_KEY = 'printful_package';

    /**
     * Split shipping packages.
     *
     * @since 3.13.0
     *
     * @param array $packages Shipping Package.
     *
     * @return array
     */
    public function split( array $packages ): array {
        $processed_packages = [];
        foreach ( $packages as $key => $package ) {
            $items = [];
            foreach ( $package['contents'] as $item_id => $item ) {
				if ( ! empty( $item[ PrintfulOrderProcessor::META_KEY_VARIANT_ID ] ) ) {
					$items[] = $item;
                    unset( $package['contents'][ $item_id ] );
				}
            }

            if ( ! empty( $items ) ) {
                $new_package = $package;
                $new_package['contents'] = $items;
                $new_package[ self::PACKAGE_KEY ] = true;
                $processed_packages[] = $new_package;
            }

            if ( ! empty( $package['contents'] ) ) {
                $processed_packages[] = $package;
            }
        }

        return parent::split( $processed_packages );
    }
}

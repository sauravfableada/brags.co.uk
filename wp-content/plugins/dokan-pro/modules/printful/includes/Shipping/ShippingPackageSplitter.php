<?php

namespace WeDevs\DokanPro\Modules\Printful\Shipping;

/**
 * Shipping package splitter class.
 *
 * This class is responsible for detecting POD services and splitting shipping package in multiple package
 * containing items from different POD services.
 *
 * @since 3.13.0
 *
 * @package WeDevs\DokanPro\Modules\Printful\Shipping
 */
abstract class ShippingPackageSplitter {
    /**
     * Next splitter in chain.
     *
     * @since 3.13.0
     *
     * @var null|ShippingPackageSplitter
     */
    protected ?ShippingPackageSplitter $next = null;

    /**
     * Sets next splitter in chain.
     *
     * @since 3.13.0
     *
     * @param ShippingPackageSplitter $next
     *
     * @return ShippingPackageSplitter
     */
    public function set_next( ShippingPackageSplitter $next ): ShippingPackageSplitter {
        $this->next = $next;

        return $next;
    }

    /**
     * Splits shipping packages.
     *
     * @since 3.13.0
     *
     * @param array $packages
     *
     * @return array
     */
    public function split( array $packages ): array {
        array_walk(
            $packages,
            function ( &$package ) {
				$package['contents_cost'] = array_sum( wp_list_pluck( $package['contents'], 'line_total' ) );
			}
        );

        if ( ! is_null( $this->next ) ) {
            return $this->next->split( $packages );
        }

        return $packages;
    }
}

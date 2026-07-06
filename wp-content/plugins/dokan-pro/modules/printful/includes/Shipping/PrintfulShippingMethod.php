<?php

namespace WeDevs\DokanPro\Modules\Printful\Shipping;

use Exception;
use WC_Shipping_Method;
use WeDevs\DokanPro\Dependencies\Printful\Exceptions\PrintfulException;
use WeDevs\DokanPro\Dependencies\Printful\PrintfulApiClient;
use WeDevs\DokanPro\Modules\Printful\Auth;
use WeDevs\DokanPro\Modules\Printful\Processors\PrintfulOrderProcessor;

class PrintfulShippingMethod extends WC_Shipping_Method {
    /**
     * Constructor. The instance ID is passed to this.
     */
    public function __construct( $instance_id = 0 ) {
        $this->id                 = 'dokan_printful_shipping';
        $this->method_title       = __( 'Printful Method', 'dokan' );
        $this->method_description = __( 'Dokan Printful Shipping method.', 'dokan' );
        $this->supports           = array();
        $this->enabled            = 'yes';
		$this->title              = __( 'Printful Shipping title', 'dokan' );

        parent::__construct( $instance_id );
	}

    /**
     * Calculate_shipping function.
     *
     * @param array $package (default: array())
     */
    public function calculate_shipping( $package = array() ) {
        try {
            $rates = $this->get_printful_rates( $package );
        } catch ( Exception $e ) {
            return;
        }

        foreach ( $rates as $rate_order => $rate ) {
            $this->add_rate(
                array(
                    'id'    => $this->id . ':' . $rate['id'],
                    'label' => esc_html( $rate['name'] ),
                    'cost'  => $rate['rate'],
                    'meta_data' => array(
                        'printful_rate_id' => $rate['id'], // The Printful rate ID
                    ), // Array of misc meta data to store along with this rate - key value pairs.
                    'package'   => $package, // The package for which this rate is calculated.
                )
            );
        }
    }

    /**
     * Get Printful rates.
     *
     * @since 3.13.0
     *
     * @param array $package Package.
     *
     * @return array
     * @throws Exception
     */
    protected function get_printful_rates( array $package ): array {
        $vendor_id = $package['seller_id'];

        $auth = new Auth( $vendor_id );
        if ( ! $auth->is_connected() ) {
            throw new Exception( esc_html__( 'Printful is not connected.', 'dokan' ) );
        }

        $printful_client = PrintfulApiClient::createOauthClient( $auth->get_access_token() );

        try {
            // Calculate shipping rates for an order

            $items = [];
            foreach ( $package['contents'] as $item_id => $item ) {
                $items[] = [
                    'external_variant_id' => $item[ PrintfulOrderProcessor::META_KEY_EXTERNAL_VARIANT_ID ],
                    'quantity' => $item['quantity'],
                ];
            }

            $rates = $printful_client->post(
                'shipping/rates',
                [
					'recipient' => [
                        'address1' => $package['destination']['address'],
                        'city' => $package['destination']['city'],
                        'state_code' => $package['destination']['state'],
                        'country_code' => $package['destination']['country'],
                        'zip' => $package['destination']['postcode'],
					],
					'items' => $items,
					'currency' => get_woocommerce_currency(),
					'locale' => get_locale(),
				]
            );
        } catch ( PrintfulException $e ) {
            //API response status code was not successful
            // TODO: Log the error with vendor, package and other information
            dokan_log( $e->getMessage() );
            throw new Exception( esc_html( $e->getMessage() ) );
        }

        return $rates;
    }
}

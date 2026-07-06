<?php

namespace WeDevs\DokanPro\Modules\Paystack\Api;

use Exception;
use WeDevs\Dokan\Exceptions\DokanException;
use WeDevs\DokanPro\Dependencies\GuzzleHttp\Exception\GuzzleException;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class SubAccount extends AbstractService {
    /**
     * Create a new subaccount.
     *
     * @param array $data
     *
     * @return array
     * @throws DokanException
     */
    public static function create( array $data ): array {
        try {
            $response = static::api()->client->post( 'subaccount', [ 'json' => $data ] );
            return static::to_array( $response->getBody() );
        } catch ( GuzzleException $e ) {
            dokan_log(
                sprintf(
                    'Paystack subaccount creation failed: %s',
                    $e->getMessage()
                ),
                'error'
            );
            throw new DokanException(
                'paystack_subaccount_creation_failed',
                esc_html__( 'Failed to create Paystack subaccount.', 'dokan' )
            );
        }
    }

    /**
     * Get a subaccount by ID.
     *
     * @param string $subaccount
     *
     * @return array
     * @throws Exception
     */
    public static function get( string $subaccount ): array {
        try {
            $response = static::api()->client->get( 'subaccount/' . $subaccount );
            return static::to_array( $response->getBody() );
        } catch ( GuzzleException $e ) {
            dokan_log(
                sprintf(
                    'Paystack subaccount retrieval failed: %s',
                    $e->getMessage()
                ),
                'error'
            );
            throw new DokanException(
                'paystack_subaccount_retrieval_failed',
                esc_html__( 'Failed to retrieve Paystack subaccount.', 'dokan' )
            );
        }
    }
}

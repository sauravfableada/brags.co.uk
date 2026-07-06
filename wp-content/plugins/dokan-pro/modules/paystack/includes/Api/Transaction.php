<?php

namespace WeDevs\DokanPro\Modules\Paystack\Api;

use WeDevs\Dokan\Exceptions\DokanException;
use WeDevs\DokanPro\Dependencies\GuzzleHttp\Exception\GuzzleException;
use WeDevs\DokanPro\Dependencies\GuzzleHttp\Exception\RequestException;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Transaction extends AbstractService {

    /**
     * Initialize a transaction.
     *
     * @param array $data
     *
     * @since 4.1.1
     *
     * @return mixed
     * @throws DokanException If the transaction initialization fails
     */
    public static function initialize( array $data ) {
        try {
            $response = static::api()->client->post( 'transaction/initialize', [ 'json' => $data ] );
			return static::to_array( $response->getBody() );
        } catch ( GuzzleException $e ) {
            dokan_log(
                sprintf(
                    'Paystack transaction initialization failed: %s',
                    $e->getMessage()
                ),
                'error'
            );
            $error_message = __( 'Paystack transaction initializing error', 'dokan' );
            if ( $e instanceof RequestException && $e->hasResponse() ) {
                $response = $e->getResponse();
                $error = static::to_array( $response->getBody() );
                if ( isset( $error['message'] ) ) {
                    $error_message .= $error['message'];
                }
            }
            throw new DokanException(
                'dokan_paystack_transaction_initialization_failed',
                esc_html( $error_message )
            );
        }
    }

    /**
     * Verify a transaction.
     *
     * @param string $reference
     *
     * @since 4.1.1
     *
     * @return mixed
     * @throws DokanException
     */
    public static function verify( string $reference ) {
        try {
            $response = static::api()->client->get( "transaction/verify/$reference" );
            return static::to_array( $response->getBody() );
        } catch ( GuzzleException $e ) {
            dokan_log(
                sprintf(
                    'Paystack transaction verification failed: %s',
                    $e->getMessage()
                ),
                'error'
            );
            $error_message = __( 'Paystack transaction verification error', 'dokan' );
            if ( $e instanceof RequestException && $e->hasResponse() ) {
                $response = $e->getResponse();
                $error = static::to_array( $response->getBody() );
                if ( isset( $error['message'] ) ) {
                    $error_message .= $error['message'];
                }
            }
            throw new DokanException(
                'dokan_paystack_transaction_verification_failed',
                esc_html( $error_message )
            );
        }
    }
}

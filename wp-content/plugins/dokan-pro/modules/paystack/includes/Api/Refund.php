<?php

namespace WeDevs\DokanPro\Modules\Paystack\Api;

use WeDevs\Dokan\Exceptions\DokanException;
use WeDevs\DokanPro\Dependencies\GuzzleHttp\Exception\GuzzleException;
use WeDevs\DokanPro\Dependencies\GuzzleHttp\Exception\RequestException;

class Refund extends AbstractService {

    /**
     * Create a new split payment.
     *
     * @param array $data
     *
     * @return array
     * @throws DokanException If the refund creation fails
     */
    public static function create( array $data ): array {
        try {
            $response = static::api()->client->post( 'refund', [ 'json' => $data ] );
            return static::to_array( $response->getBody() );
        } catch ( GuzzleException $e ) {
            dokan_log(
                sprintf(
                    'Paystack refund failed: %s',
                    $e->getMessage()
                ),
                'error'
            );
            $error_message = __( 'Failed to create Paystack refund.', 'dokan' );
            if ( $e instanceof RequestException && $e->hasResponse() ) {
                $response = $e->getResponse();
                $error = static::to_array( $response->getBody() );
                if ( isset( $error['message'] ) ) {
                    $error_message .= $error['message'];
                }
            }
            throw new DokanException(
                'dokan_paystack_refund_creation_failed',
                esc_html( $error_message )
            );
        }
    }
}

<?php

namespace WeDevs\DokanPro\Modules\Paystack\Api;

use WeDevs\Dokan\Exceptions\DokanException;
use WeDevs\DokanPro\Dependencies\GuzzleHttp\Exception\GuzzleException;
use WeDevs\DokanPro\Dependencies\GuzzleHttp\Exception\RequestException;

class SplitPayment extends AbstractService {

    /**
     * Create a new split payment.
     *
     * @param array $data
     *
     * @return array
     * @throws DokanException If the split payment creation fails
     */
    public static function create( array $data ): array {
        try {
            $response = static::api()->client->post( 'split', [ 'json' => $data ] );
            return static::to_array( $response->getBody() );
        } catch ( GuzzleException $e ) {
            dokan_log(
                sprintf(
                    'Paystack split payment failed: %s',
                    $e->getMessage()
                ),
                'error'
            );
            $error_message = __( 'Paystack split payment failed: ', 'dokan' );
            if ( $e instanceof RequestException && $e->hasResponse() ) {
                $response = $e->getResponse();
                $error = static::to_array( $response->getBody() );
                if ( isset( $error['message'] ) ) {
                    $error_message .= $error['message'];
                }
            }
            throw new DokanException(
                'dokan_paystack_payment_split_failed',
                esc_html( $error_message )
            );
        }
    }
}

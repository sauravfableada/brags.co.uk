<?php

namespace WeDevs\DokanPro\Modules\Paystack\Api;

use WeDevs\Dokan\Exceptions\DokanException;
use WeDevs\DokanPro\Dependencies\GuzzleHttp\Exception\GuzzleException;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Bank extends AbstractService {

    /**
     * List all banks.
     *
     * @param array $params
     *
     * @return array
     * @throws DokanException If the bank list retrieval fails
     */
    public static function list( array $params = [] ): array {
        try {
			$params['currency'] = apply_filters( 'dokan_paystack_bank_currency', get_woocommerce_currency() );
			$response = static::api()->client->get( 'bank', [ 'query' => $params ] );
			return apply_filters( 'dokan_paystack_bank_list_response', static::to_array( $response->getBody() ) );
        } catch ( GuzzleException $e ) {
            throw new DokanException(
                'paystack_bank_list_failed',
                esc_html__( 'Failed to retrieve Paystack banks.', 'dokan' )
            );
        }
    }
}

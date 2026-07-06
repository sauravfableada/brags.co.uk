<?php

namespace WeDevs\DokanPro\Modules\Paystack\Api;

use WeDevs\DokanPro\Dependencies\GuzzleHttp\Client;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class PaystackClient extends Client {
	private string $api_base_url = 'https://api.paystack.co/'; // with trailing slash

    public function __construct( string $secret_key ) {
        parent::__construct(
            [
				'base_uri' => $this->api_base_url,
				'headers' => [
					'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
					'Authorization'     => 'Bearer ' . $secret_key,
				],
			]
        );
    }
}

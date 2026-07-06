<?php

namespace WeDevs\DokanPro\Modules\Paystack\Api;

use WeDevs\DokanPro\Modules\Paystack\Support\Helper;

abstract class AbstractService {
    /**
     * The Paystack client instance.
     *
     * @var PaystackClient
     */
	protected PaystackClient $client;

    /**
     * Singleton instance of the service.
     *
     * @var AbstractService|null
     */
    protected static ?AbstractService $instance = null;

    /**
     * Paystack constructor.
     */
    public function __construct() {
        $this->client = new PaystackClient( Helper::get_api_secret() );
    }
    /**
     * Convert data to JSON format.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    protected static function to_array( $data ) {
        return json_decode( $data, true );
    }

    /**
     * Get the singleton instance of the service.
     *
     * @return AbstractService|null
     */
    protected static function api(): ?AbstractService {
        if ( null === self::$instance ) {
            self::$instance = new static();
        }

        return self::$instance;
    }
}

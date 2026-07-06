<?php

namespace WeDevs\DokanPro\Modules\VendorAnalytics;

use Google\Auth\FetchAuthTokenInterface;

if ( interface_exists( '\\Automattic\\WooCommerce\\GoogleListingsAndAds\\Vendor\\Google\\Auth\\FetchAuthTokenInterface' ) ) {
    \class_alias(
        '\\Automattic\\WooCommerce\\GoogleListingsAndAds\\Vendor\\Google\\Auth\\FetchAuthTokenInterface',
        __NAMESPACE__ . '\\CompatFetchAuthTokenInterface'
    );
} else {
    interface CompatFetchAuthTokenInterface {}
}


class Token implements FetchAuthTokenInterface, CompatFetchAuthTokenInterface {

    /**
     * @var \Dokan_Client
     */
    protected $auth;

    public function __construct( $auth = null ) {
        $this->auth = $auth;
    }

    /**
	 * @inheritDoc
	 */
	public function fetchAuthToken( callable $httpHandler = null ) {
        return json_decode( $this->auth->getAccessToken(), true );
	}

	/**
	 * @inheritDoc
	 */
	public function getCacheKey() {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getLastReceivedToken() {
		return json_decode( $this->auth->getAccessToken(), true );
	}
}

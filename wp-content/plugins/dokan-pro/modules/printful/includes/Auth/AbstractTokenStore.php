<?php

namespace WeDevs\DokanPro\Modules\Printful\Auth;

use WeDevs\DokanPro\Dependencies\League\OAuth2\Client\Token\AccessToken;

/**
 * Token Store.
 *
 * @since 3.13.0
 */
abstract class AbstractTokenStore {

    /**
     * Vendor ID.
     *
     * @since 3.13.0
     *
     * @var int $vendor_id Vendor ID.
     */
    protected int $vendor_id = 0;

    /**
     * Constructor.
     *
     * @param int $vendor_id Vendor ID.
     */
    public function __construct( int $vendor_id ) {
        $this->vendor_id = $vendor_id;
    }

    /**
     * Get Access Token Object.
     *
     * @since 3.13.0
     *
     * @return AccessToken
     */
    abstract public function get_token(): AccessToken;

    /**
     * Set Token in storage.
     *
     * @since 3.13.0
     *
     * @param AccessToken $access_token Token Object.
     *
     * @return AbstractTokenStore
     */
    abstract public function set_token( AccessToken $access_token ): AbstractTokenStore;

    /**
     * Delete Token from storage.
     *
     * @since 3.13.0
     *
     * @return bool
     */
    abstract public function delete_token(): bool;

    /**
     * Search Token user in storage by printful store id.
     *
     * @since 3.13.0
     *
     * @param int $printful_store_id Printful Store ID.
     *
     * @return int
     */
    abstract public static function search( int $printful_store_id ): int;
}

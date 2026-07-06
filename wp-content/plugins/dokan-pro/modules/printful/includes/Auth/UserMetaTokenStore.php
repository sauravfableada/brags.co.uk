<?php

namespace WeDevs\DokanPro\Modules\Printful\Auth;

use WeDevs\DokanPro\Dependencies\League\OAuth2\Client\Token\AccessToken;
use WP_User_Query;

/**
 * Token Store implementing User meta.
 *
 * @since 3.13.0
 */
class UserMetaTokenStore extends AbstractTokenStore {

    /**
     * User meta keys for access token.
     *
     * @since 3.13.0
     */
    const KEY_ACCESS_TOKEN = 'dokan_printful_access_token';

    /**
     * User meta keys for refresh token.
     *
     * @since 3.13.0
     */
    const KEY_REFRESH_TOKEN = 'dokan_printful_refresh_token';

    /**
     * User meta keys for expired at.
     *
     * @since 3.13.0
     */
    const KEY_EXPIRED_AT = 'dokan_printful_expired_at';

    /**
     * User meta keys for store id.
     *
     * @since 3.13.0
     */
    const KEY_STORE_ID = 'dokan_printful_store_id';

    /**
     * Get token.
     *
     * @since 3.13.0
     *
     * @return AccessToken
     */
	public function get_token(): AccessToken {
        $access_token      = $this->get_meta( self::KEY_ACCESS_TOKEN );
        $refresh_token     = $this->get_meta( self::KEY_REFRESH_TOKEN );
        $expired_at        = $this->get_meta( self::KEY_EXPIRED_AT );
        $printful_store_id = $this->get_meta( self::KEY_STORE_ID );

        return new AccessToken(
            [
                'access_token'      => $access_token,
                'refresh_token'     => $refresh_token,
                'expires'           => $expired_at,
                'resource_owner_id' => $printful_store_id,
            ]
        );
	}

    /**
     * Set and Store token in User meta.
     *
     * @since 3.13.0
     *
     * @param AccessToken $access_token
     *
     * @return UserMetaTokenStore
     */
	public function set_token( AccessToken $access_token ): UserMetaTokenStore {
        $this->set_meta( self::KEY_ACCESS_TOKEN, $access_token->getToken() );
        $this->set_meta( self::KEY_REFRESH_TOKEN, $access_token->getRefreshToken() );
        $this->set_meta( self::KEY_EXPIRED_AT, $access_token->getExpires() );
        $this->set_meta( self::KEY_STORE_ID, $access_token->getResourceOwnerId() );

        return $this;
	}

    /**
     * Get Meta Data.
     *
     * @since 3.13.0
     *
     * @param string $meta_key Meta key.
     *
     * @return mixed
     */
    protected function get_meta( string $meta_key ) {
        return get_user_meta( $this->vendor_id, $meta_key, true );
    }

    /**
     * Store Meta values.
     *
     * @since 3.13.0
     *
     * @param string $meta_key Meta key.
     * @param mixed $value Value.
     *
     * @return bool|int
     */
    protected function set_meta( string $meta_key, $value ) {
        return update_user_meta( $this->vendor_id, $meta_key, $value );
    }

    /**
     * Delete Meta values.
     *
     * @since 3.13.0
     *
     * @param string $meta_key Meta key.
     *
     * @return bool
     */
    protected function delete_meta( string $meta_key ): bool {
        return delete_user_meta( $this->vendor_id, $meta_key );
    }

    /**
     * Delete Token from storage.
     *
     * @since 3.13.0
     *
     * @return bool
     */
    public function delete_token(): bool {
        $this->delete_meta( self::KEY_ACCESS_TOKEN, '' );
        $this->delete_meta( self::KEY_REFRESH_TOKEN, '' );
        $this->delete_meta( self::KEY_EXPIRED_AT, '' );
        $this->delete_meta( self::KEY_STORE_ID, '' );

        return true;
    }

    /**
     * Search Token user in storage by printful store id.
     *
     * @since 3.13.0
     *
     * @param int $printful_store_id Printful Store ID.
     *
     * @return int
     */
    public static function search( int $printful_store_id ): int {
        $query = new WP_User_Query(
            [
                'meta_key' => self::KEY_STORE_ID,
                'meta_value' => $printful_store_id,
            ]
        );

        $users = $query->get_results();

        if ( empty( $users ) ) {
            return 0;
        }

        return $users[0]->ID;
    }
}

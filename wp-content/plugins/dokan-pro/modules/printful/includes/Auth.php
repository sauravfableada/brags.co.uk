<?php

namespace WeDevs\DokanPro\Modules\Printful;

use Exception;
use WeDevs\DokanPro\Dependencies\League\OAuth2\Client\Provider\AbstractProvider;
use WeDevs\DokanPro\Dependencies\League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use WeDevs\DokanPro\Modules\Printful\Auth\AbstractTokenStore;
use WeDevs\DokanPro\Modules\Printful\Auth\PrintfulAuthProvider;
use WeDevs\DokanPro\Modules\Printful\Auth\UserMetaTokenStore;
use WeDevs\DokanPro\Modules\Printful\Vendor\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class to handle authentication with Printful.
 *
 * @since 3.13.0
 */
class Auth {

    /**
     * Vendor ID.
     *
     * @since 3.13.0
     *
     * @var int
     */
    protected int $vendor_id = 0;

    /**
     * oAth2 Service Provider.
     *
     * @since 3.13.0
     *
     * @var AbstractProvider
     */
    protected AbstractProvider $provider;

    /**
     * Token Storage.
     *
     * @since 3.13.0
     *
     * @var AbstractTokenStore
     */
    protected AbstractTokenStore $storage;

    /**
     * Vendor Connection Status.
     *
     * @since 3.13.0
     *
     * @var bool $connected Vendor Connection Status.
     */
    protected bool $connected = false;

    /**
     * Auth constructor.
     *
     * @param int $vendor_id Vendor ID.
     */
    public function __construct( int $vendor_id ) {
        $this->vendor_id = $vendor_id;

        $this->provider = new PrintfulAuthProvider(
            [
                'clientId'                => dokan_get_option( 'app_id', 'dokan_printful', '' ),
                'clientSecret'            => dokan_get_option( 'app_secret', 'dokan_printful', '' ),
                'redirectUri'             => dokan_get_navigation_url( 'settings/' . Settings::PAGE_SLUG ),
                'urlAuthorize'            => 'https://www.printful.com/oauth/authorize',
                'urlAccessToken'          => 'https://www.printful.com/oauth/token',
                'urlResourceOwnerDetails' => 'https://api.printful.com/stores',
            ]
        );

        try {
            $this->storage = new UserMetaTokenStore( $this->vendor_id );

            $access_token = $this->storage->get_token();

            if ( empty( $access_token->getToken() ) ) {
                return;
            }

            if ( $access_token->hasExpired() ) {
                $new_access_token = $this->provider->getAccessToken(
                    'refresh_token',
                    [
                        'refresh_token' => $access_token->getRefreshToken(),
                    ]
                );

                $this->storage->set_token( $new_access_token );
            }
        } catch ( Exception $e ) {
            return;
        }

        $this->connected = true;
    }

    /**
     * Search for vendor by Printful Store ID.
     *
     * @since 3.13.0
     *
     * @param int $printful_store_id Printful Store ID.
     *
     * @return Auth
     * @throws Exception If vendor not found.
     */
    public static function search( int $printful_store_id ): Auth {
        $vendor_id = UserMetaTokenStore::search( $printful_store_id );

        if ( ! $vendor_id ) {
            throw new Exception(
                sprintf(
                    // translators: %d is Printful Store ID.
                    esc_html__( 'Connected Vendor with this Printful store ID %d not found.', 'dokan' ),
                    absint( $printful_store_id )
                )
            );
        }

        return new self( $vendor_id );
    }

    /**
     * Auth connector.
     *
     * @since 3.13.0
     *
     * @param string $code Code to grant access token.
     *
     * @return bool
     */
    public function connect( string $code ): bool {
        try {
            // Try to get an access token using the authorization code grant.
            $access_token = $this->provider->getAccessToken( 'authorization_code', [ 'code' => $code ] );
            $this->provider->getResourceOwner( $access_token );

            $this->storage->set_token( $access_token );
            $this->connected = true;
        } catch ( IdentityProviderException $e ) {
            dokan_log( $e->getMessage() );
            return false;
        }

        return true;
    }


    /**
     * Auth disconnector.
     *
     * @since 3.13.0
     *
     * @return bool
     */
    public function disconnect(): bool {
        try {
            $this->storage->delete_token();
            $this->connected = false;
        } catch ( IdentityProviderException $e ) {
            dokan_log( $e->getMessage() );
            return false;
        }

        return true;
    }

    /**
     * Check if vendor is connected with Printful.
     *
     * @since 3.13.0
     *
     * @return bool
     */
    public function is_connected(): bool {
        return $this->connected;
    }

    /**
     * Get access token.
     *
     * @since 3.13.0
     *
     * @return string
     * @throws Exception If vendor id not valid or vendor is not connected or Connection expired.
     */
    public function get_access_token(): string {
        $this->validate_connection();

        $token = $this->storage->get_token();

        if ( $token->hasExpired() ) {
            throw new Exception( esc_html__( 'Vendor is not connected to Printful.', 'dokan' ) );
        }

        return $token->getToken();
    }

    /**
     * Get store information.
     *
     * @since 3.13.0
     *
     * @return array
     * @throws Exception
     */
    public function get_store_info(): array {
        $this->validate_connection();

        return $this->provider->getResourceOwner( $this->storage->get_token() )->toArray();
    }

    /**
     * Get vendor ID.
     *
     * @since 3.13.0
     *
     * @return int
     */
    public function get_vendor_id(): int {
        return $this->vendor_id;
    }



    /**
     * Validate Connection.
     *
     * @since 3.13.0
     *
     * @return void
     * @throws Exception
     */
    protected function validate_connection(): void {
        $vendor = new \WeDevs\Dokan\Vendor\Vendor( $this->vendor_id );

        if ( ! $vendor->is_vendor() ) {
            throw new Exception( esc_html__( 'Vendor ID is not valid.', 'dokan' ) );
        }

        if ( ! $this->is_connected() ) {
            throw new Exception( esc_html__( 'Vendor is not connected to Printful.', 'dokan' ) );
        }
    }
}

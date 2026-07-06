<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\Frontend;

use Hybridauth\Exception\Exception;
use Hybridauth\Hybridauth;
use WeDevs\DokanPro\Storage\Session;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Class
 *
 * @since 3.11.1 Migrated to Class.
 */
class HybridauthHooks {

    /**
     * @var array $config
     */
    private $config;

    /**
     * @var string
     */
    private $base_url;

    /**
     * @var string
     */
    public $e_msg = '';

    /**
     * @var string $callback_url
     */
    public static string $callback_url = 'vendor-verification';

    /**
     * Class Constructor.
     *
     * @since 3.11.1
     */
    public function __construct() {
        add_action( 'init', [ $this, 'init_config' ] );
        add_action( 'template_redirect', [ $this, 'monitor_authenticate_requests' ], 99 );
        add_action( 'woocommerce_api_' . self::$callback_url, [ $this, 'redirect_callback' ] );
    }

    /**
     * Redirect callback handler.
     *
     * @since 4.2.3
     *
     * @return void
     */
    public function redirect_callback() {
        $this->monitor_authenticate_requests();
        wp_safe_redirect( dokan_get_navigation_url( 'settings/verification' ) );
        exit();
    }

    /**
     * @since 3.3.1
     *
     * @return void
     */
    public function init_config() {
        $this->base_url = WC()->api_request_url( self::$callback_url );
        $this->config = $this->get_provider_config();
    }

    /**
     * Monitors Url for Hauth Request and process Hauth for authentication
     *
     * @return void
     */
    public function monitor_authenticate_requests() {
        $vendor_id = dokan_get_current_user_id();

        if ( ! $vendor_id ) {
            return;
        }

        if ( isset( $_GET['dokan_auth_dc'] ) ) { // phpcs:ignore
            $seller_profile = dokan_get_store_info( $vendor_id );
            $provider_dc    = sanitize_text_field( wp_unslash( $_GET['dokan_auth_dc'] ) ); //phpcs:ignore

            $seller_profile['dokan_verification'][ $provider_dc ] = '';

            update_user_meta( $vendor_id, 'dokan_profile_settings', $seller_profile );

            return;
        }

        try {
            /**
             * Feed the config array to Hybridauth
             */
            $hybridauth = new Hybridauth( $this->config );

            /**
             * Initialize session storage.
             *
             * @var Session
             */
            $storage = new Session( 'vendor_verify', 5 * 60 );

            /**
             * Hold information about provider when user clicks on Sign In.
             */
            $provider = ! empty( $_GET['dokan_auth'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_auth'] ) ) : ''; // phpcs:ignore

            if ( $provider ) {
                $storage->set( 'provider', $provider );
            }

            if ( $provider = $storage->get( 'provider' ) ) { //phpcs:ignore
                $adapter = $hybridauth->getAdapter( $provider );
                $adapter->setStorage( $storage );
                $adapter->authenticate();
            }

            if ( ! isset( $adapter ) ) {
                return;
            }

            $user_profile = $adapter->getUserProfile();
            if ( ! $user_profile ) {
                $storage->clear();
                wc_add_notice( __( 'Something went wrong! please try again', 'dokan' ), 'success' );
                wp_safe_redirect( dokan_get_navigation_url( 'settings/verification' ) );
                exit();
            }

            $seller_profile                                    = dokan_get_store_info( $vendor_id );
            $seller_profile['dokan_verification'][ $provider ] = (array) $user_profile;

            update_user_meta( $vendor_id, 'dokan_profile_settings', $seller_profile );
            $storage->clear();
        } catch ( Exception $e ) {
            $this->e_msg = $e->getMessage();
        }
    }

    /**
     * Get Provider Config
     *
     * @since 1.0.0
     * @since 3.11.1 moved this file from module.php to here.
     *
     * @return mixed|null
     */
    protected function get_provider_config() {
        $config = [
            'callback'   => $this->base_url,
            'debug_mode' => false,

            'providers' => [
                'Facebook' => [
                    'enabled' => true,
                    'keys'    => [
                        'id'     => '',
                        'secret' => '',
                    ],
                    'scope'   => 'email, public_profile',
                ],
                'Google'   => [
                    'enabled'         => true,
                    'keys'            => [
                        'id'     => '',
                        'secret' => '',
                    ],
                    // @codingStandardsIgnoreLine
                    'scope'           => 'https://www.googleapis.com/auth/userinfo.profile ' . 'https://www.googleapis.com/auth/userinfo.email', // optional
                    'access_type'     => 'offline',
                    'approval_prompt' => 'force',
                    'hd'              => home_url(),
                ],
                'LinkedIn' => [
                    'enabled' => true,
                    'keys'    => [
                        'id'     => '',
                        'secret' => '',
                    ],
                ],
                'Twitter'  => [
                    'enabled' => true,
                    'keys'    => [
                        'key'    => '',
                        'secret' => '',
                    ],
                ],
            ],
        ];

        //facebook config from admin
        $fb_id                  = dokan_get_option( 'fb_app_id', 'dokan_verification' );
        $fb_secret              = dokan_get_option( 'fb_app_secret', 'dokan_verification' );
        $facebook_enable_status = dokan_get_option( 'facebook_enable_status', 'dokan_verification', 'on' );
        if ( ! empty( $fb_id ) && ! empty( $fb_secret ) && 'on' === $facebook_enable_status ) {
            $config['providers']['Facebook']['keys']['id']     = $fb_id;
            $config['providers']['Facebook']['keys']['secret'] = $fb_secret;
        }
        //google config from admin
        $g_id                 = dokan_get_option( 'google_app_id', 'dokan_verification' );
        $g_secret             = dokan_get_option( 'google_app_secret', 'dokan_verification' );
        $google_enable_status = dokan_get_option( 'google_enable_status', 'dokan_verification', 'on' );
        if ( ! empty( $g_id ) && ! empty( $g_secret ) && 'on' === $google_enable_status ) {
            $config['providers']['Google']['keys']['id']     = $g_id;
            $config['providers']['Google']['keys']['secret'] = $g_secret;
        }
        //LinkedIn config from admin
        $l_id                   = dokan_get_option( 'linkedin_app_id', 'dokan_verification' );
        $l_secret               = dokan_get_option( 'linkedin_app_secret', 'dokan_verification' );
        $linkedin_enable_status = dokan_get_option( 'linkedin_enable_status', 'dokan_verification', 'on' );
        if ( ! empty( $l_id ) && ! empty( $l_secret ) && 'on' === $linkedin_enable_status ) {
            $config['providers']['LinkedIn']['keys']['id']     = $l_id;
            $config['providers']['LinkedIn']['keys']['secret'] = $l_secret;
        }
        //Twitter config from admin
        $twitter_id            = dokan_get_option( 'twitter_app_id', 'dokan_verification' );
        $twitter_secret        = dokan_get_option( 'twitter_app_secret', 'dokan_verification' );
        $twitter_enable_status = dokan_get_option( 'twitter_enable_status', 'dokan_verification', 'on' );
        if ( ! empty( $twitter_id ) && ! empty( $twitter_secret ) && 'on' === $twitter_enable_status ) {
            $config['providers']['Twitter']['keys']['key']    = $twitter_id;
            $config['providers']['Twitter']['keys']['secret'] = $twitter_secret;
        }

        /**
         * Filter the Config array of Hybridauth
         *
         * @since 1.0.0
         *
         * @param array $config
         */
        $config = apply_filters( 'dokan_verify_providers_config', $config );

        return $config;
    }
}

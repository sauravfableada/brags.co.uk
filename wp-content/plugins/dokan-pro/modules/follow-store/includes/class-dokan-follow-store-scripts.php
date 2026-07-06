<?php

class Dokan_Follow_Store_Scripts {

    /**
     * Class constructor
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct() {
        add_action( 'init', [ $this, 'register_scripts' ] );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Register scripts
     *
     * @since 3.7.4
     */
    public function register_scripts() {
        list( $suffix, $script_version ) = dokan_get_script_suffix_and_version();

        wp_register_style( 'dokan-follow-store', DOKAN_FOLLOW_STORE_ASSETS . '/js/follow-store' . $suffix . '.css', array( 'dokan-style' ), DOKAN_FOLLOW_STORE_VERSION );
        wp_register_script( 'dokan-follow-store', DOKAN_FOLLOW_STORE_ASSETS . '/js/follow-store' . $suffix . '.js', array( 'jquery', 'dokan-login-form-popup' ), DOKAN_FOLLOW_STORE_VERSION, true );

        if ( 'off' === dokan_get_option( 'disable_dokan_fontawesome', 'dokan_appearance', 'off' ) ) {
            wp_enqueue_style( 'dokan-fontawesome' );
        }

        $dokan_follow_store = array(
            '_nonce'        => wp_create_nonce( 'dokan_follow_store' ),
            'button_labels' => dokan_follow_store_button_labels(),
        );

        wp_localize_script( 'dokan-follow-store', 'dokanFollowStore', $dokan_follow_store );

        // Register vendor dashboard React scripts.
        $script_assets = DOKAN_FOLLOW_STORE_PATH . '/assets/js/vendor-follow-store.asset.php';

        if ( file_exists( $script_assets ) ) {
            $assets = include $script_assets;

            wp_register_script(
                'dokan-frontend-follow-store',
                DOKAN_FOLLOW_STORE_ASSETS . '/js/vendor-follow-store.js',
                array_merge( $assets['dependencies'], [ 'dokan-react-frontend' ] ),
                $assets['version'],
                true
            );
        }
    }

    /**
     * Enqueue module scripts
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function enqueue_scripts() {
        if ( dokan_is_store_listing() || dokan_is_store_page() || ( is_account_page() && false !== get_query_var( 'following', false ) ) ) {
            wp_enqueue_style( 'dokan-follow-store' );
            wp_enqueue_script( 'dokan-follow-store' );
        }

        if ( dokan_is_seller_dashboard() ) {
            wp_enqueue_script( 'dokan-frontend-follow-store' );
            wp_set_script_translations( 'dokan-frontend-follow-store', 'dokan' );
        }
    }
}

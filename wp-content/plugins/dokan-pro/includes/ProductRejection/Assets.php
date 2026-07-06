<?php

namespace WeDevs\DokanPro\ProductRejection;

/**
 * Manages JavaScript and CSS assets for the product rejection feature.
 *
 * @since 3.16.0
 */
class Assets {

    /**
     * The script version.
     *
     * @var string
     */
    private $script_version;

    /**
     * The script suffix.
     *
     * @var string
     */
    private $suffix;

    /**
     * Initialize the assets manager.
     *
     * @since 3.16.0
     */
    public function __construct() {
        list( $this->suffix, $this->script_version ) = dokan_get_script_suffix_and_version();

        $this->register_hooks();
    }

    protected function register_hooks(): void {
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ], 5 );
            add_action( 'dokan_enqueue_admin_scripts', [ $this, 'load_admin_assets' ] );
        } else {
            add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ], 5 );
            add_action( 'dokan_enqueue_scripts', [ $this, 'load_vendor_assets' ] );
        }
    }

    /**
     * Register all assets with WordPress.
     *
     * @since 3.16.0
     *
     * @return void
     */
    public function register_assets(): void {
        // Register admin script
        wp_register_script(
            'dokan-product-rejection-admin',
            DOKAN_PRO_PLUGIN_ASSEST . '/js/product-rejection-admin' . $this->suffix . '.js',
            [ 'jquery', 'wp-api-fetch', 'wp-i18n', 'dokan-sweetalert2' ],
            $this->script_version,
            true
        );

        // Register admin style
        wp_register_style(
            'dokan-product-rejection-admin',
            DOKAN_PRO_PLUGIN_ASSEST . '/js/product-rejection-admin' . $this->suffix . '.css',
            [],
            $this->script_version
        );

        // Register vendor style
        wp_register_style(
            'dokan-product-rejection-vendor',
            DOKAN_PRO_PLUGIN_ASSEST . '/js/product-rejection-vendor' . $this->suffix . '.css',
            [],
            $this->script_version
        );
    }

    /**
     * Load admin-specific assets.
     *
     * @since 3.16.0
     *
     * @param string $hook_suffix The current admin page hook suffix
     *
     * @return void
     */
    public function load_admin_assets( string $hook_suffix ): void {
        if ( ! $this->is_admin_assets_required( $hook_suffix ) ) {
            return;
        }

        wp_enqueue_style( 'dokan-product-rejection-admin' );
        wp_enqueue_script( 'dokan-product-rejection-admin' );
    }

    /**
     * Load vendor-specific assets.
     *
     * @since 3.16.0
     *
     * @return void
     */
    public function load_vendor_assets(): void {
        if ( ! $this->is_vendor_assets_required() ) {
            return;
        }

        wp_enqueue_style( 'dokan-product-rejection-vendor' );
    }

    /**
     * Check if admin assets should be loaded.
     *
     * @since 3.16.0
     *
     * @param string $hook_suffix Current admin page hook suffix
     *
     * @return bool True if assets should be loaded
     */
    private function is_admin_assets_required( string $hook_suffix ): bool {
        if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php', 'edit.php' ], true ) ) {
            return false;
        }

        global $post, $post_type;

        if ( 'edit.php' === $hook_suffix ) {
            return 'product' === $post_type;
        }

        return isset( $post ) && 'product' === $post->post_type;
    }

    /**
     * Determine if vendor assets should be loaded for product edit page.
     *
     * @since 3.16.0
     *
     * @return bool
     */
    private function is_vendor_assets_required(): bool {
        // Only load on seller dashboard products page
        if ( ! dokan_is_seller_dashboard() ) {
            return false;
        }

        // Verify product access
        $product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

        return $product_id && dokan_is_product_author( $product_id );
    }
}

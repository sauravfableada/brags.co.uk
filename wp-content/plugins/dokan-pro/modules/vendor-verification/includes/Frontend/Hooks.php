<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\Frontend;

use WeDevs\Dokan\Vendor\Vendor;
use WeDevs\DokanPro\Modules\VendorVerification\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Class
 *
 * @since 3.11.1 Migrated to Class.
 */
class Hooks {

    /**
     * Class Constructor.
     *
     * @since 3.11.1
     */
    public function __construct() {
        // display vendor verification badge
        add_action( 'dokan_store_header_after_store_name', [ $this, 'add_vendor_verified_icon' ] );
        add_action( 'dokan_store_list_loop_after_store_name', [ $this, 'add_vendor_verified_icon' ] );
        add_action( 'dokan_product_single_after_store_name', [ $this, 'add_vendor_verified_icon' ] );

        // Custom dir for vendor uploaded file
        add_filter( 'upload_dir', [ $this, 'dokan_customize_upload_dir' ], 10 );
        add_filter( 'dokan_rest_store_additional_fields', [ $this, 'add_vendor_is_verified_data' ], 10, 2 );
    }

    /**
     * Render vendor verified icon after store name
     *
     * @since 3.5.2
     * @since 3.11.1 Moved to Vendor Class.
     *
     * @return void
     */
    public function add_vendor_verified_icon( $vendor ) {
        // check seller id, address or business has not verified
        if ( ! Helper::is_seller_verified( $vendor->get_id() ) ) {
            return;
        }
        $all_icons = Helper::get_verified_icons();
        $icon_id   = dokan_get_option( 'verified_icon', 'dokan_verification', 'check_circle_solid' );
        $icon      = $all_icons[ $icon_id ];
        ?>
        <div class="tips" title="<?php esc_html_e( 'Verified', 'dokan' ); ?>" style="color: #598df8; font-size: 18px; display:inline-block;">
            <style>
                .tips i {
                    margin: 0 0 0 5px !important;
                    text-shadow: 1px 1px 3px #07070766;
                }
            </style>
            <?php echo wp_kses_post( $icon ); ?>
        </div>
        <?php
    }

    /*
     * Custom dir for vendor uploaded file
     *
     * @since 2.9.0
     *
     * @return array
     *
     */
    public function dokan_customize_upload_dir( $upload ) {
        if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
            return $upload;
        }

        // @codingStandardsIgnoreLine
        if ( strpos( $_SERVER['HTTP_REFERER'], 'settings/verification' ) != false ) {

            remove_filter( 'upload_dir', [ $this, 'dokan_customize_upload_dir' ], 10 );
            // apply a security patch
            $this->disallow_direct_access();
            add_filter( 'upload_dir', [ $this, 'dokan_customize_upload_dir' ], 10 );

            $user_id = get_current_user_id();
            $user    = get_user_by( 'id', $user_id );
            if ( ! $user ) {
                return $upload;
            }

            $vendor_verification_hash = get_user_meta( $user_id, 'dokan_vendor_verification_folder_hash', true );

            if ( empty( $vendor_verification_hash ) ) {
                $vendor_verification_hash = dokan_get_random_string( 20 );
                update_user_meta( $user_id, 'dokan_vendor_verification_folder_hash', $vendor_verification_hash );
            }

            $dirname          = $user_id . '-' . $user->user_login . '/' . $vendor_verification_hash;
            $upload['subdir'] = '/verification/' . $dirname;
            $upload['path']   = $upload['basedir'] . $upload['subdir'];
            $upload['url']    = $upload['baseurl'] . $upload['subdir'];
        }

        return $upload;
    }

    /**
     * Creates .htaccess & index.html files if not exists that prevent direct folder access
     *
     * @since 3.1.3
     */
    protected function disallow_direct_access() {
        $uploads_dir   = trailingslashit( wp_upload_dir()['basedir'] ) . 'verification';
        $file_htaccess = $uploads_dir . '/.htaccess';
        $file_html     = $uploads_dir . '/index.html';
        $rule          = <<<EOD
Options -Indexes
deny from all
<FilesMatch '\.(jpg|jpeg|png|gif|pdf|doc|docx|odt)$'>
    Order Allow,Deny
    Allow from all
</FilesMatch>
EOD;
        if ( get_transient( 'dokan_vendor_verification_access_check' ) ) {
            return;
        }

        if ( ! is_dir( $uploads_dir ) ) {
            wp_mkdir_p( $uploads_dir );
        }

        global $wp_filesystem;

        // protect if the the global filesystem isn't setup yet
        if ( is_null( $wp_filesystem ) ) { // phpcs:ignore
            require_once( ABSPATH . '/wp-admin/includes/file.php' );// phpcs:ignore
            WP_Filesystem();
        }

        // phpcs:ignore
        if ( ( file_exists( $file_htaccess ) && $wp_filesystem->get_contents( $file_htaccess ) !== $rule ) || ! file_exists( $file_htaccess ) ) {

            $ret = $wp_filesystem->put_contents(
                $file_htaccess,
                '',
                FS_CHMOD_FILE
            ); // returns a status of success or failure

            $wp_filesystem->put_contents(
                $file_htaccess,
                $rule,
                FS_CHMOD_FILE
            ); // returns a status of success or failure

            $wp_filesystem->put_contents(
                $file_html,
                '',
                FS_CHMOD_FILE
            ); // returns a status of success or failure

            if ( $ret ) {
                // Sets transient for 7 days
                set_transient( 'dokan_vendor_verification_access_check', true, DAY_IN_SECONDS * 7 );
            }
        }
    }

    /**
     * Add seller is verified data.
     *
     * @since 4.1.3
     *
     * @param array  $additional_fields
     * @param Vendor $store
     *
     * @return array
     */
    public function add_vendor_is_verified_data( $additional_fields, $store ) {
        $additional_fields['is_verified'] = Helper::is_seller_verified( $store->get_id() );

        return $additional_fields;
    }
}

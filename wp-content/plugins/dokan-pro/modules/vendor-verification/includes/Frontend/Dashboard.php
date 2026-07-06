<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\Frontend;

use WeDevs\Dokan\Utilities\AdminSettings;
use WeDevs\DokanPro\Modules\VendorVerification\Helper;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Class
 *
 * @since 3.11.1 Migrated to Class.
 */
class Dashboard {

    /**
     * Class Constructor.
     *
     * @since 3.11.1
     */
    public function __construct() {
        add_filter( 'dokan_get_all_cap', [ $this, 'add_capabilities' ], 10 );
        add_filter( 'dokan_get_dashboard_settings_nav', [ $this, 'register_dashboard_menu' ] );
        add_filter( 'dokan_query_var_filter', [ $this, 'register_template_endpoint' ] );
        add_filter( 'dokan_dashboard_settings_heading_title', [ $this, 'load_header_title' ], 15, 2 );
        // Overriding templating system for vendor-verification
        add_filter( 'dokan_set_template_path', [ $this, 'load_verification_templates' ], 30, 3 );
        add_action( 'dokan_render_settings_content', [ $this, 'load_content_template' ] );
        add_action( 'dokan_pro_vendor_verification_request_updated', [ $this, 'on_verification_request_updated' ] );
        add_action( 'dokan_pro_vendor_verification_after_vendor_verified', [ $this, 'enable_selling_status_if_verification_is_approved' ] );
        add_filter( 'dokan_can_enable_selling', [ $this, 'prevent_selling_if_not_verified' ], 10, 2 );
        add_action( 'dokan_vendor_address_verification_template', [ $this, 'display_warning_message_on_address_save' ] );
        add_action( 'update_user_meta', [ $this, 'detect_vendor_default_address_change' ], 10, 4 );
    }

    /**
     * Add capabilities
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function add_capabilities( $capabilities ) {
        $capabilities['menu']['dokan_view_store_verification_menu'] = __( 'View verification settings menu', 'dokan' );

        return $capabilities;
    }

    /**
     * Adds a Verification menu on Dokan Seller Dashboard
     *
     * @since 1.0.0
     * @since 3.11.1 Moved to separate class.
     *
     * @param array $menus Menus.
     *
     * @return array
     */
    public function register_dashboard_menu( array $menus ): array {
        $menus['verification'] = [
            'title'         => __( 'Verification', 'dokan' ),
            'icon'          => '<i class="fas fa-check"></i>',
            'url'           => dokan_get_navigation_url( 'settings/verification' ),
            'pos'           => 55,
            'permission'    => 'dokan_view_store_verification_menu',
            'react_route'   => 'settings/verification',
        ];

        return $menus;
    }

    /**
     * Register Query vars.
     *
     * @since 1.0.0
     * @since 3.11.1 Moved to separate class.
     *
     * @param array $query_var Query vars.
     *
     * @return array
     */
    public function register_template_endpoint( array $query_var ): array {
        $query_var[] = 'verification';

        return $query_var;
    }

    /**
     * Set Template heading.
     *
     *
     * @since 1.0.0
     * @since 3.11.1 Moved to separate class.
     *
     * @param string $heading    Heading
     * @param string $query_vars Query vars.
     *
     * @return string
     */
    public function load_header_title( $heading, $query_vars ): string {
        // @phpstan-ignore-next-line
        if ( isset( $query_vars ) && (string) $query_vars === 'verification' ) {
            $heading = __( 'Verification', 'dokan' );
        }

        return $heading;
    }

    /**
     * Load templates. so that it can override from theme
     *
     * @since 1.0.0
     *
     * @return string
     **/
    public function load_verification_templates( $template_path, $template, $args ) {
        if ( isset( $args['is_vendor_verification'] ) && $args['is_vendor_verification'] ) {
            return dokan_pro()->plugin_path() . '/modules/vendor-verification/templates';
        }

        return $template_path;
    }

    /**
     * Load Template content.
     *
     * @since 1.0.0
     * @since 3.11.1 Moved to separate class.
     *
     * @param array $query_vars Query vars.
     *
     * @return void
     */
    public function load_content_template( array $query_vars ) {
        if ( ! isset( $query_vars['settings'] ) || (string) $query_vars['settings'] !== 'verification' ) {
            return;
        }

        if ( ! current_user_can( 'dokan_view_store_verification_menu' ) ) {
            dokan_get_template_part(
                'global/dokan-error', '', [
                    'deleted' => false,
                    'message' => __( 'You have no permission to view this verification page', 'dokan' ),
                ]
            );

            return;
        }

        dokan_get_template_part(
            'vendor-verification/verification', '', [
                'is_vendor_verification' => true,
            ]
        );
    }

    /**
     * On verification request update, set vendor as verified.
     *
     * @param int $request_id Verification Request ID.
     *
     * @return void
     */
    public function on_verification_request_updated( int $request_id ) {
        $verification_request = new VerificationRequest( $request_id );

        if ( VerificationRequest::STATUS_APPROVED !== $verification_request->get_status() ) {
            return;
        }

        // todo: this can be done by a single query
        $required_methods = array_map(
            function ( $method ) {
                return $method->get_id();
            },
            ( new VerificationMethod() )->query(
                [
                    'status'   => VerificationMethod::STATUS_ENABLED,
                    'required' => true,
                ]
            )
        );

        $approved_methods = array_unique(
            ( new VerificationRequest() )
                ->query_field(
                    [
                        'status'    => VerificationRequest::STATUS_APPROVED,
                        'vendor_id' => $verification_request->get_vendor_id(),
                        'field'     => 'method_id',
                        'order'     => 'ASC',
                    ]
                )
        );

        if ( array_diff( $required_methods, $approved_methods ) ) {
            return;
        }

        update_user_meta( $verification_request->get_vendor_id(), 'dokan_verification_status', 'approved' );

        do_action( 'dokan_pro_vendor_verification_after_vendor_verified', $verification_request->get_vendor_id() );
        do_action( 'dokan_verification_status_change', $verification_request->get_vendor_id(), dokan_get_store_info( $verification_request->get_vendor_id() ), [] );
    }

    /**
     * Display a warning message on address save.
     *
     * @since 3.11.1
     *
     * @param array|string $address Address data.
     *
     * @return void
     */
    public function display_warning_message_on_address_save( $address ) {
        if ( ! is_array( $address ) || ! dokan_is_seller_dashboard() ) {
            return;
        }

        $verification_methods        = ( new VerificationMethod() )->query( [ 'status' => VerificationMethod::STATUS_ENABLED ] );
        $verification_method_address = array_filter(
            $verification_methods,
            function ( $method ) {
                return $method->get_kind() === VerificationMethod::TYPE_ADDRESS;
            }
        );
        $verification_method_address = reset( $verification_method_address );

        if ( ! $verification_method_address ) {
            return;
        }

        $last_verification_request_for_address = ( new VerificationRequest() )->query(
            [
                'vendor_id' => dokan_get_current_user_id(),
                'order_by'  => 'id',
                'order'     => 'DESC',
                'method_id' => $verification_method_address->get_id(),
                'per_page'  => 1,
            ]
        );

        $last_verification_request_for_address = reset( $last_verification_request_for_address );

        if (
            ! $last_verification_request_for_address
            || $last_verification_request_for_address->get_status() === VerificationRequest::STATUS_CANCELLED
            || $last_verification_request_for_address->get_status() === VerificationRequest::STATUS_REJECTED
        ) {
            return;
        }

        echo '<div class="dokan-alert dokan-alert-warning">';
        echo esc_html__( 'If it is default address and you change the address, the verification request for the address will be cancelled. Please make sure that you want to change the address.', 'dokan' );
        echo '</div>';
    }

    /**
     * Detect vendor default address change.
     *
     * @sience 3.11.1
     *
     * @param int    $meta_id     Meta ID.
     * @param int    $object_id   Object ID.
     * @param string $meta_key    Meta key.
     * @param mixed  $_meta_value Meta value.
     *
     * @return void
     */
    public function detect_vendor_default_address_change( int $meta_id, int $object_id, string $meta_key, $_meta_value ) {
        if ( 'dokan_profile_settings' !== $meta_key ) {
            return;
        }

        $current_meta_value = get_user_meta( $object_id, $meta_key, true );
        $current_address    = ! ( empty( $current_meta_value['address'] ) || ! is_array( $current_meta_value['address'] ) ) ? $current_meta_value['address'] : [];
        $changed_address    = ! ( empty( $_meta_value['address'] ) || ! is_array( $_meta_value['address'] ) ) ? $_meta_value['address'] : [];
        $address_changed    = ! empty( array_diff_assoc( $current_address, $changed_address ) );

        if ( ! $address_changed ) {
            return;
        }

        $verification_method_address = ( new VerificationMethod() )->query(
            [
				'status' => VerificationMethod::STATUS_ENABLED,
				'kind' => VerificationMethod::TYPE_ADDRESS,
			]
        );
        $verification_method_address = reset( $verification_method_address );

        if ( ! $verification_method_address ) {
            return;
        }

        $last_verification_request_for_address = ( new VerificationRequest() )->query(
            [
                'vendor_id' => $object_id,
                'order_by'  => 'id',
                'order'     => 'DESC',
                'method_id' => $verification_method_address->get_id(),
                'per_page'  => 1,
            ]
        );

        $last_verification_request_for_address = reset( $last_verification_request_for_address );

        if (
            ! $last_verification_request_for_address
            || $last_verification_request_for_address->get_status() === VerificationRequest::STATUS_CANCELLED
            || $last_verification_request_for_address->get_status() === VerificationRequest::STATUS_REJECTED
        ) {
            return;
        }

        if ( $last_verification_request_for_address->get_status() === VerificationRequest::STATUS_APPROVED && $verification_method_address->is_required() ) {
            update_user_meta( $object_id, 'dokan_verification_status', 'pending' );
        }

        try {
            $last_verification_request_for_address
                ->set_status( VerificationRequest::STATUS_CANCELLED )
                ->update();
        } catch ( \Exception $e ) {
            dokan_log( $e->getMessage(), 'error' );
        }
    }

    /**
     * set enabled status as yes when the vendor is verified.
     *
     * @since 4.0.3
     *
     * @param int $vendor_id
     *
     * @return void
     */
    public function enable_selling_status_if_verification_is_approved( $vendor_id ) {
        $enable_selling_status = dokan_get_container()->get( AdminSettings::class )->get_new_seller_enable_selling_status();

        if ( ! Helper::is_seller_verified( $vendor_id ) || 'verified_only' !== $enable_selling_status ) {
            return;
        }

        if ( ! dokan_is_seller_enabled( $vendor_id ) ) {
            dokan()->vendor->activate( $vendor_id );
        }
    }

    /**
     * Prevent enabling selling if "Verified Only" is set and vendor is not verified.
     *
     * @since 5.0.1
     *
     * @param bool $enable_selling Whether to enable selling.
     * @param int  $vendor_id      The vendor ID.
     *
     * @return bool
     */
    public function prevent_selling_if_not_verified( $enable_selling, $vendor_id ) {
        if ( current_user_can( 'manage_woocommerce' ) ) {
            return $enable_selling;
        }

        $status = dokan_get_container()->get( AdminSettings::class )->get_new_seller_enable_selling_status();

        if ( 'verified_only' === $status && ! Helper::is_seller_verified( $vendor_id ) ) {
            return false;
        }

        return $enable_selling;
    }
}

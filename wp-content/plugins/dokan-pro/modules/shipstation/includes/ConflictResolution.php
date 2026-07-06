<?php

namespace WeDevs\DokanPro\Modules\ShipStation;

/**
 * Conflict Resolution Class.
 *
 * @since 3.7.15
 */
class ConflictResolution {

    /**
     * Whether the conflict found or not.
     *
     * @var bool
     */
    protected $found_conflict = false;

    /**
     * Class constructor.
     *
     * @since 3.7.15
     */
    public function __construct() {
        if ( is_plugin_active( 'woocommerce-shipstation-integration/woocommerce-shipstation.php' ) ) {
            $this->found_conflict = true;
        }

        add_filter( 'dokan_admin_notices', [ $this, 'wc_shipstation_dectivation_notice' ], 20, 1 );
        add_action( 'wp_ajax_dokan_wc_shipstation_deactivation', [ $this, 'deactivate_wc_shipstation_plugin' ], 10 );
    }

    /**
     * WooCommerce ShipStation Integration plugin deactivation notice.
     *
     * @since 3.7.15
     *
     * @param array $notices  Admin notices
     *
     * @return array $notices Admin notices
     * */
    public function wc_shipstation_dectivation_notice( $notices ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return $notices;
        }

        if ( ! $this->found_conflict ) {
            return $notices;
        }

        $notices[] = [
            'type'        => 'alert',
            'title'       => __( 'WooCommerce ShipStation Integration plugin needs to be deactivated!', 'dokan' ),
            /* translators: %1$s: WooCommerce ShipStation Integration plugin name, %2$s: Dokan ShipStation module name */
            'description' => sprintf(
                __( '%1$s plugin needs to be deactivated in order to use %2$s module!', 'dokan' ),
                '<strong>WooCommerce ShipStation Integration</strong>',
                '<strong>Dokan ShipStation</strong>'
            ),
            'priority'    => 2,
            'actions'     => [
                [
                    'type'           => 'primary',
                    'text'           => __( 'Deactivate plugin', 'dokan' ),
                    'loading_text'   => __( 'Deactivating...', 'dokan' ),
                    'completed_text' => __( 'Deactivated', 'dokan' ),
                    'reload'         => true,
                    'ajax_data'      => [
                        'action'   => 'dokan_wc_shipstation_deactivation',
                        '_wpnonce' => wp_create_nonce( 'dokan-wc-shipstation-deactivation' ),
                    ],
                ],
            ],
        ];

        return $notices;
    }

    /**
     * Deactivate Woocommerce ShipStation plugin.
     *
     * @since Dokan_PRO_SINCE
     *
     * @return void
     * */
    public function deactivate_wc_shipstation_plugin() {
        if (
            ! isset( $_REQUEST['_wpnonce'] ) ||
            ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'dokan-wc-shipstation-deactivation' ) // phpcs:ignore
        ) {
            wp_send_json_error( __( 'Nonce verification failed', 'dokan' ) );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'You have no permission to do that', 'dokan' ) );
        }

        // Deactivate the WooCommerce ShipStation Integration plugin.
        deactivate_plugins( 'woocommerce-shipstation-integration/woocommerce-shipstation.php' );

        wp_send_json_success();
    }
}

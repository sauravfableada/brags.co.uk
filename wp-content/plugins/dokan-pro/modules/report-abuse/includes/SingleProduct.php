<?php

namespace WeDevs\DokanPro\Modules\ReportAbuse;

class SingleProduct {

    /**
     * Class constructor
     *
     * @since 2.9.8
     *
     * @return void
     */
    public function __construct() {
        add_action( 'woocommerce_single_product_summary', [ self::class, 'add_report_button' ], 100 );
        add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_scripts' ] );
        add_action( 'init', [ self::class, 'register_scripts' ] );
    }

    /**
     * Add report button
     *
     * @since 2.9.8
     *
     * @return void
     */
    public static function add_report_button() {
        $label = apply_filters( 'dokan_report_abuse_button_label', esc_html__( 'Report Abuse', 'dokan' ) );

        $args = [
            'label' => $label,
        ];

        dokan_report_abuse_template( 'report-button', $args );
    }

    /**
     * Register scripts
     *
     * @since 3.7.4
     */
    public static function register_scripts() {
        list( $suffix, $version ) = dokan_get_script_suffix_and_version();

        wp_register_script( 'dokan-report-abuse', DOKAN_REPORT_ABUSE_ASSETS . '/js/dokan-report-abuse' . $suffix . '.js', [ 'jquery', 'dokan-login-form-popup' ], $version, true );
    }

    /**
     * Enqueue scripts
     *
     * @since 2.9.8
     *
     * @return void
     */
    public static function enqueue_scripts() {
        if ( is_product() ) {
            $product = wc_get_product();

            wp_enqueue_script( 'dokan-report-abuse' );

            // Get abuse reasons form `dokan_report_abuse`.
            $options                  = (array) get_option( 'dokan_report_abuse', [] );
            $options['abuse_reasons'] = $options['abuse_reasons'] ?? [];

            foreach ( $options['abuse_reasons'] as $key => $status ) {
                $options['abuse_reasons'][$key]['value'] = apply_filters('dokan_pro_abuse_report_reason', $status['value'] );
            }

            wp_localize_script(
                'dokan-report-abuse',
                'dokanReportAbuse',
                array_merge(
                    $options, [
                        'is_user_logged_in' => is_user_logged_in(),
                        'nonce'             => wp_create_nonce( 'dokan_report_abuse' ),
                        'product_id'        => $product->get_id(),
                    ]
                )
            );
        }
    }
}

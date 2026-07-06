<?php

namespace WeDevs\DokanPro\VendorDiscount\Admin;

class Discount {

    /**
     * Class constructor.
     *
     * @since 3.10.0
     */
    public function __construct() {
        add_action( 'dokan_daily_midnight_cron', [ $this, 'check_and_disable_dokan_pro_functionality' ] );
        add_filter( 'dokan_admin_localize_script', [ $this, 'add_is_valid' ] );
        add_filter( 'dokan_settings_fields', [ $this, 'add_dokan_lite_key_to_all_dokan_lite_settings_field' ], 1 );
        add_filter( 'dokan_admin_notices', [ $this, 'show_notice' ] );
    }

    /**
     * Add license valid/invalid key in localize data.
     *
     * @since 3.10.0
     *
     * @param $data
     *
     * @return mixed
     */
    public function add_is_valid( $data ) {
        $data['isValidLicense'] = dokan_pro()->license->is_valid();

        return $data;
    }

    /**
     * Add `is_lite` key to all dokan lit admin settings fields.
     *
     * @since 4.0.0
     *
     * @param array $settings_data
     *
     * @return mixed
     */
    public function add_dokan_lite_key_to_all_dokan_lite_settings_field( $settings_data ) {
        foreach ( $settings_data as $section => &$fields ) {
            foreach ( $fields as $key => &$field ) {
                if ( ! isset( $field['is_lite'] ) ) {
                    $field['is_lite'] = true;
                }
            }
        }

        return $settings_data;
    }

    /**
     * Show license expire warning notice when 7 days remaining and show expired alert notice when license expired.
     *
     * @since 3.10.0
     *
     * @param $notices
     *
     * @return mixed
     */
    public function show_notice( $notices ) {
        // If $expiry_days is false, then it is a lifetime license.
        $expiry_days = dokan_pro()->license->get_expiry_days();

        if (
            dokan_pro()->license->has_license_key()
            && dokan_pro()->license->is_valid()
            && $expiry_days <= 7
            && $expiry_days >= 0
            && false !== $expiry_days
        ) {
            $notices[] = [
                'type'        => 'warning',
                'title'       => __( 'Heads up! Dokan love needs a refresh!', 'dokan' ),
                'description' => __( 'Your license is expiring soon. Renew to secure your PRO features & avoid interruptions. Keep selling seamlessly.', 'dokan' ),
                'priority'    => 1,
                'actions'     => [
                    [
                        'type'   => 'primary',
                        'text'   => __( 'Renew Plan', 'dokan' ),
                        'action' => admin_url( 'admin.php?page=dokan-dashboard#/license' ),
                    ],
                ],
            ];
        } elseif (
            dokan_pro()->license->has_license_key()
            && ! dokan_pro()->license->is_valid()
            && $expiry_days <= 0
        ) {
            $notices[] = [
                'type'        => 'alert',
                'title'       => __( 'License expired! It\'s high time to refresh.', 'dokan' ),
                'description' => __( 'Your data is safe, renew anytime to unlock PRO features. After renewal, your marketplace will seamlessly resume.', 'dokan' ),
                'priority'    => 1,
                'actions'     => [
                    [
                        'type'   => 'primary',
                        'text'   => __( 'Renew Now!', 'dokan' ),
                        'action' => admin_url( 'admin.php?page=dokan-dashboard#/license' ),
                    ],
                ],
            ];
        }

        return $notices;
    }

    /**
     * Refresh dokan pro-license and if invalid license then deactivate all dokan pro modules.
     *
     * @since 3.10.0
     *
     * @return void
     */
    public function check_and_disable_dokan_pro_functionality() {
        dokan_pro()->license->refresh_license();

        if ( ! dokan_pro()->license->is_valid() ) {
            dokan_pro()->module->deactivate_modules( dokan_pro()->module->get_available_modules() );
        }
    }
}

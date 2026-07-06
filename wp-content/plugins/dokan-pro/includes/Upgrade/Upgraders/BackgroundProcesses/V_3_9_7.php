<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders\BackgroundProcesses;

use WeDevs\Dokan\Abstracts\DokanBackgroundProcesses;
use WeDevs\DokanPro\Modules\StripeExpress\Support\UserMeta;

/**
 * Dokan V_3_9_7 Upgrade Background Processor Class.
 *
 * @since DOAKN_PRO_SINCE
 */
class V_3_9_7 extends DokanBackgroundProcesses {

    /**
     * Action
     *
     * Override this action in processor class
     *
     * @since 3.9.7
     *
     * @var string
     */
    protected $action = 'dokan_pro_bg_action_3_9_7';

    /**
     * Perform Updates.
     *
     * @since 3.9.7
     *
     * @param mixed $item
     *
     * @return mixed
     */
    public function task( $item ) {
        if ( empty( $item ) || ! isset( $item['task'] ) ) {
            return false;
        }

        switch ( $item['task'] ) {
            case 'update_stripe_express_account_info':
                return $this->update_stripe_express_account_info( $item );
            default:
                return false;
        }
    }

    /**
     * Update Stripe Express Account Info.
     *
     * @since 3.9.7
     *
     * @param array $item
     *
     * @return bool
     */
    public function update_stripe_express_account_info( $item ) {
        if ( ! dokan_pro()->module->is_active( 'stripe_express' ) ) {
            return false;
        }

        if ( empty( $item['vendor_ids'] ) ) {
            return false;
        }

        foreach ( $item['vendor_ids'] as $vendor_id ) {
            UserMeta::get_stripe_account_id( $vendor_id ); // this method will get the stripe account id and update the user meta
        }

        return false;
    }
}

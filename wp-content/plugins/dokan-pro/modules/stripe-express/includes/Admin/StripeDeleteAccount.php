<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\Admin;

use WeDevs\DokanPro\Modules\StripeExpress\Support\Api;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Helper;
use \Exception;

/**
 * Deletes stripe account
 *
 * @since 3.9.4
 */
class StripeDeleteAccount extends Api {

    /**
     * Initializes and calls all hooks
     *
     * @since 3.9.4
     */
    public function __construct() {
        add_action(
            'dokan_stripe_express_is_vendor_stripe_account_deleted_from_remote',
            [ $this, 'delete_stripe_account' ],
            10,
            1
        );
    }

    /**
     * Checks is the account has been deleted from stripe server
     *
     * @since 3.9.4
     *
     * @param string $account_id
     *
     * @return void
     */
    public function delete_stripe_account( $account_id ) {
        // only admin can delete stripe account
        if ( ! isset( $_REQUEST['dokan_stripe_express_admin_delete'] ) || empty( $account_id ) ) {
            return;
        }

        $stripe_object = self::api();
        try {
            $response = $stripe_object->accounts->delete( $account_id, [] );
            if ( empty( $response['deleted'] ) ) {
                Helper::log( sprintf( 'Could not delete account: %1$s. Error: %2$s', $account_id, "Unrecognized Response" ) );
            }
        } catch ( Exception $e ) {
            Helper::log( sprintf( 'Could not delete account: %1$s. Error: %2$s', $account_id, $e->getMessage() ) );
        }
    }
}

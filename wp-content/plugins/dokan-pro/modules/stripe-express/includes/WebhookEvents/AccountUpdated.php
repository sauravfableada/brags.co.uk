<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\WebhookEvents;

defined( 'ABSPATH' ) || exit; // Exit if called directly

use WeDevs\DokanPro\Modules\StripeExpress\Support\UserMeta;
use WeDevs\DokanPro\Modules\StripeExpress\Api\Account;
use WeDevs\DokanPro\Modules\StripeExpress\Utilities\Abstracts\WebhookEvent;

/**
 * Class to handle `account.updated` webhook.
 *
 * @since 3.9.7
 *
 * @package WeDevs\DokanPro\Modules\StripeExpress\WebhookEvents
 */
class AccountUpdated extends WebhookEvent {

    /**
     * Handles the event.
     *
     * @since 3.9.7
     *
     * @return void
     */
    public function handle() {
        $data = $this->get_payload();
        try {
            // get an account id
            $account_id = $data->id;
            // get account info
            $account = Account::get( $account_id );
            $metadata = $account->metadata;
            $user_id = $metadata->user_id ?? 0;
            if ( ! $user_id ) {
                // get userid from database
                $user_id = UserMeta::get_user_id_by_stripe_account_id( $account->id );
            }

            UserMeta::update_stripe_account_info( $user_id, $account );
        } catch ( \Exception $e ) {
            dokan_log( __CLASS__ . '::' . __FUNCTION__, $e->getMessage() );
        }
    }
}

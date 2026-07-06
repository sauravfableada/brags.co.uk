<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\Support;

use WeDevs\DokanPro\Modules\StripeExpress\Api\Account;

defined( 'ABSPATH' ) || exit; // Exit if called directly

/**
 * User meta data handler class for Stripe gateway.
 *
 * @since   3.6.1
 *
 * @package WeDevs\DokanPro\Modules\StripeExpress\Support
 */
class UserMeta {

    /**
     * Retrieves stripe account information from user meta.
     *
     * @since 3.9.7
     *
     * @param int|string $user_id
     *
     * @return \Stripe\Account|false
     */
    private static function get_account_information_from_stripe( $user_id ) {
        try {
            $account_id = get_user_meta( $user_id, self::stripe_account_id_key(), true );

            return Account::get( $account_id, [] );
        } catch ( \Exception $e ) {
            return false;
        }
    }

    /**
     * Generates meta key for stripe account id.
     *
     * @since      3.6.1
     *
     * @deprecated 3.9.7
     *
     * @return string
     */
    public static function stripe_account_id_key() {
        $key = 'account_id';

        if ( Settings::is_test_mode() ) {
            $key = "test_$key";
        }

        return Helper::meta_key( $key );
    }

    /**
     * Retrieves stripe account ID of a user.
     *
     * @since 3.6.1
     *
     * @param int|string $user_id
     *
     * @return string|false
     */
    public static function get_stripe_account_id( $user_id ) {
        $account_info = self::get_stripe_account_info( $user_id );
        $account_id   = is_array( $account_info ) && isset( $account_info['account_id'] ) ? $account_info['account_id'] : '';
        if ( ! empty( $account_id ) ) {
            return $account_id;
        }

        // backward compatibility code
        if ( ! metadata_exists( 'user', $user_id, static::stripe_account_id_key() ) ) {
            return false;
        }

        // metadata exists, so update data to new system
        try {
            // try to get account id from meta
            $stripe_account = static::get_account_information_from_stripe( $user_id );
            if ( $stripe_account ) {
                // store it to the new system
                self::update_stripe_account_info( $user_id, $stripe_account );
            }

            // delete old meta
            delete_user_meta( $user_id, static::stripe_account_id_key() );
            delete_user_meta( $user_id, static::stripe_account_id_key() . '_trash' );

            // return new account id
            return $stripe_account->id;
        } catch ( \Exception $e ) {
            return false;
        }
    }

    /**
     * Updates a stripe account id for a user.
     *
     * @since 3.6.1
     *
     * @param int|string $user_id
     * @param string     $account_id
     *
     * @return int|boolean
     */
    public static function update_stripe_account_id( $user_id, $account_id ) {
        $account_info = self::get_stripe_account_info( $user_id );

        $account_info['account_id']         = $account_id;
        $account_info['trashed_account_id'] = '';

        return update_user_meta( $user_id, self::get_stripe_account_info_key(), $account_info );
    }

    /**
     * Generates meta key for stripe account information.
     *
     * @since 3.9.7
     *
     * @return string
     */
    public static function get_stripe_account_info_key() {
        $key = 'account_info';

        if ( Settings::is_test_mode() ) {
            $key = "test_$key";
        }

        return Helper::meta_key( $key );
    }

    /**
     * Retrieves stripe account info of a user.
     *
     * @since 3.9.7
     *
     * @param int|string $user_id
     *
     * @return array|false
     */
    public static function get_stripe_account_info( $user_id ) {
        $account_info = get_user_meta( $user_id, self::get_stripe_account_info_key(), true );

        return empty( $account_info ) || ! is_array( $account_info ) ? [] : $account_info;
    }

    /**
     * Updates stripe account info for a user.
     *
     * @since 3.6.1
     *
     * @param int|string      $user_id
     * @param \Stripe\Account $account
     *
     * @return int|boolean
     */
    public static function update_stripe_account_info( $user_id, \Stripe\Account $account ) {
        $account_info       = self::get_stripe_account_info( $user_id );
        $trashed_account_id = $account_info['trashed_account_id'] ?? '';

        /**
         * @property string                    $id                Unique identifier for the object.
         * @property null|\Stripe\StripeObject $business_profile  Business information about the account.
         * @property null|string               $business_type     The business type.
         * @property \Stripe\StripeObject      $capabilities
         * @property bool                      $charges_enabled   Whether the account can create live charges.
         * @property string                    $country           The account's country.
         * @property int                       $created           Time at which the account was connected. Measured in seconds since the Unix epoch.
         * @property string                    $default_currency  Three-letter ISO currency code representing the default currency for the account. This must be a currency that <a href="https://stripe.com/docs/payouts">Stripe supports in the account's country</a>.
         * @property bool                      $details_submitted Whether account details have been submitted. Standard accounts cannot receive payouts before this is true.
         * @property null|string               $email             An email address associated with the account. You can treat this as metadata: it is not used for authentication or messaging account holders.
         * @property \Stripe\StripeObject      $future_requirements
         * @property \Stripe\Person            $individual        <p>This is an object representing a person associated with a Stripe account.</p><p>A platform cannot access a Standard or Express account's persons after the account starts onboarding, such as after generating an account link for the account. See the <a href="https://stripe.com/docs/connect/standard-accounts">Standard onboarding</a> or <a href="https://stripe.com/docs/connect/express-accounts">Express onboarding documentation</a> for information about platform pre-filling and account onboarding steps.</p><p>Related guide: <a href="https://stripe.com/docs/connect/identity-verification-api#person-information">Handling Identity Verification with the API</a>.</p>
         * @property \Stripe\StripeObject      $metadata          Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
         * @property bool                      $payouts_enabled   Whether Stripe can send payouts to this account.
         * @property \Stripe\StripeObject      $requirements
         * @property \Stripe\StripeObject      $tos_acceptance
         * @property string                    $type              The Stripe account type. Can be <code>standard</code>, <code>express</code>, or <code>custom</code>.
         */
        $account_data = [
            'account_id'          => $trashed_account_id ? '' : $account->id, // do not update an account id if it was previously trashed
            'trashed_account_id'  => $trashed_account_id,
            'capabilities'        => $account->capabilities ?? [],
            'charges_enabled'     => $account->charges_enabled ?? false,
            'country'             => $account->country ?? '',
            'created'             => $account->created ?? 0,
            'default_currency'    => $account->default_currency ?? '',
            'details_submitted'   => $account->details_submitted ?? false,
            'email'               => $account->email ?? '',
            'metadata'            => $account->metadata ?? [],
            'tos_acceptance'      => $account->tos_acceptance ?? [],
            'type'                => $account->type ?? '',
            'business_profile'    => $account->business_profile ?? [],
            'business_type'       => $account->business_type ?? '',
            'requirements'        => $account->requirements ?? [],
            'future_requirements' => $account->future_requirements ?? [],
            'individual'          => $account->individual ?? [],
            'payouts_enabled'     => $account->payouts_enabled ?? false,
            'transfers_enabled'   => $account->transfers_enabled ?? false,
        ];

        $meta_key = self::get_stripe_account_info_key();

        return update_user_meta( $user_id, $meta_key, $account_data );
    }

    /**
     * Deletes stripe account id of a user
     *
     * @since 3.6.1
     *
     * @param int|string $user_id ID of the user
     * @param boolean    $force   Default `false` and store the current id in trash, If `true`, no trash will be maintained
     *
     * @return boolean
     */
    public static function delete_stripe_account_id( $user_id, $force = false ) {
        $account_info = [
            'account_id'         => '',
            'trashed_account_id' => '',
        ];

        $account_id = static::get_stripe_account_id( $user_id );
        if ( empty( $account_id ) ) {
            $account_id = static::get_trashed_stripe_account_id( $user_id );
        } else {
            $account_info['trashed_account_id'] = $account_id;
        }

        if ( $force ) {
            $account_info['trashed_account_id'] = '';
            do_action( 'dokan_stripe_express_is_vendor_stripe_account_deleted_from_remote', $account_id );
        }

        return update_user_meta( $user_id, self::get_stripe_account_info_key(), $account_info );
    }

    /**
     * Retrieves stripe account id that was previously trashed.
     *
     * @since 3.6.1
     *
     * @param int|string $user_id
     *
     * @return string|false
     */
    public static function get_trashed_stripe_account_id( $user_id ) {
        $account_info     = self::get_stripe_account_info( $user_id );
        $trash_account_id = $account_info['trashed_account_id'] ?? '';
        $trash_meta_key   = self::stripe_account_id_key() . '_trash';

        if ( empty( $trash_account_id ) && metadata_exists( 'user', $user_id, $trash_meta_key ) ) {
            // backward compatibility
            $trash_account_id = get_user_meta( $user_id, self::stripe_account_id_key(), true );
        }

        return ! empty( $trash_account_id ) ? $trash_account_id : false;
    }

    /**
     * Retrieves user id by stripe account id.
     *
     * @since 3.9.7
     *
     * @param string $account_id
     *
     * @return int|false
     */
    public static function get_user_id_by_stripe_account_id( string $account_id ) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s",
            self::get_stripe_account_info_key(),
            '%' . $wpdb->esc_like( $account_id ) . '%'
        );

        return $wpdb->get_var( $sql );
    }

    /**
     * Retrieves stripe customer id meta key.
     *
     * @since 3.6.1
     *
     * @return string
     */
    public static function stripe_customer_id_key() {
        $key = 'customer_id';

        if ( Settings::is_test_mode() ) {
            $key = "test_$key";
        }

        return Helper::meta_key( $key );
    }

    /**
     * Retrieves stripe customer id.
     *
     * @since 3.6.1
     *
     * @param int|string $user_id
     *
     * @return string|false
     */
    public static function get_stripe_customer_id( $user_id ) {
        return get_user_option( self::stripe_customer_id_key(), $user_id );
    }

    /**
     * Updates stripe customer id.
     *
     * @since 3.6.1
     *
     * @param int|string $user_id
     * @param string     $stripe_id
     *
     * @return string|boolean
     */
    public static function update_stripe_customer_id( $user_id, $stripe_id ) {
        return update_user_option( $user_id, self::stripe_customer_id_key(), $stripe_id );
    }

    /**
     * Deletes stripe cutomer id.
     *
     * @since 3.6.1
     *
     * @param int|string $user_id
     *
     * @return boolean
     */
    public static function delete_stripe_customer_id( $user_id ) {
        return delete_user_option( $user_id, self::stripe_customer_id_key() );
    }

    /**
     * Retrieves meta key for stripe subscription id.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public static function stripe_subscription_id_key() {
        return Helper::meta_key( 'subscription_id' );
    }

    /**
     * Retrieves stripe subscription id.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return string|false
     */
    public static function get_stripe_subscription_id( $user_id ) {
        return get_user_meta( $user_id, self::stripe_subscription_id_key(), true );
    }

    /**
     * Updates stripe subscription id.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     * @param string     $subscription_id
     *
     * @return string|boolean
     */
    public static function update_stripe_subscription_id( $user_id, $subscription_id ) {
        return update_user_meta( $user_id, self::stripe_subscription_id_key(), $subscription_id );
    }

    /**
     * Deletes stripe subscription id.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return boolean
     */
    public static function delete_stripe_subscription_id( $user_id ) {
        return delete_user_meta( $user_id, self::stripe_subscription_id_key() );
    }

    /**
     * Retrieve meta key for temporary stripe subscription id for vendor subscription checkout.
     *
     * @since 3.8.3
     *
     * @return string
     */
    public static function stripe_temp_subscription_id_key() {
        return Helper::meta_key( 'dps_temp_subscription_id' );
    }

    /**
     * Retrieves temporary stripe subscription id for vendor subscription checkout.
     *
     * @since 3.8.3
     *
     * @param int|string $user_id
     *
     * @return string|false
     */
    public static function get_stripe_temp_subscription_id( $user_id ) {
        return get_user_meta( $user_id, self::stripe_temp_subscription_id_key(), true );
    }

    /**
     * Updates stripe temporary subscription id.
     *
     * @since 3.8.3
     *
     * @param int|string $user_id
     * @param string     $subscription_id
     *
     * @return int|bool
     */
    public static function update_stripe_temp_subscription_id( $user_id, $subscription_id ) {
        return update_user_meta( $user_id, self::stripe_temp_subscription_id_key(), $subscription_id );
    }

    /**
     * Deletes stripe temporary subscription id.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return bool
     */
    public static function delete_stripe_temp_subscription_id( $user_id ) {
        return delete_user_meta( $user_id, self::stripe_temp_subscription_id_key() );
    }

    /**
     * Retrieves meta key for stripe subscription id for debugging.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public static function stripe_debug_subscription_id_key() {
        return Helper::meta_key( 'debug_subscription_id' );
    }

    /**
     * Retrieves stripe debug subscription id.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return string|false
     */
    public static function get_stripe_debug_subscription_id( $user_id ) {
        return get_user_meta( $user_id, self::stripe_debug_subscription_id_key(), true );
    }

    /**
     * Updates stripe debug subscription id.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     * @param string     $subscription_id
     *
     * @return string|boolean
     */
    public static function update_stripe_debug_subscription_id( $user_id, $subscription_id ) {
        return update_user_meta( $user_id, self::stripe_debug_subscription_id_key(), $subscription_id );
    }

    /**
     * Deletes stripe debug subscription id.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return boolean
     */
    public static function delete_stripe_debug_subscription_id( $user_id ) {
        return delete_user_meta( $user_id, self::stripe_debug_subscription_id_key() );
    }

    /**
     * Retrieves meta key for customer recurring subscription.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public static function customer_recurring_subscription_key() {
        return '_customer_recurring_subscription';
    }

    /**
     * Checks if a user has active recurring subscription.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return boolean
     */
    public static function has_customer_recurring_subscription( $user_id ) {
        return 'active' === get_user_meta( $user_id, self::customer_recurring_subscription_key(), true );
    }

    /**
     * Updates the status of customer recurring subscription.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     * @param string     $status
     *
     * @return string|boolean
     */
    public static function update_customer_recurring_subscription( $user_id, $status = 'active' ) {
        return update_user_meta( $user_id, self::customer_recurring_subscription_key(), $status );
    }

    /**
     * Retrieves meta key for product order id.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public static function product_order_id_key() {
        return 'product_order_id';
    }

    /**
     * Retrieves product order id of a user.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return string|false
     */
    public static function get_product_order_id( $user_id ) {
        return get_user_meta( $user_id, self::product_order_id_key(), true );
    }

    /**
     * Updates product order id meta value.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     * @param string     $order_id
     *
     * @return int|boolean
     */
    public static function update_product_order_id( $user_id, $order_id ) {
        return update_user_meta( $user_id, self::product_order_id_key(), $order_id );
    }

    /**
     * Retrieves meta key for product order id.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public static function product_pack_id_key() {
        return 'product_package_id';
    }

    /**
     * Retrieves subscribed product pack id of a user.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return integer|false
     */
    public static function get_product_pack_id( $user_id ) {
        return get_user_meta( $user_id, self::product_pack_id_key(), true );
    }

    /**
     * Updates product pack id meta value.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     * @param string     $product_pack_id
     *
     * @return int|boolean
     */
    public static function update_product_pack_id( $user_id, $product_pack_id ) {
        return update_user_meta( $user_id, self::product_pack_id_key(), $product_pack_id );
    }

    /**
     * Deletes product pack id meta value.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return boolean
     */
    public static function delete_product_pack_id( $user_id ) {
        return delete_user_meta( $user_id, self::product_pack_id_key() );
    }

    /**
     * Retrieves meta key for initial product package id.
     *
     * Although there is already a meta key to store this.
     * This one will work as a supporting meta key to avoid
     * any inconsistency at any point where the original meta
     * key might be unavailable for asynchronous process.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public static function initial_product_pack_id_key() {
        return Helper::meta_key( 'product_package_id' );
    }

    /**
     * Retrieves initial product pack id of a user.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return integer|false
     */
    public static function get_initial_product_pack_id( $user_id ) {
        return get_user_meta( $user_id, self::initial_product_pack_id_key(), true );
    }

    /**
     * Updates meta data of initial product pack id.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     * @param string     $product_pack_id
     *
     * @return boolean
     */
    public static function update_initial_product_pack( $user_id, $product_pack_id ) {
        return update_user_meta( $user_id, self::initial_product_pack_id_key(), $product_pack_id );
    }

    /**
     * Deletes initial product pack id meta value.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return boolean
     */
    public static function delete_initial_product_pack_id( $user_id ) {
        return delete_user_meta( $user_id, self::initial_product_pack_id_key() );
    }

    /**
     * Returns meta key for product no with pack key.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public static function product_no_with_pack_key() {
        return 'product_no_with_pack';
    }

    /**
     * Retrieves product no with pack of a user.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return string|false
     */
    public static function get_product_no_with_pack( $user_id ) {
        return get_user_meta( $user_id, self::product_no_with_pack_key(), true );
    }

    /**
     * Updates the product no with pack.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     * @param int|string $product_no
     *
     * @return int|boolean
     */
    public static function update_product_no_with_pack( $user_id, $product_no ) {
        return update_user_meta( $user_id, self::product_no_with_pack_key(), $product_no );
    }

    /**
     * Deletes meta data of no of product with pack of a vendor.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return boolean
     */
    public static function delete_product_no_with_pack( $user_id ) {
        return delete_user_meta( $user_id, self::product_no_with_pack_key() );
    }

    /**
     * Retrieves active cancelled subscription meta key.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public static function active_cancelled_subscription_key() {
        return 'dokan_has_active_cancelled_subscrption';
    }

    /**
     * Checks if a user has active cancelled subscription.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return boolean
     */
    public static function has_active_cancelled_subscrption( $user_id ) {
        return wc_string_to_bool( get_user_meta( $user_id, self::active_cancelled_subscription_key(), true ) );
    }

    /**
     * Updates flag for active cancelled subscription.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     * @param boolean    $status
     *
     * @return boolean|string
     */
    public static function update_active_cancelled_subscription( $user_id, $status = true ) {
        return update_user_meta( $user_id, self::active_cancelled_subscription_key(), $status );
    }

    /**
     * Retrieves product pack end date meta key.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public static function product_pack_end_key() {
        return 'product_pack_enddate';
    }

    /**
     * Retrieves product pack end date of a user.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return string|false
     */
    public static function get_product_pack_end_date( $user_id ) {
        return get_user_meta( $user_id, self::product_pack_end_key(), true );
    }

    /**
     * Updates product pack end date of a user.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     * @param string     $end_date
     *
     * @return int|boolean
     */
    public static function update_product_pack_end_date( $user_id, $end_date ) {
        return update_user_meta( $user_id, self::product_pack_end_key(), $end_date );
    }

    /**
     * Retrieves product pack start date meta key.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public static function product_pack_start_key() {
        return 'product_pack_startdate';
    }

    /**
     * Retrieves product pack end date of a user.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return string|false
     */
    public static function get_product_pack_start_date( $user_id ) {
        return get_user_meta( $user_id, self::product_pack_start_key(), true );
    }

    /**
     * Updates product pack start date of a user.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     * @param string     $end_date
     *
     * @return int|boolean
     */
    public static function update_product_pack_start_date( $user_id, $start_date ) {
        return update_user_meta( $user_id, self::product_pack_start_key(), $start_date );
    }

    /**
     * Retrieves has pending subscription meta key.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public static function has_pending_subscription_key() {
        return 'has_pending_subscription';
    }

    /**
     * Retrieves if a user has pending subscription.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return string|false
     */
    public static function has_pending_subscription( $user_id ) {
        return get_user_meta( $user_id, self::has_pending_subscription_key(), true );
    }

    /**
     * Update flag for pending subscription.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     * @param boolean    $status
     *
     * @return int|boolean
     */
    public static function update_pending_subscription( $user_id, $status = true ) {
        return update_user_meta( $user_id, self::has_pending_subscription_key(), ( true === $status ) );
    }

    /**
     * Retrieves can post product meta key.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public static function can_post_product_key() {
        return 'can_post_product';
    }

    /**
     * Checks if a user is allowed to post product.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     *
     * @return boolean
     */
    public static function can_post_product( $user_id ) {
        return wc_string_to_bool( get_user_meta( $user_id, self::can_post_product_key(), true ) );
    }

    /**
     * Update the can post product status.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     * @param int|string $status
     *
     * @return boolean
     */
    public static function update_post_product( $user_id, $status = '1' ) {
        return update_user_meta( $user_id, self::can_post_product_key(), $status );
    }

    /**
     * Retrieves seller enabled meta key.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public static function seller_enabled_key() {
        return 'dokan_enable_selling';
    }

    /**
     * Update whether seller is enabled.
     *
     * @since 3.7.8
     *
     * @param int|string $user_id
     * @param int|string $status
     *
     * @return boolean
     */
    public static function update_seller_enabled( $user_id, $status = 'yes' ) {
        return update_user_meta( $user_id, self::seller_enabled_key(), $status );
    }

    /**
     * Retrieves the meta key for storing the onboarding country of a user.
     *
     * This meta key is used to store the country code that a user selects or is detected during the Stripe Express onboarding process.
     * Storing the onboarding country is crucial for ensuring that the Stripe Express setup is compliant with the country-specific regulations
     * and for customizing the onboarding experience based on the user's location.
     *
     * @since 3.11.2
     *
     * @return string The meta key used for storing the onboarding country.
     */
    public static function onboarding_country_meta_key() {
        return 'dokan_stripe_express_onboarded_country';
    }

    /**
     * Get whether country is onboarded.
     *
     * Retrieves the onboarding country metadata for a user. If the metadata does not exist, it returns false.
     *
     * @since 3.11.2
     *
     * @param int|string $user_id ID of the user
     *
     * @return string|false The onboarding country code if set, otherwise false.
     */
    public static function get_onboarding_country( $user_id ) {
        return get_user_meta( $user_id, self::onboarding_country_meta_key(), true );
    }

    /**
     * Update whether country is onboarded.
     *
     * @since 3.11.2
     *
     * @param int|string $user_id ID of the user
     * @param string     $country Country code
     *
     * @return boolean
     */
    public static function update_onboarding_country( $user_id, $country ) {
        return update_user_meta( $user_id, self::onboarding_country_meta_key(), $country );
    }

    /**
     * Delete whether country is onboarded.
     *
     * @since 3.11.2
     *
     * @param int|string $user_id ID of the user
     *
     * @return boolean
     */
    public static function delete_onboarding_country( $user_id ) {
        return delete_user_meta( $user_id, self::onboarding_country_meta_key() );
    }
}

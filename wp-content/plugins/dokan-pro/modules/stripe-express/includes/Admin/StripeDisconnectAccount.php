<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\Admin;

use WeDevs\DokanPro\Modules\StripeExpress\Support\Helper;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Settings;
use WeDevs\DokanPro\Modules\StripeExpress\Support\UserMeta;

/**
 * Disconnect stripe account
 *
 * @since 3.11.2
 */
class StripeDisconnectAccount {

	/**
	 * Initializes and calls all hooks
	 *
	 * @since 3.11.2
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init_hooks' ) );
	}

	public function init_hooks() {
		// Register actions to disconnect vendors
		add_action( 'dokan_generate_individual_vendor_disconnect_queue', array( self::class, 'disconnect_vendors' ), 10, 3 );
		add_action( 'dokan_individual_vendor_disconnect_queue', array( self::class, 'disconnect_vendor' ), 10, 3 );

		// Register action to disconnect vendor when the store country is changed
		add_action( 'update_user_meta', array( self::class, 'disconnect_on_country_changed' ), 10, 4 );
	}

	/**
	 * Start the queue for disconnecting vendors
	 *
	 * @since 3.11.2
	 *
	 * @param int $offset The starting point for the queue, example: 0 is user id.
	 *
	 * @return void
	 */
	public static function start_disconnect_queue( $offset = 0 ) {
		// Get restricted countries.
		$restricted_countries = Settings::get_restricted_countries();
		$remove_connected     = Settings::is_disconnect_connected_vendors_enabled();

		// Set the queue for collecting vendor's id to disconnect
		$queue_params = array(
			'offset'           => $offset,
			'countries'        => $restricted_countries,
			'remove_connected' => $remove_connected,
		);
		WC()->queue()->add( 'dokan_generate_individual_vendor_disconnect_queue', $queue_params, 'dokan' );
		Helper::log( 'Queue added for disconnecting vendors from: ' . print_r( wp_json_encode( $queue_params ), true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
	}

	/**
	 * Create individual disconnect queue
	 *
	 * @since 3.11.2
	 *
	 * @param int   $offset             The starting point for the queue, example: 0 is user id.
	 * @param array $countries          The list of onboarding countries
	 * @param bool  $remove_connected   Whether to remove non-US vendors
	 *
	 * @return void
	 */
	public static function disconnect_vendors( $offset, $countries, $remove_connected = false ) {
		// Get the platform country
		$shop_country = WC()->countries->get_base_country();

		// Get all vendors who are connected to Stripe Express and not from the store country
		$query_limit = 10;
		$vendors     = dokan()->vendor->all(
			array(
				'fields'     => 'ID',
				'status'     => array( 'all' ),
				'number'     => $query_limit,
				'offset'     => $offset,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => UserMeta::onboarding_country_meta_key(),
						'value'   => $remove_connected ? $shop_country : $countries,
						'compare' => $remove_connected ? '!=' : 'IN',
					),
					array(
						'key'     => UserMeta::get_stripe_account_info_key(),
						'value'   => serialize( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
							array(
								'account_id'         => '',
								'trashed_account_id' => '',
							)
						),
						'compare' => 'NOT LIKE',
					),
				),
			)
		);

		if ( empty( $vendors ) ) {
			return;
		}

		/**
		 * List of vendors
		 *
		 * @var integer|string[] $vendors
		 * @var integer|string $vendor
		 *
		 * @since 3.11.2
		 */
		foreach ( $vendors as $vendor ) {
			$disconnect_queue_params = array(
				'user_id' => $vendor,
				'force'   => false,
				'reason'  => $remove_connected ? 'disconnect' : 'restriction',
			);
			WC()->queue()->add( 'dokan_individual_vendor_disconnect_queue', $disconnect_queue_params, 'dokan' );
			Helper::log( 'Queue added for disconnecting individual vendor: ' . print_r( wp_json_encode( $disconnect_queue_params ), true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}

		// Check if there are more vendors to process
		if ( $offset + $query_limit < dokan()->vendor->get_total() ) {
			$queue_params = array(
				'offset'    => $offset + $query_limit,
				'countries' => $countries,
			);
			WC()->queue()->add( 'dokan_generate_individual_vendor_disconnect_queue', $queue_params, 'dokan' );
			Helper::log( 'Queue added for disconnecting vendors: ' . print_r( wp_json_encode( $queue_params ), true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}
	}

	/**
	 * Disconnect individual vendor from Stripe Express
	 *
	 * @since 3.11.2
	 *
	 * @param int $user_id The user ID of the vendor
	 *
	 * @return void
	 */
	public static function disconnect_vendor( $user_id, $force = false, $reason = 'restriction' ) {
		// Disconnect the vendor from Stripe Express and delete the account ID
		if ( in_array( $reason, array( 'disconnect', 'country_changed' ), true ) ) {
			UserMeta::update_stripe_account_id( $user_id, '' );
			UserMeta::delete_onboarding_country( $user_id );
		} else {
			UserMeta::delete_stripe_account_id( $user_id, $force );
		}
		Helper::log( "Vendor($user_id) disconnected from Stripe Express" );

		/**
		 * Filter the arguments for the announcement
		 *
		 * @param array<string, mixed> $args The arguments for the announcement
		 *
		 * @since 3.11.2
		 */
		$args = apply_filters(
			'dokan_stripe_express_disconnected_notice_args',
			array(
				'title'             => esc_html__( 'Account Disconnected from Stripe Express', 'dokan' ),
				'content'           => self::notice_to_disconnected_vendor( $reason ),
				'announcement_type' => 'selected_seller',
				'sender_ids'        => array( $user_id ),
				'status'            => 'publish',
			)
		);

		// Create the announcement
		$disconnected_notice = dokan_pro()->announcement->manager->create_announcement( $args );
		Helper::log( 'Announcement created for disconnected seller: ' . print_r( wp_json_encode( $args ), true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

		if ( is_wp_error( $disconnected_notice ) ) {
			Helper::log(
				sprintf(
					'Error creating announcement for disconnected seller %1$s. Error Message: %2$s',
					$user_id,
					$disconnected_notice->get_error_message()
				)
			);
		}
	}

	/**
	 * Disconnect vendor when the store country is changed
	 *
	 * @since 3.11.2
	 *
	 * @param int       $meta_id        ID of the metadata entry to update.
	 * @param int       $store_id       ID of the object metadata is for.
	 * @param string    $meta_key       Metadata key.
	 * @param mixed     $_meta_value    Metadata value.
	 *
	 * @return void
	 */
	public static function disconnect_on_country_changed( $meta_id, $store_id, $meta_key, $_meta_value ) {
        // Check if the cross-border transfer is enabled
        if ( ! Settings::is_cross_border_transfer_enabled() ) {
            return;
        }

        // Check if the metadata key is for the store settings
        if ( 'dokan_profile_settings' !== $meta_key || ! is_array( $_meta_value ) ) {
			return;
		}

		// Check if the store country has changed
		if ( ! isset( $_meta_value['address']['country'] ) ) {
			return;
		}

        // Get the vendor's Stripe account ID and trashed Stripe account ID
        $stripe_id          = UserMeta::get_stripe_account_id( $store_id );
        $trashed_stripe_id  = UserMeta::get_trashed_stripe_account_id( $store_id );

        // Check if the vendor is connected to Stripe Express
        if ( empty( $stripe_id ) && ! empty( $trashed_stripe_id ) ) {
            return;
        }

        // Get the vendor's onboarding country and the new country from the store settings.
		$old_country = UserMeta::get_onboarding_country( $store_id );
        $new_country = $_meta_value['address']['country'];

        // Update the onboarding country if it is empty, but the new country is not.
        if ( empty( $old_country ) && ! empty( $new_country ) ) {
            UserMeta::update_onboarding_country( $store_id, $new_country );
            Helper::log( "Onboarding country updated for store ID: $store_id. New country: $new_country" );
            return;
        }

        // Disconnect the vendor if the store country has changed.
		if ( ! empty( $old_country ) && ( $old_country !== $new_country ) ) {
            Helper::log( "Country changed for store ID: $store_id. Old: $old_country, New: $new_country" );
            self::disconnect_vendor( $store_id, false, 'country_changed' );
		}
	}

	/**
	 * Retrieves notice for disconnected sellers.
	 *
	 * @since 3.11.2
	 *
	 * @param string $reason The reason for disconnection
	 *
	 * @return string
	 */
	private static function notice_to_disconnected_vendor( $reason ) {
		if ( 'restriction' === $reason ) {
			$message = esc_html__( 'We regret to inform you that your account has been disconnected from the Stripe Express payment method due to restrictions imposed by the admin.', 'dokan' );
		} elseif ( 'country_changed' === $reason ) {
			$message = esc_html__( 'We regret to inform you that your account has been disconnected from the Stripe Express payment method due to your store country has changed from previously connected country.', 'dokan' );
		} else {
			$message = esc_html__( 'We regret to inform you that your account has been disconnected from the Stripe Express payment method manually.', 'dokan' );
		}

		$message .= ' ' . esc_html__( ' However, please note that your Stripe Express account itself remains active, and any existing balances are unaffected.', 'dokan' );
		$message .= "\n\n";
		$message .= esc_html__( 'To continue selling products and receiving payouts, we encourage you to connect one of the other available payment methods on our platform. This will ensure a seamless experience for you and your customers during the checkout process.', 'dokan' );
		$message .= "\n\n";
		$message .= esc_html__( "Thank you for your understanding regarding this necessary account disconnection from Stripe Express. If you have any further questions or need assistance with setting up an alternative payment method, please don't hesitate to reach out to our support team.", 'dokan' );
		$message .= "\n\n";
		$message .= "\n\n";

		return $message;
	}
}

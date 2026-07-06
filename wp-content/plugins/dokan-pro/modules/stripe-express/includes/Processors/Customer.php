<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\Processors;

defined( 'ABSPATH' ) || exit; // Exit if called directly

use WC_Customer;
use WC_Order;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Settings;
use WP_Error;
use WeDevs\Dokan\Exceptions\DokanException;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Helper;
use WeDevs\DokanPro\Modules\StripeExpress\Api\SetupIntent;
use WeDevs\DokanPro\Modules\StripeExpress\Support\UserMeta;
use WeDevs\DokanPro\Modules\StripeExpress\Api\PaymentMethod;
use WeDevs\DokanPro\Modules\StripeExpress\Api\Customer as CustomerApi;

/**
 * Class for processing customers.
 *
 * Represents a Stripe Customer.
 *
 * @since 3.6.1
 *
 * @package WeDevs\DokanPro\Modules\StripeExpress\Processors
 */
class Customer {

	/**
	 * Class instance
	 *
	 * @since 3.6.1
	 *
	 * @var mixed
	 */
	private static $instance = null;

	/**
	 * Stripe customer ID.
	 *
	 * @since 3.6.1
	 *
	 * @var string
	 */
	private $id = '';

	/**
	 * WP User ID.
	 *
	 * @since 3.6.1
	 *
	 * @var integer
	 */
	private $user_id = 0;

	/**
	 * Data from API.
	 *
	 * @since 3.6.1
	 *
	 * @var \Stripe\Customer
	 */
	private $customer_data = null;

	/**
	 * Private constructor for singletone instance
	 *
	 * @since 3.6.1
	 *
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Get the class instance
	 *
	 * @since 3.8.3
	 *
	 * @return static
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new static(); // @phpstan-ignore-line
		}

		return self::$instance;
	}

	/**
	 * Sets required data.
	 *
	 * @since 3.6.1
	 *
	 * @param int|string $user_id
	 *
	 * @return static
	 */
	public static function set( $user_id = 0 ) {
		$instance = static::get_instance();

		if ( $user_id ) {
			$instance->set_user_id( $user_id );

			$customer_id = UserMeta::get_stripe_customer_id( $user_id );
			if ( $customer_id ) {
				$instance->set_id( $customer_id );
			}
		}

		return $instance;
	}

	/**
	 * Sets user id for customer.
	 *
	 * @since 3.6.1
	 *
	 * @param int|string $user_id
	 *
	 * @return static
	 */
	public function set_user_id( $user_id ) {
		$this->user_id = $user_id;
		return $this;
	}

	/**
	 * Retrieves WP user id.
	 *
	 * @since 3.6.1
	 *
	 * @return int
	 */
	public function get_user_id() {
		return absint( $this->user_id );
	}

	/**
	 * Sets Stripe customer ID.
	 *
	 * @since 3.6.1
	 *
	 * @param int|string $id
	 *
	 * @return static
	 */
	public function set_id( $id ) {
		$this->id = $id;
		return $this;
	}

	/**
	 * Retrieves Stripe customer ID.
	 *
	 * @since 3.6.1
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Retrieves user object.
	 *
	 * @since 3.6.1
	 *
	 * @return \WP_User|false
	 */
	protected function get_user() {
		return $this->get_user_id() ? \get_user_by( 'id', $this->get_user_id() ) : false;
	}

	/**
	 * Stores data from the Stripe API about this customer.
	 *
	 * @since 3.6.1
	 *
	 * @param \Stripe\Customer $data
	 *
	 * @return static
	 */
	public function set_data( $data ) {
		$this->customer_data = $data;

		return $this;
	}

	/**
	 * Retrieves data from the Stripe API about this customer.
	 *
	 * @since 3.11.4
	 *
	 * @return \Stripe\Customer|null
	 */
	public function get_data() {
		return $this->customer_data;
	}

	/**
	 * Check if a customer exists in Stripe.
	 *
	 * @param string|int $customer_id Customer ID
	 *
	 * @return bool|WP_Error
	 */
	public static function is_exists( $customer_id ) {
		$instance = static::get_instance();

		if ( empty( $customer_id ) || ! $instance instanceof static ) {
			return false;
		}

        if ( $customer_id instanceof \Stripe\Customer ) {
            $instance->set_id( $customer_id->id );
        } else {
            $instance->set_id( $customer_id );
        }

		return $instance->retrieve() instanceof \Stripe\Customer;
	}

	/**
	 * Retrieves a Customer object from Stripe API.
	 *
	 * @since 3.11.4
	 *
	 * @return WP_Error|\Stripe\Customer
	 */
	public function retrieve() {
		if ( empty( $this->get_id() ) ) {
			return new WP_Error( 'id_required_to_get_user', __( 'Attempting to get a Stripe customer without a customer ID.', 'dokan' ) );
		}

		try {
			$response = CustomerApi::retrieve( $this->get_id() );
		} catch ( \Stripe\Exception\ApiErrorException | DokanException | \Exception $e ) {
			return new WP_Error( 'dokan-stripe-customer-get-error', $e->getMessage() );
		}

		$this->set_id( $response->id );

		return $response;
	}

	/**
	 * Creates a customer via API.
	 *
	 * @since 3.6.1
	 *
	 * @param array $args Additional arguments for the request (optional).
	 *
	 * @return WP_Error|string
	 */
	public function create( $args = array() ) {
		$args = $this->generate_request( $args );

		try {
			$response = CustomerApi::create( $args );
		} catch ( DokanException $e ) {
			return new WP_Error( 'dokan-stripe-customer-create-error', $e->getMessage() );
		}

		$this->set_id( $response->id );
		$this->set_data( $response );

		if ( $this->get_user_id() ) {
			UserMeta::update_stripe_customer_id( $this->get_user_id(), $response->id );
		}

		return $response->id;
	}

	/**
	 * Updates the Stripe customer through the API.
	 *
	 * @since 3.6.1
	 *
	 * @param array $args     Additional arguments for the request (optional).
	 * @param bool  $is_retry Whether the current call is a retry (optional, defaults to false). If true, then an exception will be thrown instead of further retries on error.
	 *
	 * @return string|WP_Error
	 */
	public function update( $args = array(), $is_retry = false ) {
		if ( empty( $this->get_id() ) ) {
			return new WP_Error( 'id_required_to_update_user', __( 'Attempting to update a Stripe customer without a customer ID.', 'dokan' ) );
		}

		$args = $this->generate_request( $args );

		try {
			$response = CustomerApi::update( $this->get_id(), $args );
		} catch ( DokanException $e ) {
			if ( Helper::is_no_such_customer_error( $e->get_message() ) && ! $is_retry ) {
				/*
				 * This can happen when switching the main Stripe account
				 * or importing users from another site.
				 * If not already retrying, recreate the customer
				 * and then try updating it again.
				 */
				$this->recreate();
				return $this->update( $args, true );
			}

			return new WP_Error( 'customer_update_failed', $e->getMessage() );
		}

		$this->set_data( $response );

		return $this->get_id();
	}

	/**
	 * Updates existing Stripe customer or creates new customer for User through API.
	 *
	 * @since 3.6.1
	 *
	 * @param array $args Additional arguments for the request (optional).
	 *
	 * @return string|WP_Error
	 */
	public function update_or_create( $args = array() ) {
		if ( empty( $this->get_id() ) ) {
			return $this->recreate();
		} else {
			return $this->update( $args, true );
		}
	}

	/**
	 * Recreates the customer for this user.
	 *
	 * @since 3.6.1
	 *
	 * @return string|WP_Error ID of the new Customer object.
	 */
	private function recreate() {
		UserMeta::delete_stripe_customer_id( $this->get_user_id() );

		return $this->create();
	}

	/**
	 * Gets saved payment methods for a customer using Intentions API.
	 *
	 * @since 3.6.1
	 *
	 * @param string $payment_method_type Stripe ID of payment method type
	 *
	 * @return \Stripe\PaymentMethod[]
	 */
	public function get_payment_methods( $payment_method_type = '' ) {
		if ( ! $this->get_id() ) {
			return array();
		}

		$args = array(
			'type'  => $payment_method_type,
		);

		if ( Helper::get_sepa_payment_method_type() === $payment_method_type ) {
			$args['expand'] = array(
				"data.$payment_method_type.generated_from.charge",
				"data.$payment_method_type.generated_from.setup_attempt",
			);
		}

		return PaymentMethod::get_by_customer( $this->get_id(), $args );
	}

	/**
	 * Attaches a payment method to the customer in Stripe.
	 *
	 * @since 3.7.8
	 *
	 * @param string $payment_method_id
	 *
	 * @return \Stripe\PaymentMethod|WP_Error
	 */
	public function attach_payment_method( $payment_method_id ) {
		if ( empty( $payment_method_id ) ) {
			return new WP_Error( 'dokan_no_payment_method', __( 'No payment method provided', 'dokan' ) );
		}

		// If customer doesn't exist, create one.
		if ( ! $this->get_id() ) {
			$id = $this->create();

			if ( is_wp_error( $id ) ) {
				return $id;
			}

			$this->set_id( $id );
		}

		try {
			$payment_method = PaymentMethod::attach( $payment_method_id, $this->get_id() );
		} catch ( DokanException $e ) {
			if ( Helper::is_no_such_customer_error( $e->getMessage() ) ) {
				$customer_id = $this->recreate();
				if ( is_wp_error( $customer_id ) ) {
					return $customer_id;
				}

				return $this->attach_payment_method( $payment_method_id );
			} else {
				return new WP_Error( 'dokan_unable_to_attach_payment_method', $e->getMessage() );
			}
		}

		if ( ! empty( $payment_method->error ) ) {
			return new WP_Error( 'dokan_unable_to_attach_payment_method', Helper::get_error_message_from_response( $payment_method ) );
		}

		// Set the newly attached Payment Method as default.
		$this->set_default_payment_method( $payment_method->id );

		/**
		 * Fires after a payment method is attached to a customer.
		 *
		 * @param string                $customer_id
         * @param \Stripe\PaymentMethod $payment_method
		 * @param string                $stripe_customer_id
		 */
		do_action( 'dokan_stripe_express_attach_payment_method', $this->get_user_id(), $payment_method, $this->get_id() );

		return $payment_method;
	}

	/**
	 * Detaches a payment method from stripe.
	 *
	 * @since 3.6.1
	 *
	 * @param string $payment_method_id Payment Method ID
	 *
	 * @return boolean
	 */
	public function detach_payment_method( $payment_method_id ) {
		if ( ! $this->get_id() ) {
			return false;
		}

		try {
			$response = PaymentMethod::detach( $payment_method_id );
		} catch ( DokanException $e ) {
			return false;
		}

		if ( empty( $response->error ) ) {

			/**
			 * Fires after a payment method is detached from a customer.
			 *
			 * @param string                $stripe_customer_id
			 * @param \Stripe\PaymentMethod $payment_method
			 */
			do_action( 'dokan_stripe_express_detach_payment_method', $this->get_id(), $response );

			return true;
		}

		return false;
	}

	/**
	 * Sets default payment method in Stripe.
	 *
	 * @since 3.6.1
	 *
	 * @param string $payment_method_id Payment Method ID
	 *
	 * @return boolean
	 */
	public function set_default_payment_method( $payment_method_id ): bool {
		$customer = $this->update(
			array(
				'invoice_settings' => array(
					'default_payment_method' => $payment_method_id,
				),
			)
		);

		if ( is_wp_error( $customer ) ) {
			return false;
		}

		/**
		 * Fires after a default payment method is set for a customer.
		 *
		 * @param string $stripe_customer_id Customer ID
		 * @param string $payment_method_id Payment Method ID
		 */
		do_action( 'dokan_stripe_express_set_default_payment_method', $this->get_id(), $payment_method_id );

		return true;
	}

	/**
	 * Creates setup intent for a customer.
	 *
	 * @since 3.7.8
	 *
	 * @param array $data Additional arguments for the request (optional).
	 *
	 * @return \Stripe\SetupIntent
	 * @throws DokanException
	 */
	public function setup_intent( $data = array() ) {
		try {
			$customer_id = $this->get_id();
			// Create customer on Stripe end if not exists
			if ( empty( $customer_id ) ) {
				$customer_data = $this->map_data( null, new WC_Customer( $this->get_user_id() ) );
				$customer_id   = $this->create( $customer_data );

				if ( is_wp_error( $customer_id ) ) {
					throw new DokanException(
						'setup-intent-error',
						sprintf(
							/* translators: error message */
							__( 'We\'re not able to add this payment method. Error: %s', 'dokan' ),
							$customer_id->get_error_message()
						)
					);
				}
			}

			$request_args = wp_parse_args(
				$data,
				array(
					'customer'                  => $this->get_id(),
					'confirm'                   => 'false',
					'automatic_payment_methods' => array(
						'enabled' => true,
					),
				)
			);

            $pm_configuration = Settings::get_payment_method_configuration();
            if ( ! empty( $pm_configuration ) ) {
                $request_args['payment_method_configuration'] = $pm_configuration;
            }

			$setup_intent = SetupIntent::create( $request_args );

			if ( ! empty( $setup_intent->error ) ) {
				$error_code = 'setup-intent-error';
				if ( Helper::is_no_such_customer_error( $setup_intent->error->message ) ) {
					$error_code = 'error_setup-intent_no-such-customer';
				}
				throw new DokanException( $error_code, $setup_intent->error->message );
			}

			return $setup_intent;
		} catch ( DokanException $e ) {
			if ( Helper::is_no_such_customer_error( $e ) ) {
				$this->set_id( 0 );
				return $this->setup_intent();
			}

			throw new DokanException( 'setup-intent-error', esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Generates the customer request, used for both creating and updating customers.
	 *
	 * @since 3.6.1
	 *
	 * @param  array $args Additional arguments (optional).
	 *
	 * @return array
	 */
	protected function generate_request( $args = array() ): array {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
		$billing_email = isset( $_POST['billing_email'] ) ? sanitize_email( wp_unslash( $_POST['billing_email'] ) ) : '';
		$user          = $this->get_user();

		if ( $user instanceof \WP_User ) {
			$billing_first_name = get_user_meta( $user->ID, 'billing_first_name', true );
			$billing_last_name  = get_user_meta( $user->ID, 'billing_last_name', true );

			// If billing first name does not exists try the user first name.
			if ( empty( $billing_first_name ) ) {
				$billing_first_name = get_user_meta( $user->ID, 'first_name', true );
			}

			// If billing last name does not exists try the user last name.
			if ( empty( $billing_last_name ) ) {
				$billing_last_name = get_user_meta( $user->ID, 'last_name', true );
			}

			// translators: %1$s First name, %2$s Second name, %3$s Username.
			$description = sprintf( __( 'Name: %1$s %2$s, Username: %3$s', 'dokan' ), $billing_first_name, $billing_last_name, $user->user_login );

			$defaults = array(
				'email'       => $user->user_email,
				'description' => $description,
			);

			$billing_full_name = trim( $billing_first_name . ' ' . $billing_last_name );
			if ( ! empty( $billing_full_name ) ) {
				$defaults['name'] = $billing_full_name;
			}
		} else {
			$billing_first_name = isset( $_POST['billing_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_first_name'] ) ) : '';
			$billing_last_name  = isset( $_POST['billing_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_last_name'] ) ) : '';

			// translators: %1$s First name, %2$s Second name.
			$description = sprintf( __( 'Name: %1$s %2$s, Guest', 'dokan' ), $billing_first_name, $billing_last_name );

			$defaults = array(
				'email'       => $billing_email,
				'description' => $description,
			);

			$billing_full_name = trim( $billing_first_name . ' ' . $billing_last_name );
			if ( ! empty( $billing_full_name ) ) {
				$defaults['name'] = $billing_full_name;
			}
		}

		$defaults['preferred_locales'] = $this->get_preferred_locale();
		$defaults['metadata']          = apply_filters( 'dokan_stripe_express_customer_metadata', array(), $user );

		return wp_parse_args( $args, $defaults );
        // phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Given a WC_Order or WC_Customer, returns an array representing a Stripe customer object.
	 * At least one parameter has to not be null.
	 *
	 * @since 3.6.1
	 *
	 * @param WC_Order|null    $wc_order    The Woo order to parse.
	 * @param WC_Customer|null $wc_customer The Woo customer to parse.
	 *
	 * @return array Customer data.
	 */
	public function map_data( ?WC_Order $wc_order = null, ?WC_Customer $wc_customer = null ) {
		if ( null === $wc_customer && null === $wc_order ) {
			return array();
		}

		// Where available, the order data takes precedence over the customer.
		$object_to_parse = isset( $wc_order ) ? $wc_order : $wc_customer;
		$name            = $object_to_parse->get_billing_first_name() . ' ' . $object_to_parse->get_billing_last_name();
		$description     = '';
		if ( null !== $wc_customer && ! empty( $wc_customer->get_username() ) ) {
			// We have a logged in user, so add their username to the customer description.
			// translators: %1$s Name, %2$s Username.
			$description = sprintf( __( 'Name: %1$s, Username: %2$s', 'dokan' ), $name, $wc_customer->get_username() );
		} else {
			// Current user is not logged in.
			// translators: %1$s Name.
			$description = sprintf( __( 'Name: %1$s, Guest', 'dokan' ), $name );
		}

		$data = array(
			'name'        => $name,
			'description' => $description,
			'email'       => $object_to_parse->get_billing_email(),
			'phone'       => $object_to_parse->get_billing_phone(),
			'address'     => array(
				'line1'       => $object_to_parse->get_billing_address_1(),
				'line2'       => $object_to_parse->get_billing_address_2(),
				'postal_code' => $object_to_parse->get_billing_postcode(),
				'city'        => $object_to_parse->get_billing_city(),
				'state'       => $object_to_parse->get_billing_state(),
				'country'     => $object_to_parse->get_billing_country(),
			),
		);

		if ( ! empty( $object_to_parse->get_shipping_postcode() ) ) {
			$data['shipping'] = array(
				'name'    => $object_to_parse->get_shipping_first_name() . ' ' . $object_to_parse->get_shipping_last_name(),
				'address' => array(
					'line1'       => $object_to_parse->get_shipping_address_1(),
					'line2'       => $object_to_parse->get_shipping_address_2(),
					'postal_code' => $object_to_parse->get_shipping_postcode(),
					'city'        => $object_to_parse->get_shipping_city(),
					'state'       => $object_to_parse->get_shipping_state(),
					'country'     => $object_to_parse->get_shipping_country(),
				),
			);
		}

		return $data;
	}

	/**
	 * Get the customer's preferred locale based on the user or site setting.
	 *
	 * @since 3.6.1
	 *
	 * @return array The matched locale string wrapped in an array, or empty default.
	 */
	public function get_preferred_locale(): array {
		$user           = $this->get_user();
		$locale         = Helper::get_locale( $user );
		$stripe_locales = Helper::get_stripe_locale_options();
		$preferred      = isset( $stripe_locales[ $locale ] ) ? $stripe_locales[ $locale ] : 'en-US';
		return array( $preferred );
	}
}

<?php

namespace WeDevs\DokanPro\Modules\MangoPay\Processor;

use MangoPay\CardRegistration;
use MangoPay\Card as MangoCard;
use MangoPay\Libraries\ResponseException;
use MangoPay\Libraries\Exception as MangoException;
use WeDevs\DokanPro\Modules\MangoPay\Support\Helper;
use WeDevs\DokanPro\Modules\MangoPay\Support\Processor;

/**
 * Card handler class
 *
 * @since 3.5.0
 */
class Card extends Processor {

    /**
     * Register a credit card for mangopay user
     *
     * @since 3.5.0
     *
     * @param int|string $wp_user_id    WordPress user ID
     * @param string     $currency      Currency code
     * @param string     $card_type     Card type
     * @param string     $name
     *
     * @return array
     */
    public static function register( $wp_user_id, $currency, $card_type, $name = '' ) {
        if ( empty( $name ) ) {
            $user = get_userdata( $wp_user_id );

            if ( $user ) {
                $name = $user->display_name;
            }
        }

        try {
            $mp_user_id                  = User::sync_account_data( $wp_user_id );
            $card_registration           = new CardRegistration();
            $card_registration->Tag 	 = $name;
            $card_registration->UserId 	 = $mp_user_id;
            $card_registration->Currency = $currency;
            $card_registration->CardType = $card_type;
            $response 					 = static::config()->mangopay_api->CardRegistrations->Create( $card_registration );
        } catch( ResponseException $e ) {
            return array(
                'success' => false,
                'message' => $e->GetMessage(),
                'details' => $e->GetErrorDetails()
            );
        } catch( MangoException $e ) {
            return array(
                'success' => false,
                'message' => $e->GetMessage()
            );
        }

        return array(
            'success'  => true,
            'response' => $response,
        );
    }

    /**
     * Update a registered card
     *
     * @since 3.5.0
     *
     * @param string|int $card_id   Card ID
     * @param array 	 $data      Card data
     *
     * @return array
     */
    public static function update( $card_id, $data ) {
        try {
            $card_registration                   = new CardRegistration();
            $card_registration->Id               = $card_id;
            $card_registration->RegistrationData = $data;
            $response                            = static::config()->mangopay_api->CardRegistrations->Update( $card_registration );
        } catch( ResponseException $e ) {
            return array(
                'success' => false,
                'message' => $e->GetMessage(),
                'details' => $e->GetErrorDetails()
            );
        } catch( MangoException $e ) {
            return array(
                'success' => false,
                'message' => $e->GetMessage()
            );
        }

        return array(
            'success'  => true,
            'response' => $response,
        );
    }

    /**
     * De-activate a pre-authorized card
     *
     * @since 3.5.0
     *
     * @param int|string $card_id  Card ID
     *
     * @return array
     */
    public static function deactivate( $card_id ) {
        try {
            $card         = new MangoCard();
            $card->Id 	  = $card_id;
            $card->Active = false;
            $response     = static::config()->mangopay_api->Cards->Update( $card );

            return array(
                'success'  => true,
                'response' => $response,
            );
        } catch( ResponseException $e ) {
            Helper::log(
                sprintf( 'Could not deactivate card: %s. Message: %s. Error Details: ', $card_id, $e->GetMessage() ) . print_r( $e->GetErrorDetails(), true ),
                'Card'
            );
            return array(
                'success' => false,
                'message' => $e->GetMessage(),
                'details' => $e->GetErrorDetails(),
            );
        } catch( MangoException $e ) {
            Helper::log(
                sprintf( 'Could not deactivate card: %s. Message: %s.', $card_id, $e->GetMessage() ),
                'Card'
            );
            return array(
                'success' => false,
                'message' => $e->GetMessage(),
            );
        }
    }

    /**
     * Saves metadata for card payment
     *
     * @since 3.5.0
     *
     * @param \WC_Order $order
     * @param array $payment_data
     *
     * @return void
     */
    public static function save_metadata( $order, $payment_data ) {
        static::update_metadata( $order, $payment_data, 'card' );
    }
}

<?php
use Twilio\Rest\Client;
/**
 * SMS Gateway handler class
 *
 * @author weDevs
 */
class WeDevs_Dokan_SMS_Gateways {

    // @codingStandardsIgnoreLine
    private static $_instance;

    /**
     * Gateway slug
     *
     * @param string $provider name of the gateway
     */
    public function __construct() {
        add_filter( 'wedevs_sms_via_smsglobal', [ $this, 'smsGlobalAPI' ] );
        add_filter( 'wedevs_sms_via_clickatell', [ $this, 'clickatellAPI' ] );
        add_filter( 'wedevs_sms_via_twilio', [ $this, 'twilio_api' ] );
        add_filter( 'wedevs_sms_via_nexmo', [ $this, 'nexmo_api' ] );
    }

    public static function instance() {
        if ( ! self::$_instance ) {
            self::$_instance = new WeDevs_dokan_SMS_Gateways();
        }

        return self::$_instance;
    }

    /**
     * Get all sms gateways
     *
     * @return array
     */
    public function get_gateways() {
        $gateways = [
            'nexmo'  => [ 'label' => 'Vonage' ],
            'twilio' => [ 'label' => 'Twilio' ],
        ];

        return apply_filters( 'wedevs_dokan_sms_gateways', $gateways );
    }

    /**
     * Set last sms sent time
     */
    public function set_last_sent() {
        $last_sent = current_time( 'mysql' );

        if ( is_user_logged_in() ) {
            update_user_meta( get_current_user_id(), 'sms_last_sent', $last_sent );
        } else {
            setcookie( 'sms_last_sent', $last_sent, time() + 86400, '/' );
        }
    }

    /**
     * Sends SMS to the given number using the active gateway
     *
     * Set transient for the sent SMS code to verify later, by default it will be valid for 5 minutes.
     * Context can be used to differentiate between different verification contexts (e.g., vendor-verification, registration, password reset, etc.).
     *
     * @param string $to
     * @param string $context
     * @param int $tle TLE in minutes
     *
     * @return array|mixed
     * @throws mixed
     */
    public function send( string $to, string $context = '', int $tle = 5 ) {
        $active_gateway = dokan_get_option( 'active_gateway', 'dokan_verification_sms_gateways' );

        if ( empty( $active_gateway ) ) {
            $response = [
                'success' => false,
                'message' => 'No active gateway found',
            ];

            return $response;
        }

        $twilio_code_type = dokan_get_option( 'twilio_code_type', 'dokan_verification_sms_gateways' );
        $code = wp_rand( 1000, 9999 );
        if ( 'numeric' === $twilio_code_type ) {
            $code = wp_rand( 1000, 9999 );
        } elseif ( 'alphanumeric' === $twilio_code_type ) {
            $code = bin2hex( random_bytes( 3 ) );
        }
        $sms_text = dokan_get_option( 'sms_text', 'dokan_verification_sms_gateways' );
        $sms_text = str_replace( '%CODE%', $code, $sms_text );
        $sms_data = [
            'text' => $sms_text,
            'to'   => $to,
            'code' => $code,
        ];

        $status = apply_filters( 'wedevs_sms_via_' . $active_gateway, $sms_data );

        //set last sms sent time
        // @codingStandardsIgnoreLine
        if ( $status['success'] == true ) {
            $this->set_last_sent( $status );
        } elseif ( $status['success'] === false ) {
            dokan_log( sprintf( 'Vendor Verification: Failed to send SMS. Error: %s', print_r( $status, 1 ) ) );
        }

        if ( ! isset( $status['success'] ) ) {
            $response = [
                'success' => false,
                'message' => 'Gateway Not found!!!',
            ];

            return $response;
        }
        $code = $status['code'];
        set_transient( "dokan_sms_verification_{$to}_{$code}_$context", $status['code'], $tle * MINUTE_IN_SECONDS );

        return $status;
    }

    /**
     * Verify the SMS code sent to the user
     *
     * This method checks the transient for the saved code and compares it with the provided code.
     *
     * @param string $to Phone number to which the SMS was sent
     * @param string $code The code to verify which was sent via SMS and saved in transient
     * @param string $context Context for the verification, e.g., vendor-verification, registration, password reset, etc.
     *
     * @return bool
     */
    public function verify( string $to, string $code, string $context = '' ): bool {
        $transient_key = "dokan_sms_verification_{$to}_{$code}_$context";
        $saved_code    = get_transient( $transient_key );
        if ( ! $saved_code ) {
            // If the transient is not found, return false
            return false;
        }

        delete_transient( $transient_key );
        return true;
    }

    /**
     * Sends SMS via Twillo api
     *
     * @uses `wedevs_sms_via_twilio` filter to fire
     *
     * @param type $sms_data
     *
     * @return bool
     */
    public function twilio_api( $sms_data ) {
        $response = [
            'success' => false,
            'message' => dokan_get_option( 'sms_sent_error', 'dokan_verification_sms_gateways' ),
        ];

        $sid   = dokan_get_option( 'twilio_username', 'dokan_verification_sms_gateways' );
        $token = dokan_get_option( 'twilio_pass', 'dokan_verification_sms_gateways' );
        $from  = dokan_get_option( 'twilio_number', 'dokan_verification_sms_gateways' );

        $twilio_enable_status = dokan_get_option( 'twilio_enable_status', 'dokan_verification_sms_gateways', 'on' );
        if ( 'off' === $twilio_enable_status ) {
            return $response;
        }

        $client = new Client( $sid, $token );

        try {
            $message = $client->messages->create(
                '+' . $sms_data['to'],
                [
                    'from' => $from,
                    'body' => $sms_data['text'],
                ]
            );

            if ( 'failed' !== (string) $message->status ) {
                $response = [
                    'success' => true,
                    'code'    => $sms_data['code'],
                    'message' => dokan_get_option( 'sms_sent_msg', 'dokan_verification_sms_gateways' ),
                ];
            }
        } catch ( Exception $exc ) {
            $error_code = (int) $exc->getCode();
            $response['message']   = $this->handle_twilio_errors( $error_code );
        }

        return $response;
    }

    /**
     * Sends SMS via Nexmo api
     *
     * @uses `wedevs_sms_via_nexmo` filter to fire
     *
     * @param type $sms_data
     *
     * @return bool
     */
    public function nexmo_api( $sms_data ) {
        $response = [
            'success' => false,
            'message' => dokan_get_option( 'sms_sent_error', 'dokan_verification_sms_gateways' ),
        ];

        $sms_data['number']   = $sms_data['to'];
        $sms_data['sms_body'] = $sms_data['text'];

        $username = dokan_get_option( 'nexmo_username', 'dokan_verification_sms_gateways' );
        $password = dokan_get_option( 'nexmo_pass', 'dokan_verification_sms_gateways' );
        $from     = dokan_get_option( 'sender_name', 'dokan_verification_sms_gateways' );

        $nexmo_enable_status = dokan_get_option( 'nexmo_enable_status', 'dokan_verification_sms_gateways', 'on' );
        if ( 'off' === $nexmo_enable_status ) {
            return $response;
        }

        $api_key    = $username;
        $api_secret = $password;

        require_once __DIR__ . '/lib/NexmoMessage.php';

        $nexmo_sms = new NexmoMessage( $api_key, $api_secret );
        $info      = $nexmo_sms->sendText( $sms_data['number'], $from, $sms_data['sms_body'] );

        if ( (string) $info->messages[0]->status === '0' ) {
            $response = [
                'success' => true,
                'code'    => $sms_data['code'],
                'message' => dokan_get_option( 'sms_sent_msg', 'dokan_verification_sms_gateways' ),
            ];
        }

        return $response;
    }

    /**
     * Handles Twilio error codes and returns translatable text
     *
     * @param $error_code
     *
     * @return string|void
     */
    public function handle_twilio_errors( $error_code ) {
        $response = dokan_get_option( 'sms_sent_error', 'dokan_verification_sms_gateways' );

        switch ( $error_code ) {
            case 21211:
                $response = __( 'Invalid phone number.', 'dokan' );
                break;

            case 21610:
                $response = __( 'This number is blocked for your account.', 'dokan' );
                break;

            case 21612:
                $response = __( 'Twilio cannot route to this number.', 'dokan' );
                break;

            case 21614:
                $response = __( 'This number is incapable of receiving SMS messages.', 'dokan' );
                break;
        }

        return $response;
    }
}

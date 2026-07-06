<?php

namespace WPDataAccess\Utilities {

    use WPDataAccess\WPDA;

    class WPDA_Mail {

        const WPDA_MAIL_SERVER_OPTION = 'wpda_mail_server';
        const CIPHER = 'AES-256-CBC';
        const KEY = '886BF60F78C0EEACA0DC73D9C8E9A964CE7CFCD9DD459DE76F985030ADE0423A';

        public static function send(
            $to,
            $subject,
            $message,
            $attachments = array()
        ) {

            ob_start();

            do_action( 'wpda_sending_mail' );
            $result = wp_mail(
                $to,
                $subject,
                $message,
                array(
                    'Content-Type: text/html; charset=UTF-8',
                ),
                $attachments
            );

            $output = ob_get_clean();

            if ( $result ) {
                return array(
                    'code'    => 'ok',
                    'debug'   => $output,
                    'message' => 'Successfully processed request',
                );
            } else {
                return array(
                    'code'    => 'error',
                    'debug'   => $output,
                    'message' => 'Failed to process request',
                );
            }

        }

        public static function mail_activated() {

            $mail = self::get_option();
            if (
                false !== $mail &&
                (
                    ! isset( $mail['activate'] ) ||
                    'on' === $mail['activate']
                )
            ) {
                return true;
            }

            return false;

        }

        public static function get_option() {

            $option = get_option(
                self::WPDA_MAIL_SERVER_OPTION
            );

            if ( false !== $option ) {
                return json_decode( self::decrypt( $option ), true ); // Decrypt option
            } else {
                return false;
            }

        }

        public static function update_option( $option ) {

            return update_option(
                self::WPDA_MAIL_SERVER_OPTION,
                self::encrypt( json_encode( $option ) ) // Encrypt option
            );

        }

        public static function delete_option() {

            return delete_option(
                self::WPDA_MAIL_SERVER_OPTION
            );

        }

        private static function encrypt( $string ) {

            $ivlen = openssl_cipher_iv_length( self::CIPHER );
            $iv    = openssl_random_pseudo_bytes( $ivlen );

            $ciphertext_raw = openssl_encrypt( $string, self::CIPHER, self::KEY, OPENSSL_RAW_DATA, $iv );
            $hmac           = hash_hmac('sha256', $ciphertext_raw, self::KEY, true );

            return base64_encode($iv . $hmac . $ciphertext_raw);

        }

        private static function decrypt( $string ) {

            $c     = base64_decode( $string );
            $ivlen = openssl_cipher_iv_length( self::CIPHER );
            $iv    = substr( $c, 0, $ivlen );
            $hmac  = substr( $c, $ivlen, 32 );

            $ciphertext_raw  = substr( $c, $ivlen + 32 );
            $calculated_hmac = hash_hmac('sha256', $ciphertext_raw, self::KEY, true);

            if ( ! hash_equals($hmac, $calculated_hmac) ) {
                return false;
            }

            return openssl_decrypt( $ciphertext_raw, self::CIPHER, self::KEY, OPENSSL_RAW_DATA, $iv );

        }

    }

}


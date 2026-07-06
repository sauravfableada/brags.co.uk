<?php

namespace WPDataAccess\API;

use WPDataAccess\Connection\WPDADB;
use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Lists;
use WPDataAccess\WPDA;
class WPDA_AI extends WPDA_API_Core {
    const CIPHER = 'AES-256-CBC';

    const AI_API_KEY = 'wpda_ai_key';

    const SUPPORTED_MODELS = ['gpt-3.5-turbo', 'gpt-4-turbo'];

    public function register_rest_routes() {
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'ai/sql', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'ai_sql'),
            'permission_callback' => function () {
                return $this->current_user_can_access();
            },
            'args'                => array(
                'prompt'  => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => __( 'Prompt', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'model'   => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => __( 'Model', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ( $param ) {
                        return in_array( $param, self::SUPPORTED_MODELS );
                    },
                ),
                'explain' => array(
                    'required'          => true,
                    'type'              => 'boolean',
                    'description'       => __( 'Add explanations', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'ai/hints', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'hints'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'ai/enabled', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'enabled'),
            'permission_callback' => '__return_true',
            'args'                => array(),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'ai/enable', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'enable'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'key'     => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => __( 'API Key', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'encrypt' => array(
                    'required'          => true,
                    'type'              => 'boolean',
                    'description'       => __( 'Encrypt API key', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
            ),
        ) );
    }

    public function ai_sql( $request ) {
        $timeout = 30;
        $prompt = $request['prompt'];
        $model = $request['model'];
        $explain = ( $request['explain'] ? 'Write the SQL query first in a code block. After the code block, provide a clear, concise explanation of what the query does.' : 'Provide only the query without further explanation.' );
        $prompt = "\nYou are a professional MySQL consultant helping developers write SQL queries.\nAlways respond with clean, optimized MySQL code.\nPlace the query inside a single Markdown code block using triple backticks (```sql).\nDo not include a semicolon at the end of the query.\nImportant: Do not include a LIMIT clause unless the user specifically requests limiting the number of results.\nAssume the system will handle limits automatically if needed.\n{$explain}\nExample input for a user:\nWrite a join between tables dept and emp and show the average and total salaries per department.\nExpected Output:\n```sql\nSELECT d.dname AS department_name, \n       AVG(e.sal) AS average_salary, \n       SUM(e.sal) AS total_salary\nFROM dept d\nJOIN emp e ON d.deptno = e.deptno\nGROUP BY d.dname\n```\n{$prompt}\n";
        WPDA::wpda_log_wp_error( $prompt );
        $api_key_saved = get_user_meta( get_current_user_id(), self::AI_API_KEY, true );
        if ( false === $api_key_saved || '' === $api_key_saved ) {
            $api_key = '';
        } else {
            $api_key = substr( $api_key_saved, 0, -2 );
            $is_encrypted = substr( $api_key_saved, -1 );
            if ( '1' === $is_encrypted ) {
                // Decrypt API key
                $api_key = $this->decrypt( $api_key );
            }
        }
        if ( '' === trim( $api_key ) ) {
            return new \WP_Error('error', 'Invalid or missing API Key', array(
                'status' => 403,
            ));
        }
        return $this->ask_ai(
            $api_key,
            $model,
            $prompt,
            $timeout
        );
    }

    private function ask_ai(
        $api_key,
        $model,
        $prompt,
        $timeout,
        $msg = ''
    ) {
        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => json_encode( array(
                'model'    => $model,
                'messages' => array(array(
                    'role'    => 'user',
                    'content' => $prompt,
                )),
            ) ),
            'timeout' => $timeout,
        ) );
        if ( !is_wp_error( $response ) ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( '' !== $msg ) {
                $body['msg'] = $msg;
            }
            return rest_ensure_response( $body );
        }
        if ( self::SUPPORTED_MODELS[1] === $model ) {
            // Try 'gpt-3.5-turbo' if 'gpt-4-turbo' failed
            return $this->ask_ai(
                $api_key,
                self::SUPPORTED_MODELS[0],
                $prompt,
                $timeout,
                'Note: This result was generated using gpt-3.5-turbo due to a timeout using gpt-4-turbo.'
            );
        }
        return new \WP_Error('error', $response->get_error_message(), array(
            'status' => 403,
        ));
    }

    public function hints( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $dbs = $request->get_param( 'dbs' );
        $tables = WPDA_Dictionary_Lists::get_tables( true, $dbs );
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( null === $wpdadb ) {
            // Error connecting.
            return new \WP_Error('error', "Error connecting to database {$dbs}", array(
                'status' => 420,
            ));
        }
        $hints = array();
        foreach ( $tables as $table ) {
            $table_name = WPDA::remove_backticks( $table['table_name'] );
            $sql_cmd = $wpdadb->get_results( "SHOW CREATE TABLE `{$table_name}`", 'ARRAY_N' );
            if ( '' === $wpdadb->last_error && isset( $sql_cmd[0][1] ) ) {
                $hints[$table_name] = $sql_cmd[0][1];
            }
        }
        return $this->WPDA_Rest_Response( '', $hints );
    }

    public function enabled( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $api_key = get_user_meta( WPDA::get_current_user_id(), self::AI_API_KEY, true );
        $is_enabled = false !== $api_key && '' !== $api_key;
        return $this->WPDA_Rest_Response( '', array(
            'enabled'    => $is_enabled,
            'encryption' => $this->get_encryption_key() !== null,
        ) );
    }

    public function enable( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $key = $request['key'];
        $encrypt = ( $request['encrypt'] ? 1 : 0 );
        if ( $encrypt ) {
            $key = $this->encrypt( $key );
        }
        update_user_meta( WPDA::get_current_user_id(), self::AI_API_KEY, "{$key}|{$encrypt}" );
        return $this->WPDA_Rest_Response( '' );
    }

    private function encrypt( $string ) {
        $key = $this->get_encryption_key();
        if ( null === $key ) {
            return $string;
        }
        $ivlen = openssl_cipher_iv_length( self::CIPHER );
        $iv = openssl_random_pseudo_bytes( $ivlen );
        $ciphertext_raw = openssl_encrypt(
            $string,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        $hmac = hash_hmac(
            'sha256',
            $ciphertext_raw,
            $key,
            true
        );
        return base64_encode( $iv . $hmac . $ciphertext_raw );
    }

    private function decrypt( $string ) {
        $key = $this->get_encryption_key();
        if ( null === $key ) {
            return $string;
        }
        $c = base64_decode( $string );
        $ivlen = openssl_cipher_iv_length( self::CIPHER );
        $iv = substr( $c, 0, $ivlen );
        $hmac = substr( $c, $ivlen, 32 );
        $ciphertext_raw = substr( $c, $ivlen + 32 );
        $calculated_hmac = hash_hmac(
            'sha256',
            $ciphertext_raw,
            $key,
            true
        );
        if ( !hash_equals( $hmac, $calculated_hmac ) ) {
            return false;
        }
        return openssl_decrypt(
            $ciphertext_raw,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    private function get_encryption_key() {
        if ( defined( 'WPDA_ENCRYPT_AI_KEY' ) && '' !== trim( constant( 'WPDA_ENCRYPT_AI_KEY' ) ) ) {
            return constant( 'WPDA_ENCRYPT_AI_KEY' );
        } else {
            return null;
        }
    }

}

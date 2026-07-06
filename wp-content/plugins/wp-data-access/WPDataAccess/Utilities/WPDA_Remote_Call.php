<?php

namespace WPDataAccess\Utilities {

	use WPDataAccess\WPDA;

	class WPDA_Remote_Call {

		public static function post(
            $url,
            $body,
            $die = false,
            $headers = array(),
            $sslverify = true
        ) {
			$response = wp_remote_post(
				$url,
				array(
					'headers'   => $headers,
					'body'      => $body,
					'timeout'   => 60,
                    'sslverify' => $sslverify,
				)
			);

            if ( 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_DEBUG ) ) {
                // WPDA::wpda_log_wp_error( $response );
            }

			if ( is_wp_error( $response ) ) {
				WPDA::wpda_log_wp_error( json_encode( $response ) );
				if ( $die ) {
					wp_die( 'ERROR: Remote call failed [' . json_encode( $response ) . ']' );
				}

				return $response->get_error_message();
			}

			if ( ! isset( $response['response'], $response['body'] ) ) {
				WPDA::wpda_log_wp_error( json_encode( $response ) );
				if ( $die ) {
					wp_die( 'ERROR: Remote call failed [missing response|body]' );
				}

				return false;
			}

			return $response;
		}

		public static function get( $url, $args = array(), $die = false ) {
			$response = wp_remote_get( $url, $args );

            if ( 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_DEBUG ) ) {
                // WPDA::wpda_log_wp_error( $response );
            }

            if ( is_wp_error( $response ) ) {
				WPDA::wpda_log_wp_error( json_encode( $response ) );
				if ( $die ) {
					wp_die( 'ERROR: Remote call failed [' . json_encode( $response ) . ']' );
				}

				return false;
			}

			if ( ! isset( $response['response'], $response['body'] ) ) {
				WPDA::wpda_log_wp_error( json_encode( $response ) );
				if ( $die ) {
					wp_die( 'ERROR: Remote call failed [missing response|body]' );
				}

				return false;
			}

			return $response;
		}

        public static function delete( $url, $args = array(), $headers = array(), $die = false ) {
            $response = wp_remote_request(
                $url,
                array(
                    'method'  => 'DELETE',
                    'headers' => $headers,
                )
            );

            if ( 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_DEBUG ) ) {
                // WPDA::wpda_log_wp_error( $response );
            }

            if ( is_wp_error( $response ) ) {
                WPDA::wpda_log_wp_error( json_encode( $response ) );
                if ( $die ) {
                    wp_die( 'ERROR: Remote DELETE failed [' . json_encode( $response ) . ']' );
                }

                return false;
            }

            $status_code = wp_remote_retrieve_response_code( $response );
            if ( $status_code === 204 ) {
                return true;
            }

            WPDA::wpda_log_wp_error("ERROR: Delete failed: HTTP $status_code");
            return false;
        }

        public static function max_size() {
			$max  = ini_get('post_max_size');
			$unit = $max[ strlen( $max ) - 1 ];
			$max  = substr( $max, 0, strlen( $max ) - 1 );

			switch($unit) {
				case 'G':
					$max *= 1024;
				case 'M':
					$max *= 1024;
				case 'K':
					$max *= 1024;
			}

			return $max;
		}

	}

}
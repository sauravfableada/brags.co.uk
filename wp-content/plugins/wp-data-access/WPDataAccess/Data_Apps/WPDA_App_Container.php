<?php

namespace WPDataAccess\Data_Apps {

    use WPDataAccess\API\WPDA_API_Core;
    use WPDataAccess\API\WPDA_Apps;
    use WPDataAccess\Plugin_Table_Models\WPDA_App_Apps_Model;
    use WPDataAccess\Plugin_Table_Models\WPDA_App_Model;
	use WPDataAccess\WPDA;

	class WPDA_App_Container extends WPDA_Container {

		private $app_id = '';

		public function __construct(
                $args = array(),
                $shortcode_args = array()
        ) {

			parent::__construct( $args );

			if ( isset( $args['app_id'] ) ) {
				$this->app_id = $args['app_id'];
			}

			if (
				isset( $args['builders'] ) &&
				(
					false === $args['builders'] ||
					'false' === $args['builders']
				)
			) {
				$this->builders = false;
			}

            $this->shortcode_args = $shortcode_args;

		}

        private function get_app_metadata( $app_id ) {

            $app      = new WPDA_Apps();
            $response = $app->get_app_meta( $app_id );

            if (
                isset( $response->data['code'], $response->data['data'] ) &&
                'ok' === $response->data['code']
            ) {
                return $response->data['data'];
            }

            return null;

        }

		public function show() {

			$app = WPDA_App_Model::get_by_id( $this->app_id );
			if ( false === $app ) {
				if ( ! $this->send_feedback() ) {
					return;
				}

				$this->show_feedback( __( 'Not authorized', 'wp-data-access' ) );
				return;
			}

			if ( ! $this->user_can_access( $app ) ) {
				if ( ! $this->send_feedback() ) {
					return;
				}

				$this->show_feedback( __( 'Not authorized', 'wp-data-access' ) );
				return;
			}

            $metadata = array();
            $metadata_master = $this->get_app_metadata( $this->app_id);

            if ( null !== $metadata_master) {
                $metadata[$this->app_id] = $metadata_master;
            }

            if ( isset( $app[0]['app_type'] ) && '5' == $app[0]['app_type'] ) {
                // Add metadata children
                $detail_apps = WPDA_App_Apps_Model::select_all( $this->app_id );
                foreach ( $detail_apps as $detail_app ) {
                    if ( isset( $detail_app['app_id_detail'] ) ) {
                        $app_id_detail = $detail_app['app_id_detail'];
                        $metadata_detail = $this->get_app_metadata($app_id_detail);
                        if (null !== $metadata_detail) {
                            $metadata[$app_id_detail] = $metadata_detail;
                        }
                    }
                }
            }

            $app_type_class = '';
            switch ($app[0]['app_type']) {
                case '2':
                    // Map
                    $app_type_class = 'pp-container-map';
                    break;
                case '3':
                    // Registration form
                    $app_type_class = 'pp-container-registration';
                    break;
                case '5':
                    // Data App
                    $app_type_class = 'pp-container-apps';
                    break;
                case '6':
                    // Chart
                    $app_type_class = 'pp-container-chart';
                    break;
                case '7':
                    // Chart
                    $app_type_class = 'pp-container-dashboard';
                    break;
            }
			?>

			<div class="wpda-pp-container">
				<div
					class="pp-container-app <?php echo esc_attr( $app_type_class ); ?>"
					data-source="{ 'id': '<?php echo esc_attr( $this->app_id ); ?>' }"

					<?php
					if ( null !== $this->filter_field_name && null !== $this->filter_field_value ) {
						?>
						data-filter_field_name="<?php echo esc_attr( $this->filter_field_name ); ?>"
						data-filter_field_value="<?php echo esc_attr( $this->filter_field_value ); ?>"
						<?php
					}

                    if ( 0 < count( $this->shortcode_args ) ) {
                        $field_names  = array();
                        $field_values = array();
                        foreach ( $this->shortcode_args as $key => $value ) {
                            // Sanitize field name
                            $field_names[] = WPDA_API_Core::sanitize_db_identifier( $key );
                            // Sanitize field value
                            $sanitized_value = sanitize_text_field( $value );
                            $sanitized_value = wp_strip_all_tags( $sanitized_value );
                            $sanitized_value = str_replace( array( '"', "'", '`' ), '', $sanitized_value );
                            $sanitized_value = str_replace( array( '<', '>' ), '', $sanitized_value );
                            $sanitized_value = str_replace( '=', '', $sanitized_value );
                            $field_values[] = $sanitized_value;
                        }
                        ?>
                        data-shortcode_field_name="<?php echo esc_attr( implode( ',', $field_names) ); ?>"
                        data-shortcode_field_value="<?php echo esc_attr( implode( ',', $field_values ) ); ?>"
                        <?php
                    }
					?>
				></div>
			</div>

            <?php
            if ( 0 < count( $metadata ) ) {
                // Add metadata detail apps to decrease load time
                ?>
                <script>
                    <?php
                        foreach ( $metadata as $app_id => $data ) {
                            ?>
                            window.pp_app_<?php echo esc_attr( $app_id ); ?> = {
                                metadata: <?php echo json_encode( $data, true ); ?>
                            }
                            <?php
                        }
                    ?>
                </script>
                <?php
                }
            ?>

			<?php

			$this->add_client('app', $this->app_id );

		}

		private function user_can_access( $app ) {

			if ( ! isset ( $app[0]['app_settings'] ) ) {
				return false;
			}

			// Check access
			$app_settings_db = $app[0]['app_settings'];
			$app_settings    = json_decode( (string) $app_settings_db, true );
			if (
				! isset(
					$app_settings['rest_api']['authorization'],
					$app_settings['rest_api']['authorized_roles'],
					$app_settings['rest_api']['authorized_users']
				) ||
				! is_array( $app_settings['rest_api']['authorized_roles'] ) ||
				! is_array( $app_settings['rest_api']['authorized_users'] )
			) {
				// App contain no rest api settings
				return false;
			}

			if (
				! WPDA::current_user_is_admin() &&
				'anonymous' !== $app_settings['rest_api']['authorization']
			) {
				// Check authorization
				// Check user role
				$user_roles = WPDA::get_current_user_roles();
				if (
					! is_array( $user_roles ) ||
					empty(
						array_intersect(
							$app_settings['rest_api']['authorized_roles'],
							$user_roles
						)
					)
				) {
					// Check user login
					$user_login = WPDA::get_current_user_login();
					if ( ! in_array( $user_login, $app_settings['rest_api']['authorized_users'] ) ) {
						return false;
					}
				}
			}

			// Anonymous access
			return true;

		}

	}

}
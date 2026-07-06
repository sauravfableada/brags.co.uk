<?php

namespace WPDataAccess\API {

	use WPDataAccess\Plugin_Table_Models\WPDA_Media_Model;
	use WPDataAccess\Plugin_Table_Models\WPDA_Table_Settings_Model;
	use WPDataAccess\Plugin_Table_Models\WPDA_User_Menus_Model;
	use WPDataAccess\WPDA;

	class WPDA_Settings extends WPDA_API_Core {

		const WPDA_SETTINGS = 'wpda_settings';

		public function register_rest_routes() {

			register_rest_route(
				WPDA_API::WPDA_NAMESPACE,
				'save-settings',
				array(
					'methods'             => array( 'POST' ),
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'action'   => array(
							'required'          => true,
							'type'              => 'string',
							'description'       => __( 'Setting type', 'wp-data-access' ),
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => function ( $param ) {
								return in_array(
									$param,
									array(
										'dashboard_menus',
										'table_settings',
										'column_settings',
										'rest_api',
										'admin_settings',
										'explorer_settings'
									)
								);
							},
						),
						'dbs'      => $this->get_param( 'dbs' ),
						'tbl'      => $this->get_param( 'tbl' ),
						'settings' => array(
							'required'          => true,
							'type'              => 'string',
							'description'       => __( 'Settings JSON as string', 'wp-data-access' ),
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				)
			);

		}

		/**
		 * Save plugin settings.
		 *
		 * @param WP_REST_Request $request Rest API request.
		 * @return \WP_Error|\WP_REST_Response
		 */
		public function save_settings( $request ) {

			if ( ! $this->current_user_can_access() ) {
				return $this->unauthorized();
			}

			if ( ! $this->current_user_token_valid( $request ) ) {
				return $this->invalid_nonce();
			}

			$dbs = $request->get_param( 'dbs' );
			$tbl = $request->get_param( 'tbl' );

			$settings_string = $request->get_param( 'settings' );
			if ( ! is_string( $settings_string ) ) {
				return $this->bad_request();
			}

			$settings = json_decode( $settings_string, true );
			if ( false === $settings || is_null( $settings ) ) {
				return $this->bad_request();
			}

			switch ( $request->get_param( 'action' ) ) {
				case 'dashboard_menus':
					return $this->save_dashboard_menus( $dbs, $tbl, $settings );
				case 'table_settings':
					return $this->save_table_settings( $dbs, $tbl, $settings );
				case 'column_settings':
					return $this->save_column_settings( $dbs, $tbl, $settings );
				case 'rest_api':
					return $this->save_rest_api_settings( $dbs, $tbl, $settings );
				case 'admin_settings':
					return $this->save_admin_settings( $dbs, $tbl, $settings );
				case 'explorer_settings':
					return $this->save_explorer_settings( $dbs, $tbl, $settings );
			}

			return $this->bad_request();

		}

		private function save_explorer_settings( $schema_name, $table_name, $settings ) {

			global $wpdb;
			$dml_failed = 0;

			// Handle REST API
			$rest_api = $settings['rest_api'] ?? null;
			$rest_api_settings = get_option(  WPDA_API::WPDA_REST_API_TABLE_ACCESS );
			if ( false === $rest_api_settings ) {
				$rest_api_settings = array();
			}
			if ( null !== $rest_api ) {
				// Add table REST API
				$rest_api_settings[ $schema_name ][ $table_name ] = $rest_api;
			} else {
				// Delete table REST API
				unset($rest_api_settings[ $schema_name ][ $table_name ]);
			}
			update_option( WPDA_API::WPDA_REST_API_TABLE_ACCESS, $rest_api_settings );
			unset( $settings['rest_api'] );

			// Handle Media columns
			$media_columns = $settings['column_media'] ?? null;
			if ( is_array( $media_columns ) ) {
				foreach ( $media_columns as $column => $value ) {
					if ( false === $value || '' === $value ) {
						// Delete column media (no error handling)
						WPDA_Media_Model::delete( $table_name, $column, $schema_name );
					} elseif ( 'string' === gettype( $value) ) {
						if ( false === WPDA_Media_Model::get_column_media( $table_name, $column, $schema_name ) ) {
							if ( ! WPDA_Media_Model::insert( $table_name, $column, $value, 'Yes', $schema_name ) ) {
								$dml_failed++;
							}
						} else {
							if ( false === WPDA_Media_Model::update( $table_name, $column, $value, $schema_name ) ) {
								$dml_failed++;
							}
						}
					}
				}
			}
			unset( $settings['column_media'] );

			// Handle UPDATE | INSERT table and column settings
			$settings_db = WPDA_Table_Settings_Model::query( $table_name, $schema_name );
			if ( isset( $settings_db[0]['wpda_table_settings'] ) ) {
				// Row found, perform update
				$settings_from_db = json_decode( $settings_db[0]['wpda_table_settings'], true );
				foreach ( $settings_from_db as $key => $value ) {
					if ( ! isset( $settings[ $key ] ) ) {
						$settings[ $key ] = $value;
					}
				}

				WPDA_Table_Settings_Model::update( $table_name, json_encode( $settings ), $schema_name );
				if ( '' !== $wpdb->last_error ) {
					$dml_failed++;
				}
			} else {
				// No row found, insert new
				if ( ! WPDA_Table_Settings_Model::insert( $table_name, json_encode( $settings ), $schema_name ) ) {
					$dml_failed++;
				}
			}

			if ( 0 === $dml_failed ) {
				return $this->WPDA_Rest_Response( __( 'Changes successfully saved', 'wp-data-access' ) );
			} else {
				if ( '' === $wpdb->last_error ) {
					return new \WP_Error(
						sprintf( __( 'Failed to save changes [%s]', 'wp-data-access' ), $wpdb->last_error ),
						array( 'status' => 420 )
					);
				} else {
					return new \WP_Error(
						sprintf( __( 'Failed to save changes [%s]', 'wp-data-access' ), $wpdb->last_error ),
						array( 'status' => 420 )
					);
				}
			}

		}

		/**
		 * Save table settings.
		 *
		 * @param $schema_name
		 * @param $table_name
		 * @param $settings
		 * @return \WP_Error|\WP_REST_Response
		 */
		private function save_table_settings( $schema_name, $table_name, $settings ) {
			if (
				isset( $settings['table_settings']['hyperlink_definition'] ) &&
				isset( $settings['unused'] )
			) {
				$sql_dml = $settings['unused'];

				unset( $settings['request_type'] );
				unset( $settings['unused'] );

				global $wpdb;
				if ( 'UPDATE' === $sql_dml ) {
					$settings_db = WPDA_Table_Settings_Model::query( $table_name, $schema_name );
					if ( isset( $settings_db[0]['wpda_table_settings'] ) ) {
						$settings_from_db = json_decode( $settings_db[0]['wpda_table_settings'], true );
						foreach ( $settings_from_db as $key => $value ) {
							if ( ! isset( $settings[ $key ] ) ) {
								$settings[ $key ] = $value;
							}
						}
					}

					if (
						1 === WPDA_Table_Settings_Model::update( $table_name, json_encode( $settings ), $schema_name ) ||
						(
							'' === $wpdb->last_error &&
							0 === WPDA_Table_Settings_Model::update( $table_name, json_encode( $settings ), $schema_name )
						)
					) {
						return $this->WPDA_Rest_Response( __( 'Changes successfully saved', 'wp-data-access' ) );
					} else {
						return new \WP_Error(
							sprintf( __( 'Failed to save changes [%s]', 'wp-data-access' ), $wpdb->last_error ),
							array( 'status' => 420 )
						);
					}
				} else {
					if ( WPDA_Table_Settings_Model::insert( $table_name, json_encode( $settings ), $schema_name ) ) {
						return $this->WPDA_Rest_Response( __( 'Changes successfully saved', 'wp-data-access' ) );
					} else {
						return new \WP_Error(
							sprintf( __( 'Failed to save changes [%s]', 'wp-data-access' ), $wpdb->last_error ),
							array( 'status' => 420 )
						);
					}
				}
			} else {
				// Nothing really to save, just to satisfy the user experience.
				return $this->WPDA_Rest_Response( __( 'Changes successfully saved', 'wp-data-access' ) );
			}
		}

		/**
		 * Save column settings.
		 *
		 * @param $schema_name
		 * @param $table_name
		 * @param $settings
		 * @return \WP_Error|\WP_REST_Response
		 */
		private function save_column_settings( $schema_name, $table_name, $settings ) {
			$sql_dml	   = null;
			$dml_succeeded = 0;
			$dml_failed    = 0;

			if ( isset( $settings['column_media'] ) ) {
				// Process media columns.
				$settings_column_media = $settings['column_media'];
				foreach ( $settings_column_media as $column => $value ) {
					if ( isset( $value['value'] ) && isset( $value['dml'] ) ) {
						if ( 'INSERT' === $value['dml'] ) {
							if ( WPDA_Media_Model::insert( $table_name, $column, $value['value'], 'Yes', $schema_name ) ) {
								$dml_succeeded ++;
							} else {
								$dml_failed ++;
							}
						} elseif ( 'UPDATE' === $value['dml'] ) {
							if ( '' === $value['value'] ) {
								if ( 1 === WPDA_Media_Model::delete( $table_name, $column, $schema_name ) ) {
									$dml_succeeded ++;
								} else {
									$dml_failed ++;
								}
							} else {
								if ( 1 === WPDA_Media_Model::update( $table_name, $column, $value['value'], $schema_name ) ) {
									$dml_succeeded ++;
								} else {
									$dml_failed ++;
								}
							}
						}
					}
				}
				unset( $settings['column_media'] );
			}

			if ( isset( $settings['unused'] ) ) {
				$settings_unused = $settings['unused'];
				if ( isset( $settings_unused['sql_dml'] ) ) {
					$sql_dml = $settings_unused['sql_dml'];
				}
				unset( $settings['unused'] );
			}

			if ( null === $sql_dml ) {
				return new \WP_Error(
					sprintf(
						__( 'Failed to save changes [%s]', 'wp-data-access' ),
						'please contact the plugin development team'
					),
					array( 'status' => 420 )
				);
			} else {
				// Save settings.
				if ( isset( $settings['request_type'] ) ) {
					unset( $settings['request_type'] );
				}

				if ( 'UPDATE' === $sql_dml ) {
					$settings_db = WPDA_Table_Settings_Model::query( $table_name, $schema_name );
					if ( isset( $settings_db[0]['wpda_table_settings'] ) ) {
						$settings_from_db = json_decode( $settings_db[0]['wpda_table_settings'], true );
						foreach ( $settings_from_db as $key => $value ) {
							if ( ! isset( $settings[ $key ] ) ) {
								$settings[ $key ] = $value;
							}
						}
					}
					if ( 1 === WPDA_Table_Settings_Model::update( $table_name, json_encode( $settings ), $schema_name ) ) {
						$dml_succeeded ++;
					}
				} else {
					if ( WPDA_Table_Settings_Model::insert( $table_name, json_encode( $settings ), $schema_name ) ) {
						$dml_succeeded ++;
					} else {
						$dml_failed ++;
					}
				}
			}

			if ( $dml_succeeded >= 0 && $dml_failed === 0 ) {
				return $this->WPDA_Rest_Response( __( 'Changes successfully saved', 'wp-data-access' ) );
			} else {
				global $wpdb;
				$msg = '' !== $wpdb->last_error ? " [{$wpdb->last_error}]" : '';

				return new \WP_Error(
					sprintf( __( 'Failed to save changes [%s]', 'wp-data-access' ), $msg ),
					array( 'status' => 420 )
				);
			}
		}

		/**
		 * Save dashboard menu changes.
		 *
		 * @param $schema_name
		 * @param $table_name
		 * @param $settings
		 * @return \WP_Error|\WP_REST_Response
		 */
		private function save_dashboard_menus( $schema_name, $table_name, $settings ) {
			$dml_succeeded = 0;
			$dml_failed    = 0;
			$new_menus     = array();

			if ( isset( $settings['menu'] ) && is_array( $settings['menu'] ) ) {
				// Process menu items.
				foreach ( $settings['menu'] as $menu ) {
					if ( isset( $menu['menu_name'] ) && isset( $menu['menu_slug'] ) ) {
						if ( isset( $menu['menu_role'] ) ) {
							if ( is_array( $menu['menu_role'] ) ) {
								$menu_role = implode( ',', $menu['menu_role'] );
							} else {
								$menu_role = $menu['menu_role'];
							}
						} else {
							$menu_role = '';
						}
						if ( isset( $menu['menu_id'] ) && '' === $menu['menu_id'] ) {
							// Add new menu
							if ( WPDA_User_Menus_Model::insert(
								$table_name,
								$menu['menu_name'],
								$menu['menu_slug'],
								$menu_role,
								$schema_name
							) ) {
								$dml_succeeded ++;

								global $wpdb;
								$new_menus[] = array(
									'menu_name' => $menu['menu_name'],
									'menu_slug' => $menu['menu_slug'],
									'menu_id'   => $wpdb->insert_id,
								);
							} else {
								$dml_failed ++;
							}
						} else {
							if ( isset( $menu['menu_id'] ) ) {
								// Update existing menu.
								$update_result = WPDA_User_Menus_Model::update(
									$menu['menu_id'],
									$table_name,
									$menu['menu_name'],
									$menu['menu_slug'],
									$menu_role,
									$schema_name
								);
								if ( 1 === $update_result ) {
									$dml_succeeded ++;
								}
							}
						}
					}
				}
				unset( $settings['menu'] );
			}

			if ( isset( $settings['delete'] ) && is_array( $settings['delete'] ) ) {
				// Process menu items to be deleted.
				foreach ( $settings['delete'] as $menu ) {
					if ( 1 === WPDA_User_Menus_Model::delete( $menu ) ) {
						$dml_succeeded++;
					} else {
						$dml_failed++;
					}
				}
				unset( $settings['delete'] );
			}

			if ( $dml_succeeded >= 0 && $dml_failed === 0 ) {
				return $this->WPDA_Rest_Response(
					sprintf( __( 'Saved dashboard menus for table `%s`', 'wp-data-access' ), $table_name ),
					$new_menus
				);
			} else {
				global $wpdb;
				$msg = '' !== $wpdb->last_error ? " [{$wpdb->last_error}]" : '';

				return new \WP_Error(
					sprintf( __( 'Cannot save dashboard menus for table `%s`%s', 'wp-data-access' ), $table_name, $msg ),
					array( 'status' => 420 )
				);
			}
		}

		/**
		 * Save rest api settings.
		 *
		 * @param $schema_name
		 * @param $table_name
		 * @param $settings
		 * @return \WP_Error|\WP_REST_Response
		 */
		private function save_rest_api_settings( $schema_name, $table_name, $settings ) {
			if ( ! isset( $settings['enabled'] ) ) {
				return $this->bad_request();
			}

			$rest_api_settings = get_option(  WPDA_API::WPDA_REST_API_TABLE_ACCESS );
			if ( false === $rest_api_settings ) {
				$rest_api_settings = array();
			}

			if ( true === $settings['enabled'] ) {
				// Add new | update existing REST API rule.
				$actions = array( 'select', 'insert', 'update', 'delete' );
				foreach ( $actions as $action ) {
					if (
						isset(
							$settings[ $action ],
							$settings[ $action ]['authorization'],
							$settings[ $action ]['methods'],
							$settings[ $action ]['authorized_roles'],
							$settings[ $action ]['authorized_users']
						)
					) {
						unset( $rest_api_settings[ $schema_name ][ $table_name ][ $action ] ); // Unset previous authorization.
						$rest_api_settings[ $schema_name ][ $table_name ][ $action ]['authorization']    = $settings[ $action ]['authorization'];
						$rest_api_settings[ $schema_name ][ $table_name ][ $action ]['methods']          = $settings[ $action ]['methods'];
						$rest_api_settings[ $schema_name ][ $table_name ][ $action ]['authorized_roles'] = $settings[ $action ]['authorized_roles'];
						$rest_api_settings[ $schema_name ][ $table_name ][ $action ]['authorized_users'] = $settings[ $action ]['authorized_users'];
					}
					else {
						return $this->bad_request();
					}
				}
			} elseif ( false === $settings['enabled'] ) {
				// Remove existing REST API rule.
				if ( isset( $settings['select'] ) ) {
					unset( $rest_api_settings[ $schema_name ][ $table_name ]['select'] );
					if (
						isset( $rest_api_settings[ $schema_name ][ $table_name ] ) &&
						0 === count( $rest_api_settings[ $schema_name ][ $table_name ] )//phpcs:ignore - 8.1 proof
					) {
						unset( $rest_api_settings[ $schema_name ][ $table_name ] );
						if (
							isset( $rest_api_settings[ $schema_name ] ) &&
							0 === count( $rest_api_settings[ $schema_name ] )//phpcs:ignore - 8.1 proof
						) {
							unset( $rest_api_settings[ $schema_name ] );
						}
					}
				} else {
					return $this->bad_request();
				}
			} else {
				return $this->bad_request();
			}

			update_option( WPDA_API::WPDA_REST_API_TABLE_ACCESS, $rest_api_settings );

			return $this->WPDA_Rest_Response(
				sprintf( __( 'Saved REST API settings for table `%s`', 'wp-data-access' ), $table_name )
			);
		}

		private function save_admin_settings( $schema_name, $table_name, $settings ) {
			if (
				!
					(
						isset(
							$settings['scope'],
							$settings['target']
						) &&
						(
							isset(
								$settings['data']
							) ||
							null === $settings['data']
						) &&
						(
							'global' === $settings['scope'] ||
							'user' === $settings['scope']
						) &&
						(
							'table' === $settings['target'] ||
							'form' === $settings['target']
						)

					)
			) {
				return $this->bad_request();
			}

			$admin_settings = WPDA_Settings::get_admin_settings_key(
				$settings['target'],
				$schema_name ,
				$table_name
			);

			if ( 'global' === $settings['scope'] ) {
				// Store settings globally.
				if ( null !== $settings['data'] ) {
					update_option( $admin_settings, $settings['data'] );
				} else {
					delete_option( $admin_settings );
				}
			} else {
				// Store settings for login user.
				if ( null !== $settings['data'] ) {
					update_user_option( $admin_settings, $settings['data'] );
				} else {
					delete_user_option( $admin_settings );
				}
			}

			return $this->WPDA_Rest_Response( __( 'Successfully saved changes', 'wp-data-access' ) );
		}

		public static function get_admin_settings( $schema_name, $table_name ) {
			return array(
				'global' => array(
					'table' => get_option(
						WPDA_Settings::get_admin_settings_key(
							'table',
							$schema_name,
							$table_name
						)
					),
					'form'  => get_option(
						WPDA_Settings::get_admin_settings_key(
							'form',
							$schema_name,
							$table_name
						)
					),
				),
				'local'  => array(
					'table' => get_user_option(
						WPDA_Settings::get_admin_settings_key(
							'table',
							$schema_name,
							$table_name
						)
					),
					'form'  => get_user_option(
						WPDA_Settings::get_admin_settings_key(
							'form',
							$schema_name,
							$table_name
						)
					),
				),
			);
		}

		private static function get_admin_settings_key( $target, $schema_name, $table_name ) {
			return WPDA_Settings::WPDA_SETTINGS .
				'-' . $target .
				'-' . str_replace( ' ', '*', $schema_name ) .
				'-' . str_replace( ' ', '*', $table_name );
		}

	}

}

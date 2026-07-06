<?php

namespace WPDataAccess\Settings {

	use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Exist;
	use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Lists;
	use WPDataAccess\Utilities\WPDA_Message_Box;
	use WPDataAccess\WPDA;

	class WPDA_Settings_BackEnd extends WPDA_Settings {

		/**
		 * Add back-end tab content
		 *
		 * See class documentation for flow explanation.
		 *
		 * @since   1.0.0
		 */
		protected function add_content() {
			global $wpdb;

			if ( isset( $_REQUEST['database'] ) ) {
				$database = sanitize_text_field( wp_unslash( $_REQUEST['database'] ) ); // input var okay.
			} else {
				$database = $wpdb->dbname;
			}
			$is_wp_database = $database === $wpdb->dbname;

			if ( isset( $_REQUEST['action'] ) ) {
				$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // input var okay.

				// Security check.
				$wp_nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : ''; // input var okay.
				if ( ! wp_verify_nonce( $wp_nonce, 'wpda-back-end-settings' ) ) {
					wp_die( __( 'ERROR: Not authorized', 'wp-data-access' ) );
				}

				if ( 'save' === $action ) {
					// Save options.
					if ( $is_wp_database ) {
						WPDA::set_option(
							WPDA::OPTION_BE_TABLE_ACCESS,
							isset( $_REQUEST['table_access'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['table_access'] ) ) : null // input var okay.
						);
					} else {
						update_option(
							WPDA::BACKEND_OPTIONNAME_DATABASE_ACCESS . $database,
							isset( $_REQUEST['table_access'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['table_access'] ) ) : null // input var okay.
						);}

					$wpda_hide_manage_link = array();
					if ( isset( $_REQUEST['wpda_hide_manage_link'] ) ) {
						foreach ( $_REQUEST['wpda_hide_manage_link'] as $userid ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
							if ( is_numeric( $userid ) ) {
								$wpda_hide_manage_link[] = sanitize_text_field( wp_unslash( $userid ) );
							}
						}
					}
					update_option( 'wpda_hide_manage_link', $wpda_hide_manage_link );

					$table_access_selected_new_value = isset( $_REQUEST['table_access_selected'] ) ?
						WPDA::sanitize_text_field_array( $_REQUEST['table_access_selected'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					if ( is_array( $table_access_selected_new_value ) ) {
						// Check the requested table names for sql injection. This is simply done by checking if the table
						// name exists in our WordPress database.
						$table_access_selected_new_value_checked = array();
						foreach ( $table_access_selected_new_value as $key => $value ) {
							$wpda_dictionary_checks = new WPDA_Dictionary_Exist( $database, $value );
							if ( $wpda_dictionary_checks->table_exists( false ) ) {
								// Add existing table to list.
								$table_access_selected_new_value_checked[ $key ] = $value;
							} else {
								// An invalid table name was provided. Might be an sql injection attack or an invalid state.
								wp_die( __( 'ERROR: Invalid table name', 'wp-data-access' ) );
							}
						}
					} else {
						$table_access_selected_new_value_checked = '';
					}
					if ( $is_wp_database ) {
						WPDA::set_option(
							WPDA::OPTION_BE_TABLE_ACCESS_SELECTED,
							$table_access_selected_new_value_checked
						);
					} else {
						update_option(
							WPDA::BACKEND_OPTIONNAME_DATABASE_SELECTED . $database,
							$table_access_selected_new_value_checked
						);
					}

					if (
						isset( $_REQUEST['wpda_default_user'] ) &&
						isset( $_REQUEST['wpda_default_database'] ) &&
						'' !== $_REQUEST['wpda_default_user'] &&
						'' !== $_REQUEST['wpda_default_database']
					) {
						$default_databases = get_option( 'wpda_default_database' );
						if ( false === $default_databases ) {
							$default_databases = array();
						}
						$wpda_default_user     = sanitize_text_field( wp_unslash( $_REQUEST['wpda_default_user'] ) ); // input var okay.
						$wpda_default_database = sanitize_text_field( wp_unslash( $_REQUEST['wpda_default_database'] ) ); // input var okay.

						$default_databases[ $wpda_default_user ] = $wpda_default_database;
						update_option( 'wpda_default_database', $default_databases );
					}
				} elseif ( 'setdefaults' === $action ) {
					// Set all back-end settings back to default.
					if ( $is_wp_database ) {
						WPDA::set_option( WPDA::OPTION_BE_TABLE_ACCESS );
						WPDA::set_option( WPDA::OPTION_BE_TABLE_ACCESS_SELECTED );
					} else {
						update_option(
							WPDA::BACKEND_OPTIONNAME_DATABASE_ACCESS . $database,
							'show'
						);
						update_option(
							WPDA::BACKEND_OPTIONNAME_DATABASE_SELECTED . $database,
							''
						);
					}
					update_option( 'wpda_hide_manage_link', array() );
					update_option( 'wpda_default_database', array() );
				} elseif ( 'delete_default_user_database' === $action ) {
					if ( isset( $_REQUEST['wpda_default_database_delete'] ) ) {
						$delete_user_id = sanitize_text_field( wp_unslash( $_REQUEST['wpda_default_database_delete'] ) ); // input var okay.

						$default_databases = get_option( 'wpda_default_database' );
						if ( false !== $default_databases && isset( $default_databases[ $delete_user_id ] ) ) {
							unset( $default_databases[ $delete_user_id ] );
							update_option( 'wpda_default_database', $default_databases );
						}
					}
				}

				$msg = new WPDA_Message_Box(
					array(
						'message_text' => __( 'Settings saved', 'wp-data-access' ),
					)
				);
				$msg->box();
			}

			// Get options.
			if ( $is_wp_database ) {
				$table_access          = WPDA::get_option( WPDA::OPTION_BE_TABLE_ACCESS );
				$table_access_selected = WPDA::get_option( WPDA::OPTION_BE_TABLE_ACCESS_SELECTED );
			} else {
				$table_access = get_option( WPDA::BACKEND_OPTIONNAME_DATABASE_ACCESS . $database );
				if ( false === $table_access ) {
					$table_access = 'show';
				}
				$table_access_selected = get_option( WPDA::BACKEND_OPTIONNAME_DATABASE_SELECTED . $database );
				if ( false === $table_access_selected ) {
					$table_access_selected = '';
				}
			}

			if ( is_array( $table_access_selected ) ) {
				// Convert table for simple access.
				$table_access_selected_by_name = array();
				foreach ( $table_access_selected as $key => $value ) {
					$table_access_selected_by_name[ $value ] = true;
				}
			}

			$wpda_hide_manage_link = get_option( 'wpda_hide_manage_link' );
			if ( is_array( $wpda_hide_manage_link ) ) {
				$wpda_hide_manage_list = array_flip( $wpda_hide_manage_link ); //phpcs:ignore - 8.1 proof
			} else {
				$wpda_hide_manage_list = array();
			}
			?>

			<form id="wpda_settings_backend" method="post"
				  action="?page=<?php echo esc_attr( $this->page ); ?>&tab=backend">
				<table class="wpda-table-settings">
					<tr>
						<th><?php echo __( 'Table access', 'wp-data-access' ); ?></th>
						<td>
							<select name="database" id="schema_name">
								<?php
								$schema_names = WPDA_Dictionary_Lists::get_db_schemas();
								foreach ( $schema_names as $schema_name ) {
									$selected = $database === $schema_name['schema_name'] ? ' selected' : '';
									echo "<option value='{$schema_name['schema_name']}'$selected>{$schema_name['schema_name']}</option>"; // phpcs:ignore WordPress.Security.EscapeOutput
								}
								?>
							</select>
							<br/><br/>
							<label>
								<input
									type="radio"
									name="table_access"
									value="show"
									<?php echo 'show' === $table_access ? 'checked' : ''; ?>
								><?php echo $is_wp_database ? __( 'Show WordPress tables', 'wp-data-access' ) : __( 'Show all tables', 'wp-data-access' ); ?>
							</label>
							<br/>
							<?php
							if ( $is_wp_database ) {
								?>
								<label>
									<input
										type="radio"
										name="table_access"
										value="hide"
										<?php echo 'hide' === $table_access ? 'checked' : ''; ?>
									><?php echo __( 'Hide WordPress tables', 'wp-data-access' ); ?>
								</label>
								<br/>
								<?php
							}
							?>
							<label>
								<input
									type="radio"
									name="table_access"
									value="select"
									<?php echo 'select' === $table_access ? 'checked' : ''; ?>
								><?php echo __( 'Show only selected tables', 'wp-data-access' ); ?>
							</label>
							<div id="tables_selected" <?php echo 'select' === $table_access ? '' : 'style="display:none"'; ?>>
								<br/>
								<select name="table_access_selected[]" multiple size="10">
									<?php
									$tables = WPDA_Dictionary_Lists::get_tables( true, $database );
									foreach ( $tables as $table ) {
										$table_name = $table['table_name'];
										?>
										<option value="<?php echo esc_attr( $table_name ); ?>" <?php echo isset( $table_access_selected_by_name[ $table_name ] ) ? 'selected' : ''; ?>><?php echo esc_attr( $table_name ); ?></option>
										<?php
									}
									?>
								</select>
							</div>
							<script type='text/javascript'>
								jQuery(function () {
									jQuery("input[name='table_access']").on("click", function () {
										if (this.value == 'select') {
											jQuery("#tables_selected").show();
										} else {
											jQuery("#tables_selected").hide();
										}
									});
									jQuery('#schema_name').on('change', function() {
										window.location = '?page=<?php echo esc_attr( $this->page ); ?>&tab=backend&database=' + jQuery(this).val();
									});
								});
							</script>
						</td>
					</tr>
					<tr>
						<th><?php echo __( 'Restrict table management', 'wp-data-access' ); ?></th>
						<td>
							<select name="wpda_hide_manage_link[]" multiple="true">
								<?php
								foreach ( get_users( array( 'role' => 'administrator' ) ) as $user ) {
									$selected = isset( $wpda_hide_manage_list[ $user->ID ] ) ? 'selected' : '';
									echo "<option value='{$user->ID}' {$selected}>{$user->user_login} ({$user->user_email})</option>"; // phpcs:ignore WordPress.Security.EscapeOutput
								}
								?>
							</select>
							<div style="margin-top:5px;margin-left:-5px">
								<span class="dashicons dashicons-yes"></span>
								Removes the manage link in the Data Explorer for selected admin users
							</div>
							<div style="margin-left:-5px">
								<span class="dashicons dashicons-yes"></span>
								Hold control key to deselect or select multiple users
							</div>
							<div style="margin-left:-5px">
								<span class="dashicons dashicons-yes"></span>
								Non admin users have no access by default
							</div>
							<div style="margin-left:-5px">
								<span class="dashicons dashicons-yes"></span>
								Every administrator can change this option
							</div>
						</td>
					</tr>
					<tr>
						<th><?php echo __( 'Default database', 'wp-data-access' ); ?></th>
						<td>
							<div>
								<?php
								$users = array();
								foreach ( get_users() as $user ) {
									$users[ $user->data->ID ] = $user->data->user_login;
								}

								$databases    = array();
								$db_databases = WPDA_Dictionary_Lists::get_db_schemas();
								foreach ( $db_databases as $db_database ) {
									$databases[ $db_database['schema_name'] ] = true;
								}

								$default_databases = get_option( 'wpda_default_database' );
								if ( false === $default_databases ) {
									$default_databases = array();
								}
								if ( is_array( $default_databases ) ) {
									foreach ( $default_databases as $user_id => $database ) {
										?>
										<div id="wpda_default_database_<?php echo esc_attr( $user_id ); ?>">
											<span class="dashicons dashicons-trash"
												  style="font-size: 14px; vertical-align: text-top; cursor: pointer;"
												  onclick="if (confirm('Remove default database for this user?')) { jQuery('#wpda_default_database_delete').val('<?php echo esc_attr( $user_id ); ?>'); jQuery('#delete_default_user_database_form').submit(); } "
											></span>
											<span>
												<?php echo esc_attr( $users[ $user_id ] ); ?> > <?php echo esc_attr( $database ); ?>
											</span>
										</div>
										<?php
									}
								}
								?>
							</div>
							<?php
							if ( count( $default_databases ) > 0 ) {//phpcs:ignore - 8.1 proof
								echo '<br/>';
							}
							?>
							<div>
								<a href="javascript:void(0)" onclick="jQuery('#list_default_databases').show()" class="button">Define default database for user in Data Explorer</a>
							</div>
							<div id="list_default_databases" style="display:none">
								<br/>
								<div>
									<label for="wpda_default_user">User: </label>
									<select name="wpda_default_user" id="wpda_default_user">
										<option value="">Select user</option>
										<?php
										foreach ( get_users() as $user ) {
											echo '<option value="' . esc_attr( $user->data->ID ) . '">' . esc_attr( $user->data->user_login ) . '</option>';
										}
										?>
									</select>
									<label for="wpda_default_database">Database: </label>
									<select name="wpda_default_database" id="wpda_default_database">
										<option value="">Select database</option>
										<?php
										foreach ( $databases as $database => $value ) {
											echo '<option value="' . esc_attr( $database ) . '">' . esc_attr( $database ) . '</option>';
										}
										?>
									</select>
									<span class="dashicons dashicons-trash"
										  style="font-size: 14px; vertical-align: text-top; cursor: pointer;"
										  onclick="jQuery('#list_default_databases').hide(); jQuery('#wpda_default_user').val(''); jQuery('#wpda_default_database').val('');"
									></span>
								</div>
							</div>
						</td>
					</tr>
				</table>
				<div class="wpda-table-settings-button">
					<input type="hidden" name="action" value="save"/>
					<button type="submit" class="button button-primary">
						<i class="fas fa-check wpda_icon_on_button"></i>
						<?php echo __( 'Save Back-end Settings', 'wp-data-access' ); ?>
					</button>
					<a href="javascript:void(0)"
					   onclick="if (confirm('<?php echo __( 'Reset to defaults?', 'wp-data-access' ); ?>')) {
						   jQuery('input[name=&quot;action&quot;]').val('setdefaults');
						   jQuery('#wpda_settings_backend').trigger('submit')
						   }"
					   class="button">
						<i class="fas fa-times-circle wpda_icon_on_button"></i>
						<?php echo __( 'Reset Back-end Settings To Defaults', 'wp-data-access' ); ?>
					</a>
				</div>
				<?php wp_nonce_field( 'wpda-back-end-settings', '_wpnonce', false ); ?>
			</form>

			<form id="delete_default_user_database_form"
				  method="post"
				  action="?page=<?php echo esc_attr( $this->page ); ?>&tab=backend"
				  style="display:none">
				<input type="hidden" name="wpda_default_database_delete" id="wpda_default_database_delete" value=""/>
				<input type="hidden" name="action" value="delete_default_user_database"/>
				<?php wp_nonce_field( 'wpda-back-end-settings', '_wpnonce', false ); ?>
			</form>

			<?php
		}

	}

}

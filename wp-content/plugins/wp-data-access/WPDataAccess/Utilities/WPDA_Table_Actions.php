<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package WPDataAccess\Utilities
 */
namespace WPDataAccess\Utilities;

use WPDataAccess\API\WPDA_API;
use WPDataAccess\Connection\WPDADB;
use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Lists;
use WPDataAccess\Plugin_Table_Models\WPDA_Media_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_Table_Settings_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_User_Menus_Model;
use WPDataAccess\WPDA;
use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Exist;
use WPDataAccess\Data_Dictionary\WPDA_List_Columns_Cache;
use WPDataAccess\Data_Dictionary\WPDA_List_Columns;
/**
 * Class WPDA_Table_Actions
 *
 * @author  Peter Schulz
 * @since   2.0.13
 */
class WPDA_Table_Actions {
    /**
     * Database schema name
     *
     * @var string
     */
    protected $schema_name;

    /**
     * Database table name
     *
     * @var string
     */
    protected $table_name;

    /**
     * Database table structure
     *
     * @var array
     */
    protected $table_structure;

    /**
     * Original create table statement
     *
     * @var string
     */
    protected $create_table_stmt_orig;

    /**
     * Reformatted create table statement
     *
     * @var string
     */
    protected $create_table_stmt;

    /**
     * Database indexes
     *
     * @var array
     */
    protected $indexes;

    /**
     * Database foreign key constraints
     *
     * @var array
     */
    protected $foreign_keys;

    /**
     * Indicates if table is a WordPress table
     *
     * @var boolean
     */
    protected $is_wp_table;

    /**
     * Possible values: Table and View
     *
     * @var string
     */
    protected $dbo_type;

    /**
     * Handle to instance of WPDA_List_Columns
     *
     * @var WPDA_List_Columns
     */
    protected $wpda_list_columns;

    /**
     * Engine
     *
     * @var string
     */
    protected $engine;

    /**
     * Row number in the list table
     *
     * @var int
     */
    protected $rownum;

    /**
     * Shows the specifications for the specified table or view
     *
     * There are four tabs provided:
     *
     * TAB Actions
     * Provides actions for the given table or view, like export, rename, copy, drop, alter, and so on. A button
     * is provided for every possible action. For some actions additional info can be provided through input fields
     * like the type of download for an export. Not all buttons are available for all tables and views. WordPress
     * tables for example cannot be dropped. Views for example can not be truncated. Which buttons are provided
     * depends on the table or view.
     *
     * TAB Structure
     * Shows the columns and their attributes.
     *
     * TAB Indexes
     * Shows the indexes for the specified table. Not available for views.
     *
     * TAB SQL
     * Shows the create table or views statement for the given table of view. A button is provided to copy
     * this statement to the clipboard.
     *
     * @since   2.0.13
     */
    public function show() {
        if ( !isset( $_REQUEST['table_name'] ) || !isset( $_REQUEST['wpdaschema_name'] ) || !isset( $_REQUEST['rownum'] ) ) {
            wp_die( __( 'ERROR: Wrong arguments', 'wp-data-access' ) );
        }
        $this->schema_name = sanitize_text_field( wp_unslash( $_REQUEST['wpdaschema_name'] ) );
        // input var okay.
        $this->table_name = str_replace( '`', '', sanitize_text_field( wp_unslash( $_REQUEST['table_name'] ) ) );
        // input var okay.
        $this->rownum = sanitize_text_field( wp_unslash( $_REQUEST['rownum'] ) );
        // input var okay.
        $wpda_data_dictionary = new WPDA_Dictionary_Exist($this->schema_name, $this->table_name);
        if ( !$wpda_data_dictionary->table_exists() ) {
            echo '<div>' . __( 'ERROR: Invalid table name or not authorized', 'wp-data-access' ) . '</div>';
            return;
        }
        $wp_nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '?' );
        // input var okay.
        if ( !wp_verify_nonce( $wp_nonce, "wpda-actions-{$this->table_name}" ) ) {
            echo '<div>' . __( 'ERROR: Not authorized', 'wp-data-access' ) . '</div>';
            return;
        }
        $this->dbo_type = ( isset( $_REQUEST['dbo_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['dbo_type'] ) ) : null );
        // input var okay.
        $this->is_wp_table = WPDA::is_wp_table( $this->table_name );
        $this->engine = WPDA_Dictionary_Lists::get_engine( $this->schema_name, $this->table_name );
        $wpdadb = WPDADB::get_db_connection( $this->schema_name );
        if ( null === $wpdadb ) {
            wp_die( sprintf( __( 'ERROR - Remote database %s not available', 'wp-data-access' ), esc_attr( $this->schema_name ) ) );
        }
        $query = "show full columns from `{$wpdadb->dbname}`.`{$this->table_name}`";
        $this->table_structure = $wpdadb->get_results( $query, 'ARRAY_A' );
        if ( stripos( $this->dbo_type, 'table' ) !== false ) {
            $this->dbo_type = 'Table';
            $query = "show create table `{$wpdadb->dbname}`.`{$this->table_name}`";
            $create_table = $wpdadb->get_results( $query, 'ARRAY_A' );
            if ( isset( $create_table[0]['Create Table'] ) ) {
                $this->create_table_stmt_orig = $create_table[0]['Create Table'];
                $this->create_table_stmt = preg_replace(
                    '/\\(/',
                    '<br/>(',
                    $this->create_table_stmt_orig,
                    1
                );
                $this->create_table_stmt = preg_replace( '/\\,\\s\\s\\s/', '<br/>,   ', $this->create_table_stmt );
                $pos = strrpos( $this->create_table_stmt, ')' );
                if ( false !== $pos ) {
                    $this->create_table_stmt = substr( $this->create_table_stmt, 0, $pos - 1 ) . '<br/>)' . substr( $this->create_table_stmt, $pos + 1 );
                }
                $query = "show indexes from `{$wpdadb->dbname}`.`{$this->table_name}`";
                $this->indexes = $wpdadb->get_results( $query, 'ARRAY_A' );
                $query = $wpdadb->prepare( '
							select constraint_name AS constraint_name,
								   column_name AS column_name,
								   referenced_table_name AS referenced_table_name,
								   referenced_column_name AS referenced_column_name
							from   information_schema.key_column_usage
							where table_schema = %s
							  and table_name   = %s
							  and referenced_table_name is not null
							order by ordinal_position
						', array($wpdadb->dbname, $this->table_name) );
                $this->foreign_keys = $wpdadb->get_results( $query, 'ARRAY_A' );
            } else {
                $this->create_table_stmt = __( 'Error reading create table statement', 'wp-data-access' );
            }
        } elseif ( strtoupper( $this->dbo_type ) === 'VIEW' ) {
            $this->dbo_type = 'View';
            $query = "show create view `{$wpdadb->dbname}`.`{$this->table_name}`";
            $create_table = $wpdadb->get_results( $query, 'ARRAY_A' );
            if ( isset( $create_table[0]['Create View'] ) ) {
                $this->create_table_stmt_orig = $create_table[0]['Create View'];
                $this->create_table_stmt = str_replace( 'AS select', 'AS<br/>select', $this->create_table_stmt_orig );
                $this->create_table_stmt = str_replace( 'from', '<br/>from', $this->create_table_stmt );
            }
        }
        $this->wpda_list_columns = WPDA_List_Columns_Cache::get_list_columns( $this->schema_name, $this->table_name );
        ?>
			<div id="<?php 
        echo esc_attr( $this->rownum );
        ?>-tabs">
				<div class="nav-tab-wrapper" style="padding-top: 0 !important;">
					<?php 
        echo '<a id="' . esc_attr( $this->rownum ) . '-sel-1" class="nav-tab nav-tab-active wpda-manage-nav-tab' . '" href="javascript:void(0)" onclick="settab(\'' . esc_attr( $this->rownum ) . '\', \'1\');"
						style="font-size:inherit;">' . '<span class="dashicons dashicons-admin-tools wpda_settings_icon"></span>' . '<span class="wpda_settings_label">' . __( 'Actions', 'wp-data-access' ) . '</span>' . '</a>';
        if ( 'Table' === $this->dbo_type || 'View' === $this->dbo_type ) {
            echo '<a id="' . esc_attr( $this->rownum ) . '-sel-6" class="nav-tab wpda-manage-nav-tab' . '" href="javascript:void(0)" onclick="settab(\'' . esc_attr( $this->rownum ) . '\', \'6\');"
							style="font-size:inherit;">' . '<span class="dashicons dashicons-admin-generic wpda_settings_icon"></span> ' . '<span class="wpda_settings_label">' . __( 'Settings', 'wp-data-access' ) . '</span>' . '</a>';
        }
        if ( '' !== $this->dbo_type ) {
            echo '<a id="' . esc_attr( $this->rownum ) . '-sel-2" class="nav-tab wpda-manage-nav-tab' . '" href="javascript:void(0)" onclick="settab(\'' . esc_attr( $this->rownum ) . '\', \'2\');"
							style="font-size:inherit;">' . '<span class="dashicons dashicons-list-view wpda_settings_icon"></span> ' . '<span class="wpda_settings_label">' . __( 'Columns', 'wp-data-access' ) . '</span>' . '</a>';
        }
        if ( 'Table' === $this->dbo_type ) {
            echo '<a id="' . esc_attr( $this->rownum ) . '-sel-3" class="nav-tab wpda-manage-nav-tab' . '" href="javascript:void(0)" onclick="settab(\'' . esc_attr( $this->rownum ) . '\', \'3\');"
							style="font-size:inherit;">' . '<span class="dashicons dashicons-controls-forward wpda_settings_icon"></span> ' . '<span class="wpda_settings_label">' . __( 'Indexes', 'wp-data-access' ) . '</span>' . '</a>';
        }
        if ( 'Table' === $this->dbo_type ) {
            echo '<a id="' . esc_attr( $this->rownum ) . '-sel-5" class="nav-tab wpda-manage-nav-tab' . '" href="javascript:void(0)" onclick="settab(\'' . esc_attr( $this->rownum ) . '\', \'5\');"
							style="font-size:inherit;">' . '<span class="dashicons dashicons-networking wpda_settings_icon"></span> ' . '<span class="wpda_settings_label">' . __( 'Foreign Keys', 'wp-data-access' ) . '</span>' . '</a>';
        }
        if ( 'Table' === $this->dbo_type || 'View' === $this->dbo_type ) {
            echo '<a id="' . esc_attr( $this->rownum ) . '-sel-4" class="nav-tab wpda-manage-nav-tab' . '" href="javascript:void(0)" onclick="settab(\'' . esc_attr( $this->rownum ) . '\', \'4\');"
							style="font-size:inherit;">' . '<span class="dashicons dashicons-editor-code wpda_settings_icon"></span> ' . '<span class="wpda_settings_label">' . __( 'SQL', 'wp-data-access' ) . '</span>' . '</a>';
        }
        ?>
				</div>
				<div id="<?php 
        echo esc_attr( $this->rownum );
        ?>-tab-1" style="padding:3px;">
					<?php 
        $this->tab_actions();
        ?>
				</div>
				<div id="<?php 
        echo esc_attr( $this->rownum );
        ?>-tab-6" style="padding:3px;display:none;">
					<?php 
        $this->tab_settings();
        ?>
				</div>
				<?php 
        if ( '' !== $this->dbo_type ) {
            ?>
					<div id="<?php 
            echo esc_attr( $this->rownum );
            ?>-tab-2" style="padding:3px;display:none;">
						<?php 
            $this->tab_structure();
            ?>
					</div>
					<?php 
        }
        if ( 'Table' === $this->dbo_type ) {
            ?>
					<div id="<?php 
            echo esc_attr( $this->rownum );
            ?>-tab-3" style="padding:3px;display:none;">
						<?php 
            $this->tab_index();
            ?>
					</div>
					<?php 
        }
        if ( 'Table' === $this->dbo_type ) {
            ?>
					<div id="<?php 
            echo esc_attr( $this->rownum );
            ?>-tab-5" style="padding:3px;display:none;">
						<?php 
            $this->tab_foreign_keys();
            ?>
					</div>
					<?php 
        }
        if ( 'Table' === $this->dbo_type || 'View' === $this->dbo_type ) {
            ?>
					<div id="<?php 
            echo esc_attr( $this->rownum );
            ?>-tab-4" style="padding:3px;display:none;">
						<?php 
            $this->tab_sql();
            ?>
					</div>
					<?php 
        }
        ?>
			</div>
			<div style="height:0;padding:0;margin:0;"></div>
			<script type='text/javascript'>
				function copyTable(rowNum, dboType, tableName, formId) {
					if (jQuery("#copy-table-from-" + rowNum).val()==="") {
						alert('<?php 
        echo __( 'Please enter a valid table name', 'wp-data-access' );
        ?>');
						return false;
					}

					if (confirm('<?php 
        echo __( 'Copy', 'wp-data-access' );
        ?> ' + dboType + '?')) {
						jQuery("#copy_schema_name_" + tableName).val(
							jQuery("#copy-schema-from-" + rowNum).val()
						);
						jQuery("#copy_table_name_" + tableName).val(
							jQuery("#copy-table-from-" + rowNum).val()
						);
						jQuery("#" + formId).submit();
					}
				}
				jQuery(function () {
					var sql_to_clipboard = new ClipboardJS("#button-copy-clipboard-<?php 
        echo esc_attr( $this->rownum );
        ?>");
					sql_to_clipboard.on('success', function (e) {
						jQuery.notify('<?php 
        echo __( 'SQL successfully copied to clipboard!', 'wp-data-access' );
        ?>','info');
					});
					sql_to_clipboard.on('error', function (e) {
						jQuery.notify('<?php 
        echo __( 'Could not copy SQL to clipboard!', 'wp-data-access' );
        ?>','error');
					});
					jQuery("#rename-table-from-<?php 
        echo esc_attr( $this->rownum );
        ?>").on('keyup paste', function () {
						this.value = this.value.replace(/[^\w\$\_]/g, '');
					});
					jQuery("#copy-table-from-<?php 
        echo esc_attr( $this->rownum );
        ?>").on('keyup paste', function () {
						this.value = this.value.replace(/[^\w\$\_]/g, '');
					});
				});
			</script>
			<?php 
    }

    /**
     * Provides content for table settings
     */
    public function tab_settings() {
        $settings_db = WPDA_Table_Settings_Model::query( $this->table_name, $this->schema_name );
        if ( isset( $settings_db[0]['wpda_table_settings'] ) ) {
            $settings_db_custom = json_decode( $settings_db[0]['wpda_table_settings'] );
            $sql_dml = 'UPDATE';
        } else {
            $settings_db_custom = (object) null;
            $sql_dml = 'INSERT';
        }
        if ( has_filter( 'wpda_add_column_settings' ) ) {
            // Use filter
            $column_settings_add_column = apply_filters( 'wpda_add_column_settings', null );
        }
        if ( isset( $column_settings_add_column ) && is_array( $column_settings_add_column ) ) {
            $array_valid = true;
            foreach ( $column_settings_add_column as $add_column ) {
                if ( !isset( $add_column['label'] ) || !isset( $add_column['hint'] ) || !isset( $add_column['name_prefix'] ) || !isset( $add_column['type'] ) || !isset( $add_column['default'] ) || !isset( $add_column['disable'] ) ) {
                    $array_valid = false;
                    break;
                }
            }
            if ( !$array_valid ) {
                $column_settings_add_column = array();
            }
        } else {
            $column_settings_add_column = array();
        }
        $row_count_is_configurable = 'innodb' === strtolower( $this->engine ) || 'federated' === strtolower( $this->engine ) || 'connect' === strtolower( $this->engine ) || $this->engine === null;
        ?>
			<style>
				.wpda_table_settings_nested {
					padding-top: 5px;
					padding-left: 20px;
					padding-bottom: 20px;
					display: none;
                    margin-right: 20px;
                }

                .wpda_table_settings_nested table {
					width: 100%;
				}

				.wpda_table_settings {
					border-collapse: collapse;
				}

                .wpda_table_settings thead {
					background-color: white;
					border-bottom: 1px solid #c3c4c7;
				}

				.wpda_table_settings thead th {
					text-align: left;
					font-weight: bold;
					padding: 16px 12px;
				}

                .wpda_table_settings thead th span {
					vertical-align: middle;
                }

				.wpda_table_settings td {
					vertical-align: top;
					padding: 8px 12px;
					margin: 0;
				}

				.wpda_table_settings tr:nth-child(even) {
					background: #fff;
				}

				.wpda_table_settings_menu {
					display: grid;
					grid-template-columns: min(30%, 290px) auto;
					padding: 10px;
				}

				.wpda_table_settings_panel {
                    border: 1px solid #c3c4c7;
                    margin: 9px 5px 10px 0;
                    padding: 10px;
				}

				.wpda_table_settings_nav_vertical {
					display: grid;
                    grid-template-columns: auto;
                    margin-left: 5px;
                    margin-right: 3px;
                    padding-top: 0;
				}

                .wpda_table_settings_nav_vertical a {
					margin: 0;
                    display: grid;
                    grid-template-columns: 24px auto;
                    align-items: center;
                    padding: 8px 12px;
				}

                .wpda_table_settings_nav_vertical a.nav-tab-active {
					border-right: none;
					background-color: transparent;
                }

                .wpda_table_settings_nav_vertical a.nav-tab:focus {
                    box-shadow: none;
				}

				ul.wpda_table_settings_panel h4 {
					margin-top: 2em;
				}

				.wpda_table_settings_menu_content {
					display: flex;
					flex-direction: column;
					align-items: stretch;
				}

				.wpda_table_settings_menu_content .wpda_table_settings_vertical_border {
                    height: 10px;
                    border-right: 1px solid #c3c4c7;
                    padding: 0;
                    margin: 9px 3px 0 0;
				}

                .wpda_table_settings_menu_content .wpda_table_settings_vertical_border.wpda_table_settings_vertical_border_end {
                    flex: 2;
                    margin-top: 0;
					margin-bottom: 10px;
                }

                .nav-tab-active {
                    background-color: transparent;
                }

                .nav-tab-active:focus {
                    box-shadow: none;
                    background-color: transparent;
                }

                ul.wpda_geolocation_settings .wpda_fieldset {
					background-color: #efefef;
				}

				.wpda_table_setting_item {
					width: 100%;
				}
			</style>
			<script>
				function settingsNav(elem, className, setActive = true) {
					if (setActive) {
						jQuery(elem).closest(".wpda_table_settings_menu").find("a.nav-tab").removeClass("nav-tab-active");
						jQuery(elem).addClass("nav-tab-active");
					}

					jQuery(elem).closest(".wpda_table_settings_menu").find("ul.wpda_table_settings_nested").hide();
					jQuery(elem).closest(".wpda_table_settings_menu").find("ul.wpda_table_settings_nested." + className).show();
				}
			</script>
			<table class="widefat striped rows wpda-structure-table">
				<tr>
					<td>
						<div id="wpda_table_settings_<?php 
        echo esc_attr( $this->rownum );
        ?>" class="wpda_table_settings_menu">
							<div class="wpda_table_settings_menu_content">
								<div class="wpda_table_settings_vertical_border"></div>
								<nav class="nav-tab-wrapper wpda_table_settings_nav_vertical">
									<a href="javascript:void(0)" onclick="settingsNav(this, 'wpda_table_table_settings')" class="nav-tab">
										<i class="fa-solid fa-table wpda_settings_icon"></i>
										<span>
											Table Settings
										</span>
									</a>
									<a href="javascript:void(0)" onclick="settingsNav(this, 'wpda_table_column_settings')" class="nav-tab">
										<i class="fa-solid fa-table-columns wpda_settings_icon"></i>
										<span>
											Column Settings
										</span>
									</a>
									<?php 
        ?>
									<a href="javascript:void(0)" onclick="settingsNav(this, 'wpda_table_dynamic_hyperlinks_settings')" class="nav-tab">
										<i class="fa-solid fa-link wpda_settings_icon"></i>
										<span>
											Dynamic Hyperlinks
										</span>
									</a>
									<a href="javascript:void(0)" onclick="settingsNav(this, 'wpda_table_dashboard_menus_settings')" class="nav-tab">
										<i class="fa-solid fa-bars wpda_settings_icon"></i>
										<span>
											Dashboard Menus
										</span>
									</a>
									<a href="javascript:void(0)" onclick="settingsNav(this, 'wpda_table_rest_api_settings'); setRestApiDefaultTab();" class="nav-tab">
										<i class="fa-solid fa-gears wpda_settings_icon"></i>
										<span>
											REST API
										</span>
									</a>
								</nav>
								<div class="wpda_table_settings_vertical_border wpda_table_settings_vertical_border_end"></div>
							</div>
							<ul class="wpda_table_settings_panel">
								<?php 
        do_action(
            'wpda_prepend_table_settings',
            $this->schema_name,
            $this->table_name,
            $this->table_structure
        );
        ?>
								<li>
									<ul class="wpda_table_settings_nested wpda_table_table_settings">
										<h2>
											<?php 
        echo __( 'Table Settings', 'wp-data-access' );
        ?>
											<a href="https://docs.legacy.wpdataaccess.com/docs/manage-table-settings/" target="_blank">
												<span class="dashicons dashicons-editor-help wpda_tooltip"
													  title="<?php 
        echo __( 'Help opens in a new tab or window', 'wp-data-access' );
        ?>"
													  style="cursor:pointer"></span>
											</a>
										</h2>

										<h4>
											<?php 
        echo __( 'Row count (configurable for InnoDB tables, federated tables and views only)', 'wp-data-access' );
        ?>
										</h4>

										<div style="font-size:90%;">
											<label class="wpda_action_font">
												<input type="radio"
													   name="<?php 
        echo esc_attr( $this->table_name );
        ?>_row_count_estimate"
													   value="true"
													   <?php 
        echo ( !$row_count_is_configurable ? ' disabled ' : '' );
        if ( isset( $settings_db_custom->table_settings->row_count_estimate ) && $settings_db_custom->table_settings->row_count_estimate ) {
            echo ' checked ';
        }
        ?>
												>
												Show estimated row count (faster but less accurate)
											</label>
											<?php 
        $row_count_estimate_value = 'plugin';
        if ( isset( $settings_db_custom->table_settings->row_count_estimate_value ) ) {
            $row_count_estimate_value = $settings_db_custom->table_settings->row_count_estimate_value;
        }
        ?>
											<div style="padding: 5px 0 5px 25px">
												<label class="wpda_action_font">
													<input type="radio"
														   name="<?php 
        echo esc_attr( $this->table_name );
        ?>_row_count_estimated_value"
														   value="plugin"
															<?php 
        echo ( 'plugin' === $row_count_estimate_value ? 'checked' : '' );
        echo ( !$row_count_is_configurable ? ' disabled ' : '' );
        ?>
													/>
													Use plugin estimate calculation
												</label>
												<br/>
												<label class="wpda_action_font">
													<?php 
        $row_count = 0;
        if ( isset( $settings_db_custom->table_settings->row_count_estimate_value_hard ) ) {
            $row_count = $settings_db_custom->table_settings->row_count_estimate_value_hard;
        } else {
            if ( 'connect' === strtolower( $this->engine ) ) {
                $row_count = '';
            } else {
                $wpdadb = WPDADB::get_db_connection( $this->schema_name );
                $explain = $wpdadb->get_results( 'explain select count(*) from `' . str_replace( '`', '', $this->table_name ) . '`', 'ARRAY_A' );
                if ( isset( $explain[0]['rows'] ) ) {
                    $row_count = $explain[0]['rows'];
                } elseif ( isset( $explain[0]['ROWS'] ) ) {
                    $row_count = $explain[0]['ROWS'];
                }
            }
        }
        ?>
													<input type="radio"
														   name="<?php 
        echo esc_attr( $this->table_name );
        ?>_row_count_estimated_value"
														   value="hard"
															<?php 
        echo ( 'hard' === $row_count_estimate_value ? 'checked' : '' );
        echo ( !$row_count_is_configurable ? ' disabled ' : '' );
        ?>
													/>
													Use hard estimate:
													<input type="number"
														   id="<?php 
        echo esc_attr( $this->table_name );
        ?>_row_count_estimated_value_hard"
														   value="<?php 
        echo esc_attr( $row_count );
        ?>"
														   echo ! $row_count_is_configurable ? 'disabled ' : '';
												   />
													<a href="javascript:void(0)"
													   onclick="get_table_row_count('<?php 
        echo esc_attr( wp_create_nonce( "wpda-get-row-count-{$this->table_name}" ) );
        ?>', '<?php 
        echo esc_attr( $this->schema_name );
        ?>', '<?php 
        echo esc_attr( $this->table_name );
        ?>')"
													   title="Get real row count"
													   class="wpda_tooltip"
													   style="font-weight: bold; font-size: 140%;"
													>Σ</a>
												</label>
											</div>
											<label class="wpda_action_font">
												<input type="radio"
													   name="<?php 
        echo esc_attr( $this->table_name );
        ?>_row_count_estimate"
													   value="false"
													   <?php 
        echo ( !$row_count_is_configurable ? ' disabled ' : '' );
        if ( isset( $settings_db_custom->table_settings->row_count_estimate ) && !$settings_db_custom->table_settings->row_count_estimate ) {
            echo ' checked ';
        } else {
            if ( !$row_count_is_configurable ) {
                echo ' checked ';
            }
        }
        ?>
												>
												Show real row count
											</label>
											<br/>
											<label class="wpda_action_font" style="display: inline-block; margin-top: 10px">
												<input type="radio"
													   name="<?php 
        echo esc_attr( $this->table_name );
        ?>_row_count_estimate"
													   value=""
													   <?php 
        echo ( !$row_count_is_configurable ? ' disabled ' : '' );
        if ( isset( $settings_db_custom->table_settings->row_count_estimate ) ) {
            if ( null === $settings_db_custom->table_settings->row_count_estimate ) {
                echo ' checked ';
            }
        } else {
            if ( $row_count_is_configurable ) {
                echo ' checked ';
            }
        }
        ?>
												>
												Use plugin default (current value=<?php 
        echo esc_attr( WPDA::get_option( WPDA::OPTION_BE_INNODB_COUNT ) );
        ?>)
												[<a href="<?php 
        echo admin_url( 'options-general.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpdataaccess&tab=backend">change plugin default</a>]
											</label>
											<div style="margin-top: 5px; padding-left: 24px">
												Performs real row count if estimate <= <?php 
        echo esc_attr( WPDA::get_option( WPDA::OPTION_BE_INNODB_COUNT ) );
        ?>.
												Uses estimated row count if estimate > <?php 
        echo esc_attr( WPDA::get_option( WPDA::OPTION_BE_INNODB_COUNT ) );
        ?>.
											</div>
										</div>
										<h4>
											<?php 
        echo __( 'Query buffer size', 'wp-data-access' );
        ?>
										</h4>
										<div style="font-size:90%;">
											<label class="wpda_action_font">
												<?php 
        echo __( 'Max rows per fetch', 'wp-data-access' );
        ?>
											</label>
											<input type="text"
												   id="<?php 
        echo esc_attr( $this->table_name );
        ?>_query_buffer_size"
												<?php 
        if ( isset( $settings_db_custom->table_settings->query_buffer_size ) ) {
            echo 'value="' . esc_attr( $settings_db_custom->table_settings->query_buffer_size ) . '"';
        }
        ?>
											/>
											<span class="dashicons dashicons-editor-help wpda_tooltip" title="Large exports to other databases can result in an 'allowed memory size exhausted' error. Reduce the query buffer size to prevent this fatal error.

Start with high values and work down until the error disappears." style="cursor:pointer;vertical-align:middle"></span>
										</div>
										<h4>
											<?php 
        echo __( 'Access control', 'wp-data-access' );
        ?>
										</h4>
										<div style="font-size:90%;">
											<label class="wpda_action_font">
												<input type="checkbox"
													   id="<?php 
        echo esc_attr( $this->table_name );
        ?>_row_level_security"
													<?php 
        if ( isset( $settings_db_custom->table_settings->row_level_security ) ) {
            echo ( 'true' === $settings_db_custom->table_settings->row_level_security ? 'checked' : '' );
        }
        ?>
												/>
												<?php 
        echo __( 'Enable row level access control (adds token to row actions)', 'wp-data-access' );
        ?>
											</label>
										</div>
										<h4>
											<?php 
        echo __( 'Process hyperlink columns as', 'wp-data-access' );
        ?>
										</h4>
										<div style="font-size:90%;">
											<label for="<?php 
        echo esc_attr( $this->table_name );
        ?>table_top_setting_hyperlink_definition_json">
												<input type="radio"
													   id="<?php 
        echo esc_attr( $this->table_name );
        ?>table_top_setting_hyperlink_definition_json"
													   name="<?php 
        echo esc_attr( $this->table_name );
        ?>table_top_setting_hyperlink_definition"
													   value="json"
													   class="wpda_table_top_setting_item wpda_action_font"
													   <?php 
        if ( isset( $settings_db_custom->table_settings->hyperlink_definition ) ) {
            echo ( 'json' === $settings_db_custom->table_settings->hyperlink_definition ? 'checked' : '' );
        } else {
            echo 'checked';
        }
        ?>
												/>
												<?php 
        echo __( 'Preformatted JSON (allows individual label and target setting)', 'wp-data-access' );
        ?>
											</label>
											<br/>
											<label for="<?php 
        echo esc_attr( $this->table_name );
        ?>table_top_setting_hyperlink_definition_text" style="display: inline-block; margin-top: 10px">
												<input type="radio"
													   id="<?php 
        echo esc_attr( $this->table_name );
        ?>table_top_setting_hyperlink_definition_text"
													   name="<?php 
        echo esc_attr( $this->table_name );
        ?>table_top_setting_hyperlink_definition"
													   value="text"
													   class="wpda_table_top_setting_item wpda_action_font"
													   <?php 
        if ( isset( $settings_db_custom->table_settings->hyperlink_definition ) ) {
            echo ( 'text' === $settings_db_custom->table_settings->hyperlink_definition ? 'checked' : '' );
        }
        ?>
												/>
												<?php 
        echo __( 'Plain text (column name used as link, opens a new tab or window)', 'wp-data-access' );
        ?>
											</label>
										</div>
										<br/>
										<div>
											<button type="button"
												   id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_save_table_settings"
												   class="button button-primary"
											>
												<i class="fas fa-check wpda_icon_on_button"></i>
												<?php 
        echo __( 'Save Table Settings', 'wp-data-access' );
        ?>
											</button>
										</div>
									</ul>
								</li>
								<li>
									<ul class="wpda_table_settings_nested wpda_table_column_settings">
										<h2>
											<?php 
        echo __( 'Column Settings', 'wp-data-access' );
        ?>
											<a href="https://docs.legacy.wpdataaccess.com/docs/column-settings/" target="_blank">
												<span class="dashicons dashicons-editor-help wpda_tooltip"
													  title="<?php 
        echo __( 'Help opens in a new tab or window', 'wp-data-access' );
        ?>"
													  style="cursor:pointer"></span>
											</a>
										</h2>
										<table class="wpda_table_settings">
											<thead>
											<tr>
												<th>
													<span>
														<?php 
        echo __( 'Column', 'wp-data-access' );
        ?>
													</span>
												</th>
												<th>
													<span>
														<?php 
        echo __( 'Label on List Table', 'wp-data-access' );
        ?>
													</span>
													<span
														class="dashicons dashicons-editor-help wpda_tooltip"
														title="<?php 
        echo __( 'Define column labels for list tables', 'wp-data-access' );
        ?>"
													></span>
												</th>
												<th>
													<span>
														<?php 
        echo __( 'Label on Data Entry Form', 'wp-data-access' );
        ?>
													</span>
													<span class="dashicons dashicons-editor-help wpda_tooltip"
														  title="<?php 
        echo __( 'Define column labels for data entry forms', 'wp-data-access' );
        ?>"
													></span>
												</th>
												<th>
													<span>
														<?php 
        echo __( 'Column Type', 'wp-data-access' );
        ?>
													</span>
													<span class="dashicons dashicons-editor-help wpda_tooltip"
														  title="<?php 
        echo __( 'Featured plugin column types', 'wp-data-access' );
        ?>"
													></span>
												</th>
												<?php 
        foreach ( $column_settings_add_column as $add_column ) {
            ?>
													<th>
														<span>
															<?php 
            echo esc_attr( $add_column['label'] );
            ?>
														</span>
														<span class="dashicons dashicons-editor-help wpda_tooltip"
															  title="<?php 
            echo esc_attr( $add_column['hint'] );
            ?>"
														></span>
													</th>
												<?php 
        }
        ?>
												</tr>
											</thead>
											<tbody>
											<?php 
        $columns = $this->wpda_list_columns->get_table_column_headers();
        $media_pool = WPDA_Media_Model::get_pool();
        $media_cols = array();
        if ( isset( $media_pool[$this->schema_name][$this->table_name] ) ) {
            $media_cols = $media_pool[$this->schema_name][$this->table_name];
        }
        foreach ( $this->table_structure as $column ) {
            $label_list_table = $this->wpda_list_columns->get_column_label( $column['Field'] );
            $label_data_entry = ( isset( $columns[$column['Field']] ) ? $columns[$column['Field']] : '' );
            $option = ( isset( $media_cols[$column['Field']] ) ? $media_cols[$column['Field']] : '' );
            ?>
												<tr>
													<td>
														<?php 
            echo esc_attr( $column['Field'] );
            ?>
													</td>
													<td>
														<input
																id="list_label_<?php 
            echo esc_attr( $column['Field'] );
            ?>"
																class="wpda_table_setting_item wpda_action_font"
																type="text"
																value="<?php 
            echo esc_attr( $label_list_table );
            ?>"
																style="font-size: 90%;"
														>
													</td>
													<td>
														<input
																id="form_label_<?php 
            echo esc_attr( $column['Field'] );
            ?>"
																class="wpda_table_setting_item wpda_action_font"
																type="text"
																value="<?php 
            echo esc_attr( $label_data_entry );
            ?>"
																style="font-size: 90%;"
														>
													</td>
													<td style="text-align:center;">
														<select id="column_media_<?php 
            echo esc_attr( $column['Field'] );
            ?>"
																class="wpda_table_setting_item wpda_action_font"
																style="font-size: 90%; height: 30px;"
														>
															<option value="" <?php 
            echo ( '' === $option ? 'selected' : '' );
            ?>>
															</option>
															<option value="Attachment" <?php 
            echo ( 'Attachment' === $option ? 'selected' : '' );
            ?>>
																Attachment
															</option>
															<option value="Audio" <?php 
            echo ( 'Audio' === $option ? 'selected' : '' );
            ?>>
																Audio
															</option>
															<option value="Hyperlink" <?php 
            echo ( 'Hyperlink' === $option ? 'selected' : '' );
            ?>>
																Hyperlink
															</option>
															<option value="Image" <?php 
            echo ( 'Image' === $option ? 'selected' : '' );
            ?>>
																Image
															</option>
															<option value="ImageURL" <?php 
            echo ( 'ImageURL' === $option ? 'selected' : '' );
            ?>>
																Image URL
															</option>
															<option value="Video" <?php 
            echo ( 'Video' === $option ? 'selected' : '' );
            ?>>
																Video
															</option>
														</select>
														<input type="hidden"
															   id="column_media_<?php 
            echo esc_attr( $column['Field'] );
            ?>_dml"
															   value="<?php 
            echo ( isset( $media_cols[$column['Field']] ) ? 'UPDATE' : 'INSERT' );
            ?>"
														/>
														<input type="hidden"
															   id="column_media_<?php 
            echo esc_attr( $column['Field'] );
            ?>_old"
															   value="<?php 
            echo esc_attr( $option );
            ?>"
														/>
													</td>
													<?php 
            foreach ( $column_settings_add_column as $add_column ) {
                ?>
														<td>
															<?php 
                if ( 'Edit' === $add_column['label'] ) {
                    echo '<label>';
                }
                ?>
															<input
																type="<?php 
                echo esc_attr( $add_column['type'] );
                ?>"
																id="<?php 
                echo esc_attr( $add_column['name_prefix'] ) . esc_attr( $column['Field'] );
                ?>"
																class="wpda_table_setting_item wpda_action_font wpda_tooltip"
																<?php 
                if ( 'keys' === $add_column['disable'] ) {
                    $primary_key = $this->wpda_list_columns->get_table_primary_key();
                    if ( in_array( $column['Field'], $primary_key ) ) {
                        //phpcs:ignore - 8.1 proof
                        echo 'disabled="disabled" title="Not available for key columns"';
                    }
                }
                if ( 'checkbox' === $add_column['type'] ) {
                    $column_name = $add_column['name_prefix'] . esc_attr( $column['Field'] );
                    if ( isset( $settings_db_custom->custom_settings->{$column_name} ) ) {
                        echo ( $settings_db_custom->custom_settings->{$column_name} ? 'checked' : '' );
                    } else {
                        echo esc_attr( $add_column['default'] );
                    }
                }
                ?>
															><?php 
                if ( 'Edit' === $add_column['label'] ) {
                    echo '</label>';
                }
                ?>
														</td>
													<?php 
            }
            ?>
												</tr>
												<?php 
        }
        ?>
											</tbody></table>
										<br/>
										<div>
											<button type="button"
												   id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_save_column_settings"
												   class="button button-primary"
											>
												<i class="fas fa-check wpda_icon_on_button"></i>
												<?php 
        echo __( 'Save Column Settings', 'wp-data-access' );
        ?>
											</button>
										</div>
									</ul>
								</li>
								<?php 
        $this->settings_tab_dynamic_hyperlinks( $settings_db_custom );
        $this->settings_tab_dashboard_menus();
        $this->settings_tab_rest_api();
        do_action( 'wpda_append_table_settings' );
        ?>
							</ul>
							<input class="wpda_table_setting_item" type="hidden" id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_sql_dml"
								   value="<?php 
        echo esc_attr( $sql_dml );
        ?>"
							/>
						</div>
					</td>
				</tr>
			</table>
			<script type='text/javascript'>
				jQuery(function () {
					// Show table settings menu on startup
					settingsNav("#wpda_table_settings_<?php 
        echo esc_attr( $this->rownum );
        ?>", "wpda_table_table_settings", false);
					jQuery("#wpda_table_settings_<?php 
        echo esc_attr( $this->rownum );
        ?>").find("a.nav-tab").removeClass("nav-tab-active");
					jQuery("#wpda_table_settings_<?php 
        echo esc_attr( $this->rownum );
        ?>").find("a.nav-tab:first-child").addClass("nav-tab-active");

					// Table settings
					jQuery('#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_save_table_settings').click( function() {
						return submit_table_settings(
							'<?php 
        echo esc_attr( $this->rownum );
        ?>',
							'<?php 
        echo esc_attr( $this->schema_name );
        ?>',
							'<?php 
        echo esc_attr( $this->table_name );
        ?>'
						);
					});
					jQuery('#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_cancel_table_settings').click( function() {
						jQuery('#wpda_admin_menu_actions_<?php 
        echo esc_attr( $this->rownum );
        ?>').toggle();
						wpda_toggle_row_actions('<?php 
        echo esc_attr( $this->rownum );
        ?>');
					});

					// Column settings
					jQuery('#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_save_column_settings').click( function() {
						return submit_column_settings(
							'<?php 
        echo esc_attr( $this->rownum );
        ?>',
							'<?php 
        echo esc_attr( $this->schema_name );
        ?>',
							'<?php 
        echo esc_attr( $this->table_name );
        ?>'
						);
					});
					jQuery('#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_cancel_column_settings').click( function() {
						jQuery('#wpda_admin_menu_actions_<?php 
        echo esc_attr( $this->rownum );
        ?>').toggle();
						wpda_toggle_row_actions('<?php 
        echo esc_attr( $this->rownum );
        ?>');
					});

					jQuery('.wpda_tooltip').tooltip();
				});

				var custom_column_settings = [];
				<?php 
        foreach ( $column_settings_add_column as $add_column ) {
            echo 'custom_column_settings.push("' . $add_column['name_prefix'] . '");';
            // phpcs:ignore WordPress.Security.EscapeOutput
        }
        ?>
			</script>
			<?php 
    }

    private function settings_tab_dashboard_menus() {
        ?>
			<li>
				<ul class="wpda_table_settings_nested wpda_table_dashboard_menus_settings">
					<h2>
						<?php 
        echo __( 'Dashboard Menus', 'wp-data-access' );
        ?>
						<a href="https://docs.legacy.wpdataaccess.com/docs/dashboard-menus/" target="_blank">
							<span class="dashicons dashicons-editor-help wpda_tooltip"
								  title="<?php 
        echo sprintf( __( 'Help opens in a new tab or window', 'wp-data-access' ), esc_attr( $this->table_name ) );
        ?>"
								  style="cursor:pointer;vertical-align:text-bottom;"></span>
						</a>
					</h2>
					<table class="wpda_table_settings">
						<thead>
							<tr>
								<th>
									<span>
										<?php 
        echo __( 'Menu Name', 'wp-data-access' );
        ?>
									</span>
									<span class="dashicons dashicons-editor-help wpda_tooltip"
										  title="<?php 
        echo __( 'Name of your sub menu item', 'wp-data-access' );
        ?>"
										  style="cursor:pointer;"></span>
								</th>
								<th>
									<span>
										<?php 
        echo __( 'Menu Slug', 'wp-data-access' );
        ?>
									</span>
									<span class="dashicons dashicons-editor-help wpda_tooltip"
										  title="<?php 
        echo __( 'Menu slug of the main menu to which your sub menu should be added', 'wp-data-access' );
        ?>"
										  style="cursor:pointer;"></span>
								</th>
								<th>
									<span>
										<?php 
        echo __( 'Roles Authorized', 'wp-data-access' );
        ?>
									</span>
									<span class="dashicons dashicons-editor-help wpda_tooltip"
										  title="<?php 
        echo __( 'User roles authorized to see sub menu item', 'wp-data-access' );
        ?>"
										  style="cursor:pointer;"></span>
								</th>
								<th style="width:20px"></th>
							</tr>
						</thead>
						<tbody id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_add_dashboard_menu_body">
						<?php 
        $table_menus = WPDA_User_Menus_Model::get_table_menus( $this->table_name, $this->schema_name );
        foreach ( $table_menus as $table_menu ) {
            ?>
							<tr>
								<td>
									<input data-id="menu_name"
										   class="wpda_action_font" type="text"
										   value="<?php 
            echo esc_attr( $table_menu['menu_name'] );
            ?>"
										   style="width:100%"
									>
								</td>
								<td>
									<input data-id="menu_slug"
										   class="wpda_action_font" type="text"
										   value="<?php 
            echo esc_attr( $table_menu['menu_slug'] );
            ?>"
										   style="width:100%"
									>
								</td>
								<td>
									<select data-id="menu_role"
											class="wpda_action_font" multiple size="5"
											style="width:100%"
									>
										<?php 
            global $wp_roles;
            foreach ( $wp_roles->roles as $role => $val ) {
                $selected = ( false !== stripos( $table_menu['menu_role'], $role ) ? 'selected' : '' );
                $role_label = ( isset( $val['name'] ) ? $val['name'] : $role );
                echo "<option value='{$role}' {$selected}>{$role_label}</option>";
                // phpcs:ignore WordPress.Security.EscapeOutput
            }
            ?>
									</select>
									<input type="hidden" class="wpda_action_font wpda_dashboard_menu_id" data-id="menu_id" value="<?php 
            echo esc_attr( $table_menu['menu_id'] );
            ?>"/>
								</td>
								<td style="width:20px">
									<a href="javascript:void(0)"
									   class="dashicons dashicons-trash"
									   onclick="if (confirm('<?php 
            echo __( 'Delete menu?', 'wp-data-access' );
            ?>')) { deleteDashboardMenu(this, '<?php 
            echo esc_attr( $this->rownum );
            ?>') }"
									></a>
								</td>
							</tr>
							<?php 
        }
        ?>
						</tbody>
					</table>
					<br/>
					<div>
						<button type="button"
								id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_save_dashboard_menus"
								class="button button-primary"
						>
							<i class="fas fa-check wpda_icon_on_button"></i>
							<?php 
        echo __( 'Save Dashboard Menus', 'wp-data-access' );
        ?>
						</button>
						<button type="button"
								id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_add_dashboard_menus"
								class="button button-secondary"
						>
							<i class="fas fa-add wpda_icon_on_button"></i>
							Add Dashboard Menu
						</button>
						<script>
							jQuery('#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_save_dashboard_menus').click( function() {
								return submit_dashboard_menus(
									'<?php 
        echo esc_attr( $this->rownum );
        ?>',
									'<?php 
        echo esc_attr( $this->schema_name );
        ?>',
									'<?php 
        echo esc_attr( $this->table_name );
        ?>'
								);
							});

							jQuery('#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_add_dashboard_menus').click( function() {
								jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_add_dashboard_menu_body").append(`
								<tr>
									<td>
										<input data-id="menu_name"
											   class="wpda_action_font" type="text" value=""
											   style="width:100%"
										>
									</td>
									<td>
										<input data-id="menu_slug"
											   class="wpda_action_font" type="text" value=""
											   style="width:100%"
										>
									</td>
									<td>
										<select data-id="menu_role"
												class="wpda_action_font" multiple size="5"
											   	style="width:100%"
										>
											<?php 
        global $wp_roles;
        foreach ( $wp_roles->roles as $role => $val ) {
            $role_label = ( isset( $val['name'] ) ? $val['name'] : $role );
            echo "<option value='{$role}'>{$role_label}</option>";
            // phpcs:ignore WordPress.Security.EscapeOutput
        }
        ?>
										</select>
										<input type="hidden" class="wpda_action_font wpda_dashboard_menu_id" data-id="menu_id" value=""/>
									</td>
									<td style="width:20px">
										<a href="javascript:void(0)"
										   class="dashicons dashicons-trash"
										   onclick="if (confirm('<?php 
        echo __( 'Delete menu?', 'wp-data-access' );
        ?>')) { deleteDashboardMenu(this, '<?php 
        echo esc_attr( $this->rownum );
        ?>') }"
										></a>
									</td>
								</tr>
							`);
							});
						</script>
					</div>
				</ul>
			</li>
			<?php 
    }

    private function settings_tab_dynamic_hyperlinks( $settings_db_custom ) {
        ?>
			<li>
				<ul class="wpda_table_settings_nested wpda_table_dynamic_hyperlinks_settings">
					<h2>
						<?php 
        echo __( 'Dynamic Hyperlinks', 'wp-data-access' );
        ?>
						<a href="https://docs.legacy.wpdataaccess.com/docs/dynamic-hyperlinks/" target="_blank">
							<span class="dashicons dashicons-editor-help wpda_tooltip"
								  title="<?php 
        echo sprintf( __( 'Help opens in a new tab or window', 'wp-data-access' ), esc_attr( $this->table_name ) );
        ?>"
								  style="cursor:pointer"></span>
						</a>
					</h2>
					<table class="wpda_table_settings">
						<thead>
							<tr>
								<th>
									<span>
										<?php 
        echo __( 'Hyperlink Label', 'wp-data-access' );
        ?>
									</span>
								</th>
								<th style="text-align:center">
									<span>
										<?php 
        echo __( '+List?', 'wp-data-access' );
        ?>
									</span>
									<span class="dashicons dashicons-editor-help wpda_tooltip"
										  title="<?php 
        echo __( 'Add hyperlink to list', 'wp-data-access' );
        ?>"
										  style="cursor:pointer;"></span>
								</th>
								<th style="text-align:center">
									<span>
										<?php 
        echo __( '+Form?', 'wp-data-access' );
        ?>
									</span>
									<span class="dashicons dashicons-editor-help wpda_tooltip"
										  title="<?php 
        echo __( 'Add hyperlink to form', 'wp-data-access' );
        ?>"
										  style="cursor:pointer;"></span>
								</th>
								<th style="text-align:center">
									<span>
										<?php 
        echo __( '+Window?', 'wp-data-access' );
        ?>
									</span>
									<span class="dashicons dashicons-editor-help wpda_tooltip"
										  title="<?php 
        echo __( 'Opens URL in a new tab or window', 'wp-data-access' );
        ?>"
										  style="cursor:pointer;"></span>
								</th>
								<th>
									<span>
										<?php 
        echo __( 'HTML', 'wp-data-access' );
        ?>
									</span>
									<span class="dashicons dashicons-editor-help wpda_tooltip"
										  title="<?php 
        echo __( 'Just the URL! For example:

	https://yoursite.com/services.php?name=$$column_name$$

	Variable $$column_name$$ will be replaced with the value of column $$column_name$$ in table `' . esc_attr( $this->table_name ) . '`.', 'wp-data-access' );
        ?>"
										  style="cursor:pointer;"></span>
								</th>
								<th></th>
							</tr>
						<thead>
						<tbody id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_add_hyperlink_body">
						<?php 
        if ( isset( $settings_db_custom->hyperlinks ) && is_array( $settings_db_custom->hyperlinks ) ) {
            foreach ( $settings_db_custom->hyperlinks as $hyperlink ) {
                $hyperlink_label = ( isset( $hyperlink->hyperlink_label ) ? $hyperlink->hyperlink_label : '' );
                $hyperlink_list = ( isset( $hyperlink->hyperlink_list ) && true === $hyperlink->hyperlink_list ? 'checked' : '' );
                $hyperlink_form = ( isset( $hyperlink->hyperlink_form ) && true === $hyperlink->hyperlink_form ? 'checked' : '' );
                $hyperlink_target = ( isset( $hyperlink->hyperlink_target ) && true === $hyperlink->hyperlink_target ? 'checked' : '' );
                $hyperlink_html = ( isset( $hyperlink->hyperlink_html ) ? $hyperlink->hyperlink_html : '' );
                ?>
								<tr>
									<td>
										<input data-id="hyperlink_label"
											   class="wpda_action_font"
											   type="text"
											   value="<?php 
                echo esc_attr( $hyperlink_label );
                ?>"
											   style="width:100%"
										/>
									</td>
									<td style="text-align:center">
										<input data-id="hyperlink_list"
											   class="wpda_action_font"
											   type="checkbox"
											   style="margin-top:7px;"
											<?php 
                echo esc_attr( $hyperlink_list );
                ?>
										/>
									</td>
									<td style="text-align:center">
										<input data-id="hyperlink_form"
											   class="wpda_action_font"
											   type="checkbox"
											   style="margin-top:7px;"
											<?php 
                echo esc_attr( $hyperlink_form );
                ?>
										/>
									</td>
									<td style="text-align:center">
										<input data-id="hyperlink_target"
											   class="wpda_action_font"
											   type="checkbox"
											   style="margin-top:7px;"
											<?php 
                echo esc_attr( $hyperlink_target );
                ?>
										/>
									</td>
									<td>
										<textarea data-id="hyperlink_html"
												  rows="5"
												  class="wpda_action_font"
												  style="width:100%;resize:both;"
										><?php 
                echo urldecode( $hyperlink_html );
                // phpcs:ignore WordPress.Security.EscapeOutput
                ?></textarea>
									</td>
									<td>
										<a href="javascript:void(0)"
										   class="dashicons dashicons-trash"
										   style="margin-top:4px;"
										   onclick="if (confirm('<?php 
                echo __( 'Delete hyperlink?', 'wp-data-access' );
                ?>')) { jQuery(this).closest('tr').remove() }"
										></a>
									</td>
								</tr>
								<?php 
            }
        }
        ?>
						</tbody>
					</table>
					<br/>
					<div>
						<button type="button"
								id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_save_hyperlinks"
								class="button button-primary"
						>
							<i class="fas fa-check wpda_icon_on_button"></i>
							<?php 
        echo __( 'Save Dynamic Hyperlinks', 'wp-data-access' );
        ?>
						</button>
						<button type="button"
								id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_add_hyperlink_link"
								class="button button-secondary"
						>
							<i class="fas fa-add wpda_icon_on_button"></i>
							Add Hyperlink
						</button>
						<script>
							jQuery(function() {
								jQuery('#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_save_hyperlinks').click( function() {
									return submit_hyperlinks(
										'<?php 
        echo esc_attr( $this->rownum );
        ?>',
										'<?php 
        echo esc_attr( $this->schema_name );
        ?>',
										'<?php 
        echo esc_attr( $this->table_name );
        ?>'
									);
								});

								jQuery('#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_add_hyperlink_link').click( function() {
									// Add new row to table
									jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_add_hyperlink_body").append(`
										<tr>
											<td>
												<input data-id="hyperlink_label"
													   class="wpda_action_font"
													   type="text"
													   value=""
													   style="width:100%"
												/>
											</td>
											<td style="text-align:center">
												<input data-id="hyperlink_link"
													   class="wpda_action_font"
													   type="checkbox"
													   style="margin-top:7px;"
												/>
											</td>
											<td style="text-align:center">
												<input data-id="hyperlink_form"
													   class="wpda_action_font"
													   type="checkbox"
													   style="margin-top:7px;"
												/>
											</td>
											<td style="text-align:center">
												<input data-id="hyperlink_target"
													   class="wpda_action_font"
													   type="checkbox"
													   style="margin-top:7px;"
												/>
											</td>
											<td>
												<textarea data-id="hyperlink_html"
														  rows="5"
														  class="wpda_action_font"
														  style="width:100%;resize:both;"
												></textarea>
											</td>
											<td>
												<a href="javascript:void(0)"
												   class="dashicons dashicons-trash"
												   style="margin-top:4px;"
												   onclick="if (confirm('<?php 
        echo __( 'Delete hyperlink %s?', 'wp-data-access' );
        ?>')) { jQuery(this).closest('tr').remove() }"
												></a>
											</td>
										</tr>
									`);
								});
							});
						</script>
					</div>
				</ul>
			</li>
			<?php 
    }

    private function get_table_settings( $rest_api_settings, $rest_api_settings_saved, $action ) {
        if ( isset( $rest_api_settings_saved[$action] ) ) {
            $rest_api_settings['enabled'] = true;
            if ( isset( $rest_api_settings_saved[$action]['methods'] ) ) {
                $rest_api_settings[$action]['methods'] = $rest_api_settings_saved[$action]['methods'];
            }
            if ( isset( $rest_api_settings_saved[$action]['authorization'] ) ) {
                $rest_api_settings[$action]['authorization'] = $rest_api_settings_saved[$action]['authorization'];
            }
            if ( isset( $rest_api_settings_saved[$action]['authorized_roles'] ) ) {
                $rest_api_settings[$action]['authorized_roles'] = $rest_api_settings_saved[$action]['authorized_roles'];
            }
            if ( isset( $rest_api_settings_saved[$action]['authorized_users'] ) ) {
                $rest_api_settings[$action]['authorized_users'] = $rest_api_settings_saved[$action]['authorized_users'];
            }
        }
        return $rest_api_settings;
    }

    private function settings_tab_rest_api() {
        $rest_api_settings = array(
            'enabled' => false,
            'select'  => array(
                'methods'          => [],
                'authorization'    => 'authorized',
                'authorized_roles' => array(),
                'authorized_users' => array(),
            ),
            'insert'  => array(
                'methods'          => [],
                'authorization'    => 'authorized',
                'authorized_roles' => array(),
                'authorized_users' => array(),
            ),
            'update'  => array(
                'methods'          => [],
                'authorization'    => 'authorized',
                'authorized_roles' => array(),
                'authorized_users' => array(),
            ),
            'delete'  => array(
                'methods'          => [],
                'authorization'    => 'authorized',
                'authorized_roles' => array(),
                'authorized_users' => array(),
            ),
        );
        $rest_api_settings_saved = get_option( WPDA_API::WPDA_REST_API_TABLE_ACCESS );
        if ( isset( $rest_api_settings_saved[$this->schema_name][$this->table_name] ) ) {
            $actions = array(
                'select',
                'insert',
                'update',
                'delete'
            );
            foreach ( $actions as $action ) {
                if ( isset( $rest_api_settings_saved[$this->schema_name][$this->table_name][$action] ) ) {
                    $rest_api_settings = $this->get_table_settings( $rest_api_settings, $rest_api_settings_saved[$this->schema_name][$this->table_name], $action );
                }
            }
        }
        ?>
			<li>
				<ul class="wpda_table_settings_nested wpda_table_rest_api_settings" style="position: relative">
					<h2>
						<?php 
        echo __( 'REST API', 'wp-data-access' );
        ?>
					</h2>
					<div style="position: absolute; top: 0; right: 0; font-weight: bold">
						<span class="dashicons dashicons-warning wpda_tooltip"></span>
						THIS FEATURE IS CURRENTLY IN BETA AND CAN BE SUBJECT TO CHANGE
					</div>

					<?php 
        if ( WPDA::is_wp_table( $this->table_name ) ) {
            ?>
						<h4 style="color: red">
							<span class="dashicons dashicons-flag"></span>
							We discourage enabling REST API services on <strong>WordPress</strong> tables!
						</h4>
						<?php 
        }
        ?>

					<p style="margin-top: 25px">
						<label>
							<input type="checkbox"
								   id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api"
									<?php 
        if ( isset( $rest_api_settings['enabled'] ) && true === $rest_api_settings['enabled'] ) {
            echo 'checked';
        }
        ?>
							/>
							Enable <strong>REST API</strong> for table <strong><?php 
        echo esc_attr( $this->table_name );
        ?></strong>
						</label>
					</p>

					<div id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_panel" style="display: none">
						<nav id="rest_api_tab_menu_<?php 
        echo esc_attr( $this->rownum );
        ?>" class="nav-tab-wrapper">
							<a href="javascript:void(0)" class="nav-tab" data-id="select">
								<i class="fa-solid fa-magnifying-glass wpda_settings_icon"></i>
								<span>Select</span>
							</a>
							<a href="javascript:void(0)" class="nav-tab" data-id="insert">
								<i class="fa-solid fa-circle-plus wpda_settings_icon"></i>
								<span>Insert</span>
							</a>
							<a href="javascript:void(0)" class="nav-tab" data-id="update">
								<i class="fa-solid fa-check-circle wpda_settings_icon"></i>
								<span>Update</span>
							</a>
							<a href="javascript:void(0)" class="nav-tab" data-id="delete">
								<i class="fa-solid fa-circle-minus wpda_settings_icon"></i>
								<span>Delete</span>
							</a>
						</nav>
						<?php 
        $this->settings_tab_rest_api_tab( 'select', $rest_api_settings );
        ?>
						<?php 
        $this->settings_tab_rest_api_tab( 'insert', $rest_api_settings );
        ?>
						<?php 
        $this->settings_tab_rest_api_tab( 'update', $rest_api_settings );
        ?>
						<?php 
        $this->settings_tab_rest_api_tab( 'delete', $rest_api_settings );
        ?>
					</div>
					<br/>
					<div>
						<button type="button"
								id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_save_rest_api"
								class="button button-primary"
						>
							<i class="fas fa-check wpda_icon_on_button"></i>
							<?php 
        echo __( 'Save REST API Settings', 'wp-data-access' );
        ?>
						</button>
					</div>
					<style>
						.rest_api_tab {
                            padding: 25px;
                            border-left: 1px solid #ccd0d4;
                            border-right: 1px solid #ccd0d4;
                            border-bottom: 1px solid #ccd0d4;
                            border-bottom-left-radius: 4px;
                            border-bottom-right-radius: 4px;
                            margin: 0;
						}

                        .wpda_rest_api_fieldset {
                            margin: 30px 0 15px 0;
                            padding: 30px !important;
                            padding-bottom: 40px !important;
                        }

                        .wpda_table_settings_rest_api_access {
                            margin: 10px 20px 25px 24px;
                            display: grid;
                            grid-template-columns: repeat(auto-fill,minmax(250px, 1fr));
                            gap: 20px;
                        }

                        .wpda_table_settings_rest_api_access > div * {
                            margin-top: 5px;
                        }

                        .wpda_table_settings_rest_api_title {
                            display: grid;
                            grid-template-columns: auto 20px;
                            justify-content: start;
                        }

                        .wpda_table_settings_rest_api_select {
                            font-size: 90% !important;
                            width: 100%;
                        }
					</style>
					<script>
						function setRestApiDefaultTab() {
							jQuery("#rest_api_tab_menu_<?php 
        echo esc_attr( $this->rownum );
        ?> a:first-child").addClass("nav-tab-active");
						}

						function saveAction(action) {
							let rest_api_methods = [];
							if (jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_" + action + "_http_get").is(":checked")) {
								rest_api_methods.push("GET");
							}
							if (jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_" + action + "_http_post").is(":checked")) {
								rest_api_methods.push("POST");
							}

							let rest_api_settings = {};
							rest_api_settings = {};
							rest_api_settings.methods = rest_api_methods;
							rest_api_settings.authorization = jQuery("input[name=wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_" + action + "]:checked").val();
							rest_api_settings.authorized_roles = jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_roles_" + action + "").val();
							rest_api_settings.authorized_users = jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_users_" + action + "").val();

							return rest_api_settings;
						}

						function copyUrlToClipboard(action, extension = '') {
							let copy_url = new ClipboardJS("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_" + action + "_copy_url" + extension);
							copy_url.on('success', function (e) {
								jQuery.notify('<?php 
        echo __( 'SQL successfully copied to clipboard!' );
        ?>', 'info');
							});
							copy_url.on('error', function (e) {
								jQuery.notify('<?php 
        echo __( 'Could not copy SQL to clipboard!' );
        ?>', 'error');
							});
						}

						jQuery(function() {
							// Navigation
							jQuery("#rest_api_tab_menu_<?php 
        echo esc_attr( $this->rownum );
        ?> a").on("click", function() {
								jQuery(this).closest("div").find(".rest_api_tab").hide();
								jQuery(this).parent().find("a").removeClass("nav-tab-active");

								jQuery("#rest_api_tab_" + jQuery(this).data("id") + "_<?php 
        echo esc_attr( $this->rownum );
        ?>").show();
								jQuery(this).addClass("nav-tab-active");
							});

							// Save settings
							jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_save_rest_api").on("click", function() {
								let rest_api_settings = {};

								if (!jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api").is(":checked")) {
									rest_api_settings.enabled = false;
									rest_api_settings.select = {};
									rest_api_settings.insert = {};
									rest_api_settings.update = {};
									rest_api_settings.delete = {};
								} else {
									rest_api_settings.enabled = true;
									rest_api_settings.select =  saveAction('select');
									rest_api_settings.insert = saveAction('insert');
									rest_api_settings.update = saveAction('update');
									rest_api_settings.delete = saveAction('delete');
								}

								submit_rest_api(
									'<?php 
        echo esc_attr( $this->schema_name );
        ?>',
									'<?php 
        echo esc_attr( $this->table_name );
        ?>',
									rest_api_settings
								);
							});

							// Panel interaction
							jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api").on("change", function() {
								restApiPanelOnChange(
									jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api"),
									jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_panel")
								);
							});
							restApiPanelOnChange(
								jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api"),
								jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_panel")
							);

							// Test select GET and POST endpoints
							jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_select_http_get_test").on("click", function() {
								let data = {
									dbs: "<?php 
        echo esc_attr( $this->schema_name );
        ?>",
									tbl: "<?php 
        echo esc_attr( $this->table_name );
        ?>"
								}
								wpda_rest_api("table/select", data, restApiTestCallbackOk, restApiTestCallbackError, "GET");
							});
							jQuery("#wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_select_http_post_test").on("click", function() {
								let data = {
									dbs: "<?php 
        echo esc_attr( $this->schema_name );
        ?>",
									tbl: "<?php 
        echo esc_attr( $this->table_name );
        ?>"
								}
								wpda_rest_api("table/select", data, restApiTestCallbackOk, restApiTestCallbackError);
							});

							// Add copy to clipboard support
							let actions = ['select', 'insert', 'update', 'delete'];
							for (let i=0; i<actions.length; i++) {
								copyUrlToClipboard(actions[i]);
								if (actions[i]==='select') {
									copyUrlToClipboard(actions[i], '_get');
									copyUrlToClipboard(actions[i], '_datatable');
								}
							}
						});
					</script>
				</ul>
			</li>
			<?php 
    }

    private function settings_tab_rest_api_tab( $tab, $rest_api_settings ) {
        $authorized_roles = array();
        if ( isset( $rest_api_settings[$tab]['authorized_roles'] ) ) {
            $authorized_roles = $rest_api_settings[$tab]['authorized_roles'];
        }
        $authorized_users = array();
        if ( isset( $rest_api_settings[$tab]['authorized_users'] ) ) {
            $authorized_users = $rest_api_settings[$tab]['authorized_users'];
        }
        ?>
			<div id="rest_api_tab_<?php 
        echo esc_attr( $tab );
        ?>_<?php 
        echo esc_attr( $this->rownum );
        ?>"
				 class="rest_api_tab"
				 <?php 
        echo ( 'select' === $tab ? '' : 'style="display: none"' );
        ?>
			>
				<div>
					<h4 style="margin-top: 0">
						Supported HTTP methods
						<span class="dashicons dashicons-editor-help wpda_tooltip" title="Leave unchecked to disable" style="cursor:pointer;vertical-align:bottom;"></span>
					</h4>
					<p>
						<label>
							<input type="checkbox"
								   id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_<?php 
        echo esc_attr( $tab );
        ?>_http_get"
									<?php 
        //phpcs:ignore - 8.1 proof
        echo ( isset( $rest_api_settings[$tab]['methods'] ) && is_array( $rest_api_settings[$tab]['methods'] ) && in_array( 'GET', $rest_api_settings[$tab]['methods'] ) ? 'checked' : '' );
        ?>
							/>
							<strong>GET</strong>
						</label>
						<label style="padding-left: 30px">
							<input type="checkbox"
								   id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_<?php 
        echo esc_attr( $tab );
        ?>_http_post"
									<?php 
        //phpcs:ignore - 8.1 proof
        echo ( isset( $rest_api_settings[$tab]['methods'] ) && is_array( $rest_api_settings[$tab]['methods'] ) && in_array( 'POST', $rest_api_settings[$tab]['methods'] ) ? 'checked' : '' );
        ?>
							/>
							<strong>POST</strong>
						</label>
					</p>
					<h4>
						URLs and parameters
						<a href="<?php 
        echo site_url( '/wp-json/wpda' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>" target="_blank">
							<span class="dashicons dashicons-external wpda_tooltip"
								  style="vertical-align: sub"
								  title="Click to see WP Data Access endpoints and parameters"></span>
						</a>
					</h4>
					<p>
						<?php 
        $select_url = esc_url( get_rest_url( null, "wpda/table/{$tab}" ) );
        echo $select_url;
        ?>
						<a href="javascript:void(0)"
						   id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_<?php 
        echo esc_attr( $tab );
        ?>_copy_url"
						   title="Copy to clipboard"
						   class="wpda_tooltip"
						   style="padding-left: 5px"
						   data-clipboard-text="<?php 
        echo $select_url;
        ?>"
						>
							<i class="dashicons dashicons-clipboard wpda_tooltip"></i>
							<?php 
        if ( 'select' === $tab ) {
            ?>
								(select multiple rows)
								<?php 
        }
        ?>
						</a>
						<?php 
        if ( 'select' === $tab ) {
            echo '<br/>';
            $select_url = esc_url( get_rest_url( null, "wpda/table/get" ) );
            echo $select_url;
            ?>
							<a href="javascript:void(0)"
							   id="wpda_<?php 
            echo esc_attr( $this->rownum );
            ?>_rest_api_<?php 
            echo esc_attr( $tab );
            ?>_copy_url_get"
							   title="Copy to clipboard"
							   class="wpda_tooltip"
							   style="padding-left: 5px"
							   data-clipboard-text="<?php 
            echo $select_url;
            ?>"
							>
								<i class="dashicons dashicons-clipboard wpda_tooltip"></i>
								(select single row)
							</a>
							<?php 
        }
        ?>
					</p>
				</div>
				<div style="margin-top: 20px">
					<h4>
						Authorization
					</h4>
					<label>
						<input type="radio"
							   name="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_<?php 
        echo esc_attr( $tab );
        ?>"
							   value="authorized"
								<?php 
        if ( isset( $rest_api_settings[$tab]['authorization'] ) && 'authorized' === $rest_api_settings[$tab]['authorization'] ) {
            echo 'checked';
        }
        ?>
						/>
						Authorized access only
					</label>
				</div>
				<div class="wpda_table_settings_rest_api_access">
					<div>
						<div class="wpda_table_settings_rest_api_title">
							<span>
								<strong>
									Select roles to grant access
								</strong>
							</span>
							<span class="dashicons dashicons-editor-help wpda_tooltip" title="Hold down the control key to select multiple users" style="cursor:pointer"></span>
						</div>
						<select id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_roles_<?php 
        echo esc_attr( $tab );
        ?>" multiple size="7" class="wpda_table_settings_rest_api_select">
							<?php 
        global $wp_roles;
        $roles = $wp_roles->roles;
        foreach ( $roles as $key => $role ) {
            $selected = ( in_array( $key, $authorized_roles ) ? 'selected' : '' );
            //phpcs:ignore - 8.1 proof
            ?>
								<option value="<?php 
            echo esc_attr( $key );
            ?>" <?php 
            echo esc_attr( $selected );
            ?>>
									<?php 
            echo esc_attr( $role['name'] );
            ?>
								</option>
								<?php 
        }
        ?>
						</select>
					</div>
					<div>
						<div class="wpda_table_settings_rest_api_title">
							<span>
								<strong>
									Select users to grant access
								</strong>
							</span>
							<span class="dashicons dashicons-editor-help wpda_tooltip" title="Hold down the control key to select multiple users" style="cursor:pointer"></span>
						</div>
						<select id="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_users_<?php 
        echo esc_attr( $tab );
        ?>" multiple size="7" class="wpda_table_settings_rest_api_select">
							<?php 
        $users = get_users();
        foreach ( $users as $user ) {
            $selected = ( in_array( $user->data->user_login, $authorized_users ) ? 'selected' : '' );
            //phpcs:ignore - 8.1 proof
            ?>
								<option value="<?php 
            echo esc_attr( $user->data->user_login );
            ?>" <?php 
            echo esc_attr( $selected );
            ?>>
									<?php 
            echo esc_attr( $user->data->display_name );
            ?>
								</option>
								<?php 
        }
        ?>
						</select>
					</div>
				</div>
				<div style="margin-top: 10px">
					<label>
						<input type="radio"
							   name="wpda_<?php 
        echo esc_attr( $this->rownum );
        ?>_rest_api_<?php 
        echo esc_attr( $tab );
        ?>"
							   value="anonymous"
								<?php 
        if ( isset( $rest_api_settings[$tab]['authorization'] ) && 'anonymous' === $rest_api_settings[$tab]['authorization'] ) {
            echo 'checked';
        }
        ?>
						/>
						Anonymous access
					</label>
					<span <?php 
        if ( 'select' !== $tab ) {
            echo 'style="font-weight: bold;"';
        }
        ?>>
						<?php 
        switch ( $tab ) {
            case 'select':
                echo ' - allows non-authorized users to query this table';
                break;
            default:
                echo ' - use this option only if you know what you are doing';
        }
        ?>
					</span>
				</div>
			</div>
			<?php 
    }

    /**
     * Provides content for tab Structure
     */
    protected function tab_structure() {
        ?>
			<table class="widefat striped rows wpda-structure-table">
				<tr>
					<th class="nobr"><strong><?php 
        echo __( 'Column Name', 'wp-data-access' );
        ?></strong></th>
					<th class="nobr"><strong><?php 
        echo __( 'Data Type', 'wp-data-access' );
        ?></strong></th>
					<th><strong><?php 
        echo __( 'Collation', 'wp-data-access' );
        ?></strong></th>
					<th><strong><?php 
        echo __( 'Null?', 'wp-data-access' );
        ?></strong></th>
					<th><strong><?php 
        echo __( 'Key?', 'wp-data-access' );
        ?></strong></th>
					<th class="nobr"><strong><?php 
        echo __( 'Default Value', 'wp-data-access' );
        ?></strong></th>
					<th style="width:80%;"><strong><?php 
        echo __( 'Extra', 'wp-data-access' );
        ?></strong></th>
				</tr>
				<?php 
        foreach ( $this->table_structure as $column ) {
            ?>
					<tr>
						<td class="nobr"><?php 
            echo esc_attr( $column['Field'] );
            ?></td>
						<td class="nobr"><?php 
            echo esc_attr( $column['Type'] );
            ?></td>
						<td class="nobr"><?php 
            echo esc_attr( $column['Collation'] );
            ?></td>
						<td class="nobr"><?php 
            echo esc_attr( $column['Null'] );
            ?></td>
						<td class="nobr"><?php 
            echo esc_attr( $column['Key'] );
            ?></td>
						<td class="nobr"><?php 
            echo esc_attr( $column['Default'] );
            ?></td>
						<td><?php 
            echo esc_attr( $column['Extra'] );
            ?></td>
					</tr>
					<?php 
        }
        ?>
			</table>
			<?php 
    }

    protected function tab_foreign_keys() {
        if ( false !== stripos( $this->create_table_stmt_orig, 'ENGINE=InnoDB' ) ) {
            ?>
				<table class="widefat striped rows wpda-structure-table">
					<tr>
						<th class="nobr">
							<strong><?php 
            echo __( 'Constraint Name', 'wp-data-access' );
            ?></strong>
						</th>
						<th class="nobr">
							<strong><?php 
            echo __( 'Column Name', 'wp-data-access' );
            ?></strong>
						</th>
						<th class="nobr">
							<strong><?php 
            echo __( 'Referenced Table Name', 'wp-data-access' );
            ?></strong>
						</th>
						<th class="nobr" style="width:80%;">
							<strong><?php 
            echo __( 'Referenced Column Name', 'wp-data-access' );
            ?></strong>
						</th>
					</tr>
					<?php 
            if ( 0 === count( $this->foreign_keys ) ) {
                //phpcs:ignore - 8.1 proof
                echo '<tr><td colspan="4">' . __( 'No foreign keys defined for this table', 'wp-data-access' ) . '</td></tr>';
            }
            $constraint_name = '';
            foreach ( $this->foreign_keys as $foreign_key ) {
                $show_item = $constraint_name !== $foreign_key['constraint_name'];
                ?>
						<tr>
							<td class="nobr">
								<?php 
                echo ( $show_item ? esc_attr( $foreign_key['constraint_name'] ) : '' );
                ?>
							</td>
							<td class="nobr">
								<?php 
                echo esc_attr( $foreign_key['column_name'] );
                ?>
							</td>
							<td class="nobr">
								<?php 
                echo ( $show_item ? esc_attr( $foreign_key['referenced_table_name'] ) : '' );
                ?>
							</td>
							<td class="nobr">
								<?php 
                echo esc_attr( $foreign_key['referenced_column_name'] );
                ?>
							</td>
						</tr>
						<?php 
                $constraint_name = $foreign_key['constraint_name'];
            }
            ?>
				</table>
				<?php 
        }
    }

    /**
     * Provides content for tab Indexes
     */
    protected function tab_index() {
        ?>
			<table class="widefat striped rows wpda-structure-table">
				<tr>
					<th class="nobr"><strong><?php 
        echo __( 'Index Name', 'wp-data-access' );
        ?></strong></th>
					<th><strong><?php 
        echo __( 'Unique?', 'wp-data-access' );
        ?></strong></th>
					<th><strong>#</strong></th>
					<th class="nobr"><strong><?php 
        echo __( 'Column Name', 'wp-data-access' );
        ?></strong></th>
					<th><strong><?php 
        echo __( 'Collation', 'wp-data-access' );
        ?></strong></th>
					<th class="nobr"><strong><?php 
        echo __( 'Index Prefix?', 'wp-data-access' );
        ?></strong></th>
					<th><strong><?php 
        echo __( 'Null?', 'wp-data-access' );
        ?></strong></th>
					<th class="nobr" style="width:80%;">
						<strong><?php 
        echo __( 'Index Type', 'wp-data-access' );
        ?></strong></th>
				</tr>
				<?php 
        if ( 0 === count( (array) $this->indexes ) ) {
            //phpcs:ignore - 8.1 proof
            echo '<tr><td colspan="8">' . __( 'No indexes defined for this table', 'wp-data-access' ) . '</td></tr>';
        }
        $current_index_name = '';
        foreach ( $this->indexes as $index ) {
            if ( $current_index_name !== $index['Key_name'] ) {
                $current_index_name = esc_attr( $index['Key_name'] );
                $new_index = true;
            } else {
                $new_index = false;
            }
            ?>
					<tr>
						<td class="nobr">
							<?php 
            if ( $new_index ) {
                echo esc_attr( $index['Key_name'] );
            }
            ?>
						</td>
						<td class="nobr">
							<?php 
            if ( $new_index ) {
                echo ( '0' === $index['Non_unique'] ? 'Yes' : 'No' );
            }
            ?>
						</td>
						<td class="nobr">
							<?php 
            echo esc_attr( $index['Seq_in_index'] );
            ?>
						</td>
						<td class="nobr">
							<?php 
            echo esc_attr( $index['Column_name'] );
            ?>
						</td>
						<td class="nobr">
							<?php 
            echo ( 'A' === $index['Collation'] ? 'Ascending' : 'Not sorted' );
            ?>
						</td>
						<td class="nobr">
							<?php 
            echo esc_attr( $index['Sub_part'] );
            ?>
						</td>
						<td class="nobr">
							<?php 
            echo ( '' === $index['Null'] ? 'NO' : esc_attr( $index['Null'] ) );
            ?>
						</td>
						<td><?php 
            echo esc_attr( $index['Index_type'] );
            ?></td>
					</tr>
					<?php 
        }
        ?>
			</table>
			<?php 
    }

    /**
     * Provides content for tab SQL
     */
    protected function tab_sql() {
        ?>
			<table class="widefat striped rows wpda-structure-table">
				<tr>
					<td>
						<?php 
        echo wp_kses( $this->create_table_stmt, array(
            'br' => array(),
        ) );
        ?>
					</td>
					<td style="text-align: right;">
						<a id="button-copy-clipboard-<?php 
        echo esc_attr( $this->rownum );
        ?>"
						   href="javascript:void(0)"
						   class="button button-primary"
						   data-clipboard-text="<?php 
        echo $this->create_table_stmt_orig;
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>"
						>
							<i class="fas fa-clipboard wpda_icon_on_button"></i>
							<?php 
        echo __( 'Copy to clipboard', 'wp-data-access' );
        ?>
						</a>
					</td>
				</tr>
			</table>
			<?php 
    }

    /**
     * Provides content for tab Actions
     */
    protected function tab_actions() {
        $is_pds = false;
        ?>
			<table class="widefat striped rows wpda-structure-table">
				<?php 
        $this->tab_export();
        if ( $this->is_wp_table === false && ('Table' === $this->dbo_type || 'View' === $this->dbo_type) && !$is_pds ) {
            $this->tab_rename();
        }
        if ( 'Table' === $this->dbo_type ) {
            $this->tab_copy();
        }
        if ( 'Table' === $this->dbo_type && $this->is_wp_table === false && !$is_pds ) {
            $this->tab_truncate();
        }
        if ( $this->is_wp_table === false && ('Table' === $this->dbo_type || 'View' === $this->dbo_type) ) {
            $this->tab_drop();
        }
        if ( 'Table' === $this->dbo_type && !$is_pds ) {
            $this->tab_optimize();
        }
        if ( 'Table' === $this->dbo_type ) {
            $this->tab_alter();
        }
        if ( $is_pds ) {
            // Premium data service connection
            $this->pds();
        }
        ?>
			</table>
			<?php 
    }

    protected function pds() {
    }

    /**
     * Provides content for Export action
     */
    protected function tab_export() {
        $wp_nonce_action = 'wpda-export-' . json_encode( $this->table_name );
        $wp_nonce = wp_create_nonce( $wp_nonce_action );
        $sql_option_prefix = '';
        $export_variable_prefix_option = false;
        if ( 'Table' === $this->dbo_type ) {
            $export_variable_prefix_option = 'on' === WPDA::get_option( WPDA::OPTION_BE_EXPORT_VARIABLE_PREFIX );
        }
        ?>
			<tr>
				<td style="box-sizing:border-box;text-align:center;white-space:nowrap;width:150px;vertical-align:middle;">
					<a href="javascript:void(0)"
					   target="_blank"
					   class="button button-primary"
					   onclick="return wpda_export_button_<?php 
        echo esc_attr( $this->rownum );
        ?>()"
					   style="display:block;"
					>
						<?php 
        echo __( 'EXPORT', 'wp-data-access' );
        ?>
					</a>
				</td>
				<td style="vertical-align:middle;">
					<span><?php 
        echo __( 'Export', 'wp-data-access' );
        ?> <strong><?php 
        echo __( 'table', 'wp-data-access' );
        ?> `<?php 
        echo esc_attr( $this->table_name );
        ?>`</strong> <?php 
        echo __( 'to', 'wp-data-access' );
        ?>: </span>
					<select id="format_type_<?php 
        echo esc_attr( $this->rownum );
        ?>"
							name="format_type"
							class="wpda_action_font"
							style="height:inherit;padding-top:3px;">
						<option value="sql" <?php 
        echo ( $export_variable_prefix_option ? '' : 'selected' );
        ?>>SQL</option>
						<?php 
        global $wpdb;
        if ( stripos( $this->table_name, $wpdb->prefix ) === 0 ) {
            // Add SQL + prefix export
            ?>
							<option value="sqlpre" <?php 
            echo ( $export_variable_prefix_option ? 'selected' : '' );
            ?>>SQL (add WP prefix)</option>
							<?php 
        }
        ?>
						<option value="xml">XML</option>
						<option value="json">JSON</option>
						<option value="excel">Excel</option>
						<option value="csv">CSV</option>
					</select>
					<label style="vertical-align:text-top;">
						<input id="include_table_settings_<?php 
        echo esc_attr( $this->rownum );
        ?>"
							   type="checkbox"
							   class="wpda_action_font"
						>
						<?php 
        echo __( 'Include table settings (SQL only)', 'wp-data-access' );
        ?>&nbsp;
					</label>
					<script type="text/javascript">
						function wpda_export_button_<?php 
        echo esc_attr( $this->rownum );
        ?>() {
							<?php 
        $check_export_access = 'true';
        if ( 'on' === WPDA::get_option( WPDA::OPTION_BE_CONFIRM_EXPORT ) ) {
            $check_export_access = "confirm('Export table {$this->table_name}?')";
        }
        ?>
							if (<?php 
        echo $check_export_access;
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>) {
								wpda_table_export(
									'<?php 
        echo esc_attr( $this->schema_name );
        ?>',
									'<?php 
        echo esc_attr( $this->table_name );
        ?>',
									'<?php 
        echo esc_attr( $wp_nonce );
        ?>',
									jQuery('#format_type_<?php 
        echo esc_attr( $this->rownum );
        ?>').val(),
									jQuery('#include_table_settings_<?php 
        echo esc_attr( $this->rownum );
        ?>').prop('checked') ? 'on' : 'off'
								);
							}
							return false;
						}
					</script>
				</td>
			</tr>
			<?php 
    }

    /**
     * Provides content for Rename action
     */
    protected function tab_rename() {
        $wp_nonce_action_rename = "wpda-rename-{$this->table_name}";
        $wp_nonce_rename = wp_create_nonce( $wp_nonce_action_rename );
        $rename_table_form_id = 'rename_table_form_' . esc_attr( $this->table_name );
        $rename_table_form = '<form' . " id='" . $rename_table_form_id . "'" . " action='?page=" . esc_attr( \WP_Data_Access_Admin::PAGE_MAIN ) . "'" . " method='post'>" . "<input type='hidden' name='action' value='rename-table' />" . "<input type='hidden' name='rename_table_name_old' value='" . esc_attr( $this->table_name ) . "' />" . "<input type='hidden' name='rename_table_name_new' id='rename_table_name_" . esc_attr( $this->rownum ) . "' value='' />" . "<input type='hidden' name='_wpnonce' value='" . esc_attr( $wp_nonce_rename ) . "' />" . '</form>';
        ?>
			<tr>
				<td style="box-sizing:border-box;text-align:center;white-space:nowrap;width:150px;vertical-align:middle;">
					<script type='text/javascript'>
						jQuery("#wpda_invisible_container").append("<?php 
        echo $rename_table_form;
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>");
					</script>
					<a href="javascript:void(0)"
					   class="button button-primary"
					   onclick="if (jQuery('#rename-table-from-<?php 
        echo esc_attr( $this->rownum );
        ?>').val()==='') { alert('<?php 
        echo __( 'Please enter a valid table name', 'wp-data-access' );
        ?>'); return false; } if (confirm('<?php 
        echo __( 'Rename', 'wp-data-access' ) . ' ' . esc_attr( strtolower( $this->dbo_type ) ) . '?';
        ?>')) { jQuery('#rename_table_name_<?php 
        echo esc_attr( $this->rownum );
        ?>').val(jQuery('#rename-table-from-<?php 
        echo esc_attr( $this->rownum );
        ?>').val()); jQuery('#<?php 
        echo esc_attr( $rename_table_form_id );
        ?>').submit(); }"
					   style="display:block;"
					>
						<?php 
        echo __( 'RENAME', 'wp-data-access' );
        ?>
					</a>
				</td>
				<td style="vertical-align:middle;">
					<?php 
        echo __( 'Rename', 'wp-data-access' );
        ?>
					<strong><?php 
        echo esc_attr( strtolower( $this->dbo_type ) );
        ?>
						`<?php 
        echo esc_attr( $this->table_name );
        ?>`</strong> to:
					<input type="text" id="rename-table-from-<?php 
        echo esc_attr( $this->rownum );
        ?>" value=""
						   class="wpda_action_font">
				</td>
			</tr>
			<?php 
    }

    /**
     * Provides content for Copy action
     */
    protected function tab_copy() {
        // Add copy form.
        $wp_nonce_action_copy = "wpda-copy-{$this->table_name}";
        $wp_nonce_copy = wp_create_nonce( $wp_nonce_action_copy );
        $copy_table_form_id = 'copy_table_form_' . esc_attr( $this->table_name );
        $copy_table_form = '<form' . " id='{$copy_table_form_id}'" . " action='?page=" . esc_attr( \WP_Data_Access_Admin::PAGE_MAIN ) . "'" . " method='post'>" . "<input type='hidden' name='action' value='copy-table' />" . "<input type='hidden' name='copy_schema_name_src' value='" . esc_attr( $this->schema_name ) . "' />" . "<input type='hidden' name='copy_table_name_src' value='" . esc_attr( $this->table_name ) . "' />" . "<input type='hidden' name='copy_schema_name_dst' id='copy_schema_name_" . esc_attr( $this->table_name ) . "' value='' />" . "<input type='hidden' name='copy_table_name_dst' id='copy_table_name_" . esc_attr( $this->table_name ) . "' value='' />" . "<input type='checkbox' name='copy-table-data' id='copy_table_data_" . esc_attr( $this->rownum ) . "' checked />" . "<input type='hidden' name='_wpnonce' value='" . esc_attr( $wp_nonce_copy ) . "' />" . '</form>';
        ?>
			<tr>
				<td style="box-sizing:border-box;text-align:center;white-space:nowrap;width:150px;vertical-align:middle;">
					<script type='text/javascript'>
						jQuery("#wpda_invisible_container").append("<?php 
        echo $copy_table_form;
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>");
					</script>
					<a href="javascript:void(0)"
					   class="button button-primary"
					   onclick="copyTable('<?php 
        echo esc_attr( $this->rownum );
        ?>', '<?php 
        echo esc_attr( $this->dbo_type );
        ?>', '<?php 
        echo esc_attr( $this->table_name );
        ?>', '<?php 
        echo esc_attr( $copy_table_form_id );
        ?>')"
					   style="display:block;"
					>
						<?php 
        echo __( 'COPY', 'wp-data-access' );
        ?>
					</a>
				</td>
				<td style="vertical-align:middle;">
					<?php 
        echo __( 'Copy', 'wp-data-access' );
        ?>
					<strong><?php 
        echo esc_attr( strtolower( $this->dbo_type ) );
        ?>
						`<?php 
        echo esc_attr( $this->table_name );
        ?>
						`</strong> <?php 
        echo __( 'to', 'wp-data-access' );
        ?>:
					<select id="copy-schema-from-<?php 
        echo esc_attr( $this->rownum );
        ?>">
						<?php 
        // Get available databases.
        $schema_names = WPDA_Dictionary_Lists::get_db_schemas();
        global $wpdb;
        foreach ( $schema_names as $schema_name ) {
            $selected = ( $schema_name['schema_name'] === $this->schema_name ? 'selected' : '' );
            $database = ( $schema_name['schema_name'] === $wpdb->dbname ? "WordPress database ({$schema_name['schema_name']})" : $schema_name['schema_name'] );
            echo '<option value="' . esc_attr( $schema_name['schema_name'] ) . '" ' . $selected . '>' . esc_attr( $database ) . '</option>';
        }
        ?>
					</select>
					<input type="text" id="copy-table-from-<?php 
        echo esc_attr( $this->rownum );
        ?>" value=""
						   class="wpda_action_font">
					<label style="vertical-align:baseline">
						<input type="checkbox"
							   checked
							   onclick="jQuery('#copy_table_data_<?php 
        echo esc_attr( $this->rownum );
        ?>').prop('checked', jQuery(this).is(':checked'));"
							   class="wpda_action_font"
						>
						<?php 
        echo __( 'Copy data', 'wp-data-access' );
        ?>
					</label>
				</td>
			</tr>
			<?php 
    }

    /**
     * Provides content for Truncate action
     */
    protected function tab_truncate() {
        $wp_nonce_action_truncate = "wpda-truncate-{$this->table_name}";
        $wp_nonce_truncate = wp_create_nonce( $wp_nonce_action_truncate );
        $truncate_table_form_id = 'truncate_table_form_' . esc_attr( $this->table_name );
        $truncate_table_form = '<form' . " id='{$truncate_table_form_id}'" . " action='?page=" . esc_attr( \WP_Data_Access_Admin::PAGE_MAIN ) . "'" . " method='post'>" . "<input type='hidden' name='action' value='truncate' />" . "<input type='hidden' name='truncate_table_name' value='" . esc_attr( $this->table_name ) . "' />" . "<input type='hidden' name='_wpnonce' value='" . esc_attr( $wp_nonce_truncate ) . "' />" . '</form>';
        ?>
			<tr>
				<td style="box-sizing:border-box;text-align:center;white-space:nowrap;width:150px;vertical-align:middle;">
					<script type='text/javascript'>
						jQuery("#wpda_invisible_container").append("<?php 
        echo $truncate_table_form;
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>");
					</script>
					<a href="javascript:void(0)"
					   class="button button-primary"
					   onclick="if (confirm('<?php 
        echo __( 'Truncate table?', 'wp-data-access' );
        ?>')) { jQuery('#<?php 
        echo esc_attr( $truncate_table_form_id );
        ?>').submit(); }"
					   style="display:block;"
					>
						<?php 
        echo __( 'TRUNCATE', 'wp-data-access' );
        ?>
					</a>
				</td>
				<td style="vertical-align:middle;">
					<?php 
        echo __( 'Permanently delete all data from', 'wp-data-access' );
        ?>
					<strong><?php 
        echo esc_attr( strtolower( $this->dbo_type ) );
        ?>
						`<?php 
        echo esc_attr( $this->table_name );
        ?>`</strong>
					.<br/>
					<strong><?php 
        echo __( 'This action cannot be undone!', 'wp-data-access' );
        ?></strong>
				</td>
			</tr>
			<?php 
    }

    /**
     * Provides content for Drop action
     */
    protected function tab_drop() {
        $wp_nonce_action_drop = "wpda-drop-{$this->table_name}";
        $wp_nonce_drop = wp_create_nonce( $wp_nonce_action_drop );
        if ( 'View' === $this->dbo_type ) {
            $msg_drop = __( 'Drop view?', 'wp-data-access' );
        } else {
            $msg_drop = __( 'Drop table?', 'wp-data-access' );
        }
        $drop_table_form_id = 'drop_table_form_' . esc_attr( $this->table_name );
        $drop_table_form = '<form' . " id='{$drop_table_form_id}'" . " action='?page=" . esc_attr( \WP_Data_Access_Admin::PAGE_MAIN ) . "'" . " method='post'>" . "<input type='hidden' name='action' value='drop' />" . "<input type='hidden' name='drop_table_name' value='" . esc_attr( $this->table_name ) . "' />" . "<input type='hidden' name='_wpnonce' value='" . esc_attr( $wp_nonce_drop ) . "' />" . '</form>';
        ?>
			<tr>
				<td style="box-sizing:border-box;text-align:center;white-space:nowrap;width:150px;vertical-align:middle;">
					<script type='text/javascript'>
						jQuery("#wpda_invisible_container").append("<?php 
        echo $drop_table_form;
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>");
					</script>
					<a href="javascript:void(0)"
					   class="button button-primary"
					   onclick="if (confirm('<?php 
        echo $msg_drop;
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>')) { jQuery('#<?php 
        echo esc_attr( $drop_table_form_id );
        ?>').submit(); }"
					   style="display:block;"
					>
						<?php 
        echo __( 'DROP', 'wp-data-access' );
        ?>
					</a>
				</td>
				<td style="vertical-align:middle;">
					<?php 
        echo __( 'Permanently delete', 'wp-data-access' );
        ?>
					<strong><?php 
        echo esc_attr( strtolower( $this->dbo_type ) );
        ?>
						`<?php 
        echo esc_attr( $this->table_name );
        ?>`</strong>
					<?php 
        echo __( 'and all table data from the database.', 'wp-data-access' );
        ?><br/>
					<strong><?php 
        echo __( 'This action cannot be undone!', 'wp-data-access' );
        ?></strong>
				</td>
			</tr>
			<?php 
    }

    /**
     * Provides content for Optimize action
     *
     * Data_length
     * Index_length
     * Data_free
     */
    protected function tab_optimize() {
        $wpdadb = WPDADB::get_db_connection( $this->schema_name );
        if ( null === $wpdadb ) {
            wp_die( sprintf( __( 'ERROR - Remote database %s not available', 'wp-data-access' ), esc_attr( $this->schema_name ) ) );
        }
        $table_structure = $wpdadb->get_row( $wpdadb->prepare( 'show table status like %s', $this->table_name ) );
        $query_innodb_file_per_table = $wpdadb->get_row( "show session variables like 'innodb_file_per_table'" );
        if ( !empty( $query_innodb_file_per_table ) ) {
            $innodb_file_per_table = 'ON' === $query_innodb_file_per_table->Value;
        } else {
            $innodb_file_per_table = true;
        }
        if ( 'InnoDB' === $table_structure->Engine && !$innodb_file_per_table ) {
            return;
        }
        $consider_optimize = $table_structure->Data_free > 0 && $table_structure->Data_length > 0 && $table_structure->Data_free / $table_structure->Data_length > 0.2;
        $wp_nonce_action_optimize = "wpda-optimize-{$this->table_name}";
        $wp_nonce_optimize = wp_create_nonce( $wp_nonce_action_optimize );
        $optimize_table_form_id = 'optimize_table_form_' . esc_attr( $this->table_name );
        $optimize_table_form = '<form' . " id='{$optimize_table_form_id}'" . " action='?page=" . esc_attr( \WP_Data_Access_Admin::PAGE_MAIN ) . "'" . " method='post'>" . "<input type='hidden' name='action' value='optimize-table' />" . "<input type='hidden' name='optimize_table_name' value='" . esc_attr( $this->table_name ) . "' />" . "<input type='hidden' name='_wpnonce' value='" . esc_attr( $wp_nonce_optimize ) . "' />" . '</form>';
        $msg_optimize = __( 'Optimize table?', 'wp-data-access' );
        ?>
			<tr>
				<td style="box-sizing:border-box;text-align:center;white-space:nowrap;width:150px;vertical-align:middle;">
					<script type='text/javascript'>
						jQuery("#wpda_invisible_container").append("<?php 
        echo $optimize_table_form;
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>");
					</script>
					<a href="javascript:void(0)"
					   class="button button-primary"
					   onclick="if (confirm('<?php 
        echo $msg_optimize;
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>')) { jQuery('#<?php 
        echo esc_attr( $optimize_table_form_id );
        ?>').submit(); }"
					   style="display:block;
					   <?php 
        if ( !$consider_optimize ) {
            echo 'opacity:0.5;';
        }
        ?>
					   "
					>
						<?php 
        echo __( 'OPTIMIZE', 'wp-data-access' );
        ?>
					</a>
				</td>
				<td style="vertical-align:middle;
				<?php 
        if ( !$consider_optimize ) {
            echo 'opacity:0.5;';
        }
        ?>
				">
					<?php 
        echo __( 'Optimize', 'wp-data-access' );
        ?>
					<strong><?php 
        echo esc_attr( strtolower( $this->dbo_type ) );
        ?>
						`<?php 
        echo esc_attr( $this->table_name );
        ?>`</strong>.<br/>
					<?php 
        if ( $consider_optimize ) {
            ?>
						<strong><?php 
            echo __( 'MySQL locks the table during the time OPTIMIZE TABLE is running!', 'wp-data-access' );
            ?></strong>
						<?php 
        } else {
            ?>
						<strong><?php 
            echo __( 'Table optimization not considered useful! But you can...', 'wp-data-access' );
            ?></strong>
						<?php 
        }
        ?>
				</td>
			</tr>
			<?php 
    }

    /**
     * Provides content for Alter action
     */
    protected function tab_alter() {
        $wp_nonce_action_alter = "wpda-alter-{$this->table_name}";
        $wp_nonce_alter = wp_create_nonce( $wp_nonce_action_alter );
        $alter_table_form_id = 'alter_table_form_' . esc_attr( $this->table_name );
        $alter_table_form = '<form' . " id='{$alter_table_form_id}'" . " action='?page=" . esc_attr( \WP_Data_Access_Admin::PAGE_DESIGNER ) . "'" . " method='post'>" . "<input type='hidden' name='action' value='edit' />" . "<input type='hidden' name='action2' value='init' />" . "<input type='hidden' name='wpda_schema_name' value='" . esc_attr( $this->schema_name ) . "' />" . "<input type='hidden' name='wpda_schema_name_re' value='" . esc_attr( $this->schema_name ) . "' />" . "<input type='hidden' name='wpda_table_name' value='" . esc_attr( $this->table_name ) . "' />" . "<input type='hidden' name='wpda_table_name_re' value='" . esc_attr( $this->table_name ) . "' />" . "<input type='hidden' name='_wpnonce' value='" . esc_attr( $wp_nonce_alter ) . "' />" . "<input type='hidden' name='page_number' value='1' />" . "<input type='hidden' name='caller' value='dataexplorer' />" . '</form>';
        ?>
			<tr>
				<td style="box-sizing:border-box;text-align:center;white-space:nowrap;width:150px;vertical-align:middle;">
					<script type='text/javascript'>
						jQuery("#wpda_invisible_container").append("<?php 
        echo $alter_table_form;
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>");
					</script>
					<a href="javascript:void(0)"
					   class="button button-primary"
					   onclick="if (confirm('<?php 
        echo __( 'Alter table?', 'wp-data-access' );
        ?>')) { jQuery('#<?php 
        echo esc_attr( $alter_table_form_id );
        ?>').submit(); }"
					   style="display:block;"
					>
						<?php 
        echo __( 'ALTER', 'wp-data-access' );
        ?>
					</a>
				</td>
				<td style="vertical-align:middle;">
					<?php 
        echo __( 'Loads', 'wp-data-access' );
        ?>
					<strong><?php 
        echo esc_attr( strtolower( $this->dbo_type ) );
        ?>
						`<?php 
        echo esc_attr( $this->table_name );
        ?>`</strong>
					<?php 
        echo __( 'into the Data Designer.', 'wp-data-access' );
        ?>
				</td>
			</tr>
			<?php 
    }

}

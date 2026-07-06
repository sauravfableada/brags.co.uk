<?php

namespace WPDataAccess\Utilities;

use WPDataAccess\API\WPDA_Tree;
use WPDataAccess\Connection\WPDADB;
use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Access;
use WPDataAccess\WPDA;
class WPDA_Remote_Database {
    private $page = null;

    private $user_can_create_db = false;

    private $rdb = array();

    public function __construct() {
        wp_enqueue_style( 'wp-jquery-ui-core' );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        wp_enqueue_style( 'wp-jquery-ui-sortable' );
        wp_enqueue_style( 'wp-jquery-ui-tabs' );
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-dialog' );
        wp_enqueue_script( 'jquery-ui-tabs' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'jquery-ui-tooltip' );
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        if ( isset( $_REQUEST['page'] ) ) {
            $this->page = sanitize_text_field( wp_unslash( $_REQUEST['page'] ) );
            // input var okay.
        }
        $this->user_can_create_db = WPDA_Dictionary_Access::can_create_db();
        if ( WPDA::current_user_is_admin() ) {
            if ( isset( $_REQUEST['action'] ) ) {
                // Process create, drop and alter database actions.
                if ( 'create_db' === $_REQUEST['action'] ) {
                    $this->create_db();
                } elseif ( 'drop_db' === $_REQUEST['action'] ) {
                    $this->drop_db();
                } elseif ( 'edit_db' === $_REQUEST['action'] ) {
                    $this->edit_db();
                } elseif ( 'toggle_db' === $_REQUEST['action'] ) {
                    $this->toggle_db();
                }
            }
        }
    }

    private function create_db() {
        if ( !$this->check_wpnonce( 'wpda-create-db-from-data-explorer-' . WPDA::get_current_user_login(), '_wpnoncecreatedb' ) ) {
            return;
        }
        if ( isset( $_REQUEST['database_location'] ) && 'local' === $_REQUEST['database_location'] ) {
            // Add local database
            if ( !isset( $_REQUEST['local_database'] ) ) {
                $msg = new WPDA_Message_Box(array(
                    'message_text'           => sprintf( __( 'Cannot create database [missing argument]', 'wp-data-access' ) ),
                    'message_type'           => 'error',
                    'message_is_dismissible' => false,
                ));
                $msg->box();
                return;
            }
            $database = str_replace( '`', '', sanitize_text_field( wp_unslash( $_REQUEST['local_database'] ) ) );
            // input var okay.
            global $wpdb;
            if ( false === $wpdb->query( $wpdb->prepare( 
                'create database `%1s`',
                // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
                array(WPDA::remove_backticks( $database ))
             ) ) ) {
                // db call ok; no-cache ok.
                $msg = new WPDA_Message_Box(array(
                    'message_text'           => sprintf( __( 'Error creating database `%s`', 'wp-data-access' ), $database ),
                    'message_type'           => 'error',
                    'message_is_dismissible' => false,
                ));
                $msg->box();
            } else {
                $msg = new WPDA_Message_Box(array(
                    'message_text' => sprintf( __( 'Database `%s` created', 'wp-data-access' ), $database ),
                ));
                $msg->box();
                $this->switch_schema_name = $database;
            }
        } else {
            // Add remote database
            $database = ( isset( $_REQUEST['remote_database'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['remote_database'] ) ) : '' );
            // input var okay.
            if ( false !== WPDADB::get_remote_database( $database ) ) {
                $msg = new WPDA_Message_Box(array(
                    'message_text'           => sprintf( __( 'Remote database connection already exists', 'wp-data-access' ) ),
                    'message_type'           => 'error',
                    'message_is_dismissible' => false,
                ));
                $msg->box();
                return;
            }
            $host = ( isset( $_REQUEST['remote_host'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['remote_host'] ) ) : '' );
            // input var okay.
            $user = ( isset( $_REQUEST['remote_user'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['remote_user'] ) ) : '' );
            // input var okay.
            $passwd = ( isset( $_REQUEST['remote_passwd'] ) ? wp_unslash( $_REQUEST['remote_passwd'] ) : '' );
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $port = ( isset( $_REQUEST['remote_port'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['remote_port'] ) ) : '' );
            // input var okay.
            $schema = ( isset( $_REQUEST['remote_schema'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['remote_schema'] ) ) : '' );
            // input var okay.
            $ssl = ( isset( $_REQUEST['remote_ssl'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['remote_ssl'] ) ) : 'off' );
            // input var okay.
            $ssl_key = ( isset( $_REQUEST['remote_client_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['remote_client_key'] ) ) : '' );
            // input var okay.
            $ssl_cert = ( isset( $_REQUEST['remote_client_certificate'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['remote_client_certificate'] ) ) : '' );
            // input var okay.
            $ssl_ca = ( isset( $_REQUEST['remote_ca_certificate'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['remote_ca_certificate'] ) ) : '' );
            // input var okay.
            $ssl_path = ( isset( $_REQUEST['remote_certificate_path'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['remote_certificate_path'] ) ) : '' );
            // input var okay.
            $ssl_cipher = ( isset( $_REQUEST['remote_specified_cipher'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['remote_specified_cipher'] ) ) : '' );
            // input var okay.
            if ( '' === $database || '' === $host || '' === $user || '' === $schema ) {
                $msg = new WPDA_Message_Box(array(
                    'message_text'           => sprintf( __( 'Cannot add remote database connection [missing argument]', 'wp-data-access' ) ),
                    'message_type'           => 'error',
                    'message_is_dismissible' => false,
                ));
                $msg->box();
                return;
            }
            if ( 'rdb:' === $database ) {
                $msg = new WPDA_Message_Box(array(
                    'message_text'           => sprintf( __( 'Invalid database name [enter a valid database name, for example rdb:remotedb]', 'wp-data-access' ) ),
                    'message_type'           => 'error',
                    'message_is_dismissible' => false,
                ));
                $msg->box();
                return;
            }
            if ( !WPDADB::add_remote_database(
                $database,
                $host,
                $user,
                $passwd,
                $port,
                $schema,
                $ssl,
                $ssl_key,
                $ssl_cert,
                $ssl_ca,
                $ssl_path,
                $ssl_cipher
            ) ) {
                $msg = new WPDA_Message_Box(array(
                    'message_text'           => sprintf( __( 'Cannot add remote database connection', 'wp-data-access' ) ),
                    'message_type'           => 'error',
                    'message_is_dismissible' => false,
                ));
                $msg->box();
            } else {
                $msg = new WPDA_Message_Box(array(
                    'message_text' => sprintf( __( 'Remote database connection `%s` added', 'wp-data-access' ), $database ),
                ));
                $msg->box();
                $this->switch_schema_name = $database;
            }
        }
    }

    private function toggle_db() {
        if ( !$this->check_wpnonce( 'wpda-toggle-db-from-data-explorer-' . WPDA::get_current_user_login(), '_wpnoncetoggledb' ) ) {
            return;
        }
        if ( !isset( $_REQUEST['database'], $_REQUEST['disabled'] ) ) {
            $msg = new WPDA_Message_Box(array(
                'message_text'           => sprintf( __( 'Cannot drop database [missing argument]', 'wp-data-access' ) ),
                'message_type'           => 'error',
                'message_is_dismissible' => false,
            ));
            $msg->box();
            return;
        }
        global $wpdb;
        $database = str_replace( '`', '', sanitize_text_field( wp_unslash( $_REQUEST['database'] ) ) );
        // input var okay.
        $disabled = $_REQUEST['disabled'] === 'true';
        // input var okay.
        if ( 'rdb:' === substr( $database, 0, 4 ) ) {
            // Toogle remote database
            if ( false === WPDADB::get_remote_database( $database, true ) ) {
                $msg = new WPDA_Message_Box(array(
                    'message_text'           => sprintf( __( 'Cannot disable remote database connection `%s` [remote database connection not found]', 'wp-data-access' ), $database ),
                    'message_type'           => 'error',
                    'message_is_dismissible' => false,
                ));
                $msg->box();
            } else {
                if ( false === WPDADB::dis_remote_database( $database, $disabled ) ) {
                    $msg = new WPDA_Message_Box(array(
                        'message_text'           => sprintf( __( 'Cannot disable remote database connection `%s`', 'wp-data-access' ), $database ),
                        'message_type'           => 'error',
                        'message_is_dismissible' => false,
                    ));
                    $msg->box();
                } else {
                    $msg = new WPDA_Message_Box(array(
                        'message_text' => sprintf( __( 'Remote database connection `%s` disabled', 'wp-data-access' ), $database ),
                    ));
                    $msg->box();
                    $this->switch_schema_name = $wpdb->dbname;
                }
            }
        }
    }

    private function drop_db() {
        if ( !$this->check_wpnonce( 'wpda-drop-db-from-data-explorer-' . WPDA::get_current_user_login(), '_wpnoncedropdb' ) ) {
            return;
        }
        if ( !isset( $_REQUEST['database'] ) ) {
            $msg = new WPDA_Message_Box(array(
                'message_text'           => sprintf( __( 'Cannot drop database [missing argument]', 'wp-data-access' ) ),
                'message_type'           => 'error',
                'message_is_dismissible' => false,
            ));
            $msg->box();
            return;
        }
        global $wpdb;
        $database = str_replace( '`', '', sanitize_text_field( wp_unslash( $_REQUEST['database'] ) ) );
        // input var okay.
        if ( 'rdb:' === substr( $database, 0, 4 ) ) {
            // Delete remote database
            if ( false === WPDADB::get_remote_database( $database ) ) {
                $msg = new WPDA_Message_Box(array(
                    'message_text'           => sprintf( __( 'Cannot delete remote database connection `%s` [remote database connection not found]', 'wp-data-access' ), $database ),
                    'message_type'           => 'error',
                    'message_is_dismissible' => false,
                ));
                $msg->box();
            } else {
                if ( false === WPDADB::del_remote_database( $database ) ) {
                    $msg = new WPDA_Message_Box(array(
                        'message_text'           => sprintf( __( 'Cannot delete remote database connection `%s`', 'wp-data-access' ), $database ),
                        'message_type'           => 'error',
                        'message_is_dismissible' => false,
                    ));
                    $msg->box();
                } else {
                    $msg = new WPDA_Message_Box(array(
                        'message_text' => sprintf( __( 'Remote database connection `%s` deleted', 'wp-data-access' ), $database ),
                    ));
                    $msg->box();
                    $this->switch_schema_name = $wpdb->dbname;
                }
            }
        } else {
            // Drop local database
            if ( $wpdb->dbname === $database ) {
                $msg = new WPDA_Message_Box(array(
                    'message_text'           => __( 'Cannot drop WordPress database', 'wp-data-access' ),
                    'message_type'           => 'error',
                    'message_is_dismissible' => false,
                ));
                $msg->box();
                return;
            }
            if ( 'sys' === $database || 'mysql' === $database || 'information_schema' === $database || 'performance_schema' === $database || '' === $database ) {
                $msg = new WPDA_Message_Box(array(
                    'message_text'           => __( 'Cannot drop MySQL database', 'wp-data-access' ),
                    'message_type'           => 'error',
                    'message_is_dismissible' => false,
                ));
                $msg->box();
                return;
            }
            if ( false === $wpdb->query( $wpdb->prepare( 
                'drop database `%1s`',
                // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
                array(WPDA::remove_backticks( $database ))
             ) ) ) {
                // db call ok; no-cache ok.
                $msg = new WPDA_Message_Box(array(
                    'message_text'           => sprintf( __( 'Error dropping database `%s`', 'wp-data-access' ), $database ),
                    'message_type'           => 'error',
                    'message_is_dismissible' => false,
                ));
                $msg->box();
            } else {
                $msg = new WPDA_Message_Box(array(
                    'message_text' => sprintf( __( 'Database `%s` dropped', 'wp-data-access' ), $database ),
                ));
                $msg->box();
                $this->switch_schema_name = $wpdb->dbname;
            }
        }
    }

    private function edit_db() {
        if ( !$this->check_wpnonce( 'wpda-edit-db-from-data-explorer-' . WPDA::get_current_user_login(), '_wpnonceeditdb' ) ) {
            return;
        }
        if ( !isset( $_REQUEST['edit_remote_database'] ) ) {
            $msg = new WPDA_Message_Box(array(
                'message_text'           => sprintf( __( 'Cannot update remote database connection [missing argument]', 'wp-data-access' ) ),
                'message_type'           => 'error',
                'message_is_dismissible' => false,
            ));
            $msg->box();
            return;
        }
        $database = sanitize_text_field( wp_unslash( $_REQUEST['edit_remote_database'] ) );
        // input var okay.
        $database_old = sanitize_text_field( wp_unslash( $_REQUEST['edit_remote_database_old'] ) );
        // input var okay.
        if ( $database !== $database_old ) {
            // Update database connection name
            if ( false === WPDADB::get_remote_database( $database_old ) ) {
                $msg = new WPDA_Message_Box(array(
                    'message_text'           => sprintf( __( 'Cannot update remote database connection [remote database connection not found]', 'wp-data-access' ) ),
                    'message_type'           => 'error',
                    'message_is_dismissible' => false,
                ));
                $msg->box();
                return;
            }
        } else {
            // Update database connection information
            if ( false === WPDADB::get_remote_database( $database ) ) {
                $msg = new WPDA_Message_Box(array(
                    'message_text'           => sprintf( __( 'Cannot update remote database connection [remote database connection not found]', 'wp-data-access' ) ),
                    'message_type'           => 'error',
                    'message_is_dismissible' => false,
                ));
                $msg->box();
                return;
            }
        }
        $host = ( isset( $_REQUEST['edit_remote_host'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['edit_remote_host'] ) ) : '' );
        // input var okay.
        $user = ( isset( $_REQUEST['edit_remote_user'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['edit_remote_user'] ) ) : '' );
        // input var okay.
        $passwd = ( isset( $_REQUEST['edit_remote_passwd'] ) ? wp_unslash( $_REQUEST['edit_remote_passwd'] ) : '' );
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $port = ( isset( $_REQUEST['edit_remote_port'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['edit_remote_port'] ) ) : '' );
        // input var okay.
        $schema = ( isset( $_REQUEST['edit_remote_schema'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['edit_remote_schema'] ) ) : '' );
        // input var okay.
        $ssl = ( isset( $_REQUEST['edit_remote_ssl'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['edit_remote_ssl'] ) ) : 'off' );
        // input var okay.
        $ssl_key = ( isset( $_REQUEST['edit_remote_client_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['edit_remote_client_key'] ) ) : '' );
        // input var okay.
        $ssl_cert = ( isset( $_REQUEST['edit_remote_client_certificate'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['edit_remote_client_certificate'] ) ) : '' );
        // input var okay.
        $ssl_ca = ( isset( $_REQUEST['edit_remote_ca_certificate'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['edit_remote_ca_certificate'] ) ) : '' );
        // input var okay.
        $ssl_path = ( isset( $_REQUEST['edit_remote_certificate_path'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['edit_remote_certificate_path'] ) ) : '' );
        // input var okay.
        $ssl_cipher = ( isset( $_REQUEST['edit_remote_specified_cipher'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['edit_remote_specified_cipher'] ) ) : '' );
        // input var okay.
        if ( '' === $database || '' === $host || '' === $user || '' === $schema ) {
            $msg = new WPDA_Message_Box(array(
                'message_text'           => sprintf( __( 'Cannot edit remote database connection [missing arguments]', 'wp-data-access' ) ),
                'message_type'           => 'error',
                'message_is_dismissible' => false,
            ));
            $msg->box();
            return;
        }
        if ( !WPDADB::upd_remote_database(
            $database,
            $host,
            $user,
            $passwd,
            $port,
            $schema,
            false,
            $database_old,
            $ssl,
            $ssl_key,
            $ssl_cert,
            $ssl_ca,
            $ssl_path,
            $ssl_cipher
        ) ) {
            $msg = new WPDA_Message_Box(array(
                'message_text'           => sprintf( __( 'Cannot update remote database connection `%s`', 'wp-data-access' ), $database ),
                'message_type'           => 'error',
                'message_is_dismissible' => false,
            ));
            $msg->box();
        } else {
            $msg = new WPDA_Message_Box(array(
                'message_text' => sprintf( __( 'Remote database connection `%s` updated', 'wp-data-access' ), $database ),
            ));
            $msg->box();
            if ( $database !== $database_old ) {
                $this->switch_schema_name = $database;
            }
        }
    }

    private function check_wpnonce( $wp_nonce_action, $wp_nonce_arg ) {
        $wp_nonce = ( isset( $_REQUEST[$wp_nonce_arg] ) ? sanitize_text_field( wp_unslash( $_REQUEST[$wp_nonce_arg] ) ) : '' );
        // input var okay.
        if ( !wp_verify_nonce( $wp_nonce, $wp_nonce_action ) ) {
            $msg = new WPDA_Message_Box(array(
                'message_text'           => __( 'Not authorized', 'wp-data-access' ),
                'message_type'           => 'error',
                'message_is_dismissible' => false,
            ));
            $msg->box();
            return false;
        }
        return true;
    }

    public function show() {
        if ( null !== $this->page ) {
            $this->add_containers();
        }
    }

    private function add_containers() {
        ?>

			<div id="wpda_manage_databases">
				<div id="wpda_db_tabs">
					<ul>
						<li><a href="#wpda_tab-1">Manage Databases</a></li>
						<li><a href="#wpda_tab-2">Create Remote Database Connection</a></li>
						<li><a href="#wpda_tab-3">Create Local Database</a></li>
					</ul>

					<div class="container" id="wpda_tab-1">
						<?php 
        $this->manage_databases();
        ?>
					</div>

					<div class="container" id="wpda_tab-2">
						<?php 
        $this->create_remote_database();
        ?>
					</div>

					<div class="container" id="wpda_tab-3">
						<?php 
        $this->create_local_database();
        ?>
					</div>
				</div>

				<div class="wpda_manage_databases_close">
					<a href="javascript:void(0)"
					   onclick="jQuery('#wpda_manage_databases').hide()"><i
								class="fas fa-xmark wpda_icon_on_button"></i>
					</a>
				</div>
			</div>

			<?php 
        $this->drop_database();
        ?>
            <?php 
        $this->toggle_database();
        ?>

			<?php 
        $this->js();
        $this->css();
    }

    private function manage_databases() {
        $tree = new WPDA_Tree();
        $dbs = $tree->get_dbs( true );
        $this->rdb = array();
        $mdbs = array(
            '' => '',
        );
        if ( isset( $dbs->data['data'] ) ) {
            $dbs_list = $dbs->data['data'];
            foreach ( $dbs_list as $db ) {
                if ( 'local' === $db['dbs_type'] || 'remote' === $db['dbs_type'] ) {
                    // Admins can manage local and remote database only.
                    $mdbs[$db['dbs']] = $db['dbs_type'];
                    if ( 'remote' === $db['dbs_type'] ) {
                        // Store remote database info.
                        $this->rdb[$db['dbs']] = WPDADB::get_remote_database( $db['dbs'], true );
                    }
                }
            }
        }
        ?>

			<h3 class="wpda_db_title">
				<?php 
        echo __( 'Manage Databases', 'wp-data-access' );
        ?>
			</h3>

			<?php 
        if ( 1 === count( $mdbs ) ) {
            $this->no_database();
        } else {
            $this->list_databases( $mdbs );
        }
    }

    private function activate_pds() {
        ?>
			<div class="restyle_link">
				<strong>NOTE</strong>
				Please activate your Premium Data Services access <a href="options-general.php?page=wpdataaccess&tab=pds">here</a> to remotely connect to foreign DBMSs and remote files.
				<a href="https://docs.remote.wpdataaccess.com/pds/remote-wizard.html" class="restyle_link" target="_blank">(read more...)</a>
			</div>
			<?php 
    }

    private function no_database() {
        echo __( 'No manageable local databases or remote database connections found', 'wp-data-access' );
    }

    private function list_databases( $dbs ) {
        ?>

			<div class="wpda_manage_dbs">

				<label for="edit_remote_database" class="database_item_label">Select database:</label>
				<select id="manage_db_selection">
					<?php 
        foreach ( $dbs as $db => $db_type ) {
            echo "<option value='{$db}' data-type='{$db_type}'>{$db}</option>";
        }
        ?>
				</select>

                <a id="manage_db_create_wp_user_access"
                   class="dashicons dashicons-admin-plugins wpda_tooltip"
                   style="display: none"
                   href="javascript:void(0)"
                   style="vertical-align:middle;"
                   title="<?php 
        echo __( "Create function wpda_get_wp_user_id() to access the WordPress user ID from database views", 'wp-data-access' );
        ?>"></a>

            </div>

			<?php 
        $this->edit_local_database();
        $this->edit_remote_database();
    }

    private function edit_local_database() {
        ?>

			<div id="edit_local_database_form">
				<label for="edit_local_database" class="database_item_label">Database name:</label>
				<input type="text" name="edit_local_database" id="edit_local_database" readonly>
				<a href="javascript:void(0)"
				   id="edit_local_database_action"
                   title="Drop Database"
				   class="button button-secondary wpda_tooltip"><i
							class="fas fa-trash wpda_icon_on_button"></i></a>
			</div>

			<?php 
    }

    private function edit_remote_database() {
        ?>

			<form method="post"
				  action="?page=<?php 
        echo esc_attr( $this->page );
        ?>"
				  onsubmit="return editdb_validate_form();"
				  id="edit_remote_database_form"
			>

				<div>
					<label for="edit_remote_database" class="database_item_label">Database name:</label>
					<input type="text"
						   name="edit_remote_database"
						   id="edit_remote_database">
					<input type="hidden"
						   name="edit_remote_database_old"
						   id="edit_remote_database_old">
                    <a href="javascript:void(0)"
                       id="disable_remote_database"
                       style="display: none"
                       title="Disable Remote Connection"
                       class="button button-secondary wpda_tooltip"><i
                                class="fas fa-ban wpda_icon_on_button"></i></a>
                    <a href="javascript:void(0)"
                       id="enable_remote_database"
                       style="display: none"
                       title="Enabled Remote Connection"
                       class="button button-secondary wpda_tooltip"><i
                                class="fas fa-check wpda_icon_on_button"></i></a>
                    <a href="javascript:void(0)"
                       id="remote_local_database_action"
                       title="Drop Database"
                       class="button button-secondary wpda_tooltip"><i
                                class="fas fa-trash wpda_icon_on_button"></i></a>
				</div>

                <div
                    id="remote_connection_is_disabled"
                    style="display: none"
                >
                    <label class="database_item_label"></label>
                    <span style="color: red; line-height: 30px">
                        This remote database connection is currently disabled.
                    </span>
                </div>

                <div>
					<label for="edit_remote_host" class="database_item_label">MySQL host:</label>
					<input type="text"
						   name="edit_remote_host"
						   id="edit_remote_host">
				</div>

				<div>
					<label for="edit_remote_user" class="database_item_label">MySQL username:</label>
					<input type="text"
						   name="edit_remote_user"
						   id="edit_remote_user">
				</div>

				<div>
					<label for="edit_remote_passwd" class="database_item_label">MySQL password:</label>
					<input type="password"
						   name="edit_remote_passwd"
						   id="edit_remote_passwd"
						   autocomplete="new-password">
					<i class="fas fa-eye"
					   onclick="wpda_toggle_password('edit_remote_passwd', event)"
					   style="cursor:pointer"></i>
				</div>

				<div>
					<label for="edit_remote_port" class="database_item_label">MySQL port:</label>
					<input type="text"
						   name="edit_remote_port"
						   id="edit_remote_port">
				</div>

				<div>
					<label for="edit_remote_schema" class="database_item_label">MySQL schema:</label>
					<input type="text"
						   name="edit_remote_schema"
						   id="edit_remote_schema">
				</div>

				<div class="wpda_db_ssl">
					<label for="edit_remote_ssl" class="database_item_label">SSL:</label>
					<input type="checkbox"
						   name="edit_remote_ssl"
						   id="edit_remote_ssl"
						   onclick="jQuery('#edit_remote_database_block_ssl').toggle()">
				</div>

				<div id="edit_remote_database_block_ssl">
					<div>
						<label for="edit_remote_client_key" class="database_item_label">Client key:</label>
						<input type="text"
							   name="edit_remote_client_key"
							   id="edit_remote_client_key">
					</div>

					<div>
						<label for="edit_remote_client_certificate" class="database_item_label">Client
							certificate:</label>
						<input type="text"
							   name="edit_remote_client_certificate"
							   id="edit_remote_client_certificate">
					</div>

					<div>
						<label for="edit_remote_ca_certificate" class="database_item_label">CA certificate:</label>
						<input type="text"
							   name="edit_remote_ca_certificate"
							   id="edit_remote_ca_certificate">
					</div>

					<div>
						<label for="edit_remote_certificate_path" class="database_item_label">Certificate path:</label>
						<input type="text"
							   name="edit_remote_certificate_path"
							   id="edit_remote_certificate_path">
					</div>

					<div>
						<label for="edit_remote_specified_cipher" class="database_item_label">Specified Cipher:</label>
						<input type="text"
							   name="edit_remote_specified_cipher"
							   id="edit_remote_specified_cipher">
					</div>
				</div>

				<div>
					<label class="database_item_label"></label>
					<input type="button"
						   value="Test"
						   onclick="test_remote_connection('edit_'); return false;"
						   id="edit_remote_test_button"
						   class="button">
					<input type="button"
						   value="Clear"
						   onclick="test_remote_clear('edit_'); return false;"
						   id="edit_remote_clear_button"
						   class="button">
				</div>

				<div id="edit_remote_database_block_test">
					<div id="edit_remote_database_block_test_content"
						 class="remote_database_block_test_content"></div>
				</div>

				<input type="hidden" name="action" value="edit_db">
				<?php 
        wp_nonce_field( 'wpda-edit-db-from-data-explorer-' . WPDA::get_current_user_login(), '_wpnonceeditdb', false );
        ?>

				<?php 
        $this->buttons();
        ?>

			</form>

			<?php 
    }

    private function create_local_database() {
        ?>

			<h3 class="wpda_db_title">
				<?php 
        echo __( 'Create local database', 'wp-data-access' );
        ?>
			</h3>

			<?php 
        if ( !$this->user_can_create_db ) {
            echo __( 'You are not authorized to create local databases', 'wp-data-access' );
        } else {
            ?>

				<form method="post"
					  action="?page=<?php 
            echo esc_attr( $this->page );
            ?>"
					  onsubmit="return createdb_validate_form_local();"
				>

					<div>
						<label for="local_database" class="database_item_label">Database name:</label>
						<input type="text" name="local_database" id="local_database">
					</div>

					<input type="hidden" name="action" value="create_db">
					<input type="hidden" name="database_location" value="local">
					<?php 
            wp_nonce_field( 'wpda-create-db-from-data-explorer-' . WPDA::get_current_user_login(), '_wpnoncecreatedb', false );
            ?>

					<?php 
            $this->buttons();
            ?>

				</form>

				<?php 
        }
    }

    private function create_remote_database() {
        ?>

			<form method="post" action="?page=<?php 
        echo esc_attr( $this->page );
        ?>"
				  onsubmit="return createdb_validate_form_remote();">

				<h3 class="wpda_db_title">
					<?php 
        echo __( 'Create remote database connection', 'wp-data-access' );
        ?>
				</h3>

				<div>
					<label for="remote_database"
						   class="database_item_label">Database name:</label>
					<input type="text" name="remote_database" id="remote_database" value="rdb:">
				</div>

				<div>
					<label for="remote_host" class="database_item_label">MySQL host:</label>
					<input type="text" name="remote_host" id="remote_host">
				</div>

				<div>
					<label for="remote_user" class="database_item_label">MySQL username:</label>
					<input type="text" name="remote_user" id="remote_user">
				</div>

				<div>
					<label for="remote_passwd" class="database_item_label">MySQL password:</label>
					<input type="password" name="remote_passwd" id="remote_passwd" autocomplete="new-password">
					<i class="fas fa-eye" onclick="wpda_toggle_password('remote_passwd', event)"
					   style="cursor:pointer"></i>
				</div>

				<div>
					<label for="remote_port" class="database_item_label">MySQL port:</label>
					<input type="text" name="remote_port" id="remote_port" value="3306">
				</div>

				<div>
					<label for="remote_schema" class="database_item_label">MySQL schema:</label>
					<input type="text" name="remote_schema" id="remote_schema">
				</div>

				<div class="wpda_db_ssl">
					<label for="remote_ssl"
						   class="database_item_label">SSL:</label>
					<input type="checkbox" name="remote_ssl" id="remote_ssl" unchecked
						   onclick="jQuery('#remote_database_block_ssl').toggle()">
				</div>

				<div id="remote_database_block_ssl">
					<div>
						<label for="remote_client_key" class="database_item_label">Client key:</label>
						<input type="text" name="remote_client_key" id="remote_client_key">
					</div>

					<div>
						<label for="remote_client_certificate" class="database_item_label">Client certificate:</label>
						<input type="text" name="remote_client_certificate" id="remote_client_certificate">
					</div>

					<div>
						<label for="remote_ca_certificate" class="database_item_label">CA certificate:</label>
						<input type="text" name="remote_ca_certificate" id="remote_ca_certificate">
					</div>

					<div>
						<label for="remote_certificate_path" class="database_item_label">Certificate path:</label>
						<input type="text" name="remote_certificate_path" id="remote_certificate_path">
					</div>

					<div>
						<label for="remote_specified_cipher" class="database_item_label">Specified Cipher:</label>
						<input type="text" name="remote_specified_cipher" id="remote_specified_cipher">
					</div>
				</div>

				<div>
					<label class="database_item_label"></label>
					<input type="button" value="Test" onclick="test_remote_connection(); return false;"
						   id="remote_test_button" class="button">
					<input type="button" value="Clear" onclick="test_remote_clear(); return false;"
						   id="remote_clear_button" class="button" style="display:none;">
				</div>

				<div id="remote_database_block_test">
					<div id="remote_database_block_test_content"
						 class="remote_database_block_test_content"></div>
				</div>

				<input type="hidden" name="action" value="create_db">
				<input type="hidden" name="database_location" value="remote">
				<?php 
        wp_nonce_field( 'wpda-create-db-from-data-explorer-' . WPDA::get_current_user_login(), '_wpnoncecreatedb', false );
        ?>

				<?php 
        $this->buttons();
        ?>

			</form>

			<?php 
    }

    private function buttons() {
        ?>

			<div class="wpda_db_buttons">
				<a href="javascript:void(0)"
				   onclick="jQuery(this).closest('form').submit()"
				   class="button button-primary"><i
							class="fas fa-cloud-upload wpda_icon_on_button"></i> <?php 
        echo __( 'Save', 'wp-data-access' );
        ?>
				</a>
				<a href="javascript:void(0)"
				   onclick="jQuery('#wpda_manage_databases').hide()"
				   class="button button-secondary"><i
							class="fas fa-times-circle wpda_icon_on_button"></i> <?php 
        echo __( 'Cancel', 'wp-data-access' );
        ?>
				</a>
			</div>

			<?php 
    }

    private function drop_database() {
        ?>

			<form id="wpda_form_drop_db"
				  method="post" action="?page=<?php 
        echo esc_attr( $this->page );
        ?>"
			>
				<input type="hidden" name="database" id="drop_database">
				<input type="hidden" name="action" value="drop_db">
				<?php 
        wp_nonce_field( 'wpda-drop-db-from-data-explorer-' . WPDA::get_current_user_login(), '_wpnoncedropdb', false );
        ?>
			</form>

			<?php 
    }

    private function toggle_database() {
        ?>

            <form id="wpda_form_toggle_db"
                  method="post" action="?page=<?php 
        echo esc_attr( $this->page );
        ?>"
            >
                <input type="hidden" name="database" id="toggle_database">
                <input type="hidden" name="action" value="toggle_db">
                <input type="hidden" name="disabled" id="toggle_database_value">
                <?php 
        wp_nonce_field( 'wpda-toggle-db-from-data-explorer-' . WPDA::get_current_user_login(), '_wpnoncetoggledb', false );
        ?>
            </form>

            <?php 
    }

    private function js() {
        ?>

			<script>
				function editdb_validate_form() {
					if (jQuery('#edit_remote_database').val() === '' || jQuery('#edit_remote_database').val() === 'rdb:') {
						alert('Database name must be entered');
						return false;
					}

					if (jQuery('#edit_remote_host').val() === '') {
						alert('MySQL host must be entered');
						return false;
					}

					if (jQuery('#edit_remote_user').val() === '') {
						alert('MySQL username must be entered');
						return false;
					}

					if (jQuery('#edit_remote_schema').val() === '') {
						alert('MySQL schema must be entered');
						return false;
					}

					return true;
				}

				function createdb_validate_form_remote() {
					if (jQuery('#remote_database').val() === '' || jQuery('#remote_database').val() === 'rdb:') {
						alert('Database name must be entered');
						return false;
					}

					if (jQuery('#remote_host').val() === '') {
						alert('MySQL host must be entered');
						return false;
					}

					if (jQuery('#remote_user').val() === '') {
						alert('MySQL username must be entered');
						return false;
					}

					if (jQuery('#remote_schema').val() === '') {
						alert('MySQL schema must be entered');
						return false;
					}

					return true;
				}

				function createdb_validate_form_local() {
					if (jQuery('#local_database').val() === '') {
						alert('Database name must be entered');
						return false;
					}

					return true;
				}

				function test_remote_clear(mode = '') {
					jQuery('#' + mode + 'remote_database_block_test_content').html('');
					jQuery('#' + mode + 'remote_database_block_test').hide();
					jQuery('#' + mode + 'remote_clear_button').hide();
				}

				function test_remote_connection(mode = '') {
					host = jQuery('#' + mode + 'remote_host').val();
					user = jQuery('#' + mode + 'remote_user').val();
					pass = jQuery('#' + mode + 'remote_passwd').val();
					port = jQuery('#' + mode + 'remote_port').val();
					dbs = jQuery('#' + mode + 'remote_schema').val();
					ssl = jQuery('#' + mode + 'remote_ssl').val();
					ssl_key = jQuery('#' + mode + 'remote_client_key').val();
					ssl_cert = jQuery('#' + mode + 'remote_client_certificate').val();
					ssl_ca = jQuery('#' + mode + 'remote_ca_certificate').val();
					ssl_path = jQuery('#' + mode + 'remote_certificate_path').val();
					ssl_cipher = jQuery('#' + mode + 'remote_specified_cipher').val();

					url = '//' + window.location.host + window.location.pathname +
						'?action=wpda_check_remote_database_connection';

					jQuery('#' + mode + 'remote_test_button').val('Testing...');

					jQuery.ajax({
						method: 'POST',
						url: url,
						data: {
							host: host,
							user: user,
							passwd: pass,
							port: port,
							schema: dbs,
							ssl: ssl,
							ssl_key: ssl_key,
							ssl_cert: ssl_cert,
							ssl_ca: ssl_ca,
							ssl_path: ssl_path,
							ssl_cipher: ssl_cipher
						}
					}).done(
						function (msg) {
							jQuery('#' + mode + 'remote_database_block_test_content').html(msg);
							jQuery('#' + mode + 'remote_database_block_test').show();
						}
					).fail(
						function () {
							jQuery('#' + mode + 'remote_database_block_test_content').html('Preparing connection...<br/>Establishing connection...<br/><br/><strong>Remote database connection invalid</strong>');
							jQuery('#' + mode + 'remote_database_block_test').show();
						}
					).always(
						function () {
							jQuery('#' + mode + 'remote_test_button').val('Test');
							jQuery('#' + mode + 'remote_clear_button').show();
						}
					);
				}

				jQuery(function () {
					jQuery('#database_location').on('change', function () {
						if (jQuery(this).val() === 'remote') {
							jQuery('#local_database_block').hide();
							jQuery('#remote_database_block').show();
						} else {
							jQuery('#remote_database_block').hide();
							jQuery('#local_database_block').show();
						}
					});

					jQuery('#remote_database, #edit_remote_database').keydown(function () {
						var field = this;
						setTimeout(function () {
							if (field.value.indexOf('rdb:') !== 0) {
								jQuery(field).val('rdb:');
							}
						}, 1);
					});

					jQuery('#wpda_db_tabs').tabs();

					jQuery("#manage_db_selection").on('change', function () {
						const db_name = this.value;
						const db_type = jQuery("#manage_db_selection").find(':selected').data("type");

						if (db_type === 'local') {
							jQuery("#edit_remote_database_form").hide();
							jQuery("#edit_local_database_form").show();

							jQuery("#edit_local_database").val(db_name);
						} else {
							jQuery("#edit_local_database_form").hide();
							jQuery("#edit_remote_database_form").show();

							if (wpda_rdb[db_name]) {
								// Load remote database info
								const rdb = wpda_rdb[db_name];
								jQuery("#edit_remote_database").val(db_name)
								jQuery("#edit_remote_database_old").val(db_name)

								jQuery("#edit_remote_host").val(rdb.host)
								jQuery("#edit_remote_user").val(rdb.username)
								jQuery("#edit_remote_passwd").val(rdb.password)
								jQuery("#edit_remote_port").val(rdb.port)
								jQuery("#edit_remote_schema").val(rdb.database)

								jQuery("#edit_remote_ssl").prop("checked", rdb.ssl === "on")
								jQuery("#edit_remote_client_key").val(rdb.ssl_key)
								jQuery("#edit_remote_client_certificate").val(rdb.ssl_cert)
								jQuery("#edit_remote_ca_certificate").val(rdb.ssl_ca)
								jQuery("#edit_remote_certificate_path").val(rdb.ssl_path)
								jQuery("#edit_remote_specified_cipher").val(rdb.ssl_cipher)

                                if (rdb.disabled === true) {
                                    jQuery("#enable_remote_database").show();
                                    jQuery("#disable_remote_database").hide();
                                    jQuery("#remote_connection_is_disabled").show();
                                } else {
                                    jQuery("#disable_remote_database").show();
                                    jQuery("#enable_remote_database").hide();
                                }

								if (rdb.ssl === "on") {
									jQuery('#edit_remote_database_block_ssl').show();
								} else {
									jQuery('#edit_remote_database_block_ssl').hide();
								}
							} else {
								if (db_name !== "") {
									alert("Database not found");
									jQuery("#edit_remote_database_form").hide();
								} else {
									jQuery("#edit_remote_database_form").hide();
									jQuery("#edit_local_database_form").hide();
								}
							}
						}

                        if (db_name === '') {
                            jQuery("#manage_db_create_wp_user_access").hide();
                        } else {
                            jQuery("#manage_db_create_wp_user_access").show();
                        }
					})

                    jQuery("#manage_db_create_wp_user_access").on('click', function() {
                        const selectedDatabase = jQuery("#manage_db_selection").val()
                        wpda_dbinit_admin( selectedDatabase, '<?php 
        echo wp_create_nonce( 'wpda_dbinit_admin_' . WPDA::get_current_user_login() );
        ?>' )
                    })

					jQuery("#edit_local_database_action").on('click', function () {
						if (confirm('Warning! All your tables and views will be deleted. This action cannot be undone! Are you sure you want to delete this database?')) {
							jQuery("#drop_database").val(jQuery("#edit_local_database").val())
							jQuery("#wpda_form_drop_db").submit();
						}
					})

					jQuery("#remote_local_database_action").on('click', function () {
						if (confirm('Warning! All your tables and views will be deleted. This action cannot be undone! Are you sure you want to delete this database?')) {
							jQuery("#drop_database").val(jQuery("#edit_remote_database").val())
							jQuery("#wpda_form_drop_db").submit();
						}
					})

                    jQuery("#disable_remote_database").on('click', function() {
                        if (confirm('Are you sure you want to disable this database?')) {
                            jQuery("#toggle_database").val(jQuery("#edit_remote_database").val())
                            jQuery("#toggle_database_value").val(true);
                            jQuery("#wpda_form_toggle_db").submit();
                        }
                    })

                    jQuery("#enable_remote_database").on('click', function() {
                        jQuery("#toggle_database").val(jQuery("#edit_remote_database").val())
                        jQuery("#toggle_database_value").val(false);
                        jQuery("#wpda_form_toggle_db").submit();
                    })
				});

				var wpda_rdb = <?php 
        echo json_encode( $this->rdb );
        ?>;
			</script>

			<?php 
    }

    private function css() {
        ?>

			<style>
                #wpda_manage_databases {
					position: relative;
                    display: none;
                    padding: 24px;
                    margin-bottom: 5px;
                    border-radius: 4px;
                    background-color: #fff;
                    color: rgba(0, 0, 0, 0.87);
                    -webkit-transition: box-shadow 300ms cubic-bezier(0.4, 0, 0.2, 1) 0ms;
                    transition: box-shadow 300ms cubic-bezier(0.4, 0, 0.2, 1) 0ms;
                    box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
                }

                #wpda_db_tabs {
                    border: none;
                }

                #wpda_db_tabs ul {
                    background: none;
                    border-top: none;
                    border-right: none;
                    border-left: none;
                    border-radius: 0;
                }

                #edit_remote_clear_button,
                #edit_remote_database_block_test,
                #wpda_form_drop_db,
                #remote_database_block_ssl,
                #remote_database_block_test,
                #edit_remote_database_form,
                #edit_local_database_form {
                    display: none;
                }

                #edit_remote_database_block_ssl,
                #remote_database_block_ssl {
                    margin-bottom: 10px;
                }

                #edit_remote_database_block_test,
                #remote_database_block_test {
                    margin-top: 10px;
                }

                .wpda_manage_dbs,
                .wpda_db_title {
                    margin-bottom: 30px;
                }

                .database_item_label {
                    vertical-align: baseline;
                }

                .wpda_db_ssl {
                    margin-top: 10px;
                    margin-bottom: 10px;
                }

                .wpda_db_buttons {
                    margin-top: 30px;
                }
				.wpda_manage_databases_close {
                    position: absolute;
                    top: 22px;
                    right: 22px;
                    z-index: 9999999;
                    font-size: 1rem;
				}

                .wpda_manage_databases_close .fas {
					color: rgb(60, 67, 74);
				}

				.restyle_link a {
                    color: #2271b1;
					text-decoration: none;
					font-weight: 700;
				}

                #manage_db_create_wp_user_access:before {
                    line-height: 28px;
                }
			</style>

			<?php 
    }

}

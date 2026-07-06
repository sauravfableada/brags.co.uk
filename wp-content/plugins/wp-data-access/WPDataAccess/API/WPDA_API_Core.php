<?php

namespace WPDataAccess\API;

use WPDataAccess\Connection\WPDADB;
use WPDataAccess\Plugin_Table_Models\WPDA_Media_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_Table_Settings_Model;
use WPDataAccess\WPDA;
abstract class WPDA_API_Core {
    public abstract function register_rest_routes();

    private static $user_roles = null;

    private static $user_login = null;

    private $params;

    public function __construct() {
        $this->params = array(
            'dbs'                => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    return $this->sanitize_db_identifier( $param );
                },
                'validate_callback' => function ( $param ) {
                    return $this->validate_db_identifier( $param );
                },
            ),
            'tbl'                => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __( 'Table or view name', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    return $this->sanitize_db_identifier( $param );
                },
                'validate_callback' => function ( $param ) {
                    return $this->validate_db_identifier( $param );
                },
            ),
            'client_side'        => array(
                'required'          => false,
                'type'              => 'boolean',
                'description'       => __( 'Server side processing', 'wp-data-access' ),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'app_id'             => array(
                'required'          => true,
                'type'              => 'integer',
                'description'       => __( 'App ID', 'wp-data-access' ),
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'cnt_id'             => array(
                'required'          => true,
                'type'              => 'integer',
                'description'       => __( 'Container ID', 'wp-data-access' ),
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'app_name'           => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __( 'App name', 'wp-data-access' ),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'app_title'          => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __( 'App title', 'wp-data-access' ),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'app_type'           => array(
                'required'          => true,
                'type'              => 'integer',
                'description'       => __( 'App type', 'wp-data-access' ),
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'app_settings'       => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __( 'App settings', 'wp-data-access' ),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'app_add_to_menu'    => array(
                'required'          => true,
                'type'              => 'integer',
                'description'       => __( 'Add app to dashboard menu', 'wp-data-access' ),
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'app_cls'            => array(
                'required'          => true,
                'type'              => 'array',
                'description'       => __( 'App columns', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    return $this->sanitize_columns( $param );
                },
                'validate_callback' => function ( $param ) {
                    return $this->validate_columns( $param );
                },
            ),
            'join_tab'           => array(
                'required'          => false,
                'type'              => 'boolean',
                'description'       => __( 'Use join table', 'wp-data-access' ),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'rel_tab'            => array(
                'required'          => false,
                'type'              => 'boolean',
                'description'       => __( 'Use relation table', 'wp-data-access' ),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'md'                 => array(
                'required'          => false,
                'type'              => 'mixed',
                'description'       => __( 'Master detail join conditions', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    $columns = array();
                    foreach ( rest_sanitize_object( $param ) as $column_name => $value ) {
                        $columns[$this->sanitize_db_identifier( $column_name )] = sanitize_text_field( wp_unslash( $value ) );
                    }
                    return $columns;
                },
                'validate_callback' => function ( $param ) {
                    return is_array( $param );
                },
            ),
            'cascade'            => array(
                'required'          => false,
                'type'              => 'boolean',
                'description'       => __( 'Use search arguments if true', 'wp-data-access' ),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'app_apps'           => array(
                'required'          => false,
                'type'              => 'array',
                'description'       => __( 'Array of app IDs', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    $apps = array();
                    foreach ( $param as $value ) {
                        if ( is_numeric( $value ) ) {
                            $apps[] = $value;
                        }
                    }
                    return $apps;
                },
                'validate_callback' => function ( $param ) {
                    return is_array( $param );
                },
            ),
            'app_query'          => array(
                'required'          => false,
                'type'              => 'string',
                'description'       => __( 'Custom query', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    return html_entity_decode( wp_unslash( $param ), ENT_QUOTES );
                    // Preserve SQL operators
                },
            ),
            'col'                => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __( 'Column name', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    return $this->sanitize_db_identifier( $param );
                },
                'validate_callback' => function ( $param ) {
                    return $this->validate_db_identifier( $param );
                },
            ),
            'cols'               => array(
                'required'          => false,
                'type'              => 'mixed',
                'description'       => __( 'Table or view columns', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    $columns = array();
                    foreach ( rest_sanitize_object( $param ) as $column_name => $queryable ) {
                        $columns[$this->sanitize_db_identifier( $column_name )] = $queryable === true;
                    }
                    return $columns;
                },
                'validate_callback' => function ( $param ) {
                    return is_array( $param );
                },
            ),
            'page_index'         => array(
                'required'          => false,
                'type'              => 'integer',
                'description'       => __( 'Page number', 'wp-data-access' ),
                'default'           => 0,
                'minimum'           => 0,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'page_size'          => array(
                'required'          => false,
                'type'              => 'integer',
                'description'       => __( 'Rows per page (0=all)', 'wp-data-access' ),
                'default'           => 10,
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'search'             => array(
                'required'          => false,
                'type'              => 'string',
                'description'       => __( 'Global search filter', 'wp-data-access' ),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'search_columns'     => array(
                'required'          => false,
                'type'              => 'mixed',
                'description'       => __( 'Column search filters', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    $search = array();
                    foreach ( rest_sanitize_array( $param ) as $value ) {
                        if ( isset( $value['id'], $value['value'] ) ) {
                            $search[] = array(
                                'id'    => $this->sanitize_db_identifier( $value['id'] ),
                                'value' => ( is_array( $value['value'] ) ? map_deep( $value['value'], 'sanitize_text_field' ) : sanitize_text_field( $value['value'] ) ),
                            );
                        }
                    }
                    return $search;
                },
                'validate_callback' => function ( $param ) {
                    return is_array( $param );
                },
            ),
            'search_column_fns'  => array(
                'required'          => false,
                'description'       => __( 'Column search filter modes', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    $search_modes = array();
                    foreach ( $param as $key => $value ) {
                        if ( in_array( $value, WPDA_Table::WPDA_SEARCH_MODES ) ) {
                            // Accepting only valid modes
                            $search_modes[$this->sanitize_db_identifier( $key )] = sanitize_text_field( $value );
                        }
                    }
                    return $search_modes;
                },
                'validate_callback' => function ( $param ) {
                    return is_array( $param );
                },
            ),
            'search_column_lov'  => array(
                'required'          => false,
                'type'              => 'mixed',
                'description'       => __( 'Search columns for lov support', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    $lovs = array();
                    foreach ( $param as $value ) {
                        $lovs[] = $this->sanitize_db_identifier( $value );
                    }
                    return $lovs;
                },
                'validate_callback' => function ( $param ) {
                    return is_array( $param );
                },
            ),
            'search_data_types'  => array(
                'required'          => false,
                'type'              => 'mixed',
                'description'       => __( 'Search columns for lov support', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    $date_types = array();
                    foreach ( $param as $key => $value ) {
                        $date_types[$this->sanitize_db_identifier( $key )] = sanitize_text_field( $value );
                    }
                    return $date_types;
                },
                'validate_callback' => function ( $param ) {
                    return is_array( $param );
                },
            ),
            'search_custom'      => array(
                'required'          => false,
                'description'       => __( 'Custom search filters auto generated from http parameter requirements in default where', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    $search_custom = array();
                    foreach ( $param as $key => $value ) {
                        if ( is_array( $value ) ) {
                            foreach ( $value as $column_name => $column_value ) {
                                $search_custom[$key][$this->sanitize_db_identifier( $column_name )] = sanitize_text_field( $column_value );
                            }
                        }
                    }
                    return $search_custom;
                },
                'validate_callback' => function ( $param ) {
                    return is_array( $param );
                },
            ),
            'search_params'      => array(
                'required'          => false,
                'description'       => __( 'Shortcode parameters', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    $search_custom = array();
                    foreach ( $param as $key => $value ) {
                        $search_custom[$this->sanitize_db_identifier( $key )] = sanitize_text_field( $value );
                    }
                    return $search_custom;
                },
                'validate_callback' => function ( $param ) {
                    return is_array( $param );
                },
            ),
            'sorting'            => array(
                'required'          => false,
                'description'       => __( 'Order by (array of { id and desc })', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    $order_by = array();
                    foreach ( rest_sanitize_object( $param ) as $value ) {
                        if ( isset( $value['id'], $value['desc'] ) ) {
                            $order_by[] = array(
                                'id'   => $this->sanitize_db_identifier( $value['id'] ),
                                'desc' => sanitize_text_field( $value['desc'] ),
                            );
                        }
                    }
                    return $order_by;
                },
                'validate_callback' => function ( $param ) {
                    if ( !is_array( $param ) ) {
                        return false;
                    }
                    foreach ( $param as $value ) {
                        if ( !isset( $value['id'], $value['desc'] ) ) {
                            return false;
                        }
                    }
                    return true;
                },
            ),
            'row_count'          => array(
                'required'          => false,
                'type'              => 'integer',
                'description'       => __( 'Row count', 'wp-data-access' ),
                'minimum'           => 0,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'row_count_estimate' => array(
                'required'          => false,
                'type'              => 'boolean',
                'description'       => __( 'Calculate row count estimate', 'wp-data-access' ),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'key'                => array(
                'required'          => true,
                'type'              => 'mixed',
                'description'       => __( 'Primary key', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    $primary_keys = array();
                    foreach ( $param as $key => $value ) {
                        $primary_keys[$this->sanitize_db_identifier( $key )] = sanitize_text_field( $value );
                    }
                    return $primary_keys;
                },
                'validate_callback' => function ( $param ) {
                    return is_array( $param );
                },
            ),
            'val'                => array(
                'required'          => true,
                'type'              => 'mixed',
                'description'       => __( 'Column values', 'wp-data-access' ),
                'validate_callback' => function ( $param ) {
                    return is_array( $param );
                },
            ),
            'typ'                => array(
                'required'          => true,
                'type'              => 'integer',
                'description'       => __( 'Type = 0, view = 1', 'wp-data-access' ),
                'minimum'           => 0,
                'maximum'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'media'              => array(
                'required'          => false,
                'type'              => 'mixed',
                'description'       => __( 'Media columns', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    $media = array();
                    foreach ( $param as $key => $value ) {
                        $media[$this->sanitize_db_identifier( $key )] = sanitize_text_field( $value );
                    }
                    return $media;
                },
                'validate_callback' => function ( $param ) {
                    return is_array( $param );
                },
            ),
            'copy_key'           => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __( 'Internal copy action identifier', 'wp-data-access' ),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'access'             => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __( 'Access (user | global) ', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    return ( 'global' === strtolower( $param ) ? 'global' : 'user' );
                },
                'validate_callback' => function ( $param ) {
                    return 'global' === strtolower( $param ) || 'user' === strtolower( $param );
                },
            ),
            'query'              => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __( 'SQL query', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    return html_entity_decode( wp_unslash( $param ), ENT_QUOTES );
                    // Preserve SQL operators
                },
            ),
            'name'               => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __( 'Query name', 'wp-data-access' ),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'vqb'                => array(
                'required'          => false,
                'type'              => 'mixed',
                'description'       => __( 'Visual Query Builder', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    return rest_sanitize_object( $param );
                },
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'params'             => array(
                'required'          => false,
                'type'              => 'array',
                'description'       => __( 'Cron job parameters', 'wp-data-access' ),
                'sanitize_callback' => function ( $param ) {
                    $params = array();
                    foreach ( $param as $key => $value ) {
                        if ( 'params' === $key || 'notify' === $key ) {
                            // Sanitize custom parameters
                            $custom_params = array();
                            foreach ( $value as $param_key => $param_value ) {
                                $custom_params[sanitize_text_field( $param_key )] = sanitize_text_field( $param_value );
                            }
                            $params[sanitize_text_field( $key )] = $custom_params;
                        } else {
                            $params[sanitize_text_field( $key )] = sanitize_text_field( $value );
                        }
                    }
                    return $params;
                },
                'validate_callback' => function ( $param ) {
                    return is_array( $param );
                },
            ),
        );
    }

    protected function get_param( $key, $description = null ) {
        if ( isset( $this->params[$key] ) ) {
            $param = $this->params[$key];
            if ( null !== $description ) {
                $param['description'] = $description;
            }
            return $param;
        } else {
            // Force REST API error
            return false;
        }
    }

    protected function get_user_roles() {
        if ( null === WPDA_API_Core::$user_roles ) {
            WPDA_API_Core::$user_roles = WPDA::get_current_user_roles();
            if ( false === WPDA_API_Core::$user_roles ) {
                WPDA_API_Core::$user_roles = array();
            }
        }
        return WPDA_API_Core::$user_roles;
    }

    protected function get_user_login() {
        if ( null === WPDA_API_Core::$user_login ) {
            WPDA_API_Core::$user_login = WPDA::get_current_user_login();
        }
        return WPDA_API_Core::$user_login;
    }

    protected function current_user_can_access() {
        return WPDA::current_user_is_admin();
    }

    protected function unauthorized() {
        return new \WP_Error('error', __( 'Unauthorized', 'wp-data-access' ), array(
            'status' => 401,
        ));
    }

    protected function current_user_token_valid( $request ) {
        return wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' );
    }

    protected function invalid_nonce() {
        return new \WP_Error('rest_cookie_invalid_nonce', 'Cookie check failed', array(
            'status' => 403,
        ));
    }

    protected function bad_request() {
        return new \WP_Error('error', __( 'Bad request', 'wp-data-access' ), array(
            'status' => 400,
        ));
    }

    protected function invalid_app_settings() {
        return new \WP_Error('error', __( 'Invalid app settings - contact support', 'wp-data-access' ), array(
            'status' => 403,
        ));
    }

    protected function current_user_can_remote() {
        return false;
    }

    public static function sanitize_db_identifier( $param ) {
        if ( !is_string( $param ) ) {
            return null;
        }
        // Preserve starting and trailing spaces
        $spaces_before = strlen( $param ) - strlen( ltrim( $param ) );
        $spaces_after = strlen( $param ) - strlen( rtrim( $param ) );
        return str_repeat( ' ', $spaces_before ) . WPDA::remove_backticks( sanitize_text_field( $param ) ) . str_repeat( ' ', $spaces_after );
    }

    public static function validate_db_identifier( $param ) {
        return !empty( WPDA::remove_backticks( $param ) );
    }

    protected function sanitize_columns( $param ) {
        $sanitized_param = array();
        foreach ( $param as $p ) {
            $sanitized_param[] = array(
                'columnName' => $this->sanitize_db_identifier( $p['columnName'] ),
                'isSelected' => $p['isSelected'],
            );
        }
        return $sanitized_param;
    }

    protected function validate_columns( $param ) {
        if ( !is_array( $param ) ) {
            return false;
        }
        foreach ( $param as $p ) {
            if ( !isset( $p['columnName'], $p['isSelected'] ) || !$this->validate_db_identifier( $p['columnName'] ) || 'boolean' !== gettype( $p['isSelected'] ) ) {
                return false;
            }
        }
        return true;
    }

    protected function get_wp_roles() {
        $roles = array();
        global $wp_roles;
        foreach ( $wp_roles->roles as $role => $role_object ) {
            if ( isset( $role_object['name'] ) ) {
                $roles[$role] = $role_object['name'];
            }
        }
        return $roles;
    }

    protected function get_wp_users() {
        $users = array();
        foreach ( get_users() as $user ) {
            if ( isset( $user->user_login, $user->display_name ) ) {
                $users[$user->user_login] = $user->display_name;
            }
        }
        return $users;
    }

    protected function get_env() {
        return array(
            'ip'    => $_SERVER['REMOTE_ADDR'],
            'id'    => WPDA::get_current_user_id(),
            'user'  => WPDA::get_current_user_login(),
            'roles' => WPDA::get_current_user_roles(),
            'login' => 'anonymous' !== WPDA::get_current_user_login(),
        );
    }

    protected function get_table_info( $dbs, $tbl, $default_where = '' ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( $wpdadb === null ) {
            return array(
                'type'   => null,
                'engine' => null,
                'count'  => null,
            );
        }
        $query = $wpdadb->prepare( "\n\t\t\t\t\tselect table_type,\n\t\t\t\t\t       engine,\n\t\t\t\t\t       table_rows\n\t\t\t\t\t  from information_schema.tables\n\t\t\t\t\t where table_schema = %s\n\t\t\t\t\t   and table_name   = %s\n\t\t\t\t", array($wpdadb->dbname, $tbl) );
        $resultset = $wpdadb->get_results( $query, 'ARRAY_N' );
        // phpcs:ignore Standard.Category.SniffName.ErrorCode
        if ( count( $resultset ) === 1 ) {
            if ( null !== $resultset[0][2] ) {
                return array(
                    'type'   => $resultset[0][0],
                    'engine' => $resultset[0][1],
                    'count'  => ( '' === $default_where ? ( $resultset[0][2] == 0 ? null : $resultset[0][2] ) : null ),
                );
            } else {
                $count = $this->get_row_count_estimate( $dbs, $tbl );
                return array(
                    'type'   => $resultset[0][0],
                    'engine' => $resultset[0][1],
                    'count'  => ( $count === 0 ? null : $count ),
                );
            }
        } else {
            return array(
                'type'   => null,
                'engine' => null,
                'count'  => null,
            );
        }
    }

    protected function get_row_count_estimate( $dbs, $tbl ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( null === $wpdadb ) {
            return -1;
        }
        $explain = $wpdadb->get_results( 'explain select count(*) from `' . str_replace( '`', '', $tbl ) . '`', 'ARRAY_A' );
        if ( isset( $explain[0]['rows'] ) ) {
            return $explain[0]['rows'];
        } else {
            // This should never happen
            return -1;
        }
    }

    protected function get_media(
        $dbs,
        $tbl,
        $columns,
        $prefix = ''
    ) {
        $media = array();
        $wp_media = array();
        foreach ( $columns as $column ) {
            $media_type = WPDA_Media_Model::get_column_media( $tbl, $column['column_name'], $dbs );
            $column_name = $prefix . $column['column_name'];
            switch ( $media_type ) {
                case 'ImageURL':
                    $media[$column_name] = $media_type;
                    break;
                case 'Hyperlink':
                    // Get table settings.
                    $table_settings_db = WPDA_Table_Settings_Model::query( $tbl, $dbs );
                    if ( isset( $table_settings_db[0]['wpda_table_settings'] ) ) {
                        $table_settings = json_decode( $table_settings_db[0]['wpda_table_settings'], true );
                    } else {
                        $table_settings = null;
                    }
                    // Check hyperlink format.
                    if ( isset( $table_settings['table_settings']['hyperlink_definition'] ) && 'text' === $table_settings['table_settings']['hyperlink_definition'] ) {
                        $media[$column_name] = 'HyperlinkURL';
                    } else {
                        $media[$column_name] = 'HyperlinkObject';
                    }
                    break;
                default:
                    if ( false !== $media_type ) {
                        // Handle WordPress Media Library integration
                        $media[$column_name] = "WP-{$media_type}";
                    }
            }
            $wp_media[$column_name] = $media_type;
        }
        return [
            'media'    => $media,
            'wp_media' => $wp_media,
        ];
    }

    /**
     * Write standard JSON response.
     *
     * @param string $message Response text message.
     * @param mixed  $data Response data.
     * @param mixed  $context Context data.
     * @param mixed  $meta Meta data.
     * @return \WP_REST_Response
     */
    protected static function WPDA_Rest_Response(
        $message = '',
        $data = null,
        $context = null,
        $meta = null
    ) {
        // Prepare response.
        $response = new \WP_REST_Response(array(
            'code'    => 'ok',
            'message' => $message,
            'data'    => $data,
            'context' => $context,
            'meta'    => $meta,
        ), 200);
        // Disable caching.
        $response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0' );
        $response->header( 'Pragma', 'no-cache' );
        $response->header( 'Expires', '0' );
        return $response;
    }

    protected static function WPDA_Rest_Response_Info( $message = '' ) {
        // Prepare response.
        $response = new \WP_REST_Response(array(
            'code'    => 'info',
            'message' => $message,
            'data'    => null,
            'context' => null,
            'meta'    => null,
        ), 200);
        // Disable caching.
        $response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0' );
        $response->header( 'Pragma', 'no-cache' );
        $response->header( 'Expires', '0' );
        return $response;
    }

}

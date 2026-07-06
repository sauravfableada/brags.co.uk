<?php

namespace WPDataAccess\API;

use stdClass;
use WPDataAccess\Connection\WPDADB;
use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Access;
use WPDataAccess\Data_Dictionary\WPDA_List_Columns_Cache;
use WPDataAccess\Plugin_Table_Models\WPDA_Table_Settings_Model;
use WPDataAccess\Utilities\WPDA_WP_Media;
use WPDataAccess\WPDA;
class WPDA_Table extends WPDA_API_Core {
    const WPDA_SEARCH_MODES = array(
        'contains',
        'startsWith',
        'endsWith',
        'equals',
        'notEquals',
        'empty',
        'notEmpty',
        'between',
        'betweenInclusive',
        'greaterThan',
        'greaterThanOrEqualTo',
        'lessThan',
        'lessThanOrEqualTo'
    );

    const RELATIONTABLEPREFIX = 'relationTableColumn___';

    public function register_rest_routes() {
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'table/meta', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'table_meta'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
                'tbl' => $this->get_param( 'tbl' ),
                'waa' => array(
                    'required'    => false,
                    'type'        => 'boolean',
                    'description' => __( 'With admin actions (to support table exports)', 'wp-data-access' ),
                ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'table/select', array(
            'methods'             => array('GET', 'POST'),
            'callback'            => array($this, 'table_select'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs'                => $this->get_param( 'dbs' ),
                'tbl'                => $this->get_param( 'tbl' ),
                'col'                => $this->get_param( 'cols' ),
                'page_index'         => $this->get_param( 'page_index' ),
                'page_size'          => $this->get_param( 'page_size' ),
                'search'             => $this->get_param( 'search' ),
                'search_columns'     => $this->get_param( 'search_columns' ),
                'search_column_fns'  => $this->get_param( 'search_column_fns' ),
                'sorting'            => $this->get_param( 'sorting' ),
                'row_count'          => $this->get_param( 'row_count' ),
                'row_count_estimate' => $this->get_param( 'row_count_estimate' ),
                'media'              => $this->get_param( 'media' ),
                'client_side'        => $this->get_param( 'client_side' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'table/get', array(
            'methods'             => array('GET', 'POST'),
            'callback'            => array($this, 'table_get'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs'   => $this->get_param( 'dbs' ),
                'tbl'   => $this->get_param( 'tbl' ),
                'key'   => $this->get_param( 'key' ),
                'media' => $this->get_param( 'media' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'table/insert', array(
            'methods'             => array('GET', 'POST'),
            'callback'            => array($this, 'table_insert'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
                'tbl' => $this->get_param( 'tbl' ),
                'val' => $this->get_param( 'val' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'table/update', array(
            'methods'             => array('GET', 'POST'),
            'callback'            => array($this, 'table_update'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
                'tbl' => $this->get_param( 'tbl' ),
                'key' => $this->get_param( 'key' ),
                'val' => $this->get_param( 'val' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'table/delete', array(
            'methods'             => array('GET', 'POST'),
            'callback'            => array($this, 'table_delete'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
                'tbl' => $this->get_param( 'tbl' ),
                'key' => $this->get_param( 'key' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'table/lov', array(
            'methods'             => array('GET', 'POST'),
            'callback'            => array($this, 'table_lov'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
                'tbl' => $this->get_param( 'tbl' ),
                'col' => $this->get_param( 'col' ),
            ),
        ) );
    }

    /**
     * Get table meta info.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_meta( $request ) {
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $waa = $request->get_param( 'waa' );
        if ( $this->check_table_access(
            $dbs,
            $tbl,
            $request,
            'select',
            $msg
        ) ) {
            return $this->WPDA_Rest_Response( '', $this->get_table_meta_data( $dbs, $tbl, $waa ) );
        } else {
            if ( 'rest_cookie_invalid_nonce' === $msg ) {
                return $this->invalid_nonce();
            } else {
                return new \WP_Error('error', $msg, array(
                    'status' => 401,
                ));
            }
        }
    }

    /**
     * Database table query using the full primary key. Must return exactly one row.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_get( $request ) {
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $key = $request->get_param( 'key' );
        $media = $request->get_param( 'media' );
        if ( $this->check_table_access(
            $dbs,
            $tbl,
            $request,
            'select',
            $msg
        ) ) {
            return $this->get(
                $dbs,
                $tbl,
                $key,
                $media
            );
        } else {
            if ( 'rest_cookie_invalid_nonce' === $msg ) {
                return $this->invalid_nonce();
            } else {
                return new \WP_Error('error', $msg, array(
                    'status' => 401,
                ));
            }
        }
    }

    /**
     * Insert one row.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_insert( $request ) {
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $val = $request->get_param( 'val' );
        if ( $this->check_table_access(
            $dbs,
            $tbl,
            $request,
            'insert',
            $msg
        ) ) {
            return $this->insert( $dbs, $tbl, $val );
        } else {
            if ( 'rest_cookie_invalid_nonce' === $msg ) {
                return $this->invalid_nonce();
            } else {
                return new \WP_Error('error', $msg, array(
                    'status' => 401,
                ));
            }
        }
    }

    /**
     * Update uses primary key. Must return exactly one row.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_update( $request ) {
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $key = $request->get_param( 'key' );
        $val = $request->get_param( 'val' );
        if ( $this->check_table_access(
            $dbs,
            $tbl,
            $request,
            'update',
            $msg
        ) ) {
            return $this->update(
                $dbs,
                $tbl,
                $key,
                $val
            );
        } else {
            if ( 'rest_cookie_invalid_nonce' === $msg ) {
                return $this->invalid_nonce();
            } else {
                return new \WP_Error('error', $msg, array(
                    'status' => 401,
                ));
            }
        }
    }

    /**
     * Delete uses primary key. Must return exactly one row.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_delete( $request ) {
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $key = $request->get_param( 'key' );
        if ( $this->check_table_access(
            $dbs,
            $tbl,
            $request,
            'delete',
            $msg
        ) ) {
            return $this->delete( $dbs, $tbl, $key );
        } else {
            if ( 'rest_cookie_invalid_nonce' === $msg ) {
                return $this->invalid_nonce();
            } else {
                return new \WP_Error('error', $msg, array(
                    'status' => 401,
                ));
            }
        }
    }

    /**
     * Database table query to populate a list of values for a specific table/column.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_lov( $request ) {
    }

    /**
     * Database table query.
     *
     * Supports: searching, ordering and pagination.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_select( $request ) {
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $col = $request->get_param( 'col' );
        $page_index = $request->get_param( 'page_index' );
        $page_size = $request->get_param( 'page_size' );
        $search = $request->get_param( 'search' );
        $search_columns = $request->get_param( 'search_columns' );
        $search_column_fns = $request->get_param( 'search_column_fns' );
        $search_data_types = $request->get_param( 'search_data_types' );
        $sorting = $request->get_param( 'sorting' );
        $row_count = $request->get_param( 'row_count' );
        $row_count_estimate = $request->get_param( 'row_count_estimate' );
        $media = $request->get_param( 'media' );
        $client_side = '1' === $request->get_param( 'client_side' );
        if ( $this->check_table_access(
            $dbs,
            $tbl,
            $request,
            'select',
            $msg
        ) ) {
            return $this->select(
                $dbs,
                $tbl,
                $col,
                $page_index,
                $page_size,
                $search,
                $search_columns,
                $search_column_fns,
                $sorting,
                $row_count,
                $row_count_estimate,
                $media,
                '',
                '',
                array(),
                array(),
                array(),
                $search_data_types,
                $client_side
            );
        } else {
            if ( 'rest_cookie_invalid_nonce' === $msg ) {
                return $this->invalid_nonce();
            } else {
                return new \WP_Error('error', $msg, array(
                    'status' => 401,
                ));
            }
        }
    }

    /**
     * Perform query and return result as JSON response.
     *
     * @param string $dbs Schema name (database).
     * @param string $tbl Table Name.
     * @param array  $column_name Column name.
     * @param array  $search Global search.
     * @param array  $search_columns Column filters.
     * @param array  $search_column_fns Column filter fns.
     * @return \WP_Error|\WP_REST_Response
     */
    public function lov(
        $dbs,
        $tbl,
        $column_name,
        $cascade = false,
        $default_where = '',
        $search = '',
        $column_names = array(),
        $search_columns = array(),
        $search_column_fns = array(),
        $lookups = array(),
        $md = array(),
        $m2m_relationship = array(),
        $search_data_types = array()
    ) {
    }

    public function lookup(
        $dbs,
        $tbl,
        $column_key,
        $column_value,
        $column_dynamic_values,
        $default_where,
        $cascade = false,
        $cascade_table = '',
        $cascade_column = '',
        $cascade_where = '',
        $search = '',
        $column_names = array(),
        $search_columns = array(),
        $search_column_fns = array(),
        $lookups = array(),
        $md = array(),
        $m2m_relationship = array(),
        $search_data_types = array()
    ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( null === $wpdadb ) {
            // Error connecting.
            return new \WP_Error('error', "Error connecting to database {$dbs}", array(
                'status' => 420,
            ));
        } else {
            // Connected, perform queries.
            $suppress = $wpdadb->suppress_errors( true );
            $subquery = '';
            $where = '';
            if ( '' !== trim( $default_where ) ) {
                if ( 'where' !== strtolower( substr( trim( $default_where ), 0, 5 ) ) ) {
                    $where = "where {$default_where}";
                } else {
                    $where = $default_where;
                }
            }
            $dynamic_where = array();
            if ( is_array( $column_dynamic_values ) && 0 < count( $column_dynamic_values ) ) {
                foreach ( $column_dynamic_values as $key => $value ) {
                    $dynamic_where[] = $wpdadb->prepare( " `{$key}` = %s ", $value );
                }
                $where .= (( '' === $where ? ' where ' : ' and ' )) . ' (' . implode( ' and ', $dynamic_where ) . ') ';
            }
            $column_count = ( '' === $subquery ? '' : ", stats.total_rows as 'count'" );
            if ( strpos( $column_value, ',' ) !== false ) {
                $columns = explode( ',', $column_value );
                $columns = array_map( function ( $column ) use($wpdadb, $tbl) {
                    return $wpdadb->prepare( "`%1s`.`%1s`", [$tbl, $column] );
                }, $columns );
                $sql = $wpdadb->prepare( "\n\t\t\t\t\t\t\tselect distinct `%1s`.`%1s` as 'key'\n\t\t\t\t\t\t\t, %1s\n\t\t\t\t\t\t\t{$column_count}\n\t\t\t\t\t\t\tfrom `%1s`\n\t\t\t\t\t\t", array(
                    $tbl,
                    $column_key,
                    implode( ',', $columns ),
                    $tbl
                ) );
            } else {
                $sql = $wpdadb->prepare( "\n\t\t\t\t\t\t\tselect distinct `%1s`.`%1s` as 'key'\n\t\t\t\t\t\t\t, `%1s`.`%1s` as 'value'\n\t\t\t\t\t\t\t{$column_count}\n\t\t\t\t\t\t\tfrom `%1s`\n\t\t\t\t\t\t", array(
                    $tbl,
                    $column_key,
                    $tbl,
                    $column_value,
                    $tbl
                ) );
            }
            $orderby = ' order by 2 ';
            $sql .= " {$where} {$orderby} ";
            // $where and $orderby already sanitized and prepared
            $dataset = $wpdadb->get_results( $sql, 'OBJECT' );
            $wpdadb->suppress_errors( $suppress );
            // Send response.
            if ( '' === $wpdadb->last_error ) {
                // Prepare debug info.
                if ( 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_DEBUG ) ) {
                    $debug = array(
                        'debug' => array(
                            'sql'   => preg_replace( "/\\s+/", " ", $sql ),
                            'where' => $where ?? '',
                        ),
                    );
                } else {
                    $debug = null;
                }
                // Add context node to response.
                $context = array();
                if ( isset( $debug['debug'] ) && 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_DEBUG ) ) {
                    $context['debug'] = $debug['debug'];
                }
                return $this->WPDA_Rest_Response( '', $dataset, $context );
            } else {
                return new \WP_Error('error', $wpdadb->last_error, array(
                    'status' => 420,
                ));
            }
        }
    }

    /**
     * Perform query and return result as JSON response.
     *
     * @param string $dbs Schema name (database).
     * @param string $tbl Table Name.
     * @param array  $primary Primary (key|value pairs.
     * @param array  $media_columns Media columns.
     * @param array  $column_names Just a plain array containing the column names.
     * @return \WP_Error|\WP_REST_Response
     */
    public function get(
        $dbs,
        $tbl,
        $primary_key,
        $media_columns = array(),
        $column_names = array(),
        $default_where = ''
    ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( null === $wpdadb ) {
            // Error connecting.
            return new \WP_Error('error', "Error connecting to database {$dbs}", array(
                'status' => 420,
            ));
        } else {
            // Connected, perform queries.
            $suppress = $wpdadb->suppress_errors( true );
            $where = '';
            foreach ( $primary_key as $primary_key_column => $primary_key_value ) {
                $where = ( '' === $where ? ' where ' : $where . ' and ' );
                $where .= $wpdadb->prepare( " `%1s` = %s ", array($primary_key_column, $primary_key_value) );
            }
            if ( '' !== $default_where ) {
                if ( '' === $where ) {
                    $where = $default_where;
                } else {
                    $where .= " and {$default_where} ";
                }
            }
            // Get table column data types
            $column_list = WPDA_List_Columns_Cache::get_list_columns( $dbs, $tbl );
            $table_columns = $column_list->get_table_columns();
            // Prepare selected column list
            $columns_selected = array();
            $search_data_types = array();
            foreach ( $table_columns as $table_column ) {
                $columns_selected[$table_column['column_name']] = true;
                $search_data_types[$table_column['column_name']] = $table_column['data_type'];
            }
            $selected_columns = $this->get_selected_columns( $columns_selected, $search_data_types );
            $sql = $wpdadb->prepare( "\n                        select {$selected_columns}\n                        from `%1s`\n                        {$where}\n                    ", array($tbl) );
            $dataset = $wpdadb->get_results( $sql, 'ARRAY_A' );
            // Prepare debug info.
            if ( 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_DEBUG ) ) {
                $debug = array(
                    'debug' => array(
                        'sql'   => $sql,
                        'where' => $where,
                    ),
                );
            } else {
                $debug = null;
            }
            $wpdadb->suppress_errors( $suppress );
            // Send response.
            $media = array();
            if ( is_array( $media_columns ) && 0 < count( $media_columns ) ) {
                foreach ( $media_columns as $media_column_name => $media_column_type ) {
                    if ( isset( $dataset[0][$media_column_name] ) ) {
                        if ( in_array( $media_column_type, [
                            'WP-Image',
                            'WP-Attachment',
                            'WP-Audio',
                            'WP-Video'
                        ] ) ) {
                            $media[$media_column_name] = WPDA_WP_Media::get_media_url( $dataset[0][$media_column_name] );
                        }
                    }
                }
            }
            $context = array();
            $context['media'] = $media;
            if ( isset( $debug['debug'] ) && 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_DEBUG ) ) {
                $context['debug'] = $debug['debug'];
            }
            if ( 0 === count( $dataset ) ) {
                return $this->WPDA_Rest_Response( 'No data found', $dataset, $context );
            } else {
                if ( 1 === count( $dataset ) ) {
                    return $this->WPDA_Rest_Response( '', $dataset, $context );
                } else {
                    return $this->WPDA_Rest_Response( 'Query returned more than one row', $dataset, $context );
                }
            }
        }
    }

    public function insert( $dbs, $tbl, $column_values ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( null === $wpdadb ) {
            // Error connecting.
            return new \WP_Error('error', "Error connecting to database {$dbs}", array(
                'status' => 420,
            ));
        } else {
            // Get column default values
            $column_list = WPDA_List_Columns_Cache::get_list_columns( $dbs, $tbl );
            $table_columns = $column_list->get_table_columns();
            foreach ( $table_columns as $table_column_type ) {
                if ( isset( $column_values[$table_column_type['column_name']] ) && $column_values[$table_column_type['column_name']] === $table_column_type['column_default'] ) {
                    // Remove default values if send values equals column default to support defaults using functions
                    unset($column_values[$table_column_type['column_name']]);
                }
            }
            // Sanitize column names and values.
            $sanitized_column_values = self::sanitize_column_values( $dbs, $tbl, $column_values );
            if ( false === $sanitized_column_values ) {
                return new \WP_Error('error', "Invalid arguments", array(
                    'status' => 420,
                ));
            }
            // Insert row.
            $rows_inserted = $wpdadb->insert( $tbl, $sanitized_column_values );
            // Send response.
            if ( 1 === $rows_inserted ) {
                return $this->WPDA_Rest_Response( __( 'Row successfully inserted', 'wp-data-access' ), null, array(
                    'insert_id' => $wpdadb->insert_id,
                ) );
            } else {
                if ( '' !== $wpdadb->last_error ) {
                    return new \WP_Error('error', $wpdadb->last_error, array(
                        'status' => 420,
                    ));
                } else {
                    return new \WP_Error('error', 'Insert failed', array(
                        'status' => 420,
                    ));
                }
            }
        }
    }

    public function update(
        $dbs,
        $tbl,
        $primary_key,
        $column_values,
        $column_names = array(),
        $code_columns = array(),
        $html_columns = array()
    ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( null === $wpdadb ) {
            // Error connecting.
            return new \WP_Error('error', "Error connecting to database {$dbs}", array(
                'status' => 420,
            ));
        } else {
            // Sanitize column names and values.
            $sanitized_column_values = self::sanitize_column_values(
                $dbs,
                $tbl,
                $column_values,
                $code_columns,
                $html_columns
            );
            if ( false === $sanitized_column_values ) {
                return new \WP_Error('error', "Invalid arguments", array(
                    'status' => 420,
                ));
            }
            // Update row.
            $rows_inserted = $wpdadb->update( $tbl, $sanitized_column_values, $primary_key );
            // Send response.
            if ( 0 === $rows_inserted ) {
                return $this->WPDA_Rest_Response_Info( 'Nothing to update' );
            } elseif ( 1 === $rows_inserted ) {
                $context = null;
                if ( 0 < count( $column_names ) ) {
                    // Return updated values
                    $updated_row = $this->get(
                        $dbs,
                        $tbl,
                        $primary_key,
                        $column_names
                    );
                    if ( isset( $updated_row->data['data'][0] ) ) {
                        $updated_values = $updated_row->data['data'][0];
                        $updated_context = array();
                        foreach ( $updated_values as $key => $value ) {
                            if ( !isset( $column_values[$key] ) ) {
                                $updated_context[$key] = $value;
                            }
                        }
                        if ( 0 < count( $updated_context ) ) {
                            $context = array(
                                'updated' => $updated_context,
                            );
                        }
                    }
                }
                return $this->WPDA_Rest_Response( __( 'Row successfully updated', 'wp-data-access' ), null, $context );
            } else {
                if ( '' !== $wpdadb->last_error ) {
                    return new \WP_Error('error', $wpdadb->last_error, array(
                        'status' => 420,
                    ));
                } else {
                    return new \WP_Error('error', 'Update failed', array(
                        'status' => 420,
                    ));
                }
            }
        }
    }

    public function delete( $dbs, $tbl, $primary_key ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( null === $wpdadb ) {
            // Error connecting.
            return new \WP_Error('error', "Error connecting to database {$dbs}", array(
                'status' => 420,
            ));
        } else {
            // Delete row.
            $rows_deleted = $wpdadb->delete( $tbl, $primary_key );
            // Send response.
            if ( 0 === $rows_deleted ) {
                return $this->WPDA_Rest_Response_Info( __( 'No data found', 'wp-data-access' ) );
            } elseif ( 1 === $rows_deleted ) {
                return $this->WPDA_Rest_Response( __( 'Row successfully deleted', 'wp-data-access' ) );
            } else {
                if ( '' !== $wpdadb->last_error ) {
                    return new \WP_Error('error', $wpdadb->last_error, array(
                        'status' => 420,
                    ));
                } else {
                    return new \WP_Error('error', 'Delete failed', array(
                        'status' => 420,
                    ));
                }
            }
        }
    }

    private function generate_lookup_condition(
        $wpdadb,
        $lookups,
        $column_name,
        $search_values,
        $search_column_fns,
        $filter_mode = null,
        $filter_key = false
    ) {
        $lookup = $lookups[$column_name];
        $lookup_table = $lookup['tbl'];
        $lookup_key = $lookup['key'];
        $lookup_columns = explode( ',', $lookup['value'] );
        $lookup_where = array();
        if ( $filter_key ) {
            $filter_columns = array($lookup_key);
        } else {
            $filter_columns = $lookup_columns;
        }
        foreach ( $filter_columns as $lookup_column ) {
            foreach ( $search_values as $search_value ) {
                $lookup_where[] = $this->add_filter(
                    $wpdadb,
                    $lookup_column,
                    ( $filter_mode !== null ? $filter_mode : $search_column_fns[$column_name] ),
                    $search_value
                );
            }
        }
        if ( 0 < count( $lookup_where ) ) {
            return $wpdadb->prepare( ' `%1s` in ( select `%1s` from `%1s` where (' . implode( ' or ', $lookup_where ) . ') ) ', array(
                $column_name,
                $lookup_key,
                $lookup_table,
                $lookup_columns[0],
                "%{$search_values[0]}%"
            ) );
        } else {
            return null;
        }
    }

    public static function remove_where_from_sql( $sql ) {
        if ( 'where' === substr( trim( $sql ), 0, 5 ) ) {
            $pos = strpos( $sql, 'where' );
            if ( false !== $pos ) {
                $sql = substr_replace(
                    $sql,
                    '',
                    $pos,
                    5
                );
            }
        }
        return $sql;
    }

    private function get_md( $md, $wpdadb, $m2m_relationship ) {
    }

    private function get_global_filter(
        $wpdadb,
        $search,
        $column_names,
        $lookups,
        $m2m_relationship
    ) {
        $where_global = array();
        if ( null !== $search && "" !== $search ) {
            foreach ( $column_names as $column_name => $queryable ) {
                if ( $queryable ) {
                    if ( isset( $lookups[$column_name] ) ) {
                        // Perform look search.
                        $condition = $this->generate_lookup_condition(
                            $wpdadb,
                            $lookups,
                            $column_name,
                            array($search),
                            array(),
                            'contains'
                        );
                        if ( null !== $condition ) {
                            $where_global[] = $condition;
                        }
                    } else {
                        $where_global[] = $wpdadb->prepare( " `%1s` like '%s' ", array($this->convert_column_name( $m2m_relationship, $column_name ), '%' . esc_sql( $search ) . '%') );
                    }
                }
            }
        }
        return $where_global;
    }

    private function get_column_filters(
        $wpdadb,
        $search_columns,
        $search_column_fns,
        $lookups,
        $m2m_relationship,
        $search_data_types
    ) {
    }

    private function get_where(
        $wpdadb,
        $default_where,
        $md,
        $m2m_relationship,
        $search,
        $column_names,
        $lookups,
        $search_columns,
        $search_column_fns,
        $search_data_types,
        $geo_radius = array(),
        $operator = 'and'
    ) {
        // Default where.
        if ( '' !== trim( $default_where ) && 'where' !== strtolower( substr( trim( $default_where ), 0, 5 ) ) ) {
            $where = "where {$default_where}";
        } else {
            $where = $default_where;
        }
        // Global filter.
        $where_global = $this->get_global_filter(
            $wpdadb,
            $search,
            $column_names,
            $lookups,
            $m2m_relationship
        );
        if ( 0 < count( $where_global ) ) {
            $where .= (( '' === trim( $where ) ? ' where ' : ' and ' )) . $this->add_condition( $where_global, 'or' );
        }
        if ( is_array( $geo_radius ) && 0 < count( $geo_radius ) ) {
            // Add geo radius to query
            // Variable $geo_radius already sanitized in REST API
            $unit = ( "km" == $geo_radius['unit'] ? 1000 : 1609.344 );
            // km versus miles
            if ( $geo_radius['col']['lat'] === $geo_radius['col']['lng'] ) {
                // Location stored in GEOMETRY or POINT data type
                $geocol = $geo_radius['col']['lat'];
                $geo_where = " ( st_distance_sphere(point(st_y(`{$geocol}`), st_x(`{$geocol}`)), point({$geo_radius['loc']['lng']}, {$geo_radius['loc']['lat']})) / {$unit} ) < {$geo_radius['radius']} ";
            } else {
                // Latitude and longitude stored separately
                $geo_where = " ( st_distance_sphere(point(`{$geo_radius['col']['lng']}`, `{$geo_radius['col']['lat']}`), point({$geo_radius['loc']['lng']}, {$geo_radius['loc']['lat']})) / {$unit} ) < {$geo_radius['radius']} ";
            }
            if ( '' === $where ) {
                $where = " where {$geo_where} ";
            } else {
                $where .= " and {$geo_where} ";
            }
        }
        return $where;
    }

    private function get_selected_columns( $column_names, $search_data_types ) {
        if ( !is_array( $column_names ) ) {
            return '*';
            // select all columns
        }
        // Check for geo columns
        $geometryColumns = array();
        if ( is_array( $search_data_types ) ) {
            foreach ( $search_data_types as $column_name => $search_data_type ) {
                if ( 'geometry' === strtolower( $search_data_type ) || 'point' === strtolower( $search_data_type ) ) {
                    $geometryColumns[] = $column_name;
                }
            }
        }
        return implode( ",", array_map( function ( $column_name ) use($geometryColumns) {
            if ( in_array( $column_name, $geometryColumns ) ) {
                return 'ST_AsText(`' . WPDA::remove_backticks( $column_name ) . '`) ' . " as `{$column_name}` ";
                // Convert geo data to string
            } else {
                return '`' . WPDA::remove_backticks( $column_name ) . '`';
            }
        }, array_keys( $column_names ) ) );
    }

    /**
     * Perform query and return result as JSON response.
     *
     * @param string  $dbs Schema name (database).
     * @param string  $tbl Table Name.
     * @param string  $column_names Column Names.
     * @param string  $page_index Page number.
     * @param string  $page_size Rows per page.
     * @param string  $search Filter.
     * @param string  $search_columns Column search filters.
     * @param string  $search_column_fns Column search filter modes.
     * @param string  $Sorting Order by.
     * @param integer $last_row_count Row count previous request.
     * @param string  $row_count_estimate Indicates if row count estimate should be used.
     * @param string  $media_columns Media columns.
     * @param string  $default_where Defaul where clause
     * @param string  $default_orderby Defaul order by clause
     * @return \WP_Error|\WP_REST_Response
     */
    public function select(
        $dbs,
        $tbl,
        $column_names,
        $page_index,
        $page_size,
        $search,
        $search_columns,
        $search_column_fns,
        $sorting,
        $last_row_count,
        $row_count_estimate,
        $media_columns = array(),
        $default_where = '',
        $default_orderby = '',
        $lookups = array(),
        $md = array(),
        $m2m_relationship = array(),
        $search_data_types = array(),
        $client_side = false,
        $geo_radius = array()
    ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( null === $wpdadb ) {
            // Error connecting.
            return new \WP_Error('error', "Error connecting to database {$dbs}", array(
                'status' => 420,
            ));
        } else {
            $suppress = $wpdadb->suppress_errors( true );
            // Build where clause.
            $where = $this->get_where(
                $wpdadb,
                $default_where,
                $md,
                $m2m_relationship,
                $search,
                $column_names,
                $lookups,
                $search_columns,
                $search_column_fns,
                $search_data_types,
                $geo_radius
            );
            // Build order by.
            $sqlorder = '';
            if ( is_array( $sorting ) && 0 < count( $sorting ) ) {
                foreach ( $sorting as $sort ) {
                    if ( '' === $sqlorder ) {
                        $sqlorder = 'order by ';
                    } else {
                        $sqlorder .= ',';
                    }
                    if ( !$client_side && isset( $lookups[$sort['id']] ) ) {
                        // Use lookup table to sort
                        $lookup = $lookups[$sort['id']];
                        $lookup_dbs = $lookup['dbs'];
                        $lookup_wpdadb = ( $dbs === $lookup_dbs ? $wpdadb : WPDADB::get_db_connection( $lookup_dbs ) );
                        if ( $lookup_wpdadb !== null ) {
                            $lookup_tbl = $lookup['tbl'];
                            $lookup_key = $lookup['key'];
                            $lookup_value = $lookup['value'];
                            $lookup_dataset = $lookup_wpdadb->get_results( $lookup_wpdadb->prepare( "select `%1s`, `%1s` from `%1s` order by 2", array($lookup_key, $lookup_value, $lookup_tbl) ), 'ARRAY_N' );
                            $lookup_orderby = 'case `' . $this->convert_column_name( $m2m_relationship, $sort['id'] ) . '` ';
                            foreach ( $lookup_dataset as $index => $value ) {
                                $lookup_orderby .= $lookup_wpdadb->prepare( 'when %s then %d ', array($value[0], $index) );
                            }
                            $lookup_orderby .= 'else `' . $this->convert_column_name( $m2m_relationship, $sort['id'] ) . '` end ' . (( $sort['desc'] ? 'desc' : 'asc' ));
                            $sqlorder .= $lookup_orderby;
                        } else {
                            $sqlorder .= '`' . $this->convert_column_name( $m2m_relationship, $sort['id'] ) . '` ' . (( $sort['desc'] ? 'desc' : 'asc' ));
                        }
                    } else {
                        // Normal sort
                        $sqlorder .= '`' . $this->convert_column_name( $m2m_relationship, $sort['id'] ) . '` ' . (( $sort['desc'] ? 'desc' : 'asc' ));
                    }
                }
            }
            if ( '' === $sqlorder && '' !== trim( $default_orderby ) ) {
                $sqlorder = $default_orderby;
            }
            // Add pagination.
            if ( !is_numeric( $page_size ) ) {
                $page_size = 10;
            }
            $offset = $page_index * $page_size;
            // Calculate offset.
            if ( !is_numeric( $offset ) ) {
                $offset = 0;
            }
            // Prepare query.
            $sql = "\n\t\t\t\t\tselect " . $this->get_selected_columns( $column_names, $search_data_types ) . "\n\t\t\t\t\tfrom `%1s`\n\t\t\t\t\t{$where}\n\t\t\t\t\t{$sqlorder}\n\t\t\t\t";
            $sql_tables = array($tbl);
            // Perpare query.
            $sql = $wpdadb->prepare( ( true === $client_side ? $sql : $sql . (( 0 < $page_size ? " limit {$page_size} offset {$offset} " : '' )) ), $sql_tables );
            // Prepare debug info.
            if ( 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_DEBUG ) ) {
                $debug = array(
                    'sql'      => preg_replace( "/\\s+/", " ", $sql ),
                    'where'    => $where,
                    'order by' => $sqlorder,
                );
            } else {
                $debug = null;
            }
            // Perform query.
            $dataset = $wpdadb->get_results( $sql, 'ARRAY_A' );
            if ( $wpdadb->last_error ) {
                // Handle SQL errors.
                return new \WP_Error('error', $wpdadb->last_error, array(
                    'status' => 420,
                    'debug'  => $debug,
                ));
            }
            if ( is_numeric( $last_row_count ) and 0 <= $last_row_count ) {
                // Prevents additional unnecessary queries.
                $rowcount = $last_row_count;
            } else {
                if ( true === $client_side ) {
                    $rowcount = 0;
                } else {
                    $estimate = false;
                    if ( '1' === $row_count_estimate && '' === $where ) {
                        // Perform row count estimate
                        $countrows = $wpdadb->get_results( $wpdadb->prepare( "\n\t\t\t\t\t\t\t\t\tselect table_rows as rowcount\n\t\t\t\t\t\t\t\t\t from  information_schema.tables\n\t\t\t\t\t\t\t\t\twhere  table_schema = %s\n\t\t\t\t\t\t\t\t\t  and  table_name = %s\n\t\t\t\t\t\t\t\t", [$wpdadb->dbname, $tbl] ), 'ARRAY_A' );
                        if ( isset( $countrows[0]['rowcount'] ) && 0 != $countrows[0]['rowcount'] ) {
                            $estimate = true;
                        }
                    }
                    if ( !$estimate ) {
                        if ( !$estimate ) {
                            // (Re)Count rows.
                            $countrows = $wpdadb->get_results( $wpdadb->prepare( "\n\t\t\t\t\t\t\t\t\t\tselect count(1) as rowcount\n\t\t\t\t\t\t\t\t\t\tfrom `%1s`\n\t\t\t\t\t\t\t\t\t\t{$where}\n\t\t\t\t\t\t\t\t\t", array($tbl) ), 'ARRAY_A' );
                        }
                    }
                    if ( $wpdadb->last_error ) {
                        // Handle SQL errors.
                        return new \WP_Error('error', $wpdadb->last_error, array(
                            'status' => 420,
                        ));
                    }
                    if ( isset( $countrows[0]['rowcount'] ) ) {
                        $rowcount = $countrows[0]['rowcount'];
                    } else {
                        $rowcount = 0;
                    }
                }
            }
            // Add context node to response
            $context = array();
            if ( 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_DEBUG ) ) {
                $context['debug'] = $debug;
            }
            if ( is_array( $media_columns ) && 0 < count( $media_columns ) ) {
                // Handle WP media library
                $media = array();
                for ($i = 0; $i < count( $dataset ); $i++) {
                    $media_row = array();
                    foreach ( $media_columns as $media_column_name => $media_column_type ) {
                        if ( isset( $dataset[$i][$media_column_name] ) ) {
                            $media_row[$media_column_name] = WPDA_WP_Media::get_media_url( $dataset[$i][$media_column_name] );
                        }
                    }
                    $media[] = $media_row;
                }
                // Add media to context node
                $context['media'] = $media;
            }
            $wpdadb->suppress_errors( $suppress );
            // Send response.
            $response = $this->WPDA_Rest_Response(
                '',
                $dataset,
                $context,
                array(
                    'rowCount' => $rowcount,
                )
            );
            $response->header( 'X-WP-Total', $rowcount );
            // Total rows for this query.
            if ( 0 < $page_size ) {
                $pagecount = floor( $rowcount / $page_size );
                if ( $pagecount != $rowcount / $page_size ) {
                    // phpcs:ignore WordPress.PHP.StrictComparisons
                    $pagecount++;
                }
            } else {
                // Prevent division by zero
                $pagecount = 0;
            }
            $response->header( 'X-WP-TotalPages', $pagecount );
            // Total pages for this query.
            return $response;
        }
    }

    private function convert_column_name( $m2m_relationship, $column_name ) {
        // Return plain column name.
        return $this->sanitize_db_identifier( $column_name );
    }

    private function map_columns( $prefix, $column_names ) {
        return implode( ",", array_map( function ( $v ) use($prefix) {
            $c = $this->sanitize_db_identifier( $v );
            $r = ( 'd' === $prefix ? static::RELATIONTABLEPREFIX . $c : $c );
            return "`{$prefix}`.`{$c}` as \"{$r}\"";
        }, array_keys( $column_names ) ) );
    }

    public function add_filter(
        $wpdadb,
        $search_column,
        $search_column_fns,
        $search_value,
        $m2m_relationship = array(),
        $search_data_types = array()
    ) {
    }

    public static function add_condition( $where_lines, $operand = 'and' ) {
        if ( 0 < count( array_filter( $where_lines ) ) ) {
            // Apply all searches.
            return ' ( (' . implode( ") {$operand} (", array_filter( $where_lines ) ) . ') ) ';
        } else {
            return "";
        }
    }

    /**
     * Get table meta data.
     *
     * @param string $dbs Database schema name.
     * @param string $tbl Database table name.
     * @param string $waa With admin actions.
     * @return array\object
     */
    public function get_table_meta_data( $dbs, $tbl, $waa ) {
        $sql_create_table = '';
        if ( WPDA::current_user_is_admin() ) {
            // Admin user has access to all resources
            $access = array(
                'select' => array('POST'),
                'insert' => array('POST'),
                'update' => array('POST'),
                'delete' => array('POST'),
            );
            // Get create table script
            $wpdadb = WPDADB::get_db_connection( $dbs );
            if ( null !== $wpdadb ) {
                $suppress_errors = $wpdadb->suppress_errors;
                $wpdadb->suppress_errors = true;
                // NO_TABLE_OPTIONS is deprecated in V8
                // $wpdadb->query( "SET sql_mode = 'NO_TABLE_OPTIONS'" );
                $sql = $wpdadb->get_results( $wpdadb->prepare( 'show create table `%1s`', array($tbl) ), 'ARRAY_N' );
                if ( isset( $sql[0][1] ) ) {
                    $sql_create_table = $sql[0][1];
                }
                $wpdadb->suppress_errors = $suppress_errors;
            }
        } else {
            $access = $this->get_table_access( $dbs, $tbl );
        }
        $settings = new stdClass();
        if ( null !== $access ) {
            $columns = WPDA_List_Columns_Cache::get_list_columns( $dbs, $tbl );
            $settings_db = WPDA_Table_Settings_Model::query( $tbl, $dbs );
            if ( isset( $settings_db[0]['wpda_table_settings'] ) ) {
                $settings = json_decode( $settings_db[0]['wpda_table_settings'] );
                // Remove old settings from response.
                unset($settings->form_labels);
                unset($settings->list_labels);
                unset($settings->custom_settings);
                unset($settings->search_settings);
            }
            $settings->ui = WPDA_Settings::get_admin_settings( $dbs, $tbl );
            $rest_api = get_option( WPDA_API::WPDA_REST_API_TABLE_ACCESS );
            if ( isset( $rest_api[$dbs][$tbl] ) ) {
                $settings->rest_api = $rest_api[$dbs][$tbl];
            }
            $settings->env = $this->get_env();
            $wp_nonce_action_alter = "wpda-alter-{$tbl}";
            $wp_nonce_alter = wp_create_nonce( $wp_nonce_action_alter );
            $wp_nonce_refresh = null;
            $connect = null;
            global $wpdb;
            $settings->wp = [
                'roles'          => $this->get_wp_roles(),
                'users'          => $this->get_wp_users(),
                'home'           => admin_url( 'admin.php' ),
                'homea'          => admin_url( 'admin-ajax.php' ),
                'tables'         => array_values( $wpdb->tables() ),
                'date_format'    => get_option( 'date_format' ),
                'time_format'    => get_option( 'time_format' ),
                'alter'          => $wp_nonce_alter,
                'refresh'        => $wp_nonce_refresh,
                'connect'        => $connect,
                'copyinprogress' => WPDA_Actions::copy_in_progress(),
                'scroll_offset'  => WPDA::get_option( WPDA::OPTION_APPS_SCROLL_OFFSET ),
            ];
            if ( true === $waa ) {
                $settings->wp['aonce'] = implode( '-', array(
                    wp_create_nonce( 'wpda-export-' . json_encode( $tbl ) ),
                    // Table export
                    wp_create_nonce( 'wpda-rename-' . $tbl ),
                ) );
            }
            $table_columns = $columns->get_table_columns();
            $media = $this->get_media( $dbs, $tbl, $table_columns );
            $columns_sorted = array();
            foreach ( $table_columns as $column ) {
                if ( isset( $column['column_name'] ) ) {
                    $columns_sorted[$column['column_name']] = $column;
                }
            }
        }
        return array(
            'columns'        => $table_columns,
            'columns_sorted' => $columns_sorted,
            'table_labels'   => $columns->get_table_header_labels(),
            'form_labels'    => $columns->get_table_column_headers(),
            'primary_key'    => $columns->get_table_primary_key(),
            'access'         => $access,
            'settings'       => $settings,
            'media'          => $media['media'],
            'wp_media'       => $media['wp_media'],
            'table_info'     => $this->get_table_info( $dbs, $tbl ),
            'create'         => $sql_create_table,
        );
    }

    private function get_table_access( $dbs, $tbl ) {
        if ( WPDA::current_user_is_admin() ) {
            // Check administrator rights
            if ( is_admin() ) {
                $access = WPDA_Dictionary_Access::check_table_access_backend( $dbs, $tbl, $done );
            } else {
                $access = WPDA_Dictionary_Access::check_table_access_frontend( $dbs, $tbl, $done );
            }
            if ( $access ) {
                // Administrator access granted
                return array(
                    'select' => array('POST'),
                    'insert' => array('POST'),
                    'update' => array('POST'),
                    'delete' => array('POST'),
                );
            }
        }
        $tables = get_option( WPDA_API::WPDA_REST_API_TABLE_ACCESS );
        if ( false !== $tables && isset( $tables[$dbs][$tbl] ) && is_array( $tables[$dbs][$tbl] ) ) {
            $table = $tables[$dbs][$tbl];
            $table_access = new \stdClass();
            $table_access->select = $this->get_table_access_action( $table, 'select' );
            $table_access->insert = $this->get_table_access_action( $table, 'insert' );
            $table_access->update = $this->get_table_access_action( $table, 'update' );
            $table_access->delete = $this->get_table_access_action( $table, 'delete' );
            return $table_access;
        }
        return false;
    }

    private function get_table_access_action( $table, $action ) {
        if ( isset( $table[$action]['authorization'], $table[$action]['methods'] ) && is_array( $table[$action]['methods'] ) && 0 < count( $table[$action]['methods'] ) ) {
            if ( 'anonymous' === $table[$action]['authorization'] ) {
                return $table[$action]['methods'];
            } else {
                // Check authorized users
                if ( isset( $table[$action]['authorized_users'] ) && is_array( $table[$action]['authorized_users'] ) && 0 < count( $table[$action]['authorized_users'] ) && in_array( (string) $this->get_user_login(), $table[$action]['authorized_users'] ) ) {
                    return $table[$action]['methods'];
                }
                // Check authorized roles
                if ( isset( $table[$action]['authorized_roles'] ) && is_array( $table[$action]['authorized_roles'] ) && 0 < count( $table[$action]['authorized_roles'] ) && 0 < count( array_intersect( $this->get_user_roles(), $table[$action]['authorized_roles'] ) ) ) {
                    return $table[$action]['methods'];
                }
            }
        }
        return array();
    }

    /**
     * Check if access is grant for requested database/table.
     *
     * @param string $dbs Remote or local database connection string.
     * @param string $tbl Database table name.
     * @param onject $request Request object.
     * @param string $action Possible values: select, insert, update, delete.
     * @return bool
     */
    private function check_table_access(
        $dbs,
        $tbl,
        $request,
        $action,
        &$msg = ''
    ) {
        if ( WPDA::current_user_is_admin() ) {
            // Grant access to administrators always.
            return true;
        }
        $tables = get_option( WPDA_API::WPDA_REST_API_TABLE_ACCESS );
        if ( false === $tables ) {
            // No tables.
            $msg = __( 'Unauthorized', 'wp-data-access' );
            return false;
        }
        if ( !(isset( $tables[$dbs][$tbl][$action]['methods'] ) && is_array( $tables[$dbs][$tbl][$action]['methods'] )) ) {
            // No methods.
            $msg = __( 'Unauthorized', 'wp-data-access' );
            return false;
        } else {
            if ( !in_array( $request->get_method(), $tables[$dbs][$tbl][$action]['methods'] ) ) {
                //phpcs:ignore - 8.1 proof
                $msg = __( 'Unauthorized', 'wp-data-access' );
                return false;
            }
        }
        if ( !isset( $tables[$dbs][$tbl][$action]['authorization'] ) ) {
            // No authorization.
            $msg = __( 'Unauthorized', 'wp-data-access' );
            return false;
        } else {
            if ( 'anonymous' === $tables[$dbs][$tbl][$action]['authorization'] ) {
                // Access granted to all users.
                return true;
            }
        }
        global $wp_rest_auth_cookie;
        if ( true !== $wp_rest_auth_cookie ) {
            // No anonymous access.
            $msg = __( 'Unauthorized', 'wp-data-access' );
            return false;
        } else {
            if ( 'authorized' !== $tables[$dbs][$tbl][$action]['authorization'] ) {
                // Authorization check.
                $msg = __( 'Unauthorized', 'wp-data-access' );
                return false;
            }
            // Authorized access requires a valid nonce.
            if ( !wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
                $msg = 'rest_cookie_invalid_nonce';
                return false;
            }
            if ( !(isset( $tables[$dbs][$tbl][$action]['authorized_users'] ) && is_array( $tables[$dbs][$tbl][$action]['authorized_users'] )) ) {
                // No users.
                $msg = __( 'Unauthorized', 'wp-data-access' );
                return false;
            } else {
                $requesting_user_login = $this->get_user_login();
                if ( 0 < count( $tables[$dbs][$tbl][$action]['authorized_users'] ) && in_array( $requesting_user_login, $tables[$dbs][$tbl][$action]['authorized_users'] ) ) {
                    return true;
                }
            }
            if ( !(isset( $tables[$dbs][$tbl][$action]['authorized_roles'] ) && is_array( $tables[$dbs][$tbl][$action]['authorized_roles'] )) ) {
                // No roles.
                $msg = __( 'Unauthorized', 'wp-data-access' );
                return false;
            } else {
                $requesting_user_roles = $this->get_user_roles();
                if ( false === $requesting_user_roles ) {
                    $requesting_user_roles = array();
                }
                if ( 0 < count( $tables[$dbs][$tbl][$action]['authorized_roles'] ) && 0 < count( array_intersect( $requesting_user_roles, $tables[$dbs][$tbl][$action]['authorized_roles'] ) ) ) {
                    return true;
                }
            }
            $msg = __( 'Unauthorized', 'wp-data-access' );
            return false;
        }
    }

    private function sanitize_column_values(
        $dbs,
        $tbl,
        $column_values,
        $code_columns = array(),
        $html_columns = array()
    ) {
        $wpda_list_columns = WPDA_List_Columns_Cache::get_list_columns( $dbs, $tbl );
        $sanitized_column_values = [];
        foreach ( $column_values as $column_name => $column_value ) {
            $column_value = $column_values[$column_name];
            switch ( $wpda_list_columns->get_column_data_type( $column_name ) ) {
                case 'tinytext':
                case 'text':
                case 'mediumtext':
                case 'longtext':
                    if ( null !== $column_value ) {
                        if ( in_array( $column_name, $html_columns ) ) {
                            $column_value = sanitize_textarea_field( $column_value );
                        } else {
                            $column_value = wp_kses_post( $column_value );
                        }
                    }
                    break;
                default:
                    if ( null !== $column_value ) {
                        $column_value = sanitize_text_field( $column_value );
                    }
            }
            $sanitized_column_values[$this->sanitize_db_identifier( $column_name )] = $column_value;
        }
        return $sanitized_column_values;
    }

}

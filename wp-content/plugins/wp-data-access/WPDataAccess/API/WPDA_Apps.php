<?php

namespace WPDataAccess\API;

use stdClass;
use WPDataAccess\Connection\WPDADB;
use WPDataAccess\Data_Dictionary\WPDA_List_Columns_Cache;
use WPDataAccess\Plugin_Table_Models\WPDA_App_Container_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_App_Apps_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_App_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_Table_Settings_Model;
use WPDataAccess\Utilities\WPDA_App_Localization;
use WPDataAccess\WPDA;
class WPDA_Apps extends WPDA_API_Core {
    const METHODS = array('httpGet', 'httpPost', 'httpRequest');

    const WPDA_APP_DEFAULT_LANG = 'wpda_app_default_lang';

    private function sanitize_settings( $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $index => $item ) {
                $value[$index] = $this->sanitize_settings( $item );
            }
        } elseif ( is_object( $value ) ) {
            $object_vars = get_object_vars( $value );
            foreach ( $object_vars as $property_name => $property_value ) {
                $value->{$property_name} = $this->sanitize_settings( $property_value );
            }
        } else {
            // Allow HTML and onclick for computed fields
            $value = apply_filters(
                'wp_kses_post',
                $value,
                "",
                ["onclick"]
            );
        }
        return $value;
    }

    public function register_rest_routes() {
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/init', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_init'),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/list', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_list'),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/meta', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_meta'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id' => $this->get_param( 'app_id' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/lang', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_lang'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'lang' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => __( 'App default language', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/table/meta', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_table_meta'),
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
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/create', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_create'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_name'     => $this->get_param( 'app_name' ),
                'app_title'    => $this->get_param( 'app_title' ),
                'app_type'     => $this->get_param( 'app_type' ),
                'app_settings' => $this->get_param( 'app_settings' ),
                'app_dbs'      => $this->get_param( 'dbs' ),
                'app_tbl'      => $this->get_param( 'tbl' ),
                'app_cls'      => $this->get_param( 'app_cls' ),
                'app_table'    => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => __( 'App table', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'app_query'    => $this->get_param( 'app_query' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/createapp', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_createapp'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_name'     => $this->get_param( 'app_name' ),
                'app_title'    => $this->get_param( 'app_title' ),
                'app_type'     => $this->get_param( 'app_type' ),
                'app_settings' => $this->get_param( 'app_settings' ),
                'app_apps'     => $this->get_param( 'app_apps' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/copy', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_copy'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id' => $this->get_param( 'app_id' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/export', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_export'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id' => $this->get_param( 'app_id' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/details', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_details'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id' => $this->get_param( 'app_id' ),
                'cnt_id' => $this->get_param( 'cnt_id' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/detailmeta', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_detail_meta'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'  => $this->get_param( 'app_id' ),
                'cnt_id'  => $this->get_param( 'cnt_id' ),
                'rel_tab' => $this->get_param( 'rel_tab' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/detailreorder', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_detail_reorder'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'      => $this->get_param( 'app_id' ),
                'cnt_id_from' => $this->get_param( 'cnt_id' ),
                'cnt_id_to'   => $this->get_param( 'cnt_id' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/relationship/create', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_relationship_create'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'       => $this->get_param( 'app_id' ),
                'app_title'    => $this->get_param( 'app_title' ),
                'app_dbs'      => $this->get_param( 'dbs' ),
                'app_tbl'      => $this->get_param( 'tbl' ),
                'app_cls'      => $this->get_param( 'app_cls' ),
                'app_relation' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => __( 'Table settings', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/relationship/update', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_relationship_update'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'       => $this->get_param( 'app_id' ),
                'app_cnt'      => $this->get_param( 'cnt_id' ),
                'app_title'    => $this->get_param( 'app_title' ),
                'app_dbs'      => $this->get_param( 'dbs' ),
                'app_tbl'      => $this->get_param( 'tbl' ),
                'app_cls'      => $this->get_param( 'app_cls' ),
                'app_relation' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => __( 'Table settings', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/relationship/delete', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_relationship_delete'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'cnt_id' => $this->get_param( 'cnt_id' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/save', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_save'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'          => $this->get_param( 'app_id' ),
                'app_name'        => $this->get_param( 'app_name' ),
                'app_title'       => $this->get_param( 'app_title' ),
                'app_type'        => $this->get_param( 'app_type' ),
                'app_settings'    => $this->get_param( 'app_settings' ),
                'app_add_to_menu' => $this->get_param( 'app_add_to_menu' ),
                'app_dbs'         => $this->get_param( 'dbs' ),
                'app_tbl'         => $this->get_param( 'tbl' ),
                'app_cls'         => $this->get_param( 'app_cls' ),
                'app_query'       => $this->get_param( 'app_query' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/saveapp', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_saveapp'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'          => $this->get_param( 'app_id' ),
                'app_name'        => $this->get_param( 'app_name' ),
                'app_title'       => $this->get_param( 'app_title' ),
                'app_type'        => $this->get_param( 'app_type' ),
                'app_settings'    => $this->get_param( 'app_settings' ),
                'app_add_to_menu' => $this->get_param( 'app_add_to_menu' ),
                'app_apps'        => $this->get_param( 'app_apps' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/remove', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_remove'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id' => $this->get_param( 'app_id' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/settings', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_settings'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'   => $this->get_param( 'app_id' ),
                'cnt_id'   => $this->get_param( 'cnt_id' ),
                'target'   => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => __( 'Setting target', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'settings' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'description'       => __( 'App settings - JSON string', 'wp-data-access' ),
                    'sanitize_callback' => function ( $param ) {
                        $sanitized_settings = $this->sanitize_settings( json_decode( (string) $param, true ) );
                        // Save sanitized JSON as string
                        return json_encode( $sanitized_settings );
                    },
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'map'      => array(
                    'required'          => false,
                    'type'              => 'string',
                    'description'       => __( 'Map settings - JSON string', 'wp-data-access' ),
                    'sanitize_callback' => 'wp_kses_post',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'chart'    => array(
                    'required'          => false,
                    'type'              => 'string',
                    'description'       => __( 'Chart settings - JSON string', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'theme'    => array(
                    'required'          => false,
                    'type'              => 'string',
                    'description'       => __( 'Theme settings - JSON string', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
            ),
        ) );
        // DML
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/select', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_select'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'             => $this->get_param( 'app_id' ),
                'cnt_id'             => $this->get_param( 'cnt_id' ),
                'col'                => $this->get_param( 'cols' ),
                'page_index'         => $this->get_param( 'page_index' ),
                'page_size'          => $this->get_param( 'page_size' ),
                'search'             => $this->get_param( 'search' ),
                'search_columns'     => $this->get_param( 'search_columns' ),
                'search_column_fns'  => $this->get_param( 'search_column_fns' ),
                'search_column_lov'  => $this->get_param( 'search_column_lov' ),
                'search_data_types'  => $this->get_param( 'search_data_types' ),
                'search_custom'      => $this->get_param( 'search_custom' ),
                'search_params'      => $this->get_param( 'search_params' ),
                'shortcode_params'   => $this->get_param( 'search_params' ),
                'md'                 => $this->get_param( 'md' ),
                'sorting'            => $this->get_param( 'sorting' ),
                'row_count'          => $this->get_param( 'row_count' ),
                'row_count_estimate' => $this->get_param( 'row_count_estimate' ),
                'media'              => $this->get_param( 'media' ),
                'rel_tab'            => $this->get_param( 'rel_tab' ),
                'client_side'        => $this->get_param( 'client_side' ),
                'geo_radius'         => array(
                    'required'          => false,
                    'type'              => 'mixed',
                    'description'       => __( 'Geo radius segment', 'wp-data-access' ),
                    'sanitize_callback' => function ( $param ) {
                        $geo_radius = array();
                        if ( isset( 
                            $param['col']['lat'],
                            $param['col']['lng'],
                            $param['loc']['lat'],
                            $param['loc']['lng'],
                            $param['radius'],
                            $param['unit']
                         ) && is_numeric( $param['loc']['lat'] ) && is_numeric( $param['loc']['lng'] ) && is_numeric( $param['radius'] ) && ('km' === $param['unit'] || 'miles' === $param['unit']) ) {
                            $geo_radius['col']['lat'] = WPDA::remove_backticks( sanitize_text_field( $param['col']['lat'] ) );
                            $geo_radius['col']['lng'] = WPDA::remove_backticks( sanitize_text_field( $param['col']['lng'] ) );
                            $geo_radius['loc']['lat'] = (float) sanitize_text_field( $param['loc']['lat'] );
                            $geo_radius['loc']['lng'] = (float) sanitize_text_field( $param['loc']['lng'] );
                            $geo_radius['radius'] = (float) sanitize_text_field( $param['radius'] );
                            $geo_radius['unit'] = sanitize_text_field( $param['unit'] );
                        }
                        return $geo_radius;
                    },
                ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/get', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_get'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'  => $this->get_param( 'app_id' ),
                'cnt_id'  => $this->get_param( 'cnt_id' ),
                'key'     => $this->get_param( 'key' ),
                'media'   => $this->get_param( 'media' ),
                'rel_tab' => $this->get_param( 'rel_tab' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/insert', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_insert'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'   => $this->get_param( 'app_id' ),
                'cnt_id'   => $this->get_param( 'cnt_id' ),
                'val'      => $this->get_param( 'val' ),
                'join_tab' => $this->get_param( 'join_tab' ),
                'rel_tab'  => $this->get_param( 'rel_tab' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/update', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_update'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'   => $this->get_param( 'app_id' ),
                'cnt_id'   => $this->get_param( 'cnt_id' ),
                'key'      => $this->get_param( 'key' ),
                'val'      => $this->get_param( 'val' ),
                'join_tab' => $this->get_param( 'join_tab' ),
                'rel_tab'  => $this->get_param( 'rel_tab' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/update/inline', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_update_inline'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id' => $this->get_param( 'app_id' ),
                'cnt_id' => $this->get_param( 'cnt_id' ),
                'key'    => $this->get_param( 'key' ),
                'val'    => $this->get_param( 'val' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/delete', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_delete'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id' => $this->get_param( 'app_id' ),
                'cnt_id' => $this->get_param( 'cnt_id' ),
                'key'    => $this->get_param( 'key' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/lov', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_lov'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'            => $this->get_param( 'app_id' ),
                'cnt_id'            => $this->get_param( 'cnt_id' ),
                'col'               => $this->get_param( 'col' ),
                'cols'              => $this->get_param( 'cols' ),
                'search'            => $this->get_param( 'search' ),
                'search_columns'    => $this->get_param( 'search_columns' ),
                'search_column_fns' => $this->get_param( 'search_column_fns' ),
                'search_column_lov' => $this->get_param( 'search_column_lov' ),
                'search_data_types' => $this->get_param( 'search_data_types' ),
                'search_custom'     => $this->get_param( 'search_custom' ),
                'search_params'     => $this->get_param( 'search_params' ),
                'shortcode_params'  => $this->get_param( 'search_params' ),
                'md'                => $this->get_param( 'md' ),
                'cascade'           => $this->get_param( 'cascade' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/lookup', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_lookup'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'            => $this->get_param( 'app_id' ),
                'cnt_id'            => $this->get_param( 'cnt_id' ),
                'target'            => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => __( 'Target: table or (r)form', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'col'               => $this->get_param( 'col' ),
                'colk'              => $this->get_param( 'col' ),
                'colv'              => $this->get_param( 'col' ),
                'cold'              => $this->get_param( 'key' ),
                'cols'              => $this->get_param( 'cols' ),
                'search'            => $this->get_param( 'search' ),
                'search_columns'    => $this->get_param( 'search_columns' ),
                'search_column_fns' => $this->get_param( 'search_column_fns' ),
                'search_column_lov' => $this->get_param( 'search_column_lov' ),
                'search_data_types' => $this->get_param( 'search_data_types' ),
                'search_custom'     => $this->get_param( 'search_custom' ),
                'search_params'     => $this->get_param( 'search_params' ),
                'shortcode_params'  => $this->get_param( 'search_params' ),
                'md'                => $this->get_param( 'md' ),
                'cascade'           => $this->get_param( 'cascade' ),
                'values'            => $this->get_param( 'md' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/lookup/dbs', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_lookup_dbs'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id' => $this->get_param( 'app_id' ),
                'cnt_id' => $this->get_param( 'cnt_id' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/lookup/tbl', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_lookup_tbl'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id' => $this->get_param( 'app_id' ),
                'cnt_id' => $this->get_param( 'cnt_id' ),
                'dbs'    => $this->get_param( 'dbs' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/lookup/cls', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_lookup_cls'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id' => $this->get_param( 'app_id' ),
                'cnt_id' => $this->get_param( 'cnt_id' ),
                'dbs'    => $this->get_param( 'dbs' ),
                'tbl'    => $this->get_param( 'tbl' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/qb/list', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_qb_list'),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/chart/data', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_chart_data'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id'           => $this->get_param( 'app_id' ),
                'search_custom'    => $this->get_param( 'search_custom' ),
                'shortcode_params' => $this->get_param( 'search_params' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/dbs/rename', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_dbs_rename'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs_source'      => $this->get_param( 'dbs' ),
                'dbs_destination' => $this->get_param( 'dbs' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/lang/get', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_lang_get'),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/lang/set', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_lang_set'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'localizations' => array(
                    'required'          => true,
                    'type'              => 'mixed',
                    'description'       => __( 'Custom side translations (JSON as string)', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'app/call', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'app_call'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'app_id' => $this->get_param( 'app_id' ),
                'cls'    => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => __( 'Class name', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'fnc'    => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => __( 'Function name', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'arg'    => array(
                    'required'          => true,
                    'type'              => 'mixed',
                    'description'       => __( 'Arguments', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
            ),
        ) );
    }

    public function app_call( $request ) {
    }

    public function app_lang_get( $request ) {
        if ( !$this->current_user_can_access() ) {
            // Only admins
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        return $this->WPDA_Rest_Response( '', WPDA_App_Localization::get() );
    }

    public function app_lang_set( $request ) {
        if ( !$this->current_user_can_access() ) {
            // Only admins
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $localizations = $request->get_param( 'localizations' );
        WPDA_App_Localization::set( $localizations );
        return $this->WPDA_Rest_Response( 'Translation successfully saved' );
    }

    private function get_app_columns( $columns ) {
        if ( !is_array( $columns ) ) {
            return false;
        }
        return array_map( function ( $value ) {
            return $value['columnName'];
        }, array_filter( $columns, function ( $column ) {
            return $column['isSelected'];
        } ) );
    }

    private function get_app_table_columns( $settings, $table_settings ) {
        if ( !isset( $settings['columns'] ) ) {
            return false;
        }
        $columns = $this->get_app_columns( $settings['columns'] );
        if ( false === $columns ) {
            return false;
        }
        $columns_available = array_flip( $columns );
        if ( !isset( $settings['table'] ) ) {
            return array_map( function () {
                return true;
            }, $columns_available );
        }
        if ( !is_array( $table_settings ) || !isset( $table_settings['columns'] ) ) {
            return false;
        }
        $table_columns = $table_settings['columns'];
        for ($i = 0; $i < count( $columns ); $i++) {
            if ( isset( $columns_available[$columns[$i]] ) ) {
                $column_name = $columns[$i];
                $columns_available[$columns[$i]] = count( array_filter( $table_columns, function ( $column ) use($columns, $column_name) {
                    if ( !isset( $column['columnName'], $column['queryable'] ) ) {
                        return false;
                    }
                    $queryable = $column['queryable'];
                    return $column_name === $column['columnName'] && true === $queryable;
                } ) ) > 0;
            }
        }
        return $columns_available;
    }

    private function get_app_form_columns( $settings ) {
        if ( !isset( $settings['columns'] ) ) {
            return false;
        }
        return $this->get_app_columns( $settings['columns'] );
    }

    public function app_export( $request ) {
        $app_id = $request->get_param( 'app_id' );
        if ( !$this->main_app_access( $app_id, $msg ) ) {
            if ( 'rest_cookie_invalid_nonce' === $msg ) {
                return $this->invalid_nonce();
            }
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        return $this->do_app_export( $app_id );
    }

    public function app_copy( $request ) {
        $app_id = $request->get_param( 'app_id' );
        if ( !$this->main_app_access( $app_id, $msg ) ) {
            if ( 'rest_cookie_invalid_nonce' === $msg ) {
                return $this->invalid_nonce();
            }
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        return $this->do_app_copy( $app_id );
    }

    public function app_details( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $cnt_id = $request->get_param( 'cnt_id' );
        if ( $this->check_app_access(
            $app_id,
            $cnt_id,
            'select',
            $dbs,
            $tbl,
            $msg,
            $settings
        ) ) {
            return $this->WPDA_Rest_Response( '', WPDA_App_Container_Model::select( $app_id, 1 ) );
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

    public function app_detail_meta( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $cnt_id = $request->get_param( 'cnt_id' );
        $rel_tab = $request->get_param( 'rel_tab' );
        if ( $this->check_app_access(
            $app_id,
            $cnt_id,
            'select',
            $dbs,
            $tbl,
            $msg,
            $settings
        ) ) {
            $container = WPDA_App_Container_Model::get_container( $app_id, $cnt_id );
            if ( !isset( $container[0] ) ) {
                return $this->bad_request();
            }
            return $this->get_app_container_meta( $app_id, $container, $rel_tab );
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

    public function app_detail_reorder( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $cnt_id_from = $request->get_param( 'cnt_id_from' );
        $cnt_id_to = $request->get_param( 'cnt_id_to' );
        if ( $this->check_app_access(
            $app_id,
            $cnt_id_from,
            'select',
            $dbs,
            $tbl,
            $msg,
            $settings
        ) ) {
            $container = WPDA_App_Container_Model::get_container( $app_id, $cnt_id_from );
            if ( !isset( $container[0] ) ) {
                return $this->bad_request();
            }
            $container = WPDA_App_Container_Model::get_container( $app_id, $cnt_id_to );
            if ( !isset( $container[0] ) ) {
                return $this->bad_request();
            }
            return $this->reorder_details( $app_id, $cnt_id_from, $cnt_id_to );
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

    public function app_relationship_create( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $app_id = $request->get_param( 'app_id' );
        $app_title = $request->get_param( 'app_title' );
        $app_dbs = $request->get_param( 'app_dbs' );
        $app_tbl = $request->get_param( 'app_tbl' );
        $app_cls = $request->get_param( 'app_cls' );
        $app_relation = $request->get_param( 'app_relation' );
        return $this->WPDA_Rest_Response( '', WPDA_App_Container_Model::create(
            $app_id,
            $app_dbs,
            $app_tbl,
            json_encode( $app_cls ),
            $app_title,
            1,
            null,
            $app_relation
        ) );
    }

    public function app_relationship_update( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $app_id = $request->get_param( 'app_id' );
        $app_cnt = $request->get_param( 'app_cnt' );
        $app_title = $request->get_param( 'app_title' );
        $app_dbs = $request->get_param( 'app_dbs' );
        $app_tbl = $request->get_param( 'app_tbl' );
        $app_cls = $request->get_param( 'app_cls' );
        $app_relation = $request->get_param( 'app_relation' );
        return $this->WPDA_Rest_Response( '', WPDA_App_Container_Model::update(
            $app_id,
            $app_cnt,
            $app_dbs,
            $app_tbl,
            json_encode( $app_cls ),
            $app_title,
            $app_relation
        ) );
    }

    public function app_relationship_delete( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $cnt_id = $request->get_param( 'cnt_id' );
        return $this->WPDA_Rest_Response( '', WPDA_App_Container_Model::delete_container( $cnt_id ) );
    }

    private function build_lookups(
        $table_settings,
        $dbs,
        &$search_columns,
        $search_column_lov,
        $search_column_fns,
        &$default_where,
        &$lookups
    ) {
    }

    private function build_relationships(
        $container,
        &$m2m_relationship,
        $tbl,
        &$default_where
    ) {
    }

    public function app_select( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $cnt_id = $request->get_param( 'cnt_id' );
        $col = $request->get_param( 'col' );
        $page_index = $request->get_param( 'page_index' );
        $page_size = $request->get_param( 'page_size' );
        $search = $request->get_param( 'search' );
        $search_columns = $request->get_param( 'search_columns' );
        $search_column_fns = $request->get_param( 'search_column_fns' );
        $search_column_lov = $request->get_param( 'search_column_lov' );
        $search_data_types = $request->get_param( 'search_data_types' );
        $search_custom = $request->get_param( 'search_custom' );
        $search_params = $request->get_param( 'search_params' );
        $shortcode_params = $request->get_param( 'shortcode_params' );
        $md = $request->get_param( 'md' );
        $sorting = $request->get_param( 'sorting' );
        $row_count = $request->get_param( 'row_count' );
        $row_count_estimate = $request->get_param( 'row_count_estimate' );
        $media = $request->get_param( 'media' );
        $rel_tab = $request->get_param( 'rel_tab' );
        $client_side = '1' === $request->get_param( 'client_side' );
        $geo_radius = $request->get_param( 'geo_radius' );
        $default_where = '';
        $default_orderby = '';
        $lookups = array();
        $m2m_relationship = array();
        if ( $client_side ) {
            // Delete search values on refresh
            $search = '';
            $search_columns = array();
        }
        if ( $this->check_app_access(
            $app_id,
            $cnt_id,
            'select',
            $dbs,
            $tbl,
            $msg,
            $settings
        ) ) {
            $container = WPDA_App_Container_Model::get_container( $app_id, $cnt_id );
            $app = WPDA_App_Model::get_by_id( $app_id );
            if ( isset( $app[0]['app_type'], $container[0]['cnt_map'] ) && '2' == $app[0]['app_type'] && null !== $container[0]['cnt_map'] ) {
                // App = Map
                // Get default where map
                $map_json = json_decode( (string) $container[0]['cnt_map'], true );
                if ( isset( $map_json['setup']['defaultWhere'] ) && null !== $map_json['setup']['defaultWhere'] && '' !== trim( $map_json['setup']['defaultWhere'] ) ) {
                    $default_where = $map_json['setup']['defaultWhere'];
                }
            } else {
                // All other apps (not being a map)
                if ( '1' === $rel_tab ) {
                } else {
                    $table_settings = $settings['table'] ?? array();
                    // Get default where clause
                    if ( isset( $table_settings['table']['defaultWhere'] ) ) {
                        $default_where = $table_settings['table']['defaultWhere'];
                    }
                    // Get default order by
                    if ( isset( $table_settings['table']['defaultOrderBy'] ) ) {
                        $default_orderby_db = $table_settings['table']['defaultOrderBy'];
                        if ( is_array( $default_orderby_db ) ) {
                            foreach ( $default_orderby_db as $orderby ) {
                                if ( isset( $orderby['columnName'], $orderby['order'] ) && '' !== trim( $orderby['columnName'] ) ) {
                                    $default_orderby .= (( '' === $default_orderby ? 'order by ' : ',' )) . '`' . WPDA::remove_backticks( $orderby['columnName'] ) . '` ' . (( 'desc' === $orderby['order'] ? 'desc' : 'asc' ));
                                }
                            }
                        }
                    }
                }
            }
            $table_api = new WPDA_Table();
            return $table_api->select(
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
                $this->process_params(
                    $default_where,
                    $search_custom,
                    $search_params,
                    $shortcode_params
                ),
                $default_orderby,
                $lookups,
                $md,
                $m2m_relationship,
                $search_data_types,
                $client_side,
                $geo_radius
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

    private function get_m2m_relationship( $relationship, $tbl, $cols ) {
        return array();
    }

    private function get_lookup_lov( $column_lookup, $search_value, $search_type ) {
        return null;
    }

    private function convert_relation_columns( $columns ) {
        return array_map( function ( $value ) {
            if ( true === $value['isSelected'] ) {
                return $value['columnName'];
            }
        }, $columns );
    }

    public function app_get( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $cnt_id = $request->get_param( 'cnt_id' );
        $key = $request->get_param( 'key' );
        $media = $request->get_param( 'media' );
        $rel_tab = $request->get_param( 'rel_tab' );
        if ( $this->check_app_access(
            $app_id,
            $cnt_id,
            'select',
            $dbs,
            $tbl,
            $msg,
            $settings
        ) ) {
            $column_names = $this->get_app_form_columns( $settings );
            if ( false === $column_names ) {
                return $this->invalid_app_settings();
            }
            $default_where = '';
            $table_api = new WPDA_Table();
            return $table_api->get(
                $dbs,
                $tbl,
                $key,
                $media,
                $column_names,
                $default_where
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

    public function app_insert( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $cnt_id = $request->get_param( 'cnt_id' );
        $val = $request->get_param( 'val' );
        $join_tab = $request->get_param( 'join_tab' );
        $rel_tab = $request->get_param( 'rel_tab' );
        if ( $this->check_app_access(
            $app_id,
            $cnt_id,
            'insert',
            $dbs,
            $tbl,
            $msg
        ) ) {
            $table_api = new WPDA_Table();
            return $table_api->insert( $dbs, $tbl, $val );
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

    public function app_update( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $cnt_id = $request->get_param( 'cnt_id' );
        $key = $request->get_param( 'key' );
        $val = $request->get_param( 'val' );
        $join_tab = $request->get_param( 'join_tab' );
        $rel_tab = $request->get_param( 'rel_tab' );
        if ( $this->check_app_access(
            $app_id,
            $cnt_id,
            'update',
            $dbs,
            $tbl,
            $msg,
            $settings
        ) ) {
            $column_names = $this->get_app_form_columns( $settings );
            if ( false === $column_names ) {
                $column_names = array();
            }
            $code_columns = array();
            $html_columns = array();
            if ( isset( $settings['form']['columns'] ) ) {
                foreach ( $settings['form']['columns'] as $column ) {
                    if ( isset( $column['allowHtml'] ) && false === $column['allowHtml'] ) {
                        $html_columns[] = $column['columnName'];
                    }
                }
            }
            $table_api = new WPDA_Table();
            return $table_api->update(
                $dbs,
                $tbl,
                $key,
                $val,
                $column_names,
                $code_columns,
                $html_columns
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

    public function app_update_inline( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $cnt_id = $request->get_param( 'cnt_id' );
        $key = $request->get_param( 'key' );
        $val = $request->get_param( 'val' );
        if ( $this->check_app_access(
            $app_id,
            $cnt_id,
            'select',
            $dbs,
            $tbl,
            $msg,
            $settings
        ) ) {
            foreach ( $val as $column_name => $column ) {
                $found = false;
                if ( isset( $settings['table']['columns'] ) ) {
                    foreach ( $settings['table']['columns'] as $settings_column ) {
                        if ( isset( $settings_column['columnName'] ) && $column_name === $settings_column['columnName'] ) {
                            $found = true;
                        }
                    }
                    if ( !$found ) {
                        return $this->unauthorized();
                    }
                }
            }
            $column_names = $this->get_app_form_columns( $settings );
            if ( false === $column_names ) {
                $column_names = array();
            }
            $table_api = new WPDA_Table();
            return $table_api->update(
                $dbs,
                $tbl,
                $key,
                $val,
                $column_names
            );
        } else {
            if ( 'rest_cookie_invalid_nonce' === $msg ) {
                return $this->invalid_nonce();
            } else {
                return $this->unauthorized();
            }
        }
    }

    public function app_delete( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $cnt_id = $request->get_param( 'cnt_id' );
        $key = $request->get_param( 'key' );
        if ( $this->check_app_access(
            $app_id,
            $cnt_id,
            'delete',
            $dbs,
            $tbl,
            $msg
        ) ) {
            $table_api = new WPDA_Table();
            return $table_api->delete( $dbs, $tbl, $key );
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

    public function app_lov( $request ) {
        return $this->bad_request();
    }

    public function app_lookup( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $cnt_id = $request->get_param( 'cnt_id' );
        $target = $request->get_param( 'target' );
        $col = $request->get_param( 'col' );
        $colk = $request->get_param( 'colk' );
        $colv = $request->get_param( 'colv' );
        $cold = $request->get_param( 'cold' );
        $cols = $request->get_param( 'cols' );
        $search = $request->get_param( 'search' );
        $search_columns = $request->get_param( 'search_columns' );
        $search_column_fns = $request->get_param( 'search_column_fns' );
        $search_column_lov = $request->get_param( 'search_column_lov' );
        $search_data_types = $request->get_param( 'search_data_types' );
        $search_custom = $request->get_param( 'search_custom' );
        $search_params = $request->get_param( 'search_params' );
        $shortcode_params = $request->get_param( 'shortcode_params' );
        $md = $request->get_param( 'md' );
        $cascade = $request->get_param( 'cascade' );
        $values = $request->get_param( 'values' );
        $default_where = '';
        $default_where_lookup = '';
        $lookups = array();
        $m2m_relationship = array();
        if ( $this->check_app_access(
            $app_id,
            $cnt_id,
            'select',
            $dbs,
            $tbl,
            $msg,
            $settings
        ) ) {
            $container = WPDA_App_Container_Model::get_container( $app_id, $cnt_id );
            if ( !isset( $container[0] ) ) {
                return $this->bad_request();
            }
            $lookup = array();
            $lookup_dbs = "";
            $lookup_tbl = "";
            if ( 'form' === $target ) {
                // Handle form lookup
                if ( isset( $container[0]['cnt_form'] ) ) {
                    $lookup = json_decode( (string) $container[0]['cnt_form'], true );
                }
            } else {
                if ( 'rform' === $target ) {
                    // Handle form lookup
                    if ( isset( $container[0]['cnt_rform'] ) ) {
                        $lookup = json_decode( (string) $container[0]['cnt_rform'], true );
                    }
                } else {
                    // Handle table lookup
                    if ( isset( $container[0]['cnt_table'] ) ) {
                        $lookup = json_decode( (string) $container[0]['cnt_table'], true );
                    }
                }
            }
            if ( isset( $lookup['columns'] ) && is_array( $lookup['columns'] ) ) {
                foreach ( $lookup['columns'] as $column ) {
                    if ( $col === $column['columnName'] ) {
                        if ( !isset( $column['lookup'] ) ) {
                            return $this->WPDA_Rest_Response( '', [] );
                        }
                        $lookup_dbs = $column['lookup']['dbs'];
                        $lookup_tbl = $column['lookup']['tbl'];
                        if ( isset( $column['columnName'], $column['lookup']['defaultWhere'] ) ) {
                            $default_where_lookup = $column['lookup']['defaultWhere'];
                        }
                    }
                }
            }
            if ( $lookup_dbs === null || $lookup_dbs === "" || $lookup_tbl === null || $lookup_tbl === "" ) {
                return $this->bad_request();
            }
            $table_api = new WPDA_Table();
            return $table_api->lookup(
                $lookup_dbs,
                $lookup_tbl,
                $colk,
                $colv,
                $cold,
                $this->process_params(
                    $default_where_lookup,
                    $search_custom,
                    $search_params,
                    $shortcode_params,
                    $values
                ),
                '1' === $cascade,
                $tbl,
                $col,
                $this->process_params(
                    $default_where,
                    $search_custom,
                    $search_params,
                    $shortcode_params,
                    $values
                ),
                $search,
                $cols,
                $search_columns,
                $search_column_fns,
                $lookups,
                $md,
                $m2m_relationship,
                $search_data_types
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

    public function app_lookup_dbs( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $cnt_id = $request->get_param( 'cnt_id' );
        if ( $this->check_app_access(
            $app_id,
            $cnt_id,
            'select',
            $_dbs,
            $_tbl,
            $msg
        ) ) {
            $tree_api = new WPDA_Tree();
            return $tree_api->get_dbs();
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

    public function app_lookup_tbl( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $cnt_id = $request->get_param( 'cnt_id' );
        $dbs = $request->get_param( 'dbs' );
        if ( $this->check_app_access(
            $app_id,
            $cnt_id,
            'select',
            $_dbs,
            $_tbl,
            $msg
        ) ) {
            $tree_api = new WPDA_Tree();
            return $tree_api->get_tbl_vws( $dbs );
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

    public function app_lookup_cls( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $cnt_id = $request->get_param( 'cnt_id' );
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        if ( $this->check_app_access(
            $app_id,
            $cnt_id,
            'select',
            $_dbs,
            $_tbl,
            $msg
        ) ) {
            $tree_api = new WPDA_Tree();
            return $tree_api->get_cls( $dbs, $tbl );
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

    public function app_qb_list( $request ) {
        $qb = new WPDA_QB();
        return $qb->open( $request );
    }

    public function app_dbs_rename( $request ) {
        if ( !$this->current_user_can_access() ) {
            // Only admins
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $dbs_source = $request->get_param( 'dbs_source' );
        $dbs_destination = $request->get_param( 'dbs_destination' );
        if ( '' === trim( $dbs_source ) || '' === trim( $dbs_destination ) ) {
            return new \WP_Error('error', 'Invalid arguments', array(
                'status' => 401,
            ));
        }
        global $wpdb;
        $renamed = 0;
        $debug_mode = 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_DEBUG );
        $debug = array();
        $errors = array();
        // Rename all occurrences in repository tables and apps
        $sqls = array(
            "update `{$wpdb->prefix}wpda_publisher` set `pub_schema_name` = %s where `pub_schema_name` = %s",
            "update `{$wpdb->prefix}wpda_project_page` set `page_schema_name` = %s where `page_schema_name` = %s",
            "update `{$wpdb->prefix}wpda_project_table` set `wpda_schema_name` = %s where `wpda_schema_name` = %s",
            "update `{$wpdb->prefix}wpda_media` set `media_schema_name` = %s where `media_schema_name` = %s",
            "update `{$wpdb->prefix}wpda_menus` set `menu_schema_name` = %s where `menu_schema_name` = %s",
            "update `{$wpdb->prefix}wpda_table_design` set `wpda_schema_name` = %s where `wpda_schema_name` = %s",
            "update `{$wpdb->prefix}wpda_table_settings` set `wpda_schema_name` = %s where `wpda_schema_name` = %s",
            "update `{$wpdb->prefix}wpda_app_container` set `cnt_dbs` = %s where `cnt_dbs` = %s"
        );
        foreach ( $sqls as $sql ) {
            $result = $wpdb->query( $wpdb->prepare( $sql, array($dbs_destination, $dbs_source) ) );
            $renamed += $result;
            if ( $debug_mode ) {
                $debug[] = array(
                    'sql'    => $sql,
                    'result' => $result,
                );
            }
            if ( '' !== $wpdb->last_error ) {
                $errors[] = array(
                    'sql'   => $sql,
                    'error' => $wpdb->last_error,
                );
            }
        }
        $sql_content = array("update `{$wpdb->prefix}wpda_app_container` set `cnt_table` = replace(`cnt_table`, '\"dbs\":\"%1s\"', '\"dbs\":\"%1s\"') where `cnt_table` like '%\"dbs\":\"%1s\"%'", "update `{$wpdb->prefix}wpda_app_container` set `cnt_form` = replace(`cnt_form`, '\"dbs\":\"%1s\"', '\"dbs\":\"%1s\"') where `cnt_form` like '%\"dbs\":\"%1s\"%'");
        foreach ( $sql_content as $sql ) {
            $result = $wpdb->query( $wpdb->prepare( $sql, array($dbs_source, $dbs_destination, $dbs_source) ) );
            $renamed += $result;
            if ( $debug_mode ) {
                $debug[] = array(
                    'sql'    => $sql,
                    'result' => $result,
                );
            }
            if ( '' !== $wpdb->last_error ) {
                $errors[] = array(
                    'sql'   => $sql,
                    'error' => $wpdb->last_error,
                );
            }
        }
        $context = array();
        if ( $debug_mode ) {
            $context['debug'] = $debug;
        }
        if ( 0 < count( $errors ) ) {
            $context['errors'] = $errors;
            return new \WP_Error('error', 'Failed renaming database', array(
                'status'  => 401,
                'context' => $context,
            ));
        }
        return $this->WPDA_Rest_Response( sprintf( __( 'Successfully renamed %s database occurrences', 'wp-data-access' ), $renamed ), null, $context );
    }

    public function app_chart_data( $request ) {
        $app_id = $request->get_param( 'app_id' );
        $search_custom = $request->get_param( 'search_custom' );
        $shortcode_params = $request->get_param( 'shortcode_params' );
        if ( !$this->main_app_access( $app_id, $msg ) ) {
            if ( 'rest_cookie_invalid_nonce' === $msg ) {
                return $this->invalid_nonce();
            }
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $app_container = WPDA_App_Container_Model::select( $app_id, 0 );
        if ( 1 === count( $app_container ) && null !== $app_container[0]['cnt_query'] && '' !== trim( (string) $app_container[0]['cnt_query'] ) ) {
            $dbs = $app_container[0]['cnt_dbs'];
            $query = $app_container[0]['cnt_query'];
            // Process shortcode and url parameters
            $query = $this->process_params(
                $query,
                $search_custom,
                null,
                $shortcode_params
            );
            $wpdadb = WPDADB::get_db_connection( $dbs );
            if ( null === $wpdadb ) {
                // Error connecting.
                return new \WP_Error('error', "Error connecting to database {$dbs}", array(
                    'status' => 420,
                ));
            }
            $suppress = $wpdadb->suppress_errors( true );
            $chart_data = $wpdadb->get_results( $query, 'ARRAY_A' );
            $wpdadb->get_results( "create temporary table `wpda_chart_data_types` as {$query}", 'ARRAY_A' );
            $explain = $wpdadb->get_results( "desc `wpda_chart_data_types`", 'ARRAY_A' );
            $wpdadb->get_results( "drop temporary table `wpda_chart_data_types`", 'ARRAY_A' );
            $wpdadb->suppress_errors( $suppress );
            return array(
                'data'    => $chart_data,
                'explain' => $explain,
            );
        } else {
            return new \WP_Error('error', $msg, array(
                'status' => 401,
            ));
        }
    }

    public function app_lang( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $lang = $request->get_param( 'lang' );
        update_option( self::WPDA_APP_DEFAULT_LANG, $lang );
        return $this->WPDA_Rest_Response( __( 'Successfully saved changes', 'wp-data-access' ) );
    }

    public function app_meta( $request ) {
        $app_id = $request->get_param( 'app_id' );
        if ( !$this->main_app_access( $app_id, $msg ) ) {
            if ( 'rest_cookie_invalid_nonce' === $msg ) {
                return $this->invalid_nonce();
            }
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        return $this->get_app_meta( $app_id );
    }

    public function app_settings( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $app_id = $request->get_param( 'app_id' );
        $cnt_id = $request->get_param( 'cnt_id' );
        $target = $request->get_param( 'target' );
        $settings = $request->get_param( 'settings' );
        $chart = $request->get_param( 'chart' );
        $map = $request->get_param( 'map' );
        $theme = $request->get_param( 'theme' );
        return $this->do_app_settings(
            $app_id,
            $cnt_id,
            $target,
            $settings,
            $chart,
            $map,
            $theme
        );
    }

    public function app_init( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        return $this->get_app_init();
    }

    public function app_list( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        return $this->get_app_list();
    }

    public function app_table_meta( $request ) {
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $waa = $request->get_param( 'waa' );
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $table_api = new WPDA_Table();
        return $this->WPDA_Rest_Response( '', $table_api->get_table_meta_data( $dbs, $tbl, $waa ) );
    }

    public function app_create( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        // App details
        $app_name = $request->get_param( 'app_name' );
        $app_title = $request->get_param( 'app_title' );
        $app_type = $request->get_param( 'app_type' );
        $app_settings = $request->get_param( 'app_settings' );
        // App container
        $app_dbs = $request->get_param( 'app_dbs' );
        $app_tbl = $request->get_param( 'app_tbl' );
        $app_cls = $request->get_param( 'app_cls' );
        $app_table = $request->get_param( 'app_table' );
        $app_query = $request->get_param( 'app_query' );
        return $this->do_app_create(
            $app_name,
            $app_title,
            $app_type,
            $app_settings,
            $app_dbs,
            $app_tbl,
            $app_cls,
            $app_table,
            $app_query
        );
    }

    public function app_createapp( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $app_name = $request->get_param( 'app_name' );
        $app_title = $request->get_param( 'app_title' );
        $app_type = $request->get_param( 'app_type' );
        $app_settings = $request->get_param( 'app_settings' );
        $app_apps = $request->get_param( 'app_apps' );
        return $this->do_app_createapp(
            $app_name,
            $app_title,
            $app_type,
            $app_settings,
            $app_apps
        );
    }

    public function app_remove( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $app_id = $request->get_param( 'app_id' );
        return $this->do_app_remove( $app_id );
    }

    public function app_save( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        // App details
        $app_id = $request->get_param( 'app_id' );
        $app_name = $request->get_param( 'app_name' );
        $app_title = $request->get_param( 'app_title' );
        $app_type = $request->get_param( 'app_type' );
        $app_settings = $request->get_param( 'app_settings' );
        $app_add_to_menu = $request->get_param( 'app_add_to_menu' );
        // App container
        $app_dbs = $request->get_param( 'app_dbs' );
        $app_tbl = $request->get_param( 'app_tbl' );
        $app_cls = $request->get_param( 'app_cls' );
        $app_query = $request->get_param( 'app_query' );
        return $this->do_app_save(
            $app_id,
            $app_name,
            $app_title,
            $app_type,
            $app_settings,
            $app_add_to_menu,
            $app_dbs,
            $app_tbl,
            $app_cls,
            $app_query
        );
    }

    public function app_saveapp( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $app_id = $request->get_param( 'app_id' );
        $app_name = $request->get_param( 'app_name' );
        $app_title = $request->get_param( 'app_title' );
        $app_type = $request->get_param( 'app_type' );
        $app_settings = $request->get_param( 'app_settings' );
        $app_add_to_menu = $request->get_param( 'app_add_to_menu' );
        $app_apps = $request->get_param( 'app_apps' );
        return $this->do_app_saveapp(
            $app_id,
            $app_name,
            $app_title,
            $app_type,
            $app_settings,
            $app_add_to_menu,
            $app_apps
        );
    }

    private function do_app_settings(
        $app_id,
        $cnt_id,
        $target,
        $settings,
        $chart,
        $map,
        $theme
    ) {
        if ( 1 > $app_id || 1 > $cnt_id || 'table' !== $target && 'form' !== $target && 'rform' !== $target && 'theme' !== $target && 'chart' !== $target && 'map' !== $target && 'dashboard' !== $target ) {
            return $this->bad_request();
        }
        if ( null === $settings || '' === $settings ) {
            // Perform reset
            switch ( $target ) {
                case 'table':
                    $error_msg = WPDA_App_Container_Model::update_table_settings( $cnt_id, null );
                    if ( '' !== $error_msg ) {
                        return new \WP_Error('error', $error_msg, array(
                            'status' => 403,
                        ));
                    }
                    break;
                case 'form':
                    $error_msg = WPDA_App_Container_Model::update_form_settings( $cnt_id, null );
                    if ( '' !== $error_msg ) {
                        return new \WP_Error('error', $error_msg, array(
                            'status' => 403,
                        ));
                    }
                    break;
                case 'chart':
                    $error_msg = WPDA_App_Container_Model::update_chart_settings( $cnt_id, null );
                    if ( '' !== $error_msg ) {
                        return new \WP_Error('error', $error_msg, array(
                            'status' => 403,
                        ));
                    }
                    break;
                case 'map':
                    $error_msg = WPDA_App_Container_Model::update_map_settings( $cnt_id, null );
                    if ( '' !== $error_msg ) {
                        return new \WP_Error('error', $error_msg, array(
                            'status' => 403,
                        ));
                    }
                    break;
                case 'theme':
                    $error_msg = WPDA_App_Model::update_theme( $app_id, null );
                    if ( '' !== $error_msg ) {
                        return new \WP_Error('error', $error_msg, array(
                            'status' => 403,
                        ));
                    }
                    break;
                case 'dashboard':
                    $error_msg = WPDA_App_Container_Model::update_dashboard_settings( $app_id, null );
                    if ( '' !== $error_msg ) {
                        return new \WP_Error('error', $error_msg, array(
                            'status' => 403,
                        ));
                    }
                    break;
                default:
                    return $this->bad_request();
            }
            return $this->WPDA_Rest_Response( __( 'Reset was successful', 'wp-data-access' ) );
        }
        if ( 'table' === $target ) {
            // Update table settings
            $error_msg = WPDA_App_Container_Model::update_table_settings( $cnt_id, $settings );
            if ( '' !== $error_msg ) {
                return new \WP_Error('error', $error_msg, array(
                    'status' => 403,
                ));
            }
            // Update chart settings
            $error_msg = WPDA_App_Container_Model::update_chart_settings( $cnt_id, $chart );
            if ( '' !== $error_msg ) {
                return new \WP_Error('error', $error_msg, array(
                    'status' => 403,
                ));
            }
            // Update map settings
            $error_msg = WPDA_App_Container_Model::update_map_settings( $cnt_id, $map );
            if ( '' !== $error_msg ) {
                return new \WP_Error('error', $error_msg, array(
                    'status' => 403,
                ));
            }
        } else {
            if ( 'rform' === $target ) {
                // Update rform settings
                $error_msg = WPDA_App_Container_Model::update_rform_settings( $cnt_id, $settings );
                if ( '' !== $error_msg ) {
                    return new \WP_Error('error', $error_msg, array(
                        'status' => 403,
                    ));
                }
            } else {
                if ( 'chart' === $target ) {
                    // Update chart settings
                    $error_msg = WPDA_App_Container_Model::update_chart_settings( $cnt_id, $settings );
                    if ( '' !== $error_msg ) {
                        return new \WP_Error('error', $error_msg, array(
                            'status' => 403,
                        ));
                    }
                } else {
                    if ( 'map' === $target ) {
                        // Update chart settings
                        $error_msg = WPDA_App_Container_Model::update_map_settings( $cnt_id, $settings );
                        if ( '' !== $error_msg ) {
                            return new \WP_Error('error', $error_msg, array(
                                'status' => 403,
                            ));
                        }
                    } else {
                        if ( 'form' === $target ) {
                            // Update form settings
                            $error_msg = WPDA_App_Container_Model::update_form_settings( $cnt_id, $settings );
                            if ( '' !== $error_msg ) {
                                return new \WP_Error('error', $error_msg, array(
                                    'status' => 403,
                                ));
                            }
                        } else {
                            if ( 'dashboard' === $target ) {
                                $error_msg = WPDA_App_Container_Model::update_dashboard_settings( $cnt_id, $settings );
                                if ( '' !== $error_msg ) {
                                    return new \WP_Error('error', $error_msg, array(
                                        'status' => 403,
                                    ));
                                }
                            }
                        }
                    }
                }
            }
        }
        $error_msg = WPDA_App_Model::update_theme( $app_id, $theme );
        if ( '' !== $error_msg ) {
            return new \WP_Error('error', $error_msg, array(
                'status' => 403,
            ));
        }
        return $this->WPDA_Rest_Response( __( 'Successfully saved settings', 'wp-data-access' ) );
    }

    private function do_app_remove( $app_id ) {
        WPDA_App_Apps_Model::delete( $app_id, true );
        WPDA_App_Container_Model::delete( $app_id );
        WPDA_App_Model::delete( $app_id );
        return $this->WPDA_Rest_Response( __( 'Successfully deleted app', 'wp-data-access' ) );
    }

    private function do_app_create(
        $app_name,
        $app_title,
        $app_type,
        $app_settings,
        $app_dbs,
        $app_tbl,
        $app_cls,
        $app_table,
        $app_query
    ) {
        // Add app
        $insert = WPDA_App_Model::create(
            $app_name,
            $app_title,
            $app_type,
            $app_settings
        );
        if ( false !== $insert['app_id'] ) {
            $app_id = $insert['app_id'];
            // Add app container
            $container = WPDA_App_Container_Model::create(
                $app_id,
                $app_dbs,
                $app_tbl,
                json_encode( $app_cls ),
                $app_title,
                0,
                $app_table,
                null,
                $app_query
            );
            if ( false !== $container['cnt_id'] ) {
                // App and container successfully saved
                return $this->WPDA_Rest_Response( __( 'Successfully saved changes', 'wp-data-access' ) );
            } else {
                // Insert failed
                // Remove previously created app
                WPDA_App_Model::delete( $app_id );
                return new \WP_Error('error', $container['msg'], array(
                    'status' => 403,
                ));
            }
        } else {
            // Insert failed
            return new \WP_Error('error', $insert['msg'], array(
                'status' => 403,
            ));
        }
    }

    private function do_app_createapp(
        $app_name,
        $app_title,
        $app_type,
        $app_settings,
        $app_apps
    ) {
        // Add app
        $insert = WPDA_App_Model::create(
            $app_name,
            $app_title,
            $app_type,
            $app_settings
        );
        if ( false !== $insert['app_id'] ) {
            // Add apps
            if ( is_array( $app_apps ) ) {
                $app_id = $insert['app_id'];
                foreach ( $app_apps as $index => $app_id_detail ) {
                    // Insert app
                    WPDA_App_Apps_Model::create( $app_id, $app_id_detail, $index );
                }
            }
            // App and details successfully saved
            return $this->WPDA_Rest_Response( __( 'Successfully saved changes', 'wp-data-access' ) );
        } else {
            // Insert failed
            return new \WP_Error('error', $insert['msg'], array(
                'status' => 403,
            ));
        }
    }

    private function get_app_init() {
        return $this->WPDA_Rest_Response( '', array(
            'roles' => $this->get_wp_roles(),
            'users' => $this->get_wp_users(),
            'lang'  => get_option( self::WPDA_APP_DEFAULT_LANG ),
        ) );
    }

    private function get_app_list() {
        $dataset = WPDA_App_Model::list();
        $context = WPDA_App_Apps_Model::list();
        return $this->WPDA_Rest_Response( '', $dataset, $context );
    }

    private function get_relation_columns( $container ) {
        return null;
    }

    private function reorder_details( $app_id, $cnt_id_from, $cnt_id_to ) {
        return $this->bad_request();
    }

    private function get_app_apps_meta( $app, $apps ) {
        $app_id_details = array_map( function ( $e ) {
            if ( isset( $e['app_id_detail'] ) ) {
                return $e['app_id_detail'];
            }
        }, $apps );
        $app_titles = array();
        foreach ( $app_id_details as $app_id_detail ) {
            $app_detail = WPDA_App_Model::get_by_id( $app_id_detail );
            if ( isset( $app_detail[0]['app_title'] ) ) {
                $app_titles[$app_id_detail] = $app_detail[0]['app_title'];
            }
        }
        $response = array(
            'app' => array(
                'app'       => $app,
                'container' => array(),
                'apps'      => $app_id_details,
                'titles'    => $app_titles,
            ),
        );
        $response['settings'] = $this->get_table_settings();
        return $this->WPDA_Rest_Response( '', $response );
    }

    private function get_table_settings( $tbl = null, $dbs = null ) {
        $settings = new stdClass();
        if ( null !== $dbs && null !== $tbl ) {
            $settings_db = WPDA_Table_Settings_Model::query( $tbl, $dbs );
            if ( isset( $settings_db[0]['wpda_table_settings'] ) ) {
                $settings = json_decode( (string) $settings_db[0]['wpda_table_settings'] );
                // Remove old settings from response.
                unset($settings->form_labels);
                unset($settings->list_labels);
                unset($settings->custom_settings);
                unset($settings->search_settings);
            }
        }
        $settings->env = $this->get_env();
        global $wpdb;
        $settings->wp = [
            'roles'         => $this->get_wp_roles(),
            'users'         => $this->get_wp_users(),
            'home'          => admin_url( 'admin.php' ),
            'tables'        => array_values( $wpdb->tables() ),
            'date_format'   => get_option( 'date_format' ),
            'time_format'   => get_option( 'time_format' ),
            'scroll_offset' => WPDA::get_option( WPDA::OPTION_APPS_SCROLL_OFFSET ),
        ];
        return $settings;
    }

    private function get_app_container_meta( $app_id, $container, $rel_tab = false ) {
        $app = WPDA_App_Model::get_by_id( $app_id );
        if ( false === $app ) {
            return $this->bad_request();
        }
        if ( !isset( $container[0]['cnt_dbs'], $container[0]['cnt_tbl'], $container[0]['cnt_cls'] ) ) {
            return $this->bad_request();
        }
        $dbs = $container[0]['cnt_dbs'];
        $tbl = $container[0]['cnt_tbl'];
        $response = array(
            'app' => array(
                'app'       => $app,
                'container' => array_map( function ( $value ) {
                    $show = WPDA::current_user_is_admin();
                    if ( !$show ) {
                        // Hide database and table name in responses for non admin users.
                        unset($value['cnt_dbs']);
                        unset($value['cnt_tbl']);
                    }
                    return $value;
                }, $container ),
                'apps'      => array(),
            ),
        );
        $access = array(
            'select' => array(),
            'insert' => array(),
            'update' => array(),
            'delete' => array(),
        );
        $cls = WPDA_List_Columns_Cache::get_list_columns( $dbs, $tbl );
        $columns = $cls->get_table_columns();
        $columns_sorted = array();
        foreach ( $columns as $column ) {
            if ( isset( $column['column_name'] ) ) {
                $columns_sorted[$column['column_name']] = $column;
            }
        }
        $media = $this->get_media( $dbs, $tbl, $cls->get_table_columns() );
        $response['columns'] = $columns;
        $response['columns_sorted'] = $columns_sorted;
        $response['table_labels'] = $cls->get_table_header_labels();
        $response['form_labels'] = $cls->get_table_column_headers();
        $response['primary_key'] = $cls->get_table_primary_key();
        $response['access'] = $access;
        $response['settings'] = $this->get_table_settings( $tbl, $dbs );
        $response['media'] = $media['media'];
        $response['wp_media'] = $media['wp_media'];
        $table_settings = json_decode( (string) $container[0]['cnt_table'], true );
        if ( isset( $table_settings['table']['defaultWhere'] ) ) {
            $default_where = $table_settings['table']['defaultWhere'];
        } else {
            $default_where = '';
        }
        $response['table_info'] = $this->get_table_info( $dbs, $tbl, $default_where );
        return $this->WPDA_Rest_Response( '', $response );
    }

    public function get_app_meta( $app_id ) {
        $app = WPDA_App_Model::get_by_id( $app_id );
        if ( !isset( $app[0]['app_type'] ) ) {
            return $this->bad_request();
        }
        if ( '5' === $app[0]['app_type'] || 5 === $app[0]['app_type'] ) {
            // App container
            $apps = WPDA_App_Apps_Model::select_all( $app_id, 0 );
            return $this->get_app_apps_meta( $app, $apps );
        } else {
            // Other container
            $container = WPDA_App_Container_Model::select( $app_id, 0 );
            if ( !isset( $container[0] ) ) {
                return $this->bad_request();
            }
            return $this->get_app_container_meta( $app_id, $container );
        }
    }

    private function do_app_export_app( $app_id, $main_app_id ) {
        global $wpdb;
        $quotes = function ( $value ) {
            return str_replace( array(
                "'",
                '\\"',
                "\\\\t",
                "\\t",
                "\\\\n",
                "\\n",
                "\\r\\n",
                "\\r"
            ), array(
                "''",
                '\\\\"',
                "\\\\\\t",
                "\\\\t",
                "\\\\\\n",
                "\\\\n",
                "\\\\r\\\\n",
                "\\\\r"
            ), $value );
        };
        $app = WPDA_App_Model::get_by_id( $app_id );
        $app_settings = ( null === $app[0]['app_settings'] ? 'null' : "{$quotes( $app[0]['app_settings'] )}" );
        $app_theme = ( null === $app[0]['app_theme'] ? 'null' : "{$quotes( $app[0]['app_theme'] )}" );
        $app_sql = <<<SQL
# Import app
insert into `{wp_prefix}wpda_app`
\t(`app_name`
\t,`app_title`
\t,`app_type`
\t,`app_settings`
\t,`app_theme`
\t,`app_add_to_menu`
\t)
values
\t('{$quotes( $app[0]['app_name'] )}'
\t,'{$quotes( $app[0]['app_title'] )}'
\t,{$app[0]['app_type']}
\t,'{$app_settings}'
\t,'{$app_theme}'
\t,{$app[0]['app_add_to_menu']}
\t);

SET @APP_ID = LAST_INSERT_ID();
insert into `wpda_transfer_apps_{$main_app_id}`
values    
    ({$app[0]['app_id']}
    ,(select LAST_INSERT_ID(`app_id`) from `{wp_prefix}wpda_app` order by 1 desc limit 1)
    );


SQL;
        $containers = WPDA_App_Container_Model::select_all( $app_id );
        $containers_sql = '';
        foreach ( $containers as $container ) {
            $cnt_table = ( null === $container['cnt_table'] ? 'null' : "'{$quotes( $container['cnt_table'] )}'" );
            $cnt_form = ( null === $container['cnt_form'] ? 'null' : "'{$quotes( $container['cnt_form'] )}'" );
            $cnt_relation = ( null === $container['cnt_relation'] ? 'null' : "'{$quotes( $container['cnt_relation'] )}'" );
            $cnt_rform = ( null === $container['cnt_rform'] ? 'null' : "'{$quotes( $container['cnt_rform'] )}'" );
            $cnt_chart = ( null === $container['cnt_chart'] ? 'null' : "'{$quotes( $container['cnt_chart'] )}'" );
            $cnt_map = ( null === $container['cnt_map'] ? 'null' : "'{$quotes( $container['cnt_map'] )}'" );
            $cnt_query = ( null === $container['cnt_query'] ? 'null' : "'{$quotes( $container['cnt_query'] )}'" );
            // Replace default WordPress database with conversion string
            $cnt_dbs = ( $wpdb->dbname === $container['cnt_dbs'] ? '{wp_schema}' : "{$quotes( $container['cnt_dbs'] )}" );
            $cnt_table = str_replace( "\"dbs\":\"{$wpdb->dbname}\"", "\"dbs\":\"{wp_schema}\"", $cnt_table );
            $cnt_form = str_replace( "\"dbs\":\"{$wpdb->dbname}\"", "\"dbs\":\"{wp_schema}\"", $cnt_form );
            $containers_sql .= <<<SQL
# Import app container
insert into `{wp_prefix}wpda_app_container`
\t(`cnt_dbs`
\t,`cnt_tbl`
\t,`cnt_cls`
\t,`cnt_title`
\t,`app_id`
\t,`cnt_seq_nr`
\t,`cnt_table`
\t,`cnt_form`
\t,`cnt_relation`
    ,`cnt_rform`
    ,`cnt_chart`
    ,`cnt_map`
    ,`cnt_query`
\t)
values
\t('{$cnt_dbs}'
\t,'{$quotes( $container['cnt_tbl'] )}'
\t,'{$quotes( $container['cnt_cls'] )}'
\t,'{$quotes( $container['cnt_title'] )}'
\t,@APP_ID
\t,{$container['cnt_seq_nr']}
\t,{$cnt_table}
\t,{$cnt_form}
\t,{$cnt_relation}
\t,{$cnt_rform}
\t,{$cnt_chart}
\t,{$cnt_map}
\t,{$cnt_query}
\t);

insert into `wpda_transfer_containers_{$main_app_id}`
values
    ({$container['cnt_id']}
    ,(select LAST_INSERT_ID(`cnt_id`) from `{wp_prefix}wpda_app_container` order by 1 desc limit 1)
    );


SQL;
        }
        // Post update: update master container ids
        $containers_sql .= <<<SQL
# Update app master container IDs
update `{wp_prefix}wpda_app_container` as a
set a.`cnt_relation` =
    (
        select replace(
            a.`cnt_relation`, 
            concat('"cnt_id_master":"', b.cnt_id_old, '"'), 
            concat('"cnt_id_master":"', b.cnt_id_new, '"')
        )
        from `wpda_transfer_containers_{$main_app_id}` as b
        where a.`cnt_relation` like concat('%"cnt_id_master":"', b.cnt_id_old, '"%')
    )
where a.`app_id` = @APP_ID
  and a.`cnt_relation` is not null;


SQL;
        $apps = WPDA_App_Apps_Model::select_all( $app_id );
        $apps_sql = '';
        foreach ( $apps as $app ) {
            $apps_sql .= $this->do_app_export_app( $app['app_id_detail'], $main_app_id );
        }
        foreach ( $apps as $app ) {
            $apps_sql .= <<<SQL
# Import app relationships
insert into `{wp_prefix}wpda_app_apps`
\t(`app_id`
\t,`app_id_detail`
\t,`seq_nr`\t\t\t\t\t
\t)
values
\t((select `app_id_new` from `wpda_transfer_apps_{$main_app_id}` where `app_id_old` = {$app['app_id']})
\t,(select `app_id_new` from `wpda_transfer_apps_{$main_app_id}` where `app_id_old` = {$app['app_id_detail']})
\t,{$app['seq_nr']}\t\t\t\t\t
\t);


SQL;
        }
        return $app_sql . $containers_sql . $apps_sql;
    }

    private function do_app_export( $app_id ) {
        global $wpdb;
        $sql = '';
        $begin_sql = <<<SQL
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES {$wpdb->charset} */;

# Create temporary table
CREATE TABLE `wpda_transfer_containers_{$app_id}`
(cnt_id_old bigint(20) unsigned
,cnt_id_new bigint(20) unsigned
);

CREATE TABLE `wpda_transfer_apps_{$app_id}`
(app_id_old bigint(20) unsigned
,app_id_new bigint(20) unsigned
);
       
SET @APP_ID = NULL;


SQL;
        $sql .= $this->do_app_export_app( $app_id, $app_id );
        $end_sql = <<<SQL
# Drop temporary table
DROP TABLE `wpda_transfer_containers_{$app_id}`;
DROP TABLE `wpda_transfer_apps_{$app_id}`;
            
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


SQL;
        $data = array(
            'data' => $begin_sql . $sql . $end_sql,
        );
        return $this->WPDA_Rest_Response( __( 'App successfully exported', 'wp-data-access' ), $data );
    }

    private function do_app_copy( $app_id ) {
        $copy = WPDA_App_Model::copy( $app_id );
        if ( false === $copy['app_id'] ) {
            return new \WP_Error('error', $copy['msg'], array(
                'status' => 403,
            ));
        } else {
            return $this->WPDA_Rest_Response( __( 'App successfully copied', 'wp-data-access' ) );
        }
    }

    private function do_app_saveapp(
        $app_id,
        $app_name,
        $app_title,
        $app_type,
        $app_settings,
        $app_add_to_menu,
        $app_apps
    ) {
        $error_msg = WPDA_App_Model::update(
            $app_id,
            $app_name,
            $app_title,
            $app_type,
            $app_settings,
            $app_add_to_menu
        );
        if ( '' !== $error_msg ) {
            return new \WP_Error('error', $error_msg, array(
                'status' => 403,
            ));
        }
        WPDA_App_Apps_Model::update( $app_id, $app_apps );
        return $this->WPDA_Rest_Response( __( 'Changes successfully saved', 'wp-data-access' ) );
    }

    private function do_app_save(
        $app_id,
        $app_name,
        $app_title,
        $app_type,
        $app_settings,
        $app_add_to_menu,
        $app_dbs,
        $app_tbl,
        $app_cls,
        $app_query
    ) {
        $error_msg = WPDA_App_Model::update(
            $app_id,
            $app_name,
            $app_title,
            $app_type,
            $app_settings,
            $app_add_to_menu
        );
        if ( '' !== $error_msg ) {
            return new \WP_Error('error', $error_msg, array(
                'status' => 403,
            ));
        }
        $error_msg = WPDA_App_Container_Model::update_master(
            $app_id,
            $app_dbs,
            $app_tbl,
            json_encode( $app_cls ),
            $app_query
        );
        if ( '' !== $error_msg ) {
            return new \WP_Error('error', $error_msg, array(
                'status' => 403,
            ));
        }
        return $this->WPDA_Rest_Response( __( 'Changes successfully saved', 'wp-data-access' ) );
    }

    private function main_app_access( $app_id, &$msg = '', &$app = null ) {
        // Get app info
        $app = WPDA_App_Model::get_by_id( $app_id );
        if ( false === $app ) {
            // App not found
            $msg = __( 'Bad request', 'wp-data-access' );
            return false;
        }
        // Check access
        $app_settings_db = $app[0]['app_settings'];
        $app_settings = json_decode( (string) $app_settings_db, true );
        if ( !isset( $app_settings['rest_api']['authorization'], $app_settings['rest_api']['authorized_roles'], $app_settings['rest_api']['authorized_users'] ) || !is_array( $app_settings['rest_api']['authorized_roles'] ) || !is_array( $app_settings['rest_api']['authorized_users'] ) ) {
            // App contain no rest api settings
            $msg = __( 'Bad request', 'wp-data-access' );
            return false;
        }
        if ( !$this->current_user_can_access() && 'anonymous' !== $app_settings['rest_api']['authorization'] ) {
            // Check authorization
            // Check user role
            $user_roles = WPDA::get_current_user_roles();
            if ( !is_array( $user_roles ) || empty( array_intersect( $app_settings['rest_api']['authorized_roles'], $user_roles ) ) ) {
                // Check user login
                $user_login = WPDA::get_current_user_login();
                if ( !in_array( $user_login, $app_settings['rest_api']['authorized_users'] ) ) {
                    $msg = __( 'Unauthorized', 'wp-data-access' );
                    return false;
                }
            }
        }
        return true;
    }

    public function check_app_access(
        $app_id,
        $cnt_id,
        $action,
        &$dbs,
        &$tbl,
        &$msg = '',
        &$settings = array()
    ) {
        if ( !$this->main_app_access( $app_id, $msg ) ) {
            return false;
        }
        // Get container
        $container = WPDA_App_Container_Model::get_container( $app_id, $cnt_id );
        if ( !is_array( $container ) || 0 === count( $container ) ) {
            // Container not found
            $msg = __( 'Bad request', 'wp-data-access' );
            return false;
        }
        if ( 'select' !== $action ) {
            $cnt_table = json_decode( (string) $container[0]['cnt_table'], true );
            if ( !isset( $cnt_table['table']['transactions'][$action] ) || false === $cnt_table['table']['transactions'][$action] ) {
                if ( !isset( $cnt_table['table']['bulkActions'][$action] ) || false === $cnt_table['table']['bulkActions'][$action] ) {
                    $cnt_relation = json_decode( (string) $container[0]['cnt_relation'], true );
                    if ( !(isset( $cnt_relation['cnt_id_master'] ) && $this->check_master_container_access( $app_id, $cnt_relation['cnt_id_master'], $action )) ) {
                        $msg = __( 'Unauthorized', 'wp-data-access' );
                        return false;
                    }
                }
            }
        }
        // Return database name, table name and columns
        $dbs = $container[0]['cnt_dbs'];
        $tbl = $container[0]['cnt_tbl'];
        $settings = array(
            'columns' => json_decode( (string) $container[0]['cnt_cls'], true ),
            'table'   => json_decode( (string) $container[0]['cnt_table'], true ),
            'form'    => json_decode( (string) $container[0]['cnt_form'], true ),
        );
        return true;
    }

    private function check_master_container_access( $app_id, $cnt_id, $action ) {
        $container = WPDA_App_Container_Model::get_container( $app_id, $cnt_id );
        if ( !is_array( $container ) || 0 === count( $container ) ) {
            // Container not found
            return false;
        }
        $cnt_table = json_decode( (string) $container[0]['cnt_table'], true );
        if ( !isset( $cnt_table['table']['transactions'][$action] ) || false === $cnt_table['table']['transactions'][$action] ) {
            $cnt_relation = json_decode( (string) $container[0]['cnt_relation'], true );
            if ( !(isset( $cnt_relation['cnt_id_master'] ) && $this->check_master_container_access( $app_id, $cnt_relation['cnt_id_master'], $action )) ) {
                return false;
            }
        }
        return true;
    }

    private function process_params(
        $where,
        $search_custom,
        $search_params,
        $shortcode_params,
        $dynamic_params = array()
    ) {
        // Process $search_custom > URL parameters
        global $wpdb;
        foreach ( self::METHODS as $method ) {
            $offset = 0;
            $search = $method . '[';
            while ( ($pos_start = stripos( $where, $search, $offset )) !== false ) {
                if ( ($pos_end = stripos( $where, ']', $pos_start )) !== false ) {
                    // Get filter
                    $filter = substr( $where, $pos_start, $pos_end - $pos_start + 1 );
                    // Get name
                    $arg_name = substr( $where, $pos_start + strlen( $search ), $pos_end - $pos_start - strlen( $search ) );
                    // Remove quotes from name
                    if ( substr( $arg_name, 0, 1 ) === "'" && substr( $arg_name, -1 ) === "'" ) {
                        $arg_name = substr( $arg_name, 1, -1 );
                    }
                    // Remove double quotes from name
                    if ( substr( $arg_name, 0, 1 ) === '"' && substr( $arg_name, -1 ) === '"' ) {
                        $arg_name = substr( $arg_name, 1, -1 );
                    }
                    // Handle GET args
                    if ( $method === self::METHODS[0] ) {
                        if ( isset( $search_custom['get'][$arg_name] ) ) {
                            $arg_value = sanitize_text_field( wp_unslash( $search_custom['get'][$arg_name] ) );
                            $where = $wpdb->prepare( substr_replace(
                                $where,
                                '%s',
                                $pos_start,
                                $pos_end - $pos_start + 1
                            ), $arg_value );
                        } else {
                            $where = str_replace( $filter, 'null', $where );
                        }
                    }
                    // Handle POST args
                    if ( $method === self::METHODS[1] ) {
                        if ( isset( $search_custom['post'][$arg_name] ) ) {
                            $arg_value = sanitize_text_field( wp_unslash( $search_custom['post'][$arg_name] ) );
                            $where = $wpdb->prepare( substr_replace(
                                $where,
                                '%s',
                                $pos_start,
                                $pos_end - $pos_start + 1
                            ), $arg_value );
                        } else {
                            $where = str_replace( $filter, 'null', $where );
                        }
                    }
                    // Handle REQUEST args
                    if ( $method === self::METHODS[2] ) {
                        if ( isset( $search_custom['get'][$arg_name] ) ) {
                            $arg_value = sanitize_text_field( wp_unslash( $search_custom['get'][$arg_name] ) );
                            $where = $wpdb->prepare( substr_replace(
                                $where,
                                '%s',
                                $pos_start,
                                $pos_end - $pos_start + 1
                            ), $arg_value );
                        } elseif ( isset( $search_custom['post'][$arg_name] ) ) {
                            $arg_value = sanitize_text_field( wp_unslash( $search_custom['post'][$arg_name] ) );
                            $where = $wpdb->prepare( substr_replace(
                                $where,
                                '%s',
                                $pos_start,
                                $pos_end - $pos_start + 1
                            ), $arg_value );
                        } else {
                            $where = str_replace( $filter, 'null', $where );
                        }
                    }
                }
                $offset = $pos_start + 1;
                if ( $offset > strlen( $where ) ) {
                    $offset = strlen( $where ) - 1;
                }
            }
        }
        // Process $search_params > shortcode parameters
        if ( is_array( $search_params ) && 1 === count( $search_params ) ) {
            $filter_field_name = $this->sanitize_db_identifier( array_keys( $search_params )[0] );
            $filter_field_value = sanitize_text_field( $search_params[$filter_field_name] );
            $filter_field_name_array = array_map( 'trim', explode( ',', $filter_field_name ) );
            //phpcs:ignore - 8.1 proof
            $filter_field_value_array = array_map( 'trim', explode( ',', $filter_field_value ) );
            //phpcs:ignore - 8.1 proof
            if ( count( $filter_field_name_array ) === count( $filter_field_value_array ) ) {
                //phpcs:ignore - 8.1 proof
                // Add filter to where clause.
                for ($i = 0; $i < count( $filter_field_name_array ); $i++) {
                    // phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall, Squiz.PHP.DisallowSizeFunctionsInLoops
                    $where .= (( '' === $where ? '' : ' and ' )) . $wpdb->prepare( 
                        ' `%1s` like %s ',
                        // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
                        array($filter_field_name_array[$i], $filter_field_value_array[$i])
                     );
                }
            }
        }
        // Substitute all shortcode parameters
        if ( is_array( $shortcode_params ) ) {
            foreach ( $shortcode_params as $column_name => $column_value ) {
                $occurences = substr_count( strtolower( $where ), strtolower( "shortcodeParam['{$column_name}']" ) );
                if ( 0 < $occurences ) {
                    $column_values = array();
                    for ($i = 0; $i < $occurences; $i++) {
                        $column_values[] = sanitize_text_field( $column_value );
                    }
                    $where = $wpdb->prepare( str_ireplace( "shortcodeParam['{$column_name}']", '%s', $where ), $column_values );
                }
            }
        }
        // Substitute all unused shortcode parameter calls with null
        $offset = 0;
        $search = "shortcodeParam['";
        while ( ($pos_start = stripos( $where, $search, $offset )) !== false ) {
            if ( ($pos_end = stripos( $where, "']", $pos_start )) !== false ) {
                $shortcode_value = substr( $where, $pos_start, $pos_end - $pos_start + 2 );
                $where = str_ireplace( $shortcode_value, 'null', $where );
            }
            $offset = $pos_start + 4;
            if ( $offset > strlen( $where ) ) {
                $offset = strlen( $where ) - 1;
            }
        }
        // Substitute all dynamic parameters
        if ( is_array( $dynamic_params ) && 0 < count( $dynamic_params ) ) {
            foreach ( $dynamic_params as $column_name => $column_value ) {
                $where = $wpdb->prepare( str_ireplace( "{:{$column_name}}", '%s', $where ), $column_value );
            }
        }
        return $where;
    }

}

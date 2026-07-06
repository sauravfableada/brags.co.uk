<?php

namespace WPDataAccess\API;

use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Lists;
use WPDataAccess\Query_Builder\WPDA_Query_Builder;
use WPDataAccess\Query_Builder\WPDA_Query_Builder_Scheduler;
use WPDataAccess\Utilities\WPDA_Mail;
use WPDataAccess\WPDA;
class WPDA_QB extends WPDA_API_Core {
    const QUERY_BUILDER_AUTO_COMPLETE = 'wpda_query_builder_auto_complete';

    public function register_rest_routes() {
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'qb/open', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'open'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'access' => $this->get_param( 'access' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'qb/run', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'run'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs'     => $this->get_param( 'dbs' ),
                'query'   => $this->get_param( 'query' ),
                'limit'   => array(
                    'required'          => false,
                    'type'              => 'string',
                    'description'       => __( 'Limit query output', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'protect' => array(
                    'required'          => false,
                    'type'              => 'boolean',
                    'description'       => __( 'Protect WordPress tables', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'params'  => $this->get_param( 'params' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'qb/save', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'save'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'access'    => $this->get_param( 'access' ),
                'dbs'       => $this->get_param( 'dbs' ),
                'name'      => $this->get_param( 'name' ),
                'query'     => $this->get_param( 'query' ),
                'is_visual' => array(
                    'required'          => false,
                    'type'              => 'boolean',
                    'description'       => __( 'Is Visual Query?', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'vqb'       => $this->get_param( 'vqb' ),
                'old_name'  => array(
                    'required'          => false,
                    'type'              => 'string',
                    'description'       => __( 'Old query name', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'insert'    => array(
                    'required'          => false,
                    'type'              => 'boolean',
                    'description'       => __( 'Perform insert', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'params'    => $this->get_param( 'params' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'qb/delete', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'delete'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'access' => $this->get_param( 'access' ),
                'name'   => $this->get_param( 'name' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'qb/copy', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'copy'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'access_from' => $this->get_param( 'access' ),
                'from'        => $this->get_param( 'name' ),
                'access_to'   => $this->get_param( 'access' ),
                'to'          => $this->get_param( 'name' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'qb/hints', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'hints'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'qb/ac', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'ac'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'enable' => array(
                    'required'          => false,
                    'type'              => 'boolean',
                    'description'       => __( 'Get|set auto complete', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'qb/cron/schedules', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'cron_schedules'),
            'permission_callback' => '__return_true',
            'args'                => array(),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'qb/cron/add', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'cron_add'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'access'   => $this->get_param( 'access' ),
                'name'     => $this->get_param( 'name' ),
                'params'   => $this->get_param( 'params' ),
                'start'    => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'description'       => __( 'Start date/time', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
                'interval' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => __( 'Recurrence', 'wp-data-access' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'qb/cron/delete', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'cron_delete'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'access' => $this->get_param( 'access' ),
                'name'   => $this->get_param( 'name' ),
                'params' => $this->get_param( 'params' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'qb/vqb/get', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'vqb_get'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'access' => $this->get_param( 'access' ),
                'name'   => $this->get_param( 'name' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'qb/vqb/tbl', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'vqb_tbl'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
                'tbl' => $this->get_param( 'tbl' ),
            ),
        ) );
    }

    public function vqb_tbl( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $cols = WPDA_Dictionary_Lists::get_table_widget_info( $dbs, $tbl );
        return $this->WPDA_Rest_Response( '', $cols );
    }

    public function vqb_get( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $access = $request->get_param( 'access' );
        $name = $request->get_param( 'name' );
        $qb = new WPDA_Query_Builder();
        if ( 'user' === $access ) {
            $query = $qb->get_visual_query( $name );
        } else {
            $query = maybe_unserialize( $qb->get_visual_query_global( $name ) );
        }
        return $this->WPDA_Rest_Response( '', $query );
    }

    public function open( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $access = $request->get_param( 'access' );
        $qb = new WPDA_Query_Builder();
        if ( 'user' === $access ) {
            $queries = $qb->get_query_list();
        } else {
            $queries = $qb->get_query_list_global();
        }
        if ( is_array( $queries ) ) {
            uksort( $queries, 'strnatcasecmp' );
        }
        return $this->WPDA_Rest_Response( '', $queries );
    }

    public function run( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $dbs = $request->get_param( 'dbs' );
        $query = $request->get_param( 'query' );
        $limit = $request->get_param( 'limit' );
        $protect = $request->get_param( 'protect' );
        $params = $request->get_param( 'params' );
        $qb = new WPDA_Query_Builder();
        return $this->WPDA_Rest_Response( '', $qb->execute_query(
            $dbs,
            $query,
            $limit,
            $protect,
            $params
        ) );
    }

    public function save( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $access = $request->get_param( 'access' );
        $dbs = $request->get_param( 'dbs' );
        $name = $request->get_param( 'name' );
        $query = $request->get_param( 'query' );
        $is_visual = '1' === $request->get_param( 'is_visual' );
        $vqb = $request->get_param( 'vqb' );
        WPDA::wpda_log_wp_error( $vqb );
        $old_name = $request->get_param( 'old_name' ) ?? '';
        $insert = '1' === $request->get_param( 'insert' );
        $params = $request->get_param( 'params' );
        $qb = new WPDA_Query_Builder();
        $saved = array();
        if ( 'user' === $access ) {
            if ( $insert ) {
                // Check if query name is already used
                $saved = $qb->get_query( $name );
            }
            if ( 0 === count( $saved ) ) {
                $qb->update_query(
                    $dbs,
                    $name,
                    $query,
                    $old_name,
                    $is_visual,
                    $vqb,
                    $params
                );
            }
        } else {
            if ( $insert ) {
                // Check if query name is already used
                $saved = $qb->get_query_global( $name );
            }
            if ( 0 === count( $saved ) ) {
                $qb->update_query_global(
                    $dbs,
                    $name,
                    $query,
                    $old_name,
                    $is_visual,
                    $vqb,
                    $params
                );
            }
        }
        if ( 0 === count( $saved ) ) {
            return $this->WPDA_Rest_Response( '' );
        } else {
            return new \WP_Error('error', 'Query name already exists', array(
                'status' => 403,
            ));
        }
    }

    public function delete( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $access = $request->get_param( 'access' );
        $name = $request->get_param( 'name' );
        $qb = new WPDA_Query_Builder();
        if ( 'user' === $access ) {
            $qb->delete_query( $name );
        } else {
            $qb->delete_query_global( $name );
        }
        return $this->WPDA_Rest_Response( '' );
    }

    public function copy( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $access_from = $request->get_param( 'access_from' );
        $from_name = $request->get_param( 'from' );
        $access_to = $request->get_param( 'access_to' );
        $to_name = $request->get_param( 'to' );
        $qb = new WPDA_Query_Builder();
        $source = ( 'user' === $access_from ? $qb->get_query( $from_name ) : $qb->get_query_global( $from_name ) );
        $visual = ( 'user' === $access_from ? $qb->get_visual_query( $from_name ) : maybe_unserialize( $qb->get_visual_query_global( $from_name ) ) );
        if ( 0 === count( $source ) ) {
            return new \WP_Error('error', 'Source query not found', array(
                'status' => 403,
            ));
        }
        if ( 'user' === $access_to ) {
            $saved = $qb->get_query( $to_name );
            if ( 0 === count( $saved ) ) {
                $qb->add_query( $to_name, $source );
                if ( !empty( $visual ) ) {
                    $qb->upd_visual_query( $to_name, $visual );
                }
            }
        } else {
            $saved = $qb->get_query_global( $to_name );
            if ( 0 === count( $saved ) ) {
                $qb->add_query_global( $to_name, $source );
                if ( !empty( $visual ) ) {
                    $qb->upd_visual_query_global( $to_name, $visual );
                }
            }
        }
        if ( 0 === count( $saved ) ) {
            return $this->WPDA_Rest_Response( '' );
        } else {
            return new \WP_Error('error', 'Query name already exists', array(
                'status' => 403,
            ));
        }
    }

    public function hints( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $dbs = $request->get_param( 'dbs' );
        $qb = new WPDA_Query_Builder();
        return $this->WPDA_Rest_Response( '', $qb->get_hints( $dbs ) );
    }

    public function ac( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $enable = $request->get_param( 'enable' );
        if ( null === $enable ) {
            // Return current value
            return $this->WPDA_Rest_Response( get_user_meta( WPDA::get_current_user_id(), self::QUERY_BUILDER_AUTO_COMPLETE, true ) );
        } elseif ( '1' == $enable ) {
            // Enable auto complete
            update_user_meta( WPDA::get_current_user_id(), self::QUERY_BUILDER_AUTO_COMPLETE, true );
            return $this->WPDA_Rest_Response( '' );
        } else {
            // Enable auto complete
            update_user_meta( WPDA::get_current_user_id(), self::QUERY_BUILDER_AUTO_COMPLETE, false );
            return $this->WPDA_Rest_Response( '' );
        }
    }

    public function cron_schedules( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
    }

    public function cron_add( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
    }

    public function cron_delete( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
    }

}

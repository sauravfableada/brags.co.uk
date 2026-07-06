<?php

namespace WPDataAccess\API;

use WPDataAccess\Connection\WPDADB;
use WPDataAccess\WPDA;
class WPDA_Tree extends WPDA_API_Core {
    public function register_rest_routes() {
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'tree/dbs', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'tree_dbs'),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'tree/tbl', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'tree_tbl'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'tree/vws', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'tree_vws'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'tree/cls', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'tree_cls'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
                'tbl' => $this->get_param( 'tbl' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'tree/idx', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'tree_idx'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
                'tbl' => $this->get_param( 'tbl' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'tree/trg', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'tree_trg'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
                'tbl' => $this->get_param( 'tbl' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'tree/frk', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'tree_frk'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
                'tbl' => $this->get_param( 'tbl' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'tree/fnc', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'tree_fnc'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
            ),
        ) );
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'tree/prc', array(
            'methods'             => array('POST'),
            'callback'            => array($this, 'tree_prc'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'dbs' => $this->get_param( 'dbs' ),
            ),
        ) );
    }

    public function tree_dbs( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        return $this->get_dbs();
    }

    public function tree_tbl( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $dbs = $request->get_param( 'dbs' );
        return $this->get_tbl( $dbs );
    }

    public function tree_vws( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $dbs = $request->get_param( 'dbs' );
        return $this->get_vws( $dbs );
    }

    public function tree_cls( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        return $this->get_cls( $dbs, $tbl );
    }

    public function tree_idx( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        return $this->get_idx( $dbs, $tbl );
    }

    public function tree_trg( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        return $this->get_trg( $dbs, $tbl );
    }

    public function tree_frk( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        return $this->get_frk( $dbs, $tbl );
    }

    public function tree_fnc( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $dbs = $request->get_param( 'dbs' );
        return $this->get_fnc( $dbs );
    }

    public function tree_prc( $request ) {
        if ( !$this->current_user_can_access() ) {
            return $this->unauthorized();
        }
        if ( !$this->current_user_token_valid( $request ) ) {
            return $this->invalid_nonce();
        }
        $dbs = $request->get_param( 'dbs' );
        return $this->get_prc( $dbs );
    }

    public function get_dbs( $include_disabled = false ) {
        // Local databases
        global $wpdb;
        $local = $wpdb->get_results( "\n\t\t\t\t\tSELECT  schema_name AS dbs,\n\t\t\t\t\t\t\t'local' AS dbs_type,\n\t\t\t\t\t\t\t'false' AS pds\n\t\t\t\t\t   FROM information_schema.schemata\n\t\t\t\t\t  ORDER BY schema_name\n\t\t\t  \t", 'ARRAY_A' );
        for ($i = 0; $i < count( $local ); $i++) {
            if ( $wpdb->dbname === $local[$i]['dbs'] ) {
                // WordPress database
                $local[$i]['dbs_type'] = 'wp';
            }
            // System databases
            if ( 'information_schema' === $local[$i]['dbs'] || 'mysql' === $local[$i]['dbs'] || 'performance_schema' === $local[$i]['dbs'] || 'sys' === $local[$i]['dbs'] ) {
                // WordPress database
                $local[$i]['dbs_type'] = 'system';
            }
            $local[$i]['pds'] = false;
        }
        // Remote databases
        $remote = array();
        $pds = array();
        $rdb = WPDADB::get_remote_databases( $include_disabled );
        foreach ( $rdb as $key => $val ) {
            $remote[] = array(
                'dbs'      => $key,
                'dbs_type' => 'remote',
                'pds'      => isset( $pds[substr( $key, 4 )] ),
            );
        }
        global $wpdb;
        $context = array(
            'env' => $this->get_env(),
            'wp'  => [
                'roles'  => $this->get_wp_roles(),
                'users'  => $this->get_wp_users(),
                'home'   => admin_url( 'admin.php' ),
                'tables' => array_values( $wpdb->tables() ),
                'nonce'  => implode( '-', array(wp_create_nonce( 'wpda-export-' . WPDA::get_current_user_login() )) ),
                'zip'    => class_exists( '\\ZipArchive' ),
                'upload' => @ini_get( 'upload_max_filesize' ),
            ],
        );
        return $this->WPDA_Rest_Response( 
            '',
            array_merge( $local, $remote ),
            //phpcs:ignore - 8.1 proof
            $context
         );
    }

    public function get_tbl_vws( $dbs ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( $wpdadb === null ) {
            return $this->WPDA_Rest_Response( '', array() );
        }
        $query = $wpdadb->prepare( "\n\t\t\t\t\tselect table_name as table_name\n\t\t\t\t\t  from information_schema.tables\n\t\t\t\t\t where table_schema = %s\n\t\t\t\t\t order by table_name\n\t\t\t\t", array($wpdadb->dbname) );
        return $this->WPDA_Rest_Response( '', array_column( 
            $wpdadb->get_results( $query, 'ARRAY_A' ),
            // phpcs:ignore Standard.Category.SniffName.ErrorCode
            'table_name'
         ) );
    }

    private function get_tbl( $dbs ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( $wpdadb === null ) {
            return $this->WPDA_Rest_Response( '', array() );
        }
        $query = $wpdadb->prepare( "\n\t\t\t\t\tselect table_name as table_name\n\t\t\t\t\t  from information_schema.tables\n\t\t\t\t\t where table_schema = %s\n\t\t\t\t\t   and table_type not in ('VIEW', 'SYSTEM VIEW')\n\t\t\t\t\t order by table_name\n\t\t\t\t", array($wpdadb->dbname) );
        $context = null;
        global $wpdb;
        if ( $wpdadb->dbname === $wpdb->dbname ) {
            $context = array(
                'wp_tables'   => array_keys( WPDA::get_wp_tables() ),
                'wpda_tables' => WPDA::get_wpda_tables(),
            );
        }
        return $this->WPDA_Rest_Response( '', array_column( 
            $wpdadb->get_results( $query, 'ARRAY_A' ),
            // phpcs:ignore Standard.Category.SniffName.ErrorCode
            'table_name'
         ), $context );
    }

    private function get_vws( $dbs ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( $wpdadb === null ) {
            return $this->WPDA_Rest_Response( '', array() );
        }
        $query = $wpdadb->prepare( "\n\t\t\t\t\tselect table_name as view_name\n\t\t\t\t\t  from information_schema.tables\n\t\t\t\t\t where table_schema = %s\n\t\t\t\t\t   and table_type in ('VIEW', 'SYSTEM VIEW')\n\t\t\t\t\t order by table_name\n\t\t\t\t", array($wpdadb->dbname) );
        return $this->WPDA_Rest_Response( '', array_column( 
            $wpdadb->get_results( $query, 'ARRAY_A' ),
            // phpcs:ignore Standard.Category.SniffName.ErrorCode
            'view_name'
         ) );
    }

    public function get_cls( $dbs, $tbl ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( $wpdadb === null ) {
            return $this->WPDA_Rest_Response( '', array() );
        }
        $query = $wpdadb->prepare( "\n\t              SELECT column_name AS column_name,\n\t                     extra AS extra,\n\t                     column_type AS column_type,\n\t                     is_nullable AS is_nullable,\n\t                     IF(LEFT(column_default,1)='\\'' AND RIGHT(column_default,1)='\\'', SUBSTR(column_default,2,LENGTH(column_default)-2), column_default) AS column_default,\n\t              \t\t character_maximum_length AS character_maximum_length,\n\t                     numeric_scale AS numeric_scale,\n\t                     numeric_precision AS numeric_precision,\n\t                     ordinal_position AS ordinal_position\n\t                FROM information_schema.columns\n\t               WHERE table_schema = %s\n\t                 AND table_name   = %s\n\t               ORDER BY ordinal_position\n\t            ", array($wpdadb->dbname, $tbl) );
        return $this->WPDA_Rest_Response( '', $wpdadb->get_results( $query, 'ARRAY_A' ) );
    }

    private function get_idx( $dbs, $tbl ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( $wpdadb === null ) {
            return $this->WPDA_Rest_Response( '', array() );
        }
        $query = $wpdadb->prepare( "\n\t\t\t\t\tSELECT index_name AS index_name,\n\t\t\t\t\t\t   IF(non_unique=0, 'YES', 'NO') AS non_unique,\n\t\t\t\t\t\t   seq_in_index AS seq_in_index,\n\t\t\t\t\t\t   column_name AS column_name,\n\t\t\t\t\t\t   collation AS collation,\n\t\t\t\t\t\t   nullable AS nullable,\n\t\t\t\t\t\t   index_type AS index_type,\n\t\t\t\t\t\t   cardinality AS cardinality\n\t\t\t\t\tFROM   information_schema.statistics\n\t\t\t\t\tWHERE  table_schema = %s\n\t\t\t\t\t  AND  table_name   = %s\n\t\t\t\t\tORDER BY index_name, seq_in_index\n\t\t\t\t", array($wpdadb->dbname, $tbl) );
        return $this->WPDA_Rest_Response( '', $wpdadb->get_results( $query, 'ARRAY_A' ) );
    }

    private function get_trg( $dbs, $tbl ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( $wpdadb === null ) {
            return $this->WPDA_Rest_Response( '', array() );
        }
        $query = $wpdadb->prepare( '
					SELECT trigger_name AS trigger_name,
						   event_manipulation AS event_manipulation,
						   action_timing AS action_timing,
						   action_statement AS action_statement
					FROM   information_schema.triggers
					WHERE  event_object_schema = %s
					  AND  event_object_table  = %s
					ORDER BY trigger_name
				', array($wpdadb->dbname, $tbl) );
        return $this->WPDA_Rest_Response( '', $wpdadb->get_results( $query, 'ARRAY_A' ) );
    }

    private function get_frk( $dbs, $tbl ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( $wpdadb === null ) {
            return $this->WPDA_Rest_Response( '', array() );
        }
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
				', array($wpdadb->dbname, $tbl) );
        return $this->WPDA_Rest_Response( '', $wpdadb->get_results( $query, 'ARRAY_A' ) );
    }

    private function get_prc( $dbs ) {
        return $this->get_rtn( $dbs, 'PROCEDURE' );
    }

    private function get_fnc( $dbs ) {
        return $this->get_rtn( $dbs, 'FUNCTION' );
    }

    private function get_rtn( $dbs, $typ ) {
        $wpdadb = WPDADB::get_db_connection( $dbs );
        if ( $wpdadb === null ) {
            return $this->WPDA_Rest_Response( '', array() );
        }
        $query = $wpdadb->prepare( '
					SELECT routine_name AS routine_name,
					       routine_definition AS routine_definition
					  FROM information_schema.routines
					 WHERE routine_schema = %s
					   AND routine_type   = %s
				', array($wpdadb->dbname, $typ) );
        return $this->WPDA_Rest_Response( '', $wpdadb->get_results( $query, 'ARRAY_A' ) );
    }

}

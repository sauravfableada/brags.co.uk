<?php

namespace WPDataAccess\Query_Builder;

use WPDataAccess\Connection\WPDADB;
use WPDataAccess\Utilities\WPDA_Mail;
use WPDataAccess\WPDA;
class WPDA_Query_Builder_Scheduler {
    const SCHEDULER_HOOK_NAME = 'wpda_run_scheduled_sql';

    public static function wpda_cron_events() {
        return array();
    }

    private static function queryDecode( $sql ) {
    }

    public static function run_scheduled_sql( $args ) {
    }

    private static function notify_multiple_queries( $tabs, $params ) {
    }

    private static function attach_csv( $response ) {
    }

    private static function attach_json( $response ) {
    }

    private static function attach_xml( $response ) {
    }

    private static function notify_single_query( $response, $params ) {
    }

    private static function mail( $to, $message, $attachments = array() ) {
    }

    public static function get_scheduled_sql( $name, $access, $params ) {
        return array();
    }

}

<?php

namespace WPDataAccess\Utilities;

use WPDataAccess\Drive\WPDA_Drives;
use WPDataAccess\WPDA;
class WPDA_Export_Scheduler {
    const SCHEDULER_HOOK_NAME = 'wpda_run_scheduled_export';

    public static function wpda_cron_events() {
        $cron = get_option( 'cron' );
        if ( !is_array( $cron ) ) {
            $cron = array();
        }
        $cron_wpda = array_filter( $cron, function ( $item ) {
            if ( isset( $item[self::SCHEDULER_HOOK_NAME] ) ) {
                return true;
            }
        } );
        return $cron_wpda;
    }

    public static function run_scheduled_export( $args ) {
        if ( isset( 
            $args['name'],
            $args['params']['dbs'],
            $args['params']['tbl'],
            $args['params']['drv'],
            $args['params']['eml'],
            $args['params']['fkp']
         ) ) {
            $name = $args['name'];
            $dbs = $args['params']['dbs'];
            $tbl = $args['params']['tbl'];
            $drv = $args['params']['drv'];
            $eml = $args['params']['eml'];
            $fkp = $args['params']['fkp'];
            $drive = WPDA_Drives::get_drive( $drv, true );
            if ( false !== $drive ) {
                $refresh_token = $drive->refresh_token();
                if ( null === $refresh_token || false === $refresh_token ) {
                    // Cannot refresh token
                    WPDA::wpda_log_wp_error( "Export {$name} failed (could not refresh token)" );
                    self::mail( $eml, "Export {$name} failed (could not refresh token)" );
                } else {
                    if ( false === $drive->connect() ) {
                        // Cannot connect
                        WPDA::wpda_log_wp_error( "Export {$name} failed (could not connect)" );
                        self::mail( $eml, "Export {$name} failed (could not connect)" );
                    } else {
                        // Export tables to temporary file
                        $temporary_file = tmpfile();
                        $wpda_export = new WPDA_Export_Sql();
                        $wpda_export->set_output_stream( $temporary_file );
                        $tables = ( str_contains( $tbl, ',' ) !== false ? explode( ',', $tbl ) : $tbl );
                        $wpda_export->export_with_arguments(
                            'on',
                            'on',
                            'on',
                            $dbs,
                            $tables,
                            'table'
                        );
                        if ( WPDA_Remote_Call::max_size() < filesize( stream_get_meta_data( $temporary_file )['uri'] ) && !extension_loaded( 'zip' ) ) {
                            // File is too big to send in one chunk and zip archive is not installed.
                            WPDA::wpda_log_wp_error( "Export {$name} failed (ZipArchive not installed)" );
                            self::mail( $eml, "Export {$name} failed (ZipArchive not installed)" );
                        } else {
                            $separator = '-';
                            $file_ext = '.sql';
                            $remote_file = $name . $separator . gmdate( 'YmdHis' ) . $file_ext;
                            if ( extension_loaded( 'zip' ) ) {
                                $zip = new \ZipArchive();
                                $zipfile = sys_get_temp_dir() . '/' . $remote_file . '.zip';
                                if ( !$zip->open( $zipfile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) ) {
                                    WPDA::wpda_log_wp_error( "Export {$name} failed (could not create ZIP file)" );
                                    self::mail( $eml, "Export {$name} failed (could not create ZIP file)" );
                                } else {
                                    $zip->addFile( stream_get_meta_data( $temporary_file )['uri'], $remote_file );
                                    $zip->close();
                                    // Upload file
                                    $drive->upload_file( $zipfile, $remote_file . '.zip' );
                                    if ( '0' !== $fkp ) {
                                        // Cleanup
                                        $drive->cleanup( $name . $separator . '*' . $file_ext . '.zip', $fkp );
                                    }
                                    // Remove ZIP file
                                    unlink( $zipfile );
                                    self::mail( $eml, "Export {$name} successfully completed at " . gmdate( 'Y-m-d H:i:s' ) );
                                }
                            } else {
                                fseek( $temporary_file, 0 );
                                // Upload file
                                $drive->upload_file( stream_get_meta_data( $temporary_file )['uri'], $remote_file );
                                if ( '0' !== $fkp ) {
                                    // Cleanup
                                    $drive->cleanup( $name . $separator . '*' . $file_ext, $fkp );
                                }
                                self::mail( $eml, "Export {$name} successfully completed at " . gmdate( 'Y-m-d H:i:s' ) );
                            }
                            // Remove temporary file
                            fclose( $temporary_file );
                        }
                    }
                }
            } else {
                WPDA::wpda_log_wp_error( "Export {$name} failed (drive not found)" );
                self::mail( $eml, "Export {$name} failed (drive not found)" );
            }
        }
    }

    public static function get_export( $name ) {
        $scheduled_exports = \WPDataAccess\Utilities\WPDA_Export_Scheduler::wpda_cron_events();
        foreach ( $scheduled_exports as $time_stamp => $scheduled_export ) {
            foreach ( $scheduled_export as $wpda_events ) {
                foreach ( $wpda_events as $wpda_event ) {
                    if ( isset( $wpda_event['args']['args']['name'] ) && $name === $wpda_event['args']['args']['name'] ) {
                        return array(
                            'time_stamp' => $time_stamp,
                            'args'       => $wpda_event['args'],
                        );
                    }
                }
            }
        }
        return false;
    }

    private static function mail( $to, $message, $attachments = array() ) {
    }

}

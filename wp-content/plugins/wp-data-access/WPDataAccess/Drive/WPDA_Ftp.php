<?php

namespace WPDataAccess\Drive {

    use WPDataAccess\WPDA;

    class WPDA_Ftp extends WPDA_Drive {

        private $connection;
        private $login = false;

        public function __construct( $drive_name, $drive = array(), $enabled = false ) {

            $this->drive_type = 'ftp';

            parent::__construct( $drive_name, $drive, $enabled );

        }

        public function authorize( $authorization, $enabled = true ) {

            if (
                isset(
                    $authorization['host'],
                    $authorization['username'],
                    $authorization['password'],
                    $authorization['port'],
                    $authorization['ssl'],
                    $authorization['passive'],
                    $authorization['timeout'],
                    $authorization['directory']
                )
            ) {
                $this->drive = array(
                    'host'      => $authorization['host'],
                    'username'  => $authorization['username'],
                    'password'  => $authorization['password'],
                    'port'      => $authorization['port'],
                    'ssl'       => $authorization['ssl'],
                    'passive'   => $authorization['passive'],
                    'timeout'   => $authorization['timeout'],
                    'directory' => $authorization['directory'],
                );
                $this->enabled = $enabled;
                $this->save();

                return true;
            }

            return false;

        }

        private function prepare_connection() {

            return $this->enabled;

        }

        public function refresh_token() {

            return true; // N.A.

        }

        public function connect() {

            if ( ! $this-> prepare_connection() ) { return false; }

            if (
                ! isset(
                    $this->drive['host'],
                    $this->drive['username'],
                    $this->drive['password'],
                    $this->drive['port'],
                    $this->drive['ssl'],
                    $this->drive['passive'],
                    $this->drive['timeout'],
                    $this->drive['directory']
                )
            ) {
                return false;
            }

            $this->connection = $this->drive['ssl']
                ? ftp_ssl_connect( $this->drive['host'], $this->drive['port'], $this->drive['timeout'] )
                : ftp_connect( $this->drive['host'], $this->drive['port'], $this->drive['timeout'] );

            if ( ! $this->connection ) {
                WPDA::wpda_log_wp_error("ERROR: Could not connect to FTP server - {$this->drive_name}" );
                return false;
            }

            $this->login = ftp_login( $this->connection, $this->drive['username'], $this->drive['password'] );

            if ( ! $this->login ) {
                WPDA::wpda_log_wp_error( "ERROR: FTP login failed - {$this->drive_name}" );
                return false;
            }

            return ftp_pasv( $this->connection, $this->drive['passive'] );

        }

        public function close() {

            if ( $this->connection ) {
                ftp_close( $this->connection );
            }

        }

        public function upload_file( $local_file, $remote_file ) {

            if ( null !== $this->connection && ! $this->connection || ! $this->login ) { return false; }

            $path = '/' === substr( $this->drive['directory'], -1 ) ? $this->drive['directory'] : $this->drive['directory'] . '/';

            return ftp_put( $this->connection, $path . $remote_file, $local_file, FTP_BINARY );

        }

        public function cleanup( $query, $keep ) {

            if ( 'ALL' === $keep ) return true;

            if ( null !== $this->connection && ! $this->connection || ! $this->login ) { return false; }

            $path = '/' === substr( $this->drive['directory'], -1 ) ? $this->drive['directory'] : $this->drive['directory'] . '/';

            $files = ftp_nlist( $this->connection, $path );
            if ( ! $files || ! is_array( $files ) ) return false;

            $matched_files = array_filter( $files, function ( $file ) use ( $query ) {
                return fnmatch( $query, basename( $file ) ) !== false && $file !== '.' && $file !== '..';
            });

            usort( $matched_files, function ( $a, $b ) {
                return strcmp( $b, $a );
            });

            $count = 0;
            foreach ( $matched_files as $file ) {
                $count++;
                if ( $count > (int) $keep ) {
                    ftp_delete( $this->connection, $path . $file );
                }
            }

            return true;

        }

    }

}
<?php

namespace WPDataAccess\Drive {

    use WPDataAccess\WPDA;

    class WPDA_Sftp extends WPDA_Drive {

        private $connection = null;
        private $login = false;

        public function __construct( $drive_name, $drive = array(), $enabled = false ) {

            $this->drive_type = 'sftp';
            $this->register_phpseclib();

            parent::__construct( $drive_name, $drive, $enabled );

        }

        private function register_phpseclib() {

            $baseDir = __DIR__ . '/../../vendor/phpseclib';

            spl_autoload_register(function ($class) use ($baseDir) {
                if (strpos($class, 'phpseclib3\\') === 0) {
                    $path = str_replace('\\', '/', substr($class, strlen('phpseclib3\\')));
                    $file = $baseDir . '/' . $path . '.php';
                    if (file_exists($file)) {
                        require_once $file;
                    }
                }
            });

        }

        public function authorize( $authorization, $enabled = true ) {

            if (
                isset(
                    $authorization['host'],
                    $authorization['username'],
                    $authorization['password'],
                    $authorization['port'],
                    $authorization['timeout'],
                    $authorization['directory']
                )
            ) {
                $this->drive = array(
                    'host'      => $authorization['host'],
                    'username'  => $authorization['username'],
                    'password'  => $authorization['password'],
                    'port'      => $authorization['port'],
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

            if ( ! $this->enabled ) { return false; }

            if (
                ! isset(
                    $this->drive['host'],
                    $this->drive['port'],
                    $this->drive['timeout'],
                    $this->drive['username'],
                    $this->drive['password'],
                    $this->drive['directory']
                )
            ) {
                return false;
            }

            $this->connection = new \phpseclib3\Net\SFTP( $this->drive['host'], $this->drive['port'], $this->drive['timeout'] );

            $this->login = $this->connection->login( $this->drive['username'], $this->drive['password'] );

            if ( ! $this->login ) {
                WPDA::wpda_log_wp_error( "ERROR: SFTP login failed - {$this->drive_name}" );
                return false;
            }

            return true;

        }

        public function close() {

            if (
                null !== $this->connection &&
                $this->connection->isConnected()
            ) {
                $this->connection->disconnect();
            }

        }

        public function upload_file( $local_file, $remote_file ) {

            if ( null !== $this->connection && ! $this->connection->isConnected() || ! $this->login ) { return false; }

            $path = '/' === substr( $this->drive['directory'], -1 ) ? $this->drive['directory'] : $this->drive['directory'] . '/';

            return $this->connection->put( $path . $remote_file, $local_file, \phpseclib3\Net\SFTP::SOURCE_LOCAL_FILE );

        }

        public function cleanup( $query, $keep ) {

            if ( 'ALL' === $keep ) return true;

            if ( null !== $this->connection && ! $this->connection->isConnected() || ! $this->login ) { return false; }

            $path = '/' === substr( $this->drive['directory'], -1 ) ? $this->drive['directory'] : $this->drive['directory'] . '/';

            $files = $this->connection->nlist( $path );
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
                    $this->connection->delete( $path . $file );
                }
            }

            return true;

        }

    }

}
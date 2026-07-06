<?php

namespace WPDataAccess\Drive {

    use WPDataAccess\WPDA;

    class WPDA_Local extends WPDA_Drive {

        public function __construct( $drive = array(), $enabled = false ) {

            $this->drive_type = 'local';

            parent::__construct( 'local', $drive, $enabled );

        }

        public function authorize( $authorization, $enabled = true ) {

            if ( isset( $authorization['path'] ) ) {
                $this->drive = array(
                    'path' => $authorization['path'],
                );
                $this->enabled = $enabled;
                $this->save();

                return true;
            }

            return false;

        }

        public function refresh_token() {

            return true; // N.A.

        }

        public function connect() {

            return true; // N.A.

        }

        public function close() {

            // N.A.

        }

        public function upload_file( $local_file, $remote_file ) {

            copy( $local_file, $this->drive['path'] . $remote_file );

        }

        public function cleanup( $query, $keep ) {

            $keep_counting = 0;
            $files_sorted  = array();

            $path = '/' === substr( $this->drive['path'], -1 ) ? $this->drive['path'] : $this->drive['path'] . '/';

            foreach ( glob( $path . $query ) as $filename ) {
                array_push( $files_sorted, $filename );
            }
            rsort( $files_sorted );

            foreach ( $files_sorted as $file ) {
                $keep_counting ++;
                if ( $keep_counting > (int) $keep ) {
                    unlink( $file );
                }
            }

        }
    }

}
<?php

namespace WPDataAccess\Drive {

    use WPDataAccess\Utilities\WPDA_Remote_Call;
    use WPDataAccess\WPDA;

    class WPDA_Dropbox extends WPDA_Drive {

        const DROPBOX_CLIENT_ID = '39i1okq5b61s7k0';
        const DROPBOX_CLIENT_SECRET = '3d8plsy4prg6l3z';

        public function __construct( $drive = array(), $enabled = false ) {

            $this->drive_type = 'dropbox';

            parent::__construct( 'dropbox', $drive, $enabled );

        }

        public function authorize( $authorization, $enabled = true ) {

            // Get authorization code from link:
            // Template
            // https://www.dropbox.com/oauth2/authorize?client_id=DROPBOX_CLIENT_ID&response_type=code&token_access_type=offline
            // Actual
            // https://www.dropbox.com/oauth2/authorize?client_id=39i1okq5b61s7k0&response_type=code&token_access_type=offline

            $response = WPDA_Remote_Call::post(
                'https://api.dropboxapi.com/oauth2/token',
                array(
                    'code'          => $authorization,
                    'grant_type'    => 'authorization_code',
                    'client_id'     => self::DROPBOX_CLIENT_ID,
                    'client_secret' => self::DROPBOX_CLIENT_SECRET,
                )
            );
            if ( ! isset( $response['body'] ) ) return false;

            $body_content = json_decode( $response['body'] );
            if ( isset( $body_content->error ) ) {
                return $body_content->error;
            }

            if ( isset( $body_content->access_token, $body_content->refresh_token ) ) {
                $this->drive = array(
                    'access_token'  => $body_content->access_token,
                    'refresh_token' => $body_content->refresh_token,
                );
                $this->enabled = $enabled;
                $this->save();

                return true;
            }

            return false;

        }

        public function refresh_token() {

            if ( ! $this->enabled ) { return false; }

            if ( ! isset( $this->drive['access_token'], $this->drive['refresh_token'] ) ) { return false; }

            $response = WPDA_Remote_Call::post(
                'https://api.dropboxapi.com/oauth2/token',
                array(
                    'refresh_token' => $this->drive['refresh_token'],
                    'grant_type'    => 'refresh_token',
                    'client_id'     => self::DROPBOX_CLIENT_ID,
                    'client_secret' => self::DROPBOX_CLIENT_SECRET,
                )
            );

            if ( ! isset( $response['body'] ) ) return false;

            $body_content = json_decode( $response['body'] );
            if ( ! isset( $body_content->access_token ) ) return false;

            $this->drive['access_token'] = $body_content->access_token;
            return true;

        }

        public function connect() {

            return true; // N.A.
            //
        }

        public function close() {

            // N.A.

        }

        public function upload_file( $local_file, $remote_file ) {

            if ( ! isset( $this->drive['access_token'] ) ) return false;

            $file      = fopen( $local_file, 'r' );
            $file_size = filesize( $local_file );

            fseek( $file, 0 );
            $response = WPDA_Remote_Call::post(
                'https://content.dropboxapi.com/2/files/upload',
                fread( $file, $file_size ),
                false,
                array(
                    'Authorization'   => "Bearer {$this->drive['access_token']}",
                    'Content-Type'    => 'application/octet-stream',
                    'Dropbox-API-Arg' => '{"path":"/' . $remote_file . '","mode":"add","autorename":false,"mute":false,"strict_conflict":false}',
                )
            );

            if ( ! isset( $response['body'] ) ) return false;

            $body_content = json_decode( $response['body'] );
            return isset( $body_content->error_summary ) ? $body_content->error_summary : true;

        }

        public function cleanup( $query, $keep ) {

            if ( 'ALL' === $keep ) return true;

            $response = $this->search( '', $query );

            $body_content = json_decode( $response['body'], true );
            if ( ! isset( $body_content['matches'] ) ) { return false; }

            $keep_counting = 0;
            $files_sorted  = array();

            foreach ( $body_content['matches'] as $match ) {
                if ( isset( $match['metadata']['metadata']['name'] ) ) {
                    array_push($files_sorted, $match['metadata']['metadata']['name']);
                }
            }
            rsort( $files_sorted );

            foreach ( $files_sorted as $file ) {
                $keep_counting++;
                if ( $keep_counting > (int) $keep ) {
                    // Delete outdated files.
                    $this->delete( '/' . $file );
                }
            }

            return true;

        }

        private function search( $path, $query ) {

            if ( ! isset( $this->drive['access_token'] ) ) return false;

            return WPDA_Remote_Call::post(
                'https://api.dropboxapi.com/2/files/search_v2',
                json_encode(
                    array(
                        'options' => array(
                            'file_status' 	=> 'active',
                            'filename_only' => false,
                            'max_results' 	=> 999,
                            'path' 			=> substr( $path, 0, strlen( $path ) - 1 )
                        ),
                        'query' => $query,
                    )
                ),
                false,
                array(
                    'Authorization' => "Bearer {$this->drive['access_token']}",
                    'Content-Type'  => 'application/json',
                )
            );

        }

        private function delete( $path ) {

            if ( ! isset( $this->drive['access_token'] ) ) return false;

            WPDA_Remote_Call::post(
                'https://api.dropboxapi.com/2/files/delete_v2',
                json_encode(
                    array(
                        'path' => $path,
                    )
                ),
                false,
                array(
                    'Authorization' => "Bearer {$this->drive['access_token']}",
                    'Content-Type'  => 'application/json',
                )
            );

            return true;

        }

        public function toggle( $enabled ) {

            $this->enabled = $enabled;
            $this->save();

        }

    }

}
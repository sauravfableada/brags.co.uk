<?php

namespace WPDataAccess\Drive;

use WPDataAccess\Utilities\WPDA_Remote_Call;
use WPDataAccess\WPDA;

class WPDA_Google_Drive extends WPDA_Drive {

    const GOOGLE_CLIENT_ID     = '400971594152-30c2ro61ohe4jbb2jt09n9q86itnkjvm.apps.googleusercontent.com';
    const GOOGLE_CLIENT_SECRET = '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';
    const GOOGLE_REDIRECT_URI  = 'http://localhost';

    private $folder_id = '1xZ0CemnSlq_mnEC2860Q2W2i5n9rNAa1';

    public function __construct( $drive = array(), $enabled = false ) {

        $this->drive_type = 'google_drive';

        parent::__construct( 'google_drive', $drive, $enabled );

    }

    public function authorize( $authorization, $enabled = true ) {

        // Link to authorization application (does not return a authorization code):
        // Template
        // https://accounts.google.com/o/oauth2/v2/auth?client_id=YOUR_CLIENT_ID&redirect_uri=GOOGLE_REDIRECT_URI&response_type=code&scope=https://www.googleapis.com/auth/drive.file%20https://www.googleapis.com/auth/drive.readonly&access_type=offline&prompt=consent
        // Actual
        // https://accounts.google.com/o/oauth2/v2/auth?client_id=400971594152-30c2ro61ohe4jbb2jt09n9q86itnkjvm.apps.googleusercontent.com&redirect_uri=http://localhost&response_type=code&scope=https://www.googleapis.com/auth/drive.file%20https://www.googleapis.com/auth/drive.readonly&access_type=offline&prompt=consent

        // TODO: Get authorization code from reply

        $response = WPDA_Remote_Call::post(
            'https://oauth2.googleapis.com/token',
            array(
                'code'          => $authorization,
                'client_id'     => self::GOOGLE_CLIENT_ID,
                'client_secret' => self::GOOGLE_CLIENT_SECRET,
                'redirect_uri'  => self::GOOGLE_REDIRECT_URI,
                'grant_type'    => 'authorization_code',
            )
        );

        if ( ! isset( $response['body'] ) ) return false;

        $body_content = json_decode( $response['body'] );
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
            'https://oauth2.googleapis.com/token',
            array(
                'client_id'     => self::GOOGLE_CLIENT_ID,
                'client_secret' => self::GOOGLE_CLIENT_SECRET,
                'refresh_token' => $this->drive['refresh_token'],
                'grant_type'    => 'refresh_token',
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

        $metadata = array(
            'name'    => $remote_file,
            'parents' => array( $this->folder_id ),
        );

        $boundary = uniqid();
        $delimiter = '----' . $boundary;

        fseek( $file, 0 );
        $file_content = fread( $file, $file_size );

        $body =
            "--$delimiter\r\n" .
            "Content-Type: application/json; charset=UTF-8\r\n\r\n" .
            json_encode( $metadata ) . "\r\n" .
            "--$delimiter\r\n" .
            "Content-Type: application/octet-stream\r\n\r\n" .
            $file_content . "\r\n" .
            "--$delimiter--\r\n";

        $response = WPDA_Remote_Call::post(
            'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart',
            $body,
            false,
            array(
                'Authorization' => 'Bearer ' . $this->drive['access_token'],
                'Content-Type'  => 'multipart/related; boundary=' . $delimiter,
            )
        );

        if ( ! isset( $response['body'] ) ) return false;

        $body_content = json_decode( $response['body'] );
        return isset( $body_content->id ) ? true : $response['body'];

    }

    public function cleanup( $query, $keep ) {

        if ( 'ALL' === $keep ) return true;

        $files = $this->search( $query );
        if ( 0 < count( $files ) ) {

            // Sort by created time (newest first)
            usort( $files, function( $a, $b ) {
                return strcmp( $b['createdTime'], $a['createdTime'] );
            });

            $keep_count = 0;
            foreach ( $files as $file ) {
                $keep_count++;
                if ( $keep_count > (int) $keep ) {
                    if ( ! $this->delete( $file['id'] ) ) {
                        WPDA::wpda_log_wp_error( "ERROR: Error deleting file {$file['id']}" );
                        WPDA::wpda_log_wp_error( $file );
                    }
                }
            }

            return true;
        }

        return false;

    }

    private function search( $query ) {

        if ( ! isset( $this->drive['access_token'] ) ) return false;

        $encoded_query = sprintf(
            "'%s' in parents and name contains '%s' and trashed = false",
            $this->folder_id,
            $query
        );

        $url = add_query_arg(
            array(
                'q'        => $encoded_query,
                'fields'   => 'files(id,name,createdTime)',
                'pageSize' => 1000,
            ),
            'https://www.googleapis.com/drive/v3/files'
        );

        $response = WPDA_Remote_Call::get(
            $url,
            array(
                'headers' => array(
                    'Authorization' => "Bearer {$this->drive['access_token']}",
                    'Accept: application/json',
                )
            )
        );

        if ( isset( $response['body'] ) ) {
            $body = json_decode( $response['body'], true );

            if ( isset( $body['error'] ) ) {
                WPDA::wpda_log_wp_error( $body['error'] );
            }

            if ( isset( $body['files'] ) ) {
                return $body['files'];
            }
        }

        return array();

    }

    private function delete( $file_id ) {

        if ( ! isset( $this->drive['access_token'] ) ) return false;

        return WPDA_Remote_Call::delete(
            'https://www.googleapis.com/drive/v3/files/' . $file_id,
            array(),
            array(
                'Authorization' => "Bearer {$this->drive['access_token']}",
            )
        );

    }


    public function get_folder_id( $folder_name ) {

        if ( ! isset( $this->drive['access_token'] ) ) return false;

        $url = 'https://www.googleapis.com/drive/v3/files';
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->drive['access_token'],
            ),
            'body' => array(
                'q' => "mimeType='application/vnd.google-apps.folder' and name='" . $folder_name . "' and trashed=false",
                'fields' => 'files(id, name)',
            ),
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode($response['body'], true);
        return !empty($body['files']) ? $body['files'][0] : false;
    }

    public function toggle( $enabled ) {

        $this->enabled = $enabled;
        $this->save();

    }



}
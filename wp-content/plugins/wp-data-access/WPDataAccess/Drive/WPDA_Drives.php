<?php

namespace WPDataAccess\Drive {

    use WPDataAccess\WPDA;

    class WPDA_Drives {

        const OPTION_DRIVES = 'wpda_drives';

        static private $drives = array();
        static private $is_initialized = false;

        public static function init() {

            if ( ! static::$is_initialized ) {
                $drives = get_option(self::OPTION_DRIVES);

                if (false !== $drives) {
                    self::$drives = $drives;
                }

                self::$is_initialized = true;
            }

        }

        public static function save() {

            self::init();

            update_option( self::OPTION_DRIVES, self::$drives );

        }

        public static function get_drive_names( $enabled = false ) {

            self::init();

            $drives = array_filter(
                array_keys( self::$drives ),
                function( $drive_name ) {
                    return self::$drives[ $drive_name ]['enabled'] === true;
                }
            );

            sort( $drives );

            return $drives;

        }

        public static function get_drives() {

            self::init();

            return self::$drives;

        }

        public static function get_drive( $drive_name, $enabled = false ) {

            self::init();

            if (
                isset(
                    self::$drives[ $drive_name ]['drive'],
                    self::$drives[ $drive_name ]['type'],
                    self::$drives[ $drive_name ]['enabled']
                ) &&
                (
                    self::$drives[ $drive_name ]['enabled'] === $enabled ||
                    false === $enabled
                )
            ) {
                switch ( self::$drives[ $drive_name ]['type'] ) {
                    case 'local':
                        return new WPDA_Local(
                            self::$drives[ $drive_name ]['drive'],
                            self::$drives[ $drive_name ]['enabled']
                        );
                    case 'ftp':
                        return new WPDA_Ftp(
                            'ftp',
                            self::$drives[ $drive_name ]['drive'],
                            self::$drives[ $drive_name ]['enabled']
                        );
                    case 'sftp':
                        return new WPDA_Sftp(
                            'sftp',
                            self::$drives[ $drive_name ]['drive'],
                            self::$drives[ $drive_name ]['enabled']
                        );
                    case 'dropbox':
                        return new WPDA_Dropbox(
                            self::$drives[ $drive_name ]['drive'],
                            self::$drives[ $drive_name ]['enabled']
                        );
                    case 'google_drive':
                        return new WPDA_Google_Drive(
                            self::$drives[ $drive_name ]['drive'],
                            self::$drives[ $drive_name ]['enabled']
                        );
                }
            }

            return false;

        }

        public static function set_drive( $drive_name, $drive, $drive_type, $enabled ) {

            self::init();

            // TODO: Check if name is already used

            self::$drives[ $drive_name ] = array(
                'drive'   => $drive,
                'type'    => $drive_type,
                'enabled' => $enabled,
            );

        }

        public static function delete_drive( $drive_name ) {

            self::init();

            unset( self::$drives[ $drive_name ] );

        }

    }

}
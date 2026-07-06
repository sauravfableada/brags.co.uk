<?php

namespace WPDataAccess\Drive {

    abstract class WPDA_Drive {

        protected $drive_type = null; // must be defined in constructor

        protected $drive_name = null;
        protected $drive = array();
        protected $enabled = false;

        public function __construct( $drive_name, $drive = array(), $enabled = false ) {

            $this->drive_name = $drive_name;
            $this->drive      = $drive;
            $this->enabled    = $enabled;

        }

        final public function save() {

            if (
                $this->drive_name !== null &&
                $this->drive_type !== null
            ) {
                WPDA_Drives::set_drive($this->drive_name, $this->drive, $this->drive_type, $this->enabled);
                WPDA_Drives::save();

                return true;
            } else {
                return false;
            }

        }

        public abstract function authorize( $authorization, $enabled = true );
        public abstract function refresh_token();
        public abstract function connect();
        public abstract function close();
        public abstract function upload_file( $local_file, $remote_file );
        public abstract function cleanup( $query, $keep );

    }

}
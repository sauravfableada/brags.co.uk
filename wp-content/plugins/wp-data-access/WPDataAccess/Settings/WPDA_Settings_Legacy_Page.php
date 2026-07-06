<?php

namespace WPDataAccess\Settings {

    abstract class WPDA_Settings_Legacy_Page {

        protected $page;

        public function __construct() {

            if ( isset( $_REQUEST['page'] ) ) {
                $this->page = sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ); // input var okay.
            } else {
                wp_die( __( 'ERROR: Wrong arguments [missing page argument]', 'wp-data-access' ) );
            }

        }

        abstract public function show();

    }

}
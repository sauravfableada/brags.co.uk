<?php

namespace WPDataAccess\Utilities {

    class WPDA_App_Localization {

        const OPTIONS_LOCALIZATION = 'wpda_app_localization';

        public static function get() {

            return get_option( self::OPTIONS_LOCALIZATION );

        }

        public static function set( $localizations) {

            update_option( self::OPTIONS_LOCALIZATION, $localizations );

        }

    }

}
<?php

namespace WPDataAccess\API;

use WPDataAccess\WPDA;
class WPDA_Plugin extends WPDA_API_Core {
    public function register_rest_routes() {
        register_rest_route( WPDA_API::WPDA_NAMESPACE, 'info', array(
            'methods'             => array(\WP_REST_Server::READABLE),
            'callback'            => function () {
                $license = 'free';
                return $this->WPDA_Rest_Response( '', array(
                    'license' => $license,
                    'version' => WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
                    'client'  => WPDA::get_option( WPDA::OPTION_WPDA_CLIENT_VERSION ),
                ) );
            },
            'permission_callback' => '__return_true',
        ) );
    }

}

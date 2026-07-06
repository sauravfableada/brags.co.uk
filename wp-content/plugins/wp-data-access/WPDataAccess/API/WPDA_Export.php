<?php

namespace WPDataAccess\API {

    use WPDataAccess\Drive\WPDA_Drives;
    use WPDataAccess\Utilities\WPDA_Export_Scheduler;
    use WPDataAccess\Utilities\WPDA_Mail;
    use WPDataAccess\WPDA;

    class WPDA_Export extends WPDA_API_Core {

        public function register_rest_routes() {

            register_rest_route(
                WPDA_API::WPDA_NAMESPACE,
                'export/cron/schedules',
                array(
                    'methods'             => array( 'POST' ),
                    'callback'            => array( $this, 'cron_schedules' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(),
                )
            );

            register_rest_route(
                WPDA_API::WPDA_NAMESPACE,
                'export/cron/add',
                array(
                    'methods'             => array( 'POST' ),
                    'callback'            => array( $this, 'cron_add' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'name'     => $this->get_param( 'name' ),
                        'params'   => $this->get_param( 'params' ),
                        'start'    => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'description'       => __( 'Start date/time', 'wp-data-access' ),
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => 'rest_validate_request_arg',
                        ),
                        'interval' => array(
                            'required'          => true,
                            'type'              => 'string',
                            'description'       => __( 'Recurrence', 'wp-data-access' ),
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => 'rest_validate_request_arg',
                        ),
                    ),
                )
            );

            register_rest_route(
                WPDA_API::WPDA_NAMESPACE,
                'export/cron/delete',
                array(
                    'methods'             => array( 'POST' ),
                    'callback'            => array( $this, 'cron_delete' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'name'   => $this->get_param( 'name' ),
                    ),
                )
            );

        }

        public function cron_schedules( $request ) {

            if ( ! $this->current_user_can_access() ) {
                return $this->unauthorized();
            }

            if ( ! $this->current_user_token_valid( $request ) ) {
                return $this->invalid_nonce();
            }

            return $this->WPDA_Rest_Response(
                array(
                    'cron'      => WPDA_Export_Scheduler::wpda_cron_events(),
                    'schedules' => wp_get_schedules(),
                    'drives'    => WPDA_Drives::get_drive_names( true ),
                    'mail'      => WPDA_Mail::mail_activated(),
                )
            );

        }

        public function cron_add( $request ) {

            if ( ! $this->current_user_can_access() ) {
                return $this->unauthorized();
            }

            if ( ! $this->current_user_token_valid( $request ) ) {
                return $this->invalid_nonce();
            }

            $name     = $request->get_param('name');
            $params   = $request->get_param('params');
            $start    = $request->get_param('start');
            $interval = $request->get_param('interval');

            if ( false !== WPDA_Export_Scheduler::get_export( $name ) ) {
                return new \WP_Error(
                    'error',
                    'An export with this name is already scheduled',
                    array('status' => 403)
                );
            }

            $args = array(
                'args' => array(
                    'name' => $name,
                    'params' => $params,
                )
            );

            if ('nonrepeating' === $interval) {
                $error = wp_schedule_single_event(
                    $start,
                    WPDA_Export_Scheduler::SCHEDULER_HOOK_NAME,
                    $args
                );
            } else {
                $error = wp_schedule_event(
                    $start,
                    $interval,
                    WPDA_Export_Scheduler::SCHEDULER_HOOK_NAME,
                    $args
                );
            }

            if (is_wp_error($error)) {
                return new \WP_Error(
                    'error',
                    $error->get_error_message(),
                    array('status' => 403)
                );
            } else {
                return $this->WPDA_Rest_Response(
                    'Successfully scheduled export'
                );
            }

        }

        public function cron_delete( $request ) {

            if ( ! $this->current_user_can_access() ) {
                return $this->unauthorized();
            }

            if ( ! $this->current_user_token_valid( $request ) ) {
                return $this->invalid_nonce();
            }

            $name = $request->get_param('name');
            $jobs = WPDA_Export_Scheduler::get_export( $name );

            if ( false === $jobs ) {
                return new \WP_Error(
                    'error',
                    'Export with name "' . $name . '" is not found',
                    array('status' => 403)
                );
            } else {
                $error = wp_unschedule_event(
                    $jobs['time_stamp'],
                    WPDA_Export_Scheduler::SCHEDULER_HOOK_NAME,
                    $jobs['args'],
                    true
                );

                if ( is_wp_error( $error ) ) {
                    return new \WP_Error(
                        'error',
                        $error->get_error_message(),
                        array('status' => 403)
                    );
                }

                return $this->WPDA_Rest_Response(
                    'Successfully deleted export',
                    null,
                    $error
                );
            }

        }

    }

}
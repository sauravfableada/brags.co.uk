<?php

if ( class_exists( 'WC_REST_Subscriptions_V2_Controller' ) ) {
    class_alias( 'WC_REST_Subscriptions_V2_Controller', 'BaseSubscriptionController' );
} else {
    class_alias( 'WC_REST_Subscriptions_Controller', 'BaseSubscriptionController' );
}

class Dokan_Vendor_Subscription_Product_Rest_Api extends BaseSubscriptionController {

    /**
     * Endpoint namespace.
     *
     * @since 4.0.0
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * @since 4.0.0
     *
     * @var string Route base.
     */
    protected $rest_base = 'product-subscriptions';

    /**
     * Register the routes for the subscriptions endpoint.
     *
     * @since 4.0.0
     */
    public function register_routes() {
        parent::register_routes();

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/orders/(?P<id>\d+)/grant-download-access',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'grant_access' ],
                    'permission_callback' => [ $this, 'create_item_permissions_check' ],
                    'args'                => [
                        'id'          => [
                            'required'          => true,
                            'type'              => 'integer',
                            'validate_callback' => function ( $param ) {
                                return is_numeric( $param ) && $param > 0;
                            },
                        ],
                        'product_ids' => [
                            'required'          => true,
                            'type'              => 'array',
                            'items'             => [
                                'type' => 'integer',
                            ],
                            'validate_callback' => function ( $param ) {
                                return is_array( $param ) && ! empty( $param );
                            },
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/schedule',
            [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_schedule' ],
                    'permission_callback' => [ $this, 'check_update_scheduler_permission' ],
                    'args'                => [
                        'id'               => [
                            'required'          => true,
                            'type'              => 'integer',
                            'validate_callback' => function ( $param ) {
                                return is_numeric( $param ) && $param > 0;
                            },
                        ],
                        'billing_interval' => [
                            'required'          => false,
                            'type'              => 'integer',
                            'validate_callback' => function ( $param ) {
                                return is_numeric( $param ) && $param > 0;
                            },
                        ],
                        'billing_period'   => [
                            'required' => false,
                            'type'     => 'string',
                            'enum'     => [ 'day', 'week', 'month', 'year' ],
                        ],
                        'dates'            => [
                            'required'          => false,
                            'type'              => 'object',
                            'sanitize_callback' => function ( $dates ) {
                                return array_map( 'sanitize_text_field', $dates );
                            },
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Perform an action with vendor permission check.
     *
     * @since 4.0.0
     *
     * @param callable $action The action to perform.
     *
     * @return mixed The result of the action.
     */
    private function perform_vendor_action( callable $action ) {
        add_filter( 'woocommerce_rest_check_permissions', [ $this, 'check_vendor_permission' ] );
        return $action();
    }

    /**
     * Check if the current user has vendor permissions.
     *
     * @since 4.0.0
     *
     * @return bool
     */
    public function check_vendor_permission(): bool {
        return dokan_is_user_seller( dokan_get_current_user_id() );
    }

    /**
     * Check if a given request has access to create an item.
     *
     * @since 4.0.0
     *
     * @param  WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function create_item_permissions_check( $request ) {
        $error = $this->check_seller_is_the_order_owner( $request );
        if ( is_wp_error( $error ) ) {
            return $error;
        }

        return $this->perform_vendor_action(
            function () use ( $request ) {
                return parent::create_item_permissions_check( $request );
            }
        );
    }

    /**
     * Check if a given request has access to update an item.
     *
     * @since 4.0.0
     *
     * @param  WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function update_item_permissions_check( $request ) {
        $error = $this->check_seller_is_the_order_owner( $request );
        if ( is_wp_error( $error ) ) {
            return $error;
        }

        return $this->perform_vendor_action(
            function () use ( $request ) {
                return parent::update_item_permissions_check( $request );
            }
        );
    }

    /**
     * Check if user has permission
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request
     *
     * @return bool|WP_Error
     */
    public function check_update_scheduler_permission( $request ) {
        $error = $this->check_seller_is_the_order_owner( $request );
        if ( is_wp_error( $error ) ) {
            return $error;
        }

        if ( ! current_user_can( 'dokandar' ) ) {
            return new WP_Error(
                'dokan_rest_unauthorized',
                __( 'You are not authorized to update subscription schedule.', 'dokan' ),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        $subscription_id = $request->get_param( 'id' );
        $subscription   = wcs_get_subscription( $subscription_id );

        if ( ! $subscription ) {
            return new WP_Error(
                'dokan_rest_invalid_subscription',
                __( 'Invalid subscription ID.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        return true;
    }

    /**
     * Update subscription schedule
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function update_schedule( $request ) {
        $subscription_id = $request->get_param( 'id' );
        $subscription   = wcs_get_subscription( $subscription_id );
        $dates_to_update = [];

        // Update billing interval if provided
        if ( $request->has_param( 'billing_interval' ) ) {
            $subscription->set_billing_interval( $request->get_param( 'billing_interval' ) );
        }

        // Update billing period if provided
        if ( $request->has_param( 'billing_period' ) ) {
            $subscription->set_billing_period( $request->get_param( 'billing_period' ) );
        }

        // Process dates
        $provided_dates = $request->get_param( 'dates' );
        if ( empty( $provided_dates ) ) {
            $provided_dates = [];
        }

        // Get all available date types for the subscription
        $date_types = wcs_get_subscription_date_types();

        foreach ( $date_types as $date_type => $date_label ) {
            $date_key = wcs_normalise_date_type_key( $date_type );

            if ( 'last_order_date_created' === $date_key ) {
                continue;
            }

            $utc_timestamp_key = $date_type . '_timestamp_utc';

            // A subscription needs a created date, even if it wasn't set or is empty
            if ( 'date_created' === $date_key && empty( $provided_dates[ $utc_timestamp_key ] ) ) {
                $datetime_timestamp = current_time( 'timestamp', true ); // phpcs:ignore
            } elseif ( isset( $provided_dates[ $utc_timestamp_key ] ) ) {
                $datetime_timestamp = $provided_dates[ $utc_timestamp_key ];
            } else { // No date to set
                continue;
            }
            $dates_to_update[ $date_key ] = $datetime_timestamp ? date( 'Y-m-d H:i:s', $datetime_timestamp ) : '';
        }

        try {
            if ( ! empty( $dates_to_update ) ) {
                $subscription->update_dates( $dates_to_update, 'gmt' );
            }

            wp_cache_delete( $subscription_id, 'posts' );
            $subscription->save_meta_data();
            $subscription->save_dates();
            $subscription->save();

            $all_dates = [];

            foreach ( $date_types as $date_key => $date_label ) {
                $date = $subscription->get_date( $date_key );
                if ( ! empty( $date ) ) {
                    $all_dates[ $date_key ] = [
                        'date' => $date,
                        'label' => $date_label,
                    ];
                }
            }

            return rest_ensure_response(
                [
                    'success'      => true,
                    'message'      => __( 'Subscription schedule updated successfully.', 'dokan' ),
                    'subscription' => [
                        'id'               => $subscription->get_id(),
                        'billing_interval' => $subscription->get_billing_interval(),
                        'billing_period'   => $subscription->get_billing_period(),
                        'dates'            => $all_dates,
                    ],
                ]
            );
        } catch ( Exception $e ) {
            return new WP_Error(
                'dokan_rest_subscription_update_error',
                $e->getMessage(),
                [ 'status' => 400 ]
            );
        }
    }

    /**
     * Get a collection of posts.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {
        return $this->perform_vendor_action(
            function () use ( $request ) {
                return parent::get_items( $request );
            }
        );
    }

    /**
     * Prepares the object for the REST response.
     *
     * @since  4.0.0
     *
     * @param  WC_Data         $data_object Object data.
     * @param  WP_REST_Request $request     Request object.
     *
     * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
     */
    public function prepare_object_for_response( $data_object, $request ) {
        $response                                    = parent::prepare_object_for_response( $data_object, $request );
        $response->data['display_start_date']        = $data_object->get_date_to_display( 'start_date' );
        $response->data['display_next_payment_date'] = $data_object->get_date_to_display( 'next_payment_date' );
        $response->data['display_end_date']          = $data_object->get_date_to_display( 'end_date' );

        return apply_filters( 'dokan_rest_prepare_vendor_product_subscription_object', $response, $data_object, $request );
    }

    /**
     * Update a single post.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function update_item( $request ) {
        $response = parent::update_item( $request );

        return rest_ensure_response( $this->process_item_response( $response ) );
    }

    /**
     * Get a collection of posts.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_item( $request ) {
        $response = parent::get_item( $request );

        return rest_ensure_response( $this->process_item_response( $response ) );
    }

    /**
     * @since 4.0.0
     *
     * @param WP_REST_Response $response
     *
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    private function process_item_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response = $response->get_data();

        $subscription = wcs_get_subscription( $response['id'] );

        $date_types = [];
        $wcs_get_subscription_date_types = wcs_get_subscription_date_types();
        unset( $wcs_get_subscription_date_types['trial_end'] ); //Temp

        foreach ( $wcs_get_subscription_date_types as $date_key => $date_label ) {
            if ( false === wcs_display_date_type( $date_key, $subscription ) ) {
                continue;
            }

            $data = [];
            $internal_date_key = wcs_normalise_date_type_key( $date_key );

            $data['internal_date_key'] = $internal_date_key;
            $data['date_label'] = $date_label;
            $data['can_date_be_updated'] = $subscription->can_date_be_updated( $internal_date_key );
            $data['get_date_to_display'] = $subscription->get_date_to_display( $internal_date_key );
            $data['date_key'] = $date_key;

            $data['date_site'] = '';
            $data[ $date_key . '_timestamp_utc' ] = '';

            $internal_date_key_time = $subscription->get_time( $internal_date_key, 'gmt' );
            if ( ! empty( $internal_date_key_time ) ) {
                $data['date_site'] = dokan_current_datetime()->setTimestamp( $internal_date_key_time )->format( 'Y-m-d\TH:i:s' ); // we are passing the date in this specific format so that js can parse in th front end  do not modify this format.
                $data[ $date_key . '_timestamp_utc' ] = dokan_current_datetime()->setTimestamp( $internal_date_key_time )->getTimestamp(); // we are passing the date in this specific format so that js can parse in th front end  do not modify this format.
            }

            $date_types[] = $data;
        }

        $response['recurring_string'] = sprintf( '%s %s', esc_html( wcs_get_subscription_period_interval_strings( $subscription->get_billing_interval() ) ), esc_html( wcs_get_subscription_period_strings( 1, $subscription->get_billing_period() ) ) );
        $response['settings'] = [
            'can_date_be_updated_next_payment' => $subscription->can_date_be_updated( 'next_payment' ),
            'period_interval_strings' => wcs_get_subscription_period_interval_strings(),
            'period_strings' => wcs_get_subscription_period_strings(),
            'date_types' => $date_types,
        ];

        return $response;
    }

    /**
     * Gets the /subscriptions/[id]/orders response.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request            $request  The request object.
     *
     * @return WP_Error|WP_REST_Response $response The response or an error if one occurs.
     */
    public function get_subscription_orders( $request ) {
        return $this->perform_vendor_action(
            function () use ( $request ) {
                return parent::get_subscription_orders( $request );
            }
        );
    }

    /**
     * Check if a given request has access to read items.
     *
     * @since 4.0.0
     *
     * @param  WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check( $request ) {
        $error = $this->check_seller_is_the_order_owner( $request );
        if ( is_wp_error( $error ) ) {
            return $error;
        }

        // phpcs:ignore WordPress.WP.Capabilities.Unknown
        if ( current_user_can( dokan_admin_menu_capability() ) || current_user_can( 'dokandar' ) ) {
            return true;
        }

        return new WP_Error(
            'dokan_pro_permission_failure',
            __( 'You are not allowed to do this action.', 'dokan' ),
            [
                'status' => rest_authorization_required_code(),
			]
        );
    }

    /**
     * Check if a given request has access to read an item.
     *
     * @since 4.0.0
     *
     * @param  WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check( $request ) {
        $error = $this->check_seller_is_the_order_owner( $request );
        if ( is_wp_error( $error ) ) {
            return $error;
        }

        return $this->perform_vendor_action(
            function () use ( $request ) {
                return $this->get_items_permissions_check( $request );
            }
        );
    }

    /**
     * Add vendor id in the metq query.
     *
     * @since 4.0.0
     *
     * @param \WP_REST_Request $request
     *
     * @return array
     */
    protected function prepare_objects_query( $request ) {
        $query_args = parent::prepare_objects_query( $request );

        $meta_query = [];

        if ( ! empty( $query_args['meta_query'] ) ) {
            $meta_query = $query_args['meta_query'];
        }

        $meta_query[] = [
            'key'     => '_dokan_vendor_id',
            'value'   => dokan_get_current_user_id(),
            'compare' => '==',
        ];

        $query_args['meta_query'] = $meta_query; // phpcs:ignore

        return apply_filters( 'dokan_prepare_vendor_product_subscription_object_query', $query_args, $request );
    }

    /**
     * Grant download access
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function grant_access( $request ) {
        $order_id     = $request->get_param( 'id' );
        $product_ids  = $request->get_param( 'product_ids' );
        $order        = dokan()->order->get( $order_id );
        $granted_files = [];
        $errors       = [];

        if ( ! $order->get_billing_email() ) {
            return new WP_Error(
                'dokan_rest_invalid_order_email',
                __( 'Order does not have a billing email.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        foreach ( $product_ids as $product_id ) {
            $product = dokan()->product->get( $product_id );

            if ( ! $product ) {
                // translator: %d is the product id
                $errors[] = sprintf( __( 'Product #%d not found', 'dokan' ), $product_id );
                continue;
            }

            $files = $product->get_downloads();

            if ( ! $files ) {
                // translator: %d is the product id
                $errors[] = sprintf( __( 'Product #%d has no downloadable files', 'dokan' ), $product_id );
                continue;
            }

            $product_files = [];
            foreach ( $files as $download_id => $file ) {
                $inserted_id = wc_downloadable_file_permission( $download_id, $product_id, $order );

                if ( $inserted_id ) {
                    $download        = new WC_Customer_Download( $inserted_id );
                    $product_files[] = [
                        'download_id'         => $download_id,
                        'product_id'          => $product_id,
                        'file_name'           => $file->get_name(),
                        'downloads_remaining' => $download->get_downloads_remaining(),
                        'access_expires'      => $download->get_access_expires(),
                    ];
                } else {
                    // translator: %1$s is the file name, %2$d is the product id
                    $errors[] = sprintf( __( 'Failed to grant access for file %1$s in product #%2$d', 'dokan' ), $file->get_name(), $product_id );
                }
            }

            if ( ! empty( $product_files ) ) {
                $granted_files[ $product_id ] = $product_files;
            }
        }

        $response_data = [
            'granted_files' => $granted_files,
        ];

        if ( ! empty( $errors ) ) {
            $response_data['errors'] = $errors;
        }

        return rest_ensure_response( $response_data );
    }

    /**
     * Check if a given request has access to update a order note.
     *
     * @since 4.0.0
     *
     * @param $request
     *
     * @return true|\WP_Error
     */
    public function check_seller_is_the_order_owner( $request ) {
        $order = $this->get_object( (int) $request['id'] );
        $seller_id = dokan_get_current_user_id();

        if ( $order && (int) $order->get_meta( '_dokan_vendor_id', true ) !== $seller_id ) {
            return new WP_Error(
                'dokan_pro_permission_failure',
                __( 'Unauthorized order access', 'dokan' ),
                [
                    'status' => rest_authorization_required_code(),
                ]
            );
        }

        return true;
    }
}

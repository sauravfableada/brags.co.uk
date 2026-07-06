<?php

namespace WeDevs\DokanPro\Modules\MangoPay\REST;

use WeDevs\Dokan\Abstracts\DokanRESTController;
use WeDevs\DokanPro\Modules\MangoPay\Processor\Card;
use WeDevs\DokanPro\Modules\MangoPay\Processor\User;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Dokan mangopay block rest controller class.
 *
 * @since 4.0.0
 *
 * @class AdminStoreSupportTicketController
 *
 * @extends DokanRESTAdminController
 */
class BlockRestController extends DokanRESTController {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Route name
     *
     * @var string
     */
    protected $base = 'mangopay/block';

    /**
     * Register all routes related with logs
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, '/' . $this->base . '/registered/cards', [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_registered_cards' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_block_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_register_card' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_register_card' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => [
                        'card_id'               => [
                            'description'       => __( 'Card id', 'dokan' ),
                            'type'              => 'string',
                            'context'           => [ 'view', 'edit' ],
                            'default'           => '',
                            'validate_callback' => 'rest_validate_request_arg',
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'reg_data'               => [
                            'description'       => __( 'Registered card data', 'dokan' ),
                            'type'              => 'string',
                            'context'           => [ 'view', 'edit' ],
                            'default'           => '',
                            'validate_callback' => 'rest_validate_request_arg',
                            'sanitize_callback' => 'sanitize_text_field',
                        ]
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_card' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => [
                        'card_id'               => [
                            'description'       => __( 'Card id', 'dokan' ),
                            'type'              => 'string',
                            'context'           => [ 'view', 'edit' ],
                            'default'           => '',
                            'validate_callback' => 'rest_validate_request_arg',
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_item_schema' ]
            ]
        );
    }

    /**
     * @since 4.0.0
     *
     * @param WP_REST_Request $request
     *
     * @return array|WP_Error
     */
    public function get_registered_cards( $request ) {
        $user_id = $request->get_param( 'user_id' );

        if ( empty( $user_id ) ) {
            return new WP_Error(
                'mangopay-block-cards-error',
                __( 'User id is required, but got invalid user id', 'dokan' )
            );
        }

        return User::get_cards( $user_id );
    }

    /**
     * Create a registration record for a card.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function create_register_card( $request ) {
        $user_id        = $request->get_param( 'user_id' );
        $order_currency = get_woocommerce_currency();
        $card_type      = $request->get_param( 'card_type' );
        $nickname       = $request->get_param( 'preauth_ccnickname' );

        $registration = Card::register( $user_id, $order_currency, $card_type, $nickname );

        if ( ! $registration['success'] ) {
            return new WP_Error(
                'mangopay-block-cards-error',
                $registration['message']
            );
        }

        $item = $this->prepare_item_for_response( $registration, $request );
        return $this->prepare_response_for_collection( $item );
    }

    /**
     * Updates card registration after saving card data.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function update_register_card( $request ) {
        $card_id  = $request->get_param( 'card_id' );
        $reg_data = $request->get_param( 'reg_data' );
        $card     = Card::update( $card_id, $reg_data );

        if ( ! $card['success'] ) {
            return new WP_Error(
                'mangopay-block-cards-error',
                $card['message']
            );
        }

        return rest_ensure_response( $card['response'] );
    }

    /**
     * Deactivate card.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function delete_card( $request ) {
        $card_id  = $request->get_param( 'card_id' );
        $result  = Card::deactivate( $card_id );

        if ( ! $result['success'] ) {
            return new WP_Error(
                'mangopay-block-cards-error',
                __( 'Failed to deactivate card', 'dokan' )
            );
        }

        return rest_ensure_response( __( 'Card deactivated', 'dokan' ) );
    }

    public function prepare_item_for_response ( $item, $request ) {
        $data = [
            'CardRegistrationId'  => $item['response']->Id,
            'PreregistrationData' => $item['response']->PreregistrationData,
            'AccessKey'           => $item['response']->AccessKey,
            'CardRegistrationURL' => $item['response']->CardRegistrationURL,
            'UserId'              => $item['response']->UserId
        ];

        return rest_ensure_response( $data );
    }

    /**
     * Checks if a given request has access to get items.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return bool True if the request has read access, WP_Error object otherwise.
     */
    public function check_permission( $request ) {
        return is_user_logged_in();
    }

    /**
     * Get collection params.
     *
     * @since 3.7.14
     *
     * @return array
     */
    public function get_block_collection_params() {
        $context            = $this->get_context_param();
        $context['default'] = 'view';

        return [
            'context'      => $context,

            'user_id'    => [
                'description'       => __( 'Customer user id', 'dokan' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => get_current_user_id(),
                'validate_callback' => 'rest_validate_request_arg',
                'required'          => true,
            ],
        ];
    }

    /**
     * Retrieves an array of endpoint arguments from the item schema for the controller.
     *
     * @since 4.7.0
     *
     * @param string $method Optional. HTTP method of the request. The arguments for `CREATABLE` requests are
     *                       checked for required values and may fall-back to a given default, this is not done
     *                       on `EDITABLE` requests. Default WP_REST_Server::CREATABLE.
     * @return array Endpoint arguments.
     */
    public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
        return rest_get_endpoint_args_for_schema( $this->get_item_schema(), $method );
    }

    /**
     * Get the item schema, conforming to JSON Schema.
     *
     * @since 3.7.14
     *
     * @return array
     */
    public function get_item_schema() {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'Mango pay block Schema',
            'type'       => 'object',
            'properties' => [
                'user_id'               => [
                    'description'       => __( 'Customer user id', 'dokan' ),
                    'type'              => 'integer',
                    'context'           => [ 'view', 'edit' ],
                    'default'           => get_current_user_id(),
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'absint',
                ],
                'card_type'        => [
                    'description'       => __( 'Card type', 'dokan' ),
                    'type'              => 'string',
                    'context'           => [ 'view', 'edit' ],
                    'default'           => 'CB_VISA_MASTERCARD',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'preauth_ccnickname'    => [
                    'description'       => __( 'Name', 'dokan' ),
                    'type'              => 'string',
                    'context'           => [ 'view', 'edit' ],
                    'default'           => '',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ];

        return $this->add_additional_fields_schema( $schema );
    }
}

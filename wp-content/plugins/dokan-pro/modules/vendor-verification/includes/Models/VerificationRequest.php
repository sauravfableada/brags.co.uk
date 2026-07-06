<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\Models;

use DateTimeImmutable;
use Exception;
use stdClass;
use WeDevs\Dokan\Cache;

defined( 'ABSPATH' ) || exit();

/**
 * Vendor verification request.
 *
 * @since 3.11.1
 */
class VerificationRequest {

    /**
     * Statuses.
     *
     * @since 3.11.1
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * @since 3.11.1
     *
     * @var int $id The id of the verification request
     */
    private $id = 0;

    /**
     * @since 3.11.1
     *
     * @var int $vendor_id The id of the vendor.
     */
    private $vendor_id = 0;

    /**
     * @since 3.11.1
     *
     * @var int $method_id The id of the method.
     */
    private $method_id = 0;

    /**
     * @since 3.11.1
     *
     * @var string $status Status of the request.
     */
    private $status = self::STATUS_PENDING;

    /**
     * @since 3.11.1
     *
     * @var int $checked_by The document checker id.
     */
    private $checked_by = 0;

    /**
     * @since 3.11.1
     *
     * @var array $additional_info Additional information related to the request.
     */
    private $additional_info = [];

    /**
     * @since 3.11.1
     *
     * @var array $documents The list of the documents.
     */
    private $documents = [];

    /**
     * @since 3.11.1
     *
     * @var string $note Notes.
     */
    private $note = '';

    /**
     * @since 3.11.1
     *
     * @var DateTimeImmutable $created_at Creation time.
     */
    private $created_at;

    /**
     * @since 3.11.1
     *
     * @var DateTimeImmutable $updated_at Update time.
     */
    private $updated_at;

    /**
     * @since 3.11.1
     *
     * @var string $table_name Table name.
     */
    private $table_name = '';

    /**
     * VerificationRequest constructor.
     *
     * @since 3.11.1
     *
     * @param int $id ID.
     */
    public function __construct( int $id = 0 ) {
        $this->set_table_name();
        if ( $id ) {
            try {
                $this->set_id( $id );
                $this->get();
            } catch ( Exception $exception ) {
                $this->set_id( 0 );
            }
        }
    }

    /**
     * Get the cache key for this method.
     *
     * @since 3.11.1
     *
     * @return string
     */
    public function get_cache_key(): string {
        return 'dokan_vendor_verification_request_' . $this->get_id();
    }

    /**
     * Get the cache group for this method.
     *
     * @since 3.11.1
     *
     * @return string
     */
    public function get_cache_group(): string {
        return 'dokan_vendor_verification_requests';
    }

    /**
     * Get table name.
     *
     * @since 3.11.1
     *
     * @return string
     */
    public function get_table_name(): string {
        return $this->table_name;
    }

    /**
     * Set Table name.
     *
     * @since 3.11.1
     *
     * @return void
     */
    private function set_table_name(): void {
        global $wpdb;

        $this->table_name = $wpdb->prefix . 'dokan_vendor_verification_requests';

    }

    /**
     * Get the id.
     *
     * @since 3.11.1
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Set the id.
     *
     * @since 3.11.1
     *
     * @param int $id ID.
     *
     * @return VerificationRequest
     */
    public function set_id( int $id ): VerificationRequest {
        $this->id = $id;

        return $this;
    }

    /**
     * Get vendor ID.
     *
     * @since 3.11.1
     *
     * @return int
     */
    public function get_vendor_id(): int {
        return $this->vendor_id;
    }

    /**
     * Set vendor ID.
     *
     * @since 3.11.1
     *
     * @param int $vendor_id Vendor ID.
     *
     * @return VerificationRequest
     */
    public function set_vendor_id( int $vendor_id ): VerificationRequest {
        $this->vendor_id = $vendor_id;

        return $this;
    }

    /**
     * Get Verification Method ID.
     *
     * @since 3.11.1
     *
     * @return int
     */
    public function get_method_id(): int {
        return $this->method_id;
    }

    /**
     * Set Verification Method ID.
     *
     * @since 3.11.1
     *
     * @param int $method_id Verification Method ID.
     *
     * @return VerificationRequest
     */
    public function set_method_id( int $method_id ): VerificationRequest {
        $this->method_id = $method_id;

        return $this;
    }

    /**
     * Get Status.
     *
     * @since 3.11.1
     *
     * @return string
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Set Status.
     *
     * @since 3.11.1
     *
     * @param string $status Status.
     *
     * @return VerificationRequest
     */
    public function set_status( string $status ): VerificationRequest {
        $this->status = in_array( $status, [
            self::STATUS_APPROVED,
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED,
            self::STATUS_PENDING,
        ], true ) ? $status : self::STATUS_PENDING;

        return $this;
    }

    /**
     * Get Checked By ID
     *
     * @since 3.11.1
     *
     * @return int
     */
    public function get_checked_by(): int {
        return $this->checked_by;
    }

    /**
     * Set Checked by.
     *
     * @since 3.11.1
     *
     * @param int $checked_by Checked by user ID.
     *
     * @return VerificationRequest
     */
    public function set_checked_by( int $checked_by ): VerificationRequest {
        $this->checked_by = $checked_by;

        return $this;
    }

    /**
     * Get Additional Information.
     *
     * @since 3.11.1
     *
     * @return array
     */
    public function get_additional_info(): array {
        return $this->additional_info;
    }

    /**
     * Set additional information.
     *
     * @since 3.11.1
     *
     * @param array $additional_info Additional Information.
     *
     * @return VerificationRequest
     */
    public function set_additional_info( array $additional_info ): VerificationRequest {
        $this->additional_info = dokan_is_empty( $additional_info ) ? [] : $additional_info;

        return $this;
    }

    /**
     * Get Documents list.
     *
     * @since 3.11.1
     *
     * @return array
     */
    public function get_documents(): array {
        return $this->documents;
    }

    /**
     * Set Document List.
     *
     * @since 3.11.1
     *
     * @param array $documents Documents.
     *
     * @return VerificationRequest
     */
    public function set_documents( array $documents ): VerificationRequest {
        $this->documents = dokan_is_empty( $documents ) ? [] : $documents;

        return $this;
    }

    /**
     * Get a Note.
     *
     * @since 3.11.1
     *
     * @return string
     */
    public function get_note(): string {
        return $this->note;
    }

    /**
     * Set a note.
     *
     * @since 3.11.1
     *
     * @param string $note Note Text.
     *
     * @return VerificationRequest
     */
    public function set_note( string $note ): VerificationRequest {
        $this->note = wp_kses_post( $note );

        return $this;
    }

    /**
     * Get Created At time.
     *
     * @since 3.11.1
     *
     * @return DateTimeImmutable
     */
    public function get_created_at(): DateTimeImmutable {
        return $this->created_at;
    }

    /**
     * Set Created At time.
     *
     * @since 3.11.1
     *
     * @param DateTimeImmutable $created_at DateTime.
     *
     * @return VerificationRequest
     */
    public function set_created_at( DateTimeImmutable $created_at ): VerificationRequest {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * Get updated at time.
     *
     * @since 3.11.1
     *
     * @return DateTimeImmutable
     */
    public function get_updated_at(): DateTimeImmutable {
        return $this->updated_at;
    }

    /**
     * Set updated at time.
     *
     * @since 3.11.1
     *
     * @param DateTimeImmutable $updated_at DateTimeInterface.
     *
     * @return VerificationRequest
     */
    public function set_updated_at( DateTimeImmutable $updated_at ): VerificationRequest {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * Get All statuses.
     *
     * @since 3.11.1
     *
     * @return array
     */
    public function get_statuses(): array {
        $status = [
            self::STATUS_PENDING   => __( 'Pending', 'dokan' ),
            self::STATUS_APPROVED  => __( 'Approved', 'dokan' ),
            self::STATUS_REJECTED  => __( 'Rejected', 'dokan' ),
            self::STATUS_CANCELLED => __( 'Cancelled', 'dokan' ),
        ];

        return apply_filters( 'dokan_pro_vendor_verification_request_statuses', $status );
    }

    /**
     * Get status Title.
     *
     * @since 3.11.1
     *
     * @param string $status Status.
     *
     * @return string
     */
    public function get_status_title( string $status = '' ): string {
        $title    = '';
        $statuses = $this->get_statuses();

        if ( empty( $status ) ) {
            $status = $this->get_status();
        }

        return $statuses[ $status ] ?? $status;
    }

    /**
     * Create verification request.
     *
     * @since 3.11.1
     *
     * @throws Exception When method ID or Vendor ID is invalid.
     * @return VerificationRequest
     */
    public function create(): VerificationRequest {
        global $wpdb;

        if ( empty( $this->get_method_id() ) ) {
            throw new Exception( __( 'Verification Method ID is required.', 'dokan' ) );
        }

        if ( empty( $this->get_vendor_id() ) ) {
            throw new Exception( __( 'Vendor ID is required.', 'dokan' ) );
        }

        $inserted = $wpdb->insert(
            $this->get_table_name(),
            [
                'vendor_id'       => $this->get_vendor_id(),
                'method_id'       => $this->get_method_id(),
                'status'          => $this->get_status(),
                'checked_by'      => $this->get_checked_by(),
                'additional_info' => maybe_serialize( $this->get_additional_info() ),
                'documents'       => maybe_serialize( $this->get_documents() ),
                'note'            => $this->get_note(),
                'created_at'      => current_datetime()->getTimestamp(),
                'updated_at'      => current_datetime()->getTimestamp(),
            ],
            [
                '%d',
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
            ]
        );

        if ( false === $inserted ) {
            $error_message = sprintf(
                __( 'Error while creating new verification request.', 'dokan' ) . '%s',
                current_user_can( dokan_admin_menu_capability() ) ? sprintf( 'Error: %s', $wpdb->last_error ) : ''
            );
            throw new Exception( $error_message );
        }

        $this->set_id( $wpdb->insert_id );

        do_action( 'dokan_pro_vendor_verification_request_created', $this->get_id() );

        return $this->get();
    }

    /**
     * Get verification request.
     *
     * @since 3.11.1
     *
     * @throws Exception
     * @return VerificationRequest
     */
    public function get(): VerificationRequest {
        global $wpdb;

        if ( ! $this->get_id() ) {
            throw new Exception( __( 'ID not provided.', 'dokan' ) );
        }

        $request = wp_cache_get( $this->get_cache_key(), $this->get_cache_group() );

        if ( false === $request ) {
            $request = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->get_table_name()} WHERE `id` = %d",
                    [
                        $this->get_id(),
                    ]
                )
            );

            Cache::set( $this->get_cache_key(), $request, $this->get_cache_group() );
        }

        if ( ! $request ) {
            throw new Exception( __( 'Verification request not found.', 'dokan' ), 404 );
        }

        return $this->populate( $request );
    }

    /**
     * Populate the model with data.
     *
     * @since 3.11.1
     *
     * @param stdClass $verification_request Document Object.
     *
     * @throws Exception When date time is not valid.
     * @return VerificationRequest
     */
    public function populate( stdClass $verification_request ): VerificationRequest {
        $this
            ->set_id( $verification_request->id )
            ->set_vendor_id( $verification_request->vendor_id )
            ->set_method_id( $verification_request->method_id )
            ->set_status( $verification_request->status )
            ->set_documents( maybe_unserialize( $verification_request->documents ) )
            ->set_additional_info( maybe_unserialize( $verification_request->additional_info ) )
            ->set_note( $verification_request->note )
            ->set_checked_by( $verification_request->checked_by )
            ->set_created_at( current_datetime()->setTimestamp( $verification_request->created_at ) )
            ->set_updated_at( current_datetime()->setTimestamp( $verification_request->updated_at ) );

        return $this;
    }

    /**
     * Update verification request.
     *
     * @since 3.11.1
     *
     * @throws Exception
     * @return VerificationRequest
     */
    public function update(): VerificationRequest {
        global $wpdb;

        if ( ! $this->get_id() ) {
            throw new Exception( __( 'Verification Method id is required', 'dokan' ) );
        }

        $updated = $wpdb->update(
            $this->get_table_name(),
            [
                'vendor_id'       => $this->get_vendor_id(),
                'method_id'       => $this->get_method_id(),
                'status'          => $this->get_status(),
                'checked_by'      => $this->get_checked_by(),
                'additional_info' => maybe_serialize( $this->get_additional_info() ),
                'documents'       => maybe_serialize( $this->get_documents() ),
                'note'            => $this->get_note(),
                'updated_at'      => current_datetime()->getTimestamp(),
            ],
            [
                'id' => $this->get_id(),
            ],
            [
                '%d',
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
                '%d',
            ],
            [
                '%d',
            ]
        );

        if ( false === $updated ) {
            $error_message = sprintf(
                __( 'Error while updating verification request data.', 'dokan' ) . '%s',
                current_user_can( dokan_admin_menu_capability() ) ? sprintf( 'Error: %s', $wpdb->last_error ) : ''
            );
            throw new Exception( $error_message );
        }

        Cache::delete( $this->get_cache_key(), $this->get_cache_group() );

        do_action( 'dokan_pro_vendor_verification_request_updated', $this->get_id() );

        return $this->get();
    }

    /**
     * Save verification method data.
     *
     * @since 3.11.1
     *
     * @throws Exception
     * @return VerificationRequest
     */
    public function save(): VerificationRequest {
        if ( $this->get_id() ) {
            return $this->update();
        }

        return $this->create();
    }

    /**
     * Delete a verification request.
     *
     * @since 3.11.1
     *
     * @throws Exception If ID not provided or not deleted.
     */
    public function delete(): bool {
        global $wpdb;

        if ( ! $this->get_id() ) {
            throw new Exception( __( 'Verification request id is required', 'dokan' ) );
        }

        $deleted = $wpdb->delete(
            $this->get_table_name(),
            [
                'id' => $this->get_id(),
            ],
            [
                '%d',
            ]
        );

        if ( false === $deleted ) {
            $error_message = sprintf(
                __( 'Error while deleting verification request.', 'dokan' ) . '%s',
                current_user_can( dokan_admin_menu_capability() ) ? sprintf( 'Error: %s', $wpdb->last_error ) : ''
            );
            throw new Exception( $error_message );
        }

        Cache::delete( $this->get_cache_key(), $this->get_cache_group() );

        do_action( 'dokan_pro_vendor_verification_request_deleted', $this->get_id() );

        $this->set_id( 0 );

        return true;
    }

    /**
     * Convert to array.
     *
     * @since 3.11.1
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'id'              => $this->get_id(),
            'vendor_id'       => $this->get_vendor_id(),
            'method_id'       => $this->get_method_id(),
            'status'          => $this->get_status(),
            'status_title'    => $this->get_status_title(),
            'documents'       => $this->get_documents(),
            'note'            => $this->get_note(),
            'additional_info' => $this->get_additional_info(),
            'checked_by'      => $this->get_checked_by(),
            'created_at'      => dokan_format_datetime( $this->get_created_at() ),
            'updated_at'      => dokan_format_datetime( $this->get_updated_at() ),
        ];
    }

    /**
     * Convert Object to array.
     *
     * @since 3.11.1
     *
     * @return array
     */
    public function __toArray(): array {
        return $this->to_array();
    }

    /**
     * Convert to object.
     *
     * @since 3.11.1
     *
     * @return stdClass
     */
    public function __toObject(): stdClass {
        return (object) $this->to_array();
    }

    /**
     * Count method.
     *
     * @since 3.11.1
     *
     * @param array $args Array of arguments.
     *
     * @return array
     */
    public function count( array $args = [] ): array {
        global $wpdb;

        // fix args to return count
        $args['return']   = 'count';
        $args['per_page'] = -1;
        $args['status']   = '';

        // try to get results from db
        $result = $this->get_results_from_cache( $args );
        if ( false !== $result ) {
            return $result;
        }

        // now query the db
        $count = $this->query_resutls( $args );

        $result = [
            self::STATUS_APPROVED  => 0,
            self::STATUS_PENDING   => 0,
            self::STATUS_CANCELLED => 0,
            self::STATUS_REJECTED  => 0,
            'total'                => 0,
        ];

        if ( null === $count ) {
            // in case of error, do not cache the result, instead throw an exception.
            $error_message = sprintf( 'Database query error. Error: %s', $wpdb->last_error );
            dokan_log( $error_message );
        } else {
            foreach ( $count as $item ) {
                $result[ $item->status ] = $item->count;
                $result['total']         += $item->count;
            }

            $this->set_results_to_cache( $args, $result );
        }

        return $result;
    }

    /**
     * Query method.
     *
     * @since 3.11.1
     *
     * @param array $args Array of arguments.
     *
     * @return VerificationRequest[]
     */
    public function query( array $args ): array {
        global $wpdb;

        // fixed args to return objects
        $args['return'] = 'object';

        // try to get results from db
        $result = $this->get_results_from_cache( $args );
        if ( false !== $result ) {
            return $result;
        }

        // now query db
        $result = $this->query_resutls( $args );
        if ( null === $result ) {
            // in case of error, do not cache the result, instead throw an exception.
            $error_message = sprintf( 'Database query error. Error: %s', $wpdb->last_error );
            dokan_log( $error_message );

            return [];
        }

        $result = array_map( function ( $verification_request ) { return ( new self() )->populate( $verification_request ); }, $result );
        $this->set_results_to_cache( $args, $result );

        return $result;
    }

    /**
     * Query field method.
     *
     * @since 3.11.1
     *
     * @param array $args Array of arguments.
     *
     * @return array
     */
    public function query_field( array $args ): array {
        global $wpdb;

        // fixed args to return objects
        $args['return'] = 'field';

        // check if field args is provided and its valid field
        if ( empty( $args['field'] ) || ! in_array( $args['field'], $this->valid_fields(), true ) ) {
            return [];
        }

        // try to get results from db
        $result = $this->get_results_from_cache( $args );
        if ( false !== $result ) {
            return $result;
        }

        // now query db
        $result = $this->query_resutls( $args, 'get_col' );

        if ( null === $result ) {
            // in case of error, do not cache the result, instead throw an exception.
            $error_message = sprintf( 'Database query error. Error: %s', $wpdb->last_error );
            dokan_log( $error_message );

            return [];
        }

        $this->set_results_to_cache( $args, $result );

        return $result;
    }

    /**
     * Get query results from cache
     *
     * @since 3.11.1
     *
     * @param $args
     *
     * @return false|mixed
     */
    private function get_results_from_cache( $args ) {
        $cache_key   = $this->get_cache_group() . md5( wp_json_encode( $args ) );
        $cache_group = $this->get_cache_group() . '_query';

        return Cache::get( $cache_key, $cache_group );
    }

    /**
     * Set query results to cache
     *
     * @since 3.11.1
     *
     * @param array $args
     * @param mixed $result
     *
     * @return void
     */
    private function set_results_to_cache( $args, $result ) {
        $cache_key   = $this->get_cache_group() . md5( wp_json_encode( $args ) );
        $cache_group = $this->get_cache_group() . '_query';

        Cache::set( $cache_key, $result, $cache_group );
    }

    /**
     * Prepare query args.
     *
     * @since 3.11.1
     *
     * @param array $args Array of arguments.
     *
     * @return array
     */
    private function prepare_query_args( $args ) {
        $default = [
            'id'        => 0,
            'vendor_id' => 0,
            'method_id' => 0,
            'status'    => '',
            'page'      => 1,
            'per_page'  => 10,
            'order_by'  => 'id',
            'order'     => 'DESC',
            'field'     => [],
            'return'    => 'object', // available fields: ids, field, count, object.
        ];

        $args = wp_parse_args( $args, $default );

        $fields     = '';
        $join       = '';
        $where      = '';
        $groupby    = '';
        $orderby    = '';
        $limits     = '';
        $query_args = [ 1, 1 ];
        $status     = '';

        // determine which fields to return
        if ( 'field' === $args['return'] && in_array( $args['field'], $this->valid_fields(), true ) ) {
            $fields = $args['field'];
        } elseif ( 'count' === $args['return'] ) {
            $fields  = 'status, COUNT(id) AS count';
            $groupby = 'GROUP BY status';
        } else {
            $fields = '*';
        }

        // check if id filter is applied, id can be single or array
        if ( ! dokan_is_empty( $args['id'] ) ) {
            $ids   = implode( ",", array_map( 'absint', (array) $args['id'] ) );
            $where .= " AND id IN ($ids)";
        }

        // check if vendor_id filter is applied, vendor_id can be single or array
        if ( ! dokan_is_empty( $args['vendor_id'] ) ) {
            $ids   = implode( ",", array_map( 'absint', (array) $args['vendor_id'] ) );
            $where .= " AND vendor_id IN ($ids)";
        }

        // check if method_id filter is applied, method_id can be single or array
        if ( ! dokan_is_empty( $args['method_id'] ) ) {
            $ids   = implode( ",", array_map( 'absint', (array) $args['method_id'] ) );
            $where .= " AND method_id IN ($ids)";
        }

        // check if status filter is applied, status can only be a string value or empty string
        if ( ! dokan_is_empty( $args['status'] ) && in_array( $args['status'], array_keys( $this->get_statuses() ), true ) ) {
            $where        .= " AND status = %s";
            $query_args[] = $args['status'];
        }

        // check if the page and per_page filter is applied
        if (
            'count' !== $args['return']
            && ! dokan_is_empty( $args['page'] )
            && ! dokan_is_empty( $args['per_page'] )
            && -1 !== $args['per_page']
        ) {
            $limits       = "LIMIT %d, %d";
            $query_args[] = ( $args['page'] - 1 ) * $args['per_page'];
            $query_args[] = $args['per_page'];
        }

        // check if order by filter is applied
        if ( ! dokan_is_empty( $args['order_by'] ) && in_array( $args['order_by'], $this->valid_fields(), true ) ) {
            $orderby = "ORDER BY {$args['order_by']} {$args['order']}";
        }

        return [
            $fields,
            $join,
            $where,
            $groupby,
            $orderby,
            $limits,
            $query_args,
        ];
    }

    /**
     * Query results.
     *
     * @since 3.11.1
     *
     * @param array  $args   Array of arguments.
     * @param string $method Method name.
     *
     * @return array
     */
    private function query_resutls( $args = [], $method = 'get_results' ) {
        global $wpdb;

        [ $fields, $join, $where, $groupby, $orderby, $limits, $query_args ] = $this->prepare_query_args( $args );

        // phpcs:disable.
        $query = $wpdb->prepare(
            "SELECT {$fields} FROM {$this->get_table_name()} {$join} WHERE %d=%d {$where} {$groupby} {$orderby} {$limits}",
            $query_args
        );

        switch ( $method ) {
            case 'get_results':
                $result = $wpdb->get_results( $query );
                break;
            case 'get_col':
                $result = $wpdb->get_col( $query );
                break;
            case 'get_row':
                $result = $wpdb->get_row( $query );
                break;
            case 'get_var':
                $result = $wpdb->get_var( $query );
                break;
        }

        // phpcs:enable.

        return $result;
    }

    /**
     * Get valid fields.
     *
     * @since 3.11.1
     *
     * @return array
     */
    private function valid_fields(): array {
        return [
            'id',
            'vendor_id',
            'method_id',
            'status',
            'checked_by',
            'additional_info',
            'documents',
            'note',
        ];
    }
}

<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\Models;

use DateTimeImmutable;
use Exception;
use stdClass;
use WeDevs\Dokan\Cache;

defined( 'ABSPATH' ) || exit();

/**
 * method for vendor verification.
 *
 * @since 3.11.1
 */
class VerificationMethod {

    /**
     * @since 3.11.1
     *
     * Status Enabled constant.
     */
    const STATUS_ENABLED = true;

    /**
     * @since 3.11.1
     *
     * Status Disabled constant
     */
    const STATUS_DISABLED = false;

    /**
     * @since 3.11.1
     *
     * Kind Custom constant.
     */
    const TYPE_CUSTOM = 'custom';

    /**
     * @since 3.11.1
     *
     * Kind Address constant.
     */
    const TYPE_ADDRESS = 'address';

    /**
     * @since 3.11.1
     *
     * @var int $id The method identifier.
     */
    protected $id = 0;

    /**
     * @since 3.11.1
     *
     * @var string $title The method title.
     */
    protected $title = '';

    /**
     * @since 3.11.1
     *
     * @var string $help_text The method help text.
     */
    protected $help_text = '';

    /**
     * @since 3.11.1
     *
     * @var bool $required Is Required verification?
     */
    protected $required = false;

    /**
     * @since 3.11.1
     *
     * @var string $kind The method.
     */
    protected $kind = self::TYPE_CUSTOM;

    /**
     * @since 3.11.1
     *
     * @var DateTimeImmutable $created_at Created at date time.
     */
    protected $created_at;

    /**
     * @since 3.11.1
     *
     * @var DateTimeImmutable $updated_at Updated at date time.
     */
    protected $updated_at;

    /**
     * @since 3.11.1
     *
     * @var string $table_name Table name.
     */
    private $table_name = '';

    /**
     * @since 3.11.1
     *
     * @var bool $status Whether the method is enabled.
     */
    protected $status = self::STATUS_DISABLED;

    /**
     * VerificationMethod constructor.
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
        return 'dokan_vendor_verification_method_' . $this->get_id();
    }

    /**
     * Get the cache group for this method.
     *
     * @since 3.11.1
     *
     * @return string
     */
    public function get_cache_group(): string {
        return 'dokan_vendor_verification_methods';
    }

    /**
     * Get ID.
     *
     * @since 3.11.1
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Set ID.
     *
     * @since 3.11.1
     *
     * @param int $id ID.
     *
     * @return VerificationMethod
     */
    public function set_id( int $id ): VerificationMethod {
        $this->id = $id;

        return $this;
    }

    /**
     * Get Title.
     *
     * @since 3.11.1
     *
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }

    /**
     * Set Title.
     *
     * @since 3.11.1
     *
     * @param string $title Title.
     *
     * @return VerificationMethod
     */
    public function set_title( string $title ): VerificationMethod {
        $this->title = sanitize_text_field( $title );

        return $this;
    }

    /**
     * Get help text.
     *
     * @since 3.11.1
     *
     * @return string
     */
    public function get_help_text(): string {
        return $this->help_text;
    }

    /**
     * Set Help text.
     *
     * @since 3.11.1
     *
     * @param string $help_text Help text.
     *
     * @return VerificationMethod
     */
    public function set_help_text( string $help_text ): VerificationMethod {
        $this->help_text = wp_kses_post( $help_text );

        return $this;
    }

    /**
     * Is required?
     *
     * @since 3.11.1
     *
     * @return bool
     */
    public function is_required(): bool {
        return wc_string_to_bool( $this->required );
    }

    /**
     * Set required status.
     *
     * @since 3.11.1
     *
     * @param bool $required Required status.
     *
     * @return VerificationMethod
     */
    public function set_required( bool $required ): VerificationMethod {
        $this->required = (int) $required;

        return $this;
    }

    /**
     * Get created at date time.
     *
     * @since 3.11.1
     *
     * @return DateTimeImmutable
     */
    public function get_created_at(): DateTimeImmutable {
        return $this->created_at;
    }

    /**
     * Set created at date time.
     *
     * @since 3.11.1
     *
     * @param DateTimeImmutable $created_at DateTime.
     *
     * @return VerificationMethod
     */
    public function set_created_at( DateTimeImmutable $created_at ): VerificationMethod {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * Get method Kind.
     *
     * @since 3.11.1
     *
     * @return string
     */
    public function get_kind(): string {
        return $this->kind;
    }

    /**
     * Set method Kind.
     *
     * @since 3.11.1
     *
     * @param string $kind method Kind. Possible values are `custom` and `address`.
     *
     * @return VerificationMethod
     */
    public function set_kind( string $kind ): VerificationMethod {
        $this->kind = in_array( $kind, [ self::TYPE_ADDRESS, self::TYPE_CUSTOM ], true ) ? $kind : self::TYPE_CUSTOM;

        return $this;
    }

    /**
     * Get Updated at date time.
     *
     * @since 3.11.1
     *
     * @return DateTimeImmutable
     */
    public function get_updated_at(): DateTimeImmutable {
        return $this->updated_at;
    }

    /**
     * Set Updated at date time.
     *
     * @since 3.11.1
     *
     * @param DateTimeImmutable $updated_at DateTime.
     *
     * @return VerificationMethod
     */
    public function set_updated_at( DateTimeImmutable $updated_at ): VerificationMethod {
        $this->updated_at = $updated_at;

        return $this;
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

        $this->table_name = $wpdb->prefix . 'dokan_vendor_verification_methods';
    }

    /**
     * Is the method Enabled?
     *
     * @since 3.11.1
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return wc_string_to_bool( $this->status );
    }

    /**
     * Set the method Enabled status.
     *
     *
     * @since 3.11.1
     *
     * @param bool $status Enabled Status.
     *
     * @return VerificationMethod
     */
    public function set_enabled( bool $status ): VerificationMethod {
        $this->status = (int) $status;

        return $this;
    }

    /**
     * Get and populate data.
     *
     * @throws Exception If id does not exist or record is invalid.
     * @return VerificationMethod
     */
    public function get(): VerificationMethod {
        global $wpdb;

        if ( ! $this->get_id() ) {
            throw new Exception( __( 'ID not provided.', 'dokan' ) );
        }

        $document = Cache::get( $this->get_cache_key(), $this->get_cache_group() );

        if ( false === $document ) {
            $document = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->get_table_name()} WHERE id = %d",
                    [
                        $this->get_id(),
                    ]
                )
            );

            if ( null === $document ) {
                // in case of error, do not cache the result, instead throw an exception.
                $error_message = sprintf(
                    __( 'Verification method not found with given id.', 'dokan' ) . '%s',
                    current_user_can( dokan_admin_menu_capability() ) ? sprintf( 'Error: %s', $wpdb->last_error ) : ''
                );

                throw new Exception( $error_message, 404 );
            }

            Cache::set( $this->get_cache_key(), $document, $this->get_cache_group() );
        }

        return $this->populate( $document );
    }

    /**
     * Populate the model with data.
     *
     * @since 3.11.1
     *
     * @param stdClass $document Document Object.
     *
     * @throws Exception When date time is not valid.
     * @return VerificationMethod
     */
    protected function populate( stdClass $document ): VerificationMethod {
        $this
            ->set_id( $document->id )
            ->set_title( $document->title )
            ->set_help_text( $document->help_text )
            ->set_enabled( $document->status )
            ->set_required( $document->required )
            ->set_kind( $document->kind )
            ->set_created_at( dokan_current_datetime()->setTimestamp( $document->created_at ) )
            ->set_updated_at( dokan_current_datetime()->setTimestamp( $document->updated_at ) );

        return $this;
    }

    /**
     * Create new method.
     *
     * @since 3.11.1
     *
     * @throws Exception If required info not provided or database error.
     * @return VerificationMethod
     */
    public function create(): VerificationMethod {
        global $wpdb;

        if ( empty( $this->get_title() ) ) {
            throw new Exception( __( 'Verification Method title is required', 'dokan' ) );
        }

        $inserted = $wpdb->insert(
            $this->get_table_name(),
            [
                'title'      => $this->get_title(),
                'help_text'  => $this->get_help_text(),
                'status'     => $this->is_enabled(),
                'required'   => $this->is_required(),
                'kind'       => $this->get_kind(),
                'created_at' => current_datetime()->getTimestamp(),
                'updated_at' => current_datetime()->getTimestamp(),
            ],
            [
                '%s',
                '%s',
                '%d',
                '%d',
                '%s',
                '%d',
                '%d',
            ]
        );

        if ( false === $inserted ) {
            $error_message = sprintf(
                __( 'Error while creating new verification method.', 'dokan' ) . '%s',
                current_user_can( dokan_admin_menu_capability() ) ? sprintf( 'Error: %s', $wpdb->last_error ) : ''
            );
            throw new Exception( $error_message );
        }

        $this->set_id( $wpdb->insert_id );

        do_action( 'dokan_pro_vendor_verification_method_created', $this->get_id() );

        return $this->get();
    }

    /**
     * Updates the verification method.
     *
     * @since 3.11.1
     *
     * @throws Exception If verification method is not updated successfully.
     * @return VerificationMethod
     */
    public function update(): VerificationMethod {
        global $wpdb;

        if ( ! $this->get_id() ) {
            throw new Exception( __( 'Verification Method id is required', 'dokan' ) );
        }

        $updated = $wpdb->update(
            $this->get_table_name(),
            [
                'title'      => $this->get_title(),
                'help_text'  => $this->get_help_text(),
                'status'     => $this->is_enabled(),
                'required'   => $this->is_required(),
                'kind'       => $this->get_kind(),
                'updated_at' => current_datetime()->getTimestamp(),
            ],
            [
                'id' => $this->get_id(),
            ],
            [
                '%s',
                '%s',
                '%d',
                '%d',
                '%s',
                '%d',
            ],
            [
                '%d',
            ]
        );

        if ( false === $updated ) {
            $error_message = sprintf(
                __( 'Error while updating verification method data.', 'dokan' ) . '%s',
                current_user_can( dokan_admin_menu_capability() ) ? sprintf( 'Error: %s', $wpdb->last_error ) : ''
            );
            throw new Exception( $error_message );
        }

        Cache::delete( $this->get_cache_key(), $this->get_cache_group() );

        do_action( 'dokan_pro_vendor_verification_method_updated', $this->get_id() );

        return $this->get();
    }

    /**
     * Save verification method data.
     *
     * @since 3.11.1
     *
     * @throws Exception
     * @return VerificationMethod
     */
    public function save(): VerificationMethod {
        if ( $this->get_id() ) {
            return $this->update();
        }

        return $this->create();
    }

    /**
     * Delete a verification method.
     *
     * @since 3.11.1
     *
     * @throws Exception If ID not provided or not deleted.
     */
    public function delete(): bool {
        global $wpdb;

        if ( ! $this->get_id() ) {
            throw new Exception( __( 'Verification Method id is required', 'dokan' ) );
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
                __( 'Error while deleting verification method.', 'dokan' ) . '%s',
                current_user_can( dokan_admin_menu_capability() ) ? sprintf( 'Error: %s', $wpdb->last_error ) : ''
            );
            throw new Exception( $error_message );
        }

        Cache::delete( $this->get_cache_key(), $this->get_cache_group() );

        do_action( 'dokan_pro_vendor_verification_method_deleted', $this->get_id() );

        $this->set_id( 0 );

        return true;
    }

    /**
     * Count method.
     *
     * @since 3.11.1
     *
     * @param array $args Array of arguments.
     *
     * @return int
     */
    public function count( array $args ): int {
        global $wpdb;

        $where = '';
        $where .= ! empty( $args['search'] ) ? $wpdb->prepare( ' AND title LIKE %s', '%' . $wpdb->esc_like( $args['search'] ) . '%' ) : '';
        $where .= isset( $args['status'] ) && rest_is_boolean( $args['status'] ) ? $wpdb->prepare( ' AND status=%d', $args['status'] ) : '';
        $where .= isset( $args['required'] ) && rest_is_boolean( $args['required'] ) ? $wpdb->prepare( ' AND required=%d', $args['required'] ) : '';

        $cache_key   = $this->get_cache_group() . '_count_' . md5( wp_json_encode( $args ) );
        $cache_group = $this->get_cache_group() . '_query';
        $count       = Cache::get( $cache_key, $cache_group );

        if ( false === $count ) {
            // phpcs:disable.
            $count = $wpdb->get_var(
                "SELECT count(id) FROM {$this->get_table_name()} WHERE 1=1 {$where}"
            );
            // phpcs:enable.

            if ( null === $count ) {
                // in case of error, do not cache the result, instead throw an exception.
                $error_message = sprintf( 'Database query error. Error: %s', $wpdb->last_error );
                dokan_log( $error_message );
                return 0;
            }

            Cache::set( $cache_key, $count, $cache_group );
        }

        return absint( $count );
    }

    /**
     * Query method.
     *
     * @since 3.11.1
     *
     * @param array $args Array of arguments.
     *
     * @return VerificationMethod[]
     */
    public function query( array $args ): array {
        global $wpdb;

        $where    = '';
        $where    .= isset( $args['status'] ) && rest_is_boolean( $args['status'] ) ? $wpdb->prepare( ' AND status=%d', $args['status'] ) : '';
        $where    .= ! empty( $args['search'] ) ? $wpdb->prepare( ' AND title LIKE %s', '%' . $wpdb->esc_like( $args['search'] ) . '%' ) : '';
        $where    .= isset( $args['required'] ) && rest_is_boolean( $args['required'] ) ? $wpdb->prepare( ' AND required=%d', $args['required'] ) : '';
        $where    .= isset( $args['kind'] ) && in_array( $args['kind'], [ self::TYPE_ADDRESS, self::TYPE_CUSTOM ], true ) ? $wpdb->prepare( ' AND kind=%s', $args['kind'] ) : '';
        $limit    = ! empty( $args['limit'] ) ? $wpdb->prepare( ' LIMIT %d', $args['limit'] ) : '';
        $offset   = ! empty( $args['offset'] ) ? $wpdb->prepare( ' OFFSET %d', $args['offset'] ) : '';
        $order_by = ! empty( $args['order_by'] ) ? $wpdb->prepare( ' ORDER BY %s', esc_sql( $args['order_by'] ) ) : '';
        $order    = ! empty( $args['order'] ) && ! empty( $args['order_by'] ) ? ' ' . $args['order'] : '';

        $cache_key   = $this->get_cache_group() . md5( wp_json_encode( $args ) );
        $cache_group = $this->get_cache_group() . '_query';
        $result      = Cache::get( $cache_key, $cache_group );

        if ( false === $result ) {
            // phpcs:disable.
            $result = $wpdb->get_results(
                "SELECT * FROM {$this->get_table_name()} WHERE 1=1 {$where} {$order_by} {$order} {$limit} {$offset}"
            );
            // phpcs:enable.

            if ( null === $result ) {
                // in case of error, do not cache the result, instead throw an exception.
                $error_message = sprintf( 'Database query error. Error: %s', $wpdb->last_error );
                dokan_log( $error_message );
                return [];
            }

            Cache::set( $cache_key, $result, $cache_group );
        }

        return array_map(
            function ( $document ) {
                return ( new self() )->populate( $document );
            }, $result
        );
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
            'id'         => $this->get_id(),
            'title'      => $this->get_title(),
            'help_text'  => $this->get_help_text(),
            'status'     => $this->is_enabled(),
            'required'   => $this->is_required(),
            'kind'       => $this->get_kind(),
            'created_at' => dokan_format_datetime( $this->get_created_at() ),
            'updated_at' => dokan_format_datetime( $this->get_updated_at() ),
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
}

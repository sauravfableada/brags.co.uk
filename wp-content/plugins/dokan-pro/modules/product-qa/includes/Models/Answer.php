<?php

namespace WeDevs\DokanPro\Modules\ProductQA\Models;

use DateTimeInterface;
use DateTimeZone;
use Exception;
use stdClass;
use WeDevs\Dokan\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Answer Model.
 *
 * @since 3.11.0
 */
class Answer {

    /**
     * @var int $id Answer ID.
     */
    protected $id = 0;

    /**
     * @var int $question_id Question ID.
     */
    protected $question_id = 0;

    /**
     * @var string $answer Answer.
     */
    protected $answer = '';

    /**
     * @var int $user_id User ID.
     */
    protected $user_id = 0;

    /**
     * @var DateTimeInterface|null Created at.
     */
    protected ?DateTimeInterface $created_at;

    /**
     * @var DateTimeInterface|null Updated at.
     */
    protected ?DateTimeInterface $updated_at;


    /**
     * @var string $table Answer table name.
     */
    protected $table = 'dokan_product_qa_answers';

    /**
     * Constructor.
     */
    public function __construct( int $id = 0 ) {
        if ( $id ) {
            $this->id = $id;
            $this->get();
        }
    }

    /**
     * Get ID.
     *
     * @since 3.11.0
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Set ID.
     *
     * @param int $id ID.
     *
     * @since 3.11.0
     *
     * @return Answer
     */
    public function set_id( int $id ): Answer {
        $this->id = $id;

        return $this;
    }

    /**
     * Get question ID.
     *
     * @since 3.11.0
     *
     * @return int
     */
    public function get_question_id(): int {
        return $this->question_id;
    }

    /**
     * Set question ID.
     *
     * @param int $question_id question ID.
     *
     * @since 3.11.0
     *
     * @return Answer
     */
    public function set_question_id( int $question_id ): Answer {
        $this->question_id = $question_id;

        return $this;
    }

    /**
     * Get answer.
     *
     * @since 3.11.0
     *
     * @return string
     */
    public function get_answer(): string {
        return $this->answer;
    }

    /**
     * Set answer.
     *
     * @since 3.11.0
     *
     * @param string $answer Answer.
     *
     * @return Answer
     */
    public function set_answer( string $answer ): Answer {
        $this->answer = $answer;

        return $this;
    }

    /**
     * Get User ID.
     *
     * @since 3.11.0
     *
     * @return int
     */
    public function get_user_id(): int {
        return $this->user_id;
    }

    /**
     * Set User ID.
     *
     * @param int $user_id User ID.
     *
     * @return Answer
     */
    public function set_user_id( int $user_id ): Answer {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get created at.
     *
     * @since 3.11.0
     *
     * @return DateTimeInterface
     */
    public function get_created_at(): DateTimeInterface {
        return $this->created_at ?? dokan_current_datetime();
    }

    /**
     * Set created at.
     *
     * @since 3.11.0
     *
     * @param DateTimeInterface $created_at Created at.
     *
     * @return Answer
     */
    public function set_created_at( DateTimeInterface $created_at ): Answer {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * Get updated at.
     *
     * @since 3.11.0
     *
     * @return DateTimeInterface
     */
    public function get_updated_at(): DateTimeInterface {
        return $this->updated_at ?? dokan_current_datetime();
    }

    /**
     * Set updated at.
     *
     * @param DateTimeInterface $updated_at Updated at.
     *
     * @since 3.11.0
     *
     * @return Answer
     */
    public function set_updated_at( DateTimeInterface $updated_at ): Answer {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * Get table name.
     *
     * @since 3.11.0
     *
     * @return string
     */
    public function get_table(): string {
        global $wpdb;

        return $wpdb->prefix . $this->table;
    }

    /**
     * Get question.
     *
     * @since 3.11.0
     *
     * @return Answer
     * @throws Exception If ID not set.
     */
    public function get(): Answer {
        global $wpdb;

        if ( ! $this->get_id() && ! $this->get_question_id() ) {
            throw new Exception( esc_html__( 'Answer or Question ID not set.', 'dokan' ) );
        }

        if ( $this->get_id() ) {
            $answer = Cache::get( $this->get_cache_key(), $this->get_cache_group() );

            if ( false === $answer ) {
                $table = $this->get_table();
                $id = intval( $this->get_id() );

                $query = $wpdb->prepare(
                    'SELECT * FROM ' . esc_sql( $table ) . ' WHERE id = %d',
                    $id
                );
                $answer = $wpdb->get_row( $query ); // phpcs:ignore
                Cache::set( $this->get_cache_key(), $answer, $this->get_cache_group() );
            }

            if ( ! $answer ) {
                throw new Exception( esc_html__( 'Answer not found.', 'dokan' ) );
            }

            return $this->prepare_data( $answer );
        }
        $answers = $this->query(
            [
                'question_id' => $this->get_question_id(),
                'limit' => 1,
            ]
        );
        if ( ! empty( $answers ) ) {
            return reset( $answers );
        }

        return $this;
    }

    /**
     * Create new answer.
     *
     * @since 3.11.0
     *
     * @throws Exception
     */
    public function create(): Answer {
        global $wpdb;

        if ( $this->get_id() ) {
            throw new Exception( esc_html__( 'Answer ID is already set.', 'dokan' ) );
        }

        $inserted = $wpdb->insert(
            $this->get_table(),
            [
                'question_id' => $this->get_question_id(),
                'answer'      => $this->get_answer(),
                'user_id'     => $this->get_user_id(),
                'created_at' => dokan_current_datetime()->setTimezone( new DateTimeZone( 'UTC' ) )
                    ->format( 'Y-m-d H:i:s' ),
                'updated_at' => dokan_current_datetime()->setTimezone( new DateTimeZone( 'UTC' ) )
                    ->format( 'Y-m-d H:i:s' ),

            ],
            [
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
            ]
        );

        if ( false === $inserted ) {
            throw new Exception( esc_html__( 'Error while creating new answer.', 'dokan' ) );
        }

        $this->set_id( $wpdb->insert_id );

        do_action( 'dokan_pro_product_qa_answer_created', $this->get_id() );

        return $this->get();
    }

    /**
     * Update answer.
     *
     * @since 3.11.0
     *
     * @throws Exception
     */
    public function update(): Answer {
        global $wpdb;

        if ( ! $this->get_id() ) {
            throw new Exception( esc_html__( 'Answer id is required.', 'dokan' ) );
        }

        $updated = $wpdb->update(
            $this->get_table(),
            [
                'question_id' => $this->get_question_id(),
                'answer'      => $this->get_answer(),
                'user_id'     => $this->get_user_id(),
                'updated_at'  => dokan_current_datetime()
                    ->setTimezone( new DateTimeZone( 'UTC' ) )
                    ->format( 'Y-m-d H:i:s' ),
            ],
            [
                'id' => $this->get_id(),
            ],
            [
                '%d',
                '%s',
                '%d',
                '%s',
            ],
            [
                '%d',
            ]
        );

        if ( false === $updated ) {
            throw new Exception( esc_html__( 'Error while updating Answer.', 'dokan' ) );
        }

        Cache::delete( $this->get_cache_key(), $this->get_cache_group() );

        do_action( 'dokan_pro_product_qa_answer_updated', $this->get_id() );

        return $this->get();
    }

    /**
     * Delete answer.
     *
     * @since 3.11.0
     *
     * @throws Exception
     */
    public function delete(): Answer {
        global $wpdb;

        if ( ! $this->get_id() ) {
            throw new Exception( esc_html__( 'Answer id is required.', 'dokan' ) );
        }

        $deleted = $wpdb->delete(
            $this->get_table(),
            [
                'id' => $this->get_id(),
            ],
            [
                '%d',
            ]
        );

        if ( false === $deleted ) {
            throw new Exception( esc_html__( 'Error while deleting Answer.', 'dokan' ) );
        }

        Cache::delete( $this->get_cache_key(), $this->get_cache_group() );

        do_action( 'dokan_pro_product_qa_answer_deleted', $this->get_id(), $this );

        $this->set_id( 0 );

        return $this;
    }

    /**
     * Prepare data.
     *
     * @param stdClass $data Data.
     *
     * @since 3.11.0
     *
     * @return Answer
     * @throws Exception If properties not set.
     */
    public function prepare_data( stdClass $data ): Answer {
        $properties = [ 'id', 'question_id', 'answer', 'user_id', 'created_at', 'updated_at' ];

        $not_set_properties = [];
        foreach ( $properties as $property ) {
            if ( ! isset( $data->$property ) ) {
                $not_set_properties[] = $property;
            }
        }

        if ( ! empty( $not_set_properties ) ) {
            throw new Exception(
                'Properties not set: ' . esc_html( implode( ', ', $not_set_properties ) )
            );
        }

        $this->set_id( $data->id );
        $this->set_question_id( $data->question_id );
        $this->set_answer( $data->answer );
        $this->set_user_id( $data->user_id );
        $this->set_created_at( dokan_current_datetime()->setTimezone( new DateTimeZone( 'UTC' ) )->modify( $data->created_at ) );
        $this->set_updated_at( dokan_current_datetime()->setTimezone( new DateTimeZone( 'UTC' ) )->modify( $data->updated_at ) );

        return $this;
    }

    /**
     * Get cache key.
     *
     * @return string
     */
    public function get_cache_key(): string {
        return 'dokan_product_qa_answer_' . $this->get_id();
    }

    /**
     * Get cache group.
     *
     * @since 3.11.0
     *
     * @return string
     */
    public function get_cache_group(): string {
        return 'dokan_product_qa_answers';
    }


    /**
     * Count method.
     *
     * @since 3.11.0
     *
     * @param array $args Array of arguments.
     *
     * @return int
     */
    public function count( array $args ): int {
        global $wpdb;

        list( $where, $limit, $offset, $order_by, $order ) = $this->parse_query_args( $args );

        $table       = $this->get_table();
        $cache_key   = $this->get_cache_group() . '_count_' . md5( wp_json_encode( $args ) );
        $cache_group = $this->get_cache_group() . '_query';
        $count       = Cache::get( $cache_key, $cache_group );

        if ( false === $count ) {
            // phpcs:disable.
            $count = $wpdb->get_var(
                "SELECT count(*) as count FROM {$table} WHERE 1=1 {$where}"
            );
            // phpcs:enable.

            Cache::set( $cache_key, $count, $cache_group );
        }

        return absint( $count );
    }

    /**
     * Query method.
     *
     * @since 3.11.0
     *
     * @param array $args Array of arguments.
     *
     * @return Answer[]
     * @throws Exception
     */
    public function query( array $args ): array {
        global $wpdb;

        list( $where, $limit, $offset, $order_by, $order ) = $this->parse_query_args( $args );

        $table       = $this->get_table();
        $cache_key   = $this->get_cache_group() . md5( wp_json_encode( $args ) );
        $cache_group = $this->get_cache_group() . '_query';
        $result      = Cache::get( $cache_key, $cache_group );

        if ( false === $result ) {
            // phpcs:disable.
            $result = $wpdb->get_results(
                "SELECT * FROM {$table} WHERE 1=1 {$where} {$order_by} {$order} {$limit} {$offset}"
            );
            // phpcs:enable.

            Cache::set( $cache_key, $result, $cache_group );
        }

        return array_map(
            function ( $answer ) {
                return ( new self() )->prepare_data( $answer );
            },
            $result
        );
    }

    /**
     * To array method.
     *
     * @since 3.11.0
     *
     * @return array
     */
    public function to_array(): array {
        $user = new \WP_User( $this->get_user_id() );

        return [
            'id'                        => $this->get_id(),
            'question_id'               => $this->get_question_id(),
            'answer'                    => do_shortcode( $this->get_answer() ),
            'user_id'                   => $this->get_user_id(),
            'created_at'                => dokan_format_datetime( $this->get_created_at() ),
            'updated_at'                => dokan_format_datetime( $this->get_updated_at() ),
            'human_readable_created_at' => sprintf(
            // translators: %s is the time difference.
                esc_html__( '%s ago', 'dokan' ),
                human_time_diff( $this->get_created_at()->getTimestamp() )
            ),

            'human_readable_updated_at' => sprintf(
            // translators: %s is the time difference.
                esc_html__( '%s ago', 'dokan' ),
                human_time_diff( $this->get_updated_at()->getTimestamp() )
            ),
            'user_display_name'         => $user->exists() ? $user->display_name : __( 'Deleted User', 'dokan' ),
        ];
    }

    /**
     * Parse query args.
     *
     * @since 3.11.0
     *
     * @param array $args Args.
     *
     * @return array
     */
    public function parse_query_args( array $args ): array {
        global $wpdb;

        $where    = '';
        $where    .= ! empty( $args['search'] ) ? $wpdb->prepare( ' AND MATCH( answer ) AGAINST( %s IN BOOLEAN MODE)', esc_sql( $args['search'] ) ) : '';
        $where    .= isset( $args['user_id'] ) ? $wpdb->prepare( ' AND user_id=%d', $args['user_id'] ) : '';
        $where    .= isset( $args['question_id'] ) ? $wpdb->prepare( ' AND question_id=%d', $args['question_id'] ) : '';
        $limit    = ! empty( $args['limit'] ) ? $wpdb->prepare( ' LIMIT %d', $args['limit'] ) : '';
        $offset   = ! empty( $args['offset'] ) ? $wpdb->prepare( ' OFFSET %d', $args['offset'] ) : '';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $order_by = ! empty( $args['order_by'] ) ? ' ORDER BY `' . esc_sql( $args['order_by'] ) . '`' : '';
        $order    = ! empty( $args['order'] ) && ! empty( $args['order_by'] ) ? ' ' . esc_sql( $args['order'] ) : '';

        return array( $where, $limit, $offset, $order_by, $order );
    }
}

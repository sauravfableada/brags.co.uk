<?php

namespace WeDevs\DokanPro\Modules\ProductQA\Models;

use DateTimeInterface;
use DateTimeZone;
use Exception;
use stdClass;
use WeDevs\Dokan\Cache;
use WeDevs\DokanPro\Modules\ProductQA\DTOs\Count;

defined( 'ABSPATH' ) || exit;

/**
 * Question Model.
 *
 * @since 3.11.0
 */
class Question {

    /**
     * Question visibility status read.
     */
    const STATUS_READ = 1;

    /**
     * Question visibility status unread.
     */
    const STATUS_UNREAD = 0;

    /**
     * Question answered status.
     */
    const STATUS_ANSWERED = 1;

    /**
     * Question unanswered status.
     */
    const STATUS_UNANSWERED = 0;

    /**
     * Question status deleted.
     */
    const STATUS_DELETED = 'deleted';

    /**
     * Question status hidden.
     */
    const STATUS_HIDDEN = 'hidden';

    /**
     * Question status visible.
     */
    const STATUS_VISIBLE = 'visible';

    /**
     * @var int $id Question ID.
     */
    protected $id = 0;

    /**
     * @var int $product_id Product ID.
     */
    protected $product_id = 0;

    /**
     * @var string $question Question.
     */
    protected $question = '';

    /**
     * @var bool $is_answered Is answered question.
     */
    protected $is_answered = false;

    /**
     * @var int $user_id User ID.
     */
    protected $user_id = 0;

    /**
     * @var int $read Question read status.
     */
    protected $read = self::STATUS_UNREAD;

    /**
     * @var string $status Question status.
     */
    protected $status = self::STATUS_VISIBLE;

    /**
     * @var DateTimeInterface|null Created at.
     */
    protected ?DateTimeInterface $created_at;

    /**
     * @var DateTimeInterface|null Updated at.
     */
    protected ?DateTimeInterface $updated_at;

    /**
     * @var Answer $answer Answer.
     */
    protected $answer;

    /**
     * @var string $table Question table name.
     */
    protected $table = 'dokan_product_qa_questions';

    /**
     * Constructor.
     */
    public function __construct( int $id = 0 ) {
        $this->set_answer( new Answer() );
        if ( $id ) {
            $this->set_id( $id );
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
     * @return Question
     */
    public function set_id( int $id ): Question {
        $this->id = $id;

        $this->get_answer()->set_question_id( $id );

        return $this;
    }

    /**
     * Get product ID.
     *
     * @since 3.11.0
     *
     * @return int
     */
    public function get_product_id(): int {
        return $this->product_id;
    }

    /**
     * Set product ID.
     *
     * @param int $product_id Product ID.
     *
     * @since 3.11.0
     *
     * @return Question
     */
    public function set_product_id( int $product_id ): Question {
        $this->product_id = $product_id;

        return $this;
    }

    /**
     * Get question.
     *
     * @since 3.11.0
     *
     * @return string
     */
    public function get_question(): string {
        return $this->question;
    }

    /**
     * Set question.
     *
     * @since 3.11.0
     *
     * @param string $question Question.
     *
     * @return Question
     */
    public function set_question( string $question ): Question {
        $this->question = $question;

        return $this;
    }

    /**
     * Get answered status.
     *
     * @since 4.0.0
     *
     * @return bool
     */
    public function is_answered(): bool {
        return $this->is_answered;
    }

    /**
     * Set answered status.
     *
     * @since 4.0.0
     *
     * @param bool $is_answered Answered status.
     *
     * @return Question
     */
    public function set_is_answered( bool $is_answered ): Question {
        $this->is_answered = $is_answered;

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
     * @return Question
     */
    public function set_user_id( int $user_id ): Question {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get read status.
     *
     * @since 3.11.0
     *
     * @return int
     */
    public function get_read(): int {
        return $this->read;
    }

    /**
     * Set read status.
     *
     * @param int $read Read status.
     *
     * @since 3.11.0
     *
     * @return Question
     */
    public function set_read( int $read ): Question {
        $this->read = $read;

        return $this;
    }

    /**
     * @return string
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return Question
     */
    public function set_status( string $status ): Question {
        $this->status = $status;

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
     * @return Question
     */
    public function set_created_at( DateTimeInterface $created_at ): Question {
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
     * @return Question
     */
    public function set_updated_at( DateTimeInterface $updated_at ): Question {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * Get answer related to this question.
     *
     * @since 3.11.0
     *
     * @return Answer
     */
    public function get_answer(): Answer {
        if ( ! $this->answer->get_id() ) {
            try {
                $answers = $this->answer->query( [ 'question_id' => $this->get_id() ] );
                if ( ! empty( $answers ) ) {
                    $this->set_answer( reset( $answers ) );
                }
            } catch ( Exception $e ) {
                dokan_log( $e->getMessage() );
            }
        }

        return $this->answer;
    }

    /**
     * Set answer related to this question.
     *
     * @since 3.11.0
     *
     * @param Answer $answer Answer.
     *
     * @return Question
     */
    public function set_answer( Answer $answer ): Question {
        $this->answer = $answer;

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
     * @return Question
     * @throws Exception If ID not set.
     */
    public function get(): Question {
        global $wpdb;

        if ( ! $this->get_id() ) {
            throw new Exception( esc_html__( 'Question ID not set.', 'dokan' ) );
        }

        $question = Cache::get( $this->get_cache_key(), $this->get_cache_group() );

        if ( false === $question ) {
            $table = $this->get_table();
            $id    = intval( $this->get_id() );

            $answer_table = ( new Answer() )->get_table();
            $query        = $wpdb->prepare(
                'SELECT q.*, a.id AS is_answered FROM ' . esc_sql( $table ) . ' q LEFT JOIN ' . esc_sql( $answer_table ) . ' a ON q.id = a.question_id WHERE q.id = %d LIMIT 1',
                $id
            );

            $question = $wpdb->get_row( $query ); // phpcs:ignore

            Cache::set( $this->get_cache_key(), $question, $this->get_cache_group() );
        }

        if ( ! $question ) {
            throw new Exception( esc_html__( 'Question not found.', 'dokan' ) );
        }

        $question->is_answered = (bool) $question->is_answered;

        return $this->prepare_data( $question );
    }

    /**
     * Create question.
     *
     * @since 3.11.0
     *
     * @throws Exception
     */
    public function create(): Question {
        global $wpdb;

        if ( $this->get_id() ) {
            throw new Exception( esc_html__( 'Question ID is already set.', 'dokan' ) );
        }

        $inserted = $wpdb->insert(
            $this->get_table(),
            [
                'product_id' => $this->get_product_id(),
                'question'   => $this->get_question(),
                'user_id'    => $this->get_user_id(),
                'read'       => $this->get_read(),
                'status'     => $this->get_status(),
                'created_at' => dokan_current_datetime()->setTimezone( new DateTimeZone( 'UTC' ) )
                                                        ->format( 'Y-m-d H:i:s' ),
                'updated_at' => dokan_current_datetime()->setTimezone( new DateTimeZone( 'UTC' ) )
                                                        ->format( 'Y-m-d H:i:s' ),
            ],
            [
                '%d',
                '%s',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ( false === $inserted ) {
            throw new Exception( esc_html__( 'Error while creating new Question.', 'dokan' ) );
        }

        $this->set_id( $wpdb->insert_id );

        do_action( 'dokan_pro_product_qa_question_created', $this->get_id() );

        return $this->get();
    }

    /**
     * Update question.
     *
     * @since 3.11.0
     *
     * @throws Exception
     */
    public function update(): Question {
        global $wpdb;

        if ( ! $this->get_id() ) {
            throw new Exception( esc_html__( 'Question id is required.', 'dokan' ) );
        }

        $updated = $wpdb->update(
            $this->get_table(),
            [
                'product_id' => $this->get_product_id(),
                'question'   => $this->get_question(),
                'user_id'    => $this->get_user_id(),
                'read'       => $this->get_read(),
                'status'     => $this->get_status(),
                'updated_at' => dokan_current_datetime()
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
                '%d',
                '%s',
                '%s',
            ],
            [
                '%d',
            ]
        );

        if ( false === $updated ) {
            throw new Exception( esc_html__( 'Error while updating Question.', 'dokan' ) );
        }

        Cache::delete( $this->get_cache_key(), $this->get_cache_group() );

        do_action( 'dokan_pro_product_qa_question_updated', $this->get_id() );

        return $this->get();
    }

    /**
     * Delete question.
     *
     * @since 3.11.0
     *
     * @throws Exception
     */
    public function delete(): Question {
        global $wpdb;

        if ( ! $this->get_id() ) {
            throw new Exception( esc_html__( 'Question id is required.', 'dokan' ) );
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
            throw new Exception( esc_html__( 'Error while deleting Question.', 'dokan' ) );
        }

        Cache::delete( $this->get_cache_key(), $this->get_cache_group() );

        do_action( 'dokan_pro_product_qa_question_deleted', $this->get_id(), $this );

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
     * @return Question
     * @throws Exception If properties not set.
     */
    public function prepare_data( stdClass $data ): Question {
        $properties = [ 'id', 'product_id', 'question', 'user_id', 'read', 'status', 'created_at', 'updated_at', 'is_answered' ];

        $not_set_properties = [];
        foreach ( $properties as $property ) {
            if ( ! isset( $data->$property ) ) {
                $not_set_properties[] = $property;
            }
        }

        if ( ! empty( $not_set_properties ) ) {
            throw new Exception( 'Properties not set: ' . esc_html( implode( ', ', $not_set_properties ) ) );
        }

        $this->set_id( $data->id );
        $this->set_product_id( $data->product_id );
        $this->set_question( $data->question );
        $this->set_user_id( $data->user_id );
        $this->set_read( $data->read );
        $this->set_status( $data->status );
        $this->set_is_answered( (bool) $data->is_answered );
        $this->set_created_at( dokan_current_datetime()->setTimezone( new DateTimeZone( 'UTC' ) )->modify( $data->created_at ) );
        $this->set_updated_at( dokan_current_datetime()->setTimezone( new DateTimeZone( 'UTC' ) )->modify( $data->updated_at ) );
        $this->set_answer( $this->get_answer()->set_question_id( $this->get_id() )->get() );

        return $this;
    }

    /**
     * Get cache key.
     *
     * @return string
     */
    public function get_cache_key(): string {
        return 'dokan_product_qa_question_' . $this->get_id();
    }

    /**
     * Get cache group.
     *
     * @since 3.11.0
     *
     * @return string
     */
    public function get_cache_group(): string {
        return 'dokan_product_qa_questions';
    }

    /**
     * Count all the status, read state, answered state.
     *
     * @since 3.11.0
     *
     * @param array $args Array of arguments.
     *
     * @return Count
     */
    public function count_status( array $args ): Count {
        global $wpdb;

        unset( $args['status'], $args['read'], $args['answered'] );

        list( $where, $table, $limit, $offset, $order_by, $order, $left_join ) = $this->parse_query_args( $args );

        $cache_key   = $this->get_cache_group() . '_count_status_' . md5( wp_json_encode( $args ) );
        $cache_group = $this->get_cache_group() . '_query';
        $count       = Cache::get( $cache_key, $cache_group );
        $visible     = self::STATUS_VISIBLE;
        $read        = self::STATUS_READ;

        if ( false === $count ) {
            // phpcs:disable.
            $count = $wpdb->get_row(
                "SELECT COUNT(*) as total, COUNT( IF( status = '{$visible}', 1, NULL ) ) as visible_count, COUNT( IF( q.read = '{$read}', 1, NULL ) ) as read_count, COUNT( a.id ) as answered_count FROM {$table} as q {$left_join} WHERE 1=1 {$where}"
            );
            // phpcs:enable.

            Cache::set( $cache_key, $count, $cache_group );
        }

        return new Count( $count );
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

	    list( $where, $table, $limit, $offset, $order_by, $order, $left_join ) = $this->parse_query_args( $args );

        $cache_key   = $this->get_cache_group() . '_count_' . md5( wp_json_encode( $args ) );
        $cache_group = $this->get_cache_group() . '_query';
        $count       = Cache::get( $cache_key, $cache_group );

        if ( false === $count ) {
            // phpcs:disable.
            $count = $wpdb->get_var(
                "SELECT count(*) as count FROM {$table} as q {$left_join} WHERE 1=1 {$where}"
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
     * @return Question[]
     * @throws Exception
     */
    public function query( array $args ): array {
        global $wpdb;

	    list( $where, $table, $limit, $offset, $order_by, $order, $left_join ) = $this->parse_query_args( $args );

	    $cache_key   = $this->get_cache_group() . md5( wp_json_encode( $args ) );
        $cache_group = $this->get_cache_group() . '_query';
        $result      = Cache::get( $cache_key, $cache_group );

        if ( false === $result ) {
            // phpcs:disable.
            $result = $wpdb->get_results(
                "SELECT q.id as q_id, product_id, question, q.user_id as q_user_id, a.answer as answer, q.read, q.status, q.created_at, q.updated_at, a.id as a_id, a.user_id as a_user_id, a.created_at as a_created_at, a.updated_at as a_updated_at FROM {$table} as q {$left_join} WHERE 1=1 {$where} {$order_by} {$order} {$limit} {$offset}"
            );
            // phpcs:enable.

            Cache::set( $cache_key, $result, $cache_group );
        }

        return array_map( function ( $row ) { // phpcs:ignore
            $question              = new stdClass();
            $question->id          = $row->q_id;
            $question->product_id  = $row->product_id;
            $question->question    = $row->question;
            $question->user_id     = $row->q_user_id;
            $question->read        = $row->read;
            $question->status      = $row->status;
            $question->created_at  = $row->created_at;
            $question->updated_at  = $row->updated_at;
            $question->is_answered = (bool) $row->a_id;

            $answer_object = ( new Answer() )->set_question_id( $question->id );
            if ( $row->a_id ) {
                $answer              = new stdClass();
                $answer->id          = $row->a_id;
                $answer->question_id = $row->q_id;
                $answer->answer      = $row->answer;
                $answer->user_id     = $row->a_user_id;
                $answer->created_at  = $row->a_created_at;
                $answer->updated_at  = $row->a_updated_at;
                $answer_object       = ( $answer_object )->prepare_data( $answer );
            }

            return ( new self() )
                ->prepare_data( $question )
                ->set_answer( $answer_object );
        }, $result ); // phpcs:ignore
    }

    /**
     * Perform bulk action.
     *
     * @since 3.11.0
     *
     * @param array $ids Array of ids.
     * @param string $action Action.
     *
     * @return bool
     * @throws Exception
     */
    public function bulk_action( array $ids, string $action = 'delete' ): bool {
        global $wpdb;

        $id_list = implode( ',', esc_sql( $ids ) );
        if ( 'delete' === $action ) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM %i WHERE `id` IN ({$id_list})", // phpcs:ignore
                    [
                        $this->get_table(),
                    ]
                ) // phpcs:ignore
            );

            if ( false === $result ) {
                throw new Exception( esc_html__( 'Error while bulk deleting Question.', 'dokan' ) );
            }

            foreach ( $ids as $id ) {
                Cache::delete( 'dokan_product_qa_question_' . $id, $this->get_cache_group() );
                do_action( 'dokan_pro_product_qa_question_deleted', $id, $this );
            }
        } else {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE %i SET `read`=%d WHERE id IN ( {$id_list} )", // phpcs:ignore
                    [
                        $this->get_table(),
                        'read' === $action ? self::STATUS_READ : self::STATUS_UNREAD,
                    ]
                )// phpcs:ignore
            );

            if ( false === $result ) {
                throw new Exception( esc_html__( 'Error while bulk marking Question.', 'dokan' ) );
            }

            foreach ( $ids as $id ) {
                Cache::delete( 'dokan_product_qa_question_' . $id, $this->get_cache_group() );
                do_action( 'dokan_pro_product_qa_question_updated', $id );
            }
        }

        return true;
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
            'id'                                => $this->get_id(),
            'product_id'                        => $this->get_product_id(),
            'question'                          => $this->get_question(),
            'user_id'                           => $this->get_user_id(),
            'read'                              => $this->get_read(),
            'status'                            => $this->get_status(),
            'created_at'                        => dokan_format_datetime( $this->get_created_at() ),
            'updated_at'                        => dokan_format_datetime( $this->get_updated_at() ),
            'answer'                            => $this->get_answer()->to_array(),
            'user_display_name'                 => $user->exists() ? $user->display_name
                : __( 'Deleted User', 'dokan' ),
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
            'display_human_readable_created_at' => $this->get_created_at()->diff( dokan_current_datetime() )->d < 7,
            'display_human_readable_updated_at' => $this->get_updated_at()->diff( dokan_current_datetime() )->d < 7,
        ];
    }

	/**
	 * Parse query args.
	 *
	 * @param array $args Args.
	 *
	 * @return array
	 */
	public function parse_query_args( array $args ): array {
		global $wpdb;

        $where    = '';
		$table    = $this->get_table();
		$where    .= ! empty( $args['search'] ) ? $wpdb->prepare( ' AND ( MATCH( `question` ) AGAINST( %s IN BOOLEAN MODE ) OR MATCH( `answer` ) AGAINST( %s IN BOOLEAN MODE) )', esc_sql( $args['search'] ), esc_sql( $args['search'] ) ) : '';
		$where    .= isset( $args['status'] ) ? $wpdb->prepare( ' AND status=%s', esc_sql( $args['status'] ) ) : '';
		$where    .= isset( $args['read'] ) ? $wpdb->prepare( ' AND q.read=%d', $args['read'] ) : '';
        $where    .= isset( $args['answered'] ) ? $wpdb->prepare( ' AND a.id %1$s',  $args['answered'] ? 'IS NOT NULL' : 'IS NULL' ) : ''; // phpcs:ignore
        $where    .= isset( $args['user_id'] ) ? $wpdb->prepare( ' AND q.user_id=%d', $args['user_id'] ) : '';
		$where    .= isset( $args['exclude_user_id'] ) ? $wpdb->prepare( ' AND (NOT q.user_id=%d)', $args['exclude_user_id'] ) : '';
		$where    .= isset( $args['product_id'] ) ? $wpdb->prepare( ' AND product_id=%d', $args['product_id'] ) : '';
        $where    .= isset( $args['vendor_id'] ) ? $wpdb->prepare( ' AND product_id IN ( SELECT ID FROM %1$s WHERE post_author=%2$d AND post_type=\'product\' )', $wpdb->posts, $args['vendor_id'] ) : ''; // phpcs:ignore
        $limit    = ! empty( $args['limit'] ) ? $wpdb->prepare( ' LIMIT %d', $args['limit'] ) : '';
		$offset   = ! empty( $args['offset'] ) ? $wpdb->prepare( ' OFFSET %d', $args['offset'] ) : '';
		$order_by = '';
		$order    = '';

		// When we are using full text search, the order and order by is handled by the relevancy of the individual result.
		if ( empty( $args['search'] ) ) {
			$order_by = ! empty( $args['order_by'] ) ? $wpdb->prepare( ' ORDER BY %1$s', esc_sql( $args['order_by'] ) ) : ' ORDER BY q.id'; // phpcs:ignore
			$order    = ! empty( $args['order'] ) && ! empty( $args['order_by'] ) ? ' ' . $args['order'] : '';
		}

		$left_join = $wpdb->prepare( ' LEFT JOIN %i AS a ON q.id = a.question_id', ( new Answer() )->get_table() );

		return array( $where, $table, $limit, $offset, $order_by, $order, $left_join );
	}
}

<?php

namespace WeDevs\DokanPro\Modules\ProductQA\DTOs;

defined( 'ABSPATH' ) || exit();

/**
 * Class Count
 *
 * @since 3.11.0
 */
class Count {
    protected $total = 0;
    protected $read = 0;
    protected $unread = 0;
    protected $answered = 0;
    protected $unanswered = 0;
    protected $visible = 0;
    protected $hidden = 0;

    /**
     * Count constructor.
     *
     * @param \stdClass  $count Count from DB.
     */
    public function __construct(
        \stdClass $count
    ) {
        $this->total      = $count->total;
        $this->read       = $count->read_count;
        $this->unread     = $count->total - $count->read_count;
        $this->answered   = $count->answered_count;
        $this->unanswered = $count->total - $count->answered_count;
        $this->visible    = $count->visible_count;
        $this->hidden     = $count->total  - $count->visible_count;
    }

    /**
     * Get Total Count.
     *
     * @since 3.11.0
     * @return int
     */
    public function total(): int {
        return $this->total;
    }

    /**
     * Get Read Count.
     *
     * @since 3.11.0
     * @return int
     */
    public function read(): int {
        return $this->read;
    }

    /**
     * Get Unread Count.
     *
     * @since 3.11.0
     * @return int
     */
    public function unread(): int {
        return $this->unread;
    }

    /**
     * Get Answered Count.
     *
     * @since 3.11.0
     * @return int
     */
    public function answered(): int {
        return $this->answered;
    }

    /**
     * Get Unanswered Count.
     *
     * @since 3.11.0
     * @return int
     */
    public function unanswered(): int {
        return $this->unanswered;
    }

    /**
     * Get Visible Count.
     *
     * @since 3.11.0
     * @return int
     */
    public function visible(): int {
        return $this->visible;
    }

    /**
     * Get Hidden Count.
     *
     * @since 3.11.0
     * @return int
     */
    public function hidden(): int {
        return $this->hidden;
    }


    /**
     * Convert to array.
     *
     * @since 3.11.0
     * @return array
     */
    public function toArray(): array {
        return [
            'total'      => $this->total,
            'read'       => $this->read,
            'unread'     => $this->unread,
            'answered'   => $this->answered,
            'unanswered' => $this->unanswered,
            'visible'    => $this->visible,
            'hidden'     => $this->hidden,
        ];
    }

    /**
     * Convert to json.
     *
     * @since 3.11.0
     * @return false|string
     */
    public function toJson() {
        return wp_json_encode( $this->toArray() );
    }

    /**
     * Convert to string.
     *
     * @since 3.11.0
     * @return false|string
     */
    public function __toString() {
        return $this->toJson();
    }
}

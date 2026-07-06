<?php

namespace WeDevs\DokanPro\Emails;

use WC_Email;

/**
 * Abstract Refund Email Handler.
 *
 * @since 4.2.2
 */
abstract class AbstractRefund extends WC_Email {

    /**
     * Retrieves all translated statuses.
     *
     * @since 4.2.2
     *
     * @return array An associative array of statuses with keys as status identifier and values as their translated labels.
     */
    public function all_translated_statuses() {
        return apply_filters(
            'dokan_refund_statuses',
            [
                'pending'  => esc_html__( 'pending', 'dokan' ),
                'approved' => esc_html__( 'approved', 'dokan' ),
                'canceled' => esc_html__( 'canceled', 'dokan' ),
			]
        );
    }

    /**
     * Retrieves the translated status for a given status key.
     *
     * @since 4.2.2
     *
     * @param string $status The status key to translate.
     *
     * @return string The translated status if found, otherwise the original status key.
     */
    public function get_translated_status( $status ) {
        return $this->all_translated_statuses()[ $status ] ?? $status;
    }
}

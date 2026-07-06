<?php

class Dokan_Follow_Store_Cron {
    /**
     * Class constructor
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'cron_schedules', array( $this, 'add_weekly_schedule' ) );
        add_action( 'dokan_follow_store_send_updates', array( $this, 'send_based_on_frequency' ) );
    }
    public function send_based_on_frequency() {
        if ( $this->get_frequency() === 'daily' ) {
            $this->send_updates();
        } else {
            $this->send_weekly_updates();
        }
    }
    /**
     * Add a weekly cron schedule to WordPress.
     *
     * @param array $schedules The existing cron schedules.
     * @return array The schedules with the new 'weekly' interval.
     */ 
    public function add_weekly_schedule( $schedules ) {
        $schedules['weekly'] = array(
            'interval' => 604800,// 7 days in seconds
            'display' => 'Once Weekly',
        );
        return $schedules;
    }
    /**
     * Get frequency setting
     *
     * @return string 'daily' or 'weekly'
     */
    public function get_frequency() {
            $option_key = 'woocommerce_updates_for_store_followers_settings';
            $settings = get_option( $option_key, array() );
            return isset($settings['frequency']) ? $settings['frequency'] : 'daily';
    } 
    /**
     * Unschedule cron
     *
     * Fires when module deactivate
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function unschedule_event() {
        $timestamp = wp_next_scheduled( 'dokan_follow_store_send_updates' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'dokan_follow_store_send_updates' );
        }
    }

    /**
     * Cron action hook method
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function send_updates() {
        $processor_file = DOKAN_FOLLOW_STORE_INCLUDES . '/class-dokan-follow-store-send-updates.php';

        global $dokan_follow_store_updates_bg;
        if ( empty( $dokan_follow_store_updates_bg ) ) {
            return;
        }

        $dokan_follow_store_updates_bg->cancel_process();

        $yesterday = date( 'Y-m-d', strtotime( '-24 hours', current_time( 'timestamp' ) ) );
        $from      = $yesterday . ' 00:00:00';
        $to        = $yesterday . ' 23:59:59';

        $args = array(
            'page'  => 1,
            'from'  => $from,
            'to'    => $to,
        );

        $dokan_follow_store_updates_bg->push_to_queue( $args )->dispatch_process( $processor_file );
    }
    /**
     * Send weekly updates (conditional on frequency).
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function send_weekly_updates() {

    $processor_file = DOKAN_FOLLOW_STORE_INCLUDES . '/class-dokan-follow-store-send-updates.php';

    global $dokan_follow_store_updates_bg;
    if ( empty( $dokan_follow_store_updates_bg ) ) {
            return;
    }

    $dokan_follow_store_updates_bg->cancel_process();

        $from = date( 'Y-m-d 00:00:00', strtotime( '-7 days', dokan_current_datetime()->getTimestamp() ) );
        $to   = date( 'Y-m-d 23:59:59', strtotime( '-1 day', dokan_current_datetime()->getTimestamp() ) );

    $args = array(
        'page'  => 1,
        'from'  => $from,
        'to'    => $to,
    );

    $dokan_follow_store_updates_bg->push_to_queue( $args )->dispatch_process( $processor_file );
    }
}

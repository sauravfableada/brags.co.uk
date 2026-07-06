<?php
/**
 * Dokan Delivery time form template
 *
 * @since 3.3.0
 * @package DokanPro
 */

use \WeDevs\DokanPro\Modules\DeliveryTime\Helper;

$vendor_delivery_time_settings = isset( $vendor_delivery_time_settings ) ? $vendor_delivery_time_settings : [];

$allow_delivery_support       = isset( $vendor_delivery_time_settings['delivery_support'] ) ? $vendor_delivery_time_settings['delivery_support'] : 'off';
$delivery_date_label          = isset( $vendor_delivery_time_settings['delivery_date_label'] ) ? $vendor_delivery_time_settings['delivery_date_label'] : 'Delivery Date';
$delivery_preorder_date       = isset( $vendor_delivery_time_settings['preorder_date'] ) ? $vendor_delivery_time_settings['preorder_date'] : 0;
$delivery_prep_date           = isset( $vendor_delivery_time_settings['delivery_prep_date'] ) ? $vendor_delivery_time_settings['delivery_prep_date'] : 0;
$selected_delivery_days       = isset( $vendor_delivery_time_settings['delivery_day'] ) ? $vendor_delivery_time_settings['delivery_day'] : [];
$delivery_opening_time        = isset( $vendor_delivery_time_settings['opening_time'] ) ? (array) $vendor_delivery_time_settings['opening_time'] : [];
$delivery_closing_time        = isset( $vendor_delivery_time_settings['closing_time'] ) ? (array) $vendor_delivery_time_settings['closing_time'] : [];
$delivery_time_slot_minutes   = isset( $vendor_delivery_time_settings['time_slot_minutes'] ) ? $vendor_delivery_time_settings['time_slot_minutes'] : 30;
$delivery_order_per_slot      = isset( $vendor_delivery_time_settings['order_per_slot'] ) ? $vendor_delivery_time_settings['order_per_slot'] : 0;
$delivery_time_slot_minutes   = is_array( $delivery_time_slot_minutes ) ? max( $delivery_time_slot_minutes ) : $delivery_time_slot_minutes;
$delivery_order_per_slot      = is_array( $delivery_order_per_slot ) ? max( $delivery_order_per_slot ) : $delivery_order_per_slot;
$enable_delivery_notification = isset( $vendor_delivery_time_settings['enable_delivery_notification'] ) ? $vendor_delivery_time_settings['enable_delivery_notification'] : 'off';

// New buffer settings (unit and value)
$delivery_buffer_unit  = isset( $vendor_delivery_time_settings['delivery_buffer_unit'] ) ? $vendor_delivery_time_settings['delivery_buffer_unit'] : 'days';
$delivery_buffer_value = isset( $vendor_delivery_time_settings['delivery_buffer_value'] ) ? $vendor_delivery_time_settings['delivery_buffer_value'] : 0;

$vendor_can_override_settings = isset( $vendor_can_override_settings ) ? $vendor_can_override_settings : 'off';
$all_delivery_days            = isset( $all_delivery_days ) ? $all_delivery_days : [];
$all_delivery_time_slots      = isset( $all_delivery_time_slots ) ? $all_delivery_time_slots : [];

?>

<div class="dokan-delivery-time-wrapper">
    <?php if ( 'off' === $vendor_can_override_settings ) : ?>
        <div class="overlay"></div>
    <?php endif; ?>

    <form id="dokan_delivery_time" method="post" action="" class="dokan-form-horizontal">

        <div class="dokan-form-group">
            <label class="dokan-w3 dokan-control-label delivery-time-label"><?php esc_html_e( 'Delivery Support', 'dokan' ); ?></label>
            <div class="dokan-w5 dokan-text-left">
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="delivery" value="off">
                        <input type="checkbox" name="delivery" value="on"<?php checked( $allow_delivery_support, 'on' ); ?>> <?php esc_html_e( 'Home Delivery', 'dokan' ); ?>
                    </label>

                    <?php
                    /**
                     * @since 3.3.7
                     *
                     * @param array $vendor_delivery_time_settings
                     */
                    do_action( 'dokan_delivery_time_settings_after_delivery_show_time_option', $vendor_delivery_time_settings );
                    ?>
                </div>
            </div>
        </div>

        <?php
        /**
         * @since 3.3.7
         *
         * @param int $id
         * @param array $info
         */
        do_action( 'dokan_delivery_time_settings_after_time_option', $vendor_delivery_time_settings );
        ?>

        <div id="dokan-delivery-time-vendor-settings">
            <div class="dokan-form-group">
                <label class="dokan-w3 dokan-control-label delivery-time-label" for="delivery_buffer_unit">
                    <?php esc_html_e( 'Delivery Blocked Buffer Units', 'dokan' ); ?>
                </label>
                <div class="dokan-w5 dokan-text-left">
                    <select id="delivery_buffer_unit" name="delivery_buffer_unit" class="dokan-form-control">
                        <option value="days" <?php selected( $delivery_buffer_unit, 'days' ); ?>><?php esc_html_e( 'Days', 'dokan' ); ?></option>
                        <option value="hours" <?php selected( $delivery_buffer_unit, 'hours' ); ?>><?php esc_html_e( 'Hours', 'dokan' ); ?></option>
                    </select>
                    <span class="dokan-page-help">
                        <?php esc_html_e( 'Select the time unit for the delivery buffer. Choose "Days" for full calendar days or "Hours" for more precise same-day delivery control.', 'dokan' ); ?>
                    </span>
                </div>
            </div>

            <div class="dokan-form-group pre_order_date_container">
                <label class="dokan-w3 dokan-control-label delivery-time-label" for="pre_order_date">
                    <?php esc_html_e( 'Buffer Duration', 'dokan' ); ?>
                </label>
                <div class="dokan-w5 dokan-text-left">
                    <input min="0" type="number" id="pre_order_date" name="preorder_date"
                        class="dokan-form-control" value="<?php echo esc_attr( $delivery_preorder_date ); ?>"
                        placeholder="<?php esc_attr_e( 'Minimum number of days between order and delivery. Set to 0 for same-day delivery, 1 for next-day delivery, etc.', 'dokan' ); ?>" />
                    <span class="dokan-page-help">
                        <?php esc_html_e( 'How many days the delivery date is blocked from current date? 0 for no block buffer', 'dokan' ); ?>
                    </span>
                </div>
            </div>

            <div class="dokan-form-group delivery_buffer_value_container">
                <label class="dokan-w3 dokan-control-label delivery-time-label" for="delivery_buffer_value">
                    <?php esc_html_e( 'Buffer Duration', 'dokan' ); ?>
                </label>
                <div class="dokan-w5 dokan-text-left">
                    <input min="0" type="number" id="delivery_buffer_value" name="delivery_buffer_value"
                        class="dokan-form-control" value="<?php echo esc_attr( $delivery_buffer_value ); ?>"
                        placeholder="<?php esc_attr_e( 'Delivery blocked buffer (hours)', 'dokan' ); ?>" />
                    <span class="dokan-page-help">
                        <?php esc_html_e( 'Minimum number of hours between order and delivery. If the time exceeds today\'s store hours, delivery starts from the next business day.', 'dokan' ); ?>
                    </span>
                </div>
            </div>

            <div class="dokan-form-group">
                <label class="dokan-w3 dokan-control-label delivery-time-label" for="delivery_time_slot">
                    <?php esc_html_e( 'Time slot', 'dokan' ); ?>
                </label>
                <div class="dokan-w5 dokan-text-left">
                    <input type="number" id="delivery_time_slot" name="delivery_time_slot" class="dokan-form-control"
                        value="<?php echo esc_attr( $delivery_time_slot_minutes ); ?>" placeholder="<?php esc_attr_e( 'Time slot', 'dokan' ); ?>" />
                    <span class="dokan-page-help"><?php esc_html_e( 'Time slot in minutes. Please keep opening and closing time divisible by slot minutes. E.g ( 30, 60, 120 )', 'dokan' ); ?></span>
                </div>
            </div>

            <div class="dokan-form-group">
                <label class="dokan-w3 dokan-control-label delivery-time-label" for="order_per_slot"><?php esc_html_e( 'Order per slot', 'dokan' ); ?></label>
                <div class="dokan-w5 dokan-text-left">
                    <input type="number" id="order_per_slot" name="order_per_slot" class="dokan-form-control"
                        value="<?php echo esc_attr( $delivery_order_per_slot ); ?>" placeholder="<?php esc_attr_e( 'Order per slot', 'dokan' ); ?>" />
                    <span class="dokan-page-help">
                        <?php esc_html_e( 'Maximum orders per slot. 0 for unlimited orders', 'dokan' ); ?>
                    </span>
                </div>
            </div>

            <div class="dokan-form-group">
                <div class="dokan-time-slots">
                    <?php
                    foreach ( $all_delivery_days as $day_key => $day ) :
                        $working_status = ! empty( $selected_delivery_days[ $day_key ] ) ? '1' : '0';
                        $opening_time   = Helper::get_delivery_times( $day_key, $delivery_opening_time );
                        $closing_time   = Helper::get_delivery_times( $day_key, $delivery_closing_time );
                        $full_day       = false;

                        if ( $opening_time === '12:00 am' && $closing_time === '11:59 pm' ) {
                            $full_day = true;
                        }
                        ?>
                        <div class="dokan-store-times">
                            <div class="dokan-form-group">
                                <label class="day control-label" for="working-days">
                                    <?php echo esc_html( dokan_get_translated_days( $day_key, 'short' ) ); ?>
                                </label>

                                <label class="dokan-on-off dokan-status" for="<?php echo esc_attr( $day_key ); ?>-working-status">
                                    <p class="switch tips">
                                        <span class="slider round"></span>
                                    </p>
                                    <p class='working-status'>
                                        <input type="hidden" name="delivery_day[<?php echo esc_attr( $day_key ); ?>]"
                                            id="<?php echo esc_attr( $day_key ); ?>-working-status" class="dokan-on-off toogle-checkbox"
                                            value="<?php echo esc_attr( $working_status ); ?>" />
                                    </p>
                                </label>

                                <!-- Store opening times start -->
                                <label class="time" for="delivery-opening-time-<?php echo esc_attr( $day_key ); ?>"
                                    style="visibility: <?php echo '1' === $working_status ? 'visible' : 'hidden'; ?>;">
                                    <div class='clock-picker'>
                                        <span class="far fa-clock"></span>
                                        <input type="text" class="dokan-form-control opening-time"
                                            id="delivery-opening-time-<?php echo esc_attr( $day_key ); ?>"
                                            placeholder="<?php echo esc_attr( $place_start ); ?>"
                                            value='<?php echo esc_attr( $full_day ? $all_day : $opening_time ); ?>' />
                                        <input type="hidden" value="<?php echo esc_attr( $opening_time ); ?>"
                                            class="clockOne" name="delivery_opening_time[<?php echo esc_attr( $day_key ); ?>][]" />
                                        <span class="fa fa-exclamation-triangle"></span>
                                    </div>
                                </label>
                                <!-- Store opening times end -->

                                <span class="time-to fas fa-minus"
                                    style="visibility: <?php echo '1' === $working_status ? 'visible' : 'hidden'; ?>; display: <?php echo $full_day ? 'none' : 'block'; ?>"
                                ></span>

                                <!-- Store closing times start -->
                                <label for="delivery-closing-time-<?php echo esc_attr( $day_key ); ?>" class="time"
                                    style="visibility: <?php echo '1' === $working_status ? 'visible' : 'hidden'; ?>;  display: <?php echo $full_day ? 'none' : 'block'; ?>">
                                    <div class='clock-picker'>
                                        <span class="far fa-clock"></span>
                                        <input type="text" class="dokan-form-control closing-time"
                                            id="delivery-closing-time-<?php echo esc_attr( $day_key ); ?>"
                                            placeholder="<?php echo esc_attr( $place_end ); ?>"
                                            value='<?php echo esc_attr( ! $full_day ? $closing_time : '' ); ?>'' />
                                        <input type="hidden" value="<?php echo esc_attr( $closing_time ); ?>"
                                            class="clockTwo" name="delivery_closing_time[<?php echo esc_attr( $day_key ); ?>][]" />
                                        <span class="fa fa-exclamation-triangle"></span>
                                    </div>
                                </label>
                                <!-- Store closing times end -->

                                <!-- Store times action start -->
                                <label for="open-close-actions" class="open-close-actions" style="visibility: <?php echo '1' === $working_status ? 'visible' : 'hidden'; ?>;" >
                                    <a href="" class="remove-store-closing-time">
                                        <span class="fas fa-trash"></span>
                                    </a>
                                    <a href="" class="added-store-opening-time <?php echo $full_day ? 'hide-element' : ''; ?>">
                                        <?php echo $add_action; ?>
                                    </a>
                                </label>
                                <!-- Store times action end -->

                            </div>

                            <?php
                            /**
                             * Load multiple delivery times.
                             *
                             * If vendor choose multiple delivery times for
                             * store then load here rest of deilvery times.
                             *
                             * @since 3.7.8
                             *
                             * @param string $day_key
                             * @param string $working_status
                             * @param array  $vendor_delivery_time_settings
                             */
                            do_action( 'after_dokan_delivery_time_settings_form', $day_key, $working_status, $vendor_delivery_time_settings );
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php wp_nonce_field( 'dokan_delivery_time_form_action', 'dokan_delivery_settings_nonce' ); ?>

        <div class="dokan-form-group">
            <div class="dokan-w4 dokan-text-left" style="margin-left: 24%">
                <input type="submit" name="dokan_update_delivery_time_settings"
                    class="dokan-btn dokan-btn-danger dokan-btn-theme" value="<?php esc_attr_e( 'Update Settings', 'dokan' ); ?>" />
            </div>
        </div>
        <script>
            // Adding script here because we wount maintain this template, we have react page this setting page.
            function show_or_hide_buffar_inputs() {
                let selectedUnit = document.querySelector( '#delivery_buffer_unit' ).value;

                if ( 'days' === selectedUnit ) {
                    document.querySelector( '.delivery_buffer_value_container' ).style.display = 'none';
                    document.querySelector( '.pre_order_date_container' ).style.display = 'block';
                } else {
                    document.querySelector( '.delivery_buffer_value_container' ).style.display = 'block';
                    document.querySelector( '.pre_order_date_container' ).style.display = 'none';
                }
            }
            document.querySelector( '#delivery_buffer_unit' ).addEventListener( 'change', show_or_hide_buffar_inputs );
            window.addEventListener( 'load', show_or_hide_buffar_inputs );
        </script>
    </form>
</div>

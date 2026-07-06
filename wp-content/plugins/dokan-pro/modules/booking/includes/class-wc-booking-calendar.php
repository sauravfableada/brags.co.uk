<?php

class Dokan_WC_Bookings_Calendar {

    private $bookings;

    /**
     * Output the calendar view
     */
    public function output() {
        if ( version_compare( WOOCOMMERCE_VERSION, '2.3', '<' ) ) {
            wp_enqueue_script( 'chosen' );
            wc_enqueue_js( '$( "select#calendar-bookings-filter" ).chosen();' );
        } else {
            wp_enqueue_script( 'wc-enhanced-select' );
        }

        if ( ! defined( 'WC_BOOKINGS_PLUGIN_URL' ) ) {
            return;
        }

        $admin_script_dependencies = wc_booking_get_script_dependencies( 'admin', [ 'jquery-ui-datepicker', 'jquery-ui-sortable' ] );
        wp_enqueue_script( 'wc_bookings_admin_js', WC_BOOKINGS_PLUGIN_URL . '/dist/admin.js', $admin_script_dependencies, WC_BOOKINGS_VERSION, true );
        wp_enqueue_script( 'wc_bookings_admin_calendar_js', WC_BOOKINGS_PLUGIN_URL . '/dist/admin-calendar.js', wc_booking_get_script_dependencies( 'admin-calendar' ), WC_BOOKINGS_VERSION, true );


        $params = array(
            'i18n_remove_person'            => esc_js( __( 'Are you sure you want to remove this person type?', 'dokan' ) ),
            'nonce_unlink_person'           => wp_create_nonce( 'unlink-bookable-person' ),
            'nonce_add_person'              => wp_create_nonce( 'add-bookable-person' ),
            'i18n_remove_resource'          => esc_js( __( 'Are you sure you want to remove this resource?', 'dokan' ) ),
            'nonce_delete_resource'         => wp_create_nonce( 'delete-bookable-resource' ),
            'nonce_add_resource'            => wp_create_nonce( 'add-bookable-resource' ),
            'i18n_minutes'                  => esc_js( __( 'minutes', 'dokan' ) ),
            'i18n_hours'                    => esc_js( __( 'hours', 'dokan' ) ),
            'i18n_days'                     => esc_js( __( 'days', 'dokan' ) ),
            'i18n_new_resource_name'        => esc_js( __( 'Enter a name for the new resource', 'dokan' ) ),
            'post'                          => isset( $post->ID ) ? $post->ID : '',
            'plugin_url'                    => WC()->plugin_url(),
            'ajax_url'                      => admin_url( 'admin-ajax.php' ),
            'nonce'                         => array(
                'wc_bookings_get_product_template' => wp_create_nonce( 'wc_bookings_get_product_template' ),
            ),
            'calendar_image'                => WC_BOOKINGS_PLUGIN_URL . '/dist/images/calendar.png',
            'i18n_view_details'             => esc_js( __( 'View details', 'dokan' ) ),
            'i18n_customer'                 => esc_js( __( 'Customer', 'dokan' ) ),
            'i18n_resource'                 => esc_js( __( 'Resource', 'dokan' ) ),
            'i18n_persons'                  => esc_js( __( 'Persons', 'dokan' ) ),
            'i18n_max_booking_overwridden'  => esc_js( __( 'This setting is being overridden at the resource level.', 'dokan' ) ),
            'i18n_limited_hours'            => esc_js( __( 'A duration greater than 24 hours is not allowed when Availability is "not-available by default".', 'dokan' ) ),
            'i18n_limited_hours_in_gen_tab' => esc_js( __( 'The booking duration has been set to 24 as a duration greater than 24 hours is not allowed when Availability is "not-available by default".', 'dokan' ) ),
            'bookings_version'              => WC_BOOKINGS_VERSION,
            'bookings_db_version'           => WC_BOOKINGS_DB_VERSION,
            'start_of_week'                 => get_option( 'start_of_week' ),
            'time_in_12hours'               => ! preg_match( '/(?<!\\\\)(\\\\{2})*(H|G)/', get_option( 'time_format' ) ),
        );

        wp_localize_script( 'wc_bookings_admin_js', 'wc_bookings_admin_js_params', $params );

        // @codingStandardsIgnoreStart
        $product_filter = isset( $_REQUEST['filter_bookings'] ) ? absint( $_REQUEST['filter_bookings'] ) : '';
        $view           = isset( $_REQUEST['view'] ) && $_REQUEST['view'] == 'day' ? 'day' : 'month';
        // @codingStandardsIgnoreEnd

        if ( 'day' === (string) $view ) {
            // @codingStandardsIgnoreLine
            $day = isset( $_REQUEST['calendar_day'] ) ? wc_clean( $_REQUEST['calendar_day'] ) : date( 'Y-m-d' );

            if ( version_compare( WC_BOOKINGS_VERSION, '1.15.0', '>=' ) ) {
                $this->bookings = WC_Booking_Data_Store::get_bookings_in_date_range(
                    strtotime( 'midnight', strtotime( $day ) ),
                    strtotime( 'midnight +1 day -1 min', strtotime( $day ) ),
                    $product_filter,
                    false
                );
            } else {
                $this->bookings = WC_Bookings_Controller::get_bookings_in_date_range(
                    strtotime( 'midnight', strtotime( $day ) ),
                    strtotime( 'midnight +1 day -1 min', strtotime( $day ) ),
                    $product_filter,
                    false
                );
            }
        } else {
            // @codingStandardsIgnoreStart
            $month = isset( $_REQUEST['calendar_month'] ) ? absint( $_REQUEST['calendar_month'] ) : date( 'n' );
            $year  = isset( $_REQUEST['calendar_year'] ) ? absint( $_REQUEST['calendar_year'] ) : date( 'Y' );

            if ( $year < ( date( 'Y' ) - 10 ) || $year > 2100 ) {
                $year = date( 'Y' );
            }

            if ( $month > 12 ) {
                $month = 1;
                $year ++;
            }

            if ( $month < 1 ) {
                $month = 1;
                $year --;
            }

            $start_of_week = absint( get_option( 'start_of_week', 1 ) );
            $last_day      = date( 't', strtotime( "$year-$month-01" ) );
            $start_date_w  = absint( date( 'w', strtotime( "$year-$month-01" ) ) );
            $end_date_w    = absint( date( 'w', strtotime( "$year-$month-$last_day" ) ) );
            // @codingStandardsIgnoreEnd

            // Calc day offset
            $day_offset = $start_date_w - $start_of_week;
            $day_offset = $day_offset >= 0 ? $day_offset : 7 - abs( $day_offset );

            // Cald end day offset
            $end_day_offset = 7 - ( $last_day % 7 ) - $day_offset;
            $end_day_offset = $end_day_offset >= 0 && $end_day_offset < 7 ? $end_day_offset : 7 - abs( $end_day_offset );

            // We want to get the last minute of the day, so we will go forward one day to midnight and subtract a min
            // @codingStandardsIgnoreLine
            $end_day_offset = $end_day_offset + 1;

            $start_timestamp = strtotime( "-{$day_offset} day", strtotime( "$year-$month-01" ) );
            $end_timestamp   = strtotime( "+{$end_day_offset} day midnight -1 min", strtotime( "$year-$month-$last_day" ) );

            if ( version_compare( WC_BOOKINGS_VERSION, '1.15.0', '>=' ) ) {
                $this->bookings = WC_Booking_Data_Store::get_bookings_in_date_range(
                    $start_timestamp,
                    $end_timestamp,
                    $product_filter,
                    false
                );
            } else {
                $this->bookings = WC_Bookings_Controller::get_bookings_in_date_range(
                    $start_timestamp,
                    $end_timestamp,
                    $product_filter,
                    false
                );
            }


            $calendar_params = array(
                'default_month' => esc_html( date_i18n( 'F', mktime( 0, 0, 0, $month, 10 ) ) ),
                'default_year'  => esc_html( $year ),
                'default_day'   => esc_html( isset( $_REQUEST['calendar_day'] ) ? dokan_current_datetime()->modify( wc_clean( wp_unslash( $_REQUEST['calendar_day'] ) ) )->format( 'F d, Y' ) : dokan_current_datetime()->format( 'F d, Y' ) ),
            );
            // First day of currently selected year/month for datepicker default.
            $default_date = "$year-$month-01";

            wp_localize_script( 'wc_bookings_admin_calendar_gutenberg_js', 'wc_bookings_admin_calendar_js_params', $calendar_params );

        }

        include DOKAN_WC_BOOKING_DIR . ( '/templates/booking/calendar/html-calendar-' . $view . '.php' );
    }

    /**
     * List bookings for a day
     *
     * @param  [type] $day
     * @param  [type] $month
     * @param  [type] $year
     * @return [type]
     */
    public function list_bookings( $day, $month, $year ) {
        $date_start = strtotime( "$year-$month-$day 00:00" );
        $date_end   = strtotime( "$year-$month-$day 23:59" );
        $booking_details_url  = dokan_get_navigation_url( 'booking/booking-details' );

        foreach ( $this->bookings as $booking ) {
            // @codingStandardsIgnoreLine
            if ( get_post_field( 'post_author', $booking->product_id ) != dokan_get_current_user_id() ) {
                continue;
            }

            if (
                ( $booking->start >= $date_start && $booking->start < $date_end ) ||
                ( $booking->start < $date_start && $booking->end > $date_end ) ||
                ( $booking->end > $date_start && $booking->end <= $date_end )
                ) {
                $edit_url = add_query_arg( 'booking_id', $booking->id, $booking_details_url );
                echo '<li><a href="' . $edit_url . '">';
                    echo '<strong>#' . $booking->id . ' - ';
				if ( $booking->get_product() ) {
                    $product = $booking->get_product();
					echo $product->get_title();
				}
                    echo '</strong>';
                    echo '<ul>';

				if ( ( $booking->get_customer() ) ) {
                    $customer = $booking->get_customer();
                    if ( ! empty( $customer->name ) ) {
                        echo '<li>' . __( 'Booked by', 'dokan' ) . ' ' . $customer->name . '</li>';
                    }
				}

				echo '<li>';

				if ( $booking->is_all_day() ) {
					echo __( 'All Day', 'dokan' );
				} else {
					echo $booking->get_start_date( '', 'g:ia' ) . '&mdash;' . $booking->get_end_date( '', 'g:ia' );
				}

                        echo '</li>';

				if ( $booking->get_resource() ) {
                    $resource = $booking->get_resource();
					echo '<li>' . __( 'Resource #', 'dokan' ) . $resource->ID . ' - ' . $resource->post_title . '</li>';
				}
                    echo '</ul></a>';
                echo '</li>';
            }
        }
    }

    /**
     * List bookings on a day
     */
    public function list_bookings_for_day() {
        $bookings_by_time = array();
        $all_day_bookings = array();
        $unqiue_ids       = array();

        foreach ( $this->bookings as $booking ) {
            $seller = get_post_field( 'post_author', $booking->get_product_id() );

            if ( (int) $seller !== dokan_get_current_user_id() ) {
                continue;
            }

            $edit_url = wp_nonce_url( add_query_arg( array( 'order_id' => $booking->order_id ), dokan_get_navigation_url( 'orders' ) ), 'dokan_view_order' );

            if ( $booking->is_all_day() ) {
                $all_day_bookings[] = $booking;
            } else {
                $start_time = $booking->get_start_date( '', 'Gi' );

                if ( ! isset( $bookings_by_time[ $start_time ] ) ) {
                    $bookings_by_time[ $start_time ] = array();
                }

                $bookings_by_time[ $start_time ][] = $booking;
            }

            $unqiue_ids[] = $booking->product_id . $booking->resource_id;
        }

        ksort( $bookings_by_time );

        $unqiue_ids = array_flip( $unqiue_ids );
        $index      = 0;
        $colours    = array( '#3498db', '#34495e', '#1abc9c', '#2ecc71', '#f1c40f', '#e67e22', '#e74c3c', '#2980b9', '#8e44ad', '#2c3e50', '#16a085', '#27ae60', '#f39c12', '#d35400', '#c0392b' );

        foreach ( $unqiue_ids as $key => $value ) {
            if ( isset( $colours[ $index ] ) ) {
                $unqiue_ids[ $key ] = $colours[ $index ];
            } else {
                $unqiue_ids[ $key ] = $this->random_color();
            }

            $index++;
        }

        $column = 0;

        foreach ( $all_day_bookings as $booking ) {
            echo '<li data-tip="' . esc_attr( $this->get_tip( $booking ) ) . '" style="background: ' . $unqiue_ids[ $booking->product_id . $booking->resource_id ] . '; left:' . 100 * $column . 'px; top: 0; bottom: 0; display:list-item; ">
                    <a href="' . $edit_url . '">' . $this->get_tip( $booking ) . '</a>
                </li>';
            $column++;
        }

        $start_column = $column;
        $last_end     = 0;
        $assigned_colors = $this->get_event_color_styles( $this->bookings );
        foreach ( $bookings_by_time as $bookings ) {
            foreach ( $bookings as $booking ) {
                $data   = $this->get_booking_data( $booking );

                if ( is_null( $data ) ) {
                    continue;
                }
                $attr_data = [];
                foreach ( $data as $key => $val ) {
                    $attr_data[ 'data-booking-' . $key ] = esc_attr( $val );
                }
                $li_attrs = array(
                    'style' => $assigned_colors[ $booking->get_id() ],
                );

                $attr_data = array_merge( $attr_data, $li_attrs );
                $short_start_time = $this->get_short_time( $booking->get_start() );
                $short_end_time   = $this->get_short_time( $booking->get_end() );
                // @codingStandardsIgnoreLine
                $booking_time     = sprintf( __( '%1$s %2$s', 'dokan' ), $short_start_time, $short_end_time );

                $element = "<li class='dokan-booking-time daily_view_booking dokan-booking-{$short_start_time}' data-booking-time='{$booking_time}'";
                foreach ( $attr_data as $attribute => $value ) {
                    if ( is_array( $value ) ) {
                        $attrs = '';
                        foreach ( $value as $attr_key => $attr_val ) {
                            $attrs .= "{$attr_key}: {$attr_val};";
                        }
                        $value = $attrs;
                    }

                    $element .= "{$attribute}=\"{$value}\" ";
                }
                $element .= ">";
                $element .= "<a href='{$edit_url}'>{$this->get_tip( $booking )}</a>";
                $element .= '</li>';

                echo $element;
            }
        }
    }

    /**
     * Get a random colour
     */
    public function random_color() {
        // @codingStandardsIgnoreLine
        return sprintf( '#%06X', mt_rand( 0, 0xFFFFFF ) );
    }

    /**
     * Get a tooltip in day view
     * @param  object $booking
     * @return string
     */
    public function get_tip( $booking ) {
        $return  = '';
        $return .= '#' . $booking->id . ' - ';

        if ( $booking->get_product() ) {
            $product = $booking->get_product();
            $return .= $product->get_title();
        }

        if ( ( $booking->get_customer() ) ) {
            $customer = $booking->get_customer();

            if ( ! empty( $customer->name ) ) {
                $return .= '<br/>' . __( 'Booked by', 'dokan' ) . ' ' . $customer->name;
            }
        }

        if ( $booking->get_resource() ) {
            $resource = $booking->get_resource();
            $return .= '<br/>' . __( 'Resource #', 'dokan' ) . $resource->ID . ' - ' . $resource->post_title;
        }

        if ( $booking->get_start() ) {
            $return .= '<br/>' . $booking->get_start_date( '', 'g:ia' ) . '&mdash;' . $booking->get_end_date( '', 'g:ia' );
        }

        return $return;
    }

    /**
     * Filters products for narrowing search
     */
    public function product_filters() {
        $filters = array();

        $products = get_posts(
            apply_filters(
                'get_booking_products_args', array(
					'post_status'    => 'publish',
					'post_type'      => 'product',
					'author'         => dokan_get_current_user_id(),
					'posts_per_page' => -1,
                    // @codingStandardsIgnoreLine
					'tax_query'      => array(
						array(
							'taxonomy' => 'product_type',
							'field'    => 'slug',
							'terms'    => 'booking',
						),
					),
					'suppress_filters' => true,
                )
            )
        );

        foreach ( $products as $product ) {
            $filters[ $product->ID ] = $product->post_title;

            $booking   = new WC_Product_Booking( $product );
            $resources = $booking->get_resources();

            foreach ( $resources as $resource ) {
                $filters[ $resource->ID ] = '&nbsp;&nbsp;&nbsp;' . $resource->post_title;
            }
        }

        return $filters;
    }

    /**
     * Filters resources for narrowing search
     */
    public function resources_filters() {
        $filters = array();

        $resources = get_posts(
            apply_filters(
                'get_booking_resources_args', array(
					'post_status'      => 'publish',
					'post_type'        => 'bookable_resource',
					'posts_per_page'   => -1,
					'orderby'          => 'menu_order',
					'order'            => 'asc',
					'suppress_filters' => true,
					'author'           => dokan_get_current_user_id(),
                )
            )
        );

        foreach ( $resources as $resource ) {
            $filters[ $resource->ID ] = $resource->post_title;
        }

        return $filters;
    }

    /**
     * Get formatted time from timestamp with shortened time format.
     * Shortened format removes minutes when time is on the hour and removes
     * space between time and AM/PM.
     *
     * @param int $timestamp Timestamp to format.
     * @return string
     *
     * @since 1.15.0
     */
    public function get_short_time( $timestamp ) {
        $time_format = 'g:i a';
        // Remove spaces so AM/PM will be directly next to time.
        $time_format = str_replace( ' ', '', $time_format );

        // @codingStandardsIgnoreStart
        // Hide minutes if on the hour.
        if ( '00' === date( 'i', $timestamp ) ) {
            // Remove minutes from time format.
            $time_format = str_replace( ':i', '', $time_format );
        }

        return date( $time_format, $timestamp );
        // @codingStandardsIgnoreEnd
    }

    /**
     * Get Bookings data to be included in the html element on the calendar.
     *
     * @since 3.10.4
     *
     * @param WC_Booking $booking
     * @param integer    $check_date Timestamp during day to be checked. Defaults to $_REQUEST['calendar_day'] or current day.
     *
     * @return array|null
     */
    protected function get_booking_data( $event, $check_date = null ) {
        if ( is_null( $check_date ) ) {
            $day = dokan_current_datetime()->modify(  isset( $_REQUEST['calendar_day'] ) ? wc_clean( wp_unslash( $_REQUEST['calendar_day'] ) ) : dokan_current_datetime()->format( 'Y-m-d' ) )->getTimestamp();
        } else {
            $day = $check_date;
        }
        $startday         = dokan_current_datetime()->setTimestamp( $day )->modify( 'midnight' )->getTimestamp();
        $endday           = dokan_current_datetime()->setTimestamp( $day )->modify( 'tomorrow' )->getTimestamp();
        $booking_customer = '';
        $booking_id       = is_callable( array( $event, 'get_id' ) ) ? $event->get_id() : '';
        $booking_resource = '';
        $booking_persons  = array();
        $event_url        = '';

        if ( 'WC_Booking' === get_class( $event ) ) {
            $booking          = $event;
            $product          = $booking->get_product();
            $booking_customer = $booking->get_customer()->name ?: '';
            $booking_resource = $booking->get_resource();
            $booking_resource = $booking_resource ? $booking_resource->get_name() : '';
            $event_start      = $booking->get_start();
            $event_end        = $booking->get_end();
            $event_title      = $product ? $product->get_name() : '';
            $event_url        = admin_url( 'post.php?post=' . $booking->get_id() . '&action=edit' );
            $booking_id       = $booking->get_id();

            if ( $booking->has_persons() ) {
                foreach ( $booking->get_persons() as $id => $qty ) {
                    if ( 0 === $qty ) {
                        continue;
                    }

                    $person_type = ( 0 < $id ) ? get_the_title( $id ) : __( 'Person(s)', 'dokan' );
                    /* translators: 1: person type 2: quantity */
                    $booking_persons[] = sprintf( __( '%1$s: %2$d', 'dokan' ), $person_type, $qty );
                }
            }

            if ( $product && in_array( $product->get_duration_unit(), array( 'hour', 'minute' ), true ) ) {
                /* translators: 1: start time 2: end time */
                $short_start_time = $this->get_short_time( $booking->get_start() );
                $short_end_time   = $this->get_short_time( $booking->get_end() );
                $event_time       = $booking->is_all_day() ? __( 'All Day', 'dokan' ) : sprintf( __( '%1$s — %2$s', 'dokan' ), $short_start_time, $short_end_time );
                $event_date       = $booking->get_start_date( 'l, M j, Y', '' );
            } else {
                $event_time = $booking->get_end_date( 'l, M j, Y' );
                $event_date = $booking->get_start_date( 'l, M j, Y' );

                // If the start date is same as the end date, then this is all day for that particular date
                if ( $event_time == $event_date ) {
                    $event_time = __( 'All Day', 'dokan' );
                }
            }
        } else {
            $availability = $event;
            $range        = $availability->get_time_range_for_date( $day );
            if ( is_null( $range ) ) {
                return null;
            }
            $event_start      = $range['start'];
            $event_end        = $range['end'];
            $short_start_time = $this->get_short_time( $event_start );
            $short_end_time   = $this->get_short_time( $event_end );
            /* translators: 1: start time 2: end time */
            $event_time = sprintf( __( '%1$s — %2$s', 'dokan' ), $short_start_time, $short_end_time );
            $event_date = $availability->get_formatted_date( $event_start, 'l, M j, Y' );
            // If the start date is same as the end date, then this is all day for that particular date
            if ( ( $event_start === $event_end ) || $availability->is_all_day() ) {
                $event_time = __( 'All Day', 'dokan' );
            }

            $event_title  = ! empty( $availability->get_title() ) ? $availability->get_title() : __( 'Unavailable', 'woocommerce_bookings' );
            $event_title .= ' ' . __( '(From Google Calendar)', 'dokan' );
        }

        $booking_persons = ! empty( $booking_persons ) ? implode( ', ', $booking_persons ) : '';

        $start = dokan_current_datetime()->setTimestamp( 0 )->modify( dokan_current_datetime()->setTimestamp( $event_start )->format( 'H:i' ) )->getTimestamp() / 60;
        if ( $event_start < $startday ) {
            $start = 0;
        }

        $end = dokan_current_datetime()->setTimestamp( 0 )->modify( dokan_current_datetime()->setTimestamp( $event_end )->format( 'H:i' ) )->getTimestamp() / 60;
        if ( $endday < $event_end ) {
            $end = 1440;
        }

        $end = $end ?: 1440;

        return array(
            'customer' => $booking_customer,
            'resource' => $booking_resource,
            'persons'  => $booking_persons,
            'time'     => $event_time,
            'date'     => $event_date,
            'title'    => $event_title,
            'url'      => $event_url,
            'id'       => $booking_id,
            'start'    => $start,
            'end'      => $end,
        );
    }

    /**
     * Get color CSS styles for a given list of events.
     *
     * @param array $events
     * @return array Hash event_id => color styles
     */
    protected function get_event_color_styles( $events ) {
        $colors                = array( '#d7f1bf', '#52d4ad', '#1dbcc0', '#227a95', '#fedab9', '#feaa6e', '#ffe800', '#e67e22', '#fd8d67', '#ffb2d0', '#64d72c', '#f2d7d5', '#e6b0aa', '#d98880', '#cd6155' );
        $booked_product_colors = array();
        $assigned_colors       = array();
        $index                 = 0;

        foreach ( $events as $event ) {
            if ( 'WC_Global_Availability' === get_class( $event ) ) {
                $assigned_colors[ $event->get_id() ] = '#dbdbdb';
                continue;
            }

            if ( 'WC_Booking' !== get_class( $event ) ) {
                $assigned_colors[ $event->get_id() ] = isset( $colors[ $index ] ) ? $colors[ $index ] : $this->random_color();
                $index++;
                continue;
            }

            if ( ! isset( $booked_product_colors[ $event->get_product_id() ] ) ) {
                $booked_product_colors[ $event->get_product_id() ] = isset( $colors[ $index ] ) ? $colors[ $index ] : $this->random_color();
                $index++;
            }

            $assigned_colors[ $event->get_id() ] = $booked_product_colors[ $event->get_product_id() ];
        }

        return array_map(
            function( $color ) {
                return array(
                    'background' => $color,
                    'color'      => $this->get_font_color( $color ),
                );
            },
            $assigned_colors
        );
    }


    /**
     * Determine font color based on background color.
     * Calculations rely on perceptive luminance (contrast).
     *
     * @param string $bg_color Background color as a hex code.
     *
     * @return string Font color as a hex code.
     */
    protected function get_font_color( $bg_color ) {
        $bg_color = hexdec( str_replace( '#', '', $bg_color ) );
        $red      = 0xFF & ( $bg_color >> 0x10 );
        $green    = 0xFF & ( $bg_color >> 0x8 );
        $blue     = 0xFF & $bg_color;

        $luminance = 1 - ( 0.299 * $red + 0.587 * $green + 0.114 * $blue ) / 255;

        return $luminance < 0.5 ? '#000000' : '#ffffff';
    }
}

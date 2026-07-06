<?php

use Google\Analytics\Admin\V1beta\AnalyticsAdminServiceClient;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\ApiCore\ValidationException;
use WeDevs\DokanPro\Modules\VendorAnalytics\Formatter;
use WeDevs\DokanPro\Modules\VendorAnalytics\Token;
use WeDevs\DokanPro\Modules\VendorAnalytics\Reports;

/**
 * Returns the tabs for the analytics
 *
 * @since 1.0
 * @return array
 */
function dokan_get_analytics_tabs() {
    $tabs = array(
        'title'  => __( 'Analytics', 'dokan' ),
        'tabs' => array(
            "general"   => array(
                'title'       => __( 'General', 'dokan' ),
                'function'    => 'dokan_general_analytics'
            ),
            "pages"     => array(
                'title'       => __( 'Top pages', 'dokan' ),
                'function'    => 'dokan_page_analytics'
            ),
//            "activity"  => array(
//                'title'       => __( 'Activity', 'dokan' ),
//                'function'    => 'dokan_activity_analytics'
//            ),
            "geographic"=> array(
                'title'       => __( 'Location', 'dokan' ),
                'function'    => 'dokan_geographic_analytics'
            ),
            "system"    => array(
                'title'       => __( 'System', 'dokan' ),
                'function'    => 'dokan_system_analytics'
            ),
            "promotions"=> array(
                'title'       => __( 'Promotions', 'dokan' ),
                'function'    => 'dokan_promotion_analytics'
            ),
            "keyword"   => array(
                'title'       => __( 'Keyword', 'dokan' ),
                'function'    => 'dokan_keyword_analytics'
            )
        )
    );

    return apply_filters( 'dokan_analytics_tabs', $tabs );
}

/**
 * Returns date form
 *
 * @since 1.0
 * @since 3.7.23 Site Date format support added.
 *
 * @return void
 */
function dokan_analytics_date_form( $start_date, $end_date ) {
    $start_date_alt = dokan_format_date( $start_date );
    $end_date_alt = dokan_format_date( $end_date );
?>
    <form method="post" class="dokan-form-inline report-filter dokan-clearfix" action="">
        <div class="dokan-form-group">
            <input type="text" class="dokan-form-control dokan-daterangepicker" placeholder="<?php esc_attr_e( 'Select Date Range', 'dokan' ); ?>" value="<?php echo dokan_format_date( $start_date ) . ' - ' . dokan_format_date( $end_date ); ?>" autocomplete="off">
            <input type="hidden" name="start_date_alt" class="dokan-daterangepicker-start-date" value="<?php echo esc_attr( $start_date ); ?>" />
            <input type="hidden" name="end_date_alt" class="dokan-daterangepicker-end-date" value="<?php echo esc_attr( $end_date ); ?>" />
        </div>

        <div class="dokan-form-group">
            <?php wp_nonce_field( 'dokan_analytics_date', 'security' ); ?>
            <input type="submit" name="dokan_analytics_filter" class="dokan-btn dokan-btn-success dokan-btn-sm dokan-theme" value="<?php _e( 'Show', 'dokan' ); ?>" />
        </div>
    </form>
    <?php
}

/**
 * Returns general analytics
 *
 * @since 1.0
 * @since 3.7.23 GA4 Support Added.
 *
 * @return void
 */
function dokan_general_analytics() {
    $metrics    = 'activeUsers,sessions,screenPageViews,bounceRate,newUsers,averageSessionDuration';
    $dimensions = 'date';
    $sort       = 'sessions';

    list( $start_date, $end_date ) = dokan_vendor_analytics_date_form_handler();

    $Vendor_filter = new Reports();
    $result = $Vendor_filter->dokan_get_vendor_analytics( $start_date, $end_date, $metrics, $dimensions, $sort );

    dokan_analytics_date_form( $start_date, $end_date );

    if ( null === $result || is_wp_error( $result ) || empty( $result->getRowCount() ) ) {
        echo esc_html__( 'No Data Found!', 'dokan' );
        return;
    }

    $analytics_total = $vendor_analytics = array();

    foreach ( $result->getMetricHeaders() as $metric_header ) {
        $analytics_total[] = array(
            'header' => $metric_header->getName(),
            'value'   => 0,
        );
    }
    foreach ( $result->getRows() as $key => $row ) {
        $vendor_analytics[$key] = new stdClass();
        $vendor_analytics[$key]->post_date  = $row->getDimensionValues()[0]->getValue();
        $vendor_analytics[$key]->users      = $row->getMetricValues()[0]->getValue();
        $vendor_analytics[$key]->sessions   = $row->getMetricValues()[1]->getValue();

        foreach ( $row->getMetricValues() as $index => $metric ) {
            $analytics_total[$index]['value'] += $metric->getValue();
        }

    }

    $analytics_total = dokan_analytics_format_general_analytics_metric_value( $analytics_total, $result->getRowCount() );
    ?>

    <div id="poststuff" class="dokan-reports-wrap">
        <div class="dokan-analytics-sidebar report-left dokan-left">
            <ul class="chart-legend">
                <?php foreach ( $analytics_total as $analytics_row ) {
                    $value = is_numeric( $analytics_row['value'] ) ? ( new Formatter() )->round( $analytics_row['value'] ) : $analytics_row['value'];
                    printf( '<li><strong>%s</strong>%s</li>', esc_html( $value ), dokan_vendor_analytics_get_report_title( $analytics_row['header'] ) );
                } ?>
            </ul>
        </div>

        <div class="dokan-reports-main report-right dokan-right">
            <div class="postbox">
                <h3><span><?php esc_attr_e( 'Analytics', 'dokan' ); ?></span></h3>
                <?php dokan_analytics_overview_chart_data( $start_date, $end_date, 'day', $vendor_analytics ); ?>
            </div>
        </div>
    </div>

    <?php
}

/**
 * Format General analytics report values.
 *
 * @since 3.7.23
 *
 * @param array $metrics Metrics values and headers.
 * @param int   $row_count Row count.
 *
 * @return array
 */
function dokan_analytics_format_general_analytics_metric_value( array $metrics, int $row_count ): array {
    $formatted_metrics = array();
    foreach ( $metrics as $metric ) {
        switch (  $metric['header'] ) {
            case 'bounceRate':
                $metric['value'] = ( new Formatter() )->percentage( $metric['value'] / $row_count );
                break;
            case 'averageSessionDuration':
                $metric['value'] = ( new Formatter() )->round( $metric['value'] / $row_count );
                break;
        }
        $formatted_metrics[] = $metric;
    }

    return $formatted_metrics;
}

/**
 * Prepares chart data for sales overview
 *
 * @since 1.0
 *
 * @global WP_Locale $wp_locale WP locale.
 * @param string $start_date Start date.
 * @param string $end_date End date.
 * @param string $group_by Group by.
 * @param array $analytics Analytics data for charting.
 *
 * @return void
 */
function dokan_analytics_overview_chart_data( $start_date, $end_date, $group_by, $analytics ) {
    global $wp_locale;

    $start_date_to_time = dokan_current_datetime()->modify( $start_date )->getTimestamp();
    $end_date_to_time   = dokan_current_datetime()->modify( $end_date )->getTimestamp();
    $chart_interval     = dokan_get_interval_between_dates( $start_date_to_time, $end_date_to_time, $group_by );

    if ( $group_by == 'day' ) {
        $group_by_query       = 'YEAR(post_date), MONTH(post_date), DAY(post_date)';
        $barwidth             = 60 * 60 * 24 * 1000;
    } else {
        $group_by_query = 'YEAR(post_date), MONTH(post_date)';
        $chart_interval = 0;
        $barwidth             = 60 * 60 * 24 * 7 * 4 * 1000;
    }

    // Prepare data for report
    $user_counts    = dokan_prepare_chart_data( $analytics, 'post_date', 'users', $chart_interval, $start_date_to_time, $group_by );
    $session_counts = dokan_prepare_chart_data( $analytics, 'post_date', 'sessions', $chart_interval, $start_date_to_time, $group_by );

    // Encode in json format
    $chart_data = json_encode( array(
        'user_counts'      => array_values( $user_counts ),
        'session_counts'     => array_values( $session_counts )
    ) );

    $chart_colours = array(
        'user_counts'  => '#3498db',
        'session_counts'   => '#1abc9c'
    );

    ?>
    <div class="chart-container">
        <div class="chart-placeholder main" style="width: 100%; height: 350px;"></div>
    </div>

    <script type="text/javascript">
        jQuery(function($) {

            var analytics_data = JSON.parse( '<?php echo $chart_data; ?>' );
            var isRtl = '<?php echo is_rtl() ? "1" : "0"; ?>'

            var series = [
                {
                    label: "<?php echo esc_js( __( 'Users', 'dokan' ) ) ?>",
                    data: analytics_data.user_counts,
                    shadowSize: 0,
                    hoverable: true,
                    points: { show: true, radius: 5, lineWidth: 1, fillColor: '#fff', fill: true },
                    lines: { show: true, lineWidth: 2, fill: false },
                    shadowSize: 0,
                    append_tooltip: " <?php echo __( 'users', 'dokan' ); ?>"
                },
                {
                    label: "<?php echo esc_js( __( 'Sessions', 'dokan' ) ) ?>",
                    data: analytics_data.session_counts,
                    shadowSize: 0,
                    hoverable: true,
                    points: { show: true, radius: 5, lineWidth: 2, fillColor: '#fff', fill: true },
                    lines: { show: true, lineWidth: 3, fill: false },
                    shadowSize: 0,
                    append_tooltip: " <?php echo __( 'sessions', 'dokan' ); ?>"
                },
            ];

            var main_chart = jQuery.plot(
                jQuery('.chart-placeholder.main'),
                series,
                {
                    legend: {
                        show: true,
                        position: 'nw'
                    },
                    series: {
                        lines: { show: true, lineWidth: 4, fill: false },
                        points: { show: true }
                    },
                    grid: {
                        borderColor: '#eee',
                        color: '#aaa',
                        borderWidth: 1,
                        hoverable: true,
                        show: true,
                        aboveData: false,
                    },
                    xaxis: {
                        color: '#aaa',
                        position: "bottom",
                        tickColor: 'transparent',
                        mode: "time",
                        timeformat: "<?php if ( $group_by == 'day' ) echo '%d %b'; else echo '%b'; ?>",
                        monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
                        tickLength: 1,
                        minTickSize: [1, "<?php echo $group_by; ?>"],
                        font: {
                            color: "#aaa"
                        },
                        transform: function (v) { return ( isRtl == '1' ) ? -v : v; },
                        inverseTransform: function (v) { return ( isRtl == '1' ) ? -v : v; }
                    },
                    yaxes: [
                        {
                            position: ( isRtl == '1' ) ? "right" : "left",
                            min: 0,
                            minTickSize: 1,
                            tickDecimals: 0,
                            color: '#d4d9dc',
                            font: { color: "#aaa" }
                        },
                        {
                            position: ( isRtl == '1' ) ? "right" : "left",
                            min: 0,
                            tickDecimals: 2,
                            alignTicksWithAxis: 1,
                            color: 'transparent',
                            font: { color: "#aaa" }
                        }
                    ],
                    colors: ["<?php echo $chart_colours['user_counts']; ?>", "<?php echo $chart_colours['session_counts']; ?>"]
                }
            );

            jQuery('.chart-placeholder').resize();
        });

    </script>
    <?php
}

/**
 * Returns page analytics
 *
 * @since 1.0
 * @return void
 */
function dokan_page_analytics() {
    $metrics    = 'screenPageViews,averageSessionDuration,bounceRate';
    $dimensions = 'pageTitle,pagePath';
    $sort       = 'screenPageViews';
    $headers    = array(
        'pageTitle'              => __( 'Page Title', 'dokan' ),
        'pagePath'               => __( 'Page Path', 'dokan' ),
        'screenPageViews'        => __( 'Page Views', 'dokan' ),
        'averageSessionDuration' => __( 'Avg Time', 'dokan' ),
        'bounceRate'             => __( 'Bounce Rate', 'dokan' ),
    );

    $formatter = new Formatter();

    $formats = [
        'dimension' => [],
        'metric'    => [
            [ $formatter, 'round' ],
            [ $formatter, 'round' ],
            [ $formatter, 'percentage' ],
        ],
    ];


    $Vendor_filter = new Reports();
    $Vendor_filter->get_analytics_content( $metrics, $dimensions, $sort, $headers, $formats );
}

/**
 * Returns activity analytics
 *
 * @since 1.0
 * @return void
 */
function dokan_activity_analytics() {
    $metrics    = 'sessions';
    $dimensions = 'pageTitle,pagePath';
    $sort       = 'sessions';
    $headers = array(
        'pageTitle'     => __( 'Page Title', 'dokan' ),
        'pagePath'      => __( 'Page Path', 'dokan' ),
        'entrances'     => __( 'Session Start', 'dokan' ),
//        'exits'         => __( 'Exits', 'dokan' ),
//        'entranceRate'  => __( 'Entrance Rate', 'dokan' ),
//        'exitRate'      => __( 'Exit Rate', 'dokan' )
    );
    $Vendor_filter = new Reports();
    $Vendor_filter->get_analytics_content( $metrics, $dimensions, $sort, $headers );
}

/**
 * Returns geo analytics
 *
 * @since 1.0
 * @return void
 */
function dokan_geographic_analytics() {
    $metrics    = 'activeUsers,screenPageViews,averageSessionDuration,bounceRate';
    $dimensions = 'city,country';
    $sort       = 'activeUsers';
    $headers    = array(
        'city'                   => __( 'City', 'dokan' ),
        'country'                => __( 'Country', 'dokan' ),
        'activeUsers'            => __( 'Users', 'dokan' ),
        'screenPageViews'        => __( 'Page Views', 'dokan' ),
        'averageSessionDuration' => __( 'Avg Time', 'dokan' ),
        'bounceRate'             => __( 'Bounce Rate', 'dokan' ),
    );
    $formatter  = new Formatter();
    $formats    = [
        'dimension' => [],
        'metric'    => [
            [ $formatter, 'round' ],
            [ $formatter, 'round' ],
            [ $formatter, 'round' ],
            [ $formatter, 'percentage' ],
        ],
    ];

    list( $start_date, $end_date ) = dokan_vendor_analytics_date_form_handler();

    $reports = new Reports();

    $results = $reports->dokan_get_vendor_analytics( $start_date, $end_date, $metrics, $dimensions, $sort, [], 20 );

    if ( empty( $results ) || is_wp_error( $results ) ) {
        echo esc_html( $results->get_error_message() );
        return;
    }

    dokan_analytics_date_form( $start_date, $end_date );

    if ( empty( $results->getRowCount() ) ) {
        echo esc_html__( 'No Data Found!', 'dokan' );
        return;
    }

    $chart_data = array();


    foreach ( $results->getRows() as $row_data ) {
        $country = $row_data->getDimensionValues()[1]->getValue();
        $users = $row_data->getMetricValues()[0]->getValue();

        if ( ! isset( $chart_data[ $country ] ) ) {
            $chart_data[ $country ] = 0;
        }

        $chart_data[ $country ] += absint( $users );
    }

    $args = array(
        'is_vendor_analytics_views' => true,
        'results'                   => $results,
        'headers'                   => $headers,
        'rows'                      =>  ! empty( $results->getRows() ) ? $results->getRows() : array(),
        'formatters'                => $formats,
    );

    wp_enqueue_script( 'echarts-js' );
    wp_enqueue_script( 'echarts-js-map-world' );
    wp_enqueue_script( 'dokan-vendor-analytics-locations' );
    wp_localize_script( 'dokan-vendor-analytics-locations', 'dokanVendorAnalytics', array(
        'chart_data' => $chart_data,
    ) );

    dokan_get_template_part( 'location-map', '', $args );
}

/**
 * Returns system analytics
 *
 * @since 1.0
 * @return array
 */
function dokan_system_analytics() {
    $metrics       = 'screenPageViews';
    $dimensions    = 'browser,operatingSystem,operatingSystemVersion';
    $sort          = 'screenPageViews';
    $headers       = array(
        'browser'                => __( 'Browser', 'dokan' ),
        'operatingSystem'        => __( 'Operating System', 'dokan' ),
        'operatingSystemVersion' => __( 'OS Version', 'dokan' ),
        'sessions'               => __( 'Sessions', 'dokan' )
    );
    $Vendor_filter = new Reports();
    $Vendor_filter->get_analytics_content( $metrics, $dimensions, $sort, $headers );
}

/**
 * Returns promotion analytics
 *
 * @since 1.0
 * @return array
 */
function dokan_promotion_analytics() {
    $metrics    = 'sessions';
    $dimensions = 'source,medium,sourcePlatform';
    $sort       = 'sessions';
    $headers = array(
        'source'        => __( 'Source', 'dokan' ),
        'medium'        => __( 'Medium', 'dokan' ),
        'sourcePlatform' => __( 'Source Platform', 'dokan' ),
        'sessions'      => __( 'Sessions', 'dokan' ),
    );
    $Vendor_filter = new Reports();
    $Vendor_filter->get_analytics_content( $metrics, $dimensions, $sort, $headers );
}

/**
 * Returns keyword analytics
 *
 * @since 1.0
 * @return array
 */
function dokan_keyword_analytics() {
    $metrics    = 'sessions';
    $dimensions = 'googleAdsKeyword';
    $sort       = 'sessions';
    $headers = array(
        'keyword'   => __( 'Keyword', 'dokan' ),
        'sessions'  => __( 'Sessions', 'dokan' ),
    );
    $Vendor_filter = new Reports();
    $Vendor_filter->get_analytics_content( $metrics, $dimensions, $sort, $headers );
}

/**
 * Get dokan analytics app client_id
 *
 * @since 1.0.0
 *
 * @return string
 */
function dokan_vendor_analytics_client_id() {
    /**
     * Filter to change the client_id of the app
     *
     * @since 1.0.0
     *
     * @var string
     */
    return apply_filters( 'dokan_vendor_analytics_client_id', '171309425925-gv18udnrkk3jtquoivn98867q25o75eu.apps.googleusercontent.com' );
}

/**
 * Get dokan analytics app redirect url
 *
 * @since 1.0.0
 *
 * @return string
 */
function dokan_vendor_analytics_get_redirect_uri() {
    /**
     * Filter to change the redirect uri
     *
     * @since 1.0.0
     *
     * @var string
     */
    return apply_filters( 'dokan_vendor_analytics_redirect_uri', 'https://api.getdokan.com/vendor-analytics/redirect' );
}

/**
 * Get dokan analytics app refresh token url
 *
 * @since 1.0.0
 *
 * @return string
 */
function dokan_vendor_analytics_get_refresh_token_url() {
    /**
     * Filter to change the refresh token url
     *
     * @since 1.0.0
     *
     * @var string
     */
    return apply_filters( 'dokan_vendor_analytics_refresh_token_url', 'https://api.getdokan.com/vendor-analytics/refresh-token' );
}

/**
 * Get google auth url for dokan analytics app
 *
 * @since 1.0.0
 *
 * @return string
 */
function dokan_vendor_analytics_get_auth_url() {
    $url = 'https://accounts.google.com/o/oauth2/auth?';

    $state = site_url( '?wc-api=dokan_vendor_analytics' );

    $query = array(
        'next'            => $state,
        'scope'           => 'https://www.googleapis.com/auth/analytics.readonly',
        'response_type'   => 'code',
        'access_type'     => 'offline',
        'approval_prompt' => 'force',
    );

    $query['redirect_uri'] = dokan_vendor_analytics_get_redirect_uri();
    $query['client_id']    = dokan_vendor_analytics_client_id();
    $query['state']        = $state;

    return $url . http_build_query( $query );
}

/**
 * Get configured Dokan_Client
 *
 * @since 1.0.0
 *
 * @return Dokan_Client
 */
function dokan_vendor_analytics_client() {
    $client = new Dokan_Client();
    $client->setScopes( array( 'https://www.googleapis.com/auth/analytics.readonly' ) );
    $client->setAccessType( 'offline' );
    $client->setRedirectUri( dokan_vendor_analytics_get_redirect_uri() );
    $client->setClientId( dokan_vendor_analytics_client_id() );

    return $client;
}

/**
 * Get analytics token
 *
 * @since 1.0.0
 *
 * @return string
 */
function dokan_vendor_analytics_token() {
    $api_data = get_option( 'dokan_vendor_analytics_google_api_data', array() );
    $token    = ! empty( $api_data['token'] ) ? $api_data['token'] : '{}';

    return $token;
}

/**
 * Get a tokenized instance of Google_Client
 *
 * If token is expired, it'll refresh first.
 *
 * @since 3.0.5
 *
 * @return \Dokan_Client|\WP_Error
 */
function dokan_vendor_analytics_get_tokenized_client() {
    try {
        $client = dokan_vendor_analytics_client();
        $token  = dokan_vendor_analytics_token();

        if ( empty( json_decode( $token, true ) ) ) {
            throw new Exception( __( 'Token is empty', 'dokan' ) );
        }

        $client->setAccessToken( $token );

        if ( $client->isAccessTokenExpired() ) {
            $refresh_token = $client->getRefreshToken();

            $response = wp_remote_post( dokan_vendor_analytics_get_refresh_token_url(), array(
                'timeout' => 30,
                'body'    => array(
                    'refresh_token' => $refresh_token,
                )
            ) );

            if ( is_wp_error( $response ) ) {
                throw new Exception( $response->get_error_message() );
            }

            $token = wp_remote_retrieve_body( $response );

            if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
                throw new Exception( $token );
            }

            $client->setAccessToken( $token );

            $api_data          = get_option( 'dokan_vendor_analytics_google_api_data', array() );
            $api_data['token'] = $token;

            update_option( 'dokan_vendor_analytics_google_api_data', $api_data, false );
        }

        return $client;
    } catch ( Exception $e ) {
        return new WP_Error(
            'dokan_vendor_analytics_get_tokenized_client_error',
            $e->getMessage()
        );
    }
}

/**
 * Get analytics profiles from Google API
 *
 * @since 3.0.5
 *
 * @return array|\WP_Error
 */
function dokan_vendor_analytics_api_get_profiles() {
    try {
        $client = dokan_vendor_analytics_get_tokenized_client();

        if ( is_wp_error( $client ) ) {
            throw new Exception( $client->get_error_message() );
        }

        $analytics    = dokan_vendor_analytics_admin_service_client();
        $profiles     = [];
        $profiles_map = [];
        $accounts     = $analytics->listAccountSummaries();

        foreach ( $accounts->iterateAllElements() as $account ) {
            $summaries = $account->getPropertySummaries();
            $group     = array(
                'group_label'  => $account->getDisplayName(),
                'group_values' => array(),
            );
            foreach ( $summaries as $summary ) {
                $streams = $analytics->listDataStreams( $summary->getProperty() );

                foreach ( $streams->iterateAllElements() as $stream ) {
                    if ( empty( $stream->getWebStreamData() ) ) {
                        continue;
                    }
                    $group['group_values'][] = array(
                        'label' => $summary->getDisplayName() . ' (' . trim( $summary->getProperty(), 'properties/' ) . ') - ' . $stream->getWebStreamData()->getMeasurementId(),
                        'value' => $stream->getName(),
                    );
                    $profiles_map[ $stream->getName() ] = $stream->getWebStreamData()->getMeasurementId();
                }

            }
            $profiles[] = $group;
        }

        if ( empty( $profiles) ) {
            return $profiles;
        }

        $api_data                 = get_option( 'dokan_vendor_analytics_google_api_data', array() );
        $api_data['profiles']     = $profiles;
        $api_data['profiles_map'] = $profiles_map;

        update_option( 'dokan_vendor_analytics_google_api_data', $api_data, false );

        return $profiles;
    } catch ( Exception $e ) {
        return new WP_Error(
            'dokan_vendor_analytics_get_tokenized_client_error',
            $e->getMessage()
        );
    }
}

/**
 * Get user readable title from title key.
 *
 * @since 3.3.7
 *
 * @param string $title_key
 *
 * @return string
 */
function dokan_vendor_analytics_get_report_title( $title_key ): string {
    switch ( $title_key ) {
        case 'activeUsers':
            $title = __( 'Active Users', 'dokan' );
            break;
        case 'sessions':
            $title = __( 'Sessions', 'dokan' );
            break;
        case 'screenPageViews':
            $title = __( 'Page Views', 'dokan' );
            break;
        case 'bounceRate':
            $title = __( 'Bounce Rate', 'dokan' );
            break;
        case 'newUsers':
            $title = __( 'New Users', 'dokan' );
            break;
        case 'averageSessionDuration':
            $title = __( 'Average Session Duration', 'dokan' );
            break;
        default:
            $title = $title_key;
    }

    return $title;
}

/**
 * Vendor Analytics Data Client.
 *
 * @since 3.7.23
 *
 * @return BetaAnalyticsDataClient
 * @throws ValidationException|Exception
 */
function dokan_vendor_analytics_service_client(): BetaAnalyticsDataClient {
    $client = dokan_vendor_analytics_get_tokenized_client();

    if ( is_wp_error( $client ) ) {
        throw new Exception( $client->get_error_message() );
    }

    return new BetaAnalyticsDataClient( [
        'credentials' => new Google\ApiCore\CredentialsWrapper( new Token( $client ) ),
    ] );
}

/**
 * Vendor Analytics admin Data Client.
 *
 * @since 3.7.23
 *
 * @return AnalyticsAdminServiceClient
 * @throws ValidationException|Exception
 */
function dokan_vendor_analytics_admin_service_client(): AnalyticsAdminServiceClient {
    $client = dokan_vendor_analytics_get_tokenized_client();

    if ( is_wp_error( $client ) ) {
        throw new Exception( $client->get_error_message() );
    }

    return new AnalyticsAdminServiceClient( [
        'credentials' => new Google\ApiCore\CredentialsWrapper( new Token( $client ) ),
    ] );
}

/**
 * Vendor Analytics Date form Handler.
 *
 * @since 3.7.23
 * @since 3.7.25 Start date is set to 31 days ago and end date is set yesterday. Ranging last 30 days.
 *
 * @return array
 */
function dokan_vendor_analytics_date_form_handler(): array {
    $start_date = dokan_current_datetime()->modify( '-31 day' )->format( 'Y-m-d' );
    $end_date   = dokan_current_datetime()->modify( 'midnight yesterday' )->format( 'Y-m-d' );

    if (
        isset( $_POST['dokan_analytics_filter'] )
        && isset( $_POST['security'] )
        && wp_verify_nonce(
            sanitize_key( wp_unslash( $_POST['security'] ) ),
            'dokan_analytics_date'
        )
    ) {
        $start_date = ! empty( $_POST['start_date_alt'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date_alt'] ) ) : $start_date;
        $end_date   = ! empty( $_POST['end_date_alt'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date_alt'] ) ) : $end_date;
    }

    return [ $start_date, $end_date ];
}

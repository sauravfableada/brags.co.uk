<?php

namespace WeDevs\DokanPro\Modules\VendorAnalytics\Api;

use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\DokanPro\Modules\VendorAnalytics\Formatter;
use WeDevs\DokanPro\Modules\VendorAnalytics\Reports;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Vendor Analytics REST Controller.
 *
 * Proxies Google Analytics GA4 data for the vendor dashboard React app.
 *
 * @since 5.0.0
 */
class VendorAnalyticsController extends DokanBaseVendorController {

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'vendor/analytics';

    /**
     * Register routes.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_analytics' ],
                    'args'                => $this->get_collection_params(),
                    'permission_callback' => [ $this, 'check_permission' ],
                ],
            ]
        );
    }

    /**
     * Get analytics data for the requested tab.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function get_analytics( WP_REST_Request $request ) {
        $tab      = $request->get_param( 'tab' );
        $page     = (int) $request->get_param( 'page' );
        $per_page = (int) $request->get_param( 'per_page' );

        list( $default_start, $default_end ) = dokan_vendor_analytics_date_form_handler();

        $start_date = $request->get_param( 'start_date' );
        $end_date   = $request->get_param( 'end_date' );
        if ( empty( $start_date ) ) {
            $start_date = $default_start;
        }
        if ( empty( $end_date ) ) {
            $end_date = $default_end;
        }

        $tab_config = $this->get_tab_config( $tab );

        if ( is_wp_error( $tab_config ) ) {
            return $tab_config;
        }

        $reports = new Reports();
        $limit   = $tab === 'general' ? false : $per_page;
        $offset  = $tab === 'general' ? false : ( $page - 1 ) * $per_page;

        $result = $reports->dokan_get_vendor_analytics(
            $start_date,
            $end_date,
            $tab_config['metrics'],
            $tab_config['dimensions'],
            $tab_config['sort'],
            [],
            $limit,
            $offset
        );

        if ( is_wp_error( $result ) ) {
            return new WP_Error(
                'analytics_api_error',
                $result->get_error_message(),
                [ 'status' => 500 ]
            );
        }

        if ( null === $result ) {
            return new WP_Error(
                'analytics_not_configured',
                __( 'Google Analytics is not configured. Please connect your Google Analytics account in Dokan Settings.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        $data = $this->format_response( $tab, $tab_config, $result );

        $data['start_date'] = $start_date;
        $data['end_date']   = $end_date;

        $response    = rest_ensure_response( $data );
        $total_items = $result->getRowCount() ?? 0;

        if ( $tab !== 'general' && $total_items > 0 ) {
            $response->header( 'X-WP-Total', (int) $total_items );
            $response->header( 'X-WP-TotalPages', (int) ceil( $total_items / $per_page ) );
        }

        return $response;
    }

    /**
     * Get query params for collection.
     *
     * @since 5.0.0
     *
     * @return array
     */
    public function get_collection_params(): array {
        return [
            'tab'        => [
                'description'       => __( 'Analytics tab to query.', 'dokan' ),
                'type'              => 'string',
                'required'          => true,
                'enum'              => [ 'general', 'pages', 'geographic', 'system', 'promotions', 'keyword' ],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'start_date' => [
                'description'       => __( 'Start date in Y-m-d format.', 'dokan' ),
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => [ $this, 'validate_date_param' ],
            ],
            'end_date'   => [
                'description'       => __( 'End date in Y-m-d format.', 'dokan' ),
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => [ $this, 'validate_date_param' ],
            ],
            'page'       => [
                'description' => __( 'Current page of the collection.', 'dokan' ),
                'type'        => 'integer',
                'default'     => 1,
                'minimum'     => 1,
            ],
            'per_page'   => [
                'description' => __( 'Maximum number of items to return.', 'dokan' ),
                'type'        => 'integer',
                'default'     => 10,
                'minimum'     => 1,
                'maximum'     => 100,
            ],
        ];
    }

    /**
     * Validate a date parameter is in Y-m-d format.
     *
     * @since 5.0.0
     *
     * @param string          $value   Date string to validate.
     * @param WP_REST_Request $request Full details about the request.
     * @param string          $key     Parameter key.
     *
     * @return true|WP_Error
     */
    public function validate_date_param( $value, $request, $key ) {
        if ( empty( $value ) ) {
            return true;
        }

        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
            return new WP_Error(
                'rest_invalid_param',
                /* translators: 1: parameter key */
                sprintf( __( '%s must be in Y-m-d format (e.g. 2024-01-31).', 'dokan' ), $key ),
                [ 'status' => 400 ]
            );
        }

        return true;
    }

    /**
     * Get GA4 query configuration for a given tab.
     *
     * @since 5.0.0
     *
     * @param string $tab Tab identifier.
     *
     * @return array|WP_Error
     */
    protected function get_tab_config( string $tab ) {
        $configs = [
            'general'    => [
                'metrics'    => 'activeUsers,sessions,screenPageViews,bounceRate,newUsers,averageSessionDuration',
                'dimensions' => 'date',
                'sort'       => 'sessions',
                'headers'    => [],
                'formatters' => [
                    'metric' => [
                        'bounceRate'             => 'percentage',
                        'averageSessionDuration' => 'round',
                    ],
                ],
            ],
            'pages'      => [
                'metrics'    => 'screenPageViews,averageSessionDuration,bounceRate',
                'dimensions' => 'pageTitle,pagePath',
                'sort'       => 'screenPageViews',
                'headers'    => [
                    [
                        'key' => 'pageTitle',
                        'label' => __( 'Page Title', 'dokan' ),
                        'type' => 'dimension',
                    ],
                    [
                        'key' => 'pagePath',
                        'label' => __( 'Page Path', 'dokan' ),
                        'type' => 'dimension',
                    ],
                    [
                        'key' => 'screenPageViews',
                        'label' => __( 'Page Views', 'dokan' ),
                        'type' => 'metric',
                    ],
                    [
                        'key' => 'averageSessionDuration',
                        'label' => __( 'Avg Time', 'dokan' ),
                        'type' => 'metric',
                    ],
                    [
                        'key' => 'bounceRate',
                        'label' => __( 'Bounce Rate', 'dokan' ),
                        'type' => 'metric',
                    ],
                ],
                'formatters' => [
                    'metric' => [
                        'screenPageViews'        => 'round',
                        'averageSessionDuration' => 'round',
                        'bounceRate'             => 'percentage',
                    ],
                ],
            ],
            'geographic' => [
                'metrics'    => 'activeUsers,screenPageViews,averageSessionDuration,bounceRate',
                'dimensions' => 'city,country',
                'sort'       => 'activeUsers',
                'headers'    => [
                    [
                        'key' => 'city',
                        'label' => __( 'City', 'dokan' ),
                        'type' => 'dimension',
                    ],
                    [
                        'key' => 'country',
                        'label' => __( 'Country', 'dokan' ),
                        'type' => 'dimension',
                    ],
                    [
                        'key' => 'activeUsers',
                        'label' => __( 'Users', 'dokan' ),
                        'type' => 'metric',
                    ],
                    [
                        'key' => 'screenPageViews',
                        'label' => __( 'Page Views', 'dokan' ),
                        'type' => 'metric',
                    ],
                    [
                        'key' => 'averageSessionDuration',
                        'label' => __( 'Avg Time', 'dokan' ),
                        'type' => 'metric',
                    ],
                    [
                        'key' => 'bounceRate',
                        'label' => __( 'Bounce Rate', 'dokan' ),
                        'type' => 'metric',
                    ],
                ],
                'formatters' => [
                    'metric' => [
                        'activeUsers'            => 'round',
                        'screenPageViews'        => 'round',
                        'averageSessionDuration' => 'round',
                        'bounceRate'             => 'percentage',
                    ],
                ],
            ],
            'system'     => [
                'metrics'    => 'screenPageViews',
                'dimensions' => 'browser,operatingSystem,operatingSystemVersion',
                'sort'       => 'screenPageViews',
                'headers'    => [
                    [
                        'key' => 'browser',
                        'label' => __( 'Browser', 'dokan' ),
                        'type' => 'dimension',
                    ],
                    [
                        'key' => 'operatingSystem',
                        'label' => __( 'Operating System', 'dokan' ),
                        'type' => 'dimension',
                    ],
                    [
                        'key' => 'operatingSystemVersion',
                        'label' => __( 'OS Version', 'dokan' ),
                        'type' => 'dimension',
                    ],
                    [
                        'key' => 'screenPageViews',
                        'label' => __( 'Sessions', 'dokan' ),
                        'type' => 'metric',
                    ],
                ],
                'formatters' => [
                    'metric' => [],
                ],
            ],
            'promotions' => [
                'metrics'    => 'sessions',
                'dimensions' => 'source,medium,sourcePlatform',
                'sort'       => 'sessions',
                'headers'    => [
                    [
                        'key' => 'source',
                        'label' => __( 'Source', 'dokan' ),
                        'type' => 'dimension',
                    ],
                    [
                        'key' => 'medium',
                        'label' => __( 'Medium', 'dokan' ),
                        'type' => 'dimension',
                    ],
                    [
                        'key' => 'sourcePlatform',
                        'label' => __( 'Source Platform', 'dokan' ),
                        'type' => 'dimension',
                    ],
                    [
                        'key' => 'sessions',
                        'label' => __( 'Sessions', 'dokan' ),
                        'type' => 'metric',
                    ],
                ],
                'formatters' => [
                    'metric' => [],
                ],
            ],
            'keyword'    => [
                'metrics'    => 'sessions',
                'dimensions' => 'googleAdsKeyword',
                'sort'       => 'sessions',
                'headers'    => [
                    [
                        'key' => 'googleAdsKeyword',
                        'label' => __( 'Keyword', 'dokan' ),
                        'type' => 'dimension',
                    ],
                    [
                        'key' => 'sessions',
                        'label' => __( 'Sessions', 'dokan' ),
                        'type' => 'metric',
                    ],
                ],
                'formatters' => [
                    'metric' => [],
                ],
            ],
        ];

        if ( ! isset( $configs[ $tab ] ) ) {
            return new WP_Error(
                'invalid_tab',
                __( 'Invalid analytics tab.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        return $configs[ $tab ];
    }

    /**
     * Format GA4 RunReportResponse into JSON-serializable data.
     *
     * @since 5.0.0
     *
     * @param string $tab        Tab identifier.
     * @param array  $tab_config Tab configuration.
     * @param mixed  $result     GA4 RunReportResponse.
     *
     * @return array
     */
    protected function format_response( string $tab, array $tab_config, $result ): array {
        $formatter        = new Formatter();
        $dimension_keys   = array_map( 'trim', explode( ',', $tab_config['dimensions'] ) );
        $metric_keys      = array_map( 'trim', explode( ',', $tab_config['metrics'] ) );
        $metric_formatter = $tab_config['formatters']['metric'] ?? [];

        if ( $tab === 'general' ) {
            return $this->format_general_response( $result, $metric_keys, $metric_formatter, $formatter );
        }

        $rows = [];

        if ( ! empty( $result->getRows() ) ) {
            foreach ( $result->getRows() as $row ) {
                $row_data = [];

                foreach ( $row->getDimensionValues() as $index => $dimension ) {
                    $key             = $dimension_keys[ $index ] ?? "dimension_{$index}";
                    $row_data[ $key ] = $dimension->getValue();
                }

                foreach ( $row->getMetricValues() as $index => $metric ) {
                    $key   = $metric_keys[ $index ] ?? "metric_{$index}";
                    $value = $metric->getValue();

                    if ( isset( $metric_formatter[ $key ] ) ) {
                        $value = $metric_formatter[ $key ] === 'percentage'
                            ? $formatter->percentage( $value )
                            : $formatter->round( $value );
                    }

                    $row_data[ $key ] = $value;
                }

                $rows[] = $row_data;
            }
        }

        $data = [
            'headers' => $tab_config['headers'],
            'rows'    => $rows,
        ];

        if ( $tab === 'geographic' ) {
            $map_data = [];

            foreach ( $rows as $row ) {
                $country = $row['country'] ?? '';
                $users   = absint( $row['activeUsers'] ?? 0 );

                if ( ! isset( $map_data[ $country ] ) ) {
                    $map_data[ $country ] = 0;
                }

                $map_data[ $country ] += $users;
            }

            $data['map_data'] = $map_data;
        }

        return $data;
    }

    /**
     * Format general tab response with summary stats and chart data.
     *
     * @since 5.0.0
     *
     * @param mixed     $result           GA4 RunReportResponse.
     * @param array     $metric_keys      Metric key names.
     * @param array     $metric_formatter Metric formatters.
     * @param Formatter $formatter        Formatter instance.
     *
     * @return array
     */
    protected function format_general_response( $result, array $metric_keys, array $metric_formatter, Formatter $formatter ): array {
        $summary   = [];
        $chart     = [];
        $totals    = array_fill_keys( $metric_keys, 0 );
        $row_count = $result->getRowCount() ?? 0;

        $metric_labels = [
            'activeUsers'            => __( 'Active Users', 'dokan' ),
            'sessions'               => __( 'Sessions', 'dokan' ),
            'screenPageViews'        => __( 'Page Views', 'dokan' ),
            'bounceRate'             => __( 'Bounce Rate', 'dokan' ),
            'newUsers'               => __( 'New Users', 'dokan' ),
            'averageSessionDuration' => __( 'Average Session Duration', 'dokan' ),
        ];

        if ( ! empty( $result->getRows() ) ) {
            foreach ( $result->getRows() as $row ) {
                $date     = $row->getDimensionValues()[0]->getValue();
                $users    = (int) $row->getMetricValues()[0]->getValue();
                $sessions = (int) $row->getMetricValues()[1]->getValue();

                $chart[] = [
                    'date'     => $date,
                    'users'    => $users,
                    'sessions' => $sessions,
                ];

                foreach ( $row->getMetricValues() as $index => $metric ) {
                    $key = $metric_keys[ $index ] ?? '';

                    if ( isset( $totals[ $key ] ) ) {
                        $totals[ $key ] += (float) $metric->getValue();
                    }
                }
            }
        }

        foreach ( $metric_keys as $key ) {
            $value = $totals[ $key ];

            if ( $row_count > 0 ) {
                if ( $key === 'bounceRate' ) {
                    $value = $formatter->percentage( $value / $row_count );
                } elseif ( $key === 'averageSessionDuration' ) {
                    $value = $formatter->round( $value / $row_count );
                } else {
                    $value = $formatter->round( $value );
                }
            }

            $summary[] = [
                'key'   => $key,
                'label' => $metric_labels[ $key ] ?? $key,
                'value' => (string) $value,
            ];
        }

        return [
            'summary' => $summary,
            'chart'   => $chart,
        ];
    }
}

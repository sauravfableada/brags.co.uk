<?php

namespace WeDevs\DokanPro\Modules\VendorAnalytics;

use Exception;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\FilterExpressionList;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\Analytics\Data\V1beta\RunReportResponse;
use WeDevs\Dokan\Vendor\Vendor;
use WP_Error;

/**
 * Reports class
 *
 * @since 1.0.0
 * @since 3.7.23 Renamed the class and added namespace.
 */
class Reports {
    /**
     * Handle product for staff uploading and editing
     *
     * @since 1.0.0
     * @since 3.7.23 Return type changed to array.
     *
     * @return array
     */
    public function filter_page_path( array $filter ): array {
        if ( ! is_user_logged_in() ) {
            return $filter;
        }

        if ( ! current_user_can( 'dokandar' ) ) {
            return $filter;
        }

        $vendor            = new Vendor( dokan_get_current_user_id() );
        $store_url_query   = str_replace( home_url(), '', $vendor->get_shop_url() );
        /**
         * @var \WP_Query $products_query
         */
        $products_query    = $vendor->get_products();
        $products          = $products_query->get_posts();
        $product_url_query = [];

        if ( count( $products ) ) {
            foreach ( $products as $product ) {
                $product_url_query[] = str_replace( home_url(), '', get_permalink( $product->ID ) );
            }
        }

        return array_merge( $filter, $product_url_query, [ $store_url_query ] );
    }

    /**
     * Handle load analytics connection
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function load_dokan_vendor_analytics(): ?array {
        include_once DOKAN_VENDOR_ANALYTICS_TOOLS_DIR . '/src/Dokan/autoload.php';

        $client = dokan_vendor_analytics_client();
        $token  = dokan_vendor_analytics_token();

        if ( empty( json_decode( $token, true ) ) ) {
            return null;
        }

        $client->setAccessToken( $token );

        if ( $client->isAccessTokenExpired() ) {
            $refresh_token = $client->getRefreshToken();

            $response = wp_remote_post(
                dokan_vendor_analytics_get_refresh_token_url(), array(
					'body' => array(
						'refresh_token' => $refresh_token,
					),
                )
            );

            if ( is_wp_error( $response ) ) {
                dokan_log( $response->get_error_message() );
                return null;
            }

            $token = wp_remote_retrieve_body( $response );

            $client->setAccessToken( $token );

            $api_data  = get_option( 'dokan_vendor_analytics_google_api_data', array() );
            $api_data['token'] = $token;

            update_option( 'dokan_vendor_analytics_google_api_data', $api_data, false );
        }

        $profile_id = dokan_get_option( 'profile', 'dokan_vendor_analytics', null );

        if ( ! empty( $profile_id ) ) {
            $stream     = explode( '/', $profile_id );
            $profile_id = implode( '/', [ $stream[0], $stream[1] ] );
        }

        return array(
			'client' => $client,
			'profile_id' => $profile_id,
		);
    }

    /**
     * Get dokan vendor analytics
     *
     * @since 1.0.0
     *
     * @param string $end_date
     * @param string $metrics
     * @param bool $dimensions
     * @param bool $sort
     * @param array $filter
     * @param bool $limit
     *
     * @param string $start_date
     *
     * @return RunReportResponse|WP_Error|null
     */
    public function dokan_get_vendor_analytics( $start_date = '30daysAgo', $end_date = 'today', $metrics = 'activeUsers', $dimensions = 'date', $sort = false, $filter = [], $limit = false, $offset = false ) {
        try {
            list( 'client' => $client, 'profile_id' => $profile_id ) = $this->load_dokan_vendor_analytics();

            if ( empty( $client ) ) {
                return new WP_Error( 400, esc_html__( 'No Data Found!', 'dokan' ) );
            }

            $analytics = dokan_vendor_analytics_service_client();

            $date_range = new DateRange();
            $date_range->setStartDate( $start_date )->setEndDate( $end_date );

            $filter_paths                 = $this->filter_page_path( $filter );
            $dimension_filter_expressions = [];

            foreach ( $filter_paths as $path ) {
                $dimension_filter_expressions[] = new FilterExpression(
                    [
						'filter' => new Filter(
                            [
								'field_name'    => 'pagePath',
								'string_filter' => new StringFilter(
                                    [
										'match_type' => MatchType::EXACT,
										'value'      => $path,
                                    ]
                                ),
							]
                        ),
					]
                );
            }

            $dimension_filter = new FilterExpression(
                [
					'or_group' => new FilterExpressionList(
                        [
							'expressions' => $dimension_filter_expressions,
						]
                    ),
				]
            );

            $metrics_array = [];
            foreach ( explode( ',', $metrics ) as $metric ) {
                $metrics_array[] = new Metric( [ 'name' => trim( $metric ) ] );
            }

            $dimensions_array = [];
            foreach ( explode( ',', $dimensions ) as $dimension ) {
                $dimensions_array[] = new Dimension( [ 'name' => trim( $dimension ) ] );
            }

            $report_array = [
                'property'   => $profile_id,
                'dateRanges' => [ $date_range ],
                'dimensions' => $dimensions_array,
                'metrics'    => $metrics_array,
            ];

            if ( ! empty( $dimension_filter_expressions ) ) {
                $report_array['dimensionFilter'] = $dimension_filter;
            }

            if ( $limit ) {
                $report_array['limit'] = $limit;
            }

            if ( $offset ) {
                $report_array['offset'] = $offset;
            }

            if ( $sort ) {
                $report_array['orderBys'] = [
                    new OrderBy(
                        [
							'metric' => new MetricOrderBy(
                                [
									'metric_name' => $sort,
								]
                            ),
							'desc'   => true,
						]
                    ),
                ];
            }

            return $analytics->runReport( $report_array );
        } catch ( Exception $e ) {
            return new WP_Error(
                'dokan_get_vendor_analytics',
                $e->getMessage()
            );
        }
    }

    /**
     * Analytics content
     *
     * @since 1.0.0
     */
    public function get_analytics_content( $metrics, $dimensions, $sort, $headers, $formatters = [
		'dimension' => [],
		'metric' => [],
	] ) {
        list( $start_date, $end_date ) = dokan_vendor_analytics_date_form_handler();

        $result = $this->dokan_get_vendor_analytics( $start_date, $end_date, $metrics, $dimensions, $sort, [], 5 );

        dokan_analytics_date_form( $start_date, $end_date );

        if ( is_wp_error( $result ) || ! $result || ! $result->getRows() ) {
            esc_html_e( 'No Data Found!', 'dokan' );
            return;
        }

        if ( ! empty( $result->getRowCount() ) ) {
            ?>
            <table class="table table-striped" style='table-layout: fixed; width: 100%'>
                <thead>
                <tr>
                    <?php
                    foreach ( $headers as $header ) {
                        echo '<th>' . esc_html( $header ) . '</th>';
                    }
                    ?>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ( $result->getRows() as $row ) :
                    ?>
                    <tr>
                        <?php
                        foreach ( $row->getDimensionValues() as $index => $dimension ) {
                            $dimension_value = ! empty( $formatters['dimension'][ $index ] ) && is_callable( $formatters['dimension'][ $index ] ) ? call_user_func( $formatters['dimension'][ $index ], $dimension->getValue() ) : $dimension->getValue();

                            echo '<td style="word-wrap: break-word">' . esc_html( $dimension_value ) . '</td>';
                        }
                        foreach ( $row->getMetricValues() as $index => $metric ) {
                            $metric_value = ! empty( $formatters['metric'][ $index ] ) && is_callable( $formatters['metric'][ $index ] ) ? call_user_func( $formatters['metric'][ $index ], $metric->getValue() ) : $metric->getValue();

                            echo '<td style="word-wrap: break-word">' . esc_html( $metric_value ) . '</td>';
                        }
                        ?>
                    </tr>
					<?php
                endforeach;
                ?>
                </tbody>
            </table>
            <?php
        } else {
            esc_html_e( 'No data found.', 'dokan' );
        }
    }
}

<div id="dokan-vendor-analytics-location-map" style="height: 340px"></div>

<table class="table table-striped">
    <thead>
        <tr>
            <?php foreach( $headers as $header ): ?>
                <th><?php echo esc_html( $header ); ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach( $rows as $row ): ?>
            <tr>
                <?php
                foreach ( $row->getDimensionValues() as $index => $dimension ) {
                    $dimension_value = ! empty( $formatters['dimension'][ $index ] ) && is_callable( $formatters['dimension'][ $index ] ) ? call_user_func( $formatters['dimension'][ $index ], $dimension->getValue() ) : $dimension->getValue();
                    echo '<td>' . esc_html( $dimension_value ) . '</td>';
                }
                foreach ( $row->getMetricValues() as $index => $metric ) {
                    $metric_value = ! empty( $formatters['metric'][ $index ] ) && is_callable( $formatters['metric'][ $index ] ) ? call_user_func( $formatters['metric'][ $index ], $metric->getValue() ) : $metric->getValue();
                    echo '<td>' . esc_html( $metric_value ) . '</td>';
                }
                ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

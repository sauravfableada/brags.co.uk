<?php

namespace WeDevs\DokanPro\Modules\Printful\Processors;

/**
 * Class SizeGuideDataProcessor.
 *
 * @since 3.13.0
 */
class SizeGuideDataProcessor {

    /**
     * Size Guide Data.
     *
     * @var array
     */
    public array $data = [];


    /**
     * Class Constructor.
     *
     * @since 3.13.0
     *
     * @param array $data Data
     */
    public function __construct( array $data ) {
        $this->format( $data );
    }

    /**
     * Data Getter.
     *
     * @since 3.13.0
     *
     * @since 3.13.0
     *
     * @return array $this->data
     */
    public function get_data(): array {
        return $this->data;
    }

    /**
     * Data Formater.
     *
     * @since 3.13.0
     *
     * @param array $input Input data
     *
     * @return object $this
     */
    protected function format( array $input = [] ): object {
        $output = [
            'measure_yourself' => [
                'inches' => [],
                'cm'     => []
            ],
            'product_measure' => [
                'inches' => [],
                'cm'     => []
            ]
        ];

        if ( empty( $input ) ) {
            $this->data = $output;

            return $this;
        }

        foreach ( $input['size_tables'] as $table ) {
            $type     = $table['type'] ?? '';
            $unit     = $table['unit'] ?? '';
            $desc     = $table['description'] ?? '';
            $img_url  = $table['image_url'] ?? '';
            $img_desc = $table['image_description'] ?? '';

            foreach ( $table['measurements'] as $measurement ) {
                $typeLabel = $measurement['type_label'] ?? '';

                $output[ $type ]['description']       = $desc;
                $output[ $type ]['image_url']         = $img_url;
                $output[ $type ]['image_description'] = $img_desc;

                foreach ( $measurement['values'] as $value ) {
                    $size = strtolower( $value['size'] );

                    if ( ! isset( $output[ $type ][ $unit ][ $size ] ) ) {
                        $output[ $type ][ $unit ][ $size ] = [
                            'Size' => strtoupper( $size )
                        ];
                    }

                    if ( isset( $value['value'] ) ) {
                        $output[ $type ][ $unit ][ $size ][ $typeLabel ] = $value['value'];
                    }

                    if ( isset( $value['min_value'] ) && isset( $value['max_value'] ) ) {
                        $output[ $type ][ $unit ][ $size ][ $typeLabel ] = $value['min_value'] . ' - ' . $value['max_value'];
                    }
                }

            }
        }

        // Remove empty output type values.
        foreach ( $output as $type_key => $type_values ) {
            if ( ! empty( array_filter ( $type_values ) ) ) {
                continue;
            }

            unset( $output[ $type_key ] );
        }

        $this->data = $output;

        return $this;
    }

    /**
     * Get Size Measurement Table.
     *
     * @since 3.13.0
     *
     * @param string $measurement_type Measurement
     * @param string $unit             Unit
     *
     * @return string Size Measurement Table
     */
    public function get_table( string $measurement_type, string $unit ): string {
        $measurement_units = $this->data[ $measurement_type ][ $unit ];

        // Check if the measure and unit exist
        if ( ! $measurement_units ) {
            return esc_html__( "No data available.", 'dokan' );
        }

        // Determine the columns (keys) to be used in the table based on the first element
        $columns = array_keys( $measurement_units[ array_key_first( $measurement_units ) ] );

        // Start building the HTML table
        $table = "<table>";
        $table .= "<thead>";
        $table .= "<tr>";

        // Create table headers based on the determined columns
        foreach ( $columns as $column ) {
            $table .= "<th>" . ucfirst( $column ) . "</th>";
        }

        $table .= "</tr>";
        $table .= "</thead>";
        $table .= "<tbody>";

        // Populate the table rows with data
        foreach ( $measurement_units as $sizeData ) {
            $table .= "<tr>";
            foreach ( $columns as $column ) {
                $table .= "<td>" . $sizeData[ $column ] . "</td>";
            }
            $table .= "</tr>";
        }

        $table .= "</tbody>";
        $table .= "</table>";

        return $table;
    }
}

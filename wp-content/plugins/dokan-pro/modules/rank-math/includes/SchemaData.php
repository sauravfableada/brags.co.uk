<?php

namespace WeDevs\DokanPro\Modules\RankMath;

use RankMath\Helper;
use RankMath\Schema\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Shared Rank Math schema-payload builder.
 *
 * Kept in a trait so both Schema (page-load localize) and RankMathController
 * (SPA re-point) reuse one implementation while keeping it protected — a
 * protected method on Schema would be unreachable from the unrelated controller.
 *
 * Prefixed get_product_* so these static methods don't shadow Admin's non-static ones.
 *
 * @since 5.0.3
 */
trait SchemaData {

    /**
     * Saved schemas for a product, or its default schema when none is saved.
     *
     * @since 5.0.3
     *
     * @param int $post_id Product id.
     *
     * @return array
     */
    protected static function get_product_schema_data( $post_id ) {
        $schemas = DB::get_schemas( $post_id );
        if ( ! empty( $schemas ) ) {
            return $schemas;
        }

        // No saved schema: fall back to the post type's default, like Rank Math.
        $default_type = self::get_product_default_schema_type( $post_id );
        if ( ! $default_type ) {
            return [];
        }

        $schemas['new-9999'] = [
            '@type'    => $default_type,
            'metadata' => [
                'title'     => Helper::sanitize_schema_title( $default_type ),
                'type'      => 'template',
                'shortcode' => uniqid( 's-' ),
                'isPrimary' => true,
            ],
        ];

        return $schemas;
    }

    /**
     * Resolves a post's default schema type, normalising Rank Math's legacy names.
     *
     * @since 5.0.3
     *
     * @param int $post_id Product id.
     *
     * @return mixed
     */
    private static function get_product_default_schema_type( $post_id ) {
        $default_type = ucfirst( Helper::get_default_schema_type( $post_id ) );

        switch ( $default_type ) {
            case 'Video':
                return 'VideoObject';

            case 'Software':
                return 'SoftwareApplication';

            case 'Jobposting':
                return 'JobPosting';

            default:
                return $default_type;
        }
    }
}

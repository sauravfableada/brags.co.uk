<?php

namespace WPDataAccess\Plugin_Table_Models {

	use WPDataAccess\WPDA;

	class WPDA_App_Model extends WPDA_Plugin_Table_Base_Model {

		const BASE_TABLE_NAME = 'wpda_app';

		public static function get_by_id( $app_id ) {

			global $wpdb;
			$dataset = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM `%1s` WHERE app_id = %d', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					array(
						WPDA::remove_backticks( self::get_base_table_name() ),
						$app_id,
					)
				), // db call ok; no-cache ok.
				'ARRAY_A'
			); // phpcs:ignore Standard.Category.SniffName.ErrorCode

			return 1 === $wpdb->num_rows ? $dataset : false;

		}

		public static function get_by_name( $app_name ) {

			global $wpdb;
			$dataset = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM `%1s` WHERE app_name = %s', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					array(
						WPDA::remove_backticks( self::get_base_table_name() ),
						$app_name,
					)
				), // db call ok; no-cache ok.
				'ARRAY_A'
			); // phpcs:ignore Standard.Category.SniffName.ErrorCode

			return 1 === $wpdb->num_rows ? $dataset : false;

		}

		public static function list() {

			global $wpdb;
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM `%1s` ORDER BY app_name', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					array(
						WPDA::remove_backticks( self::get_base_table_name() ),
					)
				), // db call ok; no-cache ok.
				'ARRAY_A'
			); // phpcs:ignore Standard.Category.SniffName.ErrorCode

		}

        public static function has_any() {

            global $wpdb;
            $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT 1 FROM `%1s` LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
                    array(
                        WPDA::remove_backticks( self::get_base_table_name() ),
                    )
                ), // db call ok; no-cache ok.
                'ARRAY_A'
            ); // phpcs:ignore Standard.Category.SniffName.ErrorCode

            return 1 === $wpdb->num_rows;

        }

        public static function add_to_dashboard_menu() {

			global $wpdb;
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM `%1s` WHERE `app_add_to_menu` = 1 ORDER BY app_name', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					array(
						WPDA::remove_backticks( self::get_base_table_name() ),
					)
				), // db call ok; no-cache ok.
				'ARRAY_A'
			); // phpcs:ignore Standard.Category.SniffName.ErrorCode

		}

		public static function create(
			$app_name,
			$app_title,
			$app_type,
			$app_settings
		) {

			global $wpdb;
			if ( 1 === $wpdb->insert(
					static::get_base_table_name(),
					array(
						'app_name'     => $app_name,
						'app_title'    => $app_title,
						'app_type'     => $app_type,
						'app_settings' => $app_settings,
					)
				)
			) {
				return array(
					'app_id' => $wpdb->insert_id,
					'msg'    => '',
				);
			} else {
				return array(
					'app_id' => false,
					'msg'    => $wpdb->last_error,
				);
			}
		}

		public static function delete( $app_id ) {

			global $wpdb;
			return $wpdb->delete(
				static::get_base_table_name(),
				array(
					'app_id' => $app_id,
				)
			);

		}
		
		public static function update(
			$app_id,
			$app_name,
			$app_title,
			$app_type,
			$app_settings,
			$app_add_to_menu
		) {

			global $wpdb;
			$wpdb->update(
				static::get_base_table_name(),
				array(
					'app_name'        => $app_name,
					'app_title'       => $app_title,
					'app_type'        => $app_type,
					'app_settings'    => $app_settings,
					'app_add_to_menu' => $app_add_to_menu,
				),
				array(
					'app_id' => $app_id,
				)
			);

			return $wpdb->last_error;

		}

        public static function copy_all(
            $app
        ) {

            $app_id   = $app[0]['app_id'];
            $app_name = $app[0]['app_name'];

            // Determine new app name
            $copy_nr      = 1;
            $new_app_name = "{$app_name}_{$copy_nr} (copy)";
            $app_exists = self::get_by_name( $new_app_name );
            while ( false !== $app_exists ) {
                $copy_nr++;
                $new_app_name = "{$app_name}_{$copy_nr} (copy)";
                $app_exists   = self::get_by_name( $new_app_name );
            }

            // Copy app
            global $wpdb;
            if ( 1 === $wpdb->insert(
                    static::get_base_table_name(),
                    array(
                        'app_name'     => $new_app_name,
                        'app_title'    => $app[0]['app_title'],
                        'app_type'     => $app[0]['app_type'],
                        'app_settings' => $app[0]['app_settings'],
                        'app_theme'    => $app[0]['app_theme'],
                    )
                )
            ) {
                $new_app_id = $wpdb->insert_id;

                // Copy containers
                WPDA_App_Container_Model::copy( $app_id, $new_app_id );

                if ( 5 === $app[0]['app_type'] || '5' === $app[0]['app_type'] ) {
                    // App container, copy details
                    $apps = WPDA_App_Apps_Model::select_all( $app_id );
                    foreach ( $apps as $app ) {
                        $detail_app = self::get_by_id( $app['app_id_detail'] );
                        if ( false !== $detail_app ) {
                            $detail_app_ids = static::copy_all($detail_app);
                            if ( false !== $detail_app_ids['app_id'] ) {
                                WPDA_App_Apps_Model::create($new_app_id, $detail_app_ids['app_id'], $app['seq_nr']);
                            }
                        }
                    }
                }

                return array(
                    'app_id' => $new_app_id,
                    'msg'    => '',
                );
            } else {
                return array(
                    'app_id' => false,
                    'msg'    => $wpdb->last_error,
                );
            }

        }

		public static function copy(
			$app_id
		) {

			$app = self::get_by_id( $app_id );
			if ( false === $app ) {
				return false;
			}

			return static::copy_all( $app );

		}

		public static function update_theme(
			$app_id,
			$app_theme
		) {

			global $wpdb;
			$wpdb->update(
				static::get_base_table_name(),
				array(
					'app_theme' => $app_theme,
				),
				array(
					'app_id' => $app_id,
				)
			);

			return $wpdb->last_error;

		}

	}

}
<?php

namespace WPDataAccess\Plugin_Table_Models {

	use WPDataAccess\WPDA;

	class WPDA_App_Apps_Model extends WPDA_Plugin_Table_Base_Model {

		const BASE_TABLE_NAME = 'wpda_app_apps';

		public static function select_all( $app_id ) {

			global $wpdb;
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM `%1s` WHERE app_id = %d order by seq_nr', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					array(
						WPDA::remove_backticks( self::get_base_table_name() ),
						$app_id,
					)
				), // db call ok; no-cache ok.
				'ARRAY_A'
			); // phpcs:ignore Standard.Category.SniffName.ErrorCode

		}

		public static function list() {

			global $wpdb;
			return $wpdb->get_results(
				$wpdb->prepare('
						SELECT * 
						FROM `%1s` 
						ORDER BY app_id, seq_nr, app_id_detail
					', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					array(
						WPDA::remove_backticks( self::get_base_table_name() )
					)
				), // db call ok; no-cache ok.
				'ARRAY_A'
			); // phpcs:ignore Standard.Category.SniffName.ErrorCode

		}

		public static function create(
			$app_id,
			$app_id_detail,
			$seq_nr
		) {

			global $wpdb;
			if ( 1 === $wpdb->insert(
					static::get_base_table_name(),
					array(
						'app_id'        => $app_id,
						'app_id_detail' => $app_id_detail,
						'seq_nr'        => $seq_nr,
					)
				)
			) {
				return true;
			} else {
				return false;
			}
		}

		public static function delete( $app_id, $delete_as_detail = false ) {

            global $wpdb;

            if ( $delete_as_detail ) {
                $wpdb->delete(
                    static::get_base_table_name(),
                    array(
                        'app_id_detail' => $app_id,
                    )
                );
            }

			return $wpdb->delete(
				static::get_base_table_name(),
				array(
					'app_id' => $app_id,
				)
			);

		}

		public static function update(
			$app_id,
			$app_apps
		) {

			self::delete( $app_id );
			foreach ( $app_apps as $index => $app_detail_id ) {
				self::create( $app_id, $app_detail_id, $index );
			}

		}

	}

}
<?php

namespace WPDataAccess\Utilities {

	use WPDataAccess\WPDA;

	class WPDA_WP_Media {

		public static function get_media_columns_from_url( $columns ) {

			// Get WordPress media library columns from URL argument.
			$media_columns = array();

			if ( isset( $_POST['media'] ) && is_array( $_POST['media'] ) ) {
				$media_array = rest_sanitize_array( $_POST['media'] );
				foreach ( $media_array as $media ) {
					if ( isset( $media['target'], $columns[ $media['target'] ]['name'] ) ) {
						// Column target is stored in element data.
						$media_columns[] = $columns[ $media['target'] ]['data'];
					}
				}
			}

			return $media_columns;

		}

		public static function get_media_url( $media_column ) {

			if ( null === $media_column ) {
				return null;
			}

			$media_ids = explode( ',', $media_column );
			$media_src = array();

			foreach ( $media_ids as $media_id ) {
				$url = wp_get_attachment_url( esc_attr( $media_id ) );
				if ( false !== $url ) {
					$media_object = array(
						'url'       => $url,
						'mime_type' => get_post_mime_type( $media_id ),
						'title'     => get_the_title( esc_attr( $media_id ) )
					);

					$media_src[] = json_encode( $media_object );
				} else {
					$media_src[] = $media_id; // Forces error in browser.
				}
			}

			return $media_src;

		}

	}

}
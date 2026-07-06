<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package WPDataAccess\Simple_Form
 */

namespace WPDataAccess\Simple_Form {

	/**
	 * Class WPDA_Simple_Form_Item_Image
	 *
	 * Handles a database column of type image.
	 *
	 * @author  Peter Schulz
	 * @since   2.5.0
	 */
	class WPDA_Simple_Form_Item_Image extends WPDA_Simple_Form_Item_Media {

		/**
		 * WPDA_Simple_Form_Item_Image constructor.
		 *
		 * @param array $args
		 */
		public function __construct( $args = array() ) {
			parent::__construct( $args );

			$this->media_frame_title  = __( 'Upload or select image(s) from your WordPress media library', 'wp-data-access' );
			$this->media_frame_remove = __( 'Remove image(s)', 'wp-data-access' );
			$this->media_types        = 'image';
		}

		protected function show_item_media() {
			if ( 'number' === $this->data_type ) {
				// Column supports one image file.
				$url = wp_get_attachment_url( $this->item_value );
				if ( false !== $url ) {
					$this->create_item_image( $url );
				}
			} else {
				// Column supports multiple image files.
				if ( null !== $this->item_value && '' !== $this->item_value ) {
					$media_ids = explode( ',', $this->item_value );//phpcs:ignore - 8.1 proof
					foreach ( $media_ids as $media_id ) {
						$url = wp_get_attachment_url( $media_id );
						$this->create_item_image( $url );
					}
				}
			}
		}

		private function create_item_image( $url ) {
			?>
			<span class="wpda_media_container wpda_image">
				<img src="<?php echo esc_url( $url ); ?>" target="_blank">
			</span>
			<?php
		}

	}

}

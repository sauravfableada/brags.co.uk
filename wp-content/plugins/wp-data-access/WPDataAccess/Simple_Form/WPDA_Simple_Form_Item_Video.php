<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package WPDataAccess\Simple_Form
 */

namespace WPDataAccess\Simple_Form {

	class WPDA_Simple_Form_Item_Video extends WPDA_Simple_Form_Item_Media {

		/**
		 * WPDA_Simple_Form_Item_Video constructor.
		 *
		 * @param array $args
		 */
		public function __construct( $args = array() ) {
			parent::__construct( $args );

			$this->media_frame_title  = __( 'Upload or select video(s) from your WordPress media library', 'wp-data-access' );
			$this->media_frame_remove = __( 'Remove video(s)', 'wp-data-access' );
			$this->media_types        = 'video';
		}

		protected function show_item_media() {
			if ( 'number' === $this->data_type ) {
				// Column supports only one media file
				$url = wp_get_attachment_url( $this->item_value );
				if ( false !== $url ) {
					$url = wp_get_attachment_url( $this->item_value );
					$this->create_item_video( $url );
				}
			} else {
				// Column supports multiple media files
				if ( null !== $this->item_value && '' !== $this->item_value ) {
					$media_ids = explode( ',', $this->item_value );//phpcs:ignore - 8.1 proof
					foreach ( $media_ids as $media_id ) {
						$url = wp_get_attachment_url( $media_id );
						if ( false !== $url ) {
							$this->create_item_video( $url );
						}
					}
				}
			}
		}

		private function create_item_video( $url ) {
			?>
			<span class="wpda_media_container wpda_video">
				<video controls>
					<source src="<?php echo esc_url( $url ); ?>">
				</video>
			</span>
			<?php

		}

	}

}

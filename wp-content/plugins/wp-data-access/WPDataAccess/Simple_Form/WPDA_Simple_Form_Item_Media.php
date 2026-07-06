<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package WPDataAccess\Simple_Form
 */

namespace WPDataAccess\Simple_Form {

	/**
	 * Class WPDA_Simple_Form_Item_Media
	 *
	 * Handles a database column of type media.
	 *
	 * @author  Peter Schulz
	 * @since   2.5.0
	 */
	class WPDA_Simple_Form_Item_Media extends WPDA_Simple_Form_Item {

		protected static $media_seq_id = 0;
		protected $media_id;

		protected $media_frame_title;
		protected $media_frame_button;
		protected $media_frame_remove;

		protected $media_types = ''; // Use comma separated list (empty = all)

		/**
		 * WPDA_Simple_Form_Item_Media constructor.
		 *
		 * @param array $args
		 */
		public function __construct( $args = array() ) {
			parent::__construct( $args );

			$this->media_id = self::$media_seq_id++;

			$this->item_hide_icon = true;

			$this->media_frame_title  = __( 'Upload or select media from your WordPress media library', 'wp-data-access' );
			$this->media_frame_remove = __( 'Remove media', 'wp-data-access' );
			$this->media_types        = '';
			$this->media_frame_button = __( 'Select', 'wp-data-access' );
		}

		/**
		 * Overwrite method show_item: create media item to interact with the WordPress media library
		 *
		 * When adding a new media item type, modify the following methods:
		 * show_item_media()
		 *
		 * If you change this method all media items will be affected!
		 */
		public function show_item() {
			?>
			<div id="media_container_<?php echo esc_attr( $this->media_id ); ?>">
				<?php $this->show_item_media(); ?>
			</div>
			<?php
			if ( 'view' !== $this->show_context_action ) {
				$this->add_media_library_interaction();
			}
		}

		/**
		 * Uses the media id to the media
		 *
		 * Overwrite this method for every new media item.
		 */
		protected function show_item_media() {
			if ( 'number' === $this->data_type ) {
				// Column supports only one media file
				$url = wp_get_attachment_url( $this->item_value );
				if ( false !== $url ) {
					$icon  = wp_mime_type_icon( $this->item_value );
					$title = get_the_title( $this->item_value );
					$this->create_item_media( $url, $icon, $title );
				}
			} else {
				// Column supports multiple media files
				if ( null !== $this->item_value && '' !== $this->item_value ) {
					$media_ids = explode( ',', $this->item_value );//phpcs:ignore - 8.1 proof
					foreach ( $media_ids as $media_id ) {
						$url = wp_get_attachment_url( $media_id );
						if ( false !== $url ) {
							$icon  = wp_mime_type_icon( $media_id );
							$title = get_the_title( $media_id );
							$this->create_item_media( $url, $icon, $title );
						}
					}
				}
			}
		}

		private function create_item_media( $url, $icon, $title ) {
			?>
			<span class="wpda_media_container wpda_media">
				<a href="<?php echo esc_url( $url ); ?>" target="_blank">
					<img src="<?php echo esc_url( $icon ); ?>">
					<br/>
					<?php echo esc_attr( $title ); ?>
				</a>
			</span>
			<?php
		}

		/**
		 * Add media library interaction to media item.
		 *
		 * This method:
		 * (1) Adds a hidden item holding the media id(s).
		 * (2) Adds an upload button to start the interaction with the media library.
		 * (3) Adds a remove button to remove the media.
		 * (4) Adds Media Library event handlers.
		 *
		 * If you change this method all media items will be affected.
		 */
		protected function add_media_library_interaction() {
			switch ( substr( static::class, strrpos( static::class, '\\' ) + 1 ) ) {
				case 'WPDA_Simple_Form_Item_Image':
					$media_type = 'image';
					break;
				case 'WPDA_Simple_Form_Item_Video':
					$media_type = 'video';
					break;
				case 'WPDA_Simple_Form_Item_Audio':
					$media_type = 'audio';
					break;
				default:
					$media_type = 'media';
			}
			?>
			<input type="hidden"
				   name="<?php echo esc_attr( $this->item_name ); ?>"
				   value="<?php echo esc_attr( $this->item_value ); ?>"
				   id="media_<?php echo esc_attr( $this->media_id ); ?>"
			/>
			<div style="clear:both;">
				<button id="media_<?php echo esc_attr( $this->media_id ); ?>_upload_button"
					    class="button">
						<?php echo esc_attr( $this->media_frame_title ); ?>
				</button>
				<button id="media_<?php echo esc_attr( $this->media_id ); ?>_remove_button"
					   	class="button">
						<?php echo esc_attr( $this->media_frame_remove ); ?>
				</button>
			</div>
			<script type='text/javascript'>
				jQuery(function () {
					wpdaInitMediaItem(
						"<?php echo esc_attr( $this->media_id ); ?>",
						jQuery("#media_container_<?php echo esc_attr( $this->media_id ); ?>"),
						jQuery("#media_<?php echo esc_attr( $this->media_id ); ?>"),
						"<?php echo esc_attr( $media_type ); ?>",
						"<?php echo esc_attr( $this->data_type ); ?>",
						"<?php echo esc_attr( $this->media_frame_title ); ?>",
						"<?php echo esc_attr( $this->media_frame_button ); ?>",
						['<?php echo implode( '\',\'', explode( ',', $this->media_types ) ); // phpcs:ignore WordPress.Security.EscapeOutput 8.1 proof?>']
					);
				});
			</script>
			<?php
		}

		/**
		 * Overwrite method
		 *
		 * @param $pre_insert
		 *
		 * @return bool
		 */
		public function is_valid( $pre_insert = false ) {
			if ( ! parent::is_valid( $pre_insert ) ) {
				return false;
			}

			// TODO: check if the media id exists

			return true;
		}

	}

}

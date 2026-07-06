<?php
/**
 * Deprecated functions.
 *
 * @package woodmart
 */

if ( ! function_exists( 'woodmart_get_attachment_placeholder' ) ) {
	/**
	 * Get placeholder image. Needs ID to generate a blurred preview and size.
	 *
	 * @deprecated 8.0
	 *
	 * @return mixed|null
	 */
	function woodmart_get_attachment_placeholder() {
		_deprecated_function( 'woodmart_get_attachment_placeholder', '8.0', 'woodmart_lazy_get_default_preview' );

		return woodmart_lazy_get_default_preview();
	}
}

if ( ! function_exists( 'woodmart_get_placeholder_size' ) ) {
	/**
	 * Get placeholder size.
	 *
	 * @deprecated 8.0
	 *
	 * @param integer $x0 Width.
	 * @param integer $y0 Height.
	 * @return string
	 */
	function woodmart_get_placeholder_size( $x0, $y0 ) {
		_deprecated_function( 'woodmart_get_placeholder_size', '8.0' );

		$x = 10;
		$y = 10;

		if ( $x0 && $x0 < $y0 ) {
			$y = ( $x * $y0 ) / $x0;
		}

		if ( $y0 && $x0 > $y0 ) {
			$x = ( $y * $x0 ) / $y0;
		}

		$x = ceil( $x );
		$y = ceil( $y );

		return (int) $x . 'x' . (int) $y;
	}
}

if ( ! function_exists( 'woodmart_encode_image' ) ) {
	/**
	 * Encode image.
	 *
	 * @deprecated 8.0
	 *
	 * @param integer $id Attachment ID.
	 * @param string  $url Image URL.
	 * @return string
	 */
	function woodmart_encode_image( $id, $url ) {
		_deprecated_function( 'woodmart_encode_image', '8.0' );

		if ( ! wp_attachment_is_image( $id ) || preg_match( '/^data\:image/', $url ) ) {
			return $url;
		}

		$meta_key = '_base64_image.' . md5( $url );

		$img_url = get_post_meta( $id, $meta_key, true );

		if ( $img_url ) {
			return $img_url;
		}

		$image_path = preg_replace( '/^.*?wp-content\/uploads\//i', '', $url );

		if ( ( $uploads = wp_get_upload_dir() ) && ( false === $uploads['error'] ) && ( 0 !== strpos( $image_path, $uploads['basedir'] ) ) ) { // phpcs:ignore.
			if ( false !== strpos( $image_path, 'wp-content/uploads' ) ) {
				$image_path = trailingslashit( $uploads['basedir'] . '/' . _wp_get_attachment_relative_path( $image_path ) ) . basename( $image_path );
			} else {
				$image_path = $uploads['basedir'] . '/' . $image_path;
			}
		}

		$max_size = 150 * 1024; // MB

		if ( file_exists( $image_path ) && ( ! $max_size || ( filesize( $image_path ) <= $max_size ) ) ) {
			$filetype = wp_check_filetype( $image_path );

			// Read image path, convert to base64 encoding
			if ( function_exists( 'woodmart_compress' ) && function_exists( 'woodmart_get_file' ) ) {
				$image_data = woodmart_compress( woodmart_get_file( $image_path ) );
			} else {
				$image_data = '';
			}

			// Format the image SRC:  data:{mime};base64,{data};
			$img_url = 'data:image/' . $filetype['ext'] . ';base64,' . $image_data;

			update_post_meta( $id, $meta_key, $img_url );

			return $img_url;
		}

		return $url;
	}
}


if ( ! function_exists( 'woodmart_lazy_avatar_image' ) ) {
	/**
	 * Filters HTML <img> tag and adds lazy loading attributes. Used for avatar images.
	 *
	 * @deprecated 8.0
	 *
	 * @param string $html Image html.
	 * @return string
	 */
	function woodmart_lazy_avatar_image( $html ) {
		_deprecated_function( 'woodmart_lazy_avatar_image', '8.0', 'woodmart_lazy_image_standard' );

		return woodmart_lazy_image_standard( $html );
	}
}

if ( ! function_exists( 'woodmart_post_meta' ) ) {
	/**
	 * Post meta template.
	 *
	 * @deprecated 8.2
	 *
	 * @param array $atts Attributes.
	 */
	function woodmart_post_meta( $atts = array() ) {
		_deprecated_function( 'woodmart_post_meta', '8.2', '' );

		extract( // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			shortcode_atts(
				array(
					'author'        => 1,
					'author_avatar' => 0,
					'date'          => 1,
					'author_label'  => 'short',
					'comments'      => 1,
					'social_icons'  => 0,
				),
				$atts
			)
		);
		?>
			<ul class="entry-meta-list">
				<?php if ( get_post_type() === 'post' ) : ?>
					<li class="modified-date">
						<?php woodmart_post_modified_date(); ?>
					</li>

					<?php if ( is_sticky() ) : ?>
						<li class="meta-featured-post">
							<?php esc_html_e( 'Featured', 'woodmart' ); ?>
						</li>
					<?php endif; ?>

					<?php if ( $author ) : ?>
						<li class="meta-author">
							<?php woodmart_post_meta_author( $author_avatar, $author_label ); ?>
						</li>
					<?php endif ?>

					<?php if ( $date ) : ?>
						<li class="meta-date">
							<?php echo esc_html( _x( 'On', 'meta-date', 'woodmart' ) ) . ' ' . get_the_date(); ?>
						</li>
					<?php endif ?>

					<?php if ( $comments && comments_open() ) : ?>
						<li class="meta-reply">
							<?php woodmart_post_meta_reply(); ?>
						</li>
					<?php endif; ?>

					<?php if ( $social_icons && woodmart_is_social_link_enabled( 'share' ) && function_exists( 'woodmart_shortcode_social' ) ) : ?>
						<li class="hovered-social-icons wd-tltp">
							<div class="tooltip top">
								<div class="tooltip-arrow"></div>
								<div class="tooltip-inner">
									<?php
										echo woodmart_shortcode_social( // phpcs:ignore. WordPress.Security.EscapeOutput.OutputNotEscaped
											array(
												'size'  => 'small',
												'color' => 'light',
											)
										);
									?>
								</div>
							</div>
						</li>
					<?php endif ?>
				<?php endif; ?>
			</ul>
		<?php
	}
}

if ( ! function_exists( 'woodmart_get_old_classes' ) ) {
	/**
	 * Get old classes.
	 *
	 * @since 6.0.0
	 * @deprecated 8.4
	 *
	 * @param string $classes Classes.
	 *
	 * @return string
	 */
	function woodmart_get_old_classes( $classes ) {
		_deprecated_function( 'woodmart_get_old_classes', '8.4', '' );

		if ( ! apply_filters( 'woodmart_show_deprecated_css_classes', false ) ) {
			$classes = '';
		}

		return esc_html( $classes );
	}
}

if ( ! function_exists( 'woodmart_is_blog_design_new' ) ) {
	/**
	 * Is blog design new.
	 *
	 * @param string $design Design.
	 * @since 6.1.0
	 *
	 * @deprecated 8.4
	 */
	function woodmart_is_blog_design_new( $design ) {
		_deprecated_function( 'woodmart_is_blog_design_new', '8.4' );

		$old = array(
			'default',
			'default-alt',
			'small-images',
			'chess',
			'masonry',
			'mask',
		);

		return ! in_array( $design, $old, true );
	}
}

if ( ! function_exists( 'woodmart_http' ) ) {
	/**
	 * Get protocol (http or https).
	 *
	 * @return string
	 *
	 * @deprecated 8.4
	 */
	function woodmart_http() {
		if ( ! is_ssl() ) {
			return 'http';
		} else {
			return 'https';
		}
	}
}

if ( ! function_exists( 'woodmart_tpl2id' ) ) {
	/**
	 * Get page ID by it's template name.
	 *
	 * @param string $tpl Template name.
	 * @return int|void
	 *
	 * @deprecated 8.4
	 */
	function woodmart_tpl2id( $tpl = '' ) {
		_deprecated_function( 'woodmart_tpl2id', '8.4' );

		$version   = defined( 'WOODMART_VERSION' ) ? WOODMART_VERSION : '';
		$cache_key = 'woodmart_tpl2id_' . md5( $tpl . '-' . $version );

		$cached_id = get_transient( $cache_key );

		if ( false !== $cached_id ) {
			return $cached_id;
		}

		$pages = get_pages(
			array(
				'meta_key'   => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $tpl, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$page_id = ! empty( $pages ) ? $pages[0]->ID : 0;

		set_transient( $cache_key, $page_id, DAY_IN_SECONDS );

		return $page_id;
	}
}

if ( ! function_exists( 'woodmart_is_social_link_enable' ) ) {
	/**
	 * Check if social link is enabled.
	 *
	 * @param string $type Social type.
	 * @return bool
	 *
	 * @deprecated 8.4
	 */
	function woodmart_is_social_link_enable( $type ) {
		_deprecated_function( 'woodmart_is_social_link_enable', '8.4', 'woodmart_is_social_link_enabled' );

		return woodmart_is_social_link_enabled( $type );
	}
}

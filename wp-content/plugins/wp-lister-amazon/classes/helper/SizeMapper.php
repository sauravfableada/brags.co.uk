<?php

namespace WPLab\Amazon\Helper;

/**
 * Class SizeMapper
 *
 * Maps common size values to Amazon's valid size enum values.
 * Based on Amazon's product type schemas which define specific valid values.
 *
 * @package WPLab\Amazon\Helper
 */
class SizeMapper {

	/**
	 * Map common size inputs to Amazon's valid size values
	 * Amazon expects lowercase values with underscores
	 *
	 * @var array
	 */
	private static $size_map = [
		// Extra Small variations
		'xs' => 'x_s',
		'XS' => 'x_s',
		'X-S' => 'x_s',
		'extra small' => 'x_s',
		'extrasmall' => 'x_s',
		'Extra Small' => 'x_s',

		// Small variations
		's' => 's',
		'S' => 's',
		'small' => 's',
		'Small' => 's',

		// Medium variations
		'm' => 'm',
		'M' => 'm',
		'medium' => 'm',
		'Medium' => 'm',
		'med' => 'm',
		'Med' => 'm',

		// Large variations
		'l' => 'l',
		'L' => 'l',
		'large' => 'l',
		'Large' => 'l',

		// Extra Large variations
		'xl' => 'x_l',
		'XL' => 'x_l',
		'X-L' => 'x_l',
		'x-l' => 'x_l',
		'extra large' => 'x_l',
		'extralarge' => 'x_l',
		'Extra Large' => 'x_l',

		// 2X Large variations
		'xxl' => '2x_l',
		'XXL' => '2x_l',
		'2xl' => '2x_l',
		'2XL' => '2x_l',
		'xx-l' => '2x_l',
		'XX-L' => '2x_l',
		'2x-l' => '2x_l',
		'2X-L' => '2x_l',
		'2x large' => '2x_l',
		'2X Large' => '2x_l',
		'2xlarge' => '2x_l',
		'2XLarge' => '2x_l',

		// 3X Large variations
		'xxxl' => '3x_l',
		'XXXL' => '3x_l',
		'3xl' => '3x_l',
		'3XL' => '3x_l',
		'xxx-l' => '3x_l',
		'XXX-L' => '3x_l',
		'3x-l' => '3x_l',
		'3X-L' => '3x_l',
		'3x large' => '3x_l',
		'3X Large' => '3x_l',
		'3xlarge' => '3x_l',
		'3XLarge' => '3x_l',

		// 4X Large variations
		'xxxxl' => '4x_l',
		'XXXXL' => '4x_l',
		'4xl' => '4x_l',
		'4XL' => '4x_l',
		'xxxx-l' => '4x_l',
		'XXXX-L' => '4x_l',
		'4x-l' => '4x_l',
		'4X-L' => '4x_l',
		'4x large' => '4x_l',
		'4X Large' => '4x_l',
		'4xlarge' => '4x_l',
		'4XLarge' => '4x_l',

		// 5X Large variations
		'xxxxxl' => '5x_l',
		'XXXXXL' => '5x_l',
		'5xl' => '5x_l',
		'5XL' => '5x_l',
		'xxxxx-l' => '5x_l',
		'XXXXX-L' => '5x_l',
		'5x-l' => '5x_l',
		'5X-L' => '5x_l',
		'5x large' => '5x_l',
		'5X Large' => '5x_l',
		'5xlarge' => '5x_l',
		'5XLarge' => '5x_l',

		// 6X Large variations
		'xxxxxxl' => '6x_l',
		'XXXXXXL' => '6x_l',
		'6xl' => '6x_l',
		'6XL' => '6x_l',
		'6x-l' => '6x_l',
		'6X-L' => '6x_l',
		'6x large' => '6x_l',
		'6X Large' => '6x_l',

		// 7X, 8X, 9X variations
		'7xl' => '7x_l',
		'7XL' => '7x_l',
		'8xl' => '8x_l',
		'8XL' => '8x_l',
		'9xl' => '9x_l',
		'9XL' => '9x_l',

		// 2X Small variations
		'xxs' => '2x_s',
		'XXS' => '2x_s',
		'2xs' => '2x_s',
		'2XS' => '2x_s',
		'xx-s' => '2x_s',
		'XX-S' => '2x_s',
		'2x-s' => '2x_s',
		'2X-S' => '2x_s',

		// 3X Small variations
		'xxxs' => '3x_s',
		'XXXS' => '3x_s',
		'3xs' => '3x_s',
		'3XS' => '3x_s',
		'3x-s' => '3x_s',
		'3X-S' => '3x_s',

		// Special sizes
		'one size' => 'one_size',
		'One Size' => 'one_size',
		'onesize' => 'one_size',
		'OneSize' => 'one_size',
		'OS' => 'one_size',
		'os' => 'one_size',
		'free size' => 'free_size',
		'Free Size' => 'free_size',
		'freesize' => 'free_size',
	];

	/**
	 * Size system mappings for different marketplaces
	 *
	 * @var array
	 */
	private static $marketplace_size_systems = [
		'IT' => 'it1',  // Italy uses IT sizing system
		'ES' => 'eu1',  // Spain uses EU sizing
		'FR' => 'eu1',  // France uses EU sizing
		'DE' => 'eu1',  // Germany uses EU sizing
		'UK' => 'uk1',  // United Kingdom
		'US' => 'as1',  // United States - as1 is Amazon's code for US sizes
		'CA' => 'as1',  // Canada uses US sizing
		'MX' => 'as1',  // Mexico uses US sizing
		'JP' => 'jp1',  // Japan
		'AU' => 'au1',  // Australia
	];

	/**
	 * Map a size value to Amazon's valid enum value
	 *
	 * @param string $size Original size value
	 * @param string $marketplace_code Optional marketplace code for context
	 * @return string Mapped size value
	 */
	public static function mapSize( $size, $marketplace_code = 'US' ) {
		if ( empty( $size ) ) {
			return $size;
		}

		// Trim the input
		$original_size = $size;
		$size = trim( $size );

		// Check if we have a direct mapping
		if ( isset( self::$size_map[ $size ] ) ) {
			$mapped = self::$size_map[ $size ];
			\WPLA()->logger->info( sprintf(
				'Size mapped: "%s" -> "%s" for marketplace %s',
				$original_size,
				$mapped,
				$marketplace_code
			) );
			return $mapped;
		}

		// If no mapping found, return lowercase version with underscores
		// This handles numeric sizes and other values
		$normalized = strtolower( str_replace( '-', '_', $size ) );

		if ( $normalized !== $size ) {
			\WPLA()->logger->info( sprintf(
				'Size normalized: "%s" -> "%s" for marketplace %s',
				$original_size,
				$normalized,
				$marketplace_code
			) );
			return $normalized;
		}

		return $size;
	}

	/**
	 * Get the size system code for a marketplace
	 *
	 * @param string $marketplace_code Marketplace code (IT, US, UK, etc.)
	 * @return string Size system code
	 */
	public static function getSizeSystem( $marketplace_code ) {
		if ( isset( self::$marketplace_size_systems[ $marketplace_code ] ) ) {
			return self::$marketplace_size_systems[ $marketplace_code ];
		}

		// Default to US sizing if marketplace not recognized
		return 'as1';
	}

	/**
	 * Detect size class from a size value
	 *
	 * @param string $size Size value
	 * @return string Size class (alpha, numeric, etc.)
	 */
	public static function detectSizeClass( $size ) {
		if ( empty( $size ) ) {
			return 'alpha'; // Default to alpha
		}

		$size_lower = strtolower( trim( $size ) );

		// Check if it's in our alpha size map
		if ( isset( self::$size_map[ $size ] ) ) {
			$mapped = self::$size_map[ $size ];
			// If mapped value contains x_l, x_s, or is s, m, l, it's alpha
			if ( preg_match( '/(x_[sl]|^[sml]$|one_size|free_size)/', $mapped ) ) {
				return 'alpha';
			}
		}

		// Check if it's numeric
		if ( preg_match( '/^[0-9]+/', $size_lower ) ) {
			// Numeric sizes
			return 'numeric';
		}

		// Default to alpha for letter-based sizes
		return 'alpha';
	}

	/**
	 * Process size attribute array and add size_class and size_system if missing
	 * Also maps both 'size' and 'to_size' fields to Amazon's valid size values
	 *
	 * @param array  $size_data         Size attribute data
	 * @param string $marketplace_code  Marketplace code
	 * @param bool   $skip_size_mapping Skip size mapping when custom mapping already applied
	 * @return array Processed size data
	 */
	public static function enrichSizeData( $size_data, $marketplace_code = 'US', $skip_size_mapping = false ) {
		if ( empty( $size_data ) || ! is_array( $size_data ) ) {
			return $size_data;
		}

		// Handle array format: [0][size]
		if ( isset( $size_data[0] ) && is_array( $size_data[0] ) ) {
			$size_value = $size_data[0]['size'] ?? '';

			if ( $size_value ) {
				// Only map the size value if NOT using custom mapping
				if ( ! $skip_size_mapping ) {
					$size_data[0]['size'] = self::mapSize( $size_value, $marketplace_code );
				}

				// Always add size_class if missing (use original value for detection)
				if ( empty( $size_data[0]['size_class'] ) ) {
					$size_data[0]['size_class'] = self::detectSizeClass( $size_value );
				}

				// Always add size_system if missing
				if ( empty( $size_data[0]['size_system'] ) ) {
					$size_data[0]['size_system'] = self::getSizeSystem( $marketplace_code );
				}
			}

			// Only map to_size if NOT using custom mapping
			$to_size_value = $size_data[0]['to_size'] ?? '';
			if ( $to_size_value && ! $skip_size_mapping ) {
				$size_data[0]['to_size'] = self::mapSize( $to_size_value, $marketplace_code );
			}
		}

		return $size_data;
	}

	/**
	 * Get list of size property names that should be processed
	 *
	 * @return array Array of property names
	 */
	public static function getSizeProperties() {
		return [
			'apparel_size',
			'shirt_size',
			'skirt_size',
			'bottoms_size',
			'footwear_size',
			'pants_size',
			'dress_size',
			'jacket_size',
			'shorts_size',
			'sweater_size',
			'coat_size',
		];
	}
}

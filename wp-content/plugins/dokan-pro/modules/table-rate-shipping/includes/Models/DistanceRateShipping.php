<?php

namespace WeDevs\DokanPro\Modules\TableRate\Models;

use WeDevs\Dokan\Models\BaseModel;
use WeDevs\DokanPro\Modules\TableRate\Models\DataStore\DistanceRateShippingStore;

/**
 * Distance Rate Shipping Model Class
 *
 * @since 4.1.3
 */
class DistanceRateShipping extends BaseModel {

	/**
	 * This is the name of this object type.
	 *
	 * @since 4.1.3
	 *
	 * @var string
	 */
	protected $object_type = 'distance_rate_shipping';

	/**
	 * The default data of the object.
	 *
	 * @since 4.1.3
	 *
	 * @var array
	 */
	protected $data = array(
		'rate_id'        => 0,
		'vendor_id'      => 0,
		'zone_id'        => 0,
		'instance_id'    => 0,
		'rate_condition' => '',
		'rate_min'       => '',
		'rate_max'       => '',
		'rate_cost'      => '',
		'rate_cost_unit' => '',
		'rate_fee'       => '',
		'rate_break'     => 0,
		'rate_abort'     => 0,
		'rate_order'     => 0,
	);

	/**
	 * Constructor.
	 *
	 * @since 4.1.3
	 *
	 * @param int $id ID to load from the DB (optional).
	 */
	public function __construct( int $id = 0 ) {
		parent::__construct( $id );
		$this->set_id( $id );
		$this->data_store = apply_filters( $this->get_hook_prefix() . 'data_store', new DistanceRateShippingStore() );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Delete distance rate shipping by rate ID.
	 *
	 * @since 4.1.3
	 *
	 * @param string   $output      What data to return.
	 * @param int|null $instance_id Instance ID.
	 *
	 * @return mixed Distance rate info or null.
	 */
	public static function get_shipping_rates( $output = OBJECT, $instance_id = null ) {
		return ( new static() )->get_data_store()->get_shipping_rates( $output, $instance_id );
	}

	/**
	 * Delete distance rate shipping by rate ID.
	 *
	 * @since 4.1.3
	 *
	 * @param array $rate_data Rate data to save.
	 * @param int   $rate_id   Rate ID for update or create.
	 *
	 * @return bool True if deleted, false otherwise.
	 */
	public static function save_rates( array $rate_data, int $rate_id = 0 ) {
		return ( new static() )->get_data_store()->save_rates( $rate_data, $rate_id );
	}

	/**
	 * Delete distance rate shipping by rate ID.
	 *
	 * @since 4.1.3
	 *
	 * @param array $rate_ids Rate ID's to delete.
	 *
	 * @return bool True if deleted, false otherwise.
	 */
	public static function delete_rates( array $rate_ids ) {
		return ( new static() )->get_data_store()->delete_rates( $rate_ids );
	}

	/**
	 * Delete distance rate shipping by rate ID.
	 *
	 * @since 4.1.3
	 *
	 * @param int $id        Rate ID's to delete.
	 * @param int $seller_id Seller ID.
	 *
	 * @return array Array of distance rate info or null.
	 */
	public static function get_rate_info_by_seller( $id = 0, $seller_id = 0 ) {
		return ( new static() )->get_data_store()->get_rate_info_by_seller( $id, $seller_id );
	}

	/**
	 * Gets the rate ID.
	 *
	 * @since 4.1.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int The rate ID.
	 */
	public function get_rate_id( string $context = 'view' ) {
		return $this->get_prop( 'rate_id', $context );
	}

	/**
	 * Sets the rate ID.
	 *
	 * @since 4.1.3
	 *
	 * @param int $id The rate ID.
	 *
	 * @return void
	 */
	public function set_rate_id( int $id ) {
		$this->set_prop( 'rate_id', $id );
	}

	/**
	 * Gets the vendor ID.
	 *
	 * @since 4.1.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int The vendor ID.
	 */
	public function get_vendor_id( string $context = 'view' ) {
		return $this->get_prop( 'vendor_id', $context );
	}

	/**
	 * Sets the vendor ID.
	 *
	 * @since 4.1.3
	 *
	 * @param int $id The vendor ID.
	 *
	 * @return void
	 */
	public function set_vendor_id( int $id ) {
		$this->set_prop( 'vendor_id', $id );
	}

	/**
	 * Gets the zone ID.
	 *
	 * @since 4.1.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int The zone ID.
	 */
	public function get_zone_id( string $context = 'view' ) {
		return $this->get_prop( 'zone_id', $context );
	}

	/**
	 * Sets the zone ID.
	 *
	 * @since 4.1.3
	 *
	 * @param int $id The zone ID.
	 *
	 * @return void
	 */
	public function set_zone_id( int $id ) {
		$this->set_prop( 'zone_id', $id );
	}

	/**
	 * Gets the instance ID.
	 *
	 * @since 4.1.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int The instance ID.
	 */
	public function get_instance_id( string $context = 'view' ) {
		return $this->get_prop( 'instance_id', $context );
	}

	/**
	 * Sets the instance ID.
	 *
	 * @since 4.1.3
	 *
	 * @param int $id The instance ID.
	 *
	 * @return void
	 */
	public function set_instance_id( int $id ) {
		$this->set_prop( 'instance_id', $id );
	}

	/**
	 * Gets the rate condition.
	 *
	 * @since 4.1.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string The rate condition.
	 */
	public function get_rate_condition( string $context = 'view' ) {
		return $this->get_prop( 'rate_condition', $context );
	}

	/**
	 * Sets the rate condition.
	 *
	 * @since 4.1.3
	 *
	 * @param string $condition The rate condition.
	 *
	 * @return void
	 */
	public function set_rate_condition( string $condition ) {
		$this->set_prop( 'rate_condition', $condition );
	}

	/**
	 * Gets the rate min.
	 *
	 * @since 4.1.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string The rate min.
	 */
	public function get_rate_min( string $context = 'view' ) {
		return $this->get_prop( 'rate_min', $context );
	}

	/**
	 * Sets the rate min.
	 *
	 * @since 4.1.3
	 *
	 * @param string $min The rate min.
	 *
	 * @return void
	 */
	public function set_rate_min( string $min ) {
		$this->set_prop( 'rate_min', $min );
	}

	/**
	 * Gets the rate max.
	 *
	 * @since 4.1.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string The rate max.
	 */
	public function get_rate_max( string $context = 'view' ) {
		return $this->get_prop( 'rate_max', $context );
	}

	/**
	 * Sets the rate max.
	 *
	 * @since 4.1.3
	 *
	 * @param string $max The rate max.
	 *
	 * @return void
	 */
	public function set_rate_max( string $max ) {
		$this->set_prop( 'rate_max', $max );
	}

	/**
	 * Gets the rate cost.
	 *
	 * @since 4.1.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string The rate cost.
	 */
	public function get_rate_cost( string $context = 'view' ) {
		return $this->get_prop( 'rate_cost', $context );
	}

	/**
	 * Sets the rate cost.
	 *
	 * @since 4.1.3
	 *
	 * @param string $cost The rate cost.
	 *
	 * @return void
	 */
	public function set_rate_cost( string $cost ) {
		$this->set_prop( 'rate_cost', $cost );
	}

	/**
	 * Gets the rate cost unit.
	 *
	 * @since 4.1.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string The rate cost unit.
	 */
	public function get_rate_cost_unit( string $context = 'view' ) {
		return $this->get_prop( 'rate_cost_unit', $context );
	}

	/**
	 * Sets the rate cost unit.
	 *
	 * @since 4.1.3
	 *
	 * @param string $cost_unit The rate cost unit.
	 *
	 * @return void
	 */
	public function set_rate_cost_unit( string $cost_unit ) {
		$this->set_prop( 'rate_cost_unit', $cost_unit );
	}

	/**
	 * Gets the rate fee.
	 *
	 * @since 4.1.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string The rate fee.
	 */
	public function get_rate_fee( string $context = 'view' ) {
		return $this->get_prop( 'rate_fee', $context );
	}

	/**
	 * Sets the rate fee.
	 *
	 * @since 4.1.3
	 *
	 * @param string $fee The rate fee.
	 *
	 * @return void
	 */
	public function set_rate_fee( string $fee ) {
		$this->set_prop( 'rate_fee', $fee );
	}

	/**
	 * Gets the rate break.
	 *
	 * @since 4.1.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int The rate break.
	 */
	public function get_rate_break( string $context = 'view' ) {
		return $this->get_prop( 'rate_break', $context );
	}

	/**
	 * Sets the rate break.
	 *
	 * @since 4.1.3
	 *
	 * @param int $rate_break The rate break.
	 *
	 * @return void
	 */
	public function set_rate_break( int $rate_break ) {
		$this->set_prop( 'rate_break', $rate_break );
	}

	/**
	 * Gets the rate abort.
	 *
	 * @since 4.1.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int The rate abort.
	 */
	public function get_rate_abort( string $context = 'view' ) {
		return $this->get_prop( 'rate_abort', $context );
	}

	/**
	 * Sets the rate abort.
	 *
	 * @since 4.1.3
	 *
	 * @param int $abort The rate abort.
	 *
	 * @return void
	 */
	public function set_rate_abort( int $abort ) {
		$this->set_prop( 'rate_abort', $abort );
	}

	/**
	 * Gets the rate order.
	 *
	 * @since 4.1.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int The rate order.
	 */
	public function get_rate_order( string $context = 'view' ) {
		return $this->get_prop( 'rate_order', $context );
	}

	/**
	 * Sets the rate order.
	 *
	 * @since 4.1.3
	 *
	 * @param int $order The rate order.
	 *
	 * @return void
	 */
	public function set_rate_order( int $order ) {
		$this->set_prop( 'rate_order', $order );
	}
}

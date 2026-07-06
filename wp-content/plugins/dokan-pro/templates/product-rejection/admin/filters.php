<?php
/**
 * Template for product approval status filter dropdown
 *
 * @since 3.16.0
 *
 * Variables available:
 * @var string $current Currently selected status
 * @var array  $statuses List of available statuses with labels
 */

defined( 'ABSPATH' ) || exit;
?>
<select name="product_approval_status" class="product-approval-status">
    <?php foreach ( $statuses as $value => $label ) : ?>
        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
            <?php echo esc_html( $label ); ?>
        </option>
    <?php endforeach; ?>
</select>

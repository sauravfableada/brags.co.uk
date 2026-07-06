<?php
/**
 * Product Rejection Email Template
 *
 * This template can be overridden by copying it to:
 * yourtheme/dokan/emails/product-rejection/product-rejected.php
 *
 * @version 3.16.0
 *
 * @var array $data Array of data for the email
 * @var string $email_heading Heading for the email
 * @var string $additional_content Additional content to display in the email
 * @var array $action_steps Array of action steps for the vendor
 * @var WC_Email $email WC_Email object
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

    <p><?php esc_html_e( 'Hello,', 'dokan' ); ?></p>

    <p>
        <?php
        printf(
            // translators: %s: product name
            esc_html__( 'Your product "%s" has been reviewed and requires some updates before it can be approved.', 'dokan' ),
            esc_html( $data['product']['name'] )
        );
        ?>
    </p>

    <p><?php esc_html_e( 'Summary of review:', 'dokan' ); ?></p>
    <hr>

    <ul style="padding-left: 20px;">
        <li>
            <strong>
                <?php esc_html_e( 'Product:', 'dokan' ); ?>
            </strong>
            <?php printf( '<a href="%s">%s</a>', esc_url( $data['product']['edit_link'] ), esc_html( $data['product']['name'] ) ); ?>
        </li>
        <li>
            <strong>
                <?php esc_html_e( 'Review Date:', 'dokan' ); ?>
            </strong>
            <?php echo esc_html( $data['rejection']['date'] ); ?>
        </li>
    </ul>

    <div style="margin: 20px 0; padding: 15px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 3px;">
        <h3 style="margin: 0 0 10px; color: #856404;"><?php esc_html_e( 'The reason for the rejection', 'dokan' ); ?></h3>
        <div style="color: #856404;">
            <?php echo wp_kses_post( wpautop( $data['rejection']['reason'] ) ); ?>
        </div>
    </div>

    <h3><?php esc_html_e( 'Recommended Actions:', 'dokan' ); ?></h3>

    <ol style="padding-left: 20px;">
        <?php foreach ( $action_steps as $step ) : ?>
            <li style="margin-bottom: 10px;">
                <strong><?php echo esc_html( $step['title'] ); ?></strong>
                <p style="margin: 5px 0 0;"><?php echo esc_html( $step['desc'] ); ?></p>
            </li>
        <?php endforeach; ?>
    </ol>

    <div style="margin: 30px 0; text-align: center;">
        <a href="<?php echo esc_url( $data['product']['edit_link'] ); ?>" style="display: inline-block; padding: 10px 20px; background-color: #7f54b3; color: #ffffff; text-decoration: none; border-radius: 3px;">
            <?php esc_html_e( 'Edit Product', 'dokan' ); ?>
        </a>
    </div>

<?php if ( ! empty( $additional_content ) ) : ?>
    <p><?php echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); ?></p>
<?php endif; ?>

<?php
do_action( 'woocommerce_email_footer', $email );

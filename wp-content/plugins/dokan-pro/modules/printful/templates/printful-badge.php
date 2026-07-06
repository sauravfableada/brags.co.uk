<?php
$render_data_tips = $args['tooltip'] ?? true;
$is_listing_page  = $args['product_listing_page'] ?? false;
$label_styles     = ! $is_listing_page ? 'display: inline-flex; align-items: center; margin: 6px 0 0 0;' : 'display: inline-block; margin-top: -3px';

?>

<span class='dokan-label dokan-product-status-label <?php echo esc_attr( $render_data_tips ) ? 'tips' : ''; ?>' title='<?php esc_html_e( 'Printful', 'dokan' ); ?>' style='<?php echo esc_attr( $label_styles ); ?>'>
    <svg width='40px' xmlns='http://www.w3.org/2000/svg' version='1.0' id='Layer_1' x='0px' y='0px' viewBox='0 0 715 534.3' style='enable-background:new 0 0 715 534.3;' xml:space='preserve'>
        <style type='text/css'>
            .st0{fill:#231F20;}
            .st1{fill:#F2C994;}
            .st2{fill:#ED4642;}
            .st3{fill:#17BCB5;}
            .st4{fill:#DF392F;}
            .st5{fill:#16342F;}
            .st6{fill:#15291A;}
        </style>
        <polygon class='st1' points='120.3,320.6 245,106.6 369.6,320.6 '/>
        <polygon class='st2' points='232.9,320.6 357.5,106.6 482.1,320.6 '/>
        <polygon class='st3' points='345.4,320.6 470,106.6 594.7,320.6 '/>
        <polygon class='st4' points='232.9,320.6 369.6,320.6 300.8,203.4 '/>
        <polygon class='st5' points='345.4,320.6 482.1,320.6 414.2,203.4 '/>
        <polygon class='st6' points='345.4,320.6 369.6,320.6 357.5,300.1 '/>
    </svg>
</span>

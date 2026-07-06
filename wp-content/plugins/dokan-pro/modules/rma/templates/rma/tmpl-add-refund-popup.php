<div class="dokan-rma-modals"></div>
<div style="display: none">
    <div id="dokan-send-refund-popup" class="dokan-rma-popup white-popup">
        <form method="post" id="dokan-send-refund-popup-form" class="dokan-rma-popup-form  dokan-izimodal-wraper">
            <div class="dokan-izimodal-close-btn">
                <button data-iziModal-close class="icon-close">
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <h2 class="dokan-rma-popup-title"><i class="fas fa-undo-alt" aria-hidden="true"></i>&nbsp;<?php esc_html_e( 'Send Refund Request', 'dokan' ); ?></h2>

            <div class="rma-popup-content refund-content"></div>

            <div class="rma-popup-action">
                <input type="submit" class="dokan-btn dokan-btn-theme" name="dokan_refund_submit" value="<?php esc_attr_e( 'Send Request', 'dokan' ); ?>">
            </div>
        </form>
    </div>
</div>

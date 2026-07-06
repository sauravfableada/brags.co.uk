jQuery(document).ready(function($) {
    $('.request-invoice-btn').on('click', function() {
        const $btn = $(this);
        const orderId = $btn.data('order-id');
        const $response = $btn.next('.request-invoice-response');

        $btn.prop('disabled', true).text('Requesting...');

        $.ajax({
            url: requestInvoice.ajax_url,
            type: 'POST',
            data: {
                action: 'request_vat_invoice',
                order_id: orderId,
                nonce: requestInvoice.nonce
            },
            success: function(response) {
                $btn.hide();
                $response.html('<span style="color: green;">' + response.data + '</span>');
            },
            error: function() {
                $btn.prop('disabled', false).text('Request');
                $response.html('<span style="color: red;">Something went wrong. Try again.</span>');
            }
        });
    });


});

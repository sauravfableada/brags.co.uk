jQuery(document).ready(function ($) {
    $(".ticket-status").change(function () {
        let ticketId = $(this).data("ticket-id");
        let newStatus = $(this).val();
        let message = $(this).siblings(".status-message");

        $.ajax({
            type: "POST",
            url: brags_ajax.ajax_url,
            data: {
                action: "brags_update_ticket_status",
                nonce: brags_ajax.nonce,
                ticket_id: ticketId,
                status: newStatus,
            },
            beforeSend: function () {
                message.text("Updating...").css("color", "blue").show();
            },
            success: function (response) {
                if (response.success) {
                    message.text("Updated").css("color", "green").fadeOut(2000);
                } else {
                    message.text("Error updating status").css("color", "red");
                }
            },
        });
    });
});

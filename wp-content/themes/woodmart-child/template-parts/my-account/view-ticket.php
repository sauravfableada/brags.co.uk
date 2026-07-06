<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $wpdb;
$table_name = $wpdb->prefix . 'support_tickets';
$log_table = $wpdb->prefix . 'support_ticket_logs'; // Create this table for logs if not exists

$ticket_id = isset($_GET['view_ticket']) ? intval($_GET['view_ticket']) : 0;

if ($ticket_id > 0) {
    $ticket = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $ticket_id));

    if ($ticket) {
        ?>
        <h2>Ticket #<?php echo esc_html($ticket->id); ?> - <?php echo esc_html($ticket->subject); ?></h2>
        <p><strong>Status:</strong> <?php echo esc_html($ticket->status); ?></p>
        <p><strong>Created On:</strong> <?php echo esc_html($ticket->created_at); ?></p>
        <hr>
        <h3>Ticket Messages</h3>
        <p>
            <?php echo $ticket->message??''; ?>
        </p>
        <hr>

        <div class="ticket-messages">
            <?php
            
            $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM $log_table WHERE ticket_id = %d ORDER BY created_at ASC", $ticket_id));

            if (!empty($messages)) {
                foreach ($messages as $msg) {
                    $is_admin = get_userdata($msg->user_id)->roles[0] == 'administrator';
                    echo "<div class='" . ($is_admin ? "admin-response" : "user-message") . "'>
                            <strong>" . esc_html(get_userdata($msg->user_id)->user_login) . ":</strong>
                            <p>" . esc_html($msg->message) . "</p>
                            <span class='msg-time'>" . esc_html($msg->created_at) . "</span>
                          </div>";
                }
            } else {
                echo "<p>No Logs found.</p>";
            }
            ?>
        </div>

        <h3>Reply to Ticket</h3>
        <form method="post" id="ticket-reply-form">
            <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket_id); ?>">
            <textarea name="reply_message" required></textarea>
            <button type="submit">Send Reply</button>
        </form>

        <p id="reply-status"></p>
        <script>
            jQuery(document).ready(function ($) {
                $("#ticket-reply-form").submit(function (e) {
                    e.preventDefault();
                    let formData = $(this).serialize();
                    $("#reply-status").text("Sending...").css("color", "blue");

                    $.post("<?php echo admin_url('admin-ajax.php'); ?>", formData + "&action=brags_add_ticket_reply", function (response) {
                        if (response.success) {
                            $("#reply-status").text("Reply sent successfully!").css("color", "green");
                            location.reload();
                        } else {
                            $("#reply-status").text("Error sending reply.").css("color", "red");
                        }
                    });
                });
            });
        </script>

        <?php
    } else {
        echo "<p>Ticket not found.</p>";
    }
} else {
    echo "<p>No ticket selected.</p>";
}
?>

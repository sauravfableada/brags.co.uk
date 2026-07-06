<?php
/**
 * Dokan My Ticket Page - Show Tickets Created by Customers
 */

if (!defined('ABSPATH')) {
    exit;
}

global $current_user;
get_header();

if (!dokan_is_user_seller($current_user->ID)) {
    dokan_get_template_part('global/account-denied');
    return;
}

if (!isset($_GET['ticket_id'])) {
    echo '<p>Invalid ticket ID.</p>';
    return;
}

$ticket_id = intval($_GET['ticket_id']);
$ticket = get_post($ticket_id);

if (!$ticket || $ticket->post_type !== 'ticket') {
    echo '<p>Ticket not found.</p>';
    return;
}

// Ensure the user has permission to view the ticket
if ($current_user->ID !== $ticket->post_author && !user_can($current_user, 'view_ticket')) {
    echo '<p>You do not have permission to view this ticket.</p>';
    return;
}
?>

<style>
    div#wpas_ticketlist_filters {
        display: none;
    }
    .ticket-reply-container {
        margin-top: 20px;
    }
</style>

<div class="dokan-dashboard-wrap">
    <?php do_action('dokan_dashboard_content_before'); ?>

    <div class="dokan-dashboard-content">
        <div class="dokan-my-ticket">
            <div class="header-div">
                <div class="widget-title">
                    <i class="fas fa-ticket-alt"></i> My Customer Tickets
                </div>
            </div>

            <div id="ticketRequest">
                <h2><?php echo esc_html($ticket->post_title); ?></h2>
                <p><strong>Status:</strong> <?php echo esc_html(get_post_status($ticket_id)); ?></p>
                <p><strong>Created By:</strong> <?php echo get_the_author_meta('display_name', $ticket->post_author); ?></p>
                <p><strong>Date:</strong> <?php echo get_the_date('Y-m-d H:i', $ticket_id); ?></p>

                <hr>

                <h3>Replies</h3>
                <div id="ticketReplies">
                    <?php
                    $comments = get_comments(['post_id' => $ticket_id, 'status' => 'approve']);
                    foreach ($comments as $comment) {
                        echo "<p><strong>{$comment->comment_author}</strong>: {$comment->comment_content} <em>({$comment->comment_date})</em></p>";
                    }
                    ?>
                </div>

                <hr>

                <h3>Reply to Ticket</h3>
                <div class="ticket-reply-container">
                    <form id="ticket-reply-form">
                        <textarea name="ticket_reply" id="ticket_reply" required rows="5" cols="50"></textarea>
                        <input type="hidden" name="ticket_id" id="ticket_id" value="<?php echo esc_attr($ticket_id); ?>">
                        <button type="submit">Reply</button>
                    </form>

                    <div id="reply-status"></div> <!-- Success/Error messages -->
                </div>
            </div>
        </div>
    </div>

    <?php do_action('dokan_dashboard_content_after'); ?>
</div>

<?php get_footer(); ?>

<script>
jQuery(document).ready(function($) {
    $('#ticket-reply-form').submit(function(event) {
        event.preventDefault(); // Prevent page reload

        let ticketReply = $('#ticket_reply').val();
        let ticketId = $('#ticket_id').val();

        if (ticketReply.trim() === '') {
            $('#reply-status').html('<p style="color: red;">Reply cannot be empty.</p>');
            return;
        }

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'submit_ticket_reply',
                ticket_id: ticketId,
                ticket_reply: ticketReply,
            },
            beforeSend: function() {
                $('#reply-status').html('<p style="color: blue;">Submitting...</p>');
            },
            success: function(response) {
                if (response.success) {
                    $('#reply-status').html('<p style="color: green;">Reply added successfully!</p>');
                    $('#ticket_reply').val(''); // Clear input
                    $('#ticketReplies').prepend('<p><strong>You:</strong> ' + ticketReply + ' <em>(Just now)</em></p>');
                } else {
                    $('#reply-status').html('<p style="color: red;">' + response.data + '</p>');
                }
            }
        });
    });
});
</script>

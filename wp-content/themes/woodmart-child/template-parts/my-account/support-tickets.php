<?php
if (isset($_POST['submit_reply'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $message = sanitize_textarea_field($_POST['reply_message']);
    $user_id = get_current_user_id();

    // Insert into replies table
    $wpdb->insert(
        $wpdb->prefix . 'support_ticket_replies',
        [
            'ticket_id' => $ticket_id,
            'user_id'   => $user_id,
            'message'   => $message,
            'created_at' => current_time('mysql'),
        ],
        ['%d', '%d', '%s', '%s']
    );

    echo "<p style='color:green;'>Reply sent!</p>";
    echo "<script>location.reload();</script>"; // Refresh page
}

if (isset($_GET['view_ticket'])) {
    $ticket_id = intval($_GET['view_ticket']);

    // Fetch ticket details
    $ticket = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $ticket_id
    ));

    if ($ticket) {
        ?>
        <h2>Ticket #<?php echo esc_html($ticket->id); ?> - <?php echo esc_html($ticket->subject); ?></h2>
        <p><strong>User:</strong> <?php echo esc_html(get_userdata($ticket->user_id)->user_login); ?></p>
        <p><strong>Email:</strong> <?php echo esc_html($ticket->email); ?></p>
        <p><strong>Phone:</strong> <?php echo esc_html($ticket->phone); ?></p>
        <p><strong>Message:</strong><br><?php echo nl2br(esc_html($ticket->message)); ?></p>
        <p><strong>Extra Text:</strong><br><?php echo nl2br(esc_html($ticket->extra_text)); ?></p>
        <p><strong>Status:</strong> <?php echo esc_html($ticket->status); ?></p>

        <!-- Ticket Reply Form -->
        <h3>Reply to Ticket</h3>
        <form method="post">
            <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket_id); ?>">
            <textarea name="reply_message" rows="4" placeholder="Type your reply here..." required></textarea>
            <br>
            <button type="submit" name="submit_reply">Send Reply</button>
        </form>

        <h3>Previous Replies</h3>
        <?php
        // Fetch replies
        $replies = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}support_ticket_replies WHERE ticket_id = %d ORDER BY created_at ASC",
            $ticket_id
        ));

        if (!empty($replies)) {
            echo '<ul>';
            foreach ($replies as $reply) {
                echo '<li><strong>' . esc_html(get_userdata($reply->user_id)->user_login) . ':</strong> ' . nl2br(esc_html($reply->message)) . ' <em>(' . esc_html($reply->created_at) . ')</em></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No replies yet.</p>';
        }

        echo '<hr>';
        echo '<a href="?">Back to Tickets</a>';
        exit();
    } else {
        echo '<p style="color:red;">Ticket not found.</p>';
    }
}
?>

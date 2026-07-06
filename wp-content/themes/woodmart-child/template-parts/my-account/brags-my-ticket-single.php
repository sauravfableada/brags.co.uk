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

?>
<?php
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

// Ensure the user has permission to reply
$current_user = wp_get_current_user();
if (!user_can($current_user, 'view_ticket') && $current_user->ID !== $ticket->post_author) {
    echo '<p>You do not have permission to view this ticket.</p>';
    return;
}
?>
<h2><?php echo esc_html($ticket->post_title); ?></h2>
<p><strong>Status:</strong> <?php echo esc_html(get_post_status($ticket_id)); ?></p>
<p><strong>Created By:</strong> <?php echo get_the_author_meta('display_name', $ticket->post_author); ?></p>
<p><strong>Date:</strong> <?php echo get_the_date('Y-m-d H:i', $ticket_id); ?></p>

<hr>

<h3>Replies</h3>
<?php
$comments = get_comments(['post_id' => $ticket_id, 'status' => 'approve']);
foreach ($comments as $comment) {
    echo "<p><strong>{$comment->comment_author}</strong>: {$comment->comment_content} <em>({$comment->comment_date})</em></p>";
}
?>

<hr>

<h3>Reply to Ticket</h3>
<form method="post">
    <textarea name="ticket_reply" required rows="5" cols="50"></textarea>
    <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket_id); ?>">
    <input type="submit" name="submit_reply" value="Reply">
</form>

<?php
if (isset($_POST['submit_reply']) && !empty($_POST['ticket_reply'])) {
    $reply_content = sanitize_text_field($_POST['ticket_reply']);

    $comment_data = [
        'comment_post_ID' => $ticket_id,
        'comment_content' => $reply_content,
        'comment_author' => $current_user->display_name,
        'user_id' => $current_user->ID,
        'comment_approved' => 1,
    ];

    wp_insert_comment($comment_data);
    echo "<p>Reply added successfully! Refresh the page to see it.</p>";
}
?>

<?php get_footer(); ?>

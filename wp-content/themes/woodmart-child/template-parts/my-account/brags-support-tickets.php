<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
//subscriber // bragger
// Check if user has 'bragger' role
$current_user = wp_get_current_user();
// if (!in_array('bragger', $current_user->roles)) {
//     echo "<p style='color:red;'>You do not have permission to view this page.</p>";
//     return;
// }

global $wpdb;
$table_name = $wpdb->prefix . 'support_tickets';

// Get ticket status filter from URL
$ticket_status = isset($_GET['ticket_status']) ? sanitize_text_field($_GET['ticket_status']) : 'all';

// Get counts for each status
$all_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$open_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'Open'");
$closed_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'Closed'");
$in_progress_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'In Progress'");

// Fetch tickets based on selected status
$query = "SELECT * FROM $table_name";
if ($ticket_status === 'open') {
    $query .= " WHERE status = 'Open'";
} elseif ($ticket_status === 'closed') {
    $query .= " WHERE status = 'Closed'";
}elseif ($ticket_status === 'in_progress') {
    $query .= " WHERE status = 'In Progress'";
}
$query .= " ORDER BY created_at DESC";

$tickets = $wpdb->get_results($query);

?>
<div class="dokan-support-customer-listing dokan-support-topic-wrapper">
<h2>Manage Support Tickets</h2>
        <ul class="dokan-support-topic-counts subsubsub" style="display:flex;">
            <li>
                <a href="?ticket_status=all" class="<?php echo ($ticket_status === 'all') ? 'active' : ''; ?>">
                    All Tickets (<?php echo esc_html($all_count); ?>)
                </a>
            </li>
            <li>
                <a href="?ticket_status=open" class="<?php echo ($ticket_status === 'open') ? 'active' : ''; ?>">
                    Open Tickets (<?php echo esc_html($open_count); ?>)
                </a>
            </li>
            <li>
                <a href="?ticket_status=closed" class="<?php echo ($ticket_status === 'closed') ? 'active' : ''; ?>">
                    Closed Tickets (<?php echo esc_html($closed_count); ?>)
                </a>
            </li>
            <li>
                <a href="?ticket_status=in_progress" class="ticket-tab">
                    In Progress (<?php echo $in_progress_count; ?>)
                </a>
            </li>
        </ul>
        
</div>



<?php if (!empty($tickets)) : ?>
    <table border="1" cellpadding="10">
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Subject</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
        <?php foreach ($tickets as $ticket) : ?>
            <tr>
                <td><?php echo esc_html($ticket->id); ?></td>
                <td><?php echo esc_html(get_userdata($ticket->user_id)->user_login); ?></td>
                <td><?php echo esc_html($ticket->subject); ?></td>
                <td>
                    <select class="ticket-status" data-ticket-id="<?php echo esc_attr($ticket->id); ?>">
                        <option value="Open" <?php selected($ticket->status, 'Open'); ?>>Open</option>
                        <option value="In Progress" <?php selected($ticket->status, 'In Progress'); ?>>In Progress</option>
                        <option value="Closed" <?php selected($ticket->status, 'Closed'); ?>>Closed</option>
                    </select>
                    <span class="status-message" style="display:none; color: green;">Updated</span>
                </td>
                <td><?php echo esc_html($ticket->created_at); ?></td>
                <td>
                    <a href="?view_ticket=<?php echo esc_attr($ticket->id); ?>">View</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else : ?>
    <div class="dokan-support-topics-list">
        <div class="dokan-error">No tickets found! </div> 
    </div>
<?php endif; ?>



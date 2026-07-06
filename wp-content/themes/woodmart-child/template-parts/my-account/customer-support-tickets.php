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
$user_id = get_current_user_id();
$table_name = $wpdb->prefix . 'support_tickets';

// Fetch tickets for logged-in user
$tickets = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC", $user_id));

// Fetch ticket counts for each status
$ticket_counts = $wpdb->get_results($wpdb->prepare("
    SELECT status, COUNT(*) as count FROM $table_name WHERE user_id = %d GROUP BY status", $user_id), OBJECT_K);

// Get counts dynamically
$all_count = count($tickets);
$open_count = isset($ticket_counts['Open']) ? $ticket_counts['Open']->count : 0;
$closed_count = isset($ticket_counts['Closed']) ? $ticket_counts['Closed']->count : 0;
$in_progress_count = isset($ticket_counts['In Progress']) ? $ticket_counts['In Progress']->count : 0;

?>

<div class="dokan-support-customer-listing dokan-support-topic-wrapper">
    <ul class="dokan-support-topic-counts subsubsub" style="display:flex;">
        <li><a href="?ticket_status=all" class="ticket-tab active">All Tickets (<?php echo $all_count; ?>)</a> |</li>
        <li><a href="?ticket_status=open" class="ticket-tab">Open (<?php echo $open_count; ?>)</a> |</li>
        <li><a href="?ticket_status=closed" class="ticket-tab">Closed (<?php echo $closed_count; ?>)</a> |</li>
        <li><a href="?ticket_status=in_progress" class="ticket-tab">In Progress (<?php echo $in_progress_count; ?>)</a></li>
    </ul>

    <div class="dokan-support-topics-list">
        <?php if (!empty($tickets)) : ?>
            <table border="1" cellpadding="10">
                <tr>
                    <th>ID</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($tickets as $ticket) : ?>
                    <tr class="ticket-row" data-status="<?php echo strtolower($ticket->status); ?>">
                        <td><?php echo esc_html($ticket->id); ?></td>
                        <td><?php echo esc_html($ticket->subject); ?></td>
                        <td><?php echo esc_html($ticket->status); ?></td>
                        <td><?php echo esc_html($ticket->created_at); ?></td>
                        <td>
                            <a href="?view_ticket=<?php echo esc_attr($ticket->id); ?>">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php else : ?>
                <p>No support tickets found.</p>
                <p><a href="<?php echo esc_url(get_permalink(get_page_by_path('customer-support'))); ?>">Visit Customer Support</a></p>
            <?php endif; ?>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let tabs = document.querySelectorAll(".ticket-tab");
        let rows = document.querySelectorAll(".ticket-row");
        
        tabs.forEach(tab => {
            tab.addEventListener("click", function(event) {
                event.preventDefault();
                
                let status = this.getAttribute("href").split("=")[1]; // Extract status from URL
                
                tabs.forEach(t => t.classList.remove("active"));
                this.classList.add("active");

                rows.forEach(row => {
                    let rowStatus = row.getAttribute("data-status");
                    if (status === "all" || rowStatus === status) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            });
        });
    });
</script>

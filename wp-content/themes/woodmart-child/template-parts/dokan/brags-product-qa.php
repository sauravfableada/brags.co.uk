<?php
/**
 * Dokan My Ticket Page - Show Tickets Created by Customers
 */

if (!defined('ABSPATH')) {
    exit;
}

global $current_user,$wpdb;
get_header();

if (!dokan_is_user_seller($current_user->ID)) {
    dokan_get_template_part('global/account-denied');
    return;
}

$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

$per_page = 10;
$offset = ($paged - 1) * $per_page;
$total_items = 0;
$results = [];

$table = $wpdb->prefix . 'product_qna';

$seller_products = get_posts([
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'author'         => $current_user->ID,
    'fields'         => 'ids',
]);
$notif_msg = '';
// Handle answer submission
if (isset($_POST['submit_answer']) && isset($_POST['qna_id']) && in_array(intval($_POST['product_id']), $seller_products)) {
    $answer = sanitize_text_field($_POST['answer_text']);
    $qna_id = intval($_POST['qna_id']);
    $wpdb->update($table, [
        'answer_text' => $answer,
        'answer_user_id' =>$current_user->ID,
    ], ['id' => $qna_id]);

    $notif_msg = '<div class="alert alert-success">
        <strong>Success!</strong> Answer submitted successfully.
        </div>';
}
   
    if(!empty($seller_products)){
        
        $product_ids_placeholder = implode(',', array_map('intval', $seller_products));
        // Get total count
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE product_id IN ($product_ids_placeholder)");

         // Get paginated results
        $results = $wpdb->get_results("SELECT * FROM $table WHERE product_id IN ($product_ids_placeholder) ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");

        //$results = $wpdb->get_results("SELECT * FROM $table WHERE product_id IN ($product_ids_placeholder) ORDER BY created_at DESC");
    }
    

?>
<style>
    .dokan-pagination {
        margin-top: 20px;
        text-align: center;
    }
    .dokan-pagination .page-numbers {
        display: inline-block;
        margin: 0 5px;
        padding: 6px 12px;
        background: #f7f7f7;
        border: 1px solid #ccc;
        text-decoration: none;
    }
    .dokan-pagination .current {
        background: #0073aa;
        color: #fff;
        border-color: #0073aa;
    }

</style>
<div class="dokan-dashboard-wrap">
    <?php do_action('dokan_dashboard_content_before'); ?>

    <div class="dokan-dashboard-content">
        <div class="dokan-my-ticket">
            <div class="header-div">
                <div class="widget-title">
                    <i class="fas fa-question-circle"></i> Product Q&A
                   
                </div>
            </div>
            <?php if ($notif_msg!=''): 
                echo $notif_msg;
            endif; ?>
            <div id="Q&AList">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Question</th>
                            <th>Asked By</th>
                            <th>Answer</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): 
                            $product = get_post($row->product_id);
                            $asker = get_userdata($row->user_id);
                            $answered_by = $row->answer_user_id ? get_userdata($row->answer_user_id) : null;
                        ?>
                        <tr>
                            <td><a href="<?php echo get_permalink($row->product_id); ?>" target="_blank"><?php echo esc_html($product->post_title); ?></a></td>
                            <td><?php echo esc_html($row->question_text); ?></td>
                            <td><?php echo esc_html($asker->display_name); ?></td>
                            <td><?php echo $row->answer_text ? esc_html($row->answer_text) : '<em>Not answered</em>'; ?></td>
                            <td>
                                <?php if (!$row->answer_text): ?>
                                    <button class="button reply-button" data-id="<?php echo esc_attr($row->id); ?>" data-product="<?php echo esc_attr($row->product_id); ?>" data-question="<?php echo esc_attr($row->question_text); ?>">Reply</button>
                                <?php else: ?>
                                    Answered by: <?php echo $answered_by ? esc_html($answered_by->display_name) : 'Unknown'; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
                    $total_pages = ceil($total_items / $per_page);

                    if ($total_pages > 1):
                        echo '<div class="dokan-pagination">';
                        echo paginate_links([
                            //'base'    => add_query_arg('paged', '%#%'),
                            'base' => esc_url_raw( remove_query_arg('paged') ) . '?paged=%#%',
                            'format'  => '',
                            'current' => $paged,
                            'total'   => $total_pages,
                            'prev_text' => __('« Prev'),
                            'next_text' => __('Next »'),
                        ]);
                        echo '</div>';
                    endif;
                    ?>

            </div>

        </div>
    </div>

    <!-- Modal -->
<div id="qna-modal" style="display:none;">
    <div class="qna-modal-content">
        <h3>Submit Answer</h3>
        <p><strong>Question:</strong> <span id="modal-question"></span></p>
        <form method="POST">
            <textarea name="answer_text" rows="4" style="width:100%;" required></textarea>
            <input type="hidden" name="qna_id" id="modal-qna-id">
            <input type="hidden" name="product_id" id="modal-product-id">
            <p>
                <button type="submit" name="submit_answer" class="button button-primary">Submit</button>
                <button type="button" id="qna-close" class="button">Cancel</button>
            </p>
        </form>
    </div>
</div>

<style>
    #qna-modal {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .qna-modal-content {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 500px;
        box-shadow: 0 0 10px rgba(0,0,0,0.3);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('qna-modal');
        const questionSpan = document.getElementById('modal-question');
        const qnaIdInput = document.getElementById('modal-qna-id');
        const productIdInput = document.getElementById('modal-product-id');
        const closeBtn = document.getElementById('qna-close');

        document.querySelectorAll('.reply-button').forEach(button => {
            button.addEventListener('click', () => {
                qnaIdInput.value = button.dataset.id;
                productIdInput.value = button.dataset.product;
                questionSpan.textContent = button.dataset.question;
                modal.style.display = 'flex';
            });
        });

        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    });
</script>

    <?php do_action('dokan_dashboard_content_after'); ?>
</div>

<?php get_footer(); ?>

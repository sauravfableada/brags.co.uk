<?php

function create_qna_table_on_theme_activation() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'product_qna';

    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            question_text text NOT NULL,
            answer_text text DEFAULT NULL,
            user_id bigint(20) NOT NULL,
            answer_user_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
add_action('after_switch_theme', 'create_qna_table_on_theme_activation');

function enqueue_qna_scripts() {
    if (is_product()) {
        wp_enqueue_style('qna-style', get_stylesheet_directory_uri() . '/assets/css/qna.css');
        wp_enqueue_script('qna-ajax', get_stylesheet_directory_uri() . '/assets/js/qna-ajax.js', array('jquery'), null, true);
         // Pass product ID and other necessary data to JavaScript
         wp_localize_script('qna-ajax', 'qna_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qna_nonce'),
            'product_id' => get_the_ID() // Pass the current product ID dynamically
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_qna_scripts');



// Hook the Q&A section to the bottom of the product page
function add_qna_section_to_product_page() {
    
        
        echo do_shortcode('[qna_section product_id="' . get_the_ID() . '"]');
       
    
}
add_action('woocommerce_after_single_product', 'add_qna_section_to_product_page', 20);


function display_qna_section($atts) {
    $atts = shortcode_atts(
        array(
            'product_id' => get_the_ID(), // Default to the current product ID
        ),
        $atts,
        'qna_section'
    );

    $product_id = $atts['product_id'];

    ob_start();
    echo "<div class='container qna_section'>";
    // Display Q&A form for logged-in users
    
        ?>
        <h2>Customer Questions & Answers</h2>
        <?php 
         // Fetch and display existing questions
        global $wpdb;
        $qna_results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}product_qna WHERE product_id = %d ORDER BY created_at DESC", $product_id)
        );

        if ($qna_results) {
            foreach ($qna_results as $qna) {
                $question_user = get_user_by('ID', $qna->user_id);
                $answer_user = $qna->answer_user_id ? get_user_by('ID', $qna->answer_user_id) : null;

                echo '<div class="qna-item">';
                echo '<p><strong>Question by ' . esc_html($question_user->display_name) . ':</strong> ' . esc_html($qna->question_text) . '</p>';

                if ($qna->answer_text) {
                    echo '<p><strong>Answer by ' . ($answer_user ? esc_html($answer_user->display_name) : 'Brags Customer Service') . ':</strong> ' . esc_html($qna->answer_text) . '</p>';
                } else {
                    echo '<p><em>No answer yet</em></p>';
                }
                echo '</div>';
            }
        }

        if (is_user_logged_in() && current_user_can('customer') ) {
        ?>
        
        <form id="qna-form">
            <label for="qna-question">Ask a Question:</label>
            <textarea id="qna-question" name="qna_question" placeholder="Ask your question..." required></textarea>
            <button id="qna-submit" type="submit">Submit Question</button><div id="qna-message" style="display:none;"></div>
        </form>
        <?php
        } else if(!is_user_logged_in()){
            echo '<p>You need to be logged in as a customer to ask a question. <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">Login here</a>.</p>';
        }

        echo "</div>";

    return ob_get_clean();
}
add_shortcode('qna_section', 'display_qna_section');


function handle_qna_question_submission() {
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'qna_nonce')) {
        wp_send_json_error(array('message' => 'Nonce verification failed.'));
    }

    // Process form submission
    if (isset($_POST['question']) && isset($_POST['product_id'])) {
        $question_text = sanitize_textarea_field($_POST['question']);
        $product_id = intval($_POST['product_id']);
        $user_id = get_current_user_id();

        global $wpdb;
        $table_name = $wpdb->prefix . 'product_qna';

        $wpdb->insert(
            $table_name,
            array(
                'product_id' => $product_id,
                'question_text' => $question_text,
                'user_id' => $user_id
            )
        );

        wp_send_json_success();
    }

    wp_send_json_error(array('message' => 'Error posting question.'));
}
add_action('wp_ajax_submit_qna_question', 'handle_qna_question_submission');


//Add Custom Menu Page in Admin
function register_qna_admin_menu() {
    add_menu_page(
        'Product Q&A',
        'Product Q&A',
        'manage_woocommerce', // Admins or Shop Managers
        'product-qna',
        'render_qna_admin_page',
        'dashicons-format-chat',
        26
    );
}
add_action('admin_menu', 'register_qna_admin_menu');


add_filter('redirect_canonical', function($redirect_url){
    if (is_page('brags-product-qa') || strpos($_SERVER['REQUEST_URI'], 'dashboard/brags-product-qa') !== false) {
        return false;
    }
    return $redirect_url;
});


//Render the Q&A Admin Page
function render_qna_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'product_qna';

    // Handle answer submission via AJAX fallback
    if (isset($_POST['submit_answer']) && current_user_can('manage_woocommerce')) {
        $answer = sanitize_text_field($_POST['answer_text']);
        $qna_id = intval($_POST['qna_id']);
        $admin_id = get_current_user_id();

        $wpdb->update($table, [
            'answer_text' => $answer,
            'answer_user_id' => $admin_id
        ], ['id' => $qna_id]);

        echo '<div class="updated"><p>Answer submitted successfully.</p></div>';
    }

    // Handle edit submission
    if (isset($_POST['edit_answer']) && current_user_can('manage_woocommerce')) {
        $answer = sanitize_text_field($_POST['answer_text']);
        $qna_id = intval($_POST['qna_id']);
        $admin_id = get_current_user_id();

        $wpdb->update($table, [
            'answer_text' => $answer,
            'answer_user_id' => $admin_id
        ], ['id' => $qna_id]);

        echo '<div class="updated"><p>Answer updated successfully.</p></div>';
    }
    // Handle delete submission
    if (isset($_GET['delete_answer']) && current_user_can('manage_woocommerce')) {
        global $wpdb;
        $table = $wpdb->prefix . 'product_qna';
        $delete_id = intval($_GET['delete_answer']);
    
        $deleted = $wpdb->delete($table, ['id' => $delete_id]);
    
        if ($deleted !== false) {
            echo '<div class="updated notice"><p>Q&A deleted successfully.</p></div>';
        } else {
            echo '<div class="error notice"><p>Failed to delete the Q&A.</p></div>';
        }
    }

    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");

    ?>
    <div class="wrap">
        <h1>Customer Questions & Answers</h1>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Question</th>
                    <th>Asked By</th>
                    <th>Answer</th>
                    <th>Answered By</th>
                    <th>Reply</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) :
                    $product_title = get_the_title($row->product_id);
                    $user = get_userdata($row->user_id);
                    $answer_user = $row->answer_user_id ? get_userdata($row->answer_user_id) : null;
                ?>
                    <tr>
                        <td><?php echo esc_html($row->id); ?></td>
                        <td>
                            <a href="<?php echo get_permalink($row->product_id); ?>" target="_blank">
                                <?php echo esc_html($product_title); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($row->question_text); ?></td>
                        <td><?php echo esc_html($user->display_name); ?></td>
                        <td><?php echo $row->answer_text ? esc_html($row->answer_text) : '<em>Not answered yet</em>'; ?></td>
                        <td><?php echo $answer_user ? esc_html($answer_user->display_name) : '-'; ?></td>
                        <td>
                            <?php if (!$row->answer_text) : ?>
                                <button class="button reply-button" data-id="<?php echo $row->id; ?>" data-question="<?php echo esc_attr($row->question_text); ?>">Reply</button>
                                <a href="<?php echo admin_url('admin.php?page=product-qna&delete_answer=' . $row->id); ?>" class="button delete-button" onclick="return confirm('Are you sure you want to delete this answer?');">Delete</a>
                                <?php else : ?>
                                <div>
                                    <?php echo esc_html($row->answer_text); ?>
                                    <br>
                                    <button class="button edit-button" data-id="<?php echo $row->id; ?>" data-answer="<?php echo esc_attr($row->answer_text); ?>">Edit</button>
                                    <a href="<?php echo admin_url('admin.php?page=product-qna&delete_answer=' . $row->id); ?>" class="button delete-button" onclick="return confirm('Are you sure you want to delete this answer?');">Delete</a>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Modal for answering -->
        <div id="qna-modal" style="display:none;">
            <div class="qna-modal-content">
                <h2>Submit Answer</h2>
                <p><strong>Question:</strong> <span id="modal-question"></span></p>
                <form method="POST">
                    <textarea name="answer_text" rows="4" style="width:100%;" required></textarea>
                    <input type="hidden" name="qna_id" id="modal-qna-id" value="">
                    <p>
                        <button type="submit" name="submit_answer" class="button button-primary">Submit</button>
                        <button type="button" id="qna-close" class="button">Cancel</button>
                    </p>
                </form>
            </div>
        </div>
        <!-- Edit Answer Modal -->
        <div id="qna-edit-modal" style="display:none;">
            <div class="qna-modal-content">
                <h2>Edit Answer</h2>
                <form method="POST">
                    <textarea name="answer_text" rows="4" style="width:100%;" id="edit-answer-text" required></textarea>
                    <input type="hidden" name="qna_id" id="edit-qna-id" value="">
                    <p>
                        <button type="submit" name="edit_answer" class="button button-primary">Update</button>
                        <button type="button" id="qna-edit-close" class="button">Cancel</button>
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
            #qna-modal, #qna-edit-modal {
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
                const questionText = document.getElementById('modal-question');
                const qnaIdInput = document.getElementById('modal-qna-id');
                const closeBtn = document.getElementById('qna-close');

                document.querySelectorAll('.reply-button').forEach(button => {
                    button.addEventListener('click', () => {
                        const qnaId = button.getAttribute('data-id');
                        const question = button.getAttribute('data-question');

                        qnaIdInput.value = qnaId;
                        questionText.textContent = question;
                        modal.style.display = 'flex';
                    });
                });

                closeBtn.addEventListener('click', () => {
                    modal.style.display = 'none';
                });


                const editModal = document.getElementById('qna-edit-modal');
                const editBtn = document.getElementById('qna-edit-close');
                const editAnswerText = document.getElementById('edit-answer-text');
                const editQnaId = document.getElementById('edit-qna-id');

                document.querySelectorAll('.edit-button').forEach(button => {
                    button.addEventListener('click', () => {
                        const qnaId = button.getAttribute('data-id');
                        const answer = button.getAttribute('data-answer');

                        editQnaId.value = qnaId;
                        editAnswerText.value = answer;
                        editModal.style.display = 'flex';
                    });
                });

                editBtn.addEventListener('click', () => {
                    editModal.style.display = 'none';
                });

            });
        </script>
    </div>
    <?php
}





function add_dokan_product_qa_menu( $urls ) {
    $current_user = wp_get_current_user();

    if ( in_array( 'seller', $current_user->roles ) ) {
        $urls['brags-product-qa'] = array(
            'title'      => __( 'Product Q&A', 'dokan' ),
            'icon'       => '<i class="fas fa-question-circle"></i>',
            'url'        => dokan_get_navigation_url( 'brags-product-qa' ),
            'pos'        => 68, // Position in the menu
            'permission' => 'dokan_view_product_menu',
        );
    }

    return $urls;
}
add_filter( 'dokan_get_dashboard_nav', 'add_dokan_product_qa_menu' );

function register_dokan_product_qa_query_var( $query_vars ) {
    $query_vars[] = 'brags-product-qa';
    return $query_vars;
}
add_filter( 'query_vars', 'register_dokan_product_qa_query_var' );

function load_dokan_product_qa_template() {
    global $wp_query;

    if ( isset( $wp_query->query_vars['brags-product-qa'] ) ) {
        $template_path = get_stylesheet_directory() . '/template-parts/dokan/brags-product-qa.php';

        if ( file_exists( $template_path ) ) {
            include $template_path;
            exit;
        } else {
            wp_die( 'Product Q&A template not found.' );
        }
    }
}
add_action( 'template_redirect', 'load_dokan_product_qa_template' );

function flush_product_qa_rewrite_rules() {
    add_rewrite_endpoint( 'brags-product-qa', EP_ROOT | EP_PAGES );
    flush_rewrite_rules();
}
add_action( 'init', 'flush_product_qa_rewrite_rules' );



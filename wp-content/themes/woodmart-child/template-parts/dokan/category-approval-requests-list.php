<?php
global $wpdb, $current_user;
$requests = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}category_approval_requests WHERE user_id = %d",
        $current_user->ID
    ),
    ARRAY_A
);
?>

<div class="category-requests-container">
    <?php if (!empty($requests)): ?>
        <?php foreach ($requests as $request): ?>
            <div class="request-card" id="request-<?php echo esc_attr($request['id']); ?>">
                <div class="request-header">
                    <h4><?php echo esc_html(get_term($request['category_id'])->name); ?></h4>
                    <span class="status-badge <?php echo strtolower($request['status']); ?>">
                        <?php echo esc_html($request['status']); ?>
                    </span>
                </div>
                
                <div class="request-body">
                    <p><strong>Documents:</strong></p>
                    <div class="document-links">
                        <?php 
                        $docs = json_decode($request['documents'], true);
                        foreach ($docs as $doc): ?>
                            <a href="<?php echo esc_url($doc); ?>" target="_blank" class="doc-link">
                                 View Document
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Edit & Delete Buttons -->
                    <div class="request-actions">
                        <?php 
                        if($request['status']!='Approved'){
                            ?>
                                 <button class="edit-btn" data-id="<?php echo esc_attr($request['id']); ?>"> Edit</button>
                            <?php
                        }
                        ?>
                       
                        <button class="delete-btn" data-id="<?php echo esc_attr($request['id']); ?>"> Delete</button>
                    </div>

                    <!-- Edit Form (Hidden Initially) -->
                    <div class="edit-form" id="edit-form-<?php echo esc_attr($request['id']); ?>" style="display: none;">
                        <form class="edit-category-form" data-id="<?php echo esc_attr($request['id']); ?>" enctype="multipart/form-data">
                            
                        <div>
                            <label for="edit-category-<?php echo esc_attr($request['id']); ?>">Category:</label>
                            <select name="category" id="edit-category-<?php echo esc_attr($request['id']); ?>" required>
                                <?php
                                $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
                                foreach ($categories as $category) {
                                    $selected = ($category->term_id == $request['category_id']) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <input type="file" name="category_docs[]" multiple>
                        </div>
                            
                        <div style="margin-top: 20px;">
                            <button type="submit" class="float-right">Save Changes</button>
                        </div>
                            
                        </form>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-requests">No category approval requests found.</p>
    <?php endif; ?>
</div>

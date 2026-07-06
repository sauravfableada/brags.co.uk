<?php
if (!is_user_logged_in()) {
    echo '<p>You must be logged in to manage brands.</p>';
    return;
}

$current_user_id = get_current_user_id();
$current_user = get_userdata($current_user_id);

// Get user's profile image (Gravatar)
$default_gravatar = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($current_user->user_email))) . '?d=mp';

// Query user's brands
$args = array(
    'post_type'      => 'brand',
    'posts_per_page' => -1, // Show all brands of the user
    'author'         => $current_user_id,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'post_status'    => array('publish', 'pending', 'draft', 'private'), // Get all statuses
);

$query = new WP_Query($args);
?>

<div id="my-brands-section" class="ur-form-row">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>My Brands</h2>
        <a href="<?php echo site_url('brags-brand-network-account/add-brand'); ?>" class="button ">
            + Register a New Brand
        </a>
    </div>

    <?php if ($query->have_posts()) : ?>
        <ul class="ur-list">
            <?php while ($query->have_posts()) : $query->the_post();
                $brand_id = get_the_ID();
                $brand_logo = get_post_meta($brand_id, 'brand_logo', true);
                $trademark_number = get_post_meta($brand_id, 'trademark_number', true);
                $post_status = get_post_status($brand_id);

                // Use brand logo if available, otherwise fallback to user's Gravatar
                $brand_logo_url = !empty($brand_logo) ? esc_url($brand_logo) : $default_gravatar;

                // Map post status to labels
                $status_labels = array(
                    'publish' => '<span class="status-label published">Published</span>',
                    'pending' => '<span class="status-label pending">Pending Approval</span>',
                    'draft'   => '<span class="status-label draft">Draft</span>',
                    'private' => '<span class="status-label private">Private</span>',
                );

                $status_label = isset($status_labels[$post_status]) ? $status_labels[$post_status] : '<span class="status-label unknown">Unknown</span>';
            ?>
                <li class="ur-list-item brand-li">
                    <div>
                        <!-- Brand Logo -->
                        <img class="brand-li-logo" src="<?php echo $brand_logo_url; ?>" alt="Brand Logo">
                    </div>
                    <div class="all_brand_details">
                    <!-- Brand Details -->
                    <div style="flex-grow: 1;">
                        <strong><?php the_title(); ?></strong>
                        <?php echo $status_label; // Display status label ?>
                        <?php if ($trademark_number) : ?>
                            <p style="margin: 5px 0; font-size: 14px;">Trademark #: <?php echo esc_html($trademark_number); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div style="display: flex; gap: 10px;">
                        <?php if($post_status!="publish"){ ?>
                        <button data-id="<?php echo $brand_id; ?>" class="edit-brand" title="Edit" onclick="editBrand(<?php echo $brand_id; ?>)">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <?php } ?>
                        <button data-id="<?php echo $brand_id; ?>" class="delete-brand" title="Delete" onclick="deleteBrand(<?php echo $brand_id; ?>)">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                        </div>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else : ?>
        <p class="ur-message">No brands found.</p>
    <?php endif; ?>


    <?php wp_reset_postdata(); ?>
</div>

<script>
function editBrand(brandId) {
    window.location.href = "<?php echo site_url('brags-brand-network-account/edit-brand/?brand_id='); ?>" + brandId;
}

function deleteBrand(brandId) {
    if (confirm("Are you sure you want to delete this brand?")) {
        jQuery.ajax({
            url: "<?php echo admin_url('admin-ajax.php'); ?>",
            type: "POST",
            data: {
                action: "delete_brand",
                brand_id: brandId
            },
            success: function (response) {
                if (response.success) {
                    alert("Brand deleted successfully!");
                    location.reload(); // Refresh page after deletion
                } else {
                    alert("Error deleting brand: " + response.data.error);
                }
            }
        });
    }
}
</script>

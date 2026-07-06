<?php
if (!is_user_logged_in()) {
    echo '<p>You must be logged in to edit a brand.</p>';
    return;
}

if (!isset($_GET['brand_id'])) {
    echo '<p>Brand ID is missing.</p>';
    return;
}

$brand_id = intval($_GET['brand_id']);
$current_user_id = get_current_user_id();
$brand = get_post($brand_id);

$current_user = wp_get_current_user();
$user_email = $current_user->user_email;



if (!$brand || $brand->post_type !== 'brand' || $brand->post_author != $current_user_id) {
    echo '<p>You do not have permission to edit this brand.</p>';
    return;
}

// Retrieve all meta fields at once
$meta_fields = [
    'brand_name', 'trademark_office', 'trademark_number', 'brand_description',
    'business_name', 'business_address', 'phone_number', 'primary_contact', 'business_email',
    'website_url', 'manufacturing_locations', 'distribution_channels',
    'product_categories', 'sell_on_brags', 'approve_resellers',
    'brags_seller_email', 'brags_store_url', 'country', 'brand_logo'
];

$brand_meta = [];
foreach ($meta_fields as $field) {
    $brand_meta[$field] = get_post_meta($brand_id, $field, true);
}


// Retrieve Product IDs & Images
$product_ids = [];
$product_images = [];
for ($i = 1; $i <= 5; $i++) {
    $product_ids[$i] = get_post_meta($brand_id, "product_ids_$i", true);
    $product_images[$i] = get_post_meta($brand_id, "product_images_$i", true);
}


// Retrieve Additional Documents
$additional_documents = get_post_meta($brand_id, "additional_documents", true);;

?>
<form method="POST" enctype="multipart/form-data" id="edit-brand-form">
    <input type="hidden" name="brand_id" value="<?php echo $brand_id; ?>">
    <div class="user-registration-profile-fields__field-wrapper">
        <label>Brand Name:</label>
        <input type="text" name="brand_name" value="<?= esc_attr($brand->post_title); ?>" required>
        
        <label>Trademark Office:</label>
        <input type="text" name="trademark_office" value="<?= esc_attr($brand_meta['trademark_office']); ?>">
        
        <label>Trademark Number:</label>
        <input type="text" name="trademark_number" value="<?= esc_attr($brand_meta['trademark_number']); ?>">
        
        <label>Brand Description:</label>
        <textarea name="brand_description"><?= esc_textarea($brand_meta['brand_description']); ?></textarea>

        <label>Business Name:</label>
        <input type="text" name="business_name" value="<?= esc_attr($brand_meta['business_name']); ?>" required>

        <label>Business Address:</label>
        <textarea name="business_address"><?= esc_textarea($brand_meta['business_address']); ?></textarea>

        <label>Phone Number:</label>
        <div style="display: flex;">
            <input type="text" name="country" value="<?= esc_attr($brand_meta['country']); ?>" required>
            <input type="text" name="phone_number" value="<?= esc_attr($brand_meta['phone_number']); ?>">
        </div>

        <label>Primary Contact:</label>
        <input type="text" name="primary_contact" value="<?= esc_attr($brand_meta['primary_contact']); ?>">

        <label>Business Email:</label>
        <input type="email" name="business_email" value="<?= esc_attr($user_email); ?>" disabled>

        <label>Website URL:</label>
        <input type="url" name="website_url" value="<?= esc_url($brand_meta['website_url']); ?>">

        <label>Manufacturing Locations:</label>
        <textarea name="manufacturing_locations"><?= esc_textarea($brand_meta['manufacturing_locations']); ?></textarea>

        <label>Distribution Channels:</label>
        <textarea name="distribution_channels"><?= esc_textarea($brand_meta['distribution_channels']); ?></textarea>

        <label>Product Categories:</label>
        <input type="text" name="product_categories" value="<?= esc_attr($brand_meta['product_categories']); ?>">

        <label>Sell on Brags:</label>
        <input type="checkbox" name="sell_on_brags" value="1" <?= $brand_meta['sell_on_brags'] ? 'checked' : ''; ?>>

        <label>Approve Resellers:</label>
        <input type="checkbox" name="approve_resellers" value="1" <?= $brand_meta['approve_resellers'] ? 'checked' : ''; ?>>

        <label>Brags Seller Email:</label>
        <input type="email" name="brags_seller_email" value="<?= esc_attr($brand_meta['brags_seller_email']); ?>">

        <label>Brags Store URL:</label>
        <input type="url" name="brags_store_url" value="<?= esc_url($brand_meta['brags_store_url']); ?>">

        <label>Brand Logo:</label>
        <?php if (!empty($brand_meta['brand_logo'])): ?>
            <img src="<?= esc_url($brand_meta['brand_logo']); ?>" width="100" height="100">
        <?php endif; ?>
        <input type="file" name="brand_logo">

        <?php for ($i = 1; $i <= 5; $i++): ?>
            <label>Product ID <?= $i ?>:</label>
            <input type="text" name="product_ids_<?= $i ?>" value="<?= esc_attr($product_ids[$i]); ?>">
            
            <label>Product Image <?= $i ?>:</label>
            <?php if (!empty($product_images[$i])): ?>
                <img src="<?= esc_url($product_images[$i]); ?>" width="100" height="100">
            <?php endif; ?>
            <input type="file" name="product_images_<?= $i ?>">
        <?php endfor; ?>

        <label>Additional Documents:</label>
        <?php if ($additional_documents): ?>
            <a href="<?= esc_url($additional_documents); ?>" target="_blank">View Document</a><br>
        <?php endif; ?>
        <input type="file" name="additional_documents">

        <button type="button" id="update_profile">Update Profile</button>
        <div id="update-message"></div>
    </div>
</form>

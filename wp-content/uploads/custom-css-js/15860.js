<!-- start Simple Custom CSS and JS -->
<script type="text/javascript">
jQuery(document).ready(function( $ ){
    // Select all the form input fields you want to modify
	const form = document.forms[".woocommerce-form-register.register"];
const inputs = document.querySelectorAll('input[name="username"], input[name="email"], input[name="password"], input[name="fname"], input[name="lname"], input[name="shopname"], input[name="shopurl"], input[name="phone"]');

// Loop through each input element
inputs.forEach(input => {
  const label = document.querySelector(`label[for="${input.id}"]`);

  // Check if the label exists, then remove it and add a placeholder
  if (label) {
    input.setAttribute('placeholder', label.textContent);
    label.style.display = 'none'; // Hide the label
  }
});

});

jQuery(document).ready(function($) { $('.woocommerce-form-register .form-row label[for="tc_agree"]').html('I confirm that I have read and agree to the <a target="_blank" href="https://brags.co.uk/terms-and-conditions/">Terms & Conditions</a> and <a target="_blank" href="https://brags.co.uk/seller-policy/">Brags Seller Policy</a>. I understand that I/my company must hold the legal rights to sell my products in the UK, maintain my own Product Liability Insurance and acknowledge that Brags & Partners Ltd holds no responsibility for the products I choose to sell on Brags.co.uk.');});

jQuery(document).ready(function($) { $('.user-registration-MyAccount-navigation-link--dashboard a, .user-registration-MyAccount-content__header h1').html('Brags Brand Network');});

jQuery(document).ready(function($) {
	
	 $(document).ready(function() {
        // Create a new label element
        var newLabel = $('<label>').text('Variation Types');

        // Prepend the label before the select element
        $('.dokan-attribute-type').prepend(newLabel);
    });

	$('.dokan-btn-default.add_new_attribute').hide();
    $('#predefined_attribute').on('change', function () {
              var selectedOption = $(this).find('option:selected');
        if ($(this).val() && !selectedOption.is(':disabled')) {
            $('.dokan-btn-default.add_new_attribute').show();
        } else {
            $('.dokan-btn-default.add_new_attribute').hide();
        }
    });
});

jQuery(document).ready(function($) {
    $('.role-seller.dokan-dashboard div#dokan-shipping-zone .dokan-form-group:nth-child(2)').after('<div class="custom"><p>“As a Brags Seller, you are responsible for offering Shipping & handling Returns to customers throughout all areas of the UK. For any queries, please contact the Brags Seller Team.”</p></div>');
	$('.dokan-dashboard-content.dokan-support-listing.dokan-support-topic-wrapper header.dokan-dashboard-header .entry-title').after('<div class="custom"><p style="color: #ff9c00;">“Please aim to respond to all customer messages within 24hrs Monday to Friday excluding UK Bank Holidays. Late responses or issues not being handled fairly can affect your Brags seller sore.”</p></div>');
});


jQuery(document).ready(function($) { 
    $('li#tab-title-shipping a.wd-nav-link span, div#tab-item-title-shipping .wd-accordion-title-text span').html('Seller Shipping Info');
    $('li#tab-title-reviews a.wd-nav-link span, div#tab-item-title-reviews .wd-accordion-title-text span').html('Product Reviews');
    $('li#tab-title-seller a.wd-nav-link span, div#tab-item-title-seller .wd-accordion-title-text span').html('Seller Info');
	 $('.cart-widget-side.wd-side-hidden.wd-right .wd-heading span.title').html('Shopping Basket');
	
	
	$('.role-seller .woocommerce-MyAccount-navigation-link.woocommerce-MyAccount-navigation-link--dashboard a, .role-seller .woocommerce-MyAccount-navigation-link.woocommerce-MyAccount-navigation-link--dashboard a span').html('Seller Dashboards');
	
	$('body.role-seller.dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li.category-approval a').html('<i class="fas fa-file-upload"></i> Category Evaluation');
	
	$('.role-customer .woocommerce-MyAccount-navigation-link.woocommerce-MyAccount-navigation-link--dashboard a, .role-seller .woocommerce-MyAccount-navigation-link.woocommerce-MyAccount-navigation-link--dashboard a span').html('Customer Dashboard');
	
	$('.wd-toolbar .wd-header-cart.wd-design-5 span.wd-toolbar-label').html('Basket');
	$('.role-customer li.woocommerce-MyAccount-navigation-link.woocommerce-MyAccount-navigation-link--support-tickets a').html('Seller Messages');
	
	$('.dokan-dashboard-content.dokan-support-listing.dokan-support-topic-wrapper header.dokan-dashboard-header .entry-title').html('Customer Messages');
	
	
$('.theme-woodmart .dokan-single-store .dokan-store-tabs ul.dokan-list-inline li:last-child a,.theme-woodmart #vendor-biography #comments .headline').html('About the Seller');
	
	$('body.role-seller .dokan-form-horizontal input[name="settings_dokan_company_id_number"]').attr('placeholder', 'Companies House Number');
	$('body.role-seller .dokan-form-horizontal input[name="dokan_support_btn_name"]').attr('placeholder', 'Contact Seller');
	$('form#store-form .dokan-form-group.biography label[for="biography"]').html('About you and your Business');

});

jQuery(document).ready(function($) {
  $('.star-rating span').each(function() {
    var rating = $(this).text().trim();
    $(this).text(rating.replace('Rated', '').replace('out of 5', '').trim());
  });

  // Optional: Also remove the aria-label text "Rated X out of 5"
  $('.star-rating').each(function() {
    var aria = $(this).attr('aria-label');
    if (aria) {
      var number = aria.match(/[\d.]+/); // extract number like 2.00
      if (number) {
        $(this).attr('aria-label', number[0]); // set just the number
      }
    }
  });
});


// jQuery(function($) {
//     function isAllowedPage() {
//         return $('body').is('.home, .archive, .page-template-top-sellers, .product-category');
//     }

//     function updateCartButtons() {
//         if (isAllowedPage() && $(window).width() >= 1024) {
//             $('.wd-bottom-actions').addClass('wd-add-small-btn');
//             $('.wd-add-btn').removeClass('wd-add-btn-replace')
//                             .addClass('wd-action-btn wd-style-icon wd-add-cart-icon');
//         } else {
//             $('.wd-bottom-actions').removeClass('wd-add-small-btn');
//             $('.wd-add-btn').removeClass('wd-action-btn wd-style-icon wd-add-cart-icon');
//         }
//     }

//     $(window).on('load resize', function() {
//         updateCartButtons();
//     });
// });



jQuery(document).ready(function($) {
    $('.dokan-spmv-add-new-product-search-box-area.dokan-w13.section-closed')
        .removeClass('section-closed');
	$('.dokan-spmv-add-new-product-search-box-area.dokan-w13.section-closed')
        .attr('class', 'dokan-spmv-add-new-product-search-box-area dokan-w13');
	
});



// jQuery(document).ready(function($) { $('.woocommerce-form-register .form-row label[for="tc_agree"]').html('Please confirm that you have read and agree to our <a target="_blank" href="https://brags.co.uk/terms-and-conditions/">Terms & Conditions</a> and <a target="_blank" href="https://brags.co.uk/seller-policy/">Brags Seller Policy</a>.');});


</script>
<!-- end Simple Custom CSS and JS -->

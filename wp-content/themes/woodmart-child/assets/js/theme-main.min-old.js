jQuery(document).ready(function($){const form=document.forms[".woocommerce-form-register.register"];const inputs=document.querySelectorAll('input[name="username"], input[name="email"], input[name="password"], input[name="fname"], input[name="lname"], input[name="shopname"], input[name="shopurl"], input[name="phone"]');inputs.forEach(input=>{const label=document.querySelector(`label[for="${input.id}"]`);if(label){input.setAttribute('placeholder',label.textContent);label.style.display='none'}})});jQuery(document).ready(function($){$('.woocommerce-form-register .form-row label[for="tc_agree"]').html('I confirm that I have read and agree to the <a target="_blank" href="https://brags.co.uk/terms-and-conditions/">Terms & Conditions</a> and <a target="_blank" href="https://brags.co.uk/seller-policy/">Brags Seller Policy</a>. I understand that I/my company must hold the legal rights to sell my products in the UK, maintain my own Product Liability Insurance and acknowledge that Brags & Partners Ltd holds no responsibility for the products I choose to sell on Brags.co.uk.')});jQuery(document).ready(function($){$('.user-registration-MyAccount-navigation-link--dashboard a, .user-registration-MyAccount-content__header h1').html('Brags Brand Network')});jQuery(document).ready(function($){$(document).ready(function(){var newLabel=$('<label>').text('Variation Types');$('.dokan-attribute-type').prepend(newLabel)});$('.dokan-btn-default.add_new_attribute').hide();$('#predefined_attribute').on('change',function(){var selectedOption=$(this).find('option:selected');if($(this).val()&&!selectedOption.is(':disabled')){$('.dokan-btn-default.add_new_attribute').show()}else{$('.dokan-btn-default.add_new_attribute').hide()}})});jQuery(document).ready(function($){$('.role-seller.dokan-dashboard div#dokan-shipping-zone .dokan-form-group:nth-child(2)').after('<div class="custom"><p>“As a Brags Seller, you are responsible for offering Shipping & handling Returns to customers throughout all areas of the UK. For any queries, please contact the Brags Seller Team.”</p></div>');$('.dokan-dashboard-content.dokan-support-listing.dokan-support-topic-wrapper header.dokan-dashboard-header .entry-title').after('<div class="custom"><p style="color: #ff9c00;">“Please aim to respond to all customer messages within 24hrs Monday to Friday excluding UK Bank Holidays. Late responses or issues not being handled fairly can affect your Brags seller sore.”</p></div>')});jQuery(document).ready(function($){$('li#tab-title-shipping a.wd-nav-link span, div#tab-item-title-shipping .wd-accordion-title-text span').html('Seller Shipping Info');$('li#tab-title-reviews a.wd-nav-link span, div#tab-item-title-reviews .wd-accordion-title-text span').html('Product Reviews');$('li#tab-title-seller a.wd-nav-link span, div#tab-item-title-seller .wd-accordion-title-text span').html('Seller Info');$('.cart-widget-side.wd-side-hidden.wd-right .wd-heading span.title').html('Shopping Basket');$('.role-seller .woocommerce-MyAccount-navigation-link.woocommerce-MyAccount-navigation-link--dashboard a, .role-seller .woocommerce-MyAccount-navigation-link.woocommerce-MyAccount-navigation-link--dashboard a span').html('Seller Dashboards');$('body.role-seller.dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li.category-approval a').html('<i class="fas fa-file-upload"></i> Category Evaluation');$('.role-customer .woocommerce-MyAccount-navigation-link.woocommerce-MyAccount-navigation-link--dashboard a, .role-seller .woocommerce-MyAccount-navigation-link.woocommerce-MyAccount-navigation-link--dashboard a span').html('Customer Dashboard');$('.wd-toolbar .wd-header-cart.wd-design-5 span.wd-toolbar-label').html('Basket');$('.role-customer li.woocommerce-MyAccount-navigation-link.woocommerce-MyAccount-navigation-link--support-tickets a').html('Seller Messages');$('.dokan-dashboard-content.dokan-support-listing.dokan-support-topic-wrapper header.dokan-dashboard-header .entry-title').html('Customer Messages');$('.theme-woodmart .dokan-single-store .dokan-store-tabs ul.dokan-list-inline li:last-child a,.theme-woodmart #vendor-biography #comments .headline').html('About the Seller');$('body.role-seller .dokan-form-horizontal input[name="settings_dokan_company_id_number"]').attr('placeholder','Companies House Number');$('body.role-seller .dokan-form-horizontal input[name="dokan_support_btn_name"]').attr('placeholder','Contact Seller');$('form#store-form .dokan-form-group.biography label[for="biography"]').html('About you and your Business')});jQuery(document).ready(function($){$('.dokan-spmv-add-new-product-search-box-area.dokan-w13.section-closed').removeClass('section-closed');$('.dokan-spmv-add-new-product-search-box-area.dokan-w13.section-closed').attr('class','dokan-spmv-add-new-product-search-box-area dokan-w13')});jQuery(document).ready(function($){$('.wd-product.wd-hover-base .wd-bottom-actions .wd-action-btn.wd-style-icon > a').on('click',function(e){e.preventDefault();var button=$(this);button.css('background-color','var(--btn-accented-bgcolor-hover)')})})


    jQuery(document).ready(function($) {
        $(document).on('click', '.add-to-cart-loop', function(e) {
          // Remove 'active' class from all buttons
        //   $('.add-to-cart-loop').removeClass('active');

          $(this).addClass('active');
        });

      });



jQuery(document).ready(function ($) {
  // Change the href of the shop link to the top-seller page
  $('.wd-toolbar-shop a').attr('href', 'https://brags.co.uk/top-seller/');
  

  // Change the text inside the toolbar label only if it's inside a toolbar with class 'wd-toolbar-label-show'
  $(".wd-toolbar.wd-toolbar-label-show .wd-toolbar-shop .wd-toolbar-label").text("Top Sellers");

  // Change various text elements related to selling plans/orders
  $(".dokan-header-title-section .dokan-header-title h3").text("Selling Plan");
  $(".dokan-tab-panel #tab-panel-0-packs").html("Selling Plans");
  $(".dokan-tab-panel #tab-panel-0-orders").text("Selling Plan Orders");

  // There are two similar selectors changing h4 text, to make sure both are updated
  $("#tab-panel-0-packs-view .dokan-layout.mb-5 div h4").text("Current Selling Plan");
  $("#tab-panel-0-packs-view .dokan-layout div h4").text("Current Selling Plan");
});


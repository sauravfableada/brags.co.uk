<!-- start Simple Custom CSS and JS -->
<script type="text/javascript">
   jQuery(document).ready(function($) {
	   $('.sidebar-container.col-lg-3.col-md-3.col-12.order-last.order-md-first.sidebar-left.area-sidebar-shop.top-sellers-sidebar.wd-side-hidden.wd-left.wd-inited.wd-scroll')
    .attr('id', 'top-categories-sidebar');
	   
      if ($('#open-sidebar-btn').length === 0) {
        $('body.page-id-17444 .shop-content-area .filter-header.row .products-per-page').after('<div class="custom"><button style="cursor: pointer;color: #ff9c00;" id="open-sidebar-btn" class="mobile-sidebar-toggle">☰ Category</button></div>');
      }
 if ($('#close-sidebar-btn').length === 0) {
        $('#top-categories-sidebar').prepend('<div id="close-sidebar-btn">×</div>');
      }

      $(document).on('click', '#open-sidebar-btn', function(e) {
        e.preventDefault();
        $('#top-categories-sidebar').addClass('open');
        $('body').addClass('no-scroll');
      });

      $(document).on('click', '#close-sidebar-btn', function() {
        $('#top-categories-sidebar').removeClass('open');
        $('body').removeClass('no-scroll');
      });
            
     
    });
</script>
<!-- end Simple Custom CSS and JS -->

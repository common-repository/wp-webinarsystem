<?php
/**
* Register a custom Webinar product type when ticket is created
* 
* @return
*/
function register_webinar_product_type() {
    $is_ready = WebinarSysteemWooCommerceIntegration::is_ready();
    if($is_ready)
    {
		class WC_Product_Webinar extends WC_Product 
		{
			public function __construct( $product ) {
				$this->product_type = 'webinar';
				parent::__construct( $product );
			}
		}
    }
	}
add_action('init', 'register_webinar_product_type');
/**
* Show Inventory and Price fields in custom product type
* 
* @return
*/
function webinar_custom_js() {
    if ( 'product' != get_post_type() ) :
        return;
    endif;
    ?><script type='text/javascript'>
        jQuery( document ).ready( function() {
            jQuery( '.options_group.pricing' ).addClass( 'show_if_webinar' ).show();
            jQuery('.inventory_options').addClass('show_if_webinar').show();
            jQuery('#inventory_product_data .options_group').addClass('show_if_webinar').show();
            jQuery('#inventory_product_data ._sold_individually_field').addClass('show_if_webinar').show();
        });
    </script><?php
}
add_action( 'admin_footer', 'webinar_custom_js' );
if (! function_exists( 'woocommerce_webinar_add_to_cart' ) ) {
  /**
  * Output the simple product add to cart area.
  *
  * @subpackage Product
  */
  function webinar_add_to_cart() {
    wc_get_template( 'single-product/add-to-cart/simple.php' );
  }
  add_action('woocommerce_webinar_add_to_cart',  'webinar_add_to_cart');
}
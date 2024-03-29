<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-builder-elementor-templates/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     9.9.9.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
?>
<div class="woocommerce woocommerce-order dtwcbe-woocommerce-thankyou">
<?php
do_action('dtwcbe_thankyou_content');
?>

<?php 
if ( $order ) : 
do_action( 'woocommerce_thankyou', $order->get_id() ); 
endif; 
?>
</div>

<?php
/**
 * Cart Page
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 9.9.9.9
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_builder_elementor_before_cart' );
?>
<div class="woocommerce woocommerce-builder-elementor-cart">
	<?php 
	wc_print_notices();
	do_action('dtwcbe_cart_content'); ?>
	<?php do_action( 'woocommerce_after_cart' ); ?>
</div>
<?php do_action( 'woocommerce_builder_elementor_after_cart' ); ?>
<?php
/**
* Plugin Name: WooCommerce Page Builder For Elementor(Eight)
* Description: اختصاصی شده افزونه WooCommerce Page Builder For Elementor
* Version: 1.1
* Author: Eight
* Author URI: https://eightco.org
* License: License GNU General Public License version 2 or later
* Text-domain: woocommerce-builder-elementor
* WC tested up to: 6.3.1
* 
* @package WooCommerce-Builder-Elementor
*/

function moein(){
	echo'
	<input id="moein" type="text" value="By Moein Qorbani - https://Sitetik.ir" hidden> 
	<script>
	 //console.log("By Moein Qorbani - https://Sitetik.ir"); 
	</script>
	';
}
add_action( 'wp_footer', 'moein');
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define
define( 'DTWCBE_VERSION', '1.1.6.6.2' );

define( 'DTWCBE__FILE__', __FILE__ );
define( 'DTWCBE_PLUGIN_FILE', wp_normalize_path( DTWCBE__FILE__ ) );
define( 'DTWCBE_PLUGIN_BASENAME', plugin_basename( DTWCBE__FILE__ ) );
define( 'DTWCBE_PATH', plugin_dir_path( DTWCBE__FILE__ ) );
define( 'DTWCBE_PATH_URL' , plugin_dir_url( DTWCBE__FILE__ ));
define( 'DTWCBE_URL', plugins_url( '/', DTWCBE__FILE__ ) );

define( 'DTWCBE_MODULES_PATH', plugin_dir_path( DTWCBE__FILE__ ) . 'modules' );
define( 'DTWCBE_ASSETS_PATH', DTWCBE_URL . 'assets/' );
define( 'DTWCBE_ASSETS_URL', DTWCBE_URL . 'assets/' );


// Include the main DTWCBE_WooCommerce_Builder_Elementor class.
if( !class_exists('DTWCBE_WooCommerce_Builder_Elementor') ){
	include_once dirname( __FILE__ ) . '/includes/class-woocommerce-builder-elementor.php';
}

/**
 * Main instance of DTWCBE_WooCommerce_Builder_Elementor.
 *
 * Returns the main instance of DTWCBE to prevent the need to use globals.
 *
 * @since  1.0
 * @return DTWCBE_WooCommerce_Builder_Elementor
 */
function DTWCBE_WooCommerce_Builder_Elementor() {
	return DTWCBE_WooCommerce_Builder_Elementor::instance();
}

// Global for backwards compatibility.
$GLOBALS['dtwcbe'] = DTWCBE_WooCommerce_Builder_Elementor();

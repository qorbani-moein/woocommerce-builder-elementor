<?php
/**
 * WooCommerce Page Builder For Elementor Widget.
 *
 * @package WooCommerce-Builder-Elementor
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class DTWCBE_Single_Product_Tabs_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'single-product-tabs';
	}

	public function get_title() {
		return esc_html__( 'Woo Product Tabs', 'woocommerce-builder-elementor' );
	}

	public function get_icon() {
		return 'eicon-product-tabs';
	}

	public function get_categories() {
		return [ 'dtwcbe-woo-single-product' ];
	}
	
	public function get_keywords() {
		return [ 'woocommerce', 'tabs' , 'product' , 'single product' ];
	}

	protected function register_controls() {

	}

	protected function render() {
		
		$settings = $this->get_settings_for_display();
		$post_type = get_post_type();
	
		if ( $post_type == 'product' || $post_type == DTWCBE_Post_Types::post_type() ){
			
			echo DTWCBE_Single_Product_Elementor::_render( $this->get_name() );
			
		}else{
			
			esc_html_e('Product Tabs', 'woocommerce-builder-elementor' );
			
		}

	}
	

}

\Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DTWCBE_Single_Product_Tabs_Widget());
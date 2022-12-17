<?php
/**
 * WooCommerce Page Builder For Elementor Widget.
 *
 * @package WooCommerce-Builder-Elementor
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class DTWCBE_Single_Product_Additional_Information_Widget_moein extends \Elementor\Widget_Base {

	public function get_name() {
		return 'single-product-additional-information-moein';
	}

	public function get_title() {
		return esc_html__( 'ووکامرس - اطلاعات بیشتر محصول (معین)', 'woocommerce-builder-elementor' );
	}

	public function get_icon() {
		return 'eicon-product-info';
	}

	public function get_categories() {
		return [ 'dtwcbe-woo-single-product' ];
	}
	
	public function get_keywords() {
		return [ 'woocommerce', 'additional information' , 'product' , 'single product','معین' ];
	}

	protected function register_controls() {

		// Content Tab Start

		$this->start_controls_section(
			'section-title',
			[
				'label' => esc_html__( 'عنوان', 'elementor-addon' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'title-info-product',
			[
				'label' => esc_html__( 'عنوان اطللاعات محصول', 'elementor-addon' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'اطلاعات محصول', 'textdomain' ),
				'placeholder' => esc_html__( 'اطلاعات محصول', 'textdomain' ),
			]
		);
		$this->end_controls_section();

		// Content Tab End


		// Style Tab Start

		$this->start_controls_section(
			'section_title_style',
			[
				'label' => esc_html__( 'استایل', 'elementor-addon' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label' => esc_html__( 'رنگ عنوان', 'elementor-addon' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .moein-title-info-product' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		// Style Tab End

	}
	protected function render() {
		
		$settings = $this->get_settings_for_display();
		$post_type = get_post_type();
		
		if ( $post_type == 'product' || $post_type == DTWCBE_Post_Types::post_type() ){
			
			$table_info_products = DTWCBE_Single_Product_Elementor::_render( $this->get_name() );
			// console($table_info_product);
			$arr_info_products = getdata($table_info_products);
			style();
			open_div("moein-info-product");
				open_div("moein-title-info-product");
					echo '<p classname="">' . $settings['title-info-product'] . '</p>';
				close_div();
				foreach ($arr_info_products as $key => $value) {
					$i=0;
					foreach ($value as $key => $value) {
						$value = str_replace('cm','سانتی متر',$value);
						$value = str_replace('kg','کیلو گرم',$value);
						if($i == 0){ //get Header of info product (th)
							echo '<span class="moein-header-info-product"><span class="moein-icon-info-product">●</span> ' . $value . ': </span>';
						}else{//get Body of info product (td)
							echo '<span class="moein-body-info-product">' . $value .'</span>';
						}
						// var_dump($value);
						// echo '<p>break</p>';
						$i++;
					}
				
					# code...
				}
			close_div();
			// echo '<div class="moein">';
			// echo DTWCBE_Single_Product_Elementor::_render( $this->get_name() );
			// echo '</div>';
			
		}else{
			
			echo esc_html__('Product additional information', 'woocommerce' );
			
		}
	}
	
}

//moein (
function style() {
	style_moein_info_product();
	style_moein_header_info_product();
	style_moein_body_info_product();
	style_moein_title_info_product();
}
function style_moein_title_info_product(){
	print_style('
	.moein-title-info-product{
		padding-bottom: 5px;
		font-weight: 700;
		font-size: 16px;
	}
	');
}
function style_moein_info_product(){
	print_style('
	.moein-info-product{
		padding: 5px;
	}
	');
}
function style_moein_header_info_product(){
	print_style('
	.moein-header-info-product{
		padding-left: 5px;
		color: gray;
	}
	.moein-icon-info-product{
		color:#303030;
	}
	');
}
function style_moein_body_info_product(){
	print_style('
	.moein-body-info-product{
		color: $303030;
		padding-left: 24px;
	}
	');
}
function print_style($style){
	echo '<style>
	'. $style .'
	</style>';
}
function open_div($classname){
	echo '<div class="' . $classname . '">';
}
function close_div(){
	echo '</div>';
}

function tdrows($elements)
{
	$str = [];
	$i=0;
	foreach ($elements as $element) {
		if(trim($element->nodeValue) != '') $str[$i] = $element->nodeValue;
		$i++;
	}

	return $str;
}

function getdata($contents)
{
	//$contents = "<table><tr><td>Row 1 Column 1</td><td>Row 1 Column 2</td></tr><tr><td>Row 2 Column 1</td><td>Row 2 Column 2</td></tr></table>";
	$DOM = new DOMDocument;
	$DOM->loadHTML($contents);
	$DOM->loadHTML(mb_convert_encoding($contents, 'HTML-ENTITIES', 'UTF-8'));
	$items = $DOM->getElementsByTagName('tr');

	$res = [];
	$i=0;
	
	foreach ($items as $node) {
		$res[$i] = tdrows($node->childNodes);
		//console(tdrows($node->childNodes));
		// echo tdrows($node->childNodes) . "<br />";
		$i++;
	}
	return  $res;
	// (var_dump($res));
}
function console($str, $caption = ""){
	if (isset($GLOBALS['moein-dev'])) {
		echo '
		<script>
		console.log("moein-    ' . ($caption != "" ? $caption . ': ' : '') .  $str  .'");
		</script>
		';
	}
}
//moein )

\Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DTWCBE_Single_Product_Additional_Information_Widget_moein());
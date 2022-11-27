<?php
/**
 * DTWCBE_WooCommerce_Builder_Elementor setup
 *
 * @package WooCommerce-Builder-Elementor
 * 
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main DTWCBE_WooCommerce_Builder_Elementor Class.
 *
 * @class WooCommerce-Builder-Elementor
 */

final class DTWCBE_WooCommerce_Builder_Elementor {
	
	/**
	 * The one, true instance of this object.
	 *
	 * @static
	 * @access private
	 * @since 1.0
	 * @var object
	 */
	private static $_instance = null;
	
	public static $elementor_instance;
	
	/**
	 * Registration
	 *
	 * @since 1.1.0
	 * @static
	 * @access public
	 */
	public $registration;
	
	/**
	 * Creates or returns an instance of this class.
	 *
	 * @static
	 * @access public
	 * @since 1.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public static function elementor() {
		return \Elementor\Plugin::$instance;
	}
	
	public function __construct() {
		add_action( 'plugins_loaded', array($this, 'plugins_loaded' ) );
		$this->setup_hooks();
	}
	
	public function plugins_loaded() {
	
		if (!function_exists('is_plugin_active'))
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' )) {
			add_action('admin_notices', array($this, 'woocommerce_notice'));
			return ;
		}
	
		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_main_plugin' ) );
			return;
		}
		if ( ! defined( 'ELEMENTOR_VERSION' ) || ! is_callable( 'Elementor\Plugin::instance' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_main_plugin' ) );
			return;
		}
		
		self::$elementor_instance = Elementor\Plugin::instance();
		
		$this->includes();
		
		add_action( 'init', array($this, 'i18n' ) );
		add_action( 'init', array($this, 'init' ) );
		
		// Add Plugin actions
		add_action( 'elementor/elements/categories_registered', array($this, 'add_elementor_widget_categories' ) );
		// On Editor - Register WooCommerce frontend hooks before the Editor init.
		// Priority = 5, in order to allow plugins remove/add their wc hooks on init.
		if ( ! empty( $_REQUEST['action'] ) && 'elementor' === $_REQUEST['action'] && is_admin() ) {
			add_action( 'admin_action_elementor', [ $this, 'wc_fontend_includes' ], 5 );
		}
		
		add_action('after_setup_theme', array($this, 'include_template_functions'), 11);
		
		$this->registration = new DTWCBE_WooCommerce_Builder_Elementor_Product_Registration(
			[
				'type'    => 'plugin',
				'name'    => 'WooCommerce Page Builder For Elementor',
			]
		);
		
	}
	
	private function setup_hooks(){
		add_action( 'elementor/frontend/before_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );
		add_action( 'wp_enqueue_scripts',array($this,'enqueue_scripts'), 999999);
	}
	
	public function i18n() {
		load_plugin_textdomain( 'woocommerce-builder-elementor' , false, plugin_basename( dirname( DTWCBE__FILE__ ) ) . '/languages' );
	}
	
	public function init(){
		add_filter( 'body_class', array($this, '_body_classes') );
		/*
		 * Filter wc_get_template
		 */
		// Cart
		add_filter( 'wc_get_template', array( $this, 'cart_page_template' ), 50, 3 );
		add_action( 'dtwcbe_cart_content', array($this,'the_cart_content') );
		add_action( 'dtwcbe_cart_empty_content', array($this,'the_cart_empty_content') );
		add_action( 'woocommerce_builder_elementor_after_cart', array($this,'get_file_cart_empty_handle_id') );
		// Checkout
		add_filter(	'wc_get_template', array( $this, 'checkout_page_template' ), 50, 3 );
		add_action( 'dtwcbe_checkout_content', array($this,'the_checkout_content') );
		add_action( 'dtwcbe_thankyou_content', array($this,'the_thankyou_content') );
		
		// My Account
		add_filter(	'wc_get_template', array( $this, 'myaccount_page_template' ), 50, 3 );
		add_action( 'dtwcbe_woocommerce_account_content', array($this,'the_account_content') );
		add_action( 'dtwcbe_woocommerce_account_content_form_login', array($this,'the_account_login_content') );
		
		add_filter( 'template_include', array($this, 'redirect_woocommerce_page_template'), 999999);
		
		// Remove actions unnecessary
		add_action('template_redirect', array(__CLASS__, 'unsupported_actions'));
		
		// Load template builder when editing
		add_filter( 'single_template', array( $this, 'load_checkout_template_preview' ) );
	}
	
	public function cart_page_template( $located, $name, $args ){
		if($name === 'cart/cart.php'){
			$cart_page_id = get_option('dtwcbe_cart_page_id', '');
			if( !empty($cart_page_id) ) {

				$located = DTWCBE_PATH . 'woocommerce-builder-elementor-templates/cart/content-cart.php';
			}
		}
		if($name === 'cart/cart-empty.php'){
			$cartempty_page_id = get_option('dtwcbe_cartempty_page_id', '');
			if( !empty($cartempty_page_id) ) {
				$located = DTWCBE_PATH . 'woocommerce-builder-elementor-templates/cart/cart-empty.php';
			}
		}
		return $located;
	}
	
	public function the_cart_content(){
		$dtwcbe_cart_page_id = get_option('dtwcbe_cart_page_id', '');
		if( !empty($dtwcbe_cart_page_id) ){
			echo self::$elementor_instance->frontend->get_builder_content_for_display( $dtwcbe_cart_page_id );
		}
	}

	public function get_file_cart_empty_handle_id(){
		$cartempty_page_id = get_option('dtwcbe_cartempty_page_id', '');
		if( !empty($cartempty_page_id) ) {
			$wp_upload_dir = wp_upload_dir();
			echo '<link rel="stylesheet" id="elementor-post-'.absint($cartempty_page_id).'-css" href="'.esc_url($wp_upload_dir['baseurl']).'/elementor/css/post-'.absint($cartempty_page_id).'.css" type="text/css" />';
		}
	}

	public function the_cart_empty_content(){
		$dtwcbe_cartempty_page_id = get_option('dtwcbe_cartempty_page_id', '');
		if( !empty($dtwcbe_cartempty_page_id) ){
			echo self::$elementor_instance->frontend->get_builder_content_for_display( $dtwcbe_cartempty_page_id );
		}
	}
	
	public function checkout_page_template( $located, $name, $args ){
		if($name === 'checkout/form-checkout.php'){
			$dtwcbe_checkout_page_id = get_option('dtwcbe_checkout_page_id', '');
			if( !empty($dtwcbe_checkout_page_id) ) {
				$located = DTWCBE_PATH . 'woocommerce-builder-elementor-templates/checkout/form-checkout.php';
			}
		}elseif($name === 'checkout/thankyou.php'){
			$dtwcbe_thankyou_page_id = get_option('dtwcbe_thankyou_page_id', '');
			if( !empty($dtwcbe_thankyou_page_id) ) {
				$located = DTWCBE_PATH . 'woocommerce-builder-elementor-templates/checkout/thankyou.php';
			}
		}
		
		return $located;
	}
	
	public function the_checkout_content(){
		$dtwcbe_checkout_page_id = get_option('dtwcbe_checkout_page_id', '');
		if(!empty($dtwcbe_checkout_page_id)){
			echo self::$elementor_instance->frontend->get_builder_content_for_display( $dtwcbe_checkout_page_id );
		}else{
			the_content();
		}
	}
	
	public function the_thankyou_content(){
		$dtwcbe_thankyou_page_id = get_option('dtwcbe_thankyou_page_id', '');
		if(!empty($dtwcbe_thankyou_page_id)){
			echo self::$elementor_instance->frontend->get_builder_content_for_display( $dtwcbe_thankyou_page_id );
		}else{
			the_content();
		}
	}
	
	public function myaccount_page_template($located, $name, $args ){
		$dtwcbe_myaccount_login_page_id = get_option('dtwcbe_myaccount_login_page_id', '');
		$dtwcbe_myaccount_page_id = get_option('dtwcbe_myaccount_page_id', '');

		if($name === 'myaccount/my-account.php'){
			if( $dtwcbe_myaccount_page_id === $dtwcbe_myaccount_login_page_id ){
				return $located;
			}
			if( !empty($dtwcbe_myaccount_page_id) ) {
				$located = DTWCBE_PATH . 'woocommerce-builder-elementor-templates/myaccount/my-account.php';
			}
		}elseif($name === 'myaccount/form-login.php'){
			if( !empty($dtwcbe_myaccount_login_page_id) ) {
				$located = DTWCBE_PATH . 'woocommerce-builder-elementor-templates/myaccount/form-login.php';
			}
		}
	
		return $located;
	}
	
	public function the_account_content( $content ){
		$dtwcbe_myaccount_page_id = get_option('dtwcbe_myaccount_page_id', '');
		
		if ( is_user_logged_in() && !empty($dtwcbe_myaccount_page_id) ){
			echo self::$elementor_instance->frontend->get_builder_content_for_display( $dtwcbe_myaccount_page_id );
		}else{
			the_content();
		}
	}
	
	public function the_account_login_content( $content ){
		$dtwcbe_myaccount_login_page_id = get_option('dtwcbe_myaccount_login_page_id', '');
		
		if ( !empty($dtwcbe_myaccount_login_page_id) ){
			echo self::$elementor_instance->frontend->get_builder_content_for_display( $dtwcbe_myaccount_login_page_id );
		}else{
			the_content();
		}
	}
	
	public function get_page_template_path( $page_template ) {
		$template_path = '';
		switch ( $page_template ) {
			case 'elementor_header_footer':
				$template_path = DTWCBE_MODULES_PATH . '/page-templates/templates/header-footer.php';
				break;
			case 'elementor_canvas':
				$template_path = DTWCBE_MODULES_PATH . '/page-templates/templates/canvas.php';
				break;
		}
	
		return $template_path;
	}
	
	public function redirect_woocommerce_page_template( $template ){
		$page_template_id = 0;
		
		if( is_cart() ){

			$dtwcbe_cart_page_id = get_option('dtwcbe_cart_page_id', '');
			if( !empty($dtwcbe_cart_page_id) )
				$page_template_id = get_page_template_slug( $dtwcbe_cart_page_id );

		}elseif ( is_checkout() ){

			$dtwcbe_checkout_page_id = get_option('dtwcbe_checkout_page_id', '');
			if( !empty($dtwcbe_checkout_page_id) )
				$page_template_id = get_page_template_slug( $dtwcbe_checkout_page_id );
			
		}elseif ( is_account_page() && is_user_logged_in() ){
			
			$dtwcbe_myaccount_page_id = get_option('dtwcbe_myaccount_page_id', '');
			if( !empty($dtwcbe_myaccount_page_id) )
				$page_template_id = get_page_template_slug( $dtwcbe_myaccount_page_id );
			
		}
		
		if( !empty($page_template_id) ){
			$template_path = $this->get_page_template_path( $page_template_id );
			if ( $template_path ) {
				$template = $template_path;
			}
		}
		
		return $template;
	}
	
	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
	
	public function add_elementor_widget_categories( $elements_manager ){
		$elements_manager->add_category(
			'dtwcbe-woo-general',
			[
				'title' => esc_html__( 'Woo General', 'woocommerce-builder-elementor' ),
				'icon' => 'eicon-woocommerce',
			]
		);
		
		$elements_manager->add_category(
			'dtwcbe-woo-single-product',
			[
				'title' => esc_html__( 'Woo Product', 'woocommerce-builder-elementor' ),
				'icon' => 'eicon-woocommerce',
			]
		);
		
		$elements_manager->add_category(
			'dtwcbe-woo-product-archive',
			[
				'title' => esc_html__( 'Woo Product Archive', 'woocommerce-builder-elementor' ),
				'icon' => 'eicon-woocommerce',
			]
		);
		
		$elements_manager->add_category(
			'dtwcbe-woo-cart',
			[
				'title' => esc_html__( 'Woo Cart', 'woocommerce-builder-elementor' ),
				'icon' => 'eicon-woocommerce',
			]
		);
		
		$elements_manager->add_category(
			'dtwcbe-woo-checkout',
			[
				'title' => esc_html__( 'Woo Checkout', 'woocommerce-builder-elementor' ),
				'icon' => 'eicon-woocommerce',
			]
		);
		
		$elements_manager->add_category(
			'dtwcbe-woo-thankyou',
			[
				'title' => esc_html__( 'Woo Thank You', 'woocommerce-builder-elementor' ),
				'icon' => 'eicon-woocommerce',
			]
		);
		
		$elements_manager->add_category(
			'dtwcbe-woo-myacount',
			[
				'title' => esc_html__( 'Woo My Account', 'woocommerce-builder-elementor' ),
				'icon' => 'eicon-woocommerce',
			]
		);
		$elements_manager->add_category(
			'dtwcbe-woo-acount-login',
			[
				'title' => esc_html__( 'Woo Account Login', 'woocommerce-builder-elementor' ),
				'icon' => 'eicon-woocommerce',
			]
		);
		
	}
	
	public function includes() {
		
		include_once DTWCBE_PATH . 'includes/class-post-types.php';
		
		require_once DTWCBE_PATH . 'includes/functions.php';
		
		if( is_admin() ){
			require_once DTWCBE_PATH . 'includes/admin/admin.php';
			require_once DTWCBE_PATH . 'includes/admin/import-demo.php';
		}
		
		require_once DTWCBE_PATH . 'includes/class-woocommerce-builder-product-registration.php';
		require_once DTWCBE_PATH . 'includes/class-woocommerce-builder-envato-api.php';
		require_once DTWCBE_PATH . 'includes/class-woocommerce-builder-updater.php';
		
		require_once DTWCBE_PATH . 'includes/class-woocommerce-elementor-widgets-registered.php';
		
		require_once DTWCBE_PATH . 'includes/class-single-product-elementor.php';
		require_once DTWCBE_PATH . 'includes/class-products-renderer-elementor.php';
		require_once DTWCBE_PATH . 'includes/class-archive-product-elementor.php';
		require_once DTWCBE_PATH . 'includes/class-cart-elementor.php';
	}
	
	public function register_controls(){
		$this->start_controls_section(
			'document_settings',
			[
				'label' => esc_html__( 'Preview Settings', 'woocommerce-builder-elementor' ),
				'tab' => \Elementor\Controls_Manager::TAB_SETTINGS,
			]
		);
		
		$this->add_control(
			'post_title',
			[
				'label' => esc_html__( 'Date', 'woocommerce' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => 'WFT',
				'label_block' => true,
				'separator' => 'none',
			]
		);
		
		$this->end_controls_section();
	}
	
	public function include_template_functions() {
		if (class_exists('WooCommerce')):
			include_once DTWCBE_PATH . 'includes/template-functions.php';
			include_once DTWCBE_PATH . 'includes/template-hooks.php';
		endif;
	}
	
	public function woocommerce_notice(){
		$plugin  = get_plugin_data(__FILE__);
		echo '
		  <div class="updated">
		    <p>' . sprintf(__('The <strong>WooCommerce Page Builder For Elementor For Elementor</strong> requires <strong><a href="https://woocommerce.com/" target="_blank">WooCommerce</a></strong> plugin to be installed and activated on your site.', 'woocommerce-builder-elementor')) . '</p>
		  </div>';
	}
	
	public function admin_notice_missing_main_plugin() {

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( 'The %1$s requires %2$s to be installed and activated on your site.', 'woocommerce-builder-elementor' ),
			'<strong>' . esc_html__( 'WooCommerce Page Builder For Elementor For Elementor', 'woocommerce-builder-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'woocommerce-builder-elementor' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

	}
	
	/**
	 *  Include WC fontend.
	 */
	public function wc_fontend_includes() {
		WC()->frontend_includes();
		if ( is_null( WC()->cart ) ) {
			global $woocommerce;
			$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
			$woocommerce->session = new $session_class();
			$woocommerce->session->init();

			$woocommerce->cart     = new WC_Cart();
			$woocommerce->customer = new WC_Customer( get_current_user_id(), true );
		}
	}
	
	public function enqueue_frontend_scripts(){
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'woocommerce' );
		
	}
	
	public function enqueue_scripts(){
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		if ( $this::elementor()->preview->is_preview_mode( $this::elementor()->preview->get_post_id() ) ) {
			wp_enqueue_script( 'woocommerce' );
			global $product;
			
			if ( is_singular( 'product' ) ) {
				$product = wc_get_product();
			}
			if ( current_theme_supports( 'wc-product-gallery-zoom' ) ) {
				wp_enqueue_script( 'zoom' );
			}
			if ( current_theme_supports( 'wc-product-gallery-slider' ) ) {
				wp_enqueue_script( 'flexslider' );
			}
			if ( current_theme_supports( 'wc-product-gallery-lightbox' ) ) {
				wp_enqueue_script( 'photoswipe-ui-default' );
				wp_enqueue_style( 'photoswipe-default-skin' );
				add_action( 'wp_footer', 'woocommerce_photoswipe' );
			}
			wp_enqueue_script( 'wc-single-product' );
			
			wp_enqueue_style( 'photoswipe' );
			wp_enqueue_style( 'photoswipe-default-skin' );
			wp_enqueue_style( 'photoswipe-default-skin' );
			wp_enqueue_style( 'woocommerce_prettyPhoto_css' );
		}
		
		//wp_enqueue_style( 'slick', DTWCBE_PATH_URL . 'assets/vendor/slick/slick.min.css', array(), null, true);
		wp_enqueue_style( 'slick', DTWCBE_PATH_URL . 'assets/vendor/slick/slick.min.css' );
		wp_enqueue_script( 'slick', DTWCBE_PATH_URL . 'assets/vendor/slick/slick.min.js', array( 'jquery' ), '1.8.6', true );

		wp_enqueue_style( 'venobox', DTWCBE_PATH_URL . 'assets/vendor/venobox/venobox.css' );
		wp_enqueue_script( 'venobox', DTWCBE_PATH_URL . 'assets/vendor/venobox/venobox.min.js', array( 'jquery' ), '1.8.6', true );
		
		wp_register_style('font-awesome', DTWCBE_PATH_URL . 'assets/fonts/font-awesome/css/font-awesome.min.css',  array(), '4.4.0' );
		wp_enqueue_style('dtwcbe', DTWCBE_PATH_URL .'assets/css/style.css');
		wp_enqueue_script('dtwcbe', DTWCBE_PATH_URL.'assets/js/script'.$suffix.'.js',array('jquery'), DTWCBE_VERSION, true);
	}
	
	public static function unsupported_actions() {

		if ( dtwcbe_woocommerce_builder_notices() ) {
			remove_action( 'woocommerce_before_single_product', 'woocommerce_output_all_notices', 10 );
			remove_action( 'woocommerce_before_cart', 'woocommerce_output_all_notices', 10 );
			remove_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10 );
		}

		if (is_product()) {
			
			global $post;
	
			$product_template_id = 0;
			
			// Get All Template builder and check term in template
			$args = array(
				'post_status'=> 'publish',
				'meta_key' => '_dtwcbe_woo_template_type',
				'meta_value' => 'product',
				'post_type' => DTWCBE_Post_Types::CPT,
				'posts_per_page' => -1,
				'order' => 'asc',
			);
			$product_templates = get_posts($args);
			
			$dtwcbe_condition_product_in = get_post_meta($post->ID, 'dtwcbe_condition_product_in', true);
				
			$single_product_in_template_builder = 0;
	
		foreach ( $product_templates as $p_template ){
				$dtwcbe_product_in = get_post_meta($p_template->ID, 'dtwcbe_product_in', true);
				$dtwcbe_product_in_arr = explode(',', $dtwcbe_product_in);
				
				if( in_array($post->post_name, $dtwcbe_product_in_arr) ){
					$single_product_in_template_builder = $p_template->ID;
				}
			}
			
			if ( $single_product_in_template_builder ):
				$product_template_id = $single_product_in_template_builder;
			else:
				$product_terms = array();
				$terms = wp_get_post_terms($post->ID, 'product_cat');
				foreach ($terms as $term):
					array_push( $product_terms, $term->slug );
				endforeach;
				
				foreach ( $product_templates as $p_template ){
					$dtwcbe_cat_in = get_post_meta($p_template->ID, 'dtwcbe_cat_in', true);
					$dtwcbe_cat_in_arr = explode(',', $dtwcbe_cat_in);
					
					$containsSearch = count(array_intersect($product_terms, $dtwcbe_cat_in_arr));
					if( $containsSearch ){
						$product_template_id = $p_template->ID;
					}
				}
			endif;
			
			// Get setting option
			if ($product_template_id == 0) {
				$product_template_id = get_option('dtwcbe_condition_product_all', '');
			}
	
			if (!empty($product_template_id)) {
				
				remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
				remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
				remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
				// If theme Storefront
				remove_action('woocommerce_after_single_product_summary', 'storefront_upsell_display', 15);
				
				// If theme Impreza |
				//remove_action('woocommerce_before_main_content', 'us_woocommerce_before_main_content', 10);
				//remove_action('woocommerce_after_main_content', 'us_woocommerce_after_main_content', 20);
				// If theme Site | A Modern, Sharp eCommerce Theme by Select Themes
				remove_action('woocommerce_before_single_product_summary', 'bazaar_qodef_single_product_content_additional_tag_before', 5);
				remove_action('woocommerce_before_single_product_summary', 'bazaar_qodef_single_product_summary_additional_tag_before', 30);
				// If theme Bridge | Bridge Theme
				remove_action('woocommerce_before_single_product_summary', 'qode_single_product_summary_additional_tag_before', 30);
				remove_action('woocommerce_after_single_product_summary', 'qode_single_product_summary_additional_tag_after', 5);

				// If theme Depot - A Contemporary Theme for eCommerce
				if (function_exists('depot_mikado_single_product_content_additional_tag_after')) {
					add_action('dtwpb_woocommerce_before_single_product_summary_additional_tag_after', 'depot_mikado_single_product_content_additional_tag_after', 1);
					add_action('dtwpb_woocommerce_before_single_product_summary_additional_tag_after', 'depot_mikado_single_product_summary_additional_tag_after', 5);
				}
				// If theme DynamiX - A Contemporary Theme for eCommerce
				remove_action('woocommerce_before_single_product_summary', 'acoda_image_open_wrap', 2);
				remove_action('woocommerce_before_single_product_summary', 'acoda_close_image_div', 20);
				remove_action('woocommerce_before_single_product_summary', 'acoda_summary_open_wrap', 25);
				remove_action('woocommerce_after_single_product_summary', 'acoda_close_summary_div', 3);
				// enfold
				remove_action('woocommerce_before_single_product_summary', 'avia_add_image_div', 2);
				remove_action('woocommerce_before_single_product_summary', 'avia_close_image_div', 20);
				// If theme Salient
				remove_action('woocommerce_before_single_product_summary', 'summary_div', 35);
				remove_action('woocommerce_after_single_product_summary', 'close_div', 4);
				remove_action('woocommerce_before_single_product_summary', 'images_div', 8);
				remove_action('woocommerce_before_single_product_summary', 'close_div', 29);
				remove_action( 'woocommerce_before_single_product_summary', 'nectar_woocommerce_summary_div', 35 );
				remove_action( 'woocommerce_after_single_product_summary', 'nectar_woocommerce_close_div', 4 );
				remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 1 );
				remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
				remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_upsells', 21 );
				remove_action( 'woocommerce_after_single_product_summary', 'nectar_woo_clearfix', 7 );

				// If theme Fortuna
				remove_action('woocommerce_before_single_product_summary', 'boc_images_div', 2);
				remove_action('woocommerce_before_single_product_summary', 'boc_close_div', 20);
				remove_action('woocommerce_before_single_product_summary', 'boc_summary_div', 35);
				remove_action('woocommerce_after_single_product_summary', 'boc_close_div', 4);
				remove_action('woocommerce_after_single_product_summary', 'boc_woocommerce_output_related_products', 20);
				remove_action('woocommerce_after_single_product_summary', 'boc_woocommerce_output_upsells', 21);
				remove_action('woocommerce_before_single_product', 'boc_wrap_single_product_image', 8);
				remove_action('woocommerce_after_single_product', 'boc_close_div', 9);
				// X
				remove_action('woocommerce_before_single_product', 'x_woocommerce_before_single_product', 10);
				remove_action('woocommerce_after_single_product', 'x_woocommerce_after_single_product', 10);
				// DIVI
				remove_action( 'woocommerce_before_single_product_summary', 'et_divi_output_product_wrapper', 0  );
				remove_action( 'woocommerce_after_single_product_summary', 'et_divi_output_product_wrapper_end', 0  );
				// Superfood
				remove_action( 'woocommerce_before_single_product_summary', 'superfood_elated_single_product_content_additional_tag_before', 5 );
				remove_action( 'woocommerce_after_single_product_summary', 'superfood_elated_single_product_content_additional_tag_after', 1 );
				remove_action( 'woocommerce_before_single_product_summary', 'superfood_elated_single_product_summary_additional_tag_before', 30 );
				remove_action( 'woocommerce_after_single_product_summary', 'superfood_elated_single_product_summary_additional_tag_after', 5 );

				remove_action('thegem_woocommerce_after_single_product', 'woocommerce_output_related_products', 5);

				remove_action( 'woocommerce_before_main_content', 'aperitif_core_add_main_woo_page_template_holder', 5 );
				remove_action( 'woocommerce_after_main_content', 'aperitif_core_add_main_woo_page_template_holder_end', 20 );
				remove_action( 'woocommerce_before_main_content', 'aperitif_core_add_main_woo_page_holder', 10 );
				remove_action( 'woocommerce_after_main_content', 'aperitif_core_add_main_woo_page_holder_end', 10 );
			}
		}
		if( get_option('dtwcbe_cart_page_id', '') && is_cart() ) {
			// Impreza
			remove_action('woocommerce_after_cart', 'woocommerce_cross_sell_display', 10);
		}
	}
	
	public function load_checkout_template_preview( $single_template ) {
		global $post;
		
		if( isset($post) && $post->post_type === 'dtwcbe_woo_library' ){
			$woo_template_type = get_post_meta( $post->ID, '_dtwcbe_woo_template_type', true);
			if ($woo_template_type === 'checkout-page'){
				return DTWCBE_PATH . 'woocommerce-builder-elementor-templates/checkout/form-checkout-preview.php';
			}
		}
	
		return $single_template;
	}
	
	public function _body_classes($classes){
		global $post;
		$classes[] = 'woocommerce-builder-elementor';

		if( isset($post) && $post->post_type === 'dtwcbe_woo_library' ){
			$classes[] = 'woocommerce woocommerce-page';
			$woo_template_type = get_post_meta( $post->ID, '_dtwcbe_woo_template_type', true);
			if ($woo_template_type === 'product'){
				$classes[] = 'single-product';
			}
			if ($woo_template_type === 'cart-page'){
				$classes[] = 'woocommerce-cart';
			}
			
		}
		return $classes;
	}
	
}
<?php
/**
 * DTWCBE_Single_Product_Elementor
 *
 * @package WooCommerce-Builder-Elementor
 *
 */

defined( 'ABSPATH' ) || exit;

class DTWCBE_Single_Product_Elementor{

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function __construct(){
		add_action('init', array($this, 'init'));
	}
	
	public function init(){
		add_filter( 'post_class', array($this, 'post_class') );
		
		// Get template loader default file for content product in the single-product.php template
		add_filter( 'template_include', array( $this, 'get_single_product_template_loader' ),999999 );
		// Custom product page
		add_action('template_redirect', array($this, 'get_register_single_product_template'), 999999);
		add_filter('wc_get_template_part', array($this, 'wc_get_template_part'), 99, 3);
		
		add_action('dtwcbe_product_elementor', array($this, 'the_product_page_content'));
		add_action('dtwcbe_product_elementor', array($this, 'product_data' ), 30 );
	}
	
	public function get_single_product_template_loader( $template ){
		
		if (is_singular('product') || is_singular('dtwcbe_woo_library')) {
			$product_template_id = self::get_register_single_product_template();
			$theme = wc_get_theme_slug_for_templates();
			if ($theme == 'labomba' || $theme == 'mrtailor' || $theme == 'consultix') {
				$find 	= array();
				$file 	= 'single-product.php';
				$find[] = 'woocommerce-builder-elementor-templates/' . $file;
				if( $product_template_id ){
					$template       = locate_template( $find );
					if ( ! $template || ( ! empty( $status_options['template_debug_mode'] ) && current_user_can( 'manage_options' ) ) )
						$template = DTWCBE_PATH . '/woocommerce-builder-elementor-templates/' . $file;
						
					return $template;
				}
			}
			// Select Page Template
			if( $product_template_id ){
				$page_template_slug = get_page_template_slug( $product_template_id );
				
				if ( 'elementor_header_footer' === $page_template_slug ) {
					$template = DTWCBE_MODULES_PATH . '/product-templates/header-footer.php';
				} elseif ( 'elementor_canvas' === $page_template_slug ) {
					$template = DTWCBE_MODULES_PATH . '/product-templates/canvas.php';
				}
			}
		}
	
		return $template;
	}
	
	public static function get_register_single_product_template() {
		global $post;
		if ( is_singular('product') && isset($post->ID) ) {
	
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
				return $product_template_id;
			}
	
			return '';
	
		}
	}
	
	public function wc_get_template_part($template, $slug, $name) {
		
		if ($slug === 'content' && $name === 'single-product') {
			$product_template_id = self::get_register_single_product_template();
			$file = 'content-single-product.php';
			$find[] = 'woocommerce-builder-elementor-templates/' . $file;
			if( $product_template_id ){
				$template = '';
				if (!$template || (!empty($status_options['template_debug_mode']) && current_user_can('manage_options'))) {
					$template = DTWCBE_PATH . 'woocommerce-builder-elementor-templates/' . $file;
					return $template;
				}
			}
		}
		
		return $template;
	}
	
	public static function the_product_page_content( $post ){
		$product_template_id = self::get_register_single_product_template();
		if( $product_template_id ){
			echo DTWCBE_WooCommerce_Builder_Elementor::$elementor_instance->frontend->get_builder_content_for_display( $product_template_id );
		}else{
			the_content();
		}
	}
	
	/**
	 * Generates Product structured data.
	 *
	 * Hooked into `dtwcbe_product_elementor` action hook.
	 *
	 * @param WC_Product $product Product data (default: null).
	 */
	public function product_data() {
		WC()->structured_data->generate_product_data();
	}
	
	public static function _render( $element = '', $settings = array()){
		global $post, $product;
		
		if( get_post_type() == 'product' ){
			$product_id = $product->get_id();
		}else{
			$product_id = self::get_product_id_in_condition();
			$product = wc_get_product( $product_id );
		}
		
		if( $product_id == 0 ) return;
		
		switch ( $element ){
			case 'single-product-images':
				ob_start();
				$GLOBALS['moein-dev'] = true;
				$product_gallery_type = $settings['product_gallery_type']; // Theme default || Gallery Slider ( Horizontal - Vertical )
				
				if( $product_gallery_type == 'gallery-slider' ){
					wp_dequeue_script('flexslider');
					wp_dequeue_script('zoom');
					wp_enqueue_style( 'font-awesome' );

					if ( $product->is_on_sale() && 'yes' === $settings['sale_flash'] ) : ?>
						<?php echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>', $post, $product ); ?>
					<?php endif;
					
					$post_thumbnail_id = $product->get_image_id();
					$image_size        = apply_filters( 'woocommerce_gallery_image_size','woocommerce_single');
					$image         	   = wp_get_attachment_image($post_thumbnail_id, $image_size, true,array( "class" => "attachment-shop_single size-shop_single wp-post-image" ));
					
					$attachment_ids = $product->get_gallery_image_ids();

					$gallery_slider_style = $gallery_thumbs_vertical = '';
					if ($attachment_ids) {
						$gallery_slider_style = 'product-thumbs-' . $settings['gallery_slider_style'];
						$gallery_thumbs_vertical = 'vertical-' . $settings['gallery_thumbs_vertical'];
					}
					
					$wrapper_classes   = apply_filters( 'woocommerce_single_product_image_gallery_classes', array(
						'woocommerce-product-gallery',
						'woocommerce-product-gallery-slider',
						'woocommerce-product-gallery--' . ( $product->get_image_id() ? 'with-images' : 'without-images' ),
						'images',
						$gallery_slider_style,
						$gallery_thumbs_vertical,
					) );

					console('1');
					?>
					<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" style="opacity: 0; transition: opacity .25s ease-in-out;">
							<?php 
							console('2');
							if ( $product->get_image_id() ) {
								//echo '<div id="product-image-slider" class="slider-for woocommerce-product-gallery__wrapper">';
								$data_attachment = wc_get_product_attachment_props($post_thumbnail_id);
								console('$product->get_image_id()');
								// echo '<div class="woocommerce-product-gallery__image single-product-main-image"><a class="venobox" title="'.$data_attachment['title'].'" data-gall="product-image-lightbox" href="'.$data_attachment['url'].'" data-thumb="'.$data_attachment['gallery_thumbnail_src'].'">' . $image . '</a></div> ';
								
								if ($attachment_ids) {
									foreach ($attachment_ids as $attachment_id) {
										$thumbnail_image    = wp_get_attachment_image($attachment_id, $image_size);
										$data_attachment 		= wc_get_product_attachment_props($attachment_id);
										
										// echo '<a class="venobox" data-gall="product-image-lightbox" title="'.$data_attachment['title'].'" href="'.$data_attachment['url'].'" data-thumb="'.$data_attachment['gallery_thumbnail_src'].'">' . $thumbnail_image . '</a>';
								
									}
								}
								// echo "</div>";
								
							} else {
								console('not $product->get_image_id()');
								// console('not $product->get_image_id()');
								$html  = '<div class="woocommerce-product-gallery__image--placeholder">';
								$html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
								$html .= '</div>';
								
							}
					
							//echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $post_thumbnail_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
						
							$post_thumbnail_id = $product->get_image_id();

							//moein change size image
							// $image_size        = apply_filters( 'woocommerce_gallery_image_size','woocommerce_single');
							$image_size        = apply_filters( 'woocommerce_gallery_image_size','full');
							
							if( dtwcbe_woocommerce_version_check() ) {
								// Use new, updated functions
								$attachment_ids = $product->get_gallery_image_ids() ;
							} else {
								// Use older, deprecated functions
								$attachment_ids = $product->get_gallery_attachment_ids() ;
							}
							//moein added (


								
    						// echo '<img src="' . $image_main[0] . '"/>';


							console('3');
							echo style_slider();
							//moein )
							
							if ( $attachment_ids || has_post_thumbnail($product_id) ) {
								//moein class added
								// echo '<div class="moein-product-gallery slider-nav" id="product-thumbnails-carousel">';
								$image         	= wp_get_attachment_image($post_thumbnail_id, $image_size,true);
								$data_attachment 	= wc_get_product_attachment_props($post_thumbnail_id);
								
								console('image');
								console($image);
								//image slider active main image
								//echo '<div class="moein_item_gallery"><a class="woocommerce-product-gallery__image--thumbnail" title="'.$data_attachment['title'].'" data-href="'.$data_attachment['url'].'" data-gall="product-image-thumbs">'.$image.'</a></div>';
								
								//Open Main Div Slider
								echo '<div class="woo-slider-img">';
								
								//Add main image to slider
								console(strpos($image,'default.png'));
								if(strpos($image,'default.png') <= 0){
									echo '
									<div class="mySlides img-size">
										' . $image . '
									</div>
									';
								}

								
								//Add gallery image to slider
								foreach ( $attachment_ids as $attachment_id ) {
									$image_size="full";
									// console($image_size);
									$thumbnail_image    = wp_get_attachment_image($attachment_id, $image_size);
									$data_attachment   		= wc_get_product_attachment_props($attachment_id);
									
									// console("thumbnail_image");
									// console($thumbnail_image);
									
									// class: moein_item_gallery
									// echo '<div class="mySlides">';
									echo '<div class="mySlides img-size"><a title="'.$data_attachment['title'].'" data-href="'.$data_attachment['url'].'" data-gall="product-image-thumbs">' . $thumbnail_image . '</a></div>';
									// echo "</div>";
								}

								// add < > slider
								echo '
								<div class="moein_plusSlides">
								<a class="prev" onclick="plusSlides(-1)">❮</a>
								<a class="next" onclick="plusSlides(1)">❯</a>
								</div>
								';

								//add thumbnail div images
								echo '<div class="row">';


								//add class: demo cursor
								if(strpos($image,'default.png') <= 0){
									$image_main_slider = str_replace('class="','onclick="currentSlide(1)" class="demo cursor ',$image);
									echo '
									<div class="column">
										' . $image_main_slider .'
									</div>
									';
								}
								$i = 2;
								foreach ( $attachment_ids as $attachment_id ) {
									$thumbnail_image    = wp_get_attachment_image($attachment_id, $image_size);

									//add class: demo cursor
									console( '(' . boolval(strpos($thumbnail_image,'default.png')) . ')' , 'strpos($thumbnail_image)');
									if(boolval(strpos($thumbnail_image,'default.png')) == false){
										$thumbnail_image = str_replace('class="','onclick="currentSlide(' . $i . ')" class="demo cursor ',$thumbnail_image);
										echo '<div class="column">' . $thumbnail_image . '</div>';
										console('$thumbnail_image 11');
										console($thumbnail_image);
									}
									$i++;
								}

								echo '</div>';
							}else{
								//show img one img
								$image_main = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'single-post-thumbnail' );
								console($image_main);
								if($image_main)
									echo '<div class="woo-slider-img"><div class="mySlides img-size"><img src="' . $image_main[0] . '"/> </div></div>';
								// else
								// 	echo '<div class="woo-slider-img"><div class="mySlides img-size"><img src=""/> </div></div>';

							}
							console('4');
							echo script_slider();
						?>
						<script>
							jQuery(document).ready(function(){
								var options = {
									vertical: <?php echo ($settings['gallery_slider_style'] == 'vertical') ?  'true' : 'false'; ?>,
									thumbsToShow: <?php echo ( absint($settings['thumbs_show']) > 0 ) ? absint($settings['thumbs_show']) : 4; ?>,
								};
								jQuery().dtwcbe_product_gallery_slider(options);
								jQuery('.woocommerce-product-gallery').css('opacity','1');
							});
						</script>
						<?php
				?>
				</div>
				<?php
				}
				elseif( $product_gallery_type == 'gallery-slider-2' ){
					wp_dequeue_script('flexslider');
					wp_dequeue_script('zoom');
					wp_enqueue_style( 'font-awesome' );

					if ( $product->is_on_sale() && 'yes' === $settings['sale_flash'] ) : ?>
						<?php echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>', $post, $product ); ?>
					<?php endif;
					
					$post_thumbnail_id = $product->get_image_id();
					$image_size        = apply_filters( 'woocommerce_gallery_image_size','woocommerce_single');
					$image         	   = wp_get_attachment_image($post_thumbnail_id, $image_size, true,array( "class" => "attachment-shop_single size-shop_single wp-post-image" ));
					
					$attachment_ids = $product->get_gallery_image_ids();

					$gallery_slider_style = $gallery_thumbs_vertical = '';
					if ($attachment_ids) {
						$gallery_slider_style = 'product-thumbs-' . $settings['gallery_slider_style'];
						$gallery_thumbs_vertical = 'vertical-' . $settings['gallery_thumbs_vertical'];
					}
					
					$wrapper_classes   = apply_filters( 'woocommerce_single_product_image_gallery_classes', array(
						'woocommerce-product-gallery',
						'woocommerce-product-gallery-slider',
						'woocommerce-product-gallery--' . ( $product->get_image_id() ? 'with-images' : 'without-images' ),
						'images',
						$gallery_slider_style,
						$gallery_thumbs_vertical,
					) );

					console('1');
					?>
					<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" style="opacity: 0; transition: opacity .25s ease-in-out;">
							<?php 
							console('2');
							if ( $product->get_image_id() ) {
								//echo '<div id="product-image-slider" class="slider-for woocommerce-product-gallery__wrapper">';
								$data_attachment = wc_get_product_attachment_props($post_thumbnail_id);
								console('$product->get_image_id()');
								// echo '<div class="woocommerce-product-gallery__image single-product-main-image"><a class="venobox" title="'.$data_attachment['title'].'" data-gall="product-image-lightbox" href="'.$data_attachment['url'].'" data-thumb="'.$data_attachment['gallery_thumbnail_src'].'">' . $image . '</a></div> ';
								
								if ($attachment_ids) {
									foreach ($attachment_ids as $attachment_id) {
										$thumbnail_image    = wp_get_attachment_image($attachment_id, $image_size);
										$data_attachment 		= wc_get_product_attachment_props($attachment_id);
										
										// echo '<a class="venobox" data-gall="product-image-lightbox" title="'.$data_attachment['title'].'" href="'.$data_attachment['url'].'" data-thumb="'.$data_attachment['gallery_thumbnail_src'].'">' . $thumbnail_image . '</a>';
								
									}
								}
								// echo "</div>";
								
							} else {
								console('not $product->get_image_id()');
								// console('not $product->get_image_id()');
								$html  = '<div class="woocommerce-product-gallery__image--placeholder">';
								$html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
								$html .= '</div>';
								
							}
					
							//echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $post_thumbnail_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
						
							$post_thumbnail_id = $product->get_image_id();

							//moein change size image
							// $image_size        = apply_filters( 'woocommerce_gallery_image_size','woocommerce_single');
							$image_size        = apply_filters( 'woocommerce_gallery_image_size','full');
							
							if( dtwcbe_woocommerce_version_check() ) {
								// Use new, updated functions
								$attachment_ids = $product->get_gallery_image_ids() ;
							} else {
								// Use older, deprecated functions
								$attachment_ids = $product->get_gallery_attachment_ids() ;
							}
							//moein added (


								
    						// echo '<img src="' . $image_main[0] . '"/>';


							console('3');
							echo style_slider_2();
							//moein )
							
							if ( $attachment_ids || has_post_thumbnail($product_id) ) {
								//moein class added
								// echo '<div class="moein-product-gallery slider-nav" id="product-thumbnails-carousel">';
								$image         	= wp_get_attachment_image($post_thumbnail_id, $image_size,true);
								$data_attachment 	= wc_get_product_attachment_props($post_thumbnail_id);
								
								console('image');
								console($image);
								//image slider active main image
								//echo '<div class="moein_item_gallery"><a class="woocommerce-product-gallery__image--thumbnail" title="'.$data_attachment['title'].'" data-href="'.$data_attachment['url'].'" data-gall="product-image-thumbs">'.$image.'</a></div>';
								
								//Open Main Div Slider
								echo '<div class="woo-slider-img">';
								
								//Add main image to slider
								console(strpos($image,'default.png'));
								if(strpos($image,'default.png') <= 0){
									echo '
									<div class="mySlides img-size">
										' . $image . '
									</div>
									';
								}

								
								//Add gallery image to slider
								foreach ( $attachment_ids as $attachment_id ) {
									$image_size="full";
									// console($image_size);
									$thumbnail_image    = wp_get_attachment_image($attachment_id, $image_size);
									$data_attachment   		= wc_get_product_attachment_props($attachment_id);
									
									// console("thumbnail_image");
									// console($thumbnail_image);
									
									// class: moein_item_gallery
									// echo '<div class="mySlides">';
									echo '<div class="mySlides img-size"><a title="'.$data_attachment['title'].'" data-href="'.$data_attachment['url'].'" data-gall="product-image-thumbs">' . $thumbnail_image . '</a></div>';
									// echo "</div>";
								}


								//add thumbnail div images
								echo '<div class="row">';


								//add class: demo cursor
								if(strpos($image,'default.png') <= 0){
									// $image_main_slider = str_replace('class="','onclick="currentSlide(1)" class="demo cursor ',$image);
									$image_main_slider = $image;
									
									$src_img = explode('"',$image_main_slider);
									console('$src_img');
									// var_dump($src_img);
									console($src_img[5]);
									console($src_img[4]);
									console($src_img[6]);
									console($src_img[7]);

									// echo '
									// <div class="column">
									// 	<a href="' . $src_img[5] . '">' . $image_main_slider .'
									// 	</a>
									// </div>
									// ';
								}
								$i = 2;
								foreach ( $attachment_ids as $attachment_id ) {
									$thumbnail_image    = wp_get_attachment_image($attachment_id, $image_size);
									
									$src_img = explode('"',$thumbnail_image);
									if(count($src_img)< 5){  $src_img[5] ="";}
									// console('$src_img');
									// // var_dump($src_img);
									// console($src_img[5]);
									// console($src_img[4]);
									// console($src_img[6]);
									// console($src_img[7]);
									//add class: demo cursor
									// console( '(' . boolval(strpos($thumbnail_image,'default.png')) . ')' , 'strpos($thumbnail_image)');
									$gallery_elementor = 'data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="all-aaac984" data-elementor-lightbox-title=""';

									if(boolval(strpos($thumbnail_image,'default.png')) == false){
										// $thumbnail_image = str_replace('class="','onclick="currentSlide(' . $i . ')" class="demo cursor ',$thumbnail_image);
										echo '<div class="column"><a ' . $gallery_elementor . ' href="' . $src_img[5] . '">' . $thumbnail_image . '</a></div>';
										console('$thumbnail_image 11');
										console($thumbnail_image);
									}
									$i++;
								}

								echo '</div>';
							}else{
								//show img one img
								$image_main = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'single-post-thumbnail' );
								console($image_main);
								if($image_main)
									echo '<div class="woo-slider-img"><div class="mySlides img-size"><img src="' . $image_main[0] . '"/> </div></div>';
								// else
								// 	echo '<div class="woo-slider-img"><div class="mySlides img-size"><img src=""/> </div></div>';

							}
							console('4');
							echo script_slider();
						?>
						<script>
							jQuery(document).ready(function(){
								var options = {
									vertical: <?php echo ($settings['gallery_slider_style'] == 'vertical') ?  'true' : 'false'; ?>,
									thumbsToShow: <?php echo ( absint($settings['thumbs_show']) > 0 ) ? absint($settings['thumbs_show']) : 4; ?>,
								};
								jQuery().dtwcbe_product_gallery_slider(options);
								jQuery('.woocommerce-product-gallery').css('opacity','1');
							});
						</script>
						<?php
				?>
				</div>
				<?php
				}else{
					if ( get_post_type() == 'product'  ) {
						global $product;
						$product = wc_get_product();
							
						if ( empty( $product ) ) {
							return;
						}
						if ( 'yes' === $settings['sale_flash'] ) {
							wc_get_template( 'loop/sale-flash.php' );
						}
						wc_get_template( 'single-product/product-image.php' );
					}else{
						if ( $product->is_on_sale() && 'yes' === $settings['sale_flash'] ) : ?>
							<?php echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>', $post, $product ); ?>
						<?php endif;
						
						// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
						if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
							return;
						}
						$columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
						$post_thumbnail_id = $product->get_image_id();
						$wrapper_classes   = apply_filters( 'woocommerce_single_product_image_gallery_classes', array(
							'woocommerce-product-gallery',
							'woocommerce-product-gallery--' . ( $product->get_image_id() ? 'with-images' : 'without-images' ),
							'woocommerce-product-gallery--columns-' . absint( $columns ),
							'images',
						) );
						?>
						<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>">
							<figure class="woocommerce-product-gallery__wrapper">
								<?php
								if ( $product->get_image_id() ) {
									$html = wc_get_gallery_image_html( $post_thumbnail_id, true );
								} else {
									$html  = '<div class="woocommerce-product-gallery__image--placeholder">';
									$html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
									$html .= '</div>';
								}
						
								echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $post_thumbnail_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
								
								?>
							</figure>
						</div>
						<?php
					}
					
					// On render widget from Editor - trigger the init manually.
					if ( wp_doing_ajax() ) {
						?>
						<script>
							jQuery( '.woocommerce-product-gallery' ).each( function() {
								jQuery( this ).wc_product_gallery();
							} );
						</script>
						<?php
					}
				}
				
				return ob_get_clean();
				break;
				
			case 'single-product-title':
				
				return get_the_title($product_id);
				break;
				
			case 'single-product-rating':
				
				if ( 'no' === get_option( 'woocommerce_enable_review_rating' ) ) {
					return;
				}
				
				$rating_count = $product->get_rating_count();
				$review_count = $product->get_review_count();
				$average      = $product->get_average_rating();
				
				ob_start();
				
				if ( $rating_count > 0 ) : ?>
					<div class="product elementor">
						<div class="woocommerce-product-rating">
							<?php echo wc_get_rating_html( $average, $rating_count ); ?>
							<?php if ( comments_open($product_id) ) : ?><a href="#reviews" class="woocommerce-review-link" rel="nofollow">(<?php printf( _n( '%s customer review', '%s customer reviews', $review_count, 'woocommerce' ), '<span class="count">' . esc_html( $review_count ) . '</span>' ); ?>)</a><?php endif ?>
						</div>
					</div>
				<?php endif;
				return ob_get_clean();
				break;
				
			case 'single-product-price':
				
				return $product->get_price_html();
				break;
				
			case 'single-product-short-description':
				
				$excerpt = get_the_excerpt($product_id);
				
				$short_description = apply_filters( 'woocommerce_short_description', $excerpt );

				if ( ! $short_description ) {
					return;
				}
				return $short_description;
				
				break;
				
			case 'single-product-add-to-cart':
				ob_start();
				
				do_action( 'woocommerce_' . $product->get_type() . '_add_to_cart' );
				
				// WooCommerce Subscriptions - Display a product's first payment date on the product's page to make sure it's obvious to the customer when payments will start
				if( class_exists('WC_Subscriptions_Synchroniser') ){
					WC_Subscriptions_Synchroniser::products_first_payment_date( true );
				}
				
				return ob_get_clean();
				
				break;
				
			case 'single-product-meta':
			
				ob_start();
				
				$sku = $product->get_sku();
				?>
				<div class="product_meta">

					<?php do_action( 'woocommerce_product_meta_start' ); ?>
				
					<?php if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) : ?>
				
						<span class="sku_wrapper detail-container"><?php esc_html_e( 'SKU:', 'woocommerce' ); ?> <span class="sku"><?php echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/A', 'woocommerce' ); ?></span></span>
				
					<?php endif; ?>
				
					<?php echo wc_get_product_category_list( $product->get_id(), ', ', '<span class="posted_in detail-container">' . _n( 'Category:', 'Categories:', count( $product->get_category_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?>
				
					<?php echo wc_get_product_tag_list( $product->get_id(), ', ', '<span class="tagged_as detail-container">' . _n( 'Tag:', 'Tags:', count( $product->get_tag_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?>
				
					<?php do_action( 'woocommerce_product_meta_end' ); ?>
				
				</div>
				<?php
				return ob_get_clean();
				
				break;
				
			case 'single-product-share':
			
				ob_start();
				
				if( function_exists('acoda_share_post') ){
					echo acoda_share_post();
				}else{
					woocommerce_template_single_sharing ();
				}
				
				return ob_get_clean();
				
				break;
				
			case 'single-product-tabs':
				setup_postdata( $product->get_id() );
				ob_start();
				if( get_post_type() == DTWCBE_Post_Types::CPT ){
					add_filter('the_content', array( __CLASS__, 'product_tab_content_preview'));
				}
				wc_get_template( 'single-product/tabs/tabs.php' );
				
				// On render widget from Editor - trigger the init manually.
				if ( wp_doing_ajax() ) {
					?>
					<script>
						jQuery( '.wc-tabs-wrapper, .woocommerce-tabs, #rating' ).trigger( 'init' );
					</script>
					<?php
				}
						
				return ob_get_clean();
				
				break;
				
			case 'single-product-additional-information':
				ob_start();
				
				wc_get_template( 'single-product/tabs/additional-information.php' );
				
				return ob_get_clean();
				
				break;
			//moein added (
			case 'single-product-additional-information-moein':
				ob_start();
				
				wc_get_template( 'single-product/tabs/additional-information.php' );
				
				return ob_get_clean();
				
				break;
			// )
			case 'single-product-content':
				$theme = wc_get_theme_slug_for_templates();
				$get_product_content = get_post($product_id);
				$content = $get_product_content->post_content;
				// if( is_product() ){
				// 	if( $theme != 'elitepress' || $theme != 'mihouse' ){
				// 		$content = apply_filters('the_content', $content);
				// 		$content = str_replace(']]>', ']]&gt;', $content);
				// 	}
				// }
				return $content;
				
				break;
				
			case 'single-product-reviews':
				ob_start();
				
				if(comments_open() ){
					comments_template();
				}
				
				return ob_get_clean();
				
				break;
				
			case 'single-product-related':
				ob_start();
				
				$product = wc_get_product( $product_id );
				
				if ( ! $product ) {
					return;
				}
				$args = [
					'posts_per_page' => 4,
					'columns' => 4,
					'orderby' => $settings['orderby'],
					'order' => $settings['order'],
				];
		
				if ( ! empty( $settings['posts_per_page'] ) ) {
					$args['posts_per_page'] = $settings['posts_per_page'];
				}
		
				if ( ! empty( $settings['columns'] ) ) {
					$args['columns'] = $settings['columns'];
				}
		
				// Get visible related products then sort them at random.
				$args['related_products'] = array_filter( array_map( 'wc_get_product', wc_get_related_products( $product->get_id(), $args['posts_per_page'], $product->get_upsell_ids() ) ), 'wc_products_array_filter_visible' );
		
				// Handle orderby.
				$args['related_products'] = wc_products_array_orderby( $args['related_products'], $args['orderby'], $args['order'] );
		
				wc_get_template( 'single-product/related.php', $args );
				
				return ob_get_clean();
				
				break;
				
			case 'single-product-upsells':
				ob_start();
				$limit = '-1';
				$columns = 4;
				$orderby = 'rand';
				$order = 'desc';
				
				if ( ! empty( $settings['columns'] ) ) {
					$columns = $settings['columns'];
				}
				
				if ( ! empty( $settings['orderby'] ) ) {
					$orderby = $settings['orderby'];
				}
				
				if ( ! empty( $settings['order'] ) ) {
					$order = $settings['order'];
				}
				
				woocommerce_upsell_display( $limit, $columns, $orderby, $order );
				
				return ob_get_clean();
				
				break;
				
			case 'single-product-custom-key':
				
				if( empty( $settings['custom_key'] ) )
					return '';
					
				return get_post_meta( $product_id, $settings['custom_key'], true );
				
				break;
				
			default: 
				return '';
				break;
		}
	}
	
	public static function product_tab_content_preview($content){
		$product_id = self::get_product_id_in_condition();
		$product = wc_get_product( $product_id );
		$get_product_content = get_post($product_id);
		$content = $get_product_content->post_content;
		return $content;
	}
	
	public static function get_product_id_in_condition(){
		// Default
		$product_id = self::get_newest_product_id_in_condition();
		$template_id = get_the_ID();
		$dtwcbe_condition_product_all = get_option('dtwcbe_condition_product_all', '');
		
		if( $dtwcbe_condition_product_all == $template_id ){
			return $product_id;
		}else{
			
			$dtwcbe_condition_product_in = get_post_meta($template_id, 'dtwcbe_condition_product_in', true);
			
			if( $dtwcbe_condition_product_in == 'in-cat' ){
				$dtwcbe_cat_in = get_post_meta($template_id, 'dtwcbe_cat_in', true);
				if( !empty($dtwcbe_cat_in) ){
					$categories = explode(',',$dtwcbe_cat_in);
					
					$cat = get_term_by('slug', $categories[0], 'product_cat');
					
					$product_id = self::get_newest_product_id_in_condition( $cat->term_id );
					
				}
			}elseif( $dtwcbe_condition_product_in == 'products' ){
				$dtwcbe_product_in = get_post_meta($template_id, 'dtwcbe_product_in', true);
				if( !empty($dtwcbe_product_in) ){
					$in_products = explode(',',$dtwcbe_product_in);
					$id_product = get_posts(array('post_type' => 'product', 'numberposts' => 1, 'post_name__in'  => array($in_products[0])));
					$product_id = $id_product[0]->ID;
				}
			}else{}
		}
		
		return $product_id;
		
	}
	public static function get_newest_product_id_in_condition( $product_category_id = '' ){
	
		// Return the newest product id
		$product_id = 0;
		$args = array(
			'posts_per_page'      => 1,
			'post_type'           => 'product',
			'post_status'         => 'publish',
		);
		if( !empty($product_category_id) ){
			$args['tax_query'] = array(
			        array(
			            'taxonomy'      => 'product_cat',
			            'field' 		=> 'term_id', //This is optional, as it defaults to 'term_id'
			            'terms'         => $product_category_id,
			            'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
			        )
			    );
			$get_product_id_in_cat = 0;
			$products = new WP_Query($args);
				while ($products->have_posts()){ $products->the_post();
					$get_product_id_in_cat = get_the_ID();
				}
				if( !empty($get_product_id_in_cat) )
					$product_id = $get_product_id_in_cat;
				return $product_id;
			wp_reset_postdata();
		}
		
		$product = get_posts($args);
		
		if( !isset($product[0]) ){
			return $product_id;
		}
		
		$product_id = $product[0]->ID;
		
		return $product_id;
	}
	
	public function post_class($classes){
		if( is_singular('dtwcbe_woo_library') )
		{
			$classes[] = 'product';
		}
		return $classes;
	}
}

// function console($str){
// 	echo '
// 	<script>
// 	console.log("' . $str . '");
// 	</script>
// 	';
// }


function script_slider($tag_html = true){
	$return_script = '
		window.addEventListener("load", function() {
			
		})
		let slideIndex = 1;
		showSlides(slideIndex);

		function plusSlides(n) {
			showSlides(slideIndex += n);
		}

		function currentSlide(n) {
			showSlides(slideIndex = n);
		}

		function showSlides(n) {
			let i;
			let slides = document.getElementsByClassName("mySlides");
			let dots = document.getElementsByClassName("demo");
			let captionText = document.getElementById("caption");

			if (n > slides.length) {slideIndex = 1}
			if (n < 1) {slideIndex = slides.length}
			for (i = 0; i < slides.length; i++) {
				slides[i].style.display = "none";
			}
			for (i = 0; i < dots.length; i++) {
				dots[i].className = dots[i].className.replace(" active", "");
			}
			try{
			slides[slideIndex-1].style.display = "block";
			dots[slideIndex-1].className += " active";
			captionText.innerHTML = dots[slideIndex-1].alt;
			}catch(e){}
		}
	';
	
	if ($tag_html) 
		return '<script>' . $return_script . '</script>';
	else
		return $return_script;
}
function style_slider_2 ($tag_html = true) {
	$return_style = '
		/* Position the image woo-slider-img (needed to position the left and right arrows) */
		.woo-slider-img {
			padding-bottom: 100px;
			position: relative;
		}

		/* Hide the images by default */
		.mySlides {
		display: none;
		}

		/* Add a pointer when hovering over the thumbnail images */
		.cursor {
		cursor: pointer;
		}

		/* Number text (1/3 etc) */
		.numbertext {
		color: #f2f2f2;
		font-size: 12px;
		padding: 8px 12px;
		position: absolute;
		top: 0;
		}

		/* woo-slider-img for image text */
		/* .caption-container {
		text-align: center;
		background-color: #222;
		padding: 2px 16px;
		color: white;
		} */

		.row:after {
		content: "";
		display: table;
		clear: both;
		}

		.row{
			position: absolute;
			padding-top: 10px;
			left: 20%;
			right: 20%;
			/* border: 1px solid red; */
			display: flex;
			justify-content: center;    
		}
		/* Six columns side by side */
		.column {
			/* position: absulot;
		float: left; */
		width: 20%;
		padding: 2px;
		max-width: 200px;
		}
		
		.column img{
			height: 100% !important;
		}
		
		/* Add a transparency effect for thumnbail images */
		.demo {
		opacity: 0.6;
		border-radius: 3px !important;
		height: 100% !important;
		}

		.active,
		.demo:hover {
		opacity: 1;
		box-shadow: 1px 1px 10px 10px rgba(128, 128, 128, 0.1);
		}
		.img-size img{
			max-height: 500px !important;
			width: 100% !important;
			border-radius: 15px;
			object-fit: cover;
		  }
		}
		/*
		.moein-product-gallery img{
			max-height:50px;
		}
		.moein_item_gallery{
			width: 50px !important;
			border-radius: 5px;
		}
		*/

	';

	if ($tag_html) 
		return '<style>' . $return_style . '</style>';
	else
		return $return_style;
}
function style_slider ($tag_html = true) {
	$return_style = '
		/* Position the image woo-slider-img (needed to position the left and right arrows) */
		.woo-slider-img {
			padding-bottom: 100px;
			position: relative;
		}

		/* Hide the images by default */
		.mySlides {
		display: none;
		}

		/* Add a pointer when hovering over the thumbnail images */
		.cursor {
		cursor: pointer;
		}

		/* Next & previous buttons */
		/*
		.prev,
		.next {
		cursor: pointer;
		position: absolute;
		top: 40%;
		width: auto;
		padding: 16px;
		margin-top: -50px;
		color: white;
		font-weight: bold;
		font-size: 20px;
		border-radius: 0 3px 3px 0;
		user-select: none;
		-webkit-user-select: none;
		}
		*/
		.prev,
		.next {
			cursor: pointer;
			position: absolute;
			top: 40%;
			font-size: 20px;
			-webkit-user-select: none;
			color: #101010 !important;
			background: #EDEDED;
			border-radius: 25px !important;
			padding: 7px 17px 5px 17px;
			margin: 0px 10px;
			transition: 1s;
		}

		/* Position the "next button" to the right */
		.next {
		left: 0;
		border-radius: 3px 0 0 3px;
		}

		/* On hover, add a black background color with a little bit see-through */
		.prev:hover,
		.next:hover {
		// background-color: rgba(0, 0, 0, 0.8);
		background-color: #96969699 !important;
		color: #101010 !important;
		}

		/* Number text (1/3 etc) */
		.numbertext {
		color: #f2f2f2;
		font-size: 12px;
		padding: 8px 12px;
		position: absolute;
		top: 0;
		}

		/* woo-slider-img for image text */
		/* .caption-container {
		text-align: center;
		background-color: #222;
		padding: 2px 16px;
		color: white;
		} */

		.row:after {
		content: "";
		display: table;
		clear: both;
		}

		.row{
			position: absolute;
			bottom: 10px;
			left: 20%;
			right: 20%;
			/* border: 1px solid red; */
			display: flex;
			justify-content: center;    
		}
		/* Six columns side by side */
		.column {
			/* position: absulot;
		float: left; */
		width: 20%;
		padding: 2px;
		max-width: 200px;
		}

		.column img{
			height: 100% !important;
		  }
		
		/* Add a transparency effect for thumnbail images */
		.demo {
		opacity: 0.6;
		border-radius: 3px !important;
		height: 100% !important;
		}

		.active,
		.demo:hover {
		opacity: 1;
		box-shadow: 1px 1px 10px 10px rgba(128, 128, 128, 0.1);
		}
		.img-size img{
			max-height: 500px !important;
			width: 100% !important;
			border-radius: 15px;
			object-fit: cover;
		  }
		}
		/*
		.moein-product-gallery img{
			max-height:50px;
		}
		.moein_item_gallery{
			width: 50px !important;
			border-radius: 5px;
		}
		*/

	';

	if ($tag_html) 
		return '<style>' . $return_style . '</style>';
	else
		return $return_style;
}

DTWCBE_Single_Product_Elementor::instance();
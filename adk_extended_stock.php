<?php
/**
Plugin Name: Advertikon MinMax Product
Plugin URI: 
Version: 1.0.0
Description: Adds option to set minimum and maximum product quantity for each order
Author: Advertikon
Author URI: shop.advertikon.com.ua
Text Domain: advertikon
Domain Path :
Network :
Lisence: GPLv2 or later

Atleast: 3.5
*/

// Make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if( ! class_exists( 'AdvertikonExtendedStock' ) ) :
class AdvertikonExtendedStock {

	/**
	* @var String $qMinKey Minimum quantity mata key
	*/
	protected $qMinKey = '_adk_qty_min';

	/**
	* @var String $qMaxKey Maximum quantity mata key
	*/
	protected $qMaxKey = '_adk_qty_max';

	/**
	* @var String $qUseCatKey Use catagory level settings mata key
	*/
	protected $qUseCatKey = '_adk_qty_use_cat';

	/**
	* @var String $qMinKeyCat Product category level setting for minimum product quantity in order
	*/
	protected $qMinKeyCat = 'adk_qty_min';	

	/**
	* @var String $qMaxKeyCat Product category level setting for maximin product quantity in order
	*/
	protected $qMaxKeyCat = 'adk_qty_max';	

	/**
	* @var $product Product instnce
	*/
	protected $product = null;

	/**
	* @var Object $message Admin area notice handler
	*/
	static $message = null;

	/**
	* Activation hook handler
	*/
	static public function activate() {
		if( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			preg_match( '/Plugin Name:\s*((?: |\w)+)/i' , file_get_contents( __FILE__ ) , $m );
			self::$message->error( sprintf( __( 'Plugin "%s" needs plugin "WooCommerce" to be installed' , 'advertikon' ) , $m[ 1 ] ) );
		}
	}

	/**
	* Add options to Admin area product stock options metabox
	*/
	public function addMetaboxStock() {
		$postId = get_post()->ID;
		$minMax = $this->getProductMinMax( $postId , true );
		$minQty = $minMax[ 0 ];
		$maxQty = $minMax[ 1 ];

		echo '<div class="options_group show_if_simple show_if_variable show_if_grouped">';

		//Minimum products quantity
		woocommerce_wp_text_input( array(
			'id' 				=> $this->qMinKey,
			'label' 			=> __( 'Minimun products quantity in an order', 'advertikon' ),
			'placeholder' 		=> 1,
			'type' 				=> 'number',
			'custom_attributes' => array(
						'step' 			=> '1',
						'min'			=> '1',
						'data-cat-val'	=> $minQty,
					)
			) );

		//Maximum products quantity
		woocommerce_wp_text_input( array(
			'id' 				=> $this->qMaxKey,
			'label' 			=> __( 'Maximum products quantity in an order', 'advertikon' ),
			'placeholder' 		=> __( 'Unlimited', 'woocommerce' ),
			'description' 		=> __( 'Leave blank for unlimited quantity', 'advertikon' ),
			'type' 				=> 'number',
			'custom_attributes' => array(
						'step' 			=> '1',
						'min'			=> '0',
						'data-cat-val'	=> $maxQty,
					)
			) );

		//Category level settings
		woocommerce_wp_checkbox( array(
			'id' 			=> $this->qUseCatKey,
			'label' 		=> __( 'Apply category level settings', 'advertikon' ),
			//'description' => __( '', 'advertikon' ),
			) );

		echo '</div>';
	}

	/**
	* Add scripts to admin area
	*/
	public function addAdminScripts() {
		if( 'product' == get_post_type() && isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'edit' ) {
			wp_enqueue_script( 'adk_extended_stock_product' , plugins_url( 'assets/js/adk_extended_stock_product.js' , __FILE__ ) , array() , false , true );
		}
	}

	/**
	* Save product meta
	*
	* @param String $postId Product ID
	*/
	public function saveProductMeta( $postId ) {

		//Set minimum order quantity amount meta
		if ( isset( $_POST[ $this->qMinKey ] ) ) {
			update_post_meta( $postId, $this->qMinKey , ( '' === $_POST[ $this->qMinKey ] ) ? '' : (int)( $_POST[ $this->qMinKey ] ) );
		} 

		//Set maximum order quantity amount meta
		if ( isset( $_POST[ $this->qMaxKey ] ) ) {
			update_post_meta( $postId, $this->qMaxKey , ( '' === $_POST[ $this->qMaxKey ] ) ? '' : (int)( $_POST[ $this->qMaxKey ] ) );
		}

		//Set use category level settings meta
		if ( isset( $_POST[ $this->qMaxKey ] ) ) {
			update_post_meta( $postId, $this->qUseCatKey , ( isset( $_POST[ $this->qUseCatKey ] ) ? 'yes' : 'no' ) );
		}

	}

	/**
	* Update category specific settings
	*
	* @param Integer $termId Term ID
	* @param Integer $tt_id Termtaxonomy ID
	* @param String $taxonomy Taxonomy name
	*/
	public function updateProductCatTerms( $termId, $tt_id = '', $taxonomy = '' ) {

		if( ! function_exists( 'update_woocommerce_term_meta' ) ) {
			trigger_error( sprintf( 'Function %s doesn\'t exist' , 'update_woocommerce_term_meta' ) );
			return;
		}

		//Set category level minimum quantity
		if ( isset( $_POST[ $this->qMinKeyCat ] ) && 'product_cat' === $taxonomy ) {
			update_woocommerce_term_meta( $termId, $this->qMinKeyCat , absint( $_POST[ $this->qMinKeyCat ] ) );
		}

		//Set category level maximum quantity
		if ( isset( $_POST[ $this->qMaxKeyCat ] ) && 'product_cat' === $taxonomy ) {
			update_woocommerce_term_meta( $termId, $this->qMaxKeyCat , absint( $_POST[ $this->qMaxKeyCat ] ) );
		}
	}

	/**
	* Adds fields to edit product category form
	*
	* @param Object $term Term object
	*/
	public function addProductCategoryFields( $term ) {

		if( ! function_exists( 'get_woocommerce_term_meta' ) ) {
			trigger_error( sprintf( 'Function %s doesn\'t exist' , 'get_woocommerce_term_meta' ) );
			return;
		}

		$minQty = get_woocommerce_term_meta( $term->term_id , $this->qMinKeyCat , true );
		$maxQty = get_woocommerce_term_meta( $term->term_id , $this->qMaxKeyCat , true );

		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php _e( 'Minimum product quantity in an order', 'advertikon' ); ?></label></th> 
			<td>
				<input id="<?php echo $this->qMinKeyCat; ?>" name="<?php echo $this->qMinKeyCat; ?>" class="postform" value="<?php echo $minQty; ?>">
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php _e( 'Maximum product quantity in an order', 'advertikon' ); ?></label></th> 
			<td>
				<input id="<?php echo $this->qMaxKeyCat; ?>" name="<?php echo $this->qMaxKeyCat; ?>" class="postform" value="<?php echo $maxQty; ?>">
				<p class="description"><?php _e( 'Leave blank for unlimited quantity', 'advertikon' ); ?></p>
			</td>
		</tr>
<?php
	}

	/**
	* Adds fields to add product category form
	*/
	public function addAddProductCategoryFields() {
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php _e( 'Minimum product quantity in an order', 'advertikon' ); ?></label></th> 
			<td>
				<input id="<?php echo $this->qMinKeyCat; ?>" name="<?php echo $this->qMinKeyCat; ?>" class="postform" value="">
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php _e( 'Maximum product quantity in an order', 'advertikon' ); ?></label></th> 
			<td>
				<input id="<?php echo $this->qMaxKeyCat; ?>" name="<?php echo $this->qMaxKeyCat; ?>" class="postform" value="">
				<p class="description"><?php _e( 'Leave blank for unlimited quantity', 'advertikon' ); ?></p>
			</td>
		</tr>
<?php
	}

	/**
	* Get min/max product quantit in single order settings
	*
	* @param Integer $productId Product ID
	* @param Boolean $catOnlt Flag to fetch category based settings only
	* @return Array
	*/
	public function getProductMinMax( $productId = null , $catOnly = false ) {
		$minQty = null;
		$maxQty = null;
		$ret = array();
		$ret[] = & $minQty;
		$ret[] = & $maxQty;
		$lookAtCat = null;
		$productId = $productId ? $productId : $this->getProduct()->get_id();
		if( ! $catOnly ) {
			$minQty = get_post_meta( $productId , $this->qMinKey , true );
			$maxQty = get_post_meta( $productId , $this->qMaxKey , true );
			$lookAtCat = get_post_meta( $productId , $this->qUseCatKey , true ) == 'yes' ? true : false;
		}
		if( $lookAtCat === false || (int)$maxQty > 0 && (int)$minQty > 0 ) {
			return $ret;
		}
		require_once( 'class/category_setting.php' );
		$cs = new CategorySetting;
		foreach( $cs->getIds( $productId ) as $priority ) {
			foreach( $priority as $id ) {
				if( ! $minQty ) {
					$min = get_woocommerce_term_meta( $id , $this->qMinKeyCat , true );
					if( $min > 0 ) {
						$minQty = $min;
					}
				}
				if( ! $maxQty ) {
					$max = get_woocommerce_term_meta( $id , $this->qMaxKeyCat , true );
					if( $max > 0 ) {
						$maxQty = $max;
					}
				}
				if( (int)$minQty > 0 && (int)$maxQty > 0 ) {
					break( 2 );
				}
			}
		}
		return $ret;
	}

	/**
	* Adds min/max product quntity messaage to product page
	*/
	public function modifyProductPage() {
		$minMax = $this->getProductMinMax( $this->getProduct()->get_id() );
		if( ! in_array( (int)$minMax[ 0 ] , array( 0 , 1 ) ) ) {
			echo '<br>' . sprintf( __( 'Minimum quantity for this product is: %u' ), $minMax[ 0 ] ) . '</br>';
		}

		if( (int)$minMax[ 1 ] > 0 ) {
			echo '<br>' . sprintf( __( 'Maximum quantity for this product is: %u' ) , $minMax[ 1 ] ) . '<br>';
		}
	}

	/**
	* Checks whether product can be added to cart
	*
	* @param Boolean $status Alerady setted status
	* @param Integer $productId Product ID. Opional
	* @param Integer $quantity Ptoducts quantity
	* @return Boolean
	*/
	public function addToCartValidation( $status , $productId = null , $quantity = 1 ) {
		if( ! $status ) {
			return $status;
		}
		foreach( WC()->cart->get_cart_item_quantities() as $pId => $q ) {
			if( $productId == $pId ) {
				$quantity += $q;
				break;
			}
		}
		return $this->validateQuantity( $productId , $quantity );
	}

	/**
	* Check product min/max quantity conditions
	* Set WC notification if conditions were not met
	*
	* @param Integer $productId Product ID
	* @param Integer $quantity Quantity of products in a cart
	* @return Boolean
	*/
	protected function validateQuantity( $productId , $quantity ) {
		$minMax = $this->getProductMinMax( $productId );
		$error = false;

		if( $minMax[ 0 ] && $quantity < $minMax[ 0 ] ) {
			$msg = sprintf( __( 'Quantity of "%s" cannot be less than %u pcs' , 'advertikon' ) , $this->getProduct( $productId )->get_title() , $minMax[ 0 ] );
			wc_add_notice( $msg , 'error' );
			$error = true;
		}

		if( $minMax[ 1 ] && $quantity > $minMax[ 1 ] ) {
			$msg = sprintf( __( 'Quantity of "%s" cannot be more than %u pcs' , 'advertikon' ) , $this->getProduct( $productId )->get_title() , $minMax[ 1 ] );
			wc_add_notice( $msg , 'error' );
			$error = true;
		}

		if( $error ) {
			return false;
		}

		return true;
	}

	/**
	* Checks whether product in cart could be modified
	*
	* @param Boolean $status Already setted status
	* @param String $cartItenKey Cart item key
	* @param Array $values Cart item record
	* @param Integer $quntity New product quantity
	* @return Boolean
	*/
	public function updateCartValidation( $status , $cartItemKey, $values, $quantity ) {
		if( ! $status ) {
			return $status;
		}
		return $this->validateQuantity( $values[ 'product_id' ] , $quantity );
	}

	/**
	* Adds select quantity control to each product content template
	*/
	public function addProductQuantityInput() {
		if( ! $this->getProduct()->is_purchasable() ) {
			return;
		}
		$productId = $this->getProduct()->get_id();
		$minMax = $this->getProductMinMax( $productId );
		$min = $minMax[ 0 ] ? $minMax[ 0 ] : 1;
		$max = $minMax[ 1 ] ? $minMax[ 1 ] : PHP_INT_MAX;
		$html = <<<HTML
<input id="adk-quantity-{$productId}" class="adk-quantity" placeholder="quantity" value="{$minMax[ 0 ] }" type="number" min="{$min}" max="{$max}">
<script>

	( function( $ ){
		if( window.adk ) {
			return;
		}
		window.adk = function( evt ) {
			var input = $( evt.target ),
				button = input.parent().find( '.add_to_cart_button' ),
				href = button.attr( 'href' ),
				query = href,
				start = -1,
				params = '';

			if( input.val() > input.attr( 'max' ) ) {
				input.val( input.attr( 'max' ) );
			}
			else if( input.val() < input.attr( 'min' ) ) {
				input.val( input.attr( 'min' ) );
			}

			button.attr( 'data-quantity' , input.val() );

			if( ( start = href.indexOf( '?' ) ) !== -1 ) {
				query = href.substring( 0 , start );
				params = href.substring( start + 1 );
			}

			if( params.indexOf( 'quantity=' ) != -1 ) {
				params = params.replace( /(quantity=)\d*/ , '$1' + input.val() );
			}
			else {
				params += ( params ? '&' : '' ) + 'quantity=' + input.val();
			}

			href = query + '?' + params;
			button.attr( 'href' , href );
		}

	} )( jQuery )

	jQuery( "#adk-quantity-{$productId}" ).on( "change" , window.adk );
	/**
	* Init add to cart button
	* Since button will be rendered lately - timeout needed
	* Not much of elegance - but little choise one has
	*/
	setTimeout( function(){ window.adk( { target: jQuery( "#adk-quantity-{$productId}" ) } ) } , 2000 );
</script>

HTML;
		echo $html;
	}

	/**
	* Get current product
	*
	* @param Mixed $product Product data
	* @return Object
	*/
	public function getProduct( $product = false ) {
		global $post;
		if( ! $this->product || ( $post && $post->ID != $this->product->get_id() ) ) {
			$this->product = wc_get_product( $product );
		}
		return $this->product;
	}
	
}

require_once( dirname(__FILE__ ) . '/class/admin_message.php' );
AdvertikonExtendedStock::$message = new AdvertikonAdminMessage;

$advertikonExtendedStock = new AdvertikonExtendedStock;

//Activation hook
register_activation_hook( __FILE__, 'AdvertikonExtendedStock::activate' );

load_plugin_textdomain( 'advertikon' , false, 'adk_extended_stock/language' );

//Product stock options metabox
add_action( 'woocommerce_product_options_inventory_product_data' , array( $advertikonExtendedStock , 'addMetaboxStock' ) );

//Save product meta
add_action( "woocommerce_process_product_meta" , array( $advertikonExtendedStock , 'saveProductMeta' ) ); 

//Save woocommerce term meta
add_action( 'edit_term' , array( $advertikonExtendedStock , 'updateProductCatTerms' ) , 10 , 3 );

//Create woocommerce term
add_action( 'created_term' , array( $advertikonExtendedStock , 'updateProductCatTerms' ) , 10 , 3 );

//Add custom fields to product category edit page
add_action( 'product_cat_edit_form_fields', array( $advertikonExtendedStock , 'addProductCategoryFields' ) , 20 );

//Add custom fields to add product category page
add_action( 'product_cat_add_form_fields', array( $advertikonExtendedStock , 'addAddProductCategoryFields' ) , 20 );

//Single product page summary
add_action( 'woocommerce_single_product_summary' , array( $advertikonExtendedStock , 'modifyProductPage' ) , 100 );

//Ad to cart validation
add_action( 'woocommerce_add_to_cart_validation' , array( $advertikonExtendedStock , 'addToCartValidation' ) , 10 , 3 );

//Add product quantity for single product (9 - right before the add to cart button)
add_action( 'woocommerce_after_shop_loop_item' , array( $advertikonExtendedStock , 'addProductQuantityInput' ) , 9 );

//Update cart validateion hook
add_action( 'woocommerce_update_cart_validation', array( $advertikonExtendedStock , 'updateCartValidation' ) , 10 , 4 );

if( is_admin() ) {
	add_action( 'admin_enqueue_scripts' , array( $advertikonExtendedStock , 'addAdminScripts' ) );
}
endif;
?>

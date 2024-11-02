<?php
/**
 * Plugin Name: BeRocket Custom Thank You Page for WooCommerce
 * Plugin URI:
 * Description: BeRocket Custom Thank You Page is a extendable WP plugin that helps you better thank your customer after order.
 * Version: 1.0.1.1
 * Author: BeRocket
 * Requires at least: 5.0
 * Author URI: http://berocket.com
 * Text Domain: BeRocket_CTY_page_domain
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) exit;
load_plugin_textdomain( 'BeRocket_TY_page_domain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

class BeRocket_CTY_page {
	/**
	 *call init func
	 *@return void
	 */
	public function __construct() {
		$this->init();
	}
	/**
	 *initialise settings
	 *@return void
	 */
	public function init() {
		add_action( 'woocommerce_payment_complete',  array( $this, 'get_customer_id') );
		//Add custom ty page to menu
		add_action( 'admin_menu', array( $this, 'register_my_custom_menu_page' ) );
		//Get url to redirect customer after order
		add_action( 'woocommerce_get_return_url', array( $this, 'get_url_from_settings' ) );
		//Add meta-box(drop-down list) in product settings
		add_action( 'woocommerce_product_options_advanced', array( $this, 'add_product_data_fields' ) );
		//Page url from product settings
		add_action( 'woocommerce_process_product_meta', array( $this, 'woocom_save_general_proddata_custom_field' ) );
		//Register settings
		add_action( 'admin_init', array( $this, 'register_mysettings' ) );
		//Display content on post
		add_filter( 'the_content', array( $this, 'out_content_with_settings' ) );
    }
    /**
	 *add custom thank you page to admin menu
	 *@return void
	 */
	public function register_my_custom_menu_page(){
		add_submenu_page(
			'woocommerce', __('Thank you page'),  __('Thank you page'), 'manage_options', 'custompage', array( $this, 'create_ty_page' )
		);
	}
	/**
	 *initialise settings
	 *@return void
	 */
	public function register_mysettings() {
		register_setting( 'Berocket-group', 'berocket_global_page_id' );
		register_setting( 'Berocket-group', 'berocket_content_options' );
	}

	/**
	 *Face thank you page settings
	 *@return void
	 */
	public function create_ty_page() {
		wp_enqueue_script ( 'br-thank-you-script', plugins_url( 'js/show_selected_item.js', __FILE__ ), array ('jquery'), null, true );
	?>
		<div class="wrap">
			<h2><?php _e( 'General settings', 'BeRocket_TY_page_domain' )?></h2>
			<form method="post" action="options.php">
				<?php
					settings_fields('Berocket-group');
				?>
				<table  id = 'berocket_global_page_id_for_js' class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e('Choose Thank You page: ', 'BeRocket_TY_page_domain')?></th>
						<td>
							<select name="berocket_global_page_id">
								<?php
								$pages = get_pages();
								$option = "<option value ='None'>" . 'None' ."</option>";
								echo $option;
								foreach ( $pages as $page ) {
									$option 		= '<option value="' .  $page->ID  .'"';
									if (  $page->ID  == get_option('berocket_global_page_id') ) {
										$option .= ' selected="selected"';
									}
									$option .= '>' . ( (strlen( $page->post_title ) > 50) ? substr( $page->post_title,0,45 ).'...' : $page->post_title );
									$option .= '</option>';
									echo $option;
								}
								?>
							</select>
						</td>
					</tr>
				</table>
				<?php $berocket_global_page_id = get_option('berocket_global_page_id');?>

				<table id = 'berocket_content_options_for_js'>
					<tr>
						<?php //Checkboxes + the checkbox selected
						$berocket_content_options = get_option('berocket_content_options');?>
						<td>
							  <input type="checkbox" name = "berocket_content_options[hide_order_details]" value = "hide_order_details"
								<?php
								if( ! empty ( $berocket_content_options[ 'hide_order_details' ] ) )
									echo "checked ='checked'"?>>
							  <span class="checkmark"></span>
							  <label class="container"><?php _e( 'Hide order details', 'BeRocket_TY_page_domain' )?></label>

						</td>
					</tr>
					<tr>
						<td>
							<input type="checkbox" name = "berocket_content_options[show_cross_sell]" value = "show_cross_sell"
								<?php
								if ( ! empty ( $berocket_content_options[ 'show_cross_sell' ] ) )
									echo "checked ='checked'"?>>
							  <span class="checkmark"></span>
							<label class="container"><?php _e( 'Show cross-sell products', 'BeRocket_TY_page_domain' )?></label>
						</td>
					</tr>
					<tr>
						<td>
							<input type="checkbox" name = "berocket_content_options[show_up_sell]" value = "show_up_sell"
								<?php
								if( ! empty ( $berocket_content_options[ 'show_up_sell' ] ) )
									echo "checked ='checked'"?>>
							  <span class="checkmark"></span>
							<label class="container"><?php _e( 'Show up-sell products', 'BeRocket_TY_page_domain' )?></label>
						</td>
					</tr>
				</table>
				</div>
					<p class="submit1">
						<input type="submit" class="button-primary" name ="savechanges" value="<?php _e( 'Save Changes', 'BeRocket_TY_page_domain' ) ?>" />
					</p>
			</form>

		<?php
	}
	/**
	 *get last order id
	 *@return last order id
	 */
	public function get_last_order_id(){
		global $wpdb;
		$statuses = array_keys( wc_get_order_statuses() );
		$statuses = implode( "','", $statuses );

		// Getting last Order ID ( max value )
		$results = $wpdb->get_col( "
			SELECT MAX(ID) FROM {$wpdb->prefix}posts
			WHERE post_type LIKE 'shop_order'
			AND post_status IN ( '$statuses' )
		" );
		return reset( $results );
	}
	/**
	 *get last order id
	 *@return last order id
	 */
	public function get_customer_id() {
		$berocket_order_id 				= $this -> get_last_order_id();
		if ( ! empty ( $berocket_order_id ) ) {
			$order 						= wc_get_order( $berocket_order_id );
			$customer_id 				= $order-> get_customer_id();
			// Get the user ID
			if ( ! function_exists( 'wp_get_current_user' ) )
					return 0;
			$user = wp_get_current_user();
			$user_id  = $user->ID;
			if ( $customer_id == $user_id ) {
				return ( isset( $user->ID ) ? $user->ID : 0 );
			} else {
				return;
			}
		} else {
			return;
		}
	}

	/**
	 *check conditions and return link for redirect
	 *@return link
	 */
	public function get_url_from_settings() {
		$berocket_order_id 						= $this -> get_last_order_id();

		if ( empty($berocket_order_id) ) {
			$WC_Order = new WC_Order();
			$true_url = $WC_Order->get_checkout_order_received_url();
		} else {
			$berocket_global_page_id	= get_option('berocket_global_page_id');
			$order 						= wc_get_order( $berocket_order_id );
			$true_url 					= $order->get_checkout_order_received_url();
			$items 						= $order->get_items();
			$info						= array_pop( $items );
			$id_product 				= $info['product_id'];
			$selected_page_for_product 	= get_post_meta( $id_product, '_selected_page_for_product', true );

			if( ! empty( $selected_page_for_product ) and ( $selected_page_for_product != ' ' and $selected_page_for_product != 'Default' ) ) {
				$true_url = get_page_link( $selected_page_for_product );
			} elseif ( ! empty( $berocket_global_page_id ) and ( $berocket_global_page_id != 'None') ) {
				$true_url =  get_page_link( $berocket_global_page_id );
			}
		}
		return $true_url;
	}

	/**
	 *check conditions and display info(table details, cross-sell, up-sell) on thanq page
	 *@param text record that u wanna to display
	 *@return content
	 */
	public function out_content_with_settings( $content ) {
		$cur_user_id = get_current_user_id();
		$customer_id = $this -> get_customer_id();

		if( ! empty( $cur_user_id ) and ! empty( $customer_id ) and  $cur_user_id == $customer_id ) {
			$url_redirect_page			= $this -> get_url_from_settings();
			$berocket_global_page_id	= get_option('berocket_global_page_id');
			$berocket_content_options 	= get_option('berocket_content_options');
			$berocket_order_id 			= $this -> get_last_order_id();

			if( ! empty( $berocket_order_id ) ) {
				$order 							= wc_get_order( $berocket_order_id );
				$items 							= $order->get_items();
				$info 							= array_pop( $items );
				$id_product						= $info['product_id'];
				$selected_page_for_product 		= get_post_meta( $id_product, '_selected_page_for_product', true );
				$berocket_product_order_details = get_post_meta( $id_product, '_berocket_product_order_details', true );
				$berocket_product_cross_sell 	= get_post_meta( $id_product, '_berocket_product_cross_sell', true );
				$berocket_product_up_sell 		= get_post_meta( $id_product, '_berocket_product_up_sell', true );
				$is_add_option_to_product 		= get_post_meta( $id_product, '_is_add_option_to_product', true );
                $display_order_details = $up_sell = $cross_sell = '';

				$berocket_true_url = url_to_postid( $url_redirect_page );
				if ( $selected_page_for_product == 'Default' || $berocket_global_page_id == 'None') {
					$berocket_true_url = $order->get_checkout_order_received_url();
				}

				if ( is_page( $berocket_true_url ) ) {

					if( $berocket_global_page_id != 'None' ) {
						if ( ( !empty( $berocket_content_options ) and !array_key_exists( 'hide_order_details', $berocket_content_options ) ) 
						||  $berocket_product_order_details == 'no' ) {

							$display_order_details = $this-> display_order_details();
						}

						if ( ( $berocket_product_cross_sell == 'yes' )
						||  ( ! empty( $berocket_content_options ) and array_key_exists( 'show_cross_sell',  $berocket_content_options ) ) ) {

							$cross_sell = $this-> display_cross_sell_or_up_sell( 'cross-sell', 2 );
						}

						if ( ( $berocket_product_up_sell == 'yes' )
						||  ( ! empty( $berocket_content_options ) and array_key_exists( 'show_ul_sell',  $berocket_content_options ) ) ) {

							$up_sell = $this-> display_cross_sell_or_up_sell( 'up-sell', 2 );
						}

						return $display_order_details . $content . "<br>". $up_sell . $cross_sell;
					}
				}
			}
		}
			return $content;
	}
	/**
	 *get info about order (details)
	 *@return void
	 */
	public function woocommerce_order_details_table( $berocket_order_id ) {
		if ( ! $berocket_order_id ) {
			return;
		}
		wc_get_template( 'order/order-details.php', array(
			'order_id' => $berocket_order_id,
		) );
	}
	/**
	 *remember value of drop-down list
	 *@return void
	 */
	public function woocom_save_general_proddata_custom_field( $post_id ) {
		$is_add_option_to_product = isset( $_POST['_is_add_option_to_product'] ) ? 'yes' : 'no';
		if ( ! empty( $is_add_option_to_product ) ) {
			update_post_meta( $post_id, '_is_add_option_to_product', $is_add_option_to_product );
		}

		if( ! empty( $is_add_option_to_product ) and $is_add_option_to_product != 'no') {

			$select = $_POST['_selected_page_for_product'];
			if ( ! empty( $select ) ) {
				update_post_meta( $post_id, '_selected_page_for_product', esc_attr( $select ) );
			}

			$berocket_product_cross_sell = isset( $_POST['_berocket_product_cross_sell'] ) ? 'yes' : 'no';
			if ( ! empty( $berocket_product_cross_sell ) ) {
				update_post_meta( $post_id, '_berocket_product_cross_sell', $berocket_product_cross_sell );
			}

			$berocket_product_order_details = isset( $_POST['_berocket_product_order_details'] ) ? 'yes' : 'no';
			if ( ! empty( $berocket_product_order_details ) ) {
				update_post_meta( $post_id, '_berocket_product_order_details', $berocket_product_order_details );
			}

			$berocket_product_up_sell = isset( $_POST['_berocket_product_up_sell'] ) ? 'yes' : 'no';
			if ( ! empty( $berocket_product_up_sell ) ) {
				update_post_meta( $post_id, '_berocket_product_up_sell', $berocket_product_up_sell );
			}

		} else {
			delete_post_meta( $post_id, '_berocket_product_up_sell', $berocket_product_up_sell );
			delete_post_meta( $post_id, '_berocket_product_order_details', $berocket_product_order_details );
			delete_post_meta( $post_id, '_berocket_product_cross_sell', $berocket_product_cross_sell );
			delete_post_meta( $post_id, '_selected_page_for_product', esc_attr( $select ) );
		}

	}
	/**
	 *display list of pages and push in array
	 *@return void
	 */
	public function add_product_data_fields() {
		global $post, $woocomerce;
		wp_enqueue_script ( 'br-thank-you-script', plugins_url( 'js/show_selected_item.js', __FILE__ ), array ('jquery'), null, true );

		woocommerce_wp_checkbox( array(
			'id' 			=> '_is_add_option_to_product',
			'wrapper_class' => '',
			'label'			=> __('Private "Thank You page"', 'BeRocket_TY_page_domain'),
			'description' 	=> __( 'Add custom Thank You page that will be used for current product after checkout', 'BeRocket_TY_page_domain' ),
		));

		$pages 							 = get_pages();
		$berocket_global_page_id		 = get_option('berocket_global_page_id');
		$selected_page_for_product_id 	 = get_post_meta( $post->ID, '_selected_page_for_product', true );
		$selected_page_for_product_title = get_the_title( $selected_page_for_product_id );
		$value = "Global Thank You page from settings";
		$option_array = array();

		foreach ( $pages as $key => $page ) {
			$option_id 	=  $page->ID;
			$option_title	= ( (strlen( $page->post_title ) > 55) ? substr( $page->post_title,0,45 ).'...' : $page->post_title );

			if($option_id == $berocket_global_page_id) {
				$option_array[ $option_id ] = "Global Thank You page from settings" . '(' . $option_title .')';
			}

			$option_array["Default"]		= "Default Woocomerce";

			if( $option_id != $berocket_global_page_id) {
				$option_array[$option_id]	= $option_title;
			}
		}

		if ( ! empty( $selected_page_for_product_id )
		and ( $selected_page_for_product_id != 'None' ) ) {
			$value = $berocket_global_page_id;
		}

		if ( ! empty( $selected_page_for_product_id ) ) {
			$value = $selected_page_for_product_id;
		}

		echo "<div id = 'options_group' style='display: none' >";

			woocommerce_wp_select( array(
				'id' 		=> '_selected_page_for_product',
				'label' 	=> __('Select Thank you page for this product: ', 'BeRocket_TY_page_domain'),
				'options' 	=> $option_array,
				'value' 	=> $value,
			));
			woocommerce_wp_checkbox( array(
				'id' 			=> '_berocket_product_order_details',
				'wrapper_class' => '',
				'label'			=> __('Hide order details: ', 'BeRocket_TY_page_domain'),
			));
			woocommerce_wp_checkbox( array(
				'id'            => '_berocket_product_cross_sell',
				'wrapper_class' => '',
				'label'         => __('Show cross-sell: ', 'BeRocket_TY_page_domain' ),
			));
			woocommerce_wp_checkbox( array(
				'id' 			=> '_berocket_product_up_sell',
				'wrapper_class' => '',
				'label' 		=> __('Show up-sell: ', 'BeRocket_TY_page_domain'),
			));

		echo "</div>";
	}

	/**
	 *check conditions and display info(order details) on thanq page
	 *@param text record that will be displayed
	 *@param option that was checked on plugin main-page
	 *@return order details
	 */
	function display_order_details() {
		$berocket_order_id 	= $this->get_last_order_id();
		$order 				= wc_get_order( $berocket_order_id );
		$items 				= $order->get_items();
		$string_template    = "";

		ob_start();
        //isdisplayContent
        echo "<div class= 'woocommerce'>";
            $this->woocommerce_order_details_table( $berocket_order_id );
        echo "</div>";
		$string_template = ob_get_clean();

		return $string_template;
	}
	/**
	 *check conditions and display info(crosssell) on thanq page
	 *@param text record that will be displayed
	 *@param option that was checked on plugin option page
	 *@param option cross-sell / up-sell
	 *@return crosssell products
	 */
	function display_cross_sell_or_up_sell( $type = 'cross-sell', $amount = '2' ) {
		$berocket_order_id 	= $this->get_last_order_id();
		$order 				= wc_get_order( $berocket_order_id );
		$string_template    = "";

		if( $type == 'cross-sell' ) {
			$function = 'get_cross_sell_ids';
		}

		if( $type == 'up-sell' ) {
			$function = 'get_upsell_ids';
		}

		$products_ids = array();
		foreach ( $order->get_items() as $item_id => $item_data ) {
			$product_id			= $item_data->get_product_id();
			$WC_Product			= new WC_Product( $product_id );
			$products_ids		= array_merge( $products_ids, $WC_Product->{$function}() );

			if( count($products_ids) >= $amount ) {
				$products_ids = array_unique( $products_ids );
				if( count($products_ids) >= $amount ) {
					break;
				}
			}
		}

		$products = array();
		foreach ( $products_ids as $products_id ) {
			if ( count( $products ) == $amount ) {
				break;
			}
            $products[] = wc_get_product( $products_id );
		}

		if ( ! empty( $products ) ) {
            ob_start();
			echo "<div class= 'woocommerce'>";
                if( $type == 'up-sell') {
                    wc_get_template( 'single-product/up-sells.php', array(
                        'upsells'       => $products
                    ));
                }

                if( $type == 'cross-sell') {
                    wc_get_template( 'cart/cross-sells.php', array(
                        'cross_sells'    => $products
                    ) );
                }
            echo "</div>";
            $string_template = ob_get_clean();
		}
		return $string_template;
	}
}
$object = new BeRocket_CTY_page( __FILE__ );

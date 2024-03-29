<?php
/**
 * TBC Installments Gateway.
 *
 * Provides a TBC Installments Payment Gateway.
 *
 * @class       WC_Gateway_TBC
 * @extends     WC_Payment_Gateway
 * @version     1.0
 * @package     WooCommerce\Classes\Payment
 */

class WC_Gateway_TBC extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		// Setup general properties.
		$this->setup_properties();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->api_key            = $this->get_option( 'api_key' );
		$this->api_secret         = $this->get_option( 'api_secret' );
		$this->vendor_url         = $this->get_option( 'vendor_url' );
		$this->merchant_key       = $this->get_option( 'merchant_key' );
		$this->campaign_id        = $this->get_option( 'campaign_id' );
		$this->handling_fee       = $this->get_option( 'handling_fee' );
		$this->instructions       = $this->get_option( 'instructions' );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
		$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'change_payment_complete_order_status' ), 10, 3 );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id                 = 'tbc';
		$this->icon               = apply_filters( 'woocommerce_tbc_icon', plugins_url( '../assets/tbc_icon.png', __FILE__ ) );
		$this->method_title       = __( 'TBC Installments', 'tbc-woo' );
		$this->api_key            = __( 'TBC API Key', 'tbc-woo' );
		$this->api_secret         = __( 'TBC API Secret', 'tbc-woo' );
		$this->vendor_url         = __( 'TBC endpoint URL', 'tbc-woo' );
		$this->merchant_key       = __( 'TBC Merchant Key', 'tbc-woo' );
		$this->campaign_id        = __( 'TBC Campaign ID', 'tbc-woo' );
		$this->handling_fee       = __( 'TBC handling fee (%)', 'tbc-woo' );
		$this->method_description = __( 'Pay as you go with TBC bank.', 'tbc-woo' );
		$this->has_fields         = false;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'tbc-woo' ),
				'label'       => __( 'Enable TBC Installments', 'tbc-woo' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'              => array(
				'title'       => __( 'Title', 'tbc-woo' ),
				'type'        => 'text',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'tbc-woo' ),
				'default'     => __( 'TBC Installments', 'tbc-woo' ),
				'desc_tip'    => true,
			),
			'description'        => array(
				'title'       => __( 'Description', 'tbc-woo' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your website.', 'tbc-woo' ),
				'default'     => __( 'Pay with cash upon delivery.', 'tbc-woo' ),
				'desc_tip'    => true,
			),
			'api_key'        => array(
				'title'       => __( 'API Key', 'tbc-woo' ),
				'type'        => 'text',
				'description' => __( 'Add an API Key provided by TBC bank.', 'tbc-woo' ),
				'desc_tip'    => true,
			),
			'api_secret'        => array(
				'title'       => __( 'API Secret', 'tbc-woo' ),
				'type'        => 'text',
				'description' => __( 'Add an API secret provided by TBC bank.', 'tbc-woo' ),
				'desc_tip'    => true,
			),
			'vendor_url'        => array(
				'title'       => __( 'Vendor URL', 'tbc-woo' ),
				'type'        => 'text',
				'description' => __( 'Add an endpoint without queries, provided by TBC bank.', 'tbc-woo' ),
				'desc_tip'    => true,
			),
			'merchant_key'      => array(
				'title'       => __( 'Merchant Key', 'tbc-woo' ),
				'type'        => 'text',
				'description' => __( 'Add a Merchant Key provided by TBC bank.', 'tbc-woo' ),
				'desc_tip'    => true,
			),
			'campaign_id'      => array(
				'title'       => __( 'Campaign ID', 'tbc-woo' ),
				'type'        => 'text',
				'description' => __( 'Add a Campaign ID provided by TBC bank.', 'tbc-woo' ),
				'desc_tip'    => true,
			),
			'handling_fee'        => array(
				'title'       => __( 'Handling Fee (%)', 'tbc-woo' ),
				'type'        => 'text',
				'description' => __( 'Add an additional handling fee in percentage.', 'tbc-woo' ),
				'default'     => __( '5', 'tbc-woo' ),
				'desc_tip'    => true,
			),
			'instructions'       => array(
				'title'       => __( 'Instructions', 'tbc-woo' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'tbc-woo' ),
				'default'     => __( 'Pay with cash upon delivery.', 'tbc-woo' ),
				'desc_tip'    => true,
			),
			'enable_for_methods' => array(
				'title'             => __( 'Enable for shipping methods', 'tbc-woo' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 400px;',
				'default'           => '',
				'description'       => __( 'If COD is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'tbc-woo' ),
				'options'           => $this->load_shipping_method_options(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select shipping methods', 'tbc-woo' ),
				),
			),
			'enable_for_virtual' => array(
				'title'   => __( 'Accept for virtual orders', 'tbc-woo' ),
				'label'   => __( 'Accept COD if the order is virtual', 'tbc-woo' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),
		);
	}

	/**
	 * Check If The Gateway Is Available For Use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$order          = null;
		$needs_shipping = false;

		// Test if shipping is needed first.
		if ( WC()->cart && WC()->cart->needs_shipping() ) {
			$needs_shipping = true;
		} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
			$order    = wc_get_order( $order_id );

			// Test if order needs shipping.
			if ( 0 < count( $order->get_items() ) ) {
				foreach ( $order->get_items() as $item ) {
					$_product = $item->get_product();
					if ( $_product && $_product->needs_shipping() ) {
						$needs_shipping = true;
						break;
					}
				}
			}
		}

		$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

		// Virtual order, with virtual disabled.
		if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
			return false;
		}

		// Only apply if all packages are being shipped via chosen method, or order is virtual.
		if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {
			$order_shipping_items            = is_object( $order ) ? $order->get_shipping_methods() : false;
			$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

			if ( $order_shipping_items ) {
				$canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids( $order_shipping_items );
			} else {
				$canonical_rate_ids = $this->get_canonical_package_rate_ids( $chosen_shipping_methods_session );
			}

			if ( ! count( $this->get_matching_rates( $canonical_rate_ids ) ) ) {
				return false;
			}
		}

		return parent::is_available();
	}

	/**
	 * Checks to see whether or not the admin settings are being accessed by the current request.
	 *
	 * @return bool
	 */
	private function is_accessing_settings() {
		if ( is_admin() ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['section'] ) || 'tbc' !== $_REQUEST['section'] ) {
				return false;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			return true;
		}

		return false;
	}

	/**
	 * Loads all of the shipping method options for the enable_for_methods field.
	 *
	 * @return array
	 */
	private function load_shipping_method_options() {
		// Since this is expensive, we only want to do it if we're actually on the settings page.
		if ( ! $this->is_accessing_settings() ) {
			return array();
		}

		$data_store = WC_Data_Store::load( 'shipping-zone' );
		$raw_zones  = $data_store->get_zones();

		foreach ( $raw_zones as $raw_zone ) {
			$zones[] = new WC_Shipping_Zone( $raw_zone );
		}

		$zones[] = new WC_Shipping_Zone( 0 );

		$options = array();
		foreach ( WC()->shipping()->load_shipping_methods() as $method ) {

			$options[ $method->get_method_title() ] = array();

			// Translators: %1$s shipping method name.
			$options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method', 'tbc-woo' ), $method->get_method_title() );

			foreach ( $zones as $zone ) {

				$shipping_method_instances = $zone->get_shipping_methods();

				foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {

					if ( $shipping_method_instance->id !== $method->id ) {
						continue;
					}

					$option_id = $shipping_method_instance->get_rate_id();

					// Translators: %1$s shipping method title, %2$s shipping method id.
					$option_instance_title = sprintf( __( '%1$s (#%2$s)', 'tbc-woo' ), $shipping_method_instance->get_title(), $shipping_method_instance_id );

					// Translators: %1$s zone name, %2$s shipping method instance name.
					$option_title = sprintf( __( '%1$s &ndash; %2$s', 'tbc-woo' ), $zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'tbc-woo' ), $option_instance_title );

					$options[ $method->get_method_title() ][ $option_id ] = $option_title;
				}
			}
		}

		return $options;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 *
	 * @since  3.4.0
	 *
	 * @param  array $order_shipping_items  Array of WC_Order_Item_Shipping objects.
	 * @return array $canonical_rate_ids    Rate IDs in a canonical format.
	 */
	private function get_canonical_order_shipping_item_rate_ids( $order_shipping_items ) {

		$canonical_rate_ids = array();

		foreach ( $order_shipping_items as $order_shipping_item ) {
			$canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
		}

		return $canonical_rate_ids;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 *
	 * @since  3.4.0
	 *
	 * @param  array $chosen_package_rate_ids Rate IDs as generated by shipping methods. Can be anything if a shipping method doesn't honor WC conventions.
	 * @return array $canonical_rate_ids  Rate IDs in a canonical format.
	 */
	private function get_canonical_package_rate_ids( $chosen_package_rate_ids ) {

		$shipping_packages  = WC()->shipping()->get_packages();
		$canonical_rate_ids = array();

		if ( ! empty( $chosen_package_rate_ids ) && is_array( $chosen_package_rate_ids ) ) {
			foreach ( $chosen_package_rate_ids as $package_key => $chosen_package_rate_id ) {
				if ( ! empty( $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ] ) ) {
					$chosen_rate          = $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ];
					$canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
				}
			}
		}

		return $canonical_rate_ids;
	}

	/**
	 * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
	 *
	 * @since  3.4.0
	 *
	 * @param array $rate_ids Rate ids to check.
	 * @return boolean
	 */
	private function get_matching_rates( $rate_ids ) {
		// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
		return array_unique( array_merge( array_intersect( $this->enable_for_methods, $rate_ids ), array_intersect( $this->enable_for_methods, array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			$curl = curl_init();

			$apiKey = $this->api_key;
			$apiSecret = $this->api_secret;
			$apiCreds = $apiKey . ':' . $apiSecret;
			$merchantKey = $this->merchant_key;
			$campaignId = $this->campaign_id;
			$handling_fee = $this->handling_fee;

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://api.tbcbank.ge/oauth/token',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => 'grant_type=client_credentials&scope=online_installments',
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/x-www-form-urlencoded',
					'Authorization: Basic ' . base64_encode($apiCreds)
				),
			));

			$response = curl_exec($curl);
			$myJson = json_decode($response);

			$access_token =  $myJson->access_token;

			$productsArr = array();
			$price = 0;

			foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
				/** @var WC_Product $product */
				$product = $cart_item['data'];
				$product_quantity = $cart_item['quantity'];

				$singleProduct = array(
					'name' => $product->get_name(),
					'price' => ceil($cart_item["line_total"] / (100 - $handling_fee) * 100),
					'quantity' => $product_quantity
				);

				$price += ceil($cart_item["line_total"] / (100 - $handling_fee) * 100);

				$productsArr[] = $singleProduct;
			}

			$tbcInstallment = array(
				'merchantKey' => $merchantKey,
				'priceTotal' => $price,
				'invoiceId' => strval($order_id),
				'campaignId' => $campaignId,
				'products' => $productsArr
			);

			$tbcInstallmentJSON = json_encode($tbcInstallment);

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://api.tbcbank.ge/v1/online-installments/applications',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_HEADER => true,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS =>$tbcInstallmentJSON,
				CURLOPT_HTTPHEADER => array(
					'Authorization: Bearer ' . $access_token,
					'Content-Type: application/json'
				),
			));

			$response = curl_exec($curl);

			$curl_info = curl_getinfo($curl);
			$headers = substr($response, 0, $curl_info["header_size"]);

			preg_match("!\r\n(?:Location|URI): *(.*?) *\r\n!", $headers, $matches);
			$url = $matches[1];

			curl_close($curl);

			$response = wp_remote_post( $url, array( 'timeout' => 45 ) );

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				return "Something went wrong: $error_message";
			}
			
			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$error_message = $response->get_error_message();
				return "Something went wrong: $error_message";
			} 

			if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
				$order->payment_complete();

				WC()->cart->empty_cart();
			}
		}

		return array(
			'result' => 'success',
			'redirect' => $url
		);
	}

	/**
	* Output for the order received page.
	*/
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}
	}

	/**
	* Change payment complete order status to completed for COD orders.
	*
	* @since  3.1.0
	* @param  string         $status Current order status.
	* @param  int            $order_id Order ID.
	* @param  WC_Order|false $order Order object.
	* @return string
	*/
	public function change_payment_complete_order_status( $status, $order_id = 0, $order = false ) {
		if ( $order && 'tbc' === $order->get_payment_method() ) {
			$status = 'completed';
		}
		return $status;
	}

	/**
	* Add content to the WC emails.
	*
	* @param WC_Order $order Order object.
	* @param bool     $sent_to_admin  Sent to admin.
	* @param bool     $plain_text Email format: plain text or HTML.
	*/
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
		}
	}
}

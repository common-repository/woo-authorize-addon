<?php
if ( ! defined( 'WOO_WCAUTHNET' ) ) {
	exit;
} // Exit if accessed directly

if( ! class_exists( 'WOO_WCAUTHNET' ) ){
	/**
	 * WooCommerce Authorize.net main class
	 *
	 * @since 1.0.0
	 */
	class WOO_WCAUTHNET {
		/**
		 * Single instance of the class
		 *
		 * @var \WOO_WCAUTHNET
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \WOO_WCAUTHNET
		 * @since 1.0.0
		 */
		public static function get_instance(){
			if( is_null( self::$instance ) ){
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 *
		 * @param array $details
		 * @return \WOO_WCAUTHNET
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'plugin_fw_loader' ), 15 );
			add_action( 'plugins_loaded', array( $this, 'privacy_loader' ), 20 );

			// enqueue assets
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

			// add filter to append wallet as payment gateway
			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_to_gateways' ) );

			if( defined( 'WOO_WCAUTHNET_PREMIUM' ) && WOO_WCAUTHNET_PREMIUM ){
				WOO_WCAUTHNET_Premium();
			}
		}
		
		/**
		 * Enqueue scripts
		 *
		 * @return void
		 */
		public function enqueue() {
			global $wp;
			$path = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'unminified/' : '';
			$suffix = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '' : '.min';

			if( is_checkout() || isset( $wp->query_vars['add-payment-method'] ) ){
				wp_enqueue_script( 'woo-wcauthnet-form-handler', WOO_WCAUTHNET_URL . 'js/' . $path . 'authorize-net' . $suffix . '.js', array( 'jquery' ), WOO_WCAUTHNET_VERSION, true );
			}
		}

		/**
		 * Adds Authorize.net Gateway to payment gateways available for woocommerce checkout
		 *
		 * @param $methods array Previously available gataways, to filter with the function
		 *
		 * @return array New list of available gateways
		 * @since 1.0.0
		 * @author Antonio La Rocca <antonio.larocca@wooemes.it>
		 */
		public function add_to_gateways( $methods ) {
			if( defined( 'WOO_WCAUTHNET_PREMIUM' ) && WOO_WCAUTHNET_PREMIUM ){
				$methods[] = 'WOO_WCAUTHNET_Credit_Card_Gateway_Premium';
				$methods[] = 'WOO_WCAUTHNET_eCheck_Gateway';
			}
			else{
				$methods[] = 'WOO_WCAUTHNET_Credit_Card_Gateway';
			}
			return $methods;
		}

		/* === PLUGIN FW LOADER === */

		/**
		 * Loads plugin fw, if not yet created
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function plugin_fw_loader() {
			if ( ! defined( 'WAA_CORE_PLUGIN' ) ) {
				global $plugin_fw_data;
				if( ! empty( $plugin_fw_data ) ){
					$plugin_fw_file = array_shift( $plugin_fw_data );
					require_once( $plugin_fw_file );
				}
			}
		}

		/* === PRIVACY LOADER === */

		/**
		 * Loads privacy class
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function privacy_loader() {
			if( class_exists( 'WOO_Privacy_Plugin_Abstract' ) ) {
				require_once( WOO_WCAUTHNET_INC . 'class-woo-wcauthnet-privacy.php' );
				new WOO_WCAUTHNET_Privacy();
			}
		}
	}
}

/**
 * Unique access to instance of WOO_WCAUTHNET class
 *
 * @return \WOO_WCAUTHNET
 * @since 1.0.0
 */
function WOO_WCAUTHNET(){
	return WOO_WCAUTHNET::get_instance();
}

// Let's start the game!
// Create unique instance of the class
WOO_WCAUTHNET();
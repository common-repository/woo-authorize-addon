<?php
if ( ! defined( 'WOO_WCAUTHNET' ) ) {
	exit;
} // Exit if accessed directly

if( ! class_exists( 'WOO_WCAUTHNET_Premium' ) ){
	/**
	 * WooCommerce Authorize.net main class
	 *
	 * @since 1.0.0
	 */
	class WOO_WCAUTHNET_Premium {
		/**
		 * Single instance of the class
		 *
		 * @var \WOO_WCAUTHNET_Premium
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \WOO_WCAUTHNET_Premium
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
		 * @return \WOO_WCAUTHNET_Premium
		 * @since 1.0.0
		 */
		public function __construct() {
			// endpoint handling
			$this->add_endpoint();

			if ( version_compare( WC()->version, '2.6', '<' ) ) {
				add_action( 'woocommerce_after_my_account', array( $this, 'saved_cards_box' ) );
				add_action( 'template_redirect', array( $this, 'load_saved_cards_page' ) );

				// actions
				add_action( 'wp', array( $this, 'delete_card_handler' ) );
				add_action( 'wp', array( $this, 'set_default_card_handler' ) );
			}

			// token handling
			add_action( 'woocommerce_payment_token_deleted', array( $this, 'delete_token_handler' ), 10, 2 );
			add_action( 'woocommerce_payment_token_set_default', array( $this, 'set_default_token_handler' ), 10, 2 );

			// enqueue scripts and stuff
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		}

		/**
		 * Add the endpoint for the page in my account to manage the saved cards
		 *
		 * @since 1.0.0
		 */
		public function add_endpoint() {
			WC()->query->query_vars['authorize-saved-cards'] = get_option( 'woocommerce_myaccount_saved_cards_endpoint', 'authorize-saved-cards' );
		}

		/**
		 * Enqueue styles and scripts for my account section
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function enqueue() {
			$path = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'unminified/' : '';
			$suffix = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '' : '.min';

			wp_register_script( 'woo-wcauthnet-myaccount', WOO_WCAUTHNET_URL . 'js/' . $path . 'authorize-net-myaccount' . $suffix . '.js', array( 'jquery' ), WOO_WCAUTHNET_VERSION, true );
			wp_register_style( 'woo-wcauthnet', WOO_WCAUTHNET_URL . 'css/authorize-net.css', false, WOO_WCAUTHNET_VERSION );

			if( is_page( wc_get_page_id( 'myaccount' ) ) ){
				wp_enqueue_script( 'woo-wcauthnet-myaccount' );
				wp_enqueue_style( 'woo-wcauthnet' );
			}

			if( is_checkout() ){
				wp_enqueue_style( 'woo-wcauthnet' );
			}
		}

		/* === PRINT TEMPLATE METHODS === */

		/**
		 * If user is requesting saved card page, update the content to print correct template
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function load_saved_cards_page() {
			global $wp, $post;

			if ( WOO_WCAUTHNET_Credit_Card_Gateway_Premium()->cim_handling != 'yes' || ! is_page( wc_get_page_id( 'myaccount' ) ) || ! isset( $wp->query_vars['authorize-saved-cards'] ) ) {
				return;
			}

			$post->post_title = __( 'Saved cards', 'woo-woocommerce-authorizenet-payment-gateway' );
			$post->post_content = WC_Shortcodes::shortcode_wrapper( array( $this, 'saved_cards' ) );

			// hooks
			remove_filter( 'the_content', 'wpautop' );
			add_action( 'woocommerce_before_saved_cards', 'wc_print_notices', 10 );
		}
		
		/**
		 * Print complete template for saved cards
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function saved_cards_box() {
			if ( WOO_WCAUTHNET_Credit_Card_Gateway_Premium()->cim_handling != 'yes' ) {
				return;
			}
			?>

			<h2>
				<?php _e( 'Saved cards', 'woo-woocommerce-authorizenet-payment-gateway' ) ?>
				<a href="<?php echo wc_get_endpoint_url( 'authorize-saved-cards' ) ?>" class="edit" style="font-size:60%;font-weight:normal;float:right;"><?php _e( 'Manage cards', 'woo-woocommerce-authorizenet-payment-gateway' ) ?></a>
			</h2>

			<?php
			$this->saved_cards();
		}

		/**
		 * Include templates for saved cards table
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function saved_cards(){
			if( ! is_user_logged_in() ){
				return;
			}

			$payment_methods = get_user_meta( get_current_user_id(), '_authorize_net_payment_profiles', true );

			// Include payment form template
			$template_name = 'authorize-net-credit-card-table.php';
			$locations     = array(
				trailingslashit( WC()->template_path() ) . $template_name,
				$template_name
			);

			$template = locate_template( $locations );

			if ( ! $template ) {
				$template = WOO_WCAUTHNET_DIR . 'templates/' . $template_name;
			}

			include_once( $template );
		}

		/* === HANDLE CARD ACTIONS === */

		/**
		 * Delete card
		 *
		 * @return mixed
		 * @since 1.0.0
		 */
		public function delete_card_handler() {
			if (
				! isset( $_REQUEST['wcauthnet-action'] ) ||
				$_REQUEST['wcauthnet-action'] != 'delete-card' ||
				! isset( $_REQUEST['id'] ) ||
				! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wcauthnet-delete-card' )
			) {
				return;
			}

			$profile_id = get_user_meta( get_current_user_id(), '_authorize_net_profile_id', true );
			$payment_id = intval( $_REQUEST['id'] );

			$this->_delete_token( $profile_id, $payment_id );

			wc_add_notice( __( 'Card deleted successful.', 'woo-woocommerce-authorizenet-payment-gateway' ), 'success' );

			wp_redirect( get_permalink( wc_get_page_id( 'myaccount' ) ) );
			exit();
		}

		/**
		 * Set default card
		 *
		 * @return mixed
		 * @since 1.0.0
		 */
		public function set_default_card_handler() {
			if (
				! isset( $_REQUEST['wcauthnet-action'] ) ||
				$_REQUEST['wcauthnet-action'] != 'set-default-card' ||
				! isset( $_REQUEST['id'] ) ||
				! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wcauthnet-set-default-card' )
			) {
				return;
			}

			$profile_id = get_user_meta( get_current_user_id(), '_authorize_net_profile_id', true );
			$payment_id = intval( $_REQUEST['id'] );

			$this->_set_default_token( $profile_id, $payment_id );

			wc_add_notice( __( 'Card updated successful.', 'woo-woocommerce-authorizenet-payment-gateway' ), 'success' );

			wp_redirect( get_permalink( wc_get_page_id( 'myaccount' ) ) );
			exit();
		}

		/**
         * Delete token from Authorize.net when user removes it from the site
         *
		 * @param $token_id int Token id
         * @param $token \WC_Payment_Token_CC Token object
		 */
		public function delete_token_handler( $token_id, $token ) {
			$profile_id = get_user_meta( get_current_user_id(), '_authorize_net_profile_id', true );
			$payment_id = $token->get_token();

			if( ! $profile_id || ! $payment_id ){
			    return;
            }

			$this->_delete_token( $profile_id, $payment_id );
		}

		/**
         * Set default token on Authorize.net
         *
         * @param $token_id int Token id
		 * @param $token \WC_Payment_Token_CC Token object
		 */
		public function set_default_token_handler( $token_id, $token ) {
			$profile_id = get_user_meta( get_current_user_id(), '_authorize_net_profile_id', true );
			$payment_id = $token->get_token();

			if( ! $profile_id || ! $payment_id ){
				return;
			}

			$this->_set_default_token( $profile_id, $payment_id );
		}

		/**
         * Delete token via API
         *
         * @param $profile_id int User profile id on Authorize
         * @param $payment_id int Payment method ID
		 */
		protected function _delete_token( $profile_id, $payment_id ){
			$response = WOO_WCAUTHNET_Credit_Card_Gateway_Premium()->api->delete_customer_payment_profile( $profile_id, $payment_id );

			$payment_methods = get_user_meta( get_current_user_id(), '_authorize_net_payment_profiles', true );

			if( ! empty( $payment_methods ) ){
				$reset_default = false;
				if( $payment_methods[$payment_id]['default'] ){
					$reset_default = true;
				}

				unset( $payment_methods[$payment_id] );

				if( $reset_default && ! empty( $payment_methods ) ){
					foreach( $payment_methods as & $method ){
						$method['default'] = true;
						break;
					}
				}
			}

			update_user_meta( get_current_user_id(), '_authorize_net_payment_profiles', $payment_methods );
        }

        /**
         * Set default payment method via API
         *
         * @param $profile_id int User profile id on Authorize
         * @param $payment_id int Payment method ID
         */
        protected function  _set_default_token( $profile_id, $payment_id ){
	        $payment_methods = get_user_meta( get_current_user_id(), '_authorize_net_payment_profiles', true );

	        $masked_payment_data = new StdClass();
	        $masked_payment_data->type = 'credit_card';
	        $masked_payment_data->card_number = $payment_methods[ $payment_id ]['account_num'];
	        $masked_payment_data->expiration_date = 'XXXX';
	        $response = WOO_WCAUTHNET_Credit_Card_Gateway_Premium()->api->update_customer_payment_profile( null, $profile_id, $payment_id, $masked_payment_data, true );

	        $payment_methods = get_user_meta( get_current_user_id(), '_authorize_net_payment_profiles', true );

	        if( ! empty( $payment_methods ) ){
		        foreach( $payment_methods as $id => & $method ){
			        $method['default'] = ( $id == $payment_id );
		        }
	        }

	        update_user_meta( get_current_user_id(), '_authorize_net_payment_profiles', $payment_methods );
        }
	}
}

/**
 * Unique access to instance of WOO_WCAUTHNET_Premium class
 *
 * @return \WOO_WCAUTHNET_Premium
 * @since 1.0.0
 */
function WOO_WCAUTHNET_Premium(){
	return WOO_WCAUTHNET_Premium::get_instance();
}
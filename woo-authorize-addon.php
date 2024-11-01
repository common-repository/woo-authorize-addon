<?php
/**
 * Plugin Name:         Addon for Authorize and WooCommerce
 * Description:         Addon for Authorize and WooCommerce allows you to accept payments on your Woocommerce store. It accpets credit card payments and processes them securely with your merchant account.
 * Version:             2.0.4
 * WC requires at least:2.3
 * WC tested up to:     3.8.1
 * Requires at least:   4.0+
 * Tested up to:        5.3.2
 * Contributors:        wp_estatic
 * Author:              Estatic Infotech Pvt Ltd
 * Author URI:          http://estatic-infotech.com/
 * License:             GPLv3
 * @package WooCommerce
 * @category Woocommerce Payment Gateway WC_Authorizenet_Gateway
 */

if ( ! defined( 'WOO_WCAUTHNET' ) ) {
    define( 'WOO_WCAUTHNET', true );
}
if( ! defined( 'WOO_WCAUTHNET_VERSION' ) ){
    define( 'WOO_WCAUTHNET_VERSION', '2.0.4' );
}

if ( ! defined( 'WOO_WCAUTHNET_PREMIUM' ) ) {
    define( 'WOO_WCAUTHNET_PREMIUM', true );
}

if ( ! defined( 'WOO_WCAUTHNET_PREMIUM_INIT' ) ) {
    define( 'WOO_WCAUTHNET_PREMIUM_INIT', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'WOO_WCAUTHNET_URL' ) ) {
    define( 'WOO_WCAUTHNET_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'WOO_WCAUTHNET_DIR' ) ) {
    define( 'WOO_WCAUTHNET_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WOO_WCAUTHNET_INIT' ) ) {
    define( 'WOO_WCAUTHNET_INIT', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'WOO_WCAUTHNET_FILE' ) ) {
    define( 'WOO_WCAUTHNET_FILE', __FILE__ );
}

if ( ! defined( 'WOO_WCAUTHNET_INC' ) ) {
    define( 'WOO_WCAUTHNET_INC', WOO_WCAUTHNET_DIR . 'includes/' );
}

if ( ! defined( 'WOO_WCAUTHNET_SLUG' ) ) {
    define( 'WOO_WCAUTHNET_SLUG', 'woo-woocommerce-authorizenet-payment-gateway' );
}

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
/**
 * Check if WooCommerce is active
 **/
if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action('admin_notices', 'woo_wcauthnet_install_woocommerce_admin_notice');
}

if( ! function_exists( 'woo_wcauthnet_install_woocommerce_admin_notice' ) ) {
    function woo_wcauthnet_install_woocommerce_admin_notice() {
        ?>
        <div class="error">
            <p><?php echo sprintf( __( '%s is enabled but not effective. It requires WooCommerce in order to work.', 'woo-woocommerce-authorizenet-payment-gateway' ), 'WOO WooCommerce Authorize.net Payment Gateway' ); ?></p>
        </div>
        <?php
    }
}

add_action( 'plugins_loaded', 'wc_offline_gateway_init', 11 );

if( ! function_exists( 'wc_offline_gateway_init' ) ) {
    function wc_offline_gateway_init()
    {
        require_once( WOO_WCAUTHNET_DIR . 'includes/class-woo-wcauthnet-credit-card-gateway.php' );
        require_once( WOO_WCAUTHNET_DIR . 'includes/class-woo-wcauthnet-credit-card-gateway-premium.php' );
        require_once( WOO_WCAUTHNET_DIR . 'includes/class-woo-wcauthnet-cim-api.php' );
        require_once( WOO_WCAUTHNET_DIR . 'includes/class-woo-wcauthnet-premium.php' );
        require_once( WOO_WCAUTHNET_DIR . 'includes/class-woo-wcauthnet.php' );
        require_once( WOO_WCAUTHNET_DIR . 'includes/waa-woocommerce-compatibility.php' );
    }
}

if( ! function_exists( 'wc_offline_add_to_gateways' ) ) {
    function wc_offline_add_to_gateways($gateways)
    {
        $gateways[] = 'WOO_WCAUTHNET_Credit_Card_Gateway';
        return $gateways;
    }
}
add_filter( 'woocommerce_payment_gateways', 'wc_offline_add_to_gateways' );

/**
 *
 * @param type $links
 * @return type
 */
function authorizenet_woocommerce_settings_link($links) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=woo_wcauthnet_credit_card_gateway">' . __('Settings') . '</a>';
    array_push($links, $settings_link);
    return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'authorizenet_woocommerce_settings_link');
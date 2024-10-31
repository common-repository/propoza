<?php
/**
 * Propoza
 *
 * An awesome plugin that does awesome things
 *
 * @package   Propoza
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2018 Propoza
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Propoza
 * Plugin URI:        https://propoza.com
 * Description:       Propoza adds quotation functionality to your webshop, generating more leads & more orders!
 * Version:           2.1.2
 * Author:            Propoza
 * Text Domain:       propoza
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI:
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	define( 'PROPOZA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
	function plugins_loaded() {
		// Checks if WooCommerce is installed.
		if ( class_exists( 'WC_Integration' ) ) {
			// Include our integration class.
			require_once plugin_dir_path( __FILE__ ) . 'admin/includes/class-wc-propoza-integration.php';
			require_once plugin_dir_path( __FILE__ ) . 'public/class-propoza.php';
			require_once plugin_dir_path( __FILE__ ) . 'api/class-rest-api-init.php';

			new WC_Propoza_Rest_API();
			Propoza::get_instance()->init();
		}
	}

	add_action( 'plugins_loaded', 'plugins_loaded' );

} else {
	function woocommerce_not_active_notice() {
		echo '<div class="error"><p><b>WooCommerce</b> is not active/installed. Please activate/install <b>WooCommerce</b> to make use of <b>Propoza</b>!</p></div>';
	}

	add_action( 'admin_notices', 'woocommerce_not_active_notice' );
}


/**
 * Main instance of Propoza.
 *
 * Returns the main instance of Propoza
 *
 * @since  2.0
 * @return Propoza
 */
function propoza() {
    return Propoza::get_instance();
}

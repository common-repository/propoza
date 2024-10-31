<?php
/**
 * Propoza
 *
 * @package   Propoza_Frontend
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 */

/**
 * Propoza_Frontend class. This class should ideally be used to work with the
 * frontend side of the WordPress site.
 *
 * @package Propoza_Frontend
 * @author  Propoza <support@propoza.com>
 */
class Propoza_Frontend {
	/**
	 * Instance of this class.
	 *
	 * @since    1.0.6
	 *
	 * @var      object
	 */
	protected static $instance = null;

	protected function __construct() {

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.6
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin by setting localization and loading frontend scripts
	 * and styles.
	 *
	 * @since     1.0.6
	 */
	public function init() {
		if ( 'yes' == WC_Propoza_Integration::option( 'enabled' ) ) {
			require_once plugin_dir_path( __FILE__ ) . '/includes/class-propoza-quote-request.php';
			require_once plugin_dir_path( __FILE__ ) . '/includes/class-propoza-checkout.php';
			Propoza_Checkout::get_instance()->init();
			Propoza_Quote_Request::get_instance()->init();
		}
	}
}

<?php
/**
 * Propoza
 *
 * @package   Propoza
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 */

/**
 * Propoza class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-propoza-admin.php`
 *
 * @package Propoza
 * @author  Propoza <support@propoza.com>
 */
class Propoza {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.6
	 *
	 * @var     string
	 */
	const VERSION = '2.0';

    /**
     * @var Propoza_Quote the quote of the current instance.
     */
	public $propoza_quote;
	/**
	 * Instance of this class.
	 *
	 * @since    1.0.6
	 *
	 * @var      object
	 */
	protected static $instance = null;
	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.6
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'propoza';

	private function __construct() {

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.6
	 *
	 * @return    Propoza    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.6
	 *
	 * @param    boolean $network_wide True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}
		} else {
			self::single_activate();
		}

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.6
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		return $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE archived = '0' AND spam = '0' AND deleted = '0'" );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.6
	 */
	private static function single_activate() {
		add_action( 'quote', array( 'Propoza_Quote_Request', 'quote_post_status' ) );
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.6
	 *
	 * @param    boolean $network_wide True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}
		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.6
	 */
	private static function single_deactivate() {

	}

	/**
	 * @return string
	 */
	public static function get_sign_up_propoza_url() {
		return sprintf( '%s%s/accounts/create?client=woocommerce', self::get_protocol(), self::get_propoza_url() );
	}

	/**
	 * @return string
	 */
	public static function get_protocol() {
		return 'https://';
	}

	/**
	 * @return string
	 */
	public static function get_propoza_url() {
		return 'propoza.com';
	}

	/**
	 * @param null $sub_domain
	 *
	 * @return string
	 */
	public static function get_connection_test_url( $sub_domain = null ) {
		return sprintf( '%s/api/WooCommerceQuotes/testConnection.json', self::get_dashboard_propoza_url( $sub_domain ) );
	}

	/**
	 * @param null $sub_domain
	 *
	 * @return string
	 */
	public static function get_dashboard_propoza_url( $sub_domain = null ) {
		if ( empty( $sub_domain ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/includes/class-wc-propoza-integration.php';
			$sub_domain = WC_Propoza_Integration::option( 'web_address', null );
		}

		return sprintf( '%s%s.%s', self::get_protocol(), $sub_domain, self::get_propoza_url() );
	}

    /**
     * @param null $sub_domain
     *
     * @return string
     */
    public static function get_authenticate_url( $sub_domain = null ) {
        $url = get_site_url(null, null, 'https');

        return sprintf(
            '%s/platform_authentication/authorize/stepOne/woocommerce?domain=%s&user_id=%s',
            self::get_dashboard_propoza_url( $sub_domain ),
            urlencode($url),
            get_current_user_id()
        );
    }

	/**
	 * @param null $sub_domain
	 *
	 * @return string
	 */
	public static function get_dashboard_propoza_token_url( $sub_domain = null ) {
		return sprintf( '%s/api/MerchantsApi/token.json', self::get_dashboard_propoza_url( $sub_domain ) );
	}

	/**
	 * @param null $sub_domain
	 * @param null $token
	 *
	 * @return string
	 */
	public static function get_dashboard_propoza_login_url( $sub_domain = null, $token = null ) {
		if ( isset( $token ) ) {
			$token = sprintf( '?_token=%s', $token );
		}

		return sprintf( '%s/login%s', self::get_dashboard_propoza_url( $sub_domain ), $token );
	}

	/**
	 * @return string
	 */
	public static function get_quote_request_form_url() {
		return sprintf( '%s/api/WooCommerceQuotes/quoteRequestForm', self::get_dashboard_propoza_url() );
	}

	/**
	 * @return string
	 */
	public static function get_request_quote_assets_url() {
		return sprintf( '%s/api/WooCommerceQuotes/requestQuoteAssets', self::get_dashboard_propoza_url() );
	}

    /**
     * @return string
     */
    public static function get_request_button_url() {
        return sprintf( '%s/api/WooCommerceQuotes/requestButton', self::get_dashboard_propoza_url() );
    }

    /**
     * @return string
     */
    public static function get_request_minicart_button_url() {
        return sprintf( '%s/api/WooCommerceQuotes/requestMiniCartButton', self::get_dashboard_propoza_url() );
    }

    /**
     * @return string of the quick quote form api call.
     */
    public static function get_request_quick_quote_url() {
        return sprintf( '%s/api/WooCommerceQuotes/requestQuickQuote', self::get_dashboard_propoza_url() );
    }

    /**
     * @return string of the quick quote button api call.
     */
    public static function get_request_quick_quote_button_url() {
        return sprintf( '%s/api/WooCommerceQuotes/requestQuickQuoteButton', self::get_dashboard_propoza_url() );
    }
	/**
	 * @param $string
	 *
	 * @return false|int
	 */
	public static function is_valid_api_key( $string ) {
		return preg_match( '/^[A-Za-z0-9+\/]{226}==$/', $string );
	}

	/**
	 * @return bool
	 */
	public static function is_request_authorized() {
		return true;
	}

	/**
	 * @return string
	 */
	public static function get_quote_ordered_url() {
		return sprintf( '%s/api/WooCommerceQuotes/edit.json', self::get_dashboard_propoza_url() );
	}

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.6
	 */
	public function init() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-propoza-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-propoza-frontend.php';

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			Propoza_Admin::get_instance()->init();
			Propoza_Frontend::get_instance()->init();
		}

		if ( is_admin() ) {
			Propoza_Admin::get_instance()->init();
		} else {
			Propoza_Frontend::get_instance()->init();
            // Instantiate the Propoza Quote instance.
            $this->propoza_quote = new Propoza_Quote();
		}
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		/*
		 * Register hooks that are fired when the plugin is activated or deactivated.
		 * When the plugin is deleted, the uninstall.php file is loaded.
		 */
		register_activation_hook( __FILE__, array(
			'Propoza',
			'activate',
		) );
		register_deactivation_hook( __FILE__, array(
			'Propoza',
			'deactivate',
		) );
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.6
	 *
	 * @return string   Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.6
	 *
	 * @param    int $blog_id ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 != did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.6
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}
}

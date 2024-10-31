<?php
/**
 * Propoza_Quote_Request
 *
 * @package   Propoza
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 */

/**
 * Propoza_Quote_Request class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-propoza-admin.php`
 *
 * @package Propoza
 * @author  Propoza <support@propoza.com>
 */
class Propoza_Quote_Request {
	/**
	 * Instance of this class.
	 *
	 * @since    1.0.6
	 *
	 * @var      object
	 */
	protected static $instance = null;

    /**
     * Cart to set back after quick quote has been added.
     *
     * @var     WC_Cart
     */
    private $original_cart;
	/**
	 * Propoza_Quote_Request constructor.
	 */
	protected function __construct() {

	}

	/**
	 *
	 */
	public function init() {
		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array(
			$this,
			'enqueue_styles',
		) );

		add_action( 'wp_loaded', array(
		    $this,
            'quick_quote_item',
        ), 0 );

        add_action( 'woocommerce_after_add_to_cart_form', array(
            $this,
            'reset_cart',
        ), 1000 );

        add_action( 'wp_loaded', array(
            $this,
            'get_request_quick_quote_form_html'
        ), 100 );
        add_action( 'woocommerce_after_add_to_cart_button', array(
            $this,
            'add_propoza_quick_quote',
        ), 1 );

		add_action( 'wp_head', array(
			$this,
			'add_propoza_assets',
		), 1000 );

		add_action( 'woocommerce_widget_shopping_cart_buttons', array(
			$this,
			'add_request_quote_minicart_button',
		), 30 );

		add_action( 'wp_enqueue_scripts', array(
			$this,
			'enqueue_scripts',
		) );

		add_action( 'wp_ajax_get_request_quote_form_html', array(
			$this,
			'get_request_quote_form_html',
		) );

		add_action( 'wp_ajax_request_quote_form_submit', array(
			$this,
			'request_quote_form_submit',
		) );
		add_action( 'wp_ajax_nopriv_get_request_quote_form_html', array(
			$this,
			'get_request_quote_form_html',
		) );
		add_action( 'wp_ajax_nopriv_request_quote_form_submit', array(
			$this,
			'request_quote_form_submit',
		) );
		add_action( 'woocommerce_proceed_to_checkout', array(
			$this,
			'add_request_quote_button',
		), 1000 );

        add_action( 'woocommerce_before_calculate_totals', array(
            $this,
            'before_calculate_totals',
        ) );

		require_once plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'admin/includes/class-wc-propoza-integration.php';
		require_once plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'includes/class-propoza-quote.php';
		require_once plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'includes/class-propoza-coupon.php';
	}

	/**
	 * Adds the Propoza javascript and css to the webpage if Propoza is configured
	 */
	public function add_propoza_assets() {
		if ( self::get_instance()->configured() ) {
			$response = wp_remote_get( Propoza::get_request_quote_assets_url(), array(
				'headers'   => array(
					'Content-Type'  => 'text/json',
					'Authorization' => 'Basic ' . WC_Propoza_Integration::option( 'api_key', null ),
				),
				'sslverify' => ! WP_DEBUG,
			) );

			if ( ! is_wp_error( $response ) ) {
				if ( 200 == $response['response']['code'] ) {
					echo $response['body'];
				}
			} else {
				echo $response->get_error_message();
			}
		}
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
	 * Adds the quote_button view to the webpage
	 */
	public function add_request_quote_button() {
		if ( ! $this->has_propoza_coupon() ) {
            if ( self::get_instance()->configured() ) {
                $response = wp_remote_get( Propoza::get_request_button_url(), array(
                    'headers'   => array(
                        'Content-Type'  => 'text/json',
                        'Authorization' => 'Basic ' . WC_Propoza_Integration::option( 'api_key', null ),
                    ),
                    'sslverify' => ! WP_DEBUG,
                ) );

                if ( ! is_wp_error( $response ) ) {
                    if ( 200 == $response['response']['code'] ) {
                        echo $response['body'];
                    }
                } else {
                    echo $response->get_error_message();
                }
            }
		}
	}

    /**
     * Adds the quote_button view to the minicart
     */
    public function add_request_quote_minicart_button() {
        if ( ! $this->has_propoza_coupon() ) {
            if ( self::get_instance()->configured() ) {
                $response = wp_remote_get( Propoza::get_request_minicart_button_url(), array(
                    'headers'   => array(
                        'Content-Type'  => 'text/json',
                        'Authorization' => 'Basic ' . WC_Propoza_Integration::option( 'api_key', null ),
                    ),
                    'sslverify' => ! WP_DEBUG,
                ) );

                if ( ! is_wp_error( $response ) ) {
                    if ( 200 == $response['response']['code'] ) {
                        echo $response['body'];
                    }
                } else {
                    echo $response->get_error_message();
                }
            }
        }
    }
    /**
     * Adds Propoza quick quote button behind the add to cart button.
     */
	public function add_propoza_quick_quote() {
        global $product;
        $data = ['id' => $product->get_id()];
        if ( self::get_instance()->configured() ) {
            $response = wp_remote_get( Propoza::get_request_quick_quote_button_url(), array(
                'method'    => 'POST',
                'body' => json_encode($data),
                'headers'   => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Basic ' . WC_Propoza_Integration::option( 'api_key', null ),
                ),
                'sslverify' => ! WP_DEBUG,
            ) );

            if ( ! is_wp_error( $response ) ) {
                if ( 200 == $response['response']['code'] ) {
                    echo $response['body'];
                }
            } else {
                echo $response->get_error_message();
            }
        }
    }

	/**
	 * @return bool
	 * @throws WC_Data_Exception
	 */
	public function has_propoza_coupon() {
		$has_propoza_coupon = false;
		foreach ( wc()->cart->get_applied_coupons() as $coupon ) {
			$propoza_coupon = new Propoza_Coupon();
			$propoza_coupon->load_by_id( $coupon );
			$has_propoza_coupon = $propoza_coupon->is_propoza_proposal();
		}

		return $has_propoza_coupon;
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.6
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.6
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( Propoza::get_instance()->get_plugin_slug() . '-plugin-script', plugins_url( 'assets/js/propoza-quote-request.js', dirname( __FILE__ ) ), array( 'jquery' ), Propoza::VERSION );
		wp_localize_script( Propoza::get_instance()->get_plugin_slug() . '-plugin-script', Propoza::get_instance()->get_plugin_slug() . '_' . 'request', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}


	/**
	 *
	 */
	public function get_request_quote_form_html() {
		$propoza_quote = new Propoza_Quote();
		$propoza_quote->load_products_from_cart();
		$propoza_quote->save();
		$propoza_quote->get_prepared_quote();
		$response = wp_remote_post( Propoza::get_quote_request_form_url(), array(
			'method'    => 'POST',
			'body'      => json_encode( $propoza_quote->get_prepared_quote() ),
			'headers'   => array(
				'Content-Type'  => 'text/json',
				'Authorization' => 'Basic ' . WC_Propoza_Integration::option( 'api_key', null ),
			),
			'sslverify' => ! WP_DEBUG,
		) );

		if ( ! is_wp_error( $response ) ) {
			if ( 200 == $response['response']['code'] ) {
				echo $response['body'];
			}
		} else {
			echo $response->get_error_message();
		}

		exit;
	}

    /**
     * Resets the current Propoza cart and sets it as the current WooCommerce cart. So WooCommerce it's add_to_cart function will do the job of adding the item.
     */
	public function quick_quote_item() {
	    if ( empty($_REQUEST['quick-quote']) ) {
	        return;
        }
        $this->original_cart = wc()->cart;
        $cartcontents = propoza()->propoza_quote->get_cart_contents();
        foreach ($cartcontents as $product) {
            propoza()->propoza_quote->remove_cart_item($product['key']);
        }

        $_REQUEST['add-to-cart'] = $_REQUEST['quick-quote'];
        wc()->cart = propoza()->propoza_quote;
    }

    /**
     * Sets the propoza_quote after add_to_cart has completed. Sets back the wc cart to wc.
     */
    public function reset_cart() {
        if ( empty($_REQUEST['quick-quote']) ) {
            return;
        }
        propoza()->propoza_quote = wc()->cart;
        wc()->cart = $this->original_cart;
    }

    /**
     * Creates a quote for only the current product and its quantity and gets the quick quote form.
     * @throws Exception
     */
    public function get_request_quick_quote_form_html() {
        if ( empty($_REQUEST['quick-quote'])) {
            return;
        }
        if ( sizeof(propoza()->propoza_quote->get_cart_contents()) < 1 ) {
            return;
        }
        wc_clear_notices();
        propoza()->propoza_quote->load_products_for_quick_quote();
        propoza()->propoza_quote->save();
        propoza()->propoza_quote->add_quick_quote_image($_REQUEST['quick-quote']);
        propoza()->propoza_quote->calculate_totals();

        $response = wp_remote_post( Propoza::get_request_quick_quote_url(), array(
            'method'    => 'POST',
            'body'      => json_encode( propoza()->propoza_quote->get_prepared_quote() ),
            'headers'   => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . WC_Propoza_Integration::option( 'api_key', null ),
            ),
            'sslverify' => ! WP_DEBUG,
        ) );

        if ( ! is_wp_error( $response ) ) {
            if ( 200 == $response['response']['code'] ) {
                   echo '<div id="quick-quote" style="display: none;">' . $response['body'] . '</div>';
            }
        } else {
            echo $response->get_error_message();
        }
    }

	/**
	 *
	 */
	public function request_quote_form_submit() {
		$response = wp_remote_post( $_POST['form-action'], array(
			'method'    => 'POST',
			'timeout'   => 20,
			'body'      => json_encode( $_POST['data'] ),
			'headers'   => array(
				'Content-Type'  => 'text/json',
				'Authorization' => 'Basic ' . WC_Propoza_Integration::option( 'api_key', null ),
			),
			'sslverify' => ! WP_DEBUG,
		) );

		if ( ! is_wp_error( $response ) ) {
			if ( 200 == $response['response']['code'] ) {
				echo $response['body'];
			}
		} else {
			echo $response->get_error_message();
		}
		exit;
	}


	/**
	 * @return bool
	 */
	public function configured() {
		$api_key    = WC_Propoza_Integration::option( 'api_key', null );
		$sub_domain = WC_Propoza_Integration::option( 'web_address', null );

		return ( ! empty( $api_key ) && ! empty( $sub_domain ) ) && ! self::get_instance()->has_propoza_coupon();
	}

    /**
     * Check for proposal price for adding to cart
     *
     * @param $_cart
     */
    function before_calculate_totals( $_cart ){
        // loop through the cart_contents
        foreach ( $_cart->cart_contents as $cart_item_key => &$item ) {
            if (isset($item['proposal_price'])) {
                $item['data']->set_price($item['proposal_price']);
            }
        }
    }
}

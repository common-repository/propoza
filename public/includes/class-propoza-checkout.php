<?php
/**
 * Propoza_Checkout
 *
 * @package   Propoza
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 */

/**
 * Propoza_Checkout class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-propoza-admin.php`
 *
 * @package Propoza
 * @author  Propoza <support@propoza.com>
 */
class Propoza_Checkout {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.6
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Propoza_Checkout constructor.
	 */
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
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.6
	 */
	public function init() {
		add_action( 'init', array( $this, 'propoza_checkout_rewrites_init' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'template_redirect', array( $this, 'propoza_checkout_template_redirect_intercept' ) );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'order_placed' ), 10, 1 );
	}

	/**
	 * @param $order_id
	 *
	 * @throws WC_Data_Exception
	 */
	public function order_placed( $order_id ) {
	    if ($quoteId = WC()->session->get('propoza_quote_id')) {
	        $this->updatePropozaQuoteOrdered($quoteId);
	        return;
        }

		$order = new WC_Order( $order_id );
		foreach ( $order->get_used_coupons() as $coupon_code ) {
			$propoza_coupon = new Propoza_Coupon();
			$propoza_coupon->load_by_id( $coupon_code );
			if ( $propoza_coupon->is_propoza_proposal() ) {
                $this->updatePropozaQuoteOrdered($propoza_coupon->get_propoza_quote_id());
			}
		}
	}

    /**
     * Update the Propoza quote to ordered
     *
     * @param $quoteId
     */
	protected function updatePropozaQuoteOrdered($quoteId)
    {
        $response = wp_remote_post( Propoza::get_quote_ordered_url(), array(
            'method'    => 'POST',
            'body'      => json_encode( array(
                'Quote' => array(
                    'id'             => $quoteId,
                    'main_status_id' => 2,
                    'sub_status_id'  => 5,
                ),
            ) ),
            'headers'   => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . WC_Propoza_Integration::option( 'api_key', null ),
            ),
            'sslverify' => ! WP_DEBUG,
        ) );
        if ( ! is_wp_error( $response ) ) {
            if ( 200 == $response['response']['code'] ) {
                $propoza_quote = new Propoza_Quote( $quoteId );
                $propoza_quote->delete_proposal_quote_clones( $quoteId );
            }
        }
    }

	/**
	 *
	 */
	public function plugins_loaded() {
		require_once plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'includes/class-propoza-quote.php';
		require_once plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'includes/class-propoza-coupon.php';
	}


	/**
	 * @throws Exception
	 */
	public function propoza_checkout_template_redirect_intercept() {
		global $wp_query;

        $this->load_session_quote();
		if ( $wp_query->get( 'propoza' ) && $wp_query->get( 'checkout' ) && $wp_query->get( 'quote_id' ) ) {
			$this->request_checkout( $wp_query->get( 'quote_id' ) );
		}
		if ( $wp_query->get( 'propoza' ) && $wp_query->get( 'add_proposal' ) ) {
			$this->add_proposal_coupon();
		}
	}

	/**
	 * @param $quote_id
	 *
	 * @return WP_Error
	 * @throws Exception
	 */
	private function request_checkout( $quote_id ) {
		if ( Propoza::is_request_authorized() ) {
			$woocommerce                      = wc();
            $woocommerce->cart->propoza_quote = propoza()->propoza_quote;
            $woocommerce->cart->propoza_quote->load( $quote_id );
            if ( ! $woocommerce->cart->propoza_quote->get_id() ) {
                return new WP_Error( __( 'No quote found: #' . $quote_id, 'propoza' ) );
            }
            $woocommerce->cart->empty_cart();
            $woocommerce->cart->remove_coupons();

            if ( isset( $woocommerce->cart->propoza_quote ) && sizeof( $woocommerce->cart->propoza_quote->get_products() ) > 0) {
                $woocommerce->cart->set_cart_contents($woocommerce->cart->propoza_quote->get_products());
                $woocommerce->cart->calculate_totals();
                $propoza_coupon = new Propoza_Coupon($woocommerce->cart->propoza_quote->get_propoza_quote_id());
                add_filter(
                    'woocommerce_coupons_enabled',
                    array(
                        $this,
                        'wc_coupons_enabled',
                    ),
                    1000
                );

                try {
                    if ($woocommerce->cart->apply_coupon($propoza_coupon->get_code())) {
                        wc_add_notice(
                            __(
                                '<b>Please note:</b> <i>Changing the cart contents will invalidate the proposal price.</i>'
                            ),
                            'notice'
                        );
                    } elseif (!count($woocommerce->cart->get_applied_coupons()) > 0) {
                        wc_add_notice(
                            __(
                                '<b>Your proposal discount could not be applied.<br> Please contact the store-owner.</b>'
                            ),
                            'error'
                        );
                    }
                } catch (WC_Data_Exception $e) {
                    wc_add_notice(
                        __('<b>Your proposal discount could not be applied.<br> Please contact the store-owner.</b>' . $e->getMessage() ),
                        'error'
                    );
                }
            } else {
                wc_add_notice(
                    __(
                        '<b>Your proposal discount could not be applied.<br> The requested products could not be found.</b>'
                    ),
                    'error'
                );
            }
			wp_redirect( wc_get_cart_url() );
			exit;
		} else {
			throw new Exception( __( 'You are not authorized to access this function', 'propoza' ) );
		}
	}

	/**
	 * @throws Exception
	 */
	private function add_proposal_coupon() {
		if ( Propoza::is_request_authorized() ) {
			$post             = json_decode( file_get_contents( 'php://input' ), true );
			$propoza_quote_id = $post['id'];
			$quote_id         = $post['shop_quote_id'];
			$proposal_total   = $post['total_proposal_price'];
			$original_total   = $post['total_original_price'];
			if ( isset( $quote_id, $proposal_total, $original_total, $propoza_quote_id ) ) {

				//Load the original quote by stored id in Propoza
				$quote = new Propoza_Quote();
				$quote->load($quote_id);

				if ( null == $quote->get_id() ) {
					throw new Exception( __( 'No quote found with id: #' . $quote_id, 'propoza' ) );
				}

				$quote->set_propoza_quote_id( $propoza_quote_id );
				$quote->save();

				$cloned_quote = $quote->clone_quote();

				if ( null == $cloned_quote->get_id() ) {
					throw new Exception( __( 'Quote could not be cloned: #' . $quote_id, 'propoza' ) );
				}
				$propoza_coupon = new Propoza_Coupon( $propoza_quote_id );
				if ( ! $propoza_coupon->get_code() ) {
					$coupon_id = $propoza_coupon->create_proposal_coupon(
						$cloned_quote,
						$original_total,
						$proposal_total
					);
				} else {
					$cloned_quote->delete_proposal_quote_clones( $propoza_quote_id, array(
						(int) $cloned_quote->get_id(),
						(int) $quote->get_id(),
					) );
					$coupon_id = $propoza_coupon->update_propoza_coupon(
						$cloned_quote,
						$original_total,
						$proposal_total
					);
				}
				if ( null == $coupon_id ) {
					$propoza_coupon->create_proposal_coupon(
						$cloned_quote,
						$original_total,
						$proposal_total
					);
				}
				header( 'Content-Type: application/json' );
				echo json_encode( array( 'quote_id' => $cloned_quote->get_id() ) );
				die;
			}
		} else {
			throw new Exception( __( 'You are not authorized to access this function', 'propoza' ) );
		}
	}

	/**
	 *
	 * @return bool
	 */
	public function wc_coupons_enabled( ) {
		return 'yes' == get_option( 'woocommerce_enable_coupons' ) || !is_null( propoza()->propoza_quote->get_propoza_quote_id() );
	}

	/**
	 *
	 */
	public function propoza_checkout_rewrites_init() {
		add_rewrite_tag( '%propoza%', '([0-9]+)' );
		add_rewrite_tag( '%checkout%', '([0-9]+)' );
		add_rewrite_tag( '%quote_id%', '([0-9]+)' );
		add_rewrite_tag( '%add_proposal%', '([0-9]+)' );
	}

    /**
     * Load the quote from session
     */
	protected function load_session_quote()
    {
        if (isset($_GET['cart_session_name'], $_GET['cart_session_value'])) {
            if (is_user_logged_in()) {
                wp_logout();
            }

            $_COOKIE[$_GET['cart_session_name']] = $_GET['cart_session_value'];

            unset(WC()->session);
            WC()->init();
            WC()->session->set_customer_session_cookie(true);
            WC()->session->set('propoza_quote_id', $_GET['quote_id']);

            $this->apply_customer_checkout_data();
            wp_redirect( wc_get_checkout_url() );
            exit;
        }
    }

    /**
     * Apply the customer checkout data to the post
     * The value will be used on the checkout
     */
    protected function apply_customer_checkout_data()
    {
        $checkoutData = WC()->session->get('customer_checkout_data');

        if (is_array($checkoutData)) {
            $this->update_session($checkoutData);
        }
    }

    /**
     * Set address field for customer.
     *
     * @since 3.0.7
     * @param $field string to update
     * @param $key
     * @param $data array of data to get the value from
     */
    protected function set_customer_address_fields( $field, $key, $data ) {
        if ( isset( $data[ "billing_{$field}" ] ) ) {
            WC()->customer->{"set_billing_{$field}"}( $data[ "billing_{$field}" ] );

            if (!($field == 'phone' || $field == 'email')) {
                WC()->customer->{"set_shipping_{$field}"}( $data[ "billing_{$field}" ] );
            }
        }

        if ( isset( $data[ "shipping_{$field}" ] ) && !($field == 'phone' || $field == 'email')) {
            WC()->customer->{"set_shipping_{$field}"}( $data[ "shipping_{$field}" ] );
        }
    }

    /**
     * Update customer and session data from the posted checkout data.
     *
     * @since  3.0.0
     * @param  array $data
     * @throws WC_Data_Exception
     */
    protected function update_session( $data )
    {
        // Update both shipping and billing to the passed billing address first if set.
        $address_fields = array(
            'address_1',
            'address_2',
            'city',
            'postcode',
            'state',
            'country',
            'phone',
            'email',
            'first_name',
            'last_name',
        );

        array_walk($address_fields, array($this, 'set_customer_address_fields'), $data);
        WC()->customer->save();
    }
}

<?php
/**
 * Propoza_Quote_Session
 *
 * @package   Propoza
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 */

/**
 * Class Propoza_Quote_Session This class is not an extension of the WC_Cart_Session because of action namespace issues.
 */
class Propoza_Quote_Session
{
    CONST ItemsInQuoteCookie = 'woocommerce_propoza_items_in_quote';

    CONST QuoteHashCookie = 'woocommerce_propoza_quote_hash';

    /**
     * Reference to quote object.
     *
     * @since 2.0
     * @var Propoza_Quote
     */
    private $quote;
    /**
     * Sets up the items provided, and calculate totals.
     *
     * @since 2.0
     * @param Propoza_Quote $quote Quote object to calculate totals for.
     */
    public function __construct( &$quote ) {
        $this->quote = $quote;
    }

    /**
     * Register methods for this object on the appropriate WordPress hooks.
     */
    public function init() {
        add_action( 'woocommerce_cart_emptied', array( $this, 'destroy_quote_session' ) );
        add_action( 'wp', array( $this, 'maybe_set_quote_cookies' ), 100 );
        add_action( 'shutdown', array( $this, 'maybe_set_quote_cookies' ), 1 );
        add_action( 'woocommerce_add_to_cart', array( $this, 'maybe_set_quote_cookies' ) );
        add_action( 'woocommerce_after_calculate_totals', array( $this, 'set_session' ) );
        add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'set_session' ) );
    }

    /**
     * Get the cart data from the PHP session and store it in class variables.
     *
     * @since 3.2.0
     */
    public function get_quote_from_session()
    {
        // Flag to indicate the stored cart should be updated.
        $update_cart_session = false;
        $totals = WC()->session->get('quote_totals', null);
        $quote = WC()->session->get('propoza_quote', null);

        $this->quote->set_totals($totals);

        $saved_cart = get_user_meta(
            get_current_user_id(),
            '_woocommerce_persistent_cart_' . get_current_blog_id(),
            true
        );

        if (is_null($quote) && $saved_cart) {
            $quote = $saved_cart['cart'];
            $update_cart_session = true;
        } elseif (is_null($quote)) {
            $quote = array();
        } elseif (is_array($quote) && $saved_cart) {
            $quote = array_merge($saved_cart['cart'], $quote);
            $update_cart_session = true;
        }

        if (is_array($quote)) {
            $quote_contents = array();

            // Prime caches to reduce future queries.
            if (is_callable('_prime_post_caches')) {
                _prime_post_caches(wp_list_pluck($quote, 'product_id'));
            }

            foreach ($quote as $key => $values) {
                $product = wc_get_product($values['variation_id'] ? $values['variation_id'] : $values['product_id']);

                if (!is_customize_preview() && 'customize-preview' === $key) {
                    continue;
                }

                if (!empty($product) && $product->exists() && $values['quantity'] > 0) {

                    if (!$product->is_purchasable()) {
                        $update_cart_session = true;
                        /* translators: %s: product name */
                        wc_add_notice(
                            sprintf(
                                __(
                                    '%s has been removed from your quote because it can no longer be purchased. Please contact us if you need assistance.',
                                    'woocommerce'
                                ),
                                $product->get_name()
                            ),
                            'error'
                        );

                    } elseif (!empty($values['data_hash']) && !hash_equals(
                            $values['data_hash'],
                            wc_get_cart_item_data_hash($product)
                        )) { // phpcs:ignore PHPCompatibility.PHP.NewFunctions.hash_equalsFound
                        $update_cart_session = true;
                        /* translators: %1$s: product name. %2$s product permalink */
                        wc_add_notice(
                            sprintf(
                                __(
                                    '%1$s has been removed from your quote because it has since been modified. You can add it back to your quote <a href="%2$s">here</a>.',
                                    'woocommerce'
                                ),
                                $product->get_name(),
                                $product->get_permalink()
                            ),
                            'notice'
                        );

                    } else {
                        // Put session data into array. Run through filter so other plugins can load their own session data.
                        $session_data = array_merge(
                            $values,
                            array(
                                'data' => $product,
                            )
                        );
                        $quote_contents[$key] = apply_filters(
                            'woocommerce_get_cart_item_from_session',
                            $session_data,
                            $values,
                            $key
                        );

                        // Add to cart right away so the product is visible in woocommerce_get_cart_item_from_session hook.
                        $this->quote->set_cart_contents($quote_contents);
                    }
                }
            }
        }

        if ($update_cart_session || is_null(WC()->session->get('quote_totals', null))) {
            WC()->session->set('propoza_quote', $this->get_quote_for_session());
            $this->quote->calculate_totals();
        }
    }

    /**
     * Destroy quote session data.
     *
     * @since 2.0
     */
    public function destroy_quote_session() {
        WC()->session->set( 'propoza_quote', null);
        WC()->session->set( 'quote_totals', null );
    }

    /**
     * Will set quote cookies if needed and when possible.
     *
     * @since 2.0
     */
    public function maybe_set_quote_cookies() {
        if ( ! headers_sent() && did_action( 'wp_loaded' ) ) {
            if ( ! $this->quote->is_empty() ) {
                $this->set_quote_cookies( true );
            } elseif ( isset( $_COOKIE[Propoza_Quote_Session::ItemsInQuoteCookie] ) ) {
                $this->set_quote_cookies( false );
            }
        }
    }

    /**
     * Sets the php session data for the cart and coupons.
     */
    public function set_session() {
        WC()->session->set( 'propoza_quote', $this->get_quote_for_session() );
        WC()->session->set( 'quote_totals', $this->quote->get_totals() );
    }

    /**
     * Returns the contents of the quote in an array without the 'data' element.
     *
     * @return array contents of the quote
     */
    public function get_quote_for_session()
    {
        $cart_session = array();

        foreach ($this->quote->get_cart(true) as $key => $values) {
            $cart_session[$key] = $values;
            unset($cart_session[$key]['data']); // Unset product object.
        }
        return $cart_session;
    }

    /**
     * Set cart hash cookie and items in cart.
     *
     * @access private
     * @param bool $set Should cookies be set (true) or unset.
     */
    private function set_quote_cookies( $set = true ) {
        if ( $set ) {
            wc_setcookie( Propoza_Quote_Session::ItemsInQuoteCookie, 1 );
            wc_setcookie( Propoza_Quote_Session::QuoteHashCookie, md5( wp_json_encode( $this->get_quote_for_session() ) ) );
        } elseif ( isset( $_COOKIE[Propoza_Quote_Session::ItemsInQuoteCookie] ) ) {
            wc_setcookie( Propoza_Quote_Session::ItemsInQuoteCookie, 0, time() - HOUR_IN_SECONDS );
            wc_setcookie( Propoza_Quote_Session::QuoteHashCookie, '', time() - HOUR_IN_SECONDS );
        }
    }
}
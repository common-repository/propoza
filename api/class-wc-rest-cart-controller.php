<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API Cart controller class.
 *
 * @package WooCommerce Cart REST API/API
 */
class WC_REST_Propoza_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v2';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'propoza_cart';

    /**
     * Register the routes for cart.
     */
    public function register_routes()
    {
        // View Cart - wc/v2/cart (GET)
        register_rest_route($this->namespace, '/' . $this->rest_base. '/customer_checkout', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'add_checkout_customer')
        ));

        // View Cart - wc/v2/cart/clear (POST)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/clear', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'clear_cart')
        ));

        // Add Item - wc/v2/cart/add (POST)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/add', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'add_to_cart')
        ));

        // Add Items - wc/v2/cart/add (POST)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/add_items', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'add_items_to_cart')
        ));

        // Add Item - wc/v2/cart/add (POST)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/add_customer', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'add_customer')
        ));
    }

    /**
     * Get cart.
     *
     * @param  array $data
     * @return WP_REST_Response
     */
    public function get_cart($data = array())
    {
        $cart = WC()->cart->get_cart();

        if ($this->get_cart_contents_count() <= 0) {
            return new WP_REST_Response(
                __('Cart is empty!', 'cart-rest-api-for-woocommerce'),
                200
            );
        }

        $show_thumb = !empty($data['thumb']) ? $data['thumb'] : false;

        foreach ($cart as $item_key => $cart_item) {
            $_product = apply_filters('wc_cart_rest_api_cart_item_product', $cart_item['data'], $cart_item, $item_key);

            // Adds the product name as a new variable.
            $cart[$item_key]['product_name'] = $_product->get_name();

            // If main product thumbnail is requested then add it to each item in cart.
            if ($show_thumb) {
                $thumbnail_id = apply_filters(
                    'wc_cart_rest_api_cart_item_thumbnail',
                    $_product->get_image_id(),
                    $cart_item,
                    $item_key
                );

                $thumbnail_src = wp_get_attachment_image_src($thumbnail_id, 'woocommerce_thumbnail');

                // Add main product image as a new variable.
                $cart[$item_key]['product_image'] = esc_url($thumbnail_src[0]);
            }
        }

        return new WP_REST_Response($cart, 200);
    }

    /**
     * Clear cart.
     *
     * @return WP_ERROR|WP_REST_Response
     */
    public function clear_cart()
    {
        WC()->cart->empty_cart();

        if (WC()->cart->is_empty()) {
            $cookieHash = $this->generateCartCookie();
            $_COOKIE['wp_woocommerce_session_' . COOKIEHASH] = $cookieHash;

            unset(WC()->session);
            WC()->init();
            WC()->session->set_customer_session_cookie(true);
            WC()->session->save_data();

            $data['cleared'] = true;
            $data['cart_session_value'] = $cookieHash;
            $data['cart_session_name'] = 'wp_woocommerce_session_'.COOKIEHASH;
            $data['checkout_url'] = urlencode(WC()->cart->get_checkout_url());
            return new WP_REST_Response($data, 200);
        } else {
            return new WP_Error(
                'wc_cart_rest_clear_cart_failed',
                __('Clearing the cart failed!', 'cart-rest-api-for-woocommerce'),
                array('status' => 500)
            );
        }
    }

    /**
     * Generate hash for cart session
     *
     * @return string
     */
    protected function generateCartCookie()
    {
        $customerId = WC()->session->generate_customer_id();
        $sessionExpiring = time() + intval( apply_filters( 'wc_session_expiring', 60 * 60 * 47 ) ); // 47 Hours.
        $sessionExpiration = time() + intval( apply_filters( 'wc_session_expiration', 60 * 60 * 48 ) ); // 48 Hours.
        $toHash = $customerId . '|' . $sessionExpiration;
        $cookieHash = hash_hmac( 'md5', $toHash, wp_hash( $toHash ) );
        return $customerId . '||' . $sessionExpiration . '||' . $sessionExpiring . '||' . $cookieHash;

    }

    /**
     * Validate the product id argument.
     *
     * @param  int $product_id
     * @return WP_Error
     */
    protected function validate_product_id($product_id)
    {
        if ($product_id <= 0) {
            return new WP_Error(
                'wc_cart_rest_product_id_required',
                __('Product ID number is required!', 'cart-rest-api-for-woocommerce'),
                array('status' => 500)
            );
        }

        if (!is_numeric($product_id)) {
            return new WP_Error(
                'wc_cart_rest_product_id_not_numeric',
                __('Product ID must be numeric!', 'cart-rest-api-for-woocommerce'),
                array('status' => 500)
            );
        }
    }

    /**
     * Validate the product quantity argument.
     *
     * @param  int $quantity
     * @return WP_Error
     */
    protected function validate_quantity($quantity)
    {
        if (!is_numeric($quantity)) {
            return new WP_Error(
                'wc_cart_rest_quantity_not_numeric',
                __('Quantity must be numeric!', 'cart-rest-api-for-woocommerce'),
                array('status' => 500)
            );
        }
    }

    /**
     * Validate product before it is added to the cart, updated or removed.
     *
     * @param  int $product_id
     * @param  int $quantity
     * @return WP_Error|bool
     */
    protected function validate_product($product_id = null, $quantity = 1)
    {
        $result = $this->validate_product_id($product_id);
        if ($result instanceof WP_Error) {
            return $result;
        }

        $result = $this->validate_quantity($quantity);
        if ($result instanceof WP_Error) {
            return $result;
        }

        return true;
    }

    /**
     * Add to Cart.
     *
     * @param  array $data
     * @return WP_Error|WP_REST_Response
     * @throws Exception
     */
    public function add_to_cart($data = array())
    {
        $product_id = !isset($data['product_id']) ? 0 : absint($data['product_id']);
        $quantity = !isset($data['quantity']) ? 1 : absint($data['quantity']);
        $variation_id = !isset($data['variation_id']) ? 0 : absint($data['variation_id']);
        $variation = !isset($data['variation']) ? array() : $data['variation'];
        $cart_item_data = !isset($data['cart_item_data']) ? array() : $data['cart_item_data'];

        if (isset($data['price'])) {
            $cart_item_data['proposal_price'] = $data['price'];
        }

        $validation = $this->validate_product($product_id, $quantity);
        if ($validation instanceof WP_Error) {
            return $validation;
        }


        $product_data = wc_get_product($variation_id ? $variation_id : $product_id);

        $validation = $this->validate_add_to_cart_product($product_data, $quantity);
        if ($validation instanceof WP_Error) {
            return $validation;
        }

        $item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation, $cart_item_data);

        if ($item_key) {
            $data = WC()->cart->get_cart_item($item_key);
            WC()->session->save_data();
            do_action('wc_cart_rest_add_to_cart', $item_key, $data);

            if (is_array($data)) {
                return new WP_REST_Response($data, 200);
            }
        } else {
            return new WP_Error(
                'wc_cart_rest_cannot_add_to_cart',
                sprintf(__('You cannot add "%s" to your cart.', 'cart-rest-api-for-woocommerce'),
                    $product_data->get_name()),
                array('status' => 500)
            );
        }
    }

    /**
     * Add customer to checkout
     *
     * @param  array $data
     * @return WP_Error|WP_REST_Response
     * @throws Exception
     */
    public function add_customer($data = array())
    {
        try {
            WC()->session->set('customer_checkout_data', $data->get_params());
            WC()->session->save_data();

            return new WP_REST_Response(['success' => true], 200);
        } catch (Exception $e) {
            return new WP_Error(
                'wc_cart_rest_cannot_add_to_cart',
                __('Internal error occurred while adding the products to the cart.', 'cart-rest-api-for-woocommerce'),
                array('status' => 500)
            );
        }
    }

    /**
     * Add multiple items to Cart.
     *
     * @param  array $data
     * @return WP_Error|WP_REST_Response
     * @throws Exception
     */
    public function add_items_to_cart($data = array())
    {
        $response = false;

        if (!is_array($data['items'])) {
            return new WP_Error(
                'wc_cart_rest_cannot_add_items_to_cart',
                __('Missing array with items in the request', 'cart-rest-api-for-woocommerce'),
                array('status' => 400)
            );
        }

        try {
            foreach ($data['items'] as $item) {
                $itemResponse = $this->add_to_cart($item);

                if ($itemResponse instanceof WP_Error) {
                    $response = $itemResponse;
                    break;
                }
            }
        } catch (\Exception $exception) {
            if (isset($item['id'])) {
                $response = new WP_Error(
                    'wc_cart_rest_cannot_add_to_cart',
                    sprintf(__('Internal error occurred while adding product id "%s" to your cart.', 'cart-rest-api-for-woocommerce'),
                        $item['id']),
                    array('status' => 500)
                );
            } else {
                $response = new WP_Error(
                    'wc_cart_rest_cannot_add_to_cart',
                    __('Internal error occurred while adding the products to the cart.', 'cart-rest-api-for-woocommerce'),
                    array('status' => 500)
                );
            }
        }

        return $response;
    }

    /**
     * Calculate Cart Totals.
     *
     * @return WP_REST_Response
     */
    public function calculate_totals()
    {
        if ($this->get_cart_contents_count(array('return' => numeric)) <= 0) {
            return new WP_REST_Response(
                __('No items in cart to calculate totals.', 'cart-rest-api-for-woocommerce'),
                200
            );
        }

        WC()->cart->calculate_totals();

        return new WP_REST_Response(
            __('Cart totals have been calculated.', 'cart-rest-api-for-woocommerce'),
            200
        );
    }

    /**
     * Returns all calculated totals.
     *
     * @return array
     */
    public function get_totals()
    {
        $totals = WC()->cart->get_totals();

        return $totals;
    }

    /**
     * Get cart contents count.
     *
     * @param  array $data
     * @return string|WP_REST_Response
     */
    public function get_cart_contents_count( $data = array() ) {
        $count = WC()->cart->get_cart_contents_count();

        $return = ! empty( $data['return'] ) ? $data['return'] : '';

        if ( $return != 'numeric' && $count <= 0 ) {
            return new WP_REST_Response(
                __( 'There are no items in the cart!', 'cart-rest-api-for-woocommerce' ),
                200
            );
        }

        return $count;
    }

    /**
     * Validate the product
     *
     * @param $product_data
     * @param $quantity
     * @return bool|WP_Error
     */
    protected function validate_add_to_cart_product($product_data, $quantity)
    {
        $error = false;

        if ($quantity <= 0 || !$product_data || 'trash' === $product_data->get_status()) {
            return new WP_Error(
                'wc_cart_rest_product_does_not_exist',
                __('Warning: This product does not exist!', 'cart-rest-api-for-woocommerce'),
                array('status' => 500)
            );
        }

        // Product is purchasable check.
        if (!$product_data->is_purchasable()) {
            return new WP_Error(
                'wc_cart_rest_cannot_be_purchased',
                __('Sorry, this product cannot be purchased.',
                    'cart-rest-api-for-woocommerce'),
                array('status' => 500)
            );
        }

        // Stock check - only check if we're managing stock and backorders are not allowed.
        if (!$product_data->is_in_stock()) {
            return new WP_Error(
                'wc_cart_rest_product_out_of_stock',
                sprintf(
                    __('You cannot add &quot;%s&quot; to the cart because the product is out of stock.',
                        'cart-rest-api-for-woocommerce'),
                    $product_data->get_name()),
                array('status' => 500)
            );
        }
        if (!$product_data->has_enough_stock($quantity)) {
            /* translators: 1: product name 2: quantity in stock */
            return new WP_Error(
                'wc_cart_rest_not_enough_in_stock',
                sprintf(__('You cannot add that amount of &quot;%1$s&quot; ' .
                    'to the cart because there is not enough stock (%2$s remaining).',
                    'cart-rest-api-for-woocommerce'),
                    $product_data->get_name(),
                    wc_format_stock_quantity_for_display($product_data->get_stock_quantity(), $product_data)),
                array('status' => 500)
            );
        }

        // Stock check - this time accounting for whats already in-cart.
        if ($product_data->managing_stock()) {
            $products_qty_in_cart = WC()->cart->get_cart_item_quantities();

            if (isset($products_qty_in_cart[$product_data->get_stock_managed_by_id()]) &&
                !$product_data->has_enough_stock($products_qty_in_cart[$product_data->get_stock_managed_by_id()] + $quantity)) {
                return new WP_Error(
                    'wc_cart_rest_not_enough_stock_remaining',
                    sprintf(
                        __('You cannot add that amount to the cart &mdash; we have %1$s in stock ' .
                            'and you already have %2$s in your cart.', 'cart-rest-api-for-woocommerce'),
                        wc_format_stock_quantity_for_display($product_data->get_stock_quantity(), $product_data),
                        wc_format_stock_quantity_for_display($products_qty_in_cart[$product_data->get_stock_managed_by_id()], $product_data)
                    ),
                    array('status' => 500)
                );
            }
        }

        return $error;
    }
}

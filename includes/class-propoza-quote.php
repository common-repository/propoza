<?php
/**
 * Propoza_Quote
 *
 * @package   Propoza
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 */
require_once 'class-propoza-quote-session.php';
/**
 * Propoza_Quote class.
 *
 * @package Propoza
 * @author  Propoza <support@propoza.com>
 */
class Propoza_Quote extends WC_Cart {
    /**
     * Instance of this class.
     *
     * @since    1.0.6
     *
     * @var      object
     */
    protected static $instance = null;
    /**
     * @var string
     */
	private static $quote_id_prefix = 'quote_';
	/**
	 * @var array
	 */
	private $quote_fields = array(
		'products',
		'propoza_quote_id',
	);
	/**
	 * @var null
	 */
	private $id;
	/**
	 * @var
	 */
	private $propoza_quote_id;
	/**
	 * @var array
	 */
	private $products = array();

    /**
     * @var string $quick_quote_image image used for the quick quote
     */
	private $quick_quote_image;

	/**
	 * Propoza_Quote constructor.
	 *
	 * @param null $id
	 */
	public function __construct() {
        $this->session = new Propoza_Quote_Session($this);
        $this->fees_api = new WC_Cart_Fees($this);

        $this->session->init();
	}

    /**
	 *
	 */
	public function load($id) {
	    $this->id = $id;
		foreach ( $this->quote_fields as $field ) {
			$post_meta    = get_post_meta( $this->id, $field, true );
			$this->$field = empty( $post_meta ) ? $this->$field : $post_meta;
		}
	}

    /**
     * Gives back the cart/quote or gets it from the session. Session has different function names because of name based calls in wp_hooks
     * @var boolean $session if the cart is acquired by a session
     * @return array of cart contents
     */
    public function get_cart($session = false) {
        if ( ! did_action( 'wp_loaded' ) ) {
            wc_doing_it_wrong( __FUNCTION__, __( 'Get cart should not be called before the wp_loaded action.', 'woocommerce' ), '2.3' );
        }
        if ( ! did_action( 'woocommerce_cart_loaded_from_session' ) && $this->id == null && $session == false ) {
            $this->session->get_quote_from_session();
        }
        return array_filter( $this->get_cart_contents() );
    }


    /**
     * Checks if the cart is empty.
     *
     * @return bool
     */
    public function is_empty() {
        return 0 === count( $this->get_cart_contents() );
    }


    /**
	 * @return array
	 */
	public function get_prepared_quote() {
		return $this->prepare_quote_request();
	}

	/**
	 * @return array
	 */
	public function prepare_quote_request() {
		$to_propoza_quote                       = array();
		$to_propoza_quote['Quote']              = $this->prepare_quote();
		$to_propoza_quote['Quote']['Requester'] = $this->prepare_requester();
		$to_propoza_quote['Quote']['Product']   = $this->prepare_propoza_products();
		$to_propoza_quote['Quote']['image']     = $this->quick_quote_image;
		$to_propoza_quote['Quote']['shop_url']  = get_site_url();

		return $to_propoza_quote;
	}

	/**
	 * @return array
	 */
	private function prepare_quote() {
		$to_propoza_quote                              = array();
		$to_propoza_quote['shop_quote_id']             = $this->id;
		$to_propoza_quote['cart_currency']             = get_woocommerce_currency();
		$to_propoza_quote['include_default_store_tax'] = 'yes' == get_option( 'woocommerce_prices_include_tax' ) ? true : false;

		return $to_propoza_quote;
	}

	/**
	 * @return array
	 */
	public function prepare_requester() {
		return $this->get_prepared_logged_in_user();
	}

	/**
	 * @return array
	 */
	public function get_prepared_logged_in_user() {
		$current_user           = wp_get_current_user();
		$requester              = array();
		$requester['firstname'] = ! $current_user->user_firstname ? '' : $current_user->user_firstname;
		$requester['lastname']  = ! $current_user->user_lastname ? '' : $current_user->user_lastname;
		$requester['email']     = ! $current_user->user_email ? '' : $current_user->user_email;
		$requester['company']   = ! $current_user->get( 'billing_company' ) ? ! $current_user->get( 'shipping_company' ) ? '' : $current_user->get( 'shipping_company' ) : $current_user->get( 'billing_company' );

		return $requester;
	}

	/**
	 * Prepares the multiple Woocommerce products and its different typings to Propoza products.
	 * Parent_ids are the ids of parents of a group of variations.
	 * @return array of prepared propoza products.
	 */
    private function prepare_propoza_products() {
        $products = array();
        $count    = 0;
        $bundle   = false;
        foreach ( $this->products as $product ) {
            if ( $product['data']->get_parent_id() > 0 ) {
                list( $products, $count, $bundle ) = $this->add_variations_to_propoza_products(
                    $product,
                    $products,
                    $count,
                    $bundle
                );
            } else {
                list( $products, $count, $bundle ) = $this->add_product_to_propoza_products(
                    $product,
                    $products,
                    $count,
                    $bundle
                );
            }
        }

        return $products;
    }

	/**
	 * This function adds propoza_products to the products array from variations.
	 * First it checks if it is a bundled variation or a simple product variation.
	 *
	 * @param $product product to add as a propoza product to products
	 * @param $products array of propoza products
	 * @param $count index in the products array
	 *
	 * @return array returns products and count back with all the products added.
	 */
    private function add_variations_to_propoza_products( $product, $products, $count, $bundle ) {
        if ( !function_exists( 'wc_pb_is_bundled_cart_item' ) || !wc_pb_is_bundled_cart_item( $product )) {
            if ( $bundle === true ) {
                $bundle = false;
                $count++;
            }
            $products[] = $this->prepare_propoza_product( $product );
            $count++;
        } else {
            $children = isset($products[ $count ]['Child']) ? $products[$count]['Child'] : [];
            $children[] = $this->prepare_propoza_product( $product );
            $products[ $count ]['Child'] = $children;
        }

        return array( $products, $count, $bundle );
    }

	/**
	 * Prepares a Propoza product from a Woocommerce product.
	 *
	 * @param $product The product to prepare.
	 *
	 * @return array with the prepared Propoza product.
	 */
	private function prepare_propoza_product( $product ) {
		$propoza_product                     = array();
		$propoza_product['name']             = $product['data']->get_title();
		$propoza_product['original_price']   = $product['data']->get_price();
		$propoza_product['sku']              = $product['data']->get_sku();
		$propoza_product['quantity']         = $product['quantity'];
		$propoza_product['ProductAttribute'] = $this->prepare_product_attributes( $product );
		$propoza_product['external_url']     = get_permalink( $product['product_id'] );
		$propoza_product['external_id']      =  $product['product_id'];

		return $propoza_product;
	}

	/**
	 * Gathers the product attributes for a propoza product.
	 *
	 * @param $product product to gather attributes for.
	 *
	 * @return array returns product attributes gathered in the function
	 */
	private function prepare_product_attributes( $product ) {
		$product_attributes = array();
		$counter            = 0;
		foreach ( $this->get_quote_item_data( $product ) as $variation ) {
			if ( isset( $variation['key'], $variation['display'] ) ) {
				$product_attributes[ $counter ]['name']  = $variation['key'];
				$product_attributes[ $counter ]['value'] = $variation['display'];
				$counter ++;
			}
		}

		return $product_attributes;
	}

	/**
	 * @param $cart_item
	 *
	 * @return array
	 */
	private function get_quote_item_data( $cart_item ) {
		$item_data = array();

		// Variation data
		if ( sizeof( $cart_item['variation'] ) > 0 ) {

			foreach ( $cart_item['variation'] as $name => $value ) {

				if ( '' == $value ) {
					continue;
				}

				$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

				// If this is a term slug, get the term's nice name
				if ( taxonomy_exists( $name ) ) {
					$term = get_term_by( 'slug', $value, $name );
					if ( ! is_wp_error( $term ) && $term && $term->name ) {
						$value = $term->name;
					}
					$label = wc_attribute_label( $name );

					// If this is a custom option slug, get the options name
				} else {
					$value              = apply_filters( 'woocommerce_variation_option_name', $value );
					$product_attributes = $cart_item['data']->get_attributes();
					if ( isset( $product_attributes[ str_replace( 'attribute_', '', $name ) ] ) ) {
						$label = str_replace( 'attribute_', '', $name );
					} elseif ( isset($product_attributes[ str_replace( 'pa_', '', $name ) ] ) ) {
                        $label =  str_replace( 'pa_', '', $name );
                    }
					else {
						$label = $name;
					}
				}

				$item_data[] = array(
					'key'   => $label,
					'value' => $value,
				);
			}
		}

		// Filter item data to allow 3rd parties to add more to the array
		$item_data = apply_filters( 'woocommerce_get_item_data', $item_data, $cart_item );

		// Format item data ready to display
		foreach ( $item_data as $key => $data ) {
			// Set hidden to true to not display meta on cart.
			if ( ! empty( $data['hidden'] ) ) {
				unset( $item_data[ $key ] );
				continue;
			}
			$item_data[ $key ]['key']     = ! empty( $data['key'] ) ? $data['key'] : $data['name'];
			$item_data[ $key ]['display'] = ! empty( $data['display'] ) ? $data['display'] : $data['value'];
		}

		return $item_data;
	}

	/**
	 * This function decides in what way to add the propoza product to the products array.
	 * It does this based on the instance of the product.
	 * The order is like this: Product bundle -> item that is part of a product bundle -> simple item.
	 *
	 * @param $product product to be converted to propoza product and checked of its instance
	 * @param $products products array of propoza products
	 * @param $count index of the products array
	 * @param $bundle if currently the items are added to a bundle
	 *
	 * @return array returns the products array with the new product added, the index as $count and the bundle status.
	 */
	private function add_product_to_propoza_products( $product, $products, $count, $bundle ) {
		if ( $product['data'] instanceof WC_Product_Bundle ) {
			if ( true == $bundle ) {
				$count ++;
			}
			$products[ $count ] = $this->prepare_propoza_product( $product );
			$bundle             = true;
		} elseif ( function_exists( 'wc_pb_is_bundled_cart_item' ) && wc_pb_is_bundled_cart_item( $product ) ) {
			$children                    = isset($products[ $count ]['Child']) ? $products[$count]['Child'] : [];
			$children[]                  = $this->prepare_propoza_product( $product );
			$products[ $count ]['Child'] = $children;
		} else {
			if ( true == $bundle ) {
				$bundle = ! $bundle;
				$count ++;
			}
			$products[ $count ] = $this->prepare_propoza_product( $product );
			$count ++;
		}

		return array( $products, $count, $bundle );
	}

	/**
	 *
	 */
	public function load_products_from_cart() {
		$this->set_products( wc()->cart->get_cart_contents() );
	}

    /**
     * When preparing a quick quote load the products not from wc but from our own 'cart'
     */
	public function load_products_for_quick_quote() {
	    $this->set_products( $this->get_cart_contents() );
    }
    /**
     * function that gets the quick quote image url pased on the product id.
     * @param string $id used to get the image by product id
     */
    public function add_quick_quote_image($id) {
        $product = wc_get_product($id);
        $image = $product->get_image_id();
        $this->quick_quote_image = wp_get_attachment_url($image);
    }
	/**
	 * @param $product
	 */
	public function add_product( $product ) {
		array_push( $this->products, $product );
	}

	/**
	 * @return null
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * @return array
	 */
	public function get_products() {
		return $this->products;
	}

	/**
	 * @param $products
	 */
	public function set_products( $products ) {
		$this->products = $products;
	}

	/**
	 * @return mixed
	 */
	public function get_propoza_quote_id() {
		return $this->propoza_quote_id;
	}

	/**
	 * @param $propoza_quote_id
	 */
	public function set_propoza_quote_id( $propoza_quote_id ) {
		$this->propoza_quote_id = $propoza_quote_id;
	}

	/**
	 *
	 */
	public function custom_post_status() {
		register_post_status( 'quote', array(
			'label'                     => _x( 'Quote', 'post' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
			/* translators: %s: quote count*/
			'label_count'               => _n_noop( 'Quote <span class="count">(%s)</span>', 'Quote <span class="count">(%s)</span>' ),
		) );
	}

	/**
	 * @return int
	 */
	public function get_quote_total() {
		$total = 0;
		foreach ( $this->products as $product ) {
			$total += $product['line_total'];
		}

		return $total;
	}

	/**
	 * @return array
	 */
	/**
	 * @return array
	 */
	/**
	 * @return array
	 */
	/**
	 * @return array
	 */
	public function get_product_ids() {
		$ids = array();
		foreach ( $this->products as $product ) {
			array_push( $ids, $product['product_id'] );
		}

		return $ids;
	}

	/**
	 * @return Propoza_Quote
	 */
	/**
	 * @return Propoza_Quote
	 */
	/**
	 * @return Propoza_Quote
	 */
	/**
	 * @return Propoza_Quote
	 */
	public function clone_quote() {
		$propoza_quote = new self();
		foreach ( $this->quote_fields as $field ) {
			$propoza_quote->$field = $this->$field;
		}
		$propoza_quote->save();

		return $propoza_quote;
	}

	/**
	 *
	 */
	public function save() {
		if ( $this->id ) {
			foreach ( $this->quote_fields as $field ) {
				update_post_meta( $this->id, $field, $this->$field );
			}
		} else {
			$this->id = $this->create();
			$this->save();
		}
	}

	/**
	 * @return mixed
	 */
	/**
	 * @return mixed
	 */
	/**
	 * @return mixed
	 */
	/**
	 * @return mixed
	 */
	private function create() {
		$quote = array(
			'post_title'   => self::$quote_id_prefix . time(),
			'post_content' => '',
			'post_status'  => 'quote',
			'post_author'  => 1,
			'post_type'    => 'shop_quote',
		);

		return wp_insert_post( $quote );
	}

	/**
	 * @param $propoza_quote_id
	 * @param array $exclude_quote_ids
	 */
	/**
	 * @param $propoza_quote_id
	 * @param array $exclude_quote_ids
	 */
	/**
	 * @param $propoza_quote_id
	 * @param array $exclude_quote_ids
	 */
	/**
	 * @param $propoza_quote_id
	 * @param array $exclude_quote_ids
	 */
	public function delete_proposal_quote_clones( $propoza_quote_id, $exclude_quote_ids = array() ) {
		$data   = new WP_Query( array(
			'post_type'   => 'shop_quote',
			'post_status' => 'quote',
			'meta_key'    => 'propoza_quote_id',
			'meta_value'  => $propoza_quote_id,
		) );
		$quotes = $data->get_posts();
		foreach ( $quotes as $quote ) {
			if ( ! in_array( $quote->ID, $exclude_quote_ids ) ) {
				foreach ( $this->quote_fields as $fields ) {
					delete_post_meta( $quote->ID, $fields );
				}
				wp_delete_post( $quote->ID );
			}
		}
	}
}

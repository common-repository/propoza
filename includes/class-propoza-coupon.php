<?php
/**
 * Propoza_Coupon
 *
 * @package   Propoza
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 */

/**
 * Propoza_Coupon class.
 *
 * @package Propoza
 * @author  Propoza <support@propoza.com>
 */
class Propoza_Coupon extends WC_Coupon {
	/**
	 * @var string
	 */
	private $coupon_title = 'proposal for quote #%s';

	/**
	 * @var array
	 */
	private $coupon_fields = array(
		'is_propoza_proposal' => true,
		'propoza_quote_id'    => null,
	);

	/**
	 * Propoza_Coupon constructor.
	 *
	 * @param null $propoza_quote_id
	 */
	public function __construct( $propoza_quote_id = null ) {
		if ( null != $propoza_quote_id ) {
			parent::__construct( sprintf( $this->coupon_title, $propoza_quote_id ) );
			$this->populate();
		}
	}

	/**
	 * @param array $data
	 */
	private function populate( $data = array() ) {
		foreach ( $this->coupon_fields as $key => $value ) {
			// Try to load from meta if an ID is present
			if ( $this->id ) {
				$this->$key = get_post_meta( $this->id, $key, true );
			} else {
				$this->$key = ! empty( $data[ $key ] ) ? wc_clean( $data[ $key ] ) : '';
			}
		}
	}

	/**
	 * @param $coupon_code
	 */
	public function load_by_id( $coupon_code ) {
		parent::__construct( $coupon_code );
		$this->populate();
	}

	/**
	 * @param Propoza_Quote $quote
	 * @param $original_total
	 * @param $proposal_total
	 *
	 * @return mixed
	 */
	public function create_proposal_coupon( $quote, $original_total, $proposal_total ) {
		$coupon_code   = sprintf( $this->coupon_title, $quote->get_propoza_quote_id() );
		$discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product

		$coupon = array(
			'post_title'   => $coupon_code,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'shop_coupon',
		);

		$new_coupon_id = wp_insert_post( $coupon );

		// Add meta
		update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
		update_post_meta( $new_coupon_id, 'coupon_amount', $this->calculate_discount( $original_total, $proposal_total ) );
		update_post_meta( $new_coupon_id, 'individual_use', 'no' );
		update_post_meta( $new_coupon_id, 'product_ids', implode( ',', $quote->get_product_ids() ) );
		update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
		update_post_meta( $new_coupon_id, 'usage_limit', 1 );
		update_post_meta( $new_coupon_id, 'expiry_date', '' );
		update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
		update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
		update_post_meta( $new_coupon_id, 'usage_limit_per_user', 1 );
//		update_post_meta( $new_coupon_id, 'minimum_amount', $original_total);
		update_post_meta( $new_coupon_id, 'is_propoza_proposal', true );
		update_post_meta( $new_coupon_id, 'propoza_quote_id', $quote->get_propoza_quote_id() );

		return $new_coupon_id;
	}

	/**
	 * @param $original_total
	 * @param $proposal_total
	 *
	 * @return mixed
	 */
	public function calculate_discount( $original_total, $proposal_total ) {
		if ( 0 == $original_total ) {
			return $proposal_total;
		}

		return $original_total - $proposal_total;
	}

	/**
	 * @param Propoza_Quote $quote
	 * @param $original_total
	 * @param $proposal_total
	 *
	 * @return mixed
	 */
	public function update_propoza_coupon( $quote, $original_total, $proposal_total ) {
		update_post_meta( $this->id, 'product_ids', implode( ',', $quote->get_product_ids() ) );
		update_post_meta( $this->id, 'coupon_amount', $this->calculate_discount( $original_total, $proposal_total ) );
//		update_post_meta( $this->id, 'minimum_amount', $original_total );
		update_post_meta( $this->id, 'propoza_quote_id', $quote->get_propoza_quote_id() );

		return $this->id;
	}

	/**
	 *
	 * @return string|null
	 * @throws WC_Data_Exception
	 */
	public function get_propoza_quote_id() {
		return $this->propoza_quote_id;
	}

	/**
	 * @return boolean
	 * @throws WC_Data_Exception
	 */
	public function is_propoza_proposal() {
		return (bool)$this->is_propoza_proposal;
	}
}

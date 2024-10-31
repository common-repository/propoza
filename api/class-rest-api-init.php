<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Propoza REST API class.
 */
class WC_Propoza_Rest_API {

    /**
     * WC_Propoza_Rest_API constructor.
     */
	public function __construct() {
		$this->rest_api_init();
	}

    /**
     * Init the rest api
     */
	protected function rest_api_init() {
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			return;
		}

		$this->include_cart_controller();
		add_action( 'rest_api_init', array( $this, 'register_cart_routes' ), 0 );
	}

    /**
     * Include the cart controller
     */
    protected function include_cart_controller() {
		include_once( dirname( __FILE__ ) . '/class-wc-rest-cart-controller.php' );
	}

	/**
	 * Register Cart REST API routes.
	 */
	public function register_cart_routes() {
		$controller = new WC_REST_Propoza_Controller();
		$controller->register_routes();
	}
}


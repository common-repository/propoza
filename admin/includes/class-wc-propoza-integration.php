<?php

if ( ! class_exists( 'WC_Propoza_Integration' ) ) :

	/**
	 * Class WC_Propoza_Integration
	 */
	class WC_Propoza_Integration extends WC_Integration {
		/**
		 * @var string
		 */
		public $id = 'propoza';
		/**
		 * @var
		 */
		protected $plugin_slug;
		/**
		 * @var string
		 */
		protected $api_key;
		/**
		 * @var string
		 */
		protected $address;

		/**
		 * @var null
		 */
		private $_propoza_dashboard_token = null;

		/**
		 * WC_Propoza_Integration constructor.
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Ajax Test connection
		 */
		public function test_connection() {
			$response = wp_remote_get(
				Propoza::get_connection_test_url( $_POST['sub_domain']  ),
				array(
					'method'    => 'GET',
					'headers'   => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Basic ' . base64_encode( $_POST['api_key'] ),
					),
					'sslverify' => ! WP_DEBUG,
				)
			);
			if ( ! $response instanceof WP_Error ) {
				echo $response['body'];
			} else {
				echo json_encode( array( 'response' => false ) );
			}
			die;
		}

		/**
		 * initialize Propoza Integration tab
		 */
		public function init() {
			$this->plugin_slug = Propoza::get_instance()->get_plugin_slug();

			$this->add_actions();

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables.
			$this->api_key = $this->get_option( 'api_key' );
			$this->address = $this->get_option( 'web_address' );
		}

		/**
		 *
		 */
		public function menu_quote() {
			add_submenu_page( 'woocommerce', __( 'Quotes', 'propoza' ), __( 'Quotes', 'propoza' ), 'manage_woocommerce', 'wc-propoza-quotes', array(
				$this,
				'quotes_page',
			) );
		}

		/**
		 * @return null
		 */
		public function request_propoza_token() {
			if ( ! isset( $this->_propoza_dashboard_token ) ) {
				$response = wp_remote_get(
					Propoza::get_dashboard_propoza_token_url(),
					array(
						'method'    => 'GET',
						'headers'   => array(
							'Content-Type'  => 'application/json',
							'Authorization' => 'Basic ' . $this->get_option( 'api_key' ),
						),
						'sslverify' => ! WP_DEBUG,
					)
				);

				if ( ! $response instanceof WP_Error ) {
					$this->_propoza_dashboard_token = json_decode( $response['body'] )->response->token;
				}
			}

			return $this->_propoza_dashboard_token;
		}

		/**
		 *
		 */
		public function quotes_page() {
			if ( is_admin() ) {
				add_filter( 'propoza_dashboard_token', array( $this, 'request_propoza_token' ), 10, 1 );
			}
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'views/Quotes/grid.php';
		}


		/**
		 *
		 */
		public function add_actions() {
			//Add script and styles
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

			//Add processing options on save
			add_action( 'woocommerce_update_options_integration_' . $this->id, array(
				$this,
				'process_admin_options',
			) );

			//Add ajax test connection function
			add_action( 'wp_ajax_test_connection', array( $this, 'test_connection' ) );

			if ( 'yes' == $this->get_option( 'enabled' ) ) {
				add_action( 'admin_menu', array( $this, 'menu_quote' ) );
			}

			add_action( 'propoza_before_load_grid', array( $this, 'request_propoza_token' ) );
		}

		/**
		 * @param array $form_fields
		 * @param bool $echo
		 */
		public function generate_settings_html( $form_fields = array(), $echo = true ) {
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'views/invalid-api-key-message.php';
			parent::generate_settings_html( $form_fields );
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'views/loader.php';
		}

		/**
		 * Initialize integration settings form fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'title'           => array(
					'title'   => __( 'Propoza', $this->plugin_slug ),
					'type'    => 'title',
					'class'   => 'propoza-header',
					'default' => '',
				),
				'setup_title'     => array(
					'title'       => __( 'Setup', $this->plugin_slug ),
					'type'        => 'title',
					'description' => __( 'Get started with a free account<br><br>Sign up in 30 seconds. No credit card required.<br>Already have a Propoza account? Log in here<br>', $this->plugin_slug ),
				),
				'setup_button'    => array(
					'value'             => __( 'Create My Account', $this->plugin_slug ),
					'type'              => 'button',
					'custom_attributes' => array( 'href' => Propoza::get_sign_up_propoza_url() ),
				),
				'general_title'   => array(
					'title' => __( 'General', $this->plugin_slug ),
					'type'  => 'title',
				),
				'enabled'         => array(
					'title'    => __( 'Enable Propoza', $this->plugin_slug ),
					'type'     => 'checkbox',
					'selected' => true,
				),
				'web_address'     => array(
					'title'       => __( 'Sub-domain', $this->plugin_slug ),
					'type'        => 'text',
					'description' => __( 'Please enter the sub-domain that you have registered with your Propoza account.', $this->plugin_slug ),
					'placeholder' => __( 'yourdomain', $this->plugin_slug ),
					'after_input' => '.propoza.com',
					'css'         => 'width: 300px;',
				),
				'api_key'         => array(
					'title'       => __( 'API key', $this->plugin_slug ),
					'type'        => 'textarea',
					'description' => __( 'The API key will be send to you in our email after you have setup your Propoza account', $this->plugin_slug ),
					'css'         => 'width: 300px;height:180px',
				),
				'test_connection' => array(
					'value'       => __( 'Test connection', $this->plugin_slug ),
					'type'        => 'button',
					'class'       => 'button',
					'after_input' => '<a href="' . Propoza::get_dashboard_propoza_url( '%s' ) . '" target="_blank" class="button" id="woocommerce_propoza_launch_propoza">' . __( 'Launch Propoza', $this->plugin_slug ) . '</a>'.
                '   <a href="' . Propoza::get_authenticate_url( '%s' ) . '" target="_blank" class="button" id="woocommerce_authenticate_propoza">' . __( 'Grant access to Propoza', $this->plugin_slug ) . '</a>',
				),
			);
		}

		/**
		 * Generate Text Input HTML.
		 *
		 * @param mixed $key
		 * @param mixed $data
		 *
		 * @since 1.0.6
		 * @return string
		 */
		public function generate_text_html( $key, $data ) {

			$field    = $this->plugin_id . $this->id . '_' . $key;
			$defaults = array(
				'title'             => '',
				'disabled'          => false,
				'class'             => '',
				'css'               => '',
				'placeholder'       => '',
				'type'              => 'text',
				'desc_tip'          => false,
				'description'       => '',
				'after_input'       => '',
				'custom_attributes' => array(),
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
					<?php echo $this->get_tooltip_html( $data ); ?>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
						<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?>/>
						<span class="after-input">
                            <?php echo $data['after_input']; ?>
						</span>
						<?php echo $this->get_description_html( $data ); ?>
					</fieldset>
				</td>
			</tr>
			<?php

			return ob_get_clean();
		}

		/**
		 * Generate Button Html
		 *
		 * @param mixed $key
		 * @param mixed $data
		 *
		 * @since 1.0.6
		 * @return string
		 */
		public function generate_button_html( $key, $data ) {
			$field    = $this->plugin_id . $this->id . '_' . $key;
			$defaults = array(
				'title'             => '',
				'disabled'          => false,
				'class'             => '',
				'css'               => '',
				'type'              => 'button',
				'desc_tip'          => false,
				'description'       => '',
				'custom_attributes' => array(),
				'after_input'       => '',
				'value'             => '',
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
					<?php echo $this->get_tooltip_html( $data ); ?>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
						</legend>
						<input class="input-button button-secondary <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $data['value'] ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?>/>
						<span class="after-input">
							<?php echo $data['after_input']; ?>
						</span>
						<?php echo $this->get_description_html( $data ); ?>
					</fieldset>
				</td>
			</tr>
			<?php

			return ob_get_clean();
		}

		/**
		 * Register and enqueue admin-specific style sheet.
		 *
		 * @since     1.0.6
		 */
		public function enqueue_admin_styles() {
			wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'assets/css/wc-propoza-integration.css', dirname( __FILE__ ) ), array(), Propoza::VERSION );
		}

		/**
		 * Register and enqueue admin-specific JavaScript.
		 *
		 * @since     1.0.6
		 */
		public function enqueue_admin_scripts() {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/wc-propoza-integration.js', dirname( __FILE__ ) ), array( 'jquery' ), Propoza::VERSION );
			wp_localize_script( $this->plugin_slug . '-admin-script', $this->plugin_slug . '_' . 'object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		}

		/**
		 * @param $field
		 * @param string $default
		 *
		 * @return string
		 */
		public static function option( $field, $default = '' ) {
			$instance = new self();

			return $instance->get_option( $field, $default );
		}
	}
endif;

<?php
/**
 * Shopify Related Products Settings
 * @version 0.0.9
 * @package Shopify Related Products
 */

class SECP_Settings {
	/**
	 * Parent plugin class
	 *
	 * @var    class
	 * @since  0.0.9
	 */
	protected $plugin = null;

	/**
	 * Option key, and option page slug
	 *
	 * @var    string
	 * @since  0.0.9
	 */
	protected $key = 'shopify_ecommerce_plugin_settings';

	/**
	 * Options page metabox id
	 *
	 * @var    string
	 * @since  0.0.9
	 */
	protected $metabox_id = 'shopify_ecommerce_plugin_settings_metabox';
	protected $metabox_utm_id = 'shopify_ecommerce_plugin_utm_settings_metabox';
	protected $metabox_ad_id = 'shopify_ecommerce_plugin_ad_settings_metabox';
	protected $metabox_default_slider_id = 'shopify_ecommerce_default_slider_settings_metabox';

	/**
	 * Options Page hook
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Constructor
	 *
	 * @since  0.0.9
	 * @param  object $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  0.0.9
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );
	}

	/**
	 * Register our setting to WP
	 *
	 * @since  0.0.9
	 */
	public function admin_init() {
		register_setting( $this->key, $this->key );
	}

	/**
	 * Enqueue admin styles
	 *
	 * @since 0.0.9
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'secp-admin', shopify_ecommerce_plugin()->url( 'assets/css/styles.css' ), array(), '160223' );
	}

	/**
	 * Add menu options page
	 *
	 * @since  0.0.9
	 */
	public function add_options_page() {
		add_menu_page(
			__( 'Shopify', 'shopify-related-products' ),
			__( 'Shopify', 'shopify-related-products' ),
			'manage_options',
			$this->key,
			array( $this, 'admin_page_display' ),
			$this->plugin->url( 'assets/images/shopify_icon_small2.png' )
		);
		$this->options_page = add_submenu_page(
			$this->key,
			__( 'Shopify', 'shopify-related-products' ),
			__( 'Settings', 'shopify-related-products' ),
			'manage_options',
			$this->key,
			array( $this, 'admin_page_display' )
		);
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 *
	 * @since  0.0.9
	 */
	public function admin_page_display() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'secp-admin-customize', $this->plugin->url( 'assets/js/admin-customize' . $min . '.js' ), array( 'jquery' ), '160223', true );
		wp_enqueue_style('secp_styles_css', $this->plugin->url('assets/css/styles_admin.css'));
		?>
		<div class="wrap cmb2-options-page <?php echo esc_attr( $this->key ); ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<div class="secp-customize">
				<div class="secp-customize-left">
					<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
				</div>
				<div class="secp-customize-left">
					<h4><?php _e( 'App configuration', 'shopify-related-products' ); ?></h4>
					<ul>
						<li>1. Add your shop name (<strong>your-shop-name</strong>.myshopify.com)</li>
						<li>2. Add your API Key from your Shopify private APP</li>
						<li>3. Add your Secret from your Shopify private APP</li>
						<li>3. Base url for your product urls</li>
					</ul>
				</div>
			</div>
			<hr>

			<div class="secp-customize">
				<h3>utm campaign configuration</h3>
				<div class="secp-customize-left">
					<?php cmb2_metabox_form( $this->metabox_utm_id, $this->key ); ?>
				</div>
				<div class="secp-customize-left">
					<h4><?php _e( 'App configuration', 'shopify-related-products' ); ?></h4>
					<ul>
						<li>Campaign Source (utm_source) – Required parameter to identify the source of your traffic such as: search engine, newsletter, or other referral.</li>
						<li>Campaign Medium (utm_medium) – Required parameter to identify the medium the link was used upon such as: email, CPC, or other method of sharing.</li>
						<li>Campaign Term (utm_term) – Optional parameter suggested for paid search to identify keywords for your ad. You can skip this for Google AdWords if you have connected your AdWords and Analytics accounts and use the auto-tagging feature instead.</li>
						<li>Campaign Content (utm_content) – Optional parameter for additional details for A/B testing and content-targeted ads.</li>
						<li>Campaign Name (utm_campaign) – Required parameter to identify a specific product promotion or strategic campaign such as a spring sale or other promotion.</li>
					</ul>
				</div>
			</div>
 			<hr>

			<div class="secp-customize">
				<h3>Ad default titles</h3>
				<div class="secp-customize-left">
					<?php cmb2_metabox_form( $this->metabox_ad_id, $this->key ); ?>
				</div>
				<div class="secp-customize-left">
					<h4><?php _e( 'Utm campaign configuration', 'shopify-related-products' ); ?></h4>
					<ul>
						<li>Campaign Source (utm_source) – Required parameter to identify the source of your traffic such as: search engine, newsletter, or other referral.</li>
						<li>Campaign Medium (utm_medium) – Required parameter to identify the medium the link was used upon such as: email, CPC, or other method of sharing.</li>
					</ul>
				</div>
			</div>

			<div class="secp-customize">
				<h3>Default slider ad</h3>
				<div class="secp-customize-left">
					<button data-adtype="<?php echo SECP_Customize::AD_TYPE_COLLECTION?>" class="button secp-add-shortcode shopify-selector" data-endpoint="collections" data-product-type="collection">Select collection</button>
					<br>
					<div id="secp_popup_content" style="display: none;"></div>
					<div class="secp-added-collections">
					</div>
					<?php cmb2_metabox_form( $this->metabox_default_slider_id, $this->key ); ?>
				</div>
				<div class="secp-customize-left">
					<ul>
						<li>Select the default collection that is gona be shown in every post / custom post that doen's have a specific ad configured</li>
					</ul>
				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * Add custom fields to the options page.
	 *
	 * @since  0.0.9
	 */
	public function add_options_page_metabox() {

		$cmb = new_cmb2_box( array(
			'id'         => $this->metabox_id,
			'hookup'     => false,
			'cmb_styles' => false,
			'show_on'    => array(
				'key'   => 'options-page',
				'value' => array( $this->key ),
			),
		) );

		$cmb_utm = new_cmb2_box( array(
			'id'         => $this->metabox_utm_id,
			'hookup'     => false,
			'cmb_styles' => false,
			'show_on'    => array(
				'key'   => 'options-page',
				'value' => array( $this->key ),
			),
		) );

		$cmb_ad = new_cmb2_box( array(
			'id'         => $this->metabox_ad_id,
			'hookup'     => false,
			'cmb_styles' => false,
			'show_on'    => array(
				'key'   => 'options-page',
				'value' => array( $this->key ),
			),
		) );

		$cmb_default_slider = new_cmb2_box( array(
			'id'         => $this->metabox_default_slider_id,
			'hookup'     => false,
			'cmb_styles' => false,
			'show_on'    => array(
				'key'   => 'options-page',
				'value' => array( $this->key ),
			),
		) );

		/*
		 * Metabox configuration fields
		 */

		$cmb->add_field( array(
			'name'    => __( 'Shop', 'shopify-related-products' ),
			'id'      => 'app_shop',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb->add_field( array(
			'name'    => __( 'Application API Key', 'shopify-related-products' ),
			'id'      => 'app_key',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb->add_field( array(
			'name'    => __( 'Application API password', 'shopify-related-products' ),
			'id'      => 'app_password',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb->add_field( array(
			'name'    => __( 'Shop product url', 'shopify-related-products' ),
			'id'      => 'app_shop_url',
			'type'    => 'text',
			'default' => '',
		));


		//Metabox utm titles fields
		$cmb_utm->add_field( array(
			'name'    => __( 'Campaign Source', 'shopify-related-products' ),
			'id'      => 'secp_utm_source',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb_utm->add_field( array(
			'name'    => __( 'Campaign Medium', 'shopify-related-products' ),
			'id'      => 'secp_utm_medium',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb_utm->add_field( array(
			'name'    => __( 'Campaign Term', 'shopify-related-products' ),
			'id'      => 'secp_utm_term',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb_utm->add_field( array(
			'name'    => __( 'Campaign Content', 'shopify-related-products' ),
			'id'      => 'secp_utm_content',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb_utm->add_field( array(
			'name'    => __( 'Campaign Name', 'shopify-related-products' ),
			'id'      => 'secp_utm_campaign',
			'type'    => 'text',
			'default' => '',
		) );


		//Metabox ad default titles fields
		$cmb_ad->add_field( array(
			'name'    => __( 'Ad title', 'shopify-related-products' ),
			'id'      => 'secp_ad_title',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb_ad->add_field( array(
			'name'    => __( 'Ad subtitle', 'shopify-related-products' ),
			'id'      => 'secp_ad_subtitle',
			'type'    => 'text',
			'default' => '',
		) );

		//Metabox ad default titles fields
		$cmb_default_slider->add_field( array(
			'name'    => __( 'collection', 'shopify-related-products' ),
			'id'      => 'secp_collections_ids',
			'type'    => 'hidden',
			'default' => '',
		) );

	}
}

<?php
/**
 * Shopify Related Products - Customize
 * @version 0.0.9
 * @package Shopify Related Products
 */

require_once dirname( __FILE__ ) . '/../vendor/cmb2/init.php';

class SECP_Customize {
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
	protected $key = 'shopify_ecommerce_plugin_customize';

	/**
	 * Options page metabox id
	 *
	 * @var    string
	 * @since  0.0.9
	 */
	protected $metabox_id = 'shopify_ecommerce_plugin_customize_metabox';

	/**
	 * Options Page title
	 *
	 * @var    string
	 * @since  0.0.9
	 */
	protected $title = '';

	/**
	 * Options Page hook
	 * @var string
	 */
	protected $options_page = '';

	const SHORT_CODE_MANUAL_HTML = '<div class="secp-ad-manual-container"></div>';

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
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );

		/*Metabox shown in post/page*/
		add_action( 'add_meta_boxes', array( $this, 'secp_add_meta_boxes' ) );
        add_action( 'save_post', array($this,'secp_save_meta_boxes'), 10, 2 );
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
	 * Add menu options page
	 *
	 * @since  0.0.9
	 */
	public function add_options_page() {
		$this->options_page = add_submenu_page(
			'shopify_ecommerce_plugin_settings',
			__( 'Customize', 'shopify-related-products' ),
			__( 'Customize', 'shopify-related-products' ),
			'manage_options',
			$this->key,
			array( $this, 'admin_page_display' )
		);

		// Include CMB CSS in the head to avoid FOUC.
		add_action( "admin_print_styles-{$this->options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 *
	 * @since  0.0.9
	 */
	public function admin_page_display() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'secp-admin-customize', $this->plugin->url( 'assets/js/admin-customize' . $min . '.js' ), array( 'jquery' ), '160223', true );

		?>
		<div class="wrap cmb2-options-page <?php echo esc_attr( $this->key ); ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<div class="secp-customize">
				<div class="secp-customize-right">
					<h4><?php _e( 'Preview', 'shopify-related-products' ); ?></h4>
					<iframe class="secp-customize-preview" src="<?php
					echo esc_url( add_query_arg( array(
						'shop'           => 'embeds.myshopify.com',
						'product_handle' => 'yello-w',
						'show_cart'      => true,
						'vcenter'        => true,
					), site_url() ) );
					?>"></iframe>
				</div>
				<div class="secp-customize-left">
					<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
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

		wp_enqueue_style('thickbox');
		wp_enqueue_script('thickbox');
		add_action( "cmb2_save_options-page_fields_{$this->metabox_id}", array( $this, 'settings_notices' ), 10, 2 );
		wp_enqueue_script('secp-shopify-service', $this->plugin->url('assets/js/secp_service.js'));
		add_action('admin_head', array($this, 'secp_header_scripts'));
		wp_enqueue_script('secp_custom_wp_admin_js', $this->plugin->url('assets/js/secp_admin.js'));
		wp_enqueue_script('secp_mustache_js', $this->plugin->url('assets/js/mustache.js'));
		wp_enqueue_style('secp_styles_css', $this->plugin->url('assets/css/styles_admin.css'));

		$cmb = new_cmb2_box( array(
			'id'         => $this->metabox_id,
			'hookup'     => false,
			'cmb_styles' => false,
			'show_on'    => array(
				'key'   => 'options-page',
				'value' => array( $this->key ),
			),
		) );

		/*
		Add your fields here
		*/
		$cmb->add_field( array(
			'name'    => __( 'Colors', 'shopify-related-products' ),
			'id'   => 'color_title',
			'type'    => 'title',
		) );

		$cmb->add_field( array(
			'name'    => __( 'Button color', 'shopify-related-products' ),
			'id'      => 'button_background_color',
			'type'    => 'colorpicker',
			'default' => '7db461',
		) );

		$cmb->add_field( array(
			'name'    => __( 'Button text', 'shopify-related-products' ),
			'id'      => 'button_text_color',
			'type'    => 'colorpicker',
			'default' => 'ffffff',
		) );

		$cmb->add_field( array(
			'name'    => __( 'Accent', 'shopify-related-products' ),
			'id'      => 'accent_color',
			'type'    => 'colorpicker',
			'default' => '000000',
		) );

		$cmb->add_field( array(
			'name'    => __( 'Text', 'shopify-related-products' ),
			'id'      => 'text_color',
			'type'    => 'colorpicker',
			'default' => '000000',
		) );

		$cmb->add_field( array(
			'desc'    => __( 'Background', 'shopify-related-products' ),
			'id'      => 'background',
			'type'    => 'checkbox',
			'default' => false,
		) );

		$cmb->add_field( array(
			'name'    => __( 'Background', 'shopify-related-products' ),
			'id'      => 'background_color',
			'type'    => 'colorpicker',
			'default' => 'ffffff',
		) );

		$cmb->add_field( array(
			'name'    => __( 'Button text', 'shopify-related-products' ),
			'id'      => 'buy_button_text',
			'type'    => 'text',
			'default' => __( 'Buy now', 'shopify-related-products' ),
		) );

		$cmb->add_field( array(
			'name'    => __( 'Cart title text', 'shopify-related-products' ),
			'id'      => 'cart_title',
			'type'    => 'text',
			'default' => __( 'Your cart', 'shopify-related-products' ),
		) );

		$cmb->add_field( array(
			'name'    => __( 'Checkout button text', 'shopify-related-products' ),
			'id'      => 'checkout_button_text',
			'type'    => 'text',
			'default' => __( 'Checkout', 'shopify-related-products' ),
		) );

		$cmb->add_field( array(
			'name'    => __( 'Where this button links to (single product only)', 'shopify-related-products' ),
			'id'      => 'redirect_to',
			'type'    => 'select',
			'default' => 'checkout',
			'options' => array(
				'checkout' => __( 'Checkout', 'shopify-related-products' ),
				'modal'  => __( 'Product Modal', 'shopify-related-products' ),
				'cart'     => __( 'Cart', 'shopify-related-products' ),
			),
		) );

	}

	public function secp_header_scripts()
	{
		$templatesUrl = plugins_url('', dirname(__FILE__)) . '/assets/templates/';
		?>
		<script>
			jQuery(document).ready(function () {
				jQuery('body').append('<div id="ShopifyServiceTemplates" style="display:none"></div>');
			});

			ShopifyService.templateUrl = '<?php echo $templatesUrl?>';
			ShopifyService.templatesList = [
				'secp-admin-collections-list',
				'secp-admin-collection-list',
				'secp-admin-collection-products-list',
				'secp-admin-products-list',
				'secp-admin-product-list',
				'secp-admin-product-select-list',
				'secp-admin-variant-select-list',
				'secp-admin-product-selection',
				'secp-admin-collection'
			]
			ShopifyService.loadNextTemplate();
		</script>
		<?php
	}

	/**
	 * Register settings notices for display
	 *
	 * @since  0.0.9
	 * @param  int   $object_id Option key.
	 * @param  array $updated   Array of updated fields.
	 */
	public function settings_notices( $object_id, $updated ) {
		if ( $object_id !== $this->key || empty( $updated ) ) {
			return;
		}
		add_settings_error( $this->key . '-notices', '', __( 'Customize updated.', 'shopify-related-products' ), 'updated' );
		settings_errors( $this->key . '-notices' );
	}

	/*Metabox for post*/
	/**
	 * Add meta box
	 *
	 * @param post $post The post object
	 */
	public function secp_add_meta_boxes( $post ){
		add_meta_box( 'secp_meta_box', __( 'Shopify Related product', 'shopify-related-products' ), array( $this, 'secp_build_meta_boxes'));
	}

	public $metaBoxFields = [
	    'secp_ad_type',
	    'secp_banner_visualization_type',
	    'secp_ad_floating',
	    'secp_ad_number_of_products',
	    'secp_ad_slide_show',
	    'secp_ad_number_of_products_per_slide',
	    'secp_shortcode',
	    'secp_product_id',
	    'secp_product_variant_id',
	    'secp_collections_ids',
	    'secp_ad_manually',
	    'secp_ad_youtube',
	    'secp_ad_title',
	    'secp_ad_subtitle',
    ];

	const AD_TYPE_ALL = 99;
	const AD_TYPE_NONE = 0;
	const AD_TYPE_SINGLE = 1;
	const AD_TYPE_COLLECTION = 2;

	/**
	 * Build custom field meta box
	 *
	 * @param post $post The post object
	 */
	public function secp_build_meta_boxes( $post ){

    wp_nonce_field( basename( __FILE__ ), 'secp_meta_box_nonce' );

    $fieldsValues = [];
    foreach($this->metaBoxFields as $metaBoxField){
        $fieldsValues[$metaBoxField] = get_post_meta( $post->ID, '_'.$metaBoxField, true );
    }

    $numberOfProductsOptions = [2,3,4,5,6,7,8,9,10,15,20,30,40];
    $numberOfProductsPerSlideOptions = [1,2,3,4,5,6];
	?>
        <div id="secp_meta_box">
            <p>Only available products will be shown</p>
            <ul>
                <li><input <?php checked( $fieldsValues['secp_ad_type'] ? $fieldsValues['secp_ad_type'] : 0, self::AD_TYPE_NONE ); ?>
						data-adtype="<?php echo self::AD_TYPE_ALL?>"
						data-role="secp_shortcode"
                        name="secp_ad_type"
                        type="radio"
                        value="<?php echo self::AD_TYPE_NONE;?>"> No ad
                </li>
				<li><input <?php checked( $fieldsValues['secp_ad_type'], self::AD_TYPE_SINGLE ); ?>
						data-adtype="<?php echo self::AD_TYPE_ALL?>"
						data-role="secp_shortcode"
                        name="secp_ad_type"
                        type="radio"
                        value="<?php echo self::AD_TYPE_SINGLE;?>"> Single product banner
                </li>
                <li><input <?php checked( $fieldsValues['secp_ad_type'], self::AD_TYPE_COLLECTION ); ?>
						data-adtype="<?php echo self::AD_TYPE_ALL?>"
						data-role="secp_shortcode"
                        name="secp_ad_type"
                        type="radio"
                        value="<?php echo self::AD_TYPE_COLLECTION;?>"> Multiple product banner
                </li>
			</ul>

			<div class="adtype-container-common hidden">
				<hr>
				<ul>
					<li><input <?php checked( $fieldsValues['secp_ad_youtube'], '1' ); ?>
							data-adtype="<?php echo self::AD_TYPE_ALL?>"
							data-role="secp_shortcode"
							name="secp_ad_youtube"
							type="checkbox"
							value="1"> Show after youtube video
					</li>
					<li><input <?php checked( $fieldsValues['secp_ad_manually'], '1' ); ?>
						data-adtype="<?php echo self::AD_TYPE_ALL?>"
						data-role="secp_shortcode"
						name="secp_ad_manually"
						value="1"
						type="checkbox"> Add shortcode in html
					</li>
				</ul>
				<hr>
			</div>

			<div class="adtype-container-<?php echo self::AD_TYPE_SINGLE?> adtype-container"  data-adtype="<?php echo self::AD_TYPE_SINGLE?>">
				<p>
					<button data-adtype="<?php echo self::AD_TYPE_SINGLE?>" class="button secp-add-shortcode shopify-selector" data-endpoint="collections" data-product-type="product">Select product</button>
					<input data-adtype="<?php echo self::AD_TYPE_SINGLE?>" data-role="secp_shortcode" type="hidden" id="secp_product_id" name="secp_product_id"  value="<?php echo $fieldsValues['secp_product_id']?>">
					<input data-adtype="<?php echo self::AD_TYPE_SINGLE?>" data-role="secp_shortcode" type="hidden" id="secp_product_variant_id" name="secp_product_variant_id"  value="<?php echo $fieldsValues['secp_product_variant_id']?>">

					<div class="secp-added-product">
					</div>

					<br>
					<label>Visualization</label>
					<ul>
						<li><input data-adtype="<?php echo self::AD_TYPE_SINGLE?>" type="checkbox" data-role="secp_shortcode" <?php checked( $fieldsValues['secp_ad_floating'], '0' ); ?> class="ad_single_product" name="secp_ad_floating" type="radio" value="0">Floating banner</li>
					</ul>
					<hr>
				</p>
			</div>

			<div class="adtype-container-<?php echo self::AD_TYPE_SINGLE?> adtype-container cf" data-adtype="<?php echo self::AD_TYPE_COLLECTION?>">
				<p>

					<button data-adtype="<?php echo self::AD_TYPE_COLLECTION?>" class="button secp-add-shortcode shopify-selector" data-endpoint="collections" data-product-type="collection">Select collection</button>
					<input data-adtype="<?php echo self::AD_TYPE_COLLECTION?>" data-role="secp_shortcode" type="hidden" id="secp_collections_ids" name="secp_collections_ids" value="<?php echo $fieldsValues['secp_collections_ids']?>">

					<div class="secp-added-collections">
					</div>

					<br>
					Total of products to show
					<select data-adtype="<?php echo self::AD_TYPE_COLLECTION?>" data-role="secp_shortcode" class="ad_multiple_product" name="secp_ad_number_of_products">
					<?php foreach($numberOfProductsOptions as $numberOfProductsOption){ ?>
						<option <?php selected( $fieldsValues['secp_ad_number_of_products'], $numberOfProductsOption ); ?>><?php echo $numberOfProductsOption; ?></option>
					<?php }?>
					</select>
					<br>
					Number of products per slide
					<select data-adtype="<?php echo self::AD_TYPE_COLLECTION?>" data-role="secp_shortcode" class="ad_multiple_product" name="secp_ad_number_of_products_per_slide">
						<?php foreach($numberOfProductsPerSlideOptions as $numberOfProductsOption){ ?>
							<option <?php selected( $fieldsValues['secp_ad_number_of_products_per_slide'], $numberOfProductsOption ); ?>><?php echo $numberOfProductsOption; ?></option>
						<?php }?>
					</select>
					<input data-role="secp_shortcode" data-adtype="<?php echo self::AD_TYPE_COLLECTION?>" class="ad_multiple_product" name="secp_ad_slide_show" type="hidden" value="1">

					<hr>
				</p>
			</div>


			<div class="adtype-container-common">
				<hr>
				<p>
					<label for="secp_ad_title">Ad title</label>
					<input data-role="secp_shortcode" data-adtype="<?php echo self::AD_TYPE_ALL?>" type="text" id="secp_ad_title" name="secp_ad_title" value="<?php echo $fieldsValues['secp_ad_title']?>">
				</p>
				<p>
					<label for="secp_ad_title">Ad subtitle</label>
					<input data-role="secp_shortcode" data-adtype="<?php echo self::AD_TYPE_ALL?>" type="text" id="secp_ad_subtitle" name="secp_ad_subtitle" value="<?php echo $fieldsValues['secp_ad_subtitle']?>">
				</p>
			</div>

			<div class="adtype-container-common hidden">
				<hr>
				<p>
					<div id="secp_shortcode_manual">
					Copy in HTML the folling text <input type="text" style="width: 100%;" rows="20" value='<?php echo self::SHORT_CODE_MANUAL_HTML?>'>
					</div>
					<input type="hidden" id="secp_shortcode" name="secp_shortcode" style="width: 100%;" rows="20">
				</p>
			</div>

		</div>
        <div id="secp_popup_content" style="display: none;"></div>
	<?php
	}

    /**
     * Store custom field meta box data
     *
     * @param int $post_id The post ID.
     */
    public function secp_save_meta_boxes( $post_id ){
        // verify meta box nonce
        if ( !isset( $_POST['secp_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['secp_meta_box_nonce'], basename( __FILE__ ) ) ){
            return;
        }

        // return if autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
            return;
        }

        // Check the user's permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ){
            return;
        }

        foreach($this->metaBoxFields as $metaBoxField){

			if(!strstr($metaBoxField, 'secp')){continue;}

            // store custom fields values
            if( isset( $_REQUEST[$metaBoxField] ) ){
                $value = $_POST[$metaBoxField];
                // save data
                update_post_meta( $post_id, '_'.$metaBoxField, $value );
            }else{
                // delete data
                delete_post_meta( $post_id, '_'.$metaBoxField );
            }
        }
    }
}

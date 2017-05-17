<?php

/**
 * Shopify Related Products Output
 * @version 0.0.9
 * @package Shopify Related Products
 */
class SECP_Output
{

    /**
     * Parent plugin class
     *
     * @var   class
     * @since 0.0.9
     */
    protected $plugin = null;

    /**
     * Has the shopify js been added?
     *
     * @var boolean
     * @since 0.0.9
     */
    private $js_added = false;

    /**
     * The current shop.
     *
     * @var boolean
     * @since 0.0.9
     */
    private $shop = false;

    /**
     * Constructor
     *
     * @since  0.0.9
     * @param  object $plugin Main plugin object.
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->hooks();
    }

    /**
     * Initiate our hooks
     *
     * @since  0.0.9
     */
    public function hooks()
    {
        add_action('wp_footer', array($this, 'auto_add_shortcode'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'load_custom_wp_admin_scripts'));
    }

    public function load_custom_wp_admin_scripts($hook)
    {
        $hooks = [
            'post.php',
            'post-new.php',
        ];

        $min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        if (!in_array($hook, $hooks)) {
            return;
        }

        wp_enqueue_script('secp-shopify-service', $this->plugin->url('assets/js/secp_service.js'));
        add_action('admin_head', array($this, 'secp_header_scripts'));
        wp_enqueue_script('secp_custom_wp_admin_js', $this->plugin->url('assets/js/secp_admin.js'));
        wp_enqueue_script('secp_mustache_js', $this->plugin->url('assets/js/mustache' . $min . '.js'));
        wp_enqueue_style('secp_styles_css', $this->plugin->url('assets/css/styles_admin.css'));
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
     * Convert array of attributes to string of html data attributes.
     *
     * @since 0.0.9
     * @param  array $args Array of attributes to convert to data attributes.
     * @return string      HTML attributes.
     */
    public function array_to_data_attributes($args)
    {
        $attributes = '';
        foreach ($args as $key => $value) {
            if (!empty($value)) {
                $attributes .= sprintf(' data-%s="%s"', esc_html($key), esc_attr($value));
            }
        }
        return $attributes;
    }


    /**
     * Get shopify embed markup.
     *
     * @since 0.0.9
     * @param  array $args data arguments.
     * @return string      HTML markup.
     */
    public function get_embed($args = [])
    {
        ob_start();
        if (count($args) <= 0) {
            return;
        }

        $min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        $shopUrl = cmb2_get_option('shopify_ecommerce_plugin_settings', 'app_shop_url');
        $templatesUrl = plugins_url('', dirname(__FILE__)) . '/assets/templates/';

        $managerOptions = [
            'adYoutube' => $args['ad_youtube'],

            'utm_source' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_utm_source'),
            'utm_medium' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_utm_medium'),
            'utm_term' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_utm_term'),
            'utm_content' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_utm_content'),
            'utm_campaign' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_utm_campaign'),

            'secp_ad_title' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_ad_title'),
            'secp_ad_subtitle' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_ad_subtitle'),

        ];

        //Config depending of type of add
        switch ($args['ad_type']) {
            case SECP_Customize::AD_TYPE_SINGLE:
                if ($args['ad_floating']) {
                    $templates = ["'secp-product-floating'"];
                    $managerOptions['displayType'] = 'product-floating';
                    $managerOptions['productId'] = $args['product_id'];
                    $managerOptions['productVariantId'] = $args['product_variant_id'];

                    $endpoint = 'product';
                } else {
                    $templates = ["'secp-product'", "'secp-product-list'"];
                    $managerOptions['displayType'] = 'product-single';
                    $managerOptions['productId'] = $args['product_id'];
                    $managerOptions['productVariantId'] = $args['product_variant_id'];
                    $endpoint = 'product';
                }
                break;
            case SECP_Customize::AD_TYPE_COLLECTION:
                $templates = ["'secp-products-list'", "'secp-product-list'"];
                $managerOptions['numberOfProductsPerSlide'] = $args['ad_number_of_products_per_slide'];
                $managerOptions['numberOfProducts'] = $args['ad_number_of_products'];
                $managerOptions['collectionId'] = $args['collections_ids'];
                $endpoint = 'collectionProducts';

                wp_enqueue_script('secp-owlcarousel-js', $this->plugin->url('vendor/owl-carousel2-2.2.0/owl.carousel.min.js'));
                wp_enqueue_style('secp-owlcarousel-theme-css', $this->plugin->url('vendor/owl-carousel2-2.2.0/assets/owl.theme.default.min.css'));
                wp_enqueue_style('secp-owlcarousel-css', $this->plugin->url('vendor/owl-carousel2-2.2.0/assets/owl.carousel.min.css'));

                break;
        }

        if ($args['ad_title']) {
            $managerOptions['secp_ad_title'] = $args['ad_title'];
        }

        if ($args['ad_subtitle']) {
            $managerOptions['secp_ad_subtitle'] = $args['ad_subtitle'];
        }

        if ($args['utm_content']) {
            $managerOptions['utm_content'] = $args['utm_content'];
        }

        if ($args['utm_campaign']) {
            $managerOptions['utm_campaign'] = $args['utm_campaign'];
        }

        if ($args['ad_manually']) {
            $managerOptions['adEmbed'] = $args['ad_manually'];
        }

        //Shopify ad manager
        wp_enqueue_script('secp-shopify-service', $this->plugin->url('assets/js/secp_service.js'));

        //Javascript template engine
        wp_enqueue_script('secp-template-engine', $this->plugin->url('assets/js/mustache' . $min . '.js'));

        //General styles
        wp_enqueue_style('secp-shopify-products', $this->plugin->url('assets/css/styles.css'));

        //If ad shows after youtube video
        if ($args['ad_youtube']) {
            wp_enqueue_script('secp-shopify-youtube', $this->plugin->url('assets/js/secp_youtube_ad.js'));
        }

        add_action('admin_head', 'add_script_config');

        ?>
        <script>
            /* <![CDATA[ */
            var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

            jQuery(document).ready(function () {
                /*Initialize all shopify service variables*/
                ShopifyService.templatesList = [<?php echo implode(',', $templates);?>];
                ShopifyService.shopUrl = '<?php echo $shopUrl;?>';
                ShopifyService.endpoint = '<?php echo $endpoint;?>';
                ShopifyService.templateUrl = '<?php echo $templatesUrl;?>';

                ShopifyService.options = {
                <?php foreach( $managerOptions as $option => $value ){ ?>
                <?php echo $option;?>:
                '<?php echo $value;?>',
                <?php } ?>
            }
                ShopifyService.init();
            });
            /* ]]> */
        </script>
        <?php

        return ob_get_clean();
    }

    /**
     * Get markup for frontend buy button iframe.
     *
     * @since 0.0.9
     * @param  array $args Arguments for buy button.
     * @return string      HTML markup.
     */
    public function get_button($args)
    {

        /**
         * Arguments for buy button data attributes
         *
         * @see https://docs.shopify.com/manual/sell-online/buy-button/edit-delete
         * @var array
         */
        $args = wp_parse_args($args, array(
            // * Provided by iframe -- product/collection
            'embed_type' => 'product',
            'product_handle' => '',
            'display_type' => '',
            'display_size' => 'compact',
            'show_product_image' => 'true',
            'show_product_price' => 'true',
        ));

        //Embed types: product | collection
        //display types: endofpost | beginingofpost | floating
        //Display sizes: compact, normal, jumbo

        if ('collection' === $args['embed_type']) {
            $args['collection_handle'] = $args['product_handle'];
        }

        $args = apply_filters('secp_product_output_args', $args);

        return $this->get_embed($args);
    }

    function secp_get_custom_fields()
    {
        $custom_fields = get_post_custom();
        $custom_fields_secp = [];
        foreach ($custom_fields as $custom_field_key => $custom_field_value) {
            if (strstr($custom_field_key, 'secp')) {
                $custom_field_key = str_replace('_secp', 'secp', $custom_field_key);
                $custom_fields_secp[$custom_field_key] = current($custom_field_value);
            }
        }
        return $custom_fields_secp;
    }

    function auto_add_shortcode()
    {
        $custom_fields = $this->secp_get_custom_fields();
        $defaultCollectionId = cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_collections_ids');

        if ($custom_fields['secp_ad_type'] > 0 || $custom_fields['secp_ad_manually']) {
            $this->do_shortcode($custom_fields['secp_shortcode']);
        }elseif($custom_fields['secp_ad_type'] == 0 && $defaultCollectionId > 0 && !$custom_fields['secp_ad_manually']){
            $shortcode = '[shopify ad_type="2" collections_ids="'.$defaultCollectionId.'" ad_number_of_products="6" ad_number_of_products_per_slide="2" ad_slide_show="1"]';
            $this->do_shortcode($shortcode);
        }
    }

    function do_shortcode($shortcode){
        echo '<div id="secp-ad-container"></div>';
        echo  do_shortcode($shortcode);
    }

    function auto_add_shortcode_content($content)
    {
        $custom_fields = $this->secp_get_custom_fields();

        if (!$custom_fields['secp_ad_manually']) {
            $content .= $custom_fields['secp_shortcode'];
        }
        return $content;
    }
}

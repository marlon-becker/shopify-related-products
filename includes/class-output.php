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
        if(get_post_meta(get_the_ID(), '_secp_no_ad', true)) { return; }
        if (count($args) <= 0) { return; }

        $shopUrl = cmb2_get_option('shopify_ecommerce_plugin_settings', 'app_shop_url');
        $templatesUrl = plugins_url('', dirname(__FILE__)) . '/assets/templates/';
        $min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        $scripts = [
            'secp-shopify-service' => 'assets/js/secp_service.js',
            'secp-template-engine' => 'assets/js/mustache' . $min . '.js'
        ];
        $css = ['secp-shopify-products' => 'assets/css/styles.css'];
        $ads = [];
        $templates = [];

        //General configurations
        $managerOptions = [
            'utm_source' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_utm_source'),
            'utm_medium' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_utm_medium'),
            'utm_term' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_utm_term'),
            'utm_content' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_utm_content'),
            'utm_campaign' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_utm_campaign'),

            'secp_ad_title' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_ad_title'),
            'secp_ad_subtitle' => cmb2_get_option('shopify_ecommerce_plugin_settings', 'secp_ad_subtitle'),
        ];

        foreach (SECP_Customize::$types as $adType => $adTypeOptions) {

            $ads[$adType] = [];
            $productId = '';
            $collectionId = '';
            $adTypeConfig = explode('_', $adType);

            switch ($adTypeOptions['select']) {
                case 'product':
                    $productId = $args['product_id_' . $adType];
                    if($productId == ''){ break; }
                    $ads[$adType]['product_id'] = $productId;
                    $ads[$adType]['product_variant_id'] = $args['product_variant_id_' . $adType];
                    $ads[$adType]['active'] = true;
                    if (strstr($adType, 'floating')) {
                        $templates['secp-product-floating'] = "'secp-product-floating'";
                    } else {
                        $templates['secp-product'] = "'secp-product'";
                        $templates['secp-product-list'] = "'secp-product-list'";
                    }
                    $ads[$adType]['endpoint'] = 'product';
                    $ads[$adType]['total_products'] = '';
                    $ads[$adType]['products_per_slide'] = '';
                    break;
                case 'collection':
                    $collectionId = $args['collections_ids_' . $adType];
                    if($collectionId == ''){ break; }
                    $ads[$adType]['collection_id'] = $collectionId;
                    $ads[$adType]['active'] = true;
                    $scripts['secp-owlcarousel-js'] = 'vendor/owl-carousel2-2.2.0/owl.carousel.min.js';
                    $css['secp-owlcarousel-theme-css'] = 'vendor/owl-carousel2-2.2.0/assets/owl.theme.default.min.css';
                    $css['secp-owlcarousel-css'] = 'vendor/owl-carousel2-2.2.0/assets/owl.carousel.min.css';
                    $templates['secp-product'] = "'secp-product'";
                    $templates['secp-product-list'] = "'secp-product-list'";
                    $templates['secp-products-list'] = "'secp-products-list'";
                    $ads[$adType]['endpoint'] = 'collectionProducts';
                    $ads[$adType]['total_products'] = $args['total_products_' . $adType];
                    $ads[$adType]['products_per_slide'] = $args['products_per_slide_' . $adType];
                    break;
            }

            if(count($ads == 0)) {

            }

            if ($adTypeOptions['titles'] === true) {
                $ads[$adType]['title'] = $args['title_' . $adType] ? $args['title_' . $adType] : $managerOptions['secp_ad_title'];
                $ads[$adType]['subtitle'] = $args['subtitle_' . $adType] ? $args['subtitle_' . $adType] : $managerOptions['secp_ad_subtitle'];
            }
            $ads[$adType]['utm_content'] = $args['utm_content_' . $adType] ? $args['utm_content_' . $adType] : $managerOptions['utm_content'];
            $ads[$adType]['utm_campaign'] = $args['utm_campaign_' . $adType] ? $args['utm_campaign_' . $adType] : $args['utm_campaign'];
            $ads[$adType]['utm_source'] = $args['utm_source_' . $adType] ? $args['utm_source_' . $adType] : $managerOptions['utm_source'];
            $ads[$adType]['utm_medium'] = $args['utm_medium_' . $adType] ? $args['utm_medium_' . $adType] : $managerOptions['utm_medium'];
            $ads[$adType]['utm_term'] = $args['utm_term_' . $adType] ? $args['utm_term_' . $adType] : $managerOptions['utm_term'];

            if($collectionId == '' && $productId == ''){ unset($ads[$adType]); continue; }

            if (strstr($adType, 'video')) {
                $scripts['secp-shopify-youtube'] = 'assets/js/secp_youtube_ad.js';
            }
            $ads[$adType]['product_type'] = $adTypeConfig[0];
            $ads[$adType]['position'] = $adTypeConfig[1];
            $ads[$adType]['loaded'] = false;
        }

        foreach($scripts as $scriptName => $scriptPath) {
            wp_enqueue_script($scriptName, $this->plugin->url($scriptPath));
        }

        foreach($css as $cssName => $cssPath) {
            wp_enqueue_style($cssName, $this->plugin->url($cssPath));
        }

        add_action('admin_head', 'add_script_config');
        /*echo '<pre>';
        print_r($ads);
        echo '</pre>';*/
        ?>
        <script>
            /* <![CDATA[ */
            var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

            jQuery(document).ready(function () {
                /*Initialize all shopify service variables*/
                ShopifyService.templatesList = [<?php echo implode(',', $templates);?>];
                ShopifyService.shopUrl = '<?php echo $shopUrl;?>';
                ShopifyService.templateUrl = '<?php echo $templatesUrl;?>';

                ShopifyService.ads = {
                    <?php foreach( $ads as $adType => $adOptions ){ ?>
                        <?php echo $adType . ':';?>{
                        <?php foreach( $adOptions as $adOptionKey => $adOptionValue ){ ?>
                        <?php echo $adOptionKey;?>:
                        '<?php echo $adOptionValue;?>',
                        <?php } ?>
                    },
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
        $this->do_shortcode($custom_fields['secp_shortcode']);
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

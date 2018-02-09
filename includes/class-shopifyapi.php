<?php
/**
 * Shopify Related Products Widget
 * @version 0.0.9
 * @package Shopify Related Products
 */

require_once dirname( __FILE__ ) . '/../vendor/cmb2/init.php';

class SECP_Shopifyapi {

	/**
	 * Parent plugin class
	 *
	 * @var   class
	 * @since 0.0.9
	 */
	protected $plugin = null;

	private $api_base_url = 'https://[api_key]:[api_password]@[api_shop].myshopify.com/admin/[action]';
	private $product_fields = 'id,images,title';

	private $api_resources_urls = array(
		'products' => '/admin/products/',
		'product' => '/admin/products/[id].json',
		'collections' => '/admin/custom_collections.json',
		'collection' => '/admin/custom_collections/[id].json',
	);

	/**
	 * Construct Api Service class.
	 *
	 * @since  0.0.9
	 */
	public function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->hooks();
	}
	private function hooks(){

        add_action( 'init', array( $this, 'init' ), 30 );

        /* Ajax actions for loading data from the API*/
        add_action( 'wp_ajax_nopriv_SECP_shopify_request', array( $this, 'SECP_shopify_request') );
        add_action( 'wp_ajax_SECP_shopify_request', array( $this, 'SECP_shopify_request') );

        add_action( 'wp_ajax_nopriv_payment_update_error', array( $this, 'payment_update_error') );
        add_action( 'wp_ajax_payment_update_error', array( $this, 'payment_update_error') );
    }

    public function init(){}

    function payment_update_error($params) {
        $logFileName = '/var/log/update_payment_errors.csv';
        $error = [];
        $error[] = date('Y-m-d H:i:s');
        $error[] = str_replace(';', '',  $_REQUEST['email']);
        $error[] = str_replace(';', '',  $_REQUEST['error']);
        $error[] = str_replace(';', '',  $_REQUEST['stripeUser']);
        $error[] = str_replace(';', '',  $_REQUEST['cardType']);
        $error[] = str_replace(';', '',  $_REQUEST['cardLength']);
        $error[] = str_replace(';', '',  $_REQUEST['first4Digits']);
        file_put_content($logFileName, implode(";", $error)."\n", FILE_APPEND);
    }

    function SECP_shopify_request($params) {
        $ShopifyApi = new SECP_Shopifyapi();
        $data = [];

        switch ( $_REQUEST['endpoint'] ) {
            case 'collection':
                if( $_REQUEST['secp_id'] > 0) {
                    $response = json_decode( $ShopifyApi->getCollection($_REQUEST['secp_id']) );
                    $data['collection'] = $this->_serializeCollections([current($response)]);
                }
            break;
            case 'collections':
                $collections['custom_collections'] = json_decode( $ShopifyApi->getCollections() );
                $collections['smart_collections'] = json_decode( $ShopifyApi->getSmartCollections() );

                $collectionsFullList = [];
                foreach( $collections as $keyCollectionType => $collectionType ) {
                    foreach( $collectionType->$keyCollectionType as $key => $collection ){
                        $collectionsFullList[] = $collection;
                    }
                }
                $data['collections'] = $this->_serializeCollections($collectionsFullList);
            break;
            case 'product':
                if( $_REQUEST['secp_id'] > 0) {
                    $response = json_decode( $ShopifyApi->getProduct($_REQUEST['secp_id']) );
                    $data['product'] = $this->_serializeProducts([$response->product]);
                }

                if($_REQUEST['secp_variant_id'] > 0){
                    $data['product'] = $this->_serializeProductVariant($data['product'], $_REQUEST['secp_variant_id']);
                }
            break;
            case 'collectionProducts':
                if( $_REQUEST['secp_id'] > 0) {
                    $params = ['productsLimit' => $_REQUEST['productsLimit']];

                    $response = json_decode( $ShopifyApi->getCollection($_REQUEST['secp_id']) );
                    $data['collection'] = $this->_serializeCollections([current($response)]);
                    $response =  $ShopifyApi->getCollectionProducts($_REQUEST['secp_id']);
                    $data['products'] = $this->_serializeProducts($response, $params);


                }
            break;
        }

        switch ($_REQUEST['format']){
            case 'json':
                $data = json_encode( $data );
            break;
        }

        echo $data;
        wp_die(); // this is required to terminate immediately and return a proper response
    }


	public function getProduct($id, $params){
		if(!is_numeric($id)){return;}
		$action = 'products/'.$id;
		return $this->getJSON($action, $params);
	}

	public function getProductVariant($id, $idVariant, $params){
		if(!is_numeric($id)){return;}
		$action = 'products/'.$id;
		return $this->getJSON($action, $params);
	}

	public function getProducts($params){
		$action = 'products';
		return $this->getJSON($action, $params);
	}

	public function getCollectionProducts($id){

        if(!is_numeric($id)){return;}
		$action = 'collects.json?collection_id='.$id;
		$response = json_decode($this->getJSON($action));

        $productsIds = [];

        foreach($response->collects as $product){
            $productsIds[(int)$product->sort_value] = $product->product_id;
        }

        $action = 'products.json?ids='.implode(',', $productsIds);
        $collectionProducts = json_decode( $this->getJSON($action) );

        foreach($collectionProducts->products as $product){
            if(false !== $key = array_search($product->id, $productsIds)){
                $productsIds[$key] = $product;
            }
        }

        return $productsIds;
    }

    public function getCollection($id, $params){
		if(!is_numeric($id)){return;}
        $types = ['custom','smart'];
        foreach($types as $type){
            $action = $type.'_collections/'.$id;
            $response = $this->getJSON($action);
            if(!array_key_exists('errors', json_decode($response))){
                break;
            }
        }
        return $response;
	}

	public function getCollections(){
		$action = 'custom_collections';
		return $this->getJSON($action);
	}

	public function getSmartCollections(){
		$action = 'smart_collections';
		return $this->getJSON($action);
	}

	public function getProductsByCollection($collections, $params){
		$acceptedSearchParams = array(
			'limit' => 50,
			'page' => 1,
			'handle' => ''
		);
		return $this->getJSON($this->api_resources_urls['collections'], $params);
	}

	public function getProductsByTag($tags, $params){
		return $this->getJSON($this->api_resources_urls['collections'], $params);
	}

	public function getJSON($action){

        $configParams= [
            'api_key' =>  cmb2_get_option( 'shopify_ecommerce_plugin_settings', 'app_key' ),
            'api_password' => cmb2_get_option( 'shopify_ecommerce_plugin_settings', 'app_password' ),
            'api_shop' => cmb2_get_option( 'shopify_ecommerce_plugin_settings', 'app_shop' )
        ];

        foreach($configParams as $key => $configParam){
            $this->api_base_url = str_replace('['.$key.']',$configParam, $this->api_base_url);
        }

		$url = str_replace('[action]', $action, $this->api_base_url);
        $url .= (strstr($url, '.json') === false ? '.json' : '');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		curl_close($ch);

		return $output;
	}

    const IMAGE_SIZES = ['large','medium','small'];
	private function _generateImageSizes($images)
    {
        $productImages = [];
        foreach($images as $image){
            $url = $image->src;
            if(!$url){continue;}
            $urlExplode = explode('.jpg', $url);
            $imageSizes = array();
            foreach(self::IMAGE_SIZES as $IMAGE_SIZE)
            {
                $imageSizes[$IMAGE_SIZE] = $urlExplode[0].'_'.$IMAGE_SIZE.'.jpg'.$urlExplode[1];
            }
            $productImages[] = $imageSizes;
        }
        return $productImages;
    }

    public function _serializeProducts( $products, $params ){
        $mapped = array();
        $productsLimit = $params['productsLimit'] ? $params['productsLimit'] : 100;

        foreach($products as $product) {
            $images = $product->images;
            $available = false;
            $minPrice = 999999;
            $quantity = 0;
            $variants = [];

            foreach ($product->variants as $variant) {
                if ($variant->inventory_quantity > 0 || strstr($product->product_type,'Demand') || strstr($product->product_type,'Digital')) {
                    $available = true;
                    $usedImages = [];
                    $quantity += $variant->inventory_quantity;
                    $minPrice = $minPrice > (float)$variant->price ? (float)$variant->price : $minPrice;
                    $vimages = $this->_generateVariantImages($variant->id, $variant->position, $images);

                    if(count($vimages)>0) {
                             $variants[] = [
                            'vid' => $variant->id,
                            'vtitle' => $variant->title,
                            'vprice' => (float)$variant->price,
                            'vquantity' => $variant->inventory_quantity > 0 ? $variant->inventory_quantity : '-',
                            'vimages' => $vimages,
                        ];
                    }
                }
            }


            if ($available === true && $product->published_at) {
                $mapped[] = [
                    'id' => $product->id,
                    'title' => $product->title,
                    'description' => $product->title,
                    'handle' => $product->handle,
                    'price' => $minPrice,
                    'quantity' => $quantity,
                    'images' => $this->_generateImageSizes($images),
                    'variants' => $variants,
                ];
            }

            if(count($mapped) >= $productsLimit){break;}
        }
        return $mapped;
    }

    public function _serializeProductVariant($product, $variantId){

        foreach ($product[0]['variants'] as $variant) {
            if($variant['vid'] == $variantId){
                $product[0]['vid'] = $variantId;
                $product[0]['images'] = $variant['vimages'];
                $product[0]['quantity'] = $variant['vquantity'];
                $product[0]['vtitle'] = $variant['vtitle'];
                return $product;
            }
        }
        return $product;
    }

    private function _generateVariantImages($id, $variantPosition, $images){
        foreach($images as $image){
            foreach($image->variant_ids as $variantId){
                if($id == $variantId) {
                    return $this->_generateImageSizes([$image]);
                }
            }
        }
        foreach($images as $image){
            if($variantPosition == $image->position){
                return $this->_generateImageSizes([$image]);
            }
        }
    }
    
    public function _serializeCollections($collections)
    {
        $mapped = array();
        foreach($collections as $collection)
        {
            //Add to list of collections only the ones with published_at value (active collections)
            if($collection->published_at){
                $image = $collection->image;
                $mapped[] = [
                    'id' => $collection->id,
                    'title' => $collection->title,
                    'handle' => $collection->title,
                    'images' => $this->_generateImageSizes([$image]),
                ];
            }
        }

        return $mapped;
    }
}

<?php

//$dbname = 'pfc_database';
//$dbuser = 'pfc_wordpress';
//$dbpass = '2=KMP3vBv9Hc*39';
//$dbhost = '127.0.0.1';

$dbname = 'wp_shopify';
$dbuser = 'root';
$dbpass = 'Becker82';
$dbhost = '127.0.0.1';

$secpPostDataMigration = new SecpPostDataMigration($dbhost, $dbuser, $dbpass, $dbname);
$secpPostDataMigration->process();

class SecpPostDataMigration{

    const AD_TYPE_DEFAULT = 0;
    const AD_TYPE_SINGLE = 1;
    const AD_TYPE_COLLECTION = 2;
    const AD_TYPE_NONE = 3;

    private $attributes = [
    'numberOfProducts' => '',
    'numberOfProductsPerSlide' => '',
    'collectionId' => '',
    'productId' => '',
    'productIdVariant' => '',
    'utmContent' => '',
    'utmCampaign' => '',
    'title' => '',
    'subtitle' => '',
    'video' => '',
    'type' => '',
    'floating' => '',
    'shortcode' => []
    ];

    private $mysqli;
    private $querys = [];

    public function __construct($host, $user, $pass, $dbname)
    {
        $this->mysqli = new mysqli($host, $user, $pass, $dbname, 3306);
        if($this->mysqli->connect_error)
        {
            die("$this->mysqli->connect_errno: $this->mysqli->connect_error");
        }
    }

	public function generateShortcode($postId)
    {
        $shortcode = '[shopify ';
        foreach($this->attributes['shortcode'] as $field => $value) {
            $shortcode .= str_replace('_secp_', '' ,$field).'="'.$value.'" ';
        }
        $shortcode .= ']';
        echo '<br>'.$shortcode.'<br>';

        $query = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES('%s', '%s', '%s')";
        $this->query(sprintf($query, $postId, '_secp_shortcode', $shortcode));
    }
    
    public function resetAttributes() 
    {
        $this->querys = [];
        foreach($this->attributes as $key => $attribute) {
            $this->attributes[$key] = '';
        }
    }

    public function setType($value)
    {
	    $this->attributes['type'] = $value;
    }

    public function setNumberOfProducts($value)
    {
         $this->attributes['numberOfProducts'] = $value;
    }

    public function setNumberOfProductsPerSlide($value)
    {
        $this->attributes['numberOfProductsPerSlide'] = $value;
    }

    public function setCollectionId($value)
    {
        $this->attributes['collectionId'] = $value;
    }

    public function setProductId($value)
    {
        $this->attributes['productId'] = $value;
    }

    public function setProductIdVariant($value)
    {
        $this->attributes['productIdVariant'] = $value;
    }

    public function setTitle($value)
    {
        $this->attributes['title'] = $value;
    }

    public function setSubtitle($value)
    {
        $this->attributes['subtitle'] = $value;
    }

    public function setUtmContent($value)
    {
        $this->attributes['utmContent'] = $value;
    }

    public function setUtmCampaign($value)
    {
        $this->attributes['utmCampaign'] = $value;
    }

    public function setVideo($value)
    {
        $this->attributes['video'] = $value;
    }

    public function setFloating($value)
    {
        $this->attributes['floating'] = $value;
    }

    public function process()
    {
        $consultaPosts = "SELECT DISTINCT(post_id) FROM wp_postmeta WHERE meta_key LIKE '%_secp%'";
        $consultaMetasPost = "SELECT * FROM wp_postmeta WHERE meta_key LIKE '%_secp%' AND post_id = ?";

        $resultadosPosts = $this->query($consultaPosts);
        while ($post = $resultadosPosts->fetch_assoc())
        {

            $resultadosMetasPosts = $this->query(
                $consultaMetasPost,
                ['post_id' => ['type' => 's', 'value' => $post['post_id']]]
            );

            while ($postMeta = $resultadosMetasPosts->fetch_assoc())
            {
                $metas[$postMeta['meta_key']] = $postMeta['meta_value'];
                $value = $postMeta['meta_value'];
                switch ($postMeta['meta_key']) {
                    case '_secp_ad_number_of_products':
                        $this->setNumberOfProducts($value);
                        break;
                    case '_secp_ad_type':
                        $this->setType($value);
                        break;
                    case '_secp_ad_number_of_products_per_slide':
                        $this->setNumberOfProductsPerSlide($value);
                        break;
                    case '_secp_collections_ids':
                        $this->setCollectionId($value);
                        break;
                    case '_secp_ad_youtube':
                        $this->setVideo($value);
                        break;
                    case '_secp_ad_title':
                        $this->setTitle($value);
                        break;
                    case '_secp_ad_subtitle':
                        $this->setSubtitle($value);
                        break;
                    case '_secp_product_variant_id':
                        $this->setProductIdVariant($value);
                        break;
                    case '_secp_ad_floating':
                        $this->setFloating($value);
                        break;
                    case '_secp_product_id':
                        $this->setProductId($value);
                        break;
                    case '_secp_ad_manually':
                        break;
                    case '_secp_utm_content':
                        $this->setUtmContent($value);
                        break;
                    case '_secp_utm_campaign':
                        $this->setUtmCampaign($value);
                        break;
                }
            }


            $this->deleteNewMetas($post['post_id']);
            $this->createQuerys($post['post_id']);
            $this->generateShortcode($post['post_id']);
            $this->deleteOldMetas($post['post_id']);
            $this->resetAttributes();
        }
    }

    public function createQuerys($postId)
    {

        $query = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES('%s', '%s', '%s')";

        $position = '_bottom';
        if($this->attributes['type'] == self::AD_TYPE_SINGLE) {
            $type = 'product';
            if($this->attributes['floating']){
                $position = '_floating';
            }
            if($this->attributes['video']){
                $position = '_video';
            }

            $type = $type.$position;
            $fieldId = '_secp_product_id_'.$type;
            $this->addToShortcode($fieldId, $this->attributes['productId']);
            $this->setQuery(sprintf($query, $postId, $fieldId, $this->attributes['productId']));
            $fieldId = '_secp_product_variant_id_'.$type;
            $this->addToShortcode($fieldId, $this->attributes['productIdVariant']);
            $this->setQuery(sprintf($query, $postId, $fieldId, $this->attributes['productIdVariant']));
        }

        if($this->attributes['type'] == self::AD_TYPE_COLLECTION) {
            $type = 'collection';
            if($this->attributes['video']){
                $position = '_video';
            }

            $type = $type.$position;
            $fieldId = '_secp_total_products_'.$type;
            $this->addToShortcode($fieldId, $this->attributes['numberOfProducts']);
            $this->setQuery(sprintf($query, $postId, $fieldId, $this->attributes['numberOfProducts']));
            $fieldId = '_secp_products_per_slide_'.$type;
            $this->addToShortcode($fieldId, $this->attributes['numberOfProductsPerSlide']);
            $this->setQuery(sprintf($query, $postId, $fieldId, $this->attributes['numberOfProductsPerSlide']));
            $fieldId = '_secp_collections_ids_'.$type;
            $this->addToShortcode($fieldId, $this->attributes['collectionId']);
            $this->setQuery(sprintf($query, $postId, $fieldId, $this->attributes['collectionId']));
        }

        $fieldId = '_secp_utm_content_'.$type;
        $this->addToShortcode($fieldId, $this->attributes['utmContent']);
        $this->setQuery(sprintf($query, $postId, $fieldId, $this->attributes['utmContent']));

        $fieldId = '_secp_utm_campaign_'.$type;
        $this->addToShortcode($fieldId, $this->attributes['utmCampaign']);
        $this->setQuery(sprintf($query, $postId, $fieldId, $this->attributes['utmCampaign']));

        $fieldId = '_secp_title_'.$type;
        $this->addToShortcode($fieldId, $this->attributes['title']);
        $this->setQuery(sprintf($query, $postId, $fieldId, $this->attributes['title']));

        $fieldId = '_secp_subtitle_'.$type;
        $this->addToShortcode($fieldId, $this->attributes['subtitle']);
        $this->setQuery(sprintf($query, $postId, $fieldId, $this->attributes['subtitle']));

        foreach($this->querys as $query) {
            $this->query($query);
        }
       echo "<br>";
    }

    public function addToShortcode($field, $value)
    {
        $this->attributes['shortcode'][$field] = $value;
    }

    public function setQuery($sql)
    {
        $this->querys[] = $sql;
    }

    public function deleteNewMetas($postId)
    {
        $params = [
            '%_product_video',
            '%_collection_video',
            '%_product_bottom' ,
            '%_product_floating' ,
            '%_product_top',
            '%_collection_top',
            '%_collection_bottom',
            '%_secp_no_ad',
            '%_secp_shortcode',
    ];
        $query = "DELETE FROM wp_postmeta WHERE meta_key LIKE '%s' AND post_id = %s";

        foreach($params as $param) {
            $this->delete(
                sprintf($query, $param, $postId)
            );
        }
    }

    public function deleteOldMetas($postId)
    {
        $query = "DELETE FROM wp_postmeta WHERE meta_key LIKE '%_secp%' AND post_id = ?";

    }

    private function query($sql, $params = [])
    {
        $sentencia = $this->mysqli->stmt_init();

        echo $sql.'<br>';
        if(!$sentencia->prepare($sql))
        {
            die( "Falló la preparación de una de las sentencias\n" );
        }
        else {
            foreach($params as $type => $values) {
                $sentencia->bind_param($values['type'], $values['value']);
            }
            $sentencia->execute();
            return  $sentencia->get_result();
        }
        $sentencia->close();
    }

    private function delete($sql, $params = [])
    {
        $sentencia = $this->mysqli->stmt_init();

        echo $sql.'<br>';
        if(!$sentencia->prepare($sql))
        {
            die( "Falló la preparación de una de las sentencias\n" );
        }
        else {
            foreach($params as $type => $values) {
                $sentencia->bind_param($values['type'], $values['value']);
            }

            $sentencia->execute();
        }
        $sentencia->close();
    }

    function __destruct()
    {
        $this->mysqli->close();
    }
}
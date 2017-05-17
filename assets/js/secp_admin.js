jQuery(document).ready(function()
{
    jQuery('[data-role=secp_shortcode]').change(function(){
        secp_shortcode.generate();
    });

    secp_shortcode.generate();
    secp_admin.updateProducts();

    secp_shortcode.switchShortcodeRead(jQuery("[name=secp_ad_manually]").prop('checked') ? false : true);

    jQuery("[name=secp_ad_manually]").click(function(){
        secp_shortcode.switchShortcodeRead(jQuery(this).prop('checked') ? false : true);
    })

    jQuery('.shopify-selector').click(function(e)
    {
        e.preventDefault();
        var url = '/wp-admin/admin-ajax.php';
        var data =
        {
            'action': 'SECP_shopify_request',
            'endpoint':jQuery(this).data('endpoint'),
            'format': 'json'
        };

        var callback = '';
        switch(jQuery(this).data('product-type')){
            case 'collection':
                callback = secp_admin.showCollectionList
                break;
            case 'product':
                callback = secp_admin.showProductList
                break;
        }

        var html = Mustache.render(ShopifyService.templates['secp-admin-product-selection'], '', []).replace(/^\s*/mg, '');
        jQuery('#secp_popup_content').html(html);
        tb_show('Product selection', '#TB_inline?width=600&height=500&inlineId=secp_popup_content');

        jQuery.ajax
        (
            {
                url: url,
                type: "POST",
                //some times you cant try this method for sending action variable
                //action : 'report_callback',
                data: data,
                success: callback,
                error: function() {
                    console.log("SECP Error loading data...");
                }
            }
        );

        return false;
    })
})


var secp_shortcode =
{
    generate: function()
    {
        secp_shortcode.switchAdFields(jQuery('[name=secp_ad_type]:checked').val() );
        var fields = {};
        jQuery('[data-role=secp_shortcode]').each(function()
        {
            var  $field = jQuery(this);
            var fieldName = $field.attr('name').replace('secp_','');
            var value = '';

            if($field.is(':disabled')){return;}

            switch($field.prop("tagName")){
                case 'INPUT':
                    switch ($field.attr("type") ){
                        case 'radio':
                            if($field.prop('checked')){
                                value =$field.val();
                            }
                            break;
                        case 'checkbox':
                            if($field.prop('checked')){
                                value = 1;
                            }
                            break;
                            break;
                        default:
                            value =$field.val();
                    }
                    break;
                default:
                    value =$field.val();

            }

            if(value != ''){
                fields[fieldName] = value;
            }
        });

        var shortcodeFields = [];
        for(var i in fields)
        {
            shortcodeFields.push(i+'='+'"'+fields[i]+'"');
        }
        jQuery('#secp_shortcode').val('[shopify '+shortcodeFields.join(' ')+']');
    },
    switchAdFields: function(ad_type)
    {
        jQuery('.adtype-container').each(function(){
            if(jQuery(this).data('adtype')!= ad_type ){
                jQuery(this).stop().slideUp();
            }else{
                jQuery(this).stop().slideDown();
            }
        });

        console.log('adtype: '+ad_type)
        if(ad_type == 0)
        {
            jQuery('.adtype-container-common').stop().slideUp();
        }else
        {
            jQuery('.adtype-container-common').stop().slideDown();
        }

        jQuery('[data-adtype='+ad_type+']').stop().slideDown();
    },
    switchShortcodeRead: function( switchValue )
    {
        if(switchValue){
            jQuery('#secp_shortcode_manual').slideUp();
        }else{
            jQuery('#secp_shortcode_manual').slideDown();;
        }
    }
}

var secp_admin = {
    addProduct: function(element) {
        jQuery('#secp_product_id').val(jQuery(element).data('shopify-id'));
        jQuery('#secp_product_variant_id').val(jQuery(element).data('shopify-variant-id'));
        secp_admin.updateProducts();
        secp_shortcode.generate();
        tb_remove()
    },
    addCollection: function(element) {
        jQuery('#secp_collections_ids').val(jQuery(element).data('shopify-id'));
        secp_admin.updateProducts();
        secp_shortcode.generate();
        tb_remove()
    },
    updateProducts: function(){
        var productId = jQuery('#secp_product_id').val();
        var productVariantId = jQuery('#secp_product_variant_id').val();

        if( productId > 0 ){
            jQuery('.secp-added-product').html('<div class="secp-loader"></div>');
            secp_api.request({secp_id: productId, secp_variant_id: productVariantId, 'endpoint':'product', callback: 'secp_admin.addSingleProduct'});
        }

        var collectionId =jQuery('#secp_collections_ids').val();
        if( collectionId > 0 ){
            jQuery('.secp-added-collections').html('<div class="secp-loader"></div>');
            secp_api.request({secp_id: collectionId, 'endpoint':'collection', callback: 'secp_admin.addSingleCollection'});
        }
    },
    addSingleProduct: function(response){
        var template = ShopifyService.templates['secp-admin-product-list'];
        var html = Mustache.render(template, response.product[0]).replace(/^\s*/mg, '');
        jQuery('.secp-added-product').find('.secp-loader').remove();
        jQuery('.secp-added-product').html(html);
    },
    addSingleCollection: function(response){
        var template = ShopifyService.templates['secp-admin-collection'];
        var html = Mustache.render(template, response.collection[0]).replace(/^\s*/mg, '');
        jQuery('.secp-added-collections').find('.secp-loader').remove();
        jQuery('.secp-added-collections').html(html);
    },
    showCollectionList: function(response){
        secp_admin.showCollections(response, 'addCollection');
    },
    showProductList: function(response){
        secp_admin.showCollections(response, 'addProduct');
    },
    showCollections: function(response, type){
        jQuery('#secp-admin-collections-container').find('.secp-loader').remove();
        var response =jQuery.parseJSON(response);

        switch(type){
            case 'addCollection':
                var partials = {"secp-admin-collection-list": ShopifyService.templates['secp-admin-collection-list']};
                break;
            case 'addProduct':
                var partials = {"secp-admin-collection-list": ShopifyService.templates['secp-admin-collection-products-list']};
                break;
        }

        var template = ShopifyService.templates['secp-admin-collections-list'];
        var html = Mustache.render(template, response, partials).replace(/^\s*/mg, '');
        jQuery('#secp-admin-products-container').hide();
        jQuery('#secp-admin-collections-container').html(html).show();
    }
    ,
    showProducts: function(response){
        jQuery('#secp-admin-collections-container').find('.secp-loader').remove();
        var partials = {
            "secp-admin-product-list": ShopifyService.templates['secp-admin-product-select-list'],
            "secp-admin-variant-list": ShopifyService.templates['secp-admin-variant-select-list']
        };
        var template = ShopifyService.templates['secp-admin-products-list'];
        var html = Mustache.render(template, response, partials).replace(/^\s*/mg, '');
        jQuery('#secp-admin-collections-container').hide();
        jQuery('#secp-admin-products-container').html(html).show().find('secp-loader').remove();
    },
    loadCollectionProducts: function(element){
        var collectionId = jQuery(element).data('shopify-id');
        jQuery('#secp-admin-collections-container').hide();
        jQuery('#secp-admin-products-container').html('<div class="secp-loader"></div>').show();
        secp_api.request(
            {
                secp_id: collectionId,
                'endpoint': 'collectionProducts',
                callback: 'secp_admin.showProducts'
            })
    },
    backToCollections: function(){
        jQuery('#secp-admin-collections-container').show();
        jQuery('#secp-admin-products-container').hide();
        return false;
    }
}

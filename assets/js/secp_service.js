
ShopifyService = {

    templates: [],       //The templates setted in this object will be loaded on init
    templatesLoaded: [], //Control of what templates have been loaded
    templatesList: [],   //Way to access loaded templates
    shopUrl: '',         //Shopify shop url
    endpoint: '',        //Type of endpoint to call product|products-list|collectionProducts
    templateUrl: '',     //Url where to load the templates
    options: {},         //Option for the calls
    preloadData: '',
    ads: '',
    init: function () {
        jQuery('body').append('<div id="ShopifyServiceTemplates" style="display:none"></div>');
        console.log('Shopify Plugin - Loading templates...');
        this.loadNextTemplate();
    },
    loadNextTemplate: function () {
        if (this.templatesList.length > 0) {
            if (typeof this.templatesList[0] != 'undefined') {
                this.loadTemplate(this.templatesList[0]);
                this.templatesList.splice(0, 1);
            }
        } else {
            console.log('Shopify Plugin - Loading ads...');
            this.loadAds();
        }
    },
    loadTemplate: function (template) {
        var url = this.templateUrl + template + '.html';
        var id = 'secp-template-' + template;
        jQuery.get(url, function (template) {
            var id = jQuery(template).attr('id');
            ShopifyService.templates[id] = jQuery(template).html();
            console.log('Template loaded: '+id);
            ShopifyService.loadNextTemplate();
        });
    },
    loadAds: function() {

        for(x in this.ads) {
            if(!this.ads[x].loaded) {
                this.ads[x].loaded = true;
                this.loadAd(this.ads[x]);
                return;
            }

        }
    },
    loadAd: function(ad) {
        console.group('Loading ad');
        console.log('Type: '+ad.product_type);
        console.log('Position: '+ad.product_position);
        switch (ad.endpoint) {
            case 'product':
                secp_api.request(
                {
                    secp_id: ad.product_id,
                    product_type: ad.product_type,
                    position: ad.position,
                    title: ad.title,
                    subtitle: ad.subtitle,
                    secp_variant_id: ad.product_variant_id,
                    utm_content: ad.utm_content,
                    utm_campaign: ad.utm_campaign,
                    utm_source: ad.utm_source,
                    utm_medium: ad.utm_medium,
                    utm_term: ad.utm_term,
                    'endpoint': ad.endpoint,
                    callback: 'ShopifyService.renderAd'
                })
            break;
            case 'collectionProducts':
                secp_api.request(
                {
                    secp_id: ad.collection_id,
                    product_type: ad.product_type,
                    position: ad.position,
                    title: ad.title,
                    subtitle: ad.subtitle,
                    product_limit: ad.number_of_products,
                    total_products: ad.total_products,
                    products_per_slide: ad.products_per_slide,
                    utm_content: ad.utm_content,
                    utm_campaign: ad.utm_campaign,
                    utm_source: ad.utm_source,
                    utm_medium: ad.utm_medium,
                    utm_term: ad.utm_term,
                    'endpoint': ad.endpoint,
                    callback: 'ShopifyService.renderAd'
                })
            break;
        }
        console.groupEnd();
    },
    renderAd: function(response) {
        if(response.product_type == 'product') {
            ShopifyRender.renderProduct(response);
        } else {
            ShopifyRender.renderProductList(response);
        }
    },
    openProductUrl: function (obj) {
        var handle = obj.data('handle');
        var utm_source = obj.data('utm_source');
        var utm_medium = obj.data('utm_medium');
        var utm_term = obj.data('utm_term');
        var utm_content = obj.data('utm_content');
        var utm_campaign = obj.data('utm_campaign');

        var url = this.shopUrl + '/products/' + handle;
        var utmParams = [];

        utmParams.push('utm_source='+utm_source);
        utmParams.push('utm_medium='+utm_medium);
        utmParams.push('utm_term='+utm_term);
        utmParams.push('utm_content='+utm_content);
        utmParams.push('utm_campaign='+utm_campaign);

        url = url + '?' + utmParams.join('&');
        window.open(url, '_blank');
        window.focus();
    },
}


ShopifyRender = {
    renderProduct: function (response) {
        if (response.product.length == 0) {
            return;
        }
        if (response.position != 'video') {
            if (response.position == 'floating') {
                var html = this.renderResponse(response, 'secp-product-floating')
                jQuery('body').append(html);
                setTimeout('ShopifyRender.show()', 100);
            } else {
                var template = ShopifyService.templates['secp-product'];
                var partials = {"secp-product-list": ShopifyService.templates['secp-product-list']};
                var html = Mustache.render(template, response, partials).replace(/^\s*/mg, '');

                if(response.position == 'top') {
                    if(jQuery('.site-content').length > 0) {
                        jQuery('.site-content').prepend(html)
                    }
                } else {
                    if(jQuery('.site-content').length > 0) {
                        jQuery('.site-content').append(html)
                    } else {
                        jQuery('footer:first').before(html);
                    }
                }
                ShopifyRender.show();
            }
        } else {
            var template = ShopifyService.templates['secp-product'];
            var partials = {"secp-product-list": ShopifyService.templates['secp-product-list']};
            var html = Mustache.render(template, response, partials).replace(/^\s*/mg, '');
            ShopifyRender.showInYoutubeVideo(html, 'youtube-product');
        }
        jQuery('.secp-product-close').click(function (e) {
            e.preventDefault();
            jQuery(this).parent().removeClass('secp-product-loaded');
            return false;
        });
    ShopifyService.loadAds();
    },
    renderProductList: function (response) {
        jQuery(window).on('resize', function () {
            ShopifyRender.updateSize();
        });

        console.group('Render product list');
        var template = ShopifyService.templates['secp-products-list'];
        var partials = {"secp-product-list": ShopifyService.templates['secp-product-list']};
        var html = Mustache.render(template, response, partials).replace(/^\s*/mg, '');

        console.log('position: '+response.position);

        if (response.position != 'video') {
            if(response.position == 'top') {
                if(jQuery('.site-content').length > 0) {
                    jQuery('.site-content').prepend(html)
                }
            } else {
                if(jQuery('.site-content').length > 0) {
                    jQuery('.site-content').append(html)
                } else {
                    jQuery('footer:first').before(html);
                }
            }
        } else {
            ShopifyYoutubeAdd.init();
            ShopifyRender.showInYoutubeVideo(html, 'youtube-collection');
        }

        console.log('Products per slide: '+response.products_per_slide);
        var sizeOptions = {
            0: { items: 3 },
            600: { items: 3 },
            1000: { items: response.products_per_slide > 0 ? response.products_per_slide : 2 }
        };

        //If slider option is activated
        jQuery('.owl-carousel').owlCarousel({
            loop: true,
            margin: 35,
            nav: true,
            responsive: sizeOptions
        });
        console.groupEnd();
        ShopifyRender.show();
        ShopifyRender.updateSize();
        ShopifyService.loadAds();
    },
    showVideoAd: function() {
        jQuery('.secp-container-youtube-ad').show();
        jQuery('#secp-ad-container-youtube-product, #secp-ad-container-youtube-collection').show();
        jQuery('#pfc-video').hide();
        this.showAd();
    },
    adShowed: false,
    showInYoutubeVideo: function (html, youtubeId) {
        console.log('Show after video...');
        if (this.adShowed === true) {
            return;
        } else {
            this.adShowed = true;
        }

        jQueryvideo = jQuery('#pfc-video');
        var $fullContainerId = 'secp-ad-container-' + youtubeId;
        var $adId = 'secp-ad-' + youtubeId;

        if (jQuery('#' + $fullContainerId).length == 0) {
            var jQueryfullContainer = jQuery('<div id="' + $fullContainerId + '"></div>');
            jQueryvideo.after(jQueryfullContainer);

        } else {
            jQueryfullContainer = jQuery('#' + $fullContainerId);
        }

        var height = jQueryvideo.outerHeight();
        var $container = jQuery('<div class="secp-container-youtube-ad" id="' + $adId + '"></div>');

        $container.height( height ).css('margin-top','-30px');
        $container.html(html);
        $container.find('.secp-product-close')
            .click(function (e) {
                jQuery('#secp-ad-container-youtube-product, #secp-ad-container-youtube-collection').hide();
                jQuery('#pfc-video').show();
            });

        jQueryfullContainer.append($container);
    },
    renderResponse: function (response, templateName) {
        var template = ShopifyService.templates[templateName];
        return Mustache.render(template, response.product[0]).replace(/^\s*/mg, '');
    },
    show: function () {
        setTimeout('ShopifyRender.showAd()', 200);

        jQuery('.secp-product-close').click(function (e) {
            e.preventDefault();
            jQuery(this).parent().fadeOut(function () {
                jQuery(this).remove();
            });
            return false;
        })
    },
    showAd: function(){
        jQuery('.secp-product')
            .unbind('click')
            .click(function () {
                ShopifyService.openProductUrl(jQuery(this));
                return false;
            });
        jQuery('.secp-ad')
            .addClass('secp-product-loaded');
    },
    updateSize: function(){
        jQuery('.secp-ad').each(function(){
            var firstAd = jQuery(this).find('.owl-item:first');
            var width = firstAd.width();
            jQuery(this).removeClass('secp-ad--s').removeClass('secp-ad--m').removeClass('secp-ad--l').removeClass('secp-ad--xl')

            if (width > 400){
                jQuery(this).addClass('secp-ad--xl');
            }else if(width > 250){
                jQuery(this).addClass('secp-ad--l');
            }else if(width > 150){
                jQuery(this).addClass('secp-ad--m');
            }else{
                jQuery(this).addClass('secp-ad--s');
            }
        });
    },
    setPreloadData: function(response){
        console.log('setPreloadData');
        console.log(response);
        ShopifyService.preloadData = response;
    }
}

function executeFunctionByName(functionName, context /*, args */) {
    var args = [].slice.call(arguments).splice(2);
    var namespaces = functionName.split(".");
    var func = namespaces.pop();
    for (var i = 0; i < namespaces.length; i++) {
        context = context[namespaces[i]];
    }
    return context[func].apply(context, args);
}


var secp_api = {
    getCallback: function (response, callback) {
        console.log(response);
    },
    request: function (options) {
        var defaults = {
            'action': 'SECP_shopify_request',
            'endpoint': '',
            'format': 'json',
            'callback': '',
            'position': '',
            'product_type': '',
            'title': '',
            'subtitle': '',
            'total_products': '',
            'products_per_slide': '',
        };
        options = jQuery.extend(defaults, options);
        options.productsLimit = options.total_products;
        console.log('SECP Plugin: Loading product data');
        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            //some times you cant try this method for sending action variable
            //action : 'report_callback',
            data: options,
            success: function (response) {
                response = jQuery.parseJSON(response.replace('not authenticated', ''));
                response.position = options.position;
                response.product_type = options.product_type;
                response.title = options.title;
                response.subtitle = options.subtitle;
                response.total_products = options.total_products;
                response.products_per_slide = options.products_per_slide;
                response.utm_content = options.utm_content;
                response.utm_campaign = options.utm_campaign;
                response.utm_source = options.utm_source;
                response.utm_medium = options.utm_medium;
                response.utm_term = options.utm_term;

                if (options.callback) {
                    executeFunctionByName(options.callback, window, response);
                }
            },
            error: function () {
                console.log("SECP Error loading data...");
            }
        });
    }
}

/*secp_api.request( { secp_id: 3810743109, 'endpoint':'product', callback: 'secp_api.getCallback'} );
 secp_api.request( { secp_id: 69566853, 'endpoint':'collection', callback: 'secp_api.getCallback' } );
 secp_api.request( { 'endpoint':'collections', callback: 'secp_api.getCallback' } );
 secp_api.request( { secp_id: 69566853, 'endpoint':'collectionProducts', callback: 'secp_api.getCallback'} );
 */




ShopifyService = {

    templates: [],       //The templates setted in this object will be loaded on init
    templatesLoaded: [], //Control of what templates have been loaded
    templatesList: [],   //Way to access loaded templates
    shopUrl: '',         //Shopify shop url
    endpoint: '',        //Type of endpoint to call product|products-list|collectionProducts
    templateUrl: '',     //Url where to load the templates
    options: {},         //Option for the calls
    preloadData: '',
    init: function () {
        jQuery('body').append('<div id="ShopifyServiceTemplates" style="display:none"></div>');
        this.loadNextTemplate();
    },
    openProductUrl: function (handle) {
        var url = this.shopUrl + '/products/' + handle;
        var utmParams = [];

        utmParams.push('utm_source='+this.options.utm_source);
        utmParams.push('utm_medium='+this.options.utm_medium);
        utmParams.push('utm_term='+this.options.utm_term);
        utmParams.push('utm_content='+this.options.utm_content);
        utmParams.push('utm_campaign='+this.options.utm_campaign);

        url = url + '?' + utmParams.join('&');
        window.open(url, '_blank');
        window.focus();
    },
    loadNextTemplate: function () {
        if (this.templatesList.length > 0) {
            if (typeof this.templatesList[0] != 'undefined') {
                this.loadTemplate(this.templatesList[0]);
                this.templatesList.splice(0, 1);
            }
        } else if (this.endpoint) {
            if (!this.options.adYoutube) {
                this.showAd();
            }
        }
    },
    loadData: function(){
        switch (this.endpoint) {
            case 'product':
                secp_api.request(
                    {
                        secp_id: this.options.productId,
                        secp_variant_id: this.options.productVariantId,
                        'endpoint': this.endpoint,
                        callback: 'ShopifyRender.setPreloadData'
                    })
                break;
            case 'collectionProducts':
                secp_api.request(
                    {
                        secp_id: this.options.collectionId,
                        productsLimit: ShopifyService.options.numberOfProducts,
                        'endpoint': this.endpoint,
                        callback: 'ShopifyRender.setPreloadData'
                    })
                break;
        }
    },
    showAd: function (options) {
        switch (this.endpoint) {
            case 'product':
                if (!ShopifyService.options.adYoutube) {
                    secp_api.request(
                        {
                            secp_id: this.options.productId,
                            secp_variant_id: this.options.productVariantId,
                            'endpoint': this.endpoint,
                            callback: 'ShopifyRender.renderProduct'
                        });
                }else{
                    console.log(ShopifyService.preloadData);
                    if(ShopifyService.preloadData != ''){
                        ShopifyRender.renderProduct(ShopifyService.preloadData)
                    }
                }
                break;
            case 'collectionProducts':
                if (!ShopifyService.options.adYoutube) {
                    secp_api.request(
                        {
                            secp_id: this.options.collectionId,
                            productsLimit: ShopifyService.options.numberOfProducts,
                            'endpoint': this.endpoint,
                            callback: 'ShopifyRender.renderProductList'
                        })
                    break;
                }else{
                    console.log(ShopifyService.preloadData);
                    if(ShopifyService.preloadData != '') {
                        ShopifyRender.renderProductList(ShopifyService.preloadData)
                    }
                }
        }
    },
    loadTemplate: function (template) {
        var url = this.templateUrl + template + '.html';
        var id = 'secp-template-' + template;
        jQuery.get(url, function (template) {
            var id = jQuery(template).attr('id');
            ShopifyService.templates[id] = jQuery(template).html();
            ShopifyService.loadNextTemplate();
        });
    }
}

ShopifyRender = {
    renderProduct: function (response) {
        if (response.product.length == 0) {
            return;
        }
        if (!ShopifyService.options.adYoutube) {

            if (ShopifyService.options.displayType == 'product-floating') {
                var html = this.renderResponse(response, 'secp-product-floating')
                jQuery('body').append(html);
                setTimeout('ShopifyRender.show()', 100);
            } else {
                var template = ShopifyService.templates['secp-product'];
                var partials = {"secp-product-list": ShopifyService.templates['secp-product-list']};
                var html = Mustache.render(template, response, partials).replace(/^\s*/mg, '');

                if (ShopifyService.options.adEmbed) {
                    jQuery('.secp-ad-manual-container').append(html);
                } else {
                    if(jQuery('.site-content').length > 0){
                        jQuery('.site-content').append(html)
                    }else{
                        jQuery('footer:first').before(html);
                    }
                }
                ShopifyRender.show();
            }
        } else {

            var template = ShopifyService.templates['secp-product'];
            var partials = {"secp-product-list": ShopifyService.templates['secp-product-list']};
            var html = Mustache.render(template, response, partials).replace(/^\s*/mg, '');

            ShopifyRender.showInYoutubeVideo(html, ShopifyService.options.youtubeId);
        }
        jQuery('.secp-product-close').click(function (e) {
            e.preventDefault();
            jQuery('.secp-ad').removeClass('secp-product-loaded');
            return false;
        })

    },
    renderProductList: function (response) {

        jQuery( window ).on('resize', function(){
            ShopifyRender.updateSize();
        });

        var template = ShopifyService.templates['secp-products-list'];
        var partials = {"secp-product-list": ShopifyService.templates['secp-product-list']};
        var html = Mustache.render(template, response, partials).replace(/^\s*/mg, '');

        if (!ShopifyService.options.adYoutube) {
            if (ShopifyService.options.adEmbed) {
                jQuery('.secp-ad-manual-container').append(html);
            } else {

                if(jQuery('.site-content').length > 0){
                    jQuery('.site-content').append(html)
                }else{
                    jQuery('footer:first').before(html);
                }
            }

            var sizeOptions = {
                0: { items: 1 },
                600: { items: 2 },
                1000: { items: ShopifyService.options.numberOfProductsPerSlide }
            };

        } else {
            ShopifyYoutubeAdd.init();
            ShopifyRender.showInYoutubeVideo(html, ShopifyService.options.youtubeId);

            var sizeOptions = {
                0: { items: 3 },
                600: { items: 3 },
                1000: { items: ShopifyService.options.numberOfProductsPerSlide }
            };

        }
        //If slider option is activated
        jQuery('.owl-carousel').owlCarousel({
            loop: true,
            margin: 35,
            nav: true,
            responsive: sizeOptions
        });

        ShopifyRender.show();
        ShopifyRender.updateSize();

    },
    adShowed: false,
    showInYoutubeVideo: function (html) {

        if (this.adShowed === true) {
            return;
        } else {
            this.adShowed = true;
        }

        jQueryvideo = jQuery('#' + ShopifyService.options.youtubeId);
        var $fullContainerId = 'secp-ad-container-' + ShopifyService.options.youtubeId;
        var $adId = 'secp-ad-' + ShopifyService.options.youtubeId;

        if (jQuery('#' + $fullContainerId).length == 0) {
            var jQueryfullContainer = jQuery('<div id="' + $fullContainerId + '"></div>');
            jQueryvideo.after(jQueryfullContainer);

        } else {
            jQueryfullContainer = jQuery('#' + $fullContainerId);
        }

        var height = jQueryvideo.height();
        var $container = jQuery('<div class="secp-container-youtube-ad" id="' + $adId + '"></div>');

        $container.height( height ).css('margin-top','-30px');
        $container.html(html);

        // $container.find('.secp-product-ad-container').height( height );
        $container.find('.secp-product-close')
            .data('video-id', ShopifyService.options.youtubeId)
            .click(function (e) {
                jQuery('#'+ jQuery(this).data('video-id')).show();
            });

        jQueryfullContainer.append($container);
        jQueryvideo.hide();
        ShopifyRender.show();
    },
    renderResponse: function (response, templateName) {
        var template = ShopifyService.templates[templateName];
        return Mustache.render(template, response.product[0]).replace(/^\s*/mg, '');
    },
    show: function () {

        setTimeout('ShopifyRender.showAd()', 200);

        jQuery('.secp-product-shop-title').html(ShopifyService.options.secp_ad_title);
        jQuery('.secp-product-shop-subtitle').html(ShopifyService.options.secp_ad_subtitle);

        jQuery('.secp-product-close').click(function (e) {
            e.preventDefault();
            jQuery('.secp-ad, .secp-container-youtube-ad').fadeOut(function () {
                jQuery(this).remove();
            })
            return false;
        })
    },
    showAd: function(){
        jQuery('.secp-product')
            .unbind('click')
            .click(function () {
                ShopifyService.openProductUrl(jQuery(this).data('handle'));
                return false;
            });

        jQuery('.secp-ad')
            .addClass('secp-product-loaded');
    },
    updateSize: function(){
        console.log('update size');
        jQuery('.secp-ad').each(function(){
            var firstAd = jQuery(this).find('.owl-item:first');
            console.log('width: ' + firstAd.width())
            var width = firstAd.width();
            jQuery(this).removeClass('secp-ad--s').removeClass('secp-ad--m').removeClass('secp-ad--l').removeClass('secp-ad--xl')

            if (width > 400){
                console.log('XL')
                jQuery(this).addClass('secp-ad--xl');
            }else if(width > 250){
                console.log('L')
                jQuery(this).addClass('secp-ad--l');
            }else if(width > 150){
                console.log('M')
                jQuery(this).addClass('secp-ad--m');
            }else{
                console.log('S')
                jQuery(this).addClass('secp-ad--s');
            }
        })


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
            'endpoint': this.endpoint,
            'format': 'json',
            'callback': ''
        };
        options = jQuery.extend(defaults, options);

        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            //some times you cant try this method for sending action variable
            //action : 'report_callback',
            data: options,
            success: function (response) {
                response = jQuery.parseJSON(response.replace('not authenticated', ''));

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



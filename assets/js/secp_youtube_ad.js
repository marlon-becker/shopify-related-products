
ShopifyYoutubeAdd = {
    players: {},
    adShowed: false,
    init: function () {
        //Search all the youtube iframes with enabled js api (if not you can not check the state)
        jQuery("[enablejsapi=1]").each(function () {
            var iframeId = jQuery(this).attr('id');
            setTimeout('ShopifyYoutubeAdd.addVideoEvents("'+iframeId+'")', 1000);
        });
    },
    onPlayerReady: function (iframeId, event) {
        console.log('secp Youtube video ready with id ' + iframeId);
    },
    onPlayerStateChange: function (iframeId, event) {
        console.log('video has changed state to ' + event.data);
        if(this.adShowed === false) {
            if (event.data == YT.PlayerState.ENDED) {
                ShopifyService.options.youtubeId = iframeId;
                ShopifyYoutubeAdd.adShowed = true;
                ShopifyRender.showVideoAd();
            } else {
                if (!ShopifyService.preloadStarted) {
                    ShopifyService.preloadStarted = true;
                }
            }
        }
    },
    addVideoEvents: function(iframeId){
        ShopifyYoutubeAdd.players[iframeId] = new YT.Player(iframeId, {
            events: {
                'onReady': function (event) {
                    console.log('onReady');
                    ShopifyYoutubeAdd.onPlayerReady(iframeId, event);
                },
                'onStateChange': function (event) {
                    ShopifyYoutubeAdd.onPlayerStateChange(iframeId, event);
                }
            }
        });
    }
}

var YTInterval;
if(jQuery("script[src$='player_api']").length == 0){
    console.log('adding player_api script');
    var tag = document.createElement('script');
    tag.src = "http://www.youtube.com/player_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    function onYouTubePlayerAPIReady() {
        console.log('onYoutubePlayerAPIReady!');
        ShopifyYoutubeAdd.init();
    }
}else{
    console.log('set interval');
    YTInterval = setInterval(checkYTLoaded, 50);

}

function checkYTLoaded(){
    console.log('checkYTLoaded');
    if(window['YT']){
        console.log('check YT loaded and ready');
        if(YT.loaded){
            clearInterval(YTInterval);
            ShopifyYoutubeAdd.init();
        }
    }
}


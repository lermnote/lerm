(function ($) {
    var ms = {
        init: function (obj, args) {
            return (function () {
                ms.fillHtml(obj, args);
                ms.bindEvent(obj, args);
            })();
        },
        //填充html
        fillHtml: function (obj, args) {
            return (function () {
                var layerHtml = "";
                layerHtml += '<div id="share"><ul class="d-flex list-unstyled col-md-4 justify-content-between p-0">';
                layerHtml += '<li title="分享到QQ空间" class="mr-2"><a class="qzone border rounded p-1 text-primary"><i class="fa fa-qq"></i></a></li>';
                layerHtml += '<li title="分享到新浪微博" class="mr-2"><a class="tsina border rounded p-1 text-danger"><i class="fa fa-weibo"></i></a></li>';
                layerHtml += '<li title="分享到人人网" class="mr-2"><a class="share-people border rounded p-1 text-info"><i class="fa fa-renren"></i></a></li>';
                layerHtml += '<li title="分享到微信" class="share-code position-relative mr-2"><a class="wechat border rounded p-1 text-success"><i class="fa fa-weixin"></i></a>';
                layerHtml += '<div id="layerWxcode" class="towdimcodelayer">' +
                    '</div>';
                layerHtml += '</li></ul></div>';
                $('.banner').prepend(layerHtml);
            })();
        },
        //绑定事件
        bindEvent: function (obj, args) {
            return (function () {

                var $ShareLi = $('#share li');

                var share_url = encodeURIComponent(location.href);
                var share_title = encodeURIComponent(document.title);

                //qq空间
                $($ShareLi).find('a.qzone').on('click', function () {
                    window.open("http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=" + share_url + "&title=" + share_title + "&summary=" + args.Summary, "newwindow");
                });

                //新浪微博
                $($ShareLi).find('a.tsina').on('click', function () {
                    var param = {
                        url: share_url,
                        title: share_title,
                        summary: args.Summary,
                        searchPic: true,
                    };
                    var temp = [];
                    for (var p in param) {
                        temp.push(p + '=' + encodeURIComponent(param[p] || ''))
                    }
                    window.open('http://v.t.sina.com.cn/share/share.php?' + temp.join('&'));
                });

                //人人
                $($ShareLi).find('a.share-people').on('click', function () {
                    window.open('http://widget.renren.com/dialog/share?resourceUrl=' + share_url + '&title=' + share_title + '&images=' + '', 'newwindow');
                });

                // 微信
                $('.wechat').mouseenter(function () {
                    $('#layerWxcode').addClass('js-show-up');
                    $('#layerWxcode').prepend('<h3>分享到微信</h3>').append('<div id="qrcode" class="codebg"></div>').append('<p class="codettl">打开微信扫一扫即可将网页分享至微信</p>');
                    $('#qrcode').qrcode({
                        text: share_url,
                        width: 128,
                        height: 128
                    });

                });
                $('.wechat').mouseleave(function () {
                    $('#layerWxcode').toggleClass('js-show-up');
                    $('#layerWxcode').empty();
                });
            })();
        }
    };
    $.fn.shareConfig = function (options) {
        var args = $.extend({
            Title: "分享到：",
            Summary: "喜欢就要分享",
        }, options);
        ms.init(this, args);
    };
})(jQuery);
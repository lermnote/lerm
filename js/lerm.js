/**
 *
 * @authors Lerm http://lerm.net
 * @date    2016-04-17 22:02:49
 * @version 1.0
 */

//鼠标划过就展开子菜单，免得需要点击才能展开
$(document).ready(function() {
    var $dropdownLi = $('li.dropdown');
    $dropdownLi.mouseover(function() {
        $(this).addClass('open');
    }).mouseout(function() {
        $(this).removeClass('open');
    });

});

//平滑滚动，返回顶部效果
$(function() {
    var $body = $(document.body);;
    var $top = $('.top');
    $(window).scroll(function() {
        var scrollHeight = $(document).height();
        var scrollTop = $(window).scrollTop();
        var $footerHeight = $('.page-footer').outerHeight(true);
        var $windowHeight = $(window).innerHeight();
        scrollTop > 50 ? $("#scrollUp").fadeIn(200).css("display", "block") : $("#scrollUp").fadeOut(200);
        $top.css("bottom", scrollHeight - scrollTop - $footerHeight > $windowHeight ? 40 : $windowHeight + scrollTop + $footerHeight + 40 - scrollHeight);
    });
    $('#scrollUp').click(function(e) {
        e.preventDefault();
        $('html,body').animate({
            scrollTop: 0
        });
    });

});

//存档页面 jQ伸缩
(function($, window) {
    $(function() {
        var $a = $('#archives'),
            $m = $('.al_mon', $a),
            $l = $('.al_post_list', $a),
            $l_f = $('.al_post_list:first', $a);
        $l.hide();
        $l_f.show();
        $m.css('cursor', 's-resize').on('click', function() {
            $(this).next().slideToggle(400);
        });
        var animate = function(index, status, s) {
            if (index > $l.length) {
                return;
            }
            if (status == 'up') {
                $l.eq(index).slideUp(s, function() {
                    animate(index + 1, status, (s - 10 < 1) ? 0 : s - 10);
                });
            } else {
                $l.eq(index).slideDown(s, function() {
                    animate(index + 1, status, (s - 10 < 1) ? 0 : s - 10);
                });
            }
        };
        $('#al_expand_collapse').on('click', function(e) {
            e.preventDefault();
            if ($(this).data('s')) {
                $(this).data('s', '');
                animate(0, 'up', 100);
            } else {
                $(this).data('s', 1);
                animate(0, 'down', 100);
            }
        });
    });
})(jQuery, window);

//文章点赞
$.fn.postLike = function() {
    if ($(this).hasClass('done')) {
        return false;
    } else {
        $(this).addClass('done');
        var id = $(this).data("id"),
            action = $(this).data('action'),
            rateHolder = $(this).children('.count');
        var ajax_data = {
            action: "post_like",
            um_id: id,
            um_action: action
        };
        $.post("/wp-admin/admin-ajax.php", ajax_data,
            function(data) {
                $(rateHolder).html(data);
            });
        return false;
    }
};
$(document).on("click", ".specsZan",
    function() {
        $(this).postLike();
    });
//跟随滚动
$(function() {
    if ($(".row").height() > $("#sidebar").height()) {
        var footerHeight = 0;
        if ($('.footer-widget').length > 0) {
            footerHeight = $('.footer-widget').outerHeight(true) + $('footer').outerHeight(true);
        }
        $('#sidebar').affix({
                offset: {
                    top: $('#sidebar').offset().top - 65,
                    bottom: $('.footer').outerHeight(true) + footerHeight
                }
            })
            // todo(fat): sux you have to do this.
            .on('affix.bs.affix', function(e) {
                $(e.target).width(e.target.offsetWidth)
            })
    }
})

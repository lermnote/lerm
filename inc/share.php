<?php
/**
 * baidu share
 * @authors lerm http://lerm.net
 * @date    2016-09-02
 * @since   lerm 2.0
 */
?>
<div id="share">
	<span class="show-share"><i class="fa fa-share-alt"></i></span>
	<span class="bdsharebuttonbox">
		<a title="分享到QQ空间" href="#" class="bds_qzone" data-cmd="qzone"></a>
		<a title="分享到新浪微博" href="#" class="bds_tsina" data-cmd="tsina"></a>
		<a title="分享到QQ好友" href="#" class="bds_sqq" data-cmd="sqq"></a>
		<a title="分享到微信" href="#" class="bds_weixin" data-cmd="weixin"></a>
	</span>
</div>
<script>window._bd_share_config={
	"common":{
		"bdSnsKey":{},
		"bdText":"【<?php htmlspecialchars_decode(the_title(), ENT_QUOTES);?> | <?php echo htmlspecialchars_decode(get_bloginfo('name'), ENT_QUOTES); ?>】",
		"bdMini":"2",
		"bdMiniList":false,
		"bdPic":"",
		"bdStyle":"1",
		"bdSize":"32"},
		"share":{}
	};
	with(document)0[(getElementsByTagName('head')[0]||body).appendChild(createElement('script')).src='http://bdimg.share.baidu.com/static/api/js/share.js?v=89860593.js?cdnversion='+~(-new Date()/36e5)];</script>

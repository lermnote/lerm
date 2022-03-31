<?php
/**
 * The template for displaying the 404 template in the theme.
 *
 * @package Lerm
 */

get_header();
?>
<style>
	.layout {
	width: 1190px;
	margin: 150px auto;
}

.err {
	position: relative;
	width: 568px;
	height: 306px;
	margin: 100px auto 40px;
	background: url(<?php echo esc_url( LERM_URI . 'assets/img/404.jpg' ); ?>) no-repeat 21px 18px;
	font-size: 14px;
}

.err_text {
	position: absolute;
	top: 246px;
	left: 239px;
}

.err_back {
	position: absolute;
	top: 257px;
	left: 353px;
	width: 154px;
	height: 38px;
	text-indent: -999px;
	overflow: hidden;
}
</style>
<main role="main" class="container"><!--.container-->
	<?php get_template_part( 'template-parts/breadcrumb' ); ?>
	<div class="err">
				<p class="err_text">
					非常抱歉，您访
					<br>
					问的页面不存在
				</p>
				<a href="<?php echo esc_url( home_url( '' ) ); ?>" class="err_back" rel="home">返回首页</a>
			</div>
</main><!--.container-->
<?php
get_footer();

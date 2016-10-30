      </div><!-- .row -->
      <?php get_template_part( '/inc/blogroll' ); ?>
    </div><!--.container-->
    <?php if (!is_404()): ?>
      <div class="footer-widget">
        <div class="container">
          <div class="row media-middle">
            <?php if ( is_active_sidebar( 'bottom-sidebar-left' ) ) : ?>
              <div class="col-md-6 col-xs-12 card-block">
                <?php dynamic_sidebar( 'bottom-sidebar-left' ); ?>
              </div>
            <?php endif; ?>
            <?php if ( is_active_sidebar( 'bottom-sidebar-right' ) ) : ?>
              <div class="col-md-6 col-xs-12">
                <?php dynamic_sidebar( 'bottom-sidebar-right' ); ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <footer class="footer">
      <div class="top">
        <a id="scrollUp" class="btn btn-primary" href="javascript:;" title="飞回顶部"><i class="fa fa-chevron-up" ></i></a>
      </div>
      <div class="container text-center">
        Copyright ©
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
          <?php bloginfo( 'name' ); ?>
        </a>
        2016 版权所有 | Theme By
        <a href="http://lerm.net" rel="home"> Lerm</a>
        <?php $sitemap = of_get_option("sitemap")? of_get_option("sitemap"): ""; echo $sitemap; ?>
        <?php $icp = of_get_option("icp")? of_get_option("icp"): ""; echo $icp; ?>
      </div>
    </footer>
    <?php wp_footer();?>
  </body>
</html>

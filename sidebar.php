<div class="col-md-3 hidden-sm-down sidebar">
  <?php if( of_get_option('affix') ): ?>
    <div id="sidebar">
  <?php else: ?>
    <div>
  <?php endif;?>
    <?php if (is_home()) :
        dynamic_sidebar( 'home-sidebar' );
    endif;?>
    <?php if (is_single()) : ?>
      <aside class="card text-center">
        <div class="card-block">
          <?php if ( '' !== get_the_author_meta( 'description' ) ) {
            get_template_part( 'template-parts/biography' );
          }?>
        </div>
      </aside>
      <?php dynamic_sidebar( 'single-sidebar' );
    endif; ?>
    <?php if (is_archive()):
      wp_get_archives( array( 'type' => 'monthly' ) );
    endif; ?>

    <?php if (is_page()) :
      dynamic_sidebar( 'page-sidebar' );
    endif; ?>

    <?php if (is_search()|| is_404()||is_archive()) :
      dynamic_sidebar( 'home-sidebar' );
    endif; ?>
  </div>
</div>

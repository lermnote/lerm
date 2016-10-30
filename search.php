<?php
/**
 * The template for displaying search results pages
 *
 * @package Lerm
 * @since Lerm 1.0
 */
get_header(); ?>
  <main class="list-group">
    <?php if (have_posts()) : ?>
      <header class="list-group page-header">
        <h1 class="list-group-item text-center active page-title"><?php printf( __( '%1s为您找到以下与: %2s 相关内容', 'lerm'), bloginfo( 'name' ), '<span>' . esc_html( get_search_query() ) . '</span>' ); ?></h1>
      </header><!-- .page-header -->
      <?php
      while ( have_posts() ) : the_post();?>
        <article class="list-group-item">
          <span class="label label-default label-pill pull-left" style="margin-top: 0.25rem"><?php lerm_entry_date() ?></span>
          <?php	the_title( '<h2 class="list-group-item-heading"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );?>
          <?php lerm_excerpt(); ?>
        </article>
      <?php endwhile;
      //pagination
      pagination();
    else:
      get_template_part( 'template-parts/content', 'none' );
    endif; ?>
  </main>
  <br>
<?php get_footer();?>

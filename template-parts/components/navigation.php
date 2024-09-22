<?php
/**
 * Navigation post.
 *
 * @package Lerm https://lerm.net
 *
 * @since  4.0.0
 */
?>
<nav class="navigation post-navigation card text-center mb-3">
	<div class="nav-links d-flex flex-row">
		<div class="nav-previous border-end">
			<?php previous_post_link( '%link', '<i class="fa fa-chevron-left"></i><span class="meta-nav" aria-hidden="true">' . __( 'Previous Post', 'lerm' ) . '</span> <br/><span class="post-title d-none d-md-block">%title</span>' ); ?>
		</div>
		<div class="nav-next">
			<?php next_post_link( '%link', '<span class="meta-nav" aria-hidden="true">' . __( 'Next Post', 'lerm' ) . '</span><i class="fa fa-chevron-right"></i> <br/><span class="post-title d-none d-md-block">%title</span>' ); ?>
		</div>
	</div>
</nav>

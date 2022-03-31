<?php

// if ( is_home() && ! is_paged() && lerm_options( 'slide_position' ) === 'above_entry_list' )
// index.php
\Lerm\Inc\Carousel::instance(
	array(
		'slide_enable' => lerm_options( 'slide_enable' ),
		'slides'       => lerm_options( 'lerm_slides' ),
		'indicators'   => lerm_options( 'slide_indicators' ),
		'control'      => lerm_options( 'slide_control' ),
		'position'     => lerm_options( 'slide_position' ),
	)
);

// header.php
// if ( ( is_home() || is_front_page() ) && ! is_paged() ) {
// 	switch ( lerm_options( 'slide_position' ) ) {
// 		case 'full_width':
// 			$carousel->render();
// 			break;
// 		case 'under_navbar':
		?>
<!-- // 					<div class="container"> -->
			<?php //$carousel->render(); ?>
<!-- // 					</div> -->
				<?php
// 			break;
// 	}
// }
// // front-page.php
// $carousel = new \Lerm\Inc\Carousel();
// $carousel->render();

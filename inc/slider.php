<?php
/**
* @authors lerm http://lerm.net
* @date    2016-08-27
* @since   lerm 2.0
*
* Display slide on Home page
*/
function lerm_slide(){
  $slides='';
  $slide_on = of_get_option("slide-on")? of_get_option("slide-on"):"";
  if ($slide_on) {
    for($i=0; $i<5; $i++) {
      $slide{$i} = of_get_option("slide".$i)? of_get_option("slide".$i): "";
      $slide_url{$i} = of_get_option("slide_url".$i)? of_get_option("slide_url".$i): "";
      $slide_name{$i} = of_get_option("slide_name".$i)? of_get_option("slide_name".$i): "";
      if($slide{$i} ){
        $slides[] = $slide{$i};
				$slides_url[] = $slide_url{$i};
        $slide_name[]=$slide_name{$i};
      }
    }
    $num = count($slides);
    echo '<div id="slider" class="carousel slide" data-ride="carousel" style="margin-bottom: 0.625rem"><ol class="carousel-indicators">';
    for($i=0; $i<$num; $i++){
      echo '<li data-target="#slider" data-slide-to="'.$i.'"';
      if ($i==0) echo ' class="active"';
      echo '></li>';
    }
    echo '</ol><div class="carousel-inner" role="listbox">';
    for($i=0;$i<$num;$i++){
      echo '<div class="carousel-item';
      if($i==0) echo ' active';
      echo '"><a href="'.$slides_url[$i].'"><img class="slider" src="'.$slides[$i].'" alt="'.$slide_name[$i].'">';
      if(!empty($slide_name[$i]))
      echo '<div class="carousel-caption"><h3>'.$slide_name[$i].'</h3></div>';
      echo '</a></div>';
    };?>
  </div>
  <a class="left carousel-control" href="#slider" role="button" data-slide="prev">
    <span class="icon-prev" aria-hidden="true"></span>
    <span class="sr-only">Previous</span>
  </a>
  <a class="right carousel-control" href="#slider" role="button" data-slide="next">
    <span class="icon-next" aria-hidden="true"></span>
    <span class="sr-only">Next</span>
  </a><!--.carousel -->
</div>
<?php
    }
}

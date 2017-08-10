<?php if ( is_active_sidebar( 'va-list-page-top' ) ) : ?>
	<div id="list-page-top">

	<?php appthemes_before_sidebar_widgets( 'va-list-page-top' ); ?>

	<?php dynamic_sidebar( 'va-list-page-top' ); ?>

	<?php appthemes_after_sidebar_widgets( 'va-list-page-top' ); ?>

	</div>
<?php endif; ?>
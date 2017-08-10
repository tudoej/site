<?php appthemes_display_checkout(); ?>

<?php if ( is_active_sidebar( 'edit-listing' ) ) : ?>
	<div id="sidebar">
		<?php appthemes_before_sidebar_widgets( 'edit-listing' ); ?>

		<?php dynamic_sidebar( 'edit-listing' ); ?>

		<?php appthemes_after_sidebar_widgets( 'edit-listing' ); ?>
	</div>
<?php endif;

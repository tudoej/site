<?php
/**
 * Template Name: Create Event
 */
?>
<?php appthemes_display_checkout(); ?>

<?php if ( is_active_sidebar( 'create-event' ) ) : ?>
	<div id="sidebar">
		<?php appthemes_before_sidebar_widgets( 'create-event' ); ?>

		<?php dynamic_sidebar( 'create-event' ); ?>

		<?php appthemes_after_sidebar_widgets( 'create-event' ); ?>
	</div>
<?php endif;

<?php
/**
 * Template Name: Create Listing
 */
?>
<?php appthemes_display_checkout(); ?>

<?php if ( is_active_sidebar( 'create-listing' ) ) : ?>
	<div id="sidebar">
		<?php appthemes_before_sidebar_widgets( 'create-listing' ); ?>

		<?php dynamic_sidebar( 'create-listing' ); ?>

		<?php appthemes_after_sidebar_widgets( 'create-listing' ); ?>
	</div>
<?php endif;

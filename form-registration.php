<?php
// Template Name: Register
?>

<div id="main" class="list">
	<div class="section-head">
		<h1><?php _e( 'Register', APP_TD ); ?></h1>
	</div>

	<?php do_action( 'appthemes_notices' ); ?>

	<?php if ( get_option('users_can_register') ) : ?>

		<?php get_template_part( 'form-registration-fields' ); ?>

	<?php else: ?>

		<h3><?php _e( 'User registration has been disabled.', APP_TD ); ?></h3>

	<?php endif; ?>
</div>

<div id="sidebar">
	<?php get_sidebar( app_template_base() ); ?>
</div>

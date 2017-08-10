<?php
// Template Name: Password Reset
?>

<div id="main" class="list">
	<div class="section-head">
		<h1><?php _e( 'Password Reset', APP_TD ); ?></h1>
	</div>

	<?php do_action( 'appthemes_notices' ); ?>

	<?php get_template_part( 'form-password-reset-fields' ); ?>
</div>

<div id="sidebar">
	<?php get_sidebar( app_template_base() ); ?>
</div>

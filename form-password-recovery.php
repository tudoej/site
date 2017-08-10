<?php
// Template Name: Password Recovery
?>

<div id="main" class="list">
	<div class="section-head">
		<h1><?php _e( 'Password Recovery', APP_TD ); ?></h1>
	</div>

	<?php do_action( 'appthemes_notices' ); ?>

	<?php require APP_THEME_FRAMEWORK_DIR . '/templates/form-password-recovery.php'; ?>
</div>

<div id="sidebar">
	<?php get_sidebar( app_template_base() ); ?>
</div>

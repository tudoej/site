<div id="main">
	<div class="section-head">
		<h1><?php echo $title; ?></h1>
	</div>

	<?php do_action( 'appthemes_notices' ); ?>

	<?php while ( $listing_query->have_posts() ) : $listing_query->the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php get_template_part( 'content-renew-listing' ); ?>
		</article>
	<?php endwhile; ?>

	<div class="claim-listing">
        <p><?php printf( __('This listing "%s" has expired and will need to be renewed', APP_TD), get_the_title( $listing->ID ) );?></p>

        <p><?php _e( 'You may proceed with renewing this listing by clicking the continue button below.', APP_TD ); ?></p>
        <form id="renew-listing" method="POST" action="<?php echo appthemes_get_step_url(); ?>">
			<fieldset>
				<?php wp_nonce_field( 'va_renew_listing' ); ?>
				<input type="hidden" name="action" value="renew-listing">
				<div classess="form-field"><input type="submit" value="<?php _e( 'Continue', APP_TD ) ?>" /></div>
			</fieldset>
		</form>
	</div>
</div>
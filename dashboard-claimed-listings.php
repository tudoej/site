<div class="list">
	<div class="section-head">
		<h1><?php echo $title; ?></h1>
	</div>
<?php
$listings = va_get_dashboard_claimed_listings( $dashboard_user->ID, (bool) $is_own_dashboard );

if ( $listings && $listings->post_count > 0 ) {
	while ( $listings->have_posts() ) : $listings->the_post();

	$post_status = $is_own_dashboard ? 'post-status' : '';
?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( $post_status ); ?>>
		<?php if ( $is_own_dashboard ) { ?>
			<div class="featured-head <?php echo 'post-status-'.get_post_status( get_the_ID() ).'-head'; ?>">
				<h3><?php echo va_get_dashboard_verbiage( get_post_status( get_the_ID() ) ); ?></h3>
			</div>
			<?php
				if ( 'pending-claimed' != get_post_status( get_the_ID() ) ) {
					va_the_listing_expiration_notice();
				}
			?>
		<?php } ?>

		<?php
			if ( 'pending-claimed' == get_post_status( get_the_ID() ) ) {
				get_template_part( 'content-claimed-listing', get_post_status() );
			} else {
				get_template_part( 'content-listing', get_post_status() );
			}
		?>
	</article>
<?php
	endwhile;
} else {
?>
	<h3 class="dashboard-none"><?php _e( 'You have no claimed listings at this time. ', APP_TD ); ?></h3>
<?php
}

if ( $listings && $listings->max_num_pages > 1 ) { ?>
	<nav class="pagination">
		<?php appthemes_pagenavi( $listings ); ?>
	</nav>
<?php
}
?>
</div><!-- /#content -->

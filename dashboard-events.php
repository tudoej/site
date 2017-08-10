<div class="list">
	<div class="section-head">
		<h1><?php echo $title; ?></h1>
	</div>
<?php
$events = va_get_dashboard_events( $dashboard_user->ID, (bool) $is_own_dashboard );

if ( $events->post_count > 0 ) {
	while ( $events->have_posts() ) : $events->the_post();

	$post_status = $is_own_dashboard ? 'post-status' : '';
?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( $post_status ); ?>>
		<?php if ( $is_own_dashboard ) { ?>
			<div class="featured-head <?php echo 'post-status-'.get_post_status( get_the_ID() ).'-head'; ?>">
				<h3><?php echo va_get_dashboard_verbiage( get_post_status( get_the_ID() ) ); ?></h3>
			</div>
			<?php va_the_event_expiration_notice(); ?>
		<?php } ?>

		<?php get_template_part( 'content-event', get_post_status() ); ?>
	</article>
<?php
	endwhile;
} else {
?>
	<?php if( $is_own_dashboard ) { ?>
	<h3 class="dashboard-none"><?php echo __( 'You have no events at this time. ', APP_TD ) . html_link( va_get_event_create_url(), __( 'Create an event', APP_TD ) ); ?></h3>
	<?php } else { ?>
	<h3 class="dashboard-none"><?php printf( __( '%s has no events.', APP_TD ), $dashboard_user->display_name ); ?></h3>
<?php
	}
}

if ( $events->max_num_pages > 1 ) { ?>
	<nav class="pagination">
		<?php appthemes_pagenavi( $events ); ?>
	</nav>
<?php
}
?>
</div><!-- /#content -->

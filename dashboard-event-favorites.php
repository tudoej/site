<div class="list">
	<div class="section-head">
		<h1><?php echo $title; ?></h1>
	</div>
<?php
$events = va_get_dashboard_event_favorites($dashboard_user->ID, (bool) $is_own_dashboard );

if ( $events->post_count > 0 ) {
	while ( $events->have_posts() ) : $events->the_post();

	$post_status = $is_own_dashboard ? 'post-status' : '';
?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( $post_status ); ?>>
		<?php get_template_part( 'content-event', get_post_status() ); ?>
	</article>
<?php
	endwhile;
} else {
?>
	<?php if( $is_own_dashboard ) { ?>
	<h3 class="dashboard-none"><?php _e( 'You have no favorite events. ', APP_TD); ?></h3>
	<?php } else { ?>
	<h3 class="dashboard-none"><?php printf(  __( '%s has no favorite events.', APP_TD ), $dashboard_user->display_name ); ?></h3>
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

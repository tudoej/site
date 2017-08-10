<div class="list">
	<div class="section-head">
		<h1><?php echo $title; ?></h1>
	</div>
<?php
$favorites = va_get_dashboard_favorites($dashboard_user->ID, (bool) $is_own_dashboard );

if ( $favorites->post_count > 0 ) {
	while ( $favorites->have_posts() ) : $favorites->the_post();

	$post_status = $is_own_dashboard ? 'post-status' : '';
?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( $post_status ); ?>>
		<?php get_template_part( 'content-listing', get_post_status() ); ?>
	</article>
<?php
	endwhile;
} else {
?>
	<?php if( $is_own_dashboard ) { ?>
	<h3 class="dashboard-none"><?php echo __( 'You have no favorites listings. ', APP_TD); ?></h3>
	<?php } else { ?>
	<h3 class="dashboard-none"><?php printf(  __( '%s has no favorite listings.', APP_TD ), $dashboard_user->display_name ); ?></h3>
<?php
	}
}

if ( $favorites->max_num_pages > 1 ) { ?>
	<nav class="pagination">
		<?php appthemes_pagenavi( $favorites ); ?>
	</nav>
<?php
}
?>
</div><!-- /#content -->

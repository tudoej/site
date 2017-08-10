<?php
	global $va_options;
?>
<div class="list events-list">
	<div class="section-head">
		<h1><?php _e( 'Events', APP_TD ); ?></h1>
	</div>

	<div class="sorting">
		<div id="events-time-nav"><?php echo va_event_archive_date_selector(); ?></div>
		<div class="list-sort-dropdown"><?php echo va_list_sort_dropdown( VA_EVENT_PTYPE, va_events_base_url(), $va_options->default_event_home_sort ); ?></div>
	</div>

<?php
$events = va_get_event_home_listings();
if ( $events->post_count > 0 ) :
?>
<?php appthemes_before_loop( VA_EVENT_PTYPE ); ?>
<?php while ( $events->have_posts() ) : $events->the_post(); ?>
	<?php appthemes_before_post( VA_EVENT_PTYPE ); ?>
	<?php if ( va_is_listing_featured( get_the_ID() ) ) : ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'featured' ); ?> <?php echo va_post_coords_attr(); ?> itemscope itemtype="http://schema.org/Event">
		<div class="featured-head">
			<h3><?php _e( 'Featured', APP_TD ); ?></h3>
		</div>
	<?php else: ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php echo va_post_coords_attr(); ?> itemscope itemtype="http://schema.org/Event">
	<?php endif; ?>
			<?php get_template_part( 'content-event' ); ?>
	</article>
	<?php appthemes_after_post( VA_EVENT_PTYPE ); ?>
<?php endwhile; ?>

<?php else : ?>
	<article class="event">
		<h2><?php __( 'Sorry there are no events yet', APP_TD ); ?></h2>
	</article>
<?php endif; ?>

<?php if ( $events->max_num_pages > 1 ) : ?>
	<nav class="pagination">
		<?php appthemes_pagenavi( $events, 'paged', array( 'home_events' => 1 ) ); ?>
	</nav>
<?php endif; ?>

<?php wp_reset_query(); ?>

	<div class="advert">
		<?php appthemes_before_sidebar_widgets( 'va-listings-ad' ); ?>

		<?php dynamic_sidebar( 'va-listings-ad' ); ?>

		<?php appthemes_after_sidebar_widgets( 'va-listings-ad' ); ?>
	</div>
</div>

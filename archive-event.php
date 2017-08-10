<?php
// Archive Events
?>

<div class="list">
	<div class="section-head">
		<?php if ( is_tax( VA_EVENT_CATEGORY ) || is_tax( VA_EVENT_TAG ) || is_tax( VA_EVENT_DAY ) ) { ?>
			<h1><?php printf( __( 'Events - %s', APP_TD ), single_term_title( '', false )); ?></h1>
		<?php } elseif( $date_slug = va_get_search_query_var( VA_EVENT_DAY ) ) { ?>
			<?php $date = va_format_event_day( $date_slug ); ?>
				<h1><?php printf( __( 'Events - %s', APP_TD ), $date ); ?></h1>
		<?php } else { ?>
			<h1><?php _e( 'Events', APP_TD ); ?></h1>
		<?php } ?>
	</div>

	<div class="sorting">
		<?php if ( !is_search() ) { ?>
		<div id="events-time-nav"><?php echo va_event_archive_date_selector(); ?></div>
		<?php } ?>
		<div class="list-sort-dropdown"><?php echo va_list_sort_dropdown( VA_EVENT_PTYPE, va_events_base_url() ); ?></div>
	</div>

	<?php va_the_archive_description( '<div class="taxonomy-description">', '<div class="clear"></div></div>' ); ?>

<?php if ( have_posts() ) : ?>

	<?php if ( is_search() ) : ?>
	<article class="archive-top event-archive-top">
		<h3 class="archive-head event-archive-head"><?php printf( __( 'Events found for "%s" near "%s"', APP_TD ), va_get_search_query_var( 'ls' ), va_get_search_query_var( 'location' ) ); ?></h3>
	</article>
	<?php endif; ?>

<?php appthemes_before_loop( VA_EVENT_PTYPE ); ?>
<?php while ( have_posts() ) : the_post(); ?>
	<?php appthemes_before_post( VA_EVENT_PTYPE ); ?>
	<?php if ( va_show_featured() && va_is_listing_featured( get_the_ID() ) ) : ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'featured' ); ?> <?php echo va_post_coords_attr(); ?> itemscope itemtype="http://schema.org/Event">
		<div class="featured-head">
			<h3><?php _e( 'Featured', APP_TD ); ?></h3>
		</div>
	<?php else:  ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php echo va_post_coords_attr(); ?> itemscope itemtype="http://schema.org/Event">
	<?php  endif;  ?>
			<?php get_template_part( 'content-event' ); ?>
	</article>
	<?php appthemes_after_post( VA_EVENT_PTYPE ); ?>
<?php endwhile; ?>
<?php appthemes_after_loop( VA_EVENT_PTYPE ); ?>

<?php else : ?>
	<?php if ( is_search() ) : ?>
	<article class="event">
		<h2><?php printf( __( 'Sorry, no events were found for "%s" near "%s"', APP_TD ), va_get_search_query_var( 'ls' ), va_get_search_query_var( 'location' ) ); ?></h2>
	</article>
	<?php elseif ( is_archive() ) : ?>
	<article class="event">
		<?php
		if ( is_tax( VA_EVENT_CATEGORY ) ) {
			$tax = __( 'category', APP_TD );
		} else if( is_tax( VA_EVENT_TAG ) ) {
			$tax = __( 'tag', APP_TD );
		} else if( is_tax( VA_EVENT_DAY ) ){
			$tax = __( 'date', APP_TD );
		}
		?>
		<h2><?php printf( __( 'Sorry there are no events for %s "%s"', APP_TD ), $tax, single_term_title( '', false ) ); ?></h2>
	</article>
	<?php elseif ( $date_slug ) : ?>
	<article class="event">
		<h2><?php printf( __( 'Sorry there are no events for date "%s"', APP_TD ), $date ); ?></h2>
	</article>
	<?php endif; ?>
<?php endif; ?>
	<div class="advert">
		<?php dynamic_sidebar( 'va-events-ad' ); ?>
	</div>
<?php if ( $wp_query->max_num_pages > 1 ) : ?>
	<nav class="pagination">
		<?php appthemes_pagenavi(); ?>
	</nav>
<?php endif; ?>

</div>

<div id="sidebar">
	<?php get_sidebar( app_template_base() ); ?>
</div>

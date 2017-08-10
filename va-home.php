<?php
// Template Name: Listings and Events
?>

<div class="list">
	<div class="section-head">
		<?php if ( is_tax( VA_LISTING_CATEGORY ) || is_tax( VA_LISTING_TAG ) ) { ?>
			<h1><?php printf( __( 'Business Listings - %s', APP_TD ), single_term_title( '', false )); ?></h1>
		<?php } else { ?>
			<h1><?php _e( 'Business Listings', APP_TD ); ?></h1>
		<?php } ?>
	</div>

	<div class="sorting">
		<div class="list-sort-dropdown"><?php echo va_list_sort_dropdown( VA_LISTING_PTYPE, va_listings_base_url(), $va_options->default_listing_home_sort ); ?></div>
	</div>

<?php
$listings = va_get_home_listings();
if ( $listings->post_count > 0 ) :
?>
<?php appthemes_before_loop( VA_LISTING_PTYPE ); ?>
<?php while ( $listings->have_posts() ) : $listings->the_post(); ?>
	<?php appthemes_before_post( VA_LISTING_PTYPE ); ?>
	<?php if ( va_is_listing_featured( get_the_ID() ) ) : ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'featured' ); ?> <?php echo va_post_coords_attr(); ?> itemscope itemtype="http://schema.org/Organization">
		<div class="featured-head">
			<h3><?php _e( 'Featured', APP_TD ); ?></h3>
		</div>
	<?php else: ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php echo va_post_coords_attr(); ?> itemscope itemtype="http://schema.org/Organization">
	<?php endif; ?>
			<?php get_template_part( 'content-listing' ); ?>
	</article>
	<?php appthemes_after_post( VA_LISTING_PTYPE ); ?>
<?php endwhile; ?>

<?php else : ?>
	<article class="listing">
		<h2><?php __( 'Sorry there are no listings yet', APP_TD ); ?></h2>
	</article>
<?php endif; ?>

<?php if ( $listings->max_num_pages > 1 ) : ?>
	<nav class="pagination">
		<?php  appthemes_pagenavi( $listings, 'paged', array( 'home_listings' => 1 ) ); ?>
	</nav>
<?php endif; ?>

<?php wp_reset_query(); ?>

	<div class="advert">
		<?php appthemes_before_sidebar_widgets( 'va-listings-ad' ); ?>

		<?php dynamic_sidebar( 'va-listings-ad' ); ?>

		<?php appthemes_after_sidebar_widgets( 'va-listings-ad' ); ?>
	</div>
<?php
if ( $va_options->events_enabled ) {
	get_template_part('home-events-loop');
}
?>
</div>

<div id="sidebar">
	<?php get_sidebar( app_template_base() ); ?>
</div>

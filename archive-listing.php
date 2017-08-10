<?php
// Archive Listings
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
		<div class="list-sort-dropdown"><?php echo va_list_sort_dropdown( VA_LISTING_PTYPE, va_listings_base_url() ); ?></div>
	</div>

	<?php va_the_archive_description( '<div class="taxonomy-description">', '<div class="clear"></div></div>' ); ?>

<?php if ( have_posts() ) : ?>

	<?php if ( is_search() ) : ?>
	<article class="archive-top listing-archive-top">
		<h3 class="archive-head listing-archive-head"><?php printf( __( 'Listings found for "%s" near "%s"', APP_TD ), va_get_search_query_var( 'ls' ), va_get_search_query_var( 'location' ) ); ?></h3>
	</article>
	<?php endif; ?>

<?php appthemes_before_loop( VA_LISTING_PTYPE ); ?>
<?php while ( have_posts() ) : the_post(); ?>
	<?php appthemes_before_post( VA_LISTING_PTYPE ); ?>
	<?php if ( va_show_featured() && va_is_listing_featured( get_the_ID() ) ) : ?>
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
<?php appthemes_after_loop( VA_LISTING_PTYPE ); ?>

<?php else : ?>
	<?php if ( is_search() ) : ?>
	<article class="listing">
		<h2><?php printf( __( 'Sorry, no listings were found for "%s" near "%s"', APP_TD ), va_get_search_query_var( 'ls' ), va_get_search_query_var( 'location' ) ); ?></h2>
	</article>
	<?php elseif ( is_archive() ) : ?>
	<article class="listing">
		<h2><?php printf( __( 'Sorry there are no listings for %s "%s"', APP_TD ), ( is_tax( VA_LISTING_CATEGORY ) ? __( 'category', APP_TD ) : __( 'tag', APP_TD ) ), single_term_title( '', false ) ); ?></h2>
	</article>
	<?php endif; ?>
<?php endif; ?>
	<div class="advert">
		<?php appthemes_before_sidebar_widgets( 'va-listings-ad' ); ?>

		<?php dynamic_sidebar( 'va-listings-ad' ); ?>

		<?php appthemes_after_sidebar_widgets( 'va-listings-ad' ); ?>
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

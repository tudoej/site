<?php global $va_options; ?>

<?php
	echo html( 'a', array(
		'href' => get_permalink( get_the_ID() ),
		'title' => get_the_title(),
		'rel' => 'bookmark',
	), get_the_listing_thumbnail());
?>

<div class="review-meta" itemprop="review" itemscope itemtype="http://schema.org/Review" >
	<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">
		<?php the_listing_star_rating(); ?>
	</div>

	<p class="reviews"><?php
		if ( va_user_can_add_reviews() ) {
			echo html_link(
				get_permalink( get_the_ID() ) . '#add-review',
				__( 'Add your review', APP_TD )
			);
			echo ', ';
		} else if ( !is_user_logged_in() ) {
			echo html_link(
				get_permalink( get_the_ID() ) . '#add-review',
				__( 'Add your review', APP_TD )
			);
			echo ', ';
		}

		the_review_count();
	?></p>
</div>

<?php appthemes_before_post_title( VA_LISTING_PTYPE ); ?>
<h2 class="entry-title" itemprop="name"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
<?php appthemes_after_post_title( VA_LISTING_PTYPE ); ?>

<div class="added" style="display:none;"><?php _e( 'Updated:', APP_TD ); ?> <span class="date updated"><?php the_modified_time('Y-m-d'); ?></span></div>
<p class="vcard author" style="display:none;"><span class="fn" itemprop="employee"><?php echo va_get_the_author_listings_link();?></span></p>

<p class="listing-cat"><?php the_listing_categories(); ?></p>
<?php if ( function_exists('sharethis_button') && $va_options->listing_sharethis ): ?>
	<div class="listing-sharethis"><?php sharethis_button(); ?></div>
	<div class="clear"></div>
<?php endif; ?>
<div class="content-listing listing-faves">
	<?php the_listing_faves_link(); ?>
	<?php the_listing_delete_link(); ?>
</div>
<div itemprop="location" itemscope itemtype="http://schema.org/Place">
	<p class="listing-phone" itemprop="telephone"><?php echo esc_html( get_post_meta( get_the_ID(), 'phone', true ) ); ?></p>
	<p class="listing-address" itemprop="address"><?php the_listing_address(); ?></p>
	<p class="listing-description"><strong><?php _e( 'Description:', APP_TD ); ?></strong> <?php the_excerpt(); ?> <?php echo html_link( get_permalink(), __( 'Read more...', APP_TD ) ); ?></p>
	<?php
	$coord = appthemes_get_coordinates( get_the_ID() );
	if ( 0 < $coord->lat ) {
	?>
		<div itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">
			<meta itemprop="latitude" content="<?php echo esc_attr( $coord->lat ); ?>" />
			<meta itemprop="longitude" content="<?php echo esc_attr( $coord->lng ); ?>" />
		</div>
	<?php } ?>
</div>
<?php the_listing_thumbnail(); ?>

<div class="review-meta">
	<?php the_listing_star_rating(); ?>

	<p class="reviews"><?php the_review_count(); ?></p>
</div>

<h2><?php the_title(); ?></h2>

<p class="listing-cat"><?php the_listing_categories(); ?></p>

<div class="content-listing listing-faves">
	<?php the_listing_delete_link(); ?>
</div>

<p class="listing-phone"><?php echo esc_html( get_post_meta( get_the_ID(), 'phone', true ) ); ?></p>
<p class="listing-address"><?php the_listing_address(); ?></p>

<p class="listing-description"><strong><?php _e( 'Description:', APP_TD ); ?></strong> <?php the_excerpt(); ?></p>

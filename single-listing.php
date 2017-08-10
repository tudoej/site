<div id="main">

<?php the_post(); ?>

<?php do_action( 'appthemes_notices' ); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> itemscope itemtype="http://schema.org/LocalBusiness">
	<?php the_listing_image_gallery(); ?>

	<?php appthemes_before_post_title( VA_LISTING_PTYPE ); ?>
	<h1 class="entry-title" itemprop="name"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h1>
	<p class="vcard author"><?php printf( __( 'Added by %s', APP_TD ), '<span class="fn" itemprop="employee">'. va_get_the_author_listings_link() .'</span>' ); ?></p>

	<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">
		<?php the_listing_star_rating(); ?>
		<p class="reviews"><?php
			the_review_count();

			if ( va_user_can_add_reviews() ) {
				echo ', ' . html_link( '#add-review', __( 'Add your review', APP_TD ) );
			}
		?></p>
	</div><!-- /.aggregateRating -->

	<p><?php the_listing_categories(); ?></p>
	<?php appthemes_after_post_title( VA_LISTING_PTYPE ); ?>

	<?php $website = get_post_meta( get_the_ID(), 'website', true ); ?>
	<?php $email = get_post_meta( get_the_ID(), 'email', true ); ?>

	<div itemprop="location" itemscope itemtype="http://schema.org/Place">
		<ul>
			<li class="address" itemprop="address"><?php the_listing_address(); ?></li>
			<li class="phone" itemprop="telephone"><strong><?php echo esc_html( get_post_meta( get_the_ID(), 'phone', true ) ); ?></strong></li>
		<?php if ( $website ) { ?>
			<li id="listing-website"><a href="<?php echo esc_url( $website ); ?>" title="<?php _e( 'Website', APP_TD ); ?>" target="_blank" itemprop="url"><?php echo esc_html( $website ); ?></a></li>
		<?php } ?>
		<?php if ( $email ) { ?>
			<li id="listing-email"><a href="mailto:<?php echo esc_attr( $email ); ?>" title="<?php _e( 'Email', APP_TD ); ?>" target="_blank"><?php echo esc_html( $email ); ?></a></li>
		<?php } ?>

		<?php do_action( 'va_display_listing_contact_fields', get_the_ID() ); ?>
		</ul>

		<?php
		$coord = appthemes_get_coordinates( $post->ID );
		if ( 0 < $coord->lat ) {
		?>
			<div itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">
				<meta itemprop="latitude" content="<?php echo esc_attr( $coord->lat ); ?>" />
				<meta itemprop="longitude" content="<?php echo esc_attr( $coord->lng ); ?>" />
			</div>
		<?php } ?>
	</div><!-- /.Place -->

	<?php
	$social_networks = va_get_available_listing_networks( get_the_ID() );
	if ( ! empty( $social_networks ) ) { ?>
		<div id="listing-follow">
			<p><?php _e( 'Follow:', APP_TD ); ?></p>
			<?php foreach ( $social_networks as $social_network => $account ) { ?>
				<a href="<?php echo va_get_social_account_url( $social_network, $account ); ?>" title="<?php echo esc_attr( va_get_social_network_title( $social_network ) ); ?>" target="_blank">
					<span class="<?php echo esc_attr( $social_network ); ?>-icon social-icon"><?php echo va_get_social_network_title( $social_network ); ?></span>
					<?php if ( 'twitter' === $social_network ) { ?>
						<span class="twitter-handle"> @<?php echo esc_html( $account ); ?></span>
					<?php } ?>
				</a>
			<?php } ?>
		</div>
	<?php } ?>

	<div class="listing-fields">
		<?php the_listing_fields(); ?>
	</div>

	<div class="single-listing listing-faves">
		<?php the_listing_faves_link(); ?>
	</div>

	<div class="listing-actions">
		<?php the_listing_edit_link(); ?>
		<?php the_listing_claimable_link(); ?>
		<?php the_listing_purchase_link(); ?>
		<?php the_contact_listing_owner_button(); ?>
	</div>

	<div class="listing-share">
		<?php if ( function_exists( 'sharethis_button' ) ) sharethis_button(); ?>
	</div>

	<hr />
	<div class="tags"><?php the_listing_tags( '<span>' . __( 'Tags:', APP_TD ) . '</span> ' ); ?></div>
	<div class="added" style="display:none;"><?php _e( 'Updated:', APP_TD ); ?> <span class="date updated"><?php the_modified_time('M j, Y'); ?></span></div>

	<?php va_the_files_list(); ?>

	<div id="listing-tabs">
		<div class="tabs">
			<a id="overview-tab" class="active-tab rounded-t first" href="#overview"><?php _e( 'Overview', APP_TD ); ?></a>
			<a id="reviews-tab" class="rounded-t" href="#reviews"><?php _e( 'Reviews', APP_TD ); ?></a>

			<br class="clear" />
		</div>

		<section id="overview" itemprop="description">
			<?php appthemes_before_post_content( VA_LISTING_PTYPE ); ?>
			<?php the_content(); ?>
			<?php appthemes_after_post_content( VA_LISTING_PTYPE ); ?>
		</section>

		<section id="reviews">
			<?php get_template_part( 'reviews', 'listing' ); ?>
		</section>
	</div>

	<div class="clear"></div>

	<div class="section-head">
		<a id="add-review" name="add-review"></a>
		<h2 id="left-hanger-add-review"><?php _e( 'Add Your Review', APP_TD ); ?></h2>
	</div>

	<?php if ( $review = va_get_user_review( get_current_user_id(), get_the_ID() ) ) : ?>

		<?php if ( '1' !== $review->comment_approved ) { ?>
			<p>
				<?php _e( 'Your review is awaiting moderation.', APP_TD ); ?>
			</p>
		<?php } else { ?>
			<p>
				<?php _e( 'You have already reviewed this listing.', APP_TD ); ?>
			</p>
		<?php } ?>

	<?php elseif ( va_user_can_add_reviews() ) : ?>
		<?php appthemes_load_template( 'form-review.php' ); ?>
	<?php elseif ( get_current_user_id() == get_the_author_meta('ID') ) : ?>
		<p>
			<?php _e( "You can't review your own listing.", APP_TD ); ?>
		</p>
	<?php elseif ( !is_user_logged_in() ) : ?>
		<p>
			<?php
				$login_url = wp_login_url( get_permalink() );
				$register_url = add_query_arg( 'redirect_to', urlencode( get_permalink() ), appthemes_get_registration_url() );
				printf( __( 'Please <a href="%1$s">login</a> or <a href="%2$s">register</a> to add your review.', APP_TD ), $login_url, $register_url );
			?>
		</p>
	<?php else : ?>
		<p>
			<?php _e( 'Reviews are closed.', APP_TD ); ?>
		</p>
	<?php endif; ?>

</article>

</div><!-- /#main -->

<div id="sidebar">
<?php get_sidebar( 'single-listing' ); ?>
</div>

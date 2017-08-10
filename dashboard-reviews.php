<div class="list">
<?php do_action( 'appthemes_notices' ); ?>
	<div class="section-head">
		<h1><?php echo $title; ?></h1>
	</div>
<?php
$reviews = va_get_dashboard_reviews($dashboard_user->ID, (bool) $is_own_dashboard );

if ( $reviews ) {

	foreach( $reviews as $review ) {

		$review_listing = get_post( $review->comment_post_ID );
	?>
		<div class="dashboard-review" id="review-<?php echo $review->comment_ID; ?>">

			<div class="review-listing">
				<h2><a href="<?php echo get_permalink( $review_listing->ID ); ?>" rel="bookmark"><?php echo get_the_title( $review_listing->ID ); ?></a></h2>

				<p class="listing-cat"><?php the_listing_categories( $review_listing->ID ); ?></p>
				<p class="listing-phone"><?php echo esc_html( get_post_meta(  $review_listing->ID , 'phone', true ) ); ?></p>
				<p class="listing-address"><?php the_listing_address( $review_listing->ID ); ?></p>

				<div class="review-meta">
					<?php the_listing_star_rating( $review_listing->ID ); ?>
					<p class="reviews"><?php
						printf( __( 'Reviewed on %s.' , APP_TD ),  mysql2date( get_option('date_format'), $review->comment_date ) );
						printf( __( ' #%d of %d Reviews', APP_TD), 1, va_get_reviews_count( $review_listing->ID ) );
					?></p>
				</div>
			</div>

			<?php if ( $is_own_dashboard ) { ?>
			<div class="review-manage">
				<form action="" method="post" name="dashboard-reviews" onsubmit="return confirm('<?php _e( 'Are you sure you want to delete this review?', APP_TD ); ?>');" >
					<?php wp_nonce_field( 'va-dashboard-reviews' ); ?>
					<input type="hidden" name="action" value="dashboard-reviews" />
					<input type="hidden" name="review_id" value="<?php echo $review->comment_ID; ?>" />
					<input type="submit" name="del-review" value="<?php _e( 'Delete', APP_TD ); ?>" class="review-manage-link" />
				</form>
			</div>
			<?php } ?>

			<div class="review-content">
				<?php echo apply_filters( 'comment_text', $review->comment_content, $review ); ?>
			</div>

		</div>
<?php }// end foreach $reviews ?>

	<?php } else { //else if !$reviews ?>

		<?php if( $is_own_dashboard ) { ?>
		<h3 class="dashboard-none"><?php _e( 'You have no reviews.', APP_TD ); ?></h3>
		<?php } else { ?>
		<h3 class="dashboard-none"><?php printf(  __( '%s has no reviews.', APP_TD ), $dashboard_user->display_name ); ?></h3>
		<?php }// /else !$is_own_dashboard ?>

	<?php }// /else !$reviews  ?>

<?php if ( ( $review_pages = va_get_dashboard_reviews_count($dashboard_user->ID, (bool) $is_own_dashboard ) ) > 1 ) {   ?>
	<nav class="pagination">
		<?php appthemes_pagenavi( array(
			'current' => get_query_var( 'paged' ),
			'total' => $review_pages
		) ); ?>
	</nav>
<?php
}
?>
</div><!-- /#content -->

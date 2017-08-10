<div class="list">
<?php do_action( 'appthemes_notices' ); ?>
	<div class="section-head">
		<h1><?php echo $title; ?></h1>
	</div>
<?php
$event_comments = va_get_dashboard_event_comments($dashboard_user->ID, (bool) $is_own_dashboard );

if ( $event_comments ) {

	foreach( $event_comments as $review ) {

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
					<p class="event_comments"><?php
						printf( __( 'Reviewed on %s.' , APP_TD ),  mysql2date( get_option('date_format'), $review->comment_date ) );
						printf( __( ' #%d of %d event_comments', APP_TD), 1, va_get_event_comments_count( $review_listing->ID ) );
					?></p>
				</div>
			</div>

			<?php if ( $is_own_dashboard ) { ?>
			<div class="review-manage">
				<form action="" method="post" name="dashboard-event_comments" onsubmit="return confirm('<?php _e( 'Are you sure you want to delete this comment?', APP_TD ); ?>');" >
					<?php wp_nonce_field( 'va-dashboard-event_comments' ); ?>
					<input type="hidden" name="action" value="dashboard-event_comments" />
					<input type="hidden" name="review_id" value="<?php echo $review->comment_ID; ?>" />
					<input type="submit" name="del-review" value="<?php _e( 'Delete', APP_TD ); ?>" class="review-manage-link" />
				</form>
			</div>
			<?php } ?>

			<div class="review-content">
				<?php echo apply_filters( 'comment_text', $review->comment_content, $review ); ?>
			</div>

		</div>
<?php }// end foreach $event_comments ?>

	<?php } else { //else if !$event_comments ?>

		<?php if( $is_own_dashboard ) { ?>
		<h3 class="dashboard-none"><?php _e( 'You have no event comments.', APP_TD ); ?></h3>
		<?php } else { ?>
		<h3 class="dashboard-none"><?php printf(  __( '%s has no event comments.', APP_TD ), $dashboard_user->display_name ); ?></h3>
		<?php }// /else !$is_own_dashboard ?>

	<?php }// /else !$event_comments  ?>

<?php if ( ( $comment_pages = va_get_dashboard_event_comments_count($dashboard_user->ID, (bool) $is_own_dashboard ) ) > 1 ) {   ?>
	<nav class="pagination">
		<?php appthemes_pagenavi( array(
			'current' => get_query_var( 'paged' ),
			'total' => $comment_pages
		) ); ?>
	</nav>
<?php
}
?>
</div><!-- /#content -->

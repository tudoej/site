<?php
$reviews = va_get_reviews( array(
	'post_id' => get_the_ID(),
	'status' => 'approve'
) );
$replies = array();
foreach( $reviews as $key=>$review ) {
	if( $review->comment_parent != 0 ) {
		$replies[$review->comment_parent] = $review;
		unset($reviews[$key]);
	}
}
foreach( $reviews as $review ) {
	$user = get_userdata( $review->user_id );

	$user_url = va_dashboard_url( 'reviews', $user->ID );
?>
	<div class="review" id="review-<?php echo $review->comment_ID; ?>" itemprop="review" itemscope itemtype="http://schema.org/Review">
		<meta itemprop="datePublished" content="<?php echo esc_attr( mysql2date( 'Y-m-d', $review->comment_date ) ); ?>" />
		<div class="review-meta">
			<div class="review-author">
				<?php echo html_link( $user_url, get_avatar( $user->ID, 45 ) ); ?>
				<ul class="review-author-meta">
					<li itemprop="author"><strong><?php echo html_link( $user_url, $user->display_name ); ?></strong></li>
					<li><?php echo esc_html( $user->location ); ?></li>
					<li><?php _e( 'Member Since:' , APP_TD ); ?> <?php echo mysql2date( get_option('date_format'), $user->user_registered ); ?></li>
				</ul>
				<?php $reply = !empty( $replies[$review->comment_ID] ) ? $replies[$review->comment_ID] : ''; ?>
				<?php if ( get_current_user_id() == get_the_author_meta('ID') && empty($reply) ) { ?>
					<div class="review-author-reply"><a class="reply-link"><?php _e( 'Reply', APP_TD ); ?></a></div>
				<?php } ?>
			</div>
		</div>
		<div class="review-content">
			<div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">
				<div class="stars-cont">
					<div class="stars stars-<?php echo $rating = va_get_rating( $review->comment_ID ); ?>"></div>
				</div>
				<meta itemprop="ratingValue" content="<?php echo esc_attr( $rating ); ?>" />
				<meta itemprop="worstRating" content="1" />
				<meta itemprop="bestRating" content="5" />
			</div><!-- /.reviewRating -->
			<p class="review-date"><?php echo html_link( va_get_review_link( $review->comment_ID ), mysql2date( get_option('date_format'), $review->comment_date ) ); ?></p>
			<div class="clear"></div>
			<div class="review-body" itemprop="description"><?php echo apply_filters( 'comment_text', $review->comment_content, $review ); ?></div>
			<?php if ( !empty( $reply ) ) { ?>
			<?php $author = get_userdata( $reply->user_id ); ?>
				<div class="review-reply" id="review-<?php echo $reply->comment_ID; ?>">
					<p class="review-reply-author">
						<?php printf( __('Response from %s on %s', APP_TD ), get_the_title(),  mysql2date( get_option('date_format'), $reply->comment_date ) ); ?>
					</p>
					<div class="reply-body"><?php echo apply_filters( 'comment_text', $reply->comment_content, $reply ); ?></div>
				</div>
			<?php } ?>
		</div>
	</div>
<?php } ?>

<?php if ( get_current_user_id() == get_the_author_meta('ID') ) { ?>
	<?php appthemes_load_template( 'form-review-reply.php' ); ?>
<?php } ?>

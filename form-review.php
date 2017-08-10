<form id="add-review-form" action="<?php echo site_url( 'wp-comments-post.php' ); ?>" method="post">
	<input type="hidden" name="comment_post_ID" value="<?php echo get_the_ID(); ?>" />
	<?php wp_comment_form_unfiltered_html_nonce(); ?>
	<input type="hidden" name="comment_type" value="<?php echo VA_REVIEWS_CTYPE; ?>" />
	<label>
		<?php _e( 'Rating', APP_TD ); ?> <span class="label-helper">(<?php _e( 'required', APP_TD ); ?>)</span>
        <div id="review-rating"></div>
	</label>
    
    <label>
		<?php _e( 'Review', APP_TD ); ?> <span class="label-helper">(<?php _e( 'required', APP_TD ); ?>)</span>
        <textarea name="comment" id="review_body" class="required"></textarea>
	</label>
    
    <input type="submit" value="<?php esc_attr_e( 'Submit Review', APP_TD ); ?>" />
</form>

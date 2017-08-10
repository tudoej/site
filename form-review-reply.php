<form id="reply-review-form" action="<?php echo site_url( 'wp-comments-post.php' ); ?>" method="post">
	<input type="hidden" name="comment_post_ID" value="<?php echo get_the_ID(); ?>" />
	<?php wp_comment_form_unfiltered_html_nonce(); ?>
	<input type="hidden" name="comment_type" value="<?php echo VA_REVIEWS_CTYPE; ?>" />
	<input type="hidden" name="comment_parent" id="comment_parent" value="">
	<label for="reply_body"><?php _e( 'Your Reply', APP_TD ); ?> <span class="label-helper">(<?php _e( 'required', APP_TD ); ?>)</span></label></td>
	<textarea name="comment" id="reply_body" class="required"></textarea>
	<input type="submit" value="<?php esc_attr_e( 'Submit Reply', APP_TD ); ?>" />
</form>

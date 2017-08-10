<?php

add_filter( 'map_meta_cap', 'va_restrict_review_editing', 10, 4 );

add_filter( 'preprocess_comment', 'va_process_review' );

add_action( 'transition_comment_status', 'va_refresh_average_rating', 10, 3 );

add_action( 'va_notify_comment_type_author', 'va_reviews_notify_comment_type_author', 10, 3 );
add_action( 'va_notify_comment_type_moderator', 'va_reviews_notify_comment_type_moderator', 10, 2 );
add_filter( 'va_comments_notify_intercept_'.VA_REVIEWS_CTYPE, 'va_reviews_notify_intercept' );

add_filter( 'admin_comment_types_dropdown' , 'va_review_comment_type' );

add_action( 'admin_menu', 'va_reviews_add_menu', 11 );

if ( ! is_admin() ) {
	add_filter( 'comments_clauses', 'va_exclude_reviews', 10, 2 );
}

function va_reviews_add_menu() {
	add_submenu_page( 'edit.php?post_type='.VA_LISTING_PTYPE, 'Reviews', 'Reviews', 'moderate_comments', 'edit-comments.php?comment_type='.VA_REVIEWS_CTYPE );
}

function va_process_review( $data ) {
	if ( ! isset( $_POST['comment_type'] ) || VA_REVIEWS_CTYPE != $_POST['comment_type'] ) {
		return $data;
	}

	if ( va_get_user_review_id( get_current_user_id(), $data['comment_post_ID'] ) && empty( $data['comment_parent'] ) ) {
		wp_die( __( 'You already posted a review for this listing.', APP_TD ) );
	}

	if ( empty( $_POST['review_rating'] ) && empty( $data['comment_parent'] ) ) {
		wp_die( __( 'You forgot to choose a rating.', APP_TD ) );
	}

	// Only allow the business owner to reply
	if ( $data['comment_parent'] != 0 ) {

		// Unregistered users cannot reply to reviews
		if ( $data['user_ID'] == 0 ) {
			wp_die( __( 'Unregistered users cannot reply to reviews.', APP_TD ) );
		}

		$listing = get_post( $data['comment_post_ID'] );

		// Check that it is the author's id
		if ( $data['user_ID'] != $listing->post_author ) {
			wp_die( __( 'Only the business owner can reply to the review.', APP_TD ) );
		}
	}

	$data['comment_type'] = VA_REVIEWS_CTYPE;

	add_action( 'wp_insert_comment', 'va_set_review_rating', 10, 2 );

	return $data;
}

function va_set_review_rating( $review_id, $review ) {
	$rating = isset( $_POST['review_rating'] ) ? $_POST['review_rating'] : 0;

	va_set_rating( $review_id, $rating );

	if ( 1 == $review->comment_approved ) {
		va_refresh_average_rating( '', '', $review );
	}
}

function va_refresh_average_rating( $new_status, $old_status, $review ) {
	if ( VA_REVIEWS_CTYPE != $review->comment_type ) {
		return;
	}

	$post_id = $review->comment_post_ID;

	update_post_meta( $post_id, 'rating_avg', _va_calculate_average( $post_id ) );
}

function _va_calculate_average( $post_id ) {
	$reviews = va_get_reviews( array(
		'post_id' => $post_id,
		'status' => 'approve',
		'parent' => 0,
	) );

	if ( empty( $reviews ) ) {
		return 0;
	}

	$ratings = array();

	foreach ( $reviews as $review ) {
		$ratings[] = va_get_rating( $review->comment_ID );
	}

	$num = array_sum( $ratings ) / count( $ratings );

	$ceil = ceil( $num );

	$half = $ceil - 0.5;

	if ( $num >= $half + 0.25 ) {
		return $ceil;
	} else if ( $num < $half - 0.25 ) {
		return floor( $num );
	} else {
		return $half;
	}
}

/**
 * Check if a user can add a review for a particular listing
 *
 * @param int $listing_id (optional)
 * @param int $user_id (optional)
 *
 * @return bool
 */
function va_user_can_add_reviews( $listing_id = 0, $user_id = 0 ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	$listing_id = empty( $listing_id ) ? get_the_ID() : $listing_id;

	$user_id = empty( $user_id ) ? get_current_user_id() : $user_id;

	$listing = get_post( $listing_id );

	// A user can't review their own listings
	if ( $listing->post_author == $user_id ) {
		return false;
	}

	// Reviews are disabled for this listing
	if ( 'open' != $listing->comment_status ) {
		return false;
	}

	// A user can't post more than one review per listing
	if ( va_get_user_review_id( $user_id, $listing_id ) ) {
		return false;
	}

	return true;
}

function va_restrict_review_editing( $caps, $cap, $user_id, $args ) {
	if ( 'edit_comment' == $cap ) {
		$comment = get_comment( $args[0] );

		if ( VA_REVIEWS_CTYPE == $comment->comment_type && $comment->user_id != $user_id ) {
			$caps[] = 'moderate_comments';
		}
	}

	return $caps;
}

/**
 * Get existing review on a listing by a user
 *
 * @param int     $user_id    The user id to search for
 * @param int     $listing_id The listing id to search in
 * @return array  array of review if a review exists
 */
function va_get_user_review( $user_id, $listing_id ) {
	$reviews = va_get_reviews( array(
		'post_id' => $listing_id,
		'user_id' => $user_id
	) );

	if ( empty( $reviews ) ) {
		return false;
	}

	return $reviews[0];
}

/**
 * Get existing review_id on a listing by a user
 *
 * @param int     $user_id    The user id to search for
 * @param int     $listing_id The listing id to search in
 * @return int    review_id of exiting review on a listing if exists
 */
function va_get_user_review_id( $user_id, $listing_id ) {
	$reviews = va_get_reviews( array(
		'post_id' => $listing_id,
		'user_id' => $user_id
	) );

	if ( empty( $reviews ) ) {
		return '';
	}

	return $reviews[0]->comment_ID;
}

/**
 * Returns reviews that match the given criteria
 *
 * @param array   $args get_comments style array of arguments for searching
 * @return array Resulting array of reviews
 */
function va_get_reviews( $args ) {
	$args['type'] = VA_REVIEWS_CTYPE;

	return get_comments( $args );
}

/**
 * Updates a review with new data
 *
 * @param array   $review_data New data in wp_update_comment style
 * @return boolean
 */
function va_update_review( $review_data ) {
	return wp_update_comment( $review_data );
}

/**
 * Deletes the review
 *
 * @param int     $review_id    ID of the review to be deleted
 * @param boolean $force_delete
 * @return <type>
 */
function va_delete_review( $review_id, $force_delete = false ) {
	return wp_delete_comment( $review_id, $force_delete );
}

/**
 * Updates the rating attached to a certain review
 *
 * @param int     $review_id  Review to be updated
 * @param int     $new_rating New value
 * @return <type>
 */
function va_set_rating( $review_id, $new_rating ) {
	$rating = min( 5, max( 0, (int) $new_rating ) );

	return update_comment_meta( $review_id, VA_REVIEWS_RATINGS, $new_rating );
}

/**
 * Retrieves the rating for a particular review
 *
 * @param int     $review_id Review to get value for
 * @return int
 */
function va_get_rating( $review_id ) {
	return get_comment_meta( $review_id, VA_REVIEWS_RATINGS, true );
}

/**
 * Retrieves the review count for a particular post
 *
 * @param int     $post_id post ID to get review count for
 * @return int
 */
function va_get_reviews_count( $post_id = '' ) {
	$post_id = empty( $post_id ) ? get_the_ID() : $post_id;

	return va_get_reviews( array(
		'post_id' => $post_id,
		'status' => 'approve',
		'parent' => 0,
		'count' => true
	) );
}

/**
 * Retrieves the review count for a particular user and status
 *
 * @param int     $user_id user ID to get review count for
 * @param string  $status comment status to get review count for
 * @return int
 */
function va_get_user_reviews_count( $user_id, $status ) {

	return va_get_reviews( array(
		'user_id' => $user_id,
		'status' => $status,
		'parent' => 0,
		'count' => true
	) );
}

/**
 * Retrieves the rating average rounded to the nearest half for a particular post
 *
 * @param int     $post_id post ID to get rating average for
 * @return string
 */
function va_get_rating_average( $post_id = '' ) {
	$post_id = empty( $post_id ) ? get_the_ID() : $post_id;

	return get_post_meta( $post_id, 'rating_avg', true );
}

function va_exclude_reviews( $clauses, $query ) {
	global $wpdb;

	if ( ! $query->query_vars['type'] ) {
		$clauses['where'] .= $wpdb->prepare( ' AND comment_type <> %s', VA_REVIEWS_CTYPE );
	}

	return $clauses;
}

function va_get_review_link( $review_id ) {
	add_filter( 'get_comment_link', 'va_comment_to_review' );
	$url = get_comment_link( $review_id, array( 'type' => VA_REVIEWS_CTYPE ) );
	remove_filter( 'get_comment_link', 'va_comment_to_review' );
	return $url;
}

if ( ! empty( $_POST['comment_type'] ) && VA_REVIEWS_CTYPE == $_POST['comment_type'] ) {
	add_filter( 'comment_post_redirect', 'va_comment_to_review' );
}

function va_comment_to_review( $url ) {
	return preg_replace( "/#comment-([\d]+)/", "#review-$1", $url );
}

function va_notify_listing_author( $comment_id ) {
	$comment = get_comment( $comment_id );
	$post    = get_post( $comment->comment_post_ID );
	$author  = get_userdata( $post->post_author );

	// The comment was left by the author
	if ( $comment->user_id == $post->post_author ) {
		return false;
	}

	// The author moderated a comment on his own post
	if ( $post->post_author == get_current_user_id() ) {
		return false;
	}

	// If there's no email to send the comment to
	if ( '' == $author->user_email ) {
		return false;
	}

	$comment_author_domain = @gethostbyaddr( $comment->comment_author_IP );

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$notify_message  = sprintf( __( 'New review on your listing "%s"', APP_TD ), $post->post_title ) . "\r\n";
	/* translators: 1: comment author, 2: author IP, 3: author domain */
	$notify_message .= sprintf( __( 'Reviewer : %1$s (%3$s)', APP_TD ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
	$notify_message .= sprintf( __( 'E-mail : %s', APP_TD ), $comment->comment_author_email ) . "\r\n";
	$notify_message .= sprintf( __( 'URL    : %s', APP_TD ), $comment->comment_author_url ) . "\r\n";

	$notify_message .= __( 'Review: ', APP_TD ) . "\r\n" . $comment->comment_content . "\r\n\r\n";
	$notify_message .= __( 'You can see all reviews on this listing here: ', APP_TD ) . "\r\n";
	/* translators: 1: blog name, 2: post title */
	$subject = sprintf( __( '[%1$s] Review: "%2$s"', APP_TD ), $blogname, $post->post_title );


	$notify_message .= get_permalink( $comment->comment_post_ID ) . "#reviews\r\n\r\n";
	$notify_message .= sprintf( __( 'Permalink: %s', APP_TD ), va_get_review_link( $comment_id ) ) . "\r\n";

	$wp_email = 'wordpress@' . preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );

	if ( '' == $comment->comment_author ) {
		$from = "From: \"$blogname\" <$wp_email>";
		if ( '' != $comment->comment_author_email ) {
			$reply_to = "Reply-To: $comment->comment_author_email";
		}
	} else {
		$from = "From: \"$comment->comment_author\" <$wp_email>";
		if ( '' != $comment->comment_author_email ) {
			$reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
		}
	}

	$message_headers = "$from\n"
		. "Content-Type: text/plain; charset=\"" . get_option( 'blog_charset' ) . "\"\n";

	if ( isset( $reply_to ) ) {
		$message_headers .= $reply_to . "\n";
	}

	$notify_message = apply_filters( 'comment_notification_text', $notify_message, $comment_id );
	$subject = apply_filters( 'comment_notification_subject', $subject, $comment_id );
	$message_headers = apply_filters( 'comment_notification_headers', $message_headers, $comment_id );

	@wp_mail( $author->user_email, $subject, $notify_message, $message_headers );

	return true;
}

function va_notify_listing_moderator( $comment_id ) {
	global $wpdb;

	$comment = get_comment( $comment_id );
	$post = get_post( $comment->comment_post_ID );
	$user = get_userdata( $post->post_author );
	// Send to the administration and to the post author if the author can modify the comment.
	$email_to = array( get_option( 'admin_email' ) );
	if ( user_can( $user->ID, 'edit_comment', $comment_id ) && ! empty( $user->user_email ) && ( get_option( 'admin_email' ) != $user->user_email ) ) {
		$email_to[] = $user->user_email;
	}

	$comment_author_domain = @gethostbyaddr( $comment->comment_author_IP );
	$comments_waiting = $wpdb->get_var( "SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0' AND comment_type = '" . VA_REVIEWS_CTYPE . "'" );

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$notify_message  = sprintf( __( 'A new review on the listing "%s" is waiting for your approval', APP_TD ), $post->post_title ) . "\r\n";
	$notify_message .= get_permalink( $comment->comment_post_ID ) . "\r\n\r\n";
	$notify_message .= sprintf( __( 'Author : %1$s (IP: %2$s , %3$s)', APP_TD ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
	$notify_message .= sprintf( __( 'E-mail : %s', APP_TD ), $comment->comment_author_email ) . "\r\n";
	$notify_message .= sprintf( __( 'URL    : %s', APP_TD ), $comment->comment_author_url ) . "\r\n";
	$notify_message .= sprintf( __( 'Whois  : http://whois.arin.net/rest/ip/%s', APP_TD ), $comment->comment_author_IP ) . "\r\n";
	$notify_message .= __( 'Review: ', APP_TD ) . "\r\n" . $comment->comment_content . "\r\n\r\n";

	$notify_message .= sprintf( __( 'Approve it: %s', APP_TD ),  admin_url( "comment.php?action=approve&c=$comment_id" ) ) . "\r\n";
	if ( EMPTY_TRASH_DAYS ) {
		$notify_message .= sprintf( __( 'Trash it: %s', APP_TD ), admin_url( "comment.php?action=trash&c=$comment_id" ) ) . "\r\n";
	} else {
		$notify_message .= sprintf( __( 'Delete it: %s', APP_TD ), admin_url( "comment.php?action=delete&c=$comment_id" ) ) . "\r\n";
	}
	$notify_message .= sprintf( __( 'Spam it: %s', APP_TD ), admin_url( "comment.php?action=spam&c=$comment_id" ) ) . "\r\n";

	$notify_message .= sprintf( _n( 'Currently %s review is waiting for approval. Please visit the moderation panel:',
 		'Currently %s reviews are waiting for approval. Please visit the moderation panel:', $comments_waiting, APP_TD ), number_format_i18n( $comments_waiting ) ) . "\r\n";
	$notify_message .= admin_url( "edit-comments.php?comment_status=moderated&comment_type=" . VA_REVIEWS_CTYPE ) . "\r\n";

	$subject = sprintf( __( '[%1$s] Please moderate: "%2$s"' ), $blogname, $post->post_title );
	$message_headers = '';

	$notify_message = apply_filters( 'review_moderation_text', $notify_message, $comment_id );
	$subject = apply_filters( 'review_moderation_subject', $subject, $comment_id );
	$message_headers = apply_filters( 'review_moderation_headers', $message_headers );

	foreach ( $email_to as $email ) {
		@wp_mail( $email, $subject, $notify_message, $message_headers );
	}

	return true;
}

function va_reviews_notify_intercept( $false ) {
	if ( ! empty( $_POST['comment_type'] ) && $_POST['comment_type'] == VA_REVIEWS_CTYPE ) {
		return 0;
	}
	return false;
}

function va_reviews_notify_comment_type_author( $comment, $post, $comment_approved ) {
	if ( VA_REVIEWS_CTYPE == $comment->comment_type && $comment_approved == 1 && $post->post_author != $comment->user_id ) {
		va_notify_listing_author( $comment->comment_ID );
	}
}

function va_reviews_notify_comment_type_moderator( $comment, $comment_approved ) {
	if ( VA_REVIEWS_CTYPE == $comment->comment_type && $comment_approved != 1 ) {
		va_notify_listing_moderator( $comment->comment_ID );
	}
}

function va_review_comment_type( $comment_types ) {

	$comment_types = $comment_types + array(
		VA_REVIEWS_CTYPE => __( 'Reviews', APP_TD )
	);

	return $comment_types;
}

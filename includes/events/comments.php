<?php

add_filter( 'map_meta_cap', 'va_restrict_event_comment_editing', 10, 4 );

add_filter( 'preprocess_comment', 'va_process_event_comment' );

add_action( 'va_notify_comment_type_author', 'va_event_comments_notify_comment_type_author', 10, 3 );
add_action( 'va_notify_comment_type_moderator', 'va_event_comments_notify_comment_type_moderator', 10, 2 );
add_filter( 'va_comments_notify_intercept_'.VA_EVENT_COMMENT_CTYPE, 'va_event_comments_notify_intercept' );

add_filter( 'admin_comment_types_dropdown' , 'va_event_comment_type' );

add_action( 'pre_get_comments', 'va_fix_event_comment_template_comment_query' );

add_action( 'admin_menu', 'va_event_comments_add_menu', 11 );

add_filter( 'get_avatar_comment_types', 'va_event_avatar_comment_types' );

if ( !is_admin() )
	add_filter( 'comments_clauses', 'va_exclude_event_comments', 10, 2 );

function va_event_comments_add_menu(){
	add_submenu_page( 'edit.php?post_type='.VA_EVENT_PTYPE, 'Comments', 'Comments', 'moderate_comments', 'edit-comments.php?comment_type='.VA_EVENT_COMMENT_CTYPE );
}

function va_process_event_comment( $data ) {

	if ( !isset( $_POST['comment_type'] ) || VA_EVENT_COMMENT_CTYPE != $_POST['comment_type'] )
		return $data;

	$data['comment_type'] = VA_EVENT_COMMENT_CTYPE;

	return $data;
}

function va_restrict_event_comment_editing( $caps, $cap, $user_id, $args ) {
	if ( 'edit_comment' == $cap ) {
		$comment = get_comment( $args[0] );

		if ( VA_EVENT_COMMENT_CTYPE == $comment->comment_type && $comment->user_id != $user_id )
			$caps[] = 'moderate_comments';
	}

	return $caps;
}

/**
 * Get existing comment on an event by a user
 *
 * @param int     $user_id    The user id to search for
 * @param int     $event_id The event id to search in
 * @return array  array of comments if a comment exists
 */
function va_get_user_event_comment( $user_id, $event_id ) {
	$comments = va_get_event_comments( array(
		"post_id" => $event_id,
		"user_id" => $user_id
	) );

	if ( empty($comments) )
		return false;

	return $comments[0];
}

/**
 * Get existing comment_id on a event by a user
 *
 * @param int     $user_id    The user id to search for
 * @param int     $event_id The event id to search in
 * @return int    comment_id of exiting comment on an event if exists
 */
function va_get_user_event_comment_id( $user_id, $listing_id ) {
	$comments = va_get_event_comments( array(
		"post_id" => $event_id,
		"user_id" => $user_id
	) );

	if ( empty( $comments ) )
		return '';

	return $comments[0]->comment_ID;
}

/**
 * Returns commentss that match the given criteria
 *
 * @param array   $args get_comments style array of arguments for searching
 * @return array Resulting array of comments
 */
function va_get_event_comments( $args ) {
	$args['type'] = VA_EVENT_COMMENT_CTYPE;

	return get_comments( $args );
}

/**
 * Updates a comment with new data
 *
 * @param array   $comment_data New data in wp_update_comment style
 * @return boolean
 */
function va_update_event_comment( $comment_data ) {
	return wp_update_comment( $comment_data );
}

/**
 * Deletes the comment
 *
 * @param int     $comment_id    ID of the comment to be deleted
 * @param boolean $force_delete
 * @return <type>
 */
function va_delete_event_comment( $comment_id, $force_delete = false ) {
	return wp_delete_comment( $comment_id, $force_delete );
}

/**
 * Retrieves the comment count for a particular event
 *
 * @param int     $event_id event ID to get comment count for
 * @return int
 */
function va_get_event_comments_count( $event_id = '' ) {
	$event_id = empty( $event_id ) ? get_the_ID() : $event_id;

	return va_get_event_comments( array(
		'post_id' => $event_id,
		'status' => 'approve',
		'parent' => 0,
		'count' => true
	) );
}

/**
 * Retrieves the comment count for a particular user and status
 *
 * @param int     $user_id user ID to get comment count for
 * @param string  $status comment status to get comment count for
 * @return int
 */
function va_get_user_event_comments_count( $user_id, $status ) {

	return va_get_event_comments( array(
		'user_id' => $user_id,
		'status' => $status,
		'parent' => 0,
		'count' => true
	) );
}


function va_exclude_event_comments( $clauses, $query ) {
	global $wpdb;

	if ( ! $query->query_vars['type'] )
		$clauses['where'] .= $wpdb->prepare( ' AND comment_type <> %s', VA_EVENT_COMMENT_CTYPE );

	return $clauses;
}

function va_get_event_comment_link( $comment_id ){
	return get_comment_link( $comment_id, array( 'type' => VA_EVENT_COMMENT_CTYPE ) );
}

function va_notify_event_author( $comment_id ) {
	$comment = get_comment( $comment_id );
	$post    = get_post( $comment->comment_post_ID );
	$author  = get_userdata( $post->post_author );

	// The comment was left by the author
	if ( $comment->user_id == $post->post_author )
		return false;

	// The author moderated a comment on his own post
	if ( $post->post_author == get_current_user_id() )
		return false;

	// If there's no email to send the comment to
	if ( '' == $author->user_email )
		return false;

	$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$notify_message  = sprintf( __( 'New comment on your event "%s"', APP_TD ), $post->post_title ) . "\r\n";
	/* translators: 1: comment author, 2: author IP, 3: author domain */
	$notify_message .= sprintf( __('Commenter : %1$s (%3$s)', APP_TD), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
	$notify_message .= sprintf( __('E-mail : %s', APP_TD), $comment->comment_author_email ) . "\r\n";
	$notify_message .= sprintf( __('URL    : %s', APP_TD), $comment->comment_author_url ) . "\r\n";

	$notify_message .= __('Comment: ', APP_TD) . "\r\n" . $comment->comment_content . "\r\n\r\n";
	$notify_message .= __('You can see all comments on this event here: ', APP_TD) . "\r\n";
	/* translators: 1: blog name, 2: post title */
	$subject = sprintf( __('[%1$s] Comment: "%2$s"', APP_TD), $blogname, $post->post_title );


	$notify_message .= get_permalink($comment->comment_post_ID) . "#comments\r\n\r\n";
	$notify_message .= sprintf( __('Permalink: %s', APP_TD), va_get_event_comment_link( $comment_id ) ) . "\r\n";

	$wp_email = 'wordpress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));

	if ( '' == $comment->comment_author ) {
		$from = "From: \"$blogname\" <$wp_email>";
		if ( '' != $comment->comment_author_email )
			$reply_to = "Reply-To: $comment->comment_author_email";
	} else {
		$from = "From: \"$comment->comment_author\" <$wp_email>";
		if ( '' != $comment->comment_author_email )
			$reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
	}

	$message_headers = "$from\n"
		. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";

	if ( isset($reply_to) )
		$message_headers .= $reply_to . "\n";

	$notify_message = apply_filters('comment_notification_text', $notify_message, $comment_id);
	$subject = apply_filters('comment_notification_subject', $subject, $comment_id);
	$message_headers = apply_filters('comment_notification_headers', $message_headers, $comment_id);

	@wp_mail( $author->user_email, $subject, $notify_message, $message_headers );

	return true;
}

function va_notify_event_moderator($comment_id) {
	global $wpdb;

	$comment = get_comment($comment_id);
	$post = get_post($comment->comment_post_ID);
	$user = get_userdata( $post->post_author );
	// Send to the administration and to the post author if the author can modify the comment.
	$email_to = array( get_option('admin_email') );
	if ( user_can($user->ID, 'edit_comment', $comment_id) && !empty($user->user_email) && ( get_option('admin_email') != $user->user_email) )
		$email_to[] = $user->user_email;

	$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);
	$comments_waiting = $wpdb->get_var("SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0' AND comment_type = '" . VA_EVENT_COMMENT_CTYPE . "'");

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$notify_message  = sprintf( __('A new comment on the event "%s" is waiting for your approval', APP_TD), $post->post_title ) . "\r\n";
	$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
	$notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)', APP_TD), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
	$notify_message .= sprintf( __('E-mail : %s', APP_TD), $comment->comment_author_email ) . "\r\n";
	$notify_message .= sprintf( __('URL    : %s', APP_TD), $comment->comment_author_url ) . "\r\n";
	$notify_message .= sprintf( __('Whois  : http://whois.arin.net/rest/ip/%s', APP_TD), $comment->comment_author_IP ) . "\r\n";
	$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";

	$notify_message .= sprintf( __('Approve it: %s', APP_TD),  admin_url("comment.php?action=approve&c=$comment_id") ) . "\r\n";
	if ( EMPTY_TRASH_DAYS )
		$notify_message .= sprintf( __('Trash it: %s', APP_TD), admin_url("comment.php?action=trash&c=$comment_id") ) . "\r\n";
	else
		$notify_message .= sprintf( __('Delete it: %s', APP_TD), admin_url("comment.php?action=delete&c=$comment_id") ) . "\r\n";
	$notify_message .= sprintf( __('Spam it: %s', APP_TD), admin_url("comment.php?action=spam&c=$comment_id") ) . "\r\n";

	$notify_message .= sprintf( _n('Currently %s event comment is waiting for approval. Please visit the moderation panel:',
 		'Currently %s event comments are waiting for approval. Please visit the moderation panel:', $comments_waiting), number_format_i18n($comments_waiting) ) . "\r\n";
	$notify_message .= admin_url("edit-comments.php?comment_status=moderated&comment_type=".VA_EVENT_COMMENT_CTYPE) . "\r\n";

	$subject = sprintf( __('[%1$s] Please moderate: "%2$s"'), $blogname, $post->post_title );
	$message_headers = '';

	$notify_message = apply_filters('event_comment_moderation_text', $notify_message, $comment_id);
	$subject = apply_filters('event_comment_moderation_subject', $subject, $comment_id);
	$message_headers = apply_filters('event_comment_moderation_headers', $message_headers);

	foreach ( $email_to as $email )
		@wp_mail($email, $subject, $notify_message, $message_headers);

	return true;
}

function va_event_comments_notify_intercept( $false ) {
	return 0;
}

function va_event_comments_notify_comment_type_author( $comment, $post, $comment_approved ) {
	if ( ( VA_EVENT_COMMENT_CTYPE == $comment->comment_type ) && $comment_approved == 1 && ( $post->post_author != $comment->user_id ) ) {
		va_notify_event_author($comment->comment_ID);
	}
}

function va_event_comments_notify_comment_type_moderator( $comment, $comment_approved ) {
	if ( VA_EVENT_COMMENT_CTYPE == $comment->comment_type && $comment_approved !=1 ) {
		va_notify_event_moderator( $comment->comment_ID );
	}
}

function va_event_comment_type($comment_types) {

	$comment_types = $comment_types + array(
		VA_EVENT_COMMENT_CTYPE => __('Comments', APP_TD)
	);

	return $comment_types;
}

function va_event_comment_form() {
	$commenter = wp_get_current_user();
	$req = get_option( 'require_name_email' );
	$aria_req = ( $req ? ' aria-required="true"' : '' );

	$args = array(
		'title_reply' => __( 'Leave a Reply', APP_TD ),
		'title_reply_to' => __( 'Leave a Reply to %s', APP_TD ),
		'cancel_reply_link' => __( 'Cancel Reply', APP_TD ),
		'label_submit' => __( 'Submit comment', APP_TD ),
		'comment_field' => '<input type="hidden" name="comment_type" value="'.VA_EVENT_COMMENT_CTYPE.'" ><p class="comment-form-comment"><label for="comment">' . _x( 'Comment', 'noun', APP_TD ) . '</label><textarea id="comment" name="comment" cols="45" rows="8" '.$aria_req.'></textarea></p>',
		'must_log_in' => '<p class="must-log-in">' .  sprintf( __( 'You must be <a href="%s">logged in</a> to post a comment.', APP_TD ), wp_login_url( apply_filters( 'the_permalink', get_permalink( ) ) ) ) . '</p>',
		'logged_in_as' => '<p class="logged-in-as">' . sprintf( __( 'Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>', APP_TD ), appthemes_get_edit_profile_url(), $commenter->display_name, wp_logout_url( apply_filters( 'the_permalink', get_permalink( ) ) ) ) . '</p>',
		'comment_notes_before' => '<p class="comment-notes">' . __( 'Your email address will not be published.', APP_TD ) . ( $req ? sprintf( '<span class="required_text">%s</span>', __( ' (* denotes required field)', APP_TD )) : '' ) . '</p>',
		'comment_notes_after' => '',
		'fields' => apply_filters( 'comment_form_default_fields',
			array(
				'author' => '<p class="comment-form-author">' . '<label for="author">' . __( 'Name', APP_TD ) . '</label> ' . ( $req ? '<span class="required">*</span>' : '' ) . '<input id="author" name="author" type="text" value="' . esc_attr( $commenter->display_name ) . '" size="30"' . $aria_req . ' /></p>',

				'email' => '<p class="comment-form-email"><label for="email">' . __( 'Email', APP_TD ) . '</label> ' . ( $req ? '<span class="required">*</span>' : '' ) . '<input id="email" name="email" type="text" value="' . esc_attr(  $commenter->user_email ) . '" size="30"' . $aria_req . ' /></p>',

				'url' => '' )
			)
	);

	comment_form( apply_filters( 'va_event_comment_form', $args ) );
}

function va_event_list_comments() {
	$args = array(
		'callback' => 'va_event_comment',
		'style' => 'div',
		'type' => VA_EVENT_COMMENT_CTYPE
	);

	wp_list_comments( apply_filters( 'va_event_list_comments', $args ) );
}

function va_event_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;

	global $post;

	if( !empty( $comment->user_id ) ) {
		$user = get_userdata( $comment->user_id );
		$user_url = va_dashboard_url( 'event-comments', $user->ID );
	}
?>
	<div <?php comment_class('comment'); ?> id="comment-<?php echo $comment->comment_ID; ?>">
		<div class="comment-inner">
			<div class="comment-meta">
				<div class="comment-author">
					<?php
					if ( !empty( $comment->user_id ) ) {
					?>
					<?php echo html_link( $user_url, get_avatar( $user->ID, 45 ) ); ?>
					<ul class="comment-author-meta">
						<li><strong><?php echo html_link( $user_url, $user->display_name ); ?></strong></li>
						<li><?php echo esc_html( $user->location ); ?></li>
						<li><?php _e( 'Member Since:' , APP_TD ); ?> <?php echo mysql2date( get_option('date_format'), $user->user_registered ); ?></li>
					</ul>
					<?php
					} else {
						echo get_avatar( $comment, 45 );
						echo get_comment_author_link();
					} ?>
					<div class="comment-author-reply">
						<?php comment_reply_link( array_merge( $args, array( 'reply_text' => __( 'Reply', APP_TD ), 'after' => ' <span></span>', 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
					</div>
				</div>
			</div>
			<div class="comment-content">
				<p class="comment-date"><?php echo html_link( va_get_event_comment_link( $comment->comment_ID ), mysql2date( get_option( 'date_format' ), $comment->comment_date ) ); ?></p>
				<div class="clear"></div>
				<?php if ( '0' == $comment->comment_approved ) : ?>
				<p class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', APP_TD ); ?></p>
				<?php endif; ?>
				<?php comment_text(); ?>
			</div>

			<div class="clear"></div>
		</div>
		<?php // Note: the ending </div> is ommitted here on purpose, see http://codex.wordpress.org/Function_Reference/wp_list_comments, see $callback parameter ?>
	<?php
}

function va_fix_event_comment_template_comment_query( $wp_comment_query ) {
	global $post;

	if ( isset( $post->post_type ) && VA_EVENT_PTYPE == $post->post_type && VA_REVIEWS_CTYPE !== $wp_comment_query->query_vars['type'] ) {
		$wp_comment_query->query_vars['type'] = VA_EVENT_COMMENT_CTYPE;
	}

}

function va_event_avatar_comment_types( $ctypes ) {
    $ctypes[] = VA_EVENT_COMMENT_CTYPE;
    return $ctypes;
}
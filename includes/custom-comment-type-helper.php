<?php

add_filter( 'pre_option_comments_notify', 'va_comments_notify_intercept', 99, 1);
add_filter( 'pre_option_moderation_notify', 'va_comments_notify_intercept', 99, 1);

add_action( 'comment_post', 'va_comment_post', 10, 2 );

add_action( 'wp_set_comment_status', 'va_set_comment_status', 10, 2 );

function va_handle_comments_notify($comment_id, $comment_approved = 1) {
	$all_options = wp_load_alloptions();
	if ( 1 != $all_options['comments_notify'] ) return;

	$comment = get_comment( $comment_id );
	$post = get_post( $comment->comment_post_ID );

	do_action('va_notify_comment_type_author', $comment, $post, $comment_approved );

	if ( 1 != $all_options['moderation_notify'] ) return;

	do_action('va_notify_comment_type_moderator', $comment, $comment_approved );
}

function va_comment_post( $comment_id, $comment_approved ) {
	va_handle_comments_notify( $comment_id, $comment_approved );
}

function va_comments_notify_intercept($option) {

	if ( !empty( $_POST['comment_type'] ) ) {
		$comment_type = $_POST['comment_type'];
	} elseif( !empty( $_POST['action'] ) && 'dim-comment' == $_POST['action'] ) {
		$comment = $comment = get_comment( $_POST['id'] );
		$comment_type = $comment->comment_type;
	}

	if ( empty( $comment_type ) ) return false;

	$interceptor = apply_filters('va_comments_notify_intercept_' . $comment_type, false );

	if ( false !== $interceptor )
		return $interceptor;

	return false;
}

function va_set_comment_status($comment_id, $comment_status) {
	if( in_array($comment_status, array('approve', '1'))  ) {
		va_handle_comments_notify($comment_id, 1);
	}
}

<?php

add_action( 'transition_post_status', 'va_event_notifications', 10, 3 );


function va_event_notifications( $new_status, $old_status, $post ) {
	if ( VA_EVENT_PTYPE != $post->post_type ) {
		return;
	}

	if ( 'pending' == $new_status && 'publish' != $old_status ) {
		va_send_pending_event_notification( $post );

	} elseif ( 'publish' == $new_status && 'pending' == $old_status ) {
		va_send_approved_event_notification( $post );

	} elseif ( 'publish' == $new_status && 'draft' == $old_status ) {
		va_send_publish_event_notification( $post );

	} elseif ( 'expired' == $new_status && 'publish' == $old_status ) {
		va_send_expired_event_notification( $post );
	}
}

function va_send_publish_event_notification( $post ) {

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject = sprintf( __( '[%s] Published Event: "%s"', APP_TD ), $blogname, $post->post_title );
	$recipient = get_user_by( 'id', $post->post_author );

	$content = html( 'p', sprintf(
		__( 'A new event is successfully published: %s', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title ) ) );

	$args = array(
		'to' => get_option( 'admin_email' ),
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'appthemes_notify_admin_publish_listing', $args );

	$content = html( 'p', sprintf(
		__( 'Your "%s" event has been published.', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title )
	) );

	$args = array(
		'to' => $recipient->user_email,
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'appthemes_notify_user_publish_listing', $args );
}

function va_send_pending_event_notification( $post ) {
	$content = '';

	$content .= html( 'p', sprintf(
		__( 'A new event is awaiting moderation: %s', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title ) ) );

	$content .= html( 'p', html_link(
		admin_url( 'edit.php?post_status=pending&post_type=event' ),
		__( 'Review pending events', APP_TD ) ) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%s] Pending Event: "%s"', APP_TD ), $blogname, $post->post_title );

	$args = array(
		'to' => get_option( 'admin_email' ),
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'appthemes_notify_admin_pending_listing', $args );
}

function va_send_approved_event_notification( $post ) {
	$recipient = get_user_by( 'id', $post->post_author );

	$content = '';

	$content .= html( 'p', sprintf( __( 'Hello %s,', APP_TD ), $recipient->display_name ) );

	$content .= html( 'p', sprintf(
		__( 'Your "%s" event has been approved.', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title )
	) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%s] Event Approved: "%s"', APP_TD ), $blogname, $post->post_title );

	$args = array(
		'to' => $recipient->user_email,
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'appthemes_notify_user_approved_listing', $args );
}

function va_send_expired_event_notification( $post ) {
	$recipient = get_user_by( 'id', $post->post_author );

	$content = '';

	$content .= html( 'p', sprintf( __( 'Hello %s,', APP_TD ), $recipient->display_name) );

	$content .= html( 'p', sprintf(
		__( 'Your "%s" event has expired (it is not visible to the public anymore).', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title )
	) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%s] Event Expired: "%s"', APP_TD ), $blogname, $post->post_title );

	$args = array(
		'to' => $recipient->user_email,
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'appthemes_notify_user_expired_listing', $args );
}


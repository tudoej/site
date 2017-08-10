<?php
/**
 * Emails processing
 *
 * @package Vantage\Emails
 * @author  AppThemes
 * @since   Vantage 1.0
 */

add_action( 'appthemes_transaction_completed', 'va_send_receipt' );
add_action( 'appthemes_transaction_failed', 'va_send_admin_failed_transaction' );
add_action( 'transition_post_status', 'va_new_listing_notification', 10, 3 );


/**
 * A wrapper for appthemes_send_email()
 *
 * @uses appthemes_send_email()
 * @param array $args An array of wp_mail() arguments, including the "to",
 *                    "subject", "message", "headers", "attachments" and
 *                    other custom values ("listing" or "order", etc.).
 */
function va_appthemes_send_email( $id, $args = array() ) {
	$defaults = array(
		'to'          => '',
		'subject'     => '',
		'message'     => '',
		'attachments' => array(),
		'headers'     => array(
			'type' => 'Content-Type: text/html; charset="' . get_bloginfo( 'charset' ) . '"',
		),
	);

	$args =  wp_parse_args( apply_filters( $id, $args ), $defaults );

	appthemes_send_email( $args['to'], $args['subject'], $args['message'] );
}

/**
 * Determines email type and sends notifications.
 *
 * @param string $new_status
 * @param string $old_status
 * @param object $post
 *
 * @return void
 */
function va_new_listing_notification( $new_status, $old_status, $post ) {
	if ( VA_LISTING_PTYPE != $post->post_type ) {
		return;
	}

	if ( 'pending' == $new_status ) {
		va_send_pending_listing_notification( $post );

	} elseif ( 'publish' == $new_status && 'pending' == $old_status ) {
		va_send_approved_notification( $post );

	} elseif ( 'publish' == $new_status && 'draft' == $old_status ) {
		va_send_publish_notification( $post );

	} elseif ( 'expired' == $new_status && 'publish' == $old_status ) {
		va_send_expired_notification( $post );

	} elseif ( 'pending-claimed' == $new_status ) {
		va_send_admin_pending_claimed_listing_notification( $post );
		va_send_claimee_pending_claimed_listing_notification( $post );
	}

}

/**
 * Sends emails to admin and user after listing published
 *
 * @param WP_Post $post published listing object
 */
function va_send_publish_notification( $post ) {

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject = sprintf( __( '[%s] Published Listing: "%s"', APP_TD ), $blogname, $post->post_title );
	$recipient = get_user_by( 'id', $post->post_author );

	$content = html( 'p', sprintf(
		__( 'A new listing is successfully published: %s', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title ) ) );

	$args = array(
		'to' => get_option( 'admin_email' ),
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'appthemes_notify_admin_publish_listing', $args );

	$content = html( 'p', sprintf(
		__( 'Your "%s" listing has been published.', APP_TD ),
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

/**
 * Sends email with receipt to customer after completed purchase.
 *
 * @param object $order
 *
 * @return void
 */
function va_send_receipt( $order ) {
	global $va_options;

	$recipient = get_user_by( 'id', $order->get_author() );

	$item = array();
	foreach ( $order->get_items() as $item ) {
		$ptype_obj = get_post_type_object( $item['post']->post_type );
		if ( ! $ptype_obj->public ) {
			continue;
		}
		break;
	}

	if ( empty( $item ) ) {
		return;
	}

	if ( VA_LISTING_PTYPE === $item['post']->post_type && ! $va_options->listing_charge ) {
		return;
	}

	if ( defined( 'VA_EVENT_PTYPE' ) && VA_EVENT_PTYPE === $item['post']->post_type && ! $va_options->event_charge ) {
		return;
	}

	$item_link = html( 'p', html_link( get_permalink( $item['post']->ID ), $item['post']->post_title ) );

	$table = new APP_Order_Summary_Table( $order );
	ob_start();
	$table->show();
	$table_output = ob_get_clean();

	$content = '';
	$content .= html( 'p', sprintf( __( 'Hello %s,', APP_TD ), $recipient->display_name ) );
	$content .= html( 'p', __( 'This email confirms that you have purchased the following item:', APP_TD ) );
	$content .= $item_link;
	$content .= html( 'p', __( 'Order Summary:', APP_TD ) );
	$content .= $table_output;

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%s] Receipt for your order #%d', APP_TD ), $blogname, $order->get_id() );

	$args = array(
		'to' => $recipient->user_email,
		'subject' => $subject,
		'message' => $content,
		'order'   => $order
	);

	va_appthemes_send_email( 'appthemes_send_user_receipt', $args );
}

/**
 * Sends email notification to admin if payment failed.
 *
 * @param object $order
 *
 * @return void
 */
function va_send_admin_failed_transaction( $order ) {

	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject = sprintf( __( '[%1$s] Failed Order #%2$d', APP_TD ), $blogname, $order->get_id() );

	$content = '';
	$content .= html( 'p', sprintf( __( 'Payment for the order #%s has failed.', APP_TD ), $order->get_id() ) );
	$content .= html( 'p', sprintf( __( 'Please <a href="%s">review this order</a>, and if necessary disable assigned services.', APP_TD ), get_edit_post_link( $order->get_id() ) ) );

	$args = array(
		'to' => get_option( 'admin_email' ),
		'subject' => $subject,
		'message' => $content,
		'order'   => $order
	);

	va_appthemes_send_email( 'appthemes_notify_admin_failed_transaction', $args );
}

/**
 * Sends notification to admin about new pending listing.
 *
 * @param object $post
 *
 * @return void
 */
function va_send_pending_listing_notification( $post ) {
	$content = '';

	$content .= html( 'p', sprintf(
		__( 'A listing is awaiting moderation: %s', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title ) ) );

	$content .= html( 'p', html_link(
		admin_url( 'edit.php?post_status=pending&post_type=listing' ),
		__( 'Review pending listings', APP_TD ) ) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%s] Pending Listing: "%s"', APP_TD ), $blogname, $post->post_title );

	$args = array(
		'to' => get_option( 'admin_email' ),
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'appthemes_notify_admin_pending_listing', $args );
}

/**
 * Sends notification to admin about new pending claimed listing.
 *
 * @param object $post
 *
 * @return void
 */
function va_send_admin_pending_claimed_listing_notification( $post ) {
	$content = '';

	$content .= html( 'p', sprintf(
		__( 'A new listing claim is awaiting moderation: %s', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title ) ) );

	$content .= html( 'p', html_link(
		admin_url( 'edit.php?post_status=pending-claimed&post_type=listing' ),
		__( 'Review pending claimed listings', APP_TD ) ) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%s] Pending Claimed Listing: "%s"', APP_TD ), $blogname, $post->post_title );

	$args = array(
		'to' => get_option( 'admin_email' ),
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'va_email_pending_claimed_listing', $args );
}

/**
 * Sends notification to claimee about pending claimed listing.
 *
 * @param object $post
 *
 * @return void
 */
function va_send_claimee_pending_claimed_listing_notification( $post ) {
	$recipient_id = get_post_meta( $post->ID, 'claimee', true );
	$recipient = get_user_by( 'id', $recipient_id );

	$content = '';

	$content .= html( 'p', sprintf( __( 'Hello %s,', APP_TD ), $recipient->display_name ) );

	$content .= html( 'p', sprintf(
		__( 'Your "%s" listing claim is awaiting approval.', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title )
	) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%s] Claimed Listing Pending: "%s"', APP_TD ), $blogname, $post->post_title );

	$args = array(
		'to' => $recipient->user_email,
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'va_email_claimee_pending_claimed_listing', $args );
}

/**
 * Sends notification to user when his listing has been approved.
 *
 * @param object $post
 *
 * @return void
 */
function va_send_approved_notification( $post ) {
	$recipient = get_user_by( 'id', $post->post_author );

	$content = '';

	$content .= html( 'p', sprintf( __( 'Hello %s,', APP_TD ), $recipient->display_name ) );

	$content .= html( 'p', sprintf(
		__( 'Your "%s" listing has been approved.', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title )
	) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%s] Listing Approved: "%s"', APP_TD ), $blogname, $post->post_title );

	$args = array(
		'to' => $recipient->user_email,
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'appthemes_notify_user_approved_listing', $args );
}


/**
 * Sends notification to user when his listing claim has been approved.
 *
 * @param object $post
 *
 * @return void
 */
function va_send_approved_claim_notification( $post ) {
	$post = get_post( $post->ID );

	$recipient = get_user_by( 'id', $post->post_author );

	$content = '';

	$content .= html( 'p', sprintf( __( 'Hello %s,', APP_TD ), $recipient->display_name ) );

	$content .= html( 'p', sprintf(
		__( 'Your "%s" listing claim has been approved.', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title )
	) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%s] Claimed Listing Approved: "%s"', APP_TD ), $blogname, $post->post_title );

	$args = array(
		'to' => $recipient->user_email,
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'va_email_listing_claim_approved', $args );
}

/**
 * Sends notification to user when his listing claim has been rejected.
 *
 * @param object $post
 * @param int $recipient_id (optional)
 *
 * @return void
 */
function va_send_rejected_claim_notification( $post, $recipient_id = 0 ) {

	$recipient_id = !empty( $recipient_id ) ? $recipient_id : get_post_meta( $post->ID, 'rejected_claimee', true );
	$recipient = get_user_by( 'id', $recipient_id );

	$content = '';

	$content .= html( 'p', sprintf( __( 'Hello %s,', APP_TD ), $recipient->display_name ) );

	$content .= html( 'p', sprintf(
		__( 'Your "%s" listing claim has been denied.', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title )
	) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%s] Listing Claim Denied: "%s"', APP_TD ), $blogname, $post->post_title );

	$args = array(
		'to' => $recipient->user_email,
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'va_email_listing_claim_rejected', $args );
}

/**
 * Sends notification to user when his listing has expired.
 *
 * @param object $post
 *
 * @return void
 */
function va_send_expired_notification( $post ) {

	$send = apply_filters( 'va_send_expired_notification_' . $post->ID, true, $post );
	if ( ! $send ) {
		return;
	}

	$recipient = get_user_by( 'id', $post->post_author );

	$content = '';

	$content .= html( 'p', sprintf( __( 'Hello %s,', APP_TD ), $recipient->display_name) );

	$content .= html( 'p', sprintf(
		__( 'Your listing: "%s" has expired (it is not visible to the public anymore).', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title )
	) );

	$content .= html( 'p', html_link( va_get_listing_renew_url( $post->ID ), __( 'Renew listing!', APP_TD ) ) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%s] Listing Expired: "%s"', APP_TD ), $blogname, $post->post_title );

	$args = array(
		'to' => $recipient->user_email,
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'appthemes_notify_user_expired_listing', $args );
}

/**
 * Sends notification to user when his listing upgrade has expired.
 *
 * @param object $post
 * @param string $upgrade
 *
 * @return void
 */
function va_send_expired_upgrade_notification( $post, $upgrade ) {
	$send = apply_filters( 'va_send_expired_upgrade_notification_' . $post->ID, true, $post, $upgrade );
	if ( ! $send ) {
		return;
	}

	$recipient = get_user_by( 'id', $post->post_author );

	$content = '';

	$content .= html( 'p', sprintf( __( 'Hello %s,', APP_TD ), $recipient->display_name ) );

	$content .= html( 'p', sprintf(
		__( 'Your upgrade: "%s" has expired for your listing: "%s".', APP_TD ),
		$upgrade,
		html_link( get_permalink( $post ), $post->post_title )
	) );

	$content .= html( 'p', html_link( va_get_listing_purchase_url( $post->ID ), __( 'Upgrade listing!', APP_TD ) ) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%s] Listing Upgrade Expired for: "%s"', APP_TD ), $blogname, $post->post_title );

	$args = array(
		'to' => $recipient->user_email,
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'va_email_listing_upgrade_expired', $args );
}

/**
 * Sends notification to user when his subscription has expired.
 *
 * @param object $post
 *
 * @return void
 */
function va_send_expired_subscription_expired_notification( $post ) {
	$args = func_get_args();

	$recipient = get_user_by( 'id', $post->post_author );

	$content = '';

	$content .= html( 'p', sprintf( __( 'Hello %s,', APP_TD ), $recipient->display_name ) );

	$content .= html( 'p', sprintf(
		__( 'The most recent payment attempt for the subscription plan for listing: "%s" was not successful, either due to the payment gateway subscription period coming to completion or manual cancelation.', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title )
	) );

	$content .= html( 'p', sprintf (
		__( 'Your listing "%1$s" will expire on: %2$s', APP_TD ),
		html_link( get_permalink( $post ), $post->post_title ),
		mysql2date( get_option('date_format'), va_get_listing_exipration_date( $post->ID ) )
	) );

	$content .= html( 'p', __( 'After your Listing expires, you will receive an email with renewal details.', APP_TD ) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%1$s] Subscription Ended - Listing: "%2$s"', APP_TD ), $blogname, $post->post_title );

	$args = array(
		'to' => $recipient->user_email,
		'subject' => $subject,
		'message' => $content,
		'listing' => $post
	);

	va_appthemes_send_email( 'va_email_subscription_expired', $args );
}

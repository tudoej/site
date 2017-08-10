<?php

add_action( 'pending_to_publish', 'va_update_listing_start_date' );
add_action( 'draft_to_publish', 'va_update_listing_start_date' );
add_action( 'pending-claimed_to_publish', 'va_update_listing_start_date' );

add_action( 'after_setup_theme', 'va_setup_payment_transition_hook', 1000 );

add_action( 'init', 'va_schedule_listing_prune' );
add_action( 'va_prune_expired_listings', 'va_prune_expired_listings' );

add_filter( 'posts_clauses', 'va_expired_listing_sql', 10, 2 );

function va_maybe_publish_listing( $listing_id ) {
	global $va_options;

	if ( $va_options->moderate_listings ) {
		wp_update_post( array(
			'ID' => $listing_id,
			'post_status' => 'pending'
		) );
	}
	else {
		wp_update_post( array(
			'ID' => $listing_id,
			'post_status' => 'publish'
		) );
	}

}

function va_update_listing_start_date( $post ) {
	global $va_options;
	if ( $post->post_type == VA_LISTING_PTYPE ) {
		wp_update_post( array(
			"ID" => $post->ID,
			"post_date" => current_time( 'mysql' )
		) );

	}
}

function va_schedule_listing_prune() {
	if ( !wp_next_scheduled( 'va_prune_expired_listings' ) )
		wp_schedule_event( time(), 'hourly', 'va_prune_expired_listings' );
}

function va_prune_expired_listings() {

	$expired_posts = new WP_Query( array(
		'post_type' => VA_LISTING_PTYPE,
		'expired_listings' => true,
		'nopaging' => true,
	) );

	foreach ( $expired_posts->posts as $post ) {
		if ( $order = _va_postpone_recurring_order_expiraton( $post->ID ) ){
			continue;
		}
		wp_update_post( array(
			'ID' => $post->ID,
			'post_status' => 'expired'
		) );
	}
}

function va_expired_listing_sql( $clauses, $wp_query ) {
	global $wpdb;

	if ( $wp_query->get( 'expired_listings' ) ) {
		$clauses['join'] .= " INNER JOIN " . $wpdb->postmeta ." AS exp1 ON (" . $wpdb->posts .".ID = exp1.post_id)";

		$clauses['where'] .= " AND ( exp1.meta_key = 'listing_duration' AND DATE_ADD(post_date, INTERVAL exp1.meta_value DAY) < '" . current_time( 'mysql' ) . "' AND exp1.meta_value > 0 )";
	}

	return $clauses;
}

function _va_postpone_recurring_order_expiraton( $listing_id ) {
	return _va_get_pending_recurring_listing_order( $listing_id );
}

function va_get_all_orders_in_subscription( $order ) {
	 if ( !$order->is_recurring() )
	 	return;

	$orders = array();

	$parent_order_id = $order->get_id();
	for( ;; ) {
		$this_order = new WP_Query( array(
			'post_type' => APPTHEMES_ORDER_PTYPE,
			'nopaging' => true,
			'post__in' => array($parent_order_id)
		) );

		$orders[$this_order->post->ID] = $this_order->post;

		$previous_order = new WP_Query( array(
			'post_type' => APPTHEMES_ORDER_PTYPE,
			'nopaging' => true,
			'post__in' => array($this_order->post->post_parent)
		) );

		$orders[$previous_order->post->ID] = $previous_order->post;
		if ( $previous_order->post->post_parent != 0 ) {

			$parent_order_id = $previous_order->post->ID;
		} else {
			break;
		}
	}
	return $orders;
}

function va_setup_payment_transition_hook() {
	add_action( APPTHEMES_ORDER_PENDING . '_to_' . APPTHEMES_ORDER_FAILED, '_va_handle_failed_recurring_order' );
}

function _va_handle_failed_recurring_order( $post ) {

	$order = appthemes_get_order( $post->ID );

	if( !_va_is_recurring_order( $order ) )
		return;

	$orders = va_get_all_orders_in_subscription( $order );

	if ( count( $orders) <= 1 )
		return;

	$order_info = _va_get_order_listing_info( $order );

	$listing_id = $order_info['listing']->ID;

	va_send_expired_subscription_expired_notification( get_post( $listing_id ) );
}

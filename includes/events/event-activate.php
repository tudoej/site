<?php

add_action( 'appthemes_transaction_completed', 'va_event_handle_completed_transaction' );
add_action( 'pending_to_publish', '_va_event_handle_moderated_transaction');

add_action( 'appthemes_transaction_activated', '_va_event_activate_plan');
add_action( 'appthemes_transaction_activated', '_va_event_activate_addons');

function va_event_handle_completed_transaction( $order ){
	global $va_options;

	if( get_post_type( _va_get_order_post_id( $order ) ) != VA_EVENT_PTYPE )
		return;

	$needs_moderation = false;

	$event_id = _va_get_order_post_id( $order );

	if ( $va_options->moderate_events ) {
		wp_update_post( array(
			'ID' => $event_id,
			'post_status' => 'pending'
		) );
		return;
	} else {
		wp_update_post( array(
			'ID' => $event_id,
			'post_status' => 'publish'
		) );
	}

	$order->activate();

}


function _va_event_handle_moderated_transaction( $post ){

	if( $post->post_type != VA_EVENT_PTYPE )
		return;

	$order = _va_get_listing_order( $post->ID );
	if( !$order || $order->get_status() !== APPTHEMES_ORDER_COMPLETED )
		return;

	add_action( 'save_post', '_va_event_activate_moderated_transaction', 11);
}

function _va_event_activate_moderated_transaction( $post_id ){

	if( get_post_type( $post_id ) != VA_EVENT_PTYPE )
		return;

	$order = _va_get_event_order( $post_id );
	$order->activate();

}

function _va_event_activate_plan( $order ){

	if( get_post_type( _va_get_order_post_id( $order ) ) != VA_EVENT_PTYPE )
		return;

	$order_info = _va_get_order_listing_info( $order );

	$event_item = $order->get_item( VA_EVENT_PTYPE );
	$event_id = $event_item['post_id'];

	$event = get_post( $event_id );

	if( _va_needs_publish( $event ) ) {
		wp_update_post( array(
			'ID' => $event_id,
			'post_status' => 'publish'
		) );
	}
	foreach( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ){
		$featured_item = $order->get_item( $addon );

		if( !empty( $featured_item ) ){
			va_event_add_featured( $event_id, $addon );
		}
	}

}

function _va_event_activate_addons( $order ){
	global $va_options;

	if( get_post_type( _va_get_order_post_id( $order ) ) != VA_EVENT_PTYPE )
		return;

	foreach( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ){
		foreach( $order->get_items( $addon ) as $item ){
			va_event_add_featured( $item['post_id'], $addon );
		}
	}

}

function _va_get_event_order( $event_id ){

	$connected = new WP_Query( array(
		'connected_type' => APPTHEMES_ORDER_CONNECTION,
		'connected_to' => $event_id,
		'nopaging' => true
	) );

	if( ! $connected->posts )
		return false;
	else
		return appthemes_get_order( $connected->post->ID );

}

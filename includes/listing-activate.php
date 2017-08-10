<?php

add_action( 'appthemes_transaction_completed', 'va_handle_completed_transaction' );
add_action( 'pending_to_publish', '_va_handle_moderated_transaction' );
add_action( 'pending-claimed_to_publish', '_va_handle_moderated_transaction' );

add_action( 'appthemes_transaction_activated', '_va_activate_plan' );
add_action( 'appthemes_transaction_activated', '_va_activate_addons' );

function va_handle_completed_transaction( $order ) {
	global $va_options;

	$listing_id = _va_get_order_post_id( $order );

	if ( get_post_type( $listing_id ) != VA_LISTING_PTYPE ) {
		return;
	}

	$needs_moderation = false;

	if ( _va_is_claimed( $order ) && $va_options->moderate_claimed_listings ) {
		wp_update_post( array(
			'ID' => $listing_id,
			'post_status' => 'pending-claimed'
		) );
		return;
	} else if ( _va_is_claimed( $order ) ) {
		$order->activate();
		return;
	}

	if ( _va_is_renewal_order( $order ) ) {
		wp_update_post( array(
			'ID' => $listing_id,
			'post_status' => 'publish'
		) );
		$order->activate();
		return;
	}

	if ( _va_is_recurring_order( $order ) && ! _va_is_original_recurring_order( $order ) ) {
		va_update_listing_start_date( get_post( $listing_id ) );
		wp_update_post( array(
			'ID' => $listing_id,
			'post_status' => 'publish'
		) );
		$order->activate();
		return;
	}

	if ( $va_options->moderate_listings ) {
		wp_update_post( array(
			'ID' => $listing_id,
			'post_status' => 'pending'
		) );
		return;
	}

	wp_update_post( array(
		'ID' => $listing_id,
		'post_status' => 'publish'
	) );

	$order->activate();

}

function _va_handle_moderated_transaction( $post ) {

	if ( $post->post_type != VA_LISTING_PTYPE ) {
		return;
	}

	// do not activate when rejecting claimed listing
	if ( isset( $_GET['reject'] ) ) {
		return;
	}

	$order = _va_get_listing_order( $post->ID );
	if ( ! $order || $order->get_status() !== APPTHEMES_ORDER_COMPLETED ) {
		return;
	}

	add_action( 'save_post', '_va_activate_moderated_transaction', 11 );
}

function _va_activate_moderated_transaction( $post_id ){

	if ( get_post_type( $post_id ) != VA_LISTING_PTYPE ) {
		return;
	}

	$order = _va_get_listing_order( $post_id );
	$order->activate();

}

function _va_get_order_listing_id( $order ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.3', '_va_get_order_post_id()' );
	return _va_get_order_post_id( $order );
}

function _va_order_connection_post_status_fix( $wp_query ) {
	if ( ! isset( $wp_query->_p2p_capture ) ) {
		return;
	}

	if ( ( in_array( VA_LISTING_PTYPE, $wp_query->query['post_type'] ) || ( va_events_enabled() && in_array( VA_EVENT_PTYPE, $wp_query->query['post_type'] ) ) ) && in_array( APPTHEMES_ORDER_PTYPE, $wp_query->query['post_type'] ) ) {
		$wp_query->set( 'post_status', 'any' );
	}
}

function _va_get_last_plan_info( $listing_id ) {

	$valid_plan_names = array();

	$plans = new WP_Query( array(
		'post_type' => APPTHEMES_PRICE_PLAN_PTYPE,
		'nopaging' => 1,
		'post_status' => 'any'
	) );

	foreach ( $plans->posts as $key => $plan ) {
		$plans_array[ $plan->post_name ] = $plan;
		$valid_plan_names[] = $plan->post_name;
	}

	add_action( 'parse_query', '_va_order_connection_post_status_fix' );

	$connected = new WP_Query( array(
		'connected_type' => APPTHEMES_ORDER_CONNECTION,
		'connected_to' => $listing_id,
		'connected_meta' => array(
			array(
				'key' => 'type',
				'value' => $valid_plan_names,
				'compare' => 'IN',
			)
		),
		'post_status' => array( APPTHEMES_ORDER_COMPLETED, APPTHEMES_ORDER_ACTIVATED ),
		'nopaging' => true
	) );

	if ( ! $connected->posts ) {
		return false;
	}

	$plan_name = p2p_get_meta( $connected->posts[0]->p2p_id, 'type', true );

	$plan_info = get_post_custom( $plans_array[ $plan_name ]->ID );
	$plan_info['ID'] = $plans_array[ $plan_name ]->ID;

	return $plan_info;
}

function _va_get_order_listing_info( $order ) {

	$plans = new WP_Query( array( 'post_type' => APPTHEMES_PRICE_PLAN_PTYPE, 'nopaging' => 1, 'post_status' => 'any' ) );
	foreach ( $plans->posts as $key => $plan ) {
		if ( empty( $plan->post_name ) ) {
			continue;
		}

		$plan_slug = $plan->post_name;

		$items = $order->get_items( $plan_slug );
		if ( $items ) {
			$plan_data = va_get_plan_options( $plan->ID );
			return array(
				'listing_id' => $items[0]['post_id'],
				'listing' => $items[0]['post'],
				'plan' => $plan,
				'plan_data' => $plan_data
			);
		}
	}

	return false;
}

function _va_get_order_post_id( $order ) {
	$items = $order->get_items();

	$order_main_item_post_types = array();
	$order_main_item_post_types[] = VA_LISTING_PTYPE;

	if ( va_events_enabled() ) {
		$order_main_item_post_types[] = VA_EVENT_PTYPE;
	}

	foreach ( $items as $item ) {
		if ( in_array( $item['post']->post_type, $order_main_item_post_types ) ) {
			return $item['post_id'];
		}
	}
}

function _va_get_listing_order( $listing_id ) {

	$connected = new WP_Query( array(
		'connected_type' => APPTHEMES_ORDER_CONNECTION,
		'connected_to' => $listing_id,
		'nopaging' => true
	) );

	if ( ! $connected->posts ) {
		return false;
	} else {
		return appthemes_get_order( $connected->post->ID );
	}
}

function _va_get_latest_listing_order( $listing_id ) {

	$connected = new WP_Query( array(
		'connected_type' => APPTHEMES_ORDER_CONNECTION,
		'connected_to' => $listing_id,
		'orderby' => 'date',
		'order' => 'DESC',
		'nopaging' => true
	) );

	if ( ! $connected->post ) {
		return false;
	} else {
		return appthemes_get_order( $connected->post->ID );
	}
}

function va_is_active_recurring_listing_order( $listing_id ) {
	$order = _va_get_pending_recurring_listing_order( $listing_id );
	return ( false === $order || $order->get_status() == APPTHEMES_ORDER_FAILED ) ? false : true;
}

function _va_get_pending_recurring_listing_order( $listing_id ) {
	$order = _va_get_recurring_listing_order( $listing_id );
	return ( false === $order || $order->get_status() == APPTHEMES_ORDER_FAILED ) ? false : $order;
}

function _va_get_recurring_listing_order( $listing_id ) {
	$connected = new WP_Query( array(
		'connected_type' => APPTHEMES_ORDER_CONNECTION,
		'connected_to' => $listing_id,
		'post_status' => array( APPTHEMES_ORDER_PENDING, APPTHEMES_ORDER_COMPLETED, APPTHEMES_ORDER_FAILED ),
		'nopaging' => true
	) );

	if ( ! isset( $connected->post ) ) {
		return false;
	} else {
		$order = appthemes_get_order( $connected->post->ID );
		if ( $order->is_recurring() ) {
			return $order;
		} else {
			return false;
		}
	}

}

function _va_activate_plan( $order ) {

	if ( get_post_type( _va_get_order_post_id( $order ) ) != VA_LISTING_PTYPE ) {
		return;
	}

	$listing_data = _va_get_order_listing_info( $order );
	if ( ! $listing_data ) {
		return;
	}

	extract( $listing_data );

	if ( _va_needs_publish( $listing ) ) {
		wp_update_post( array(
			'ID' => $listing_id,
			'post_status' => 'publish'
		) );
	}

	va_update_listing_start_date( get_post( $listing_id ) );

	update_post_meta( $listing_id, 'listing_duration', $plan_data['duration'] );

	foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
		if ( ! empty( $plan_data[ $addon ] ) ){
			va_add_featured( $listing_id, $addon, $plan_data[ $addon . '_duration' ] );
		}
	}
}

function _va_activate_addons( $order ) {
	global $va_options;

	if ( get_post_type( _va_get_order_post_id( $order ) ) != VA_LISTING_PTYPE ) {
		return;
	}

	foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
		foreach ( $order->get_items( $addon ) as $item ) {
			va_add_featured( $item['post_id'], $addon, $va_options->addons[ $addon ]['duration'] );
		}
	}
}

function _va_is_claimed( $order ) {
	$claimee = $order->get_item( VA_LISTING_CLAIM_ITEM );
	return ! empty( $claimee );
}

function _va_is_renewal_order( $order ) {
	$renew = $order->get_item( VA_LISTING_RENEW_ITEM );
	return ! empty( $renew );
}

function _va_is_recurring_order( $order ) {
	return $order->is_recurring();
}

function _va_is_original_recurring_order( $order ) {
	if ( ! $order->is_recurring() ) {
		return false;
	}

	return 0 == $order->get_info( 'parent' ) ? true : false;
}

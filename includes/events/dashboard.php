<?php

add_action( 'va_listing_dashboard_add_rewrite_rules', 'va_listing_dashboard_add_event_rewrite_rules' );
add_filter( 'va_dashboard_permalink_settings', 'va_dashboard_event_permalink_settings' );
add_action( 'va_dashboard_sidebar_links', 'va_event_dashboard_sidebar_links', 10, 2 );
add_filter( 'va_get_dashboard_name', 'va_events_get_dashboard_name', 10, 2 );
add_filter( 'va_get_dashboard_type', 'va_events_get_dashboard_type', 10, 2 );
add_filter( 'va_dashboard_user_stat_sections', 'va_dashboard_event_user_stat_sections' );
add_filter( 'va_dashboard_stats', 'va_dashboard_event_stats', 10, 2 );

function va_get_dashboard_events( $user_id, $self = false ) {
	global $va_options;

	$args = array(
		'post_type' => VA_EVENT_PTYPE,
		'author' => $user_id,
		'paged' => get_query_var( 'paged' ),
		'posts_per_page' => $va_options->listings_per_page
	);

	if ( $self ) {
		$args['post_status'] = array( 'publish', 'pending', 'expired', 'draft' );
	} else {
		$args['post_status'] = array( 'publish' );
	}

	$query = new WP_Query( $args );
	return $query;
}

function va_get_dashboard_events_attending( $user_id, $self = false ) {

	$favorites = new WP_Query( array(
	  'connected_type'  => VA_EVENT_ATTENDEE_CONNECTION,
	  'connected_items' => $user_id,
	  'nopaging' 	    => true,
	) );

	return $favorites;
}

function va_get_dashboard_event_favorites( $user_id, $self = false ) {

	$favorites = new WP_Query( array(
	  'connected_type'  => VA_EVENT_FAVORITES,
	  'connected_items' => $user_id,
	  'nopaging' 	    => true,
	) );

	return $favorites;
}

function va_get_dashboard_event_comments( $user_id, $self = false ) {

	$limit = VA_REVIEWS_PER_PAGE;

	$page = max( 1, get_query_var( 'paged' ) );

	$offset = $limit * ( $page - 1 );

	$reviews = va_get_event_comments( array(
			'user_id' => $user_id,
			'status' => ( $self === true ? '' : 'approve' ),
			'number' => $limit,
			'offset' => $offset,
		) );

	return $reviews;
}

function va_get_dashboard_event_comments_count( $user_id, $self = false ) {
	return max( 1, ceil( va_get_user_event_comments_count( $user_id, ( $self === true ? '' : 'approve' ) )  / VA_REVIEWS_PER_PAGE ) );
}

function va_listing_dashboard_add_event_rewrite_rules() {
	global $va_options;

	$dashboard_permalink = $va_options->dashboard_permalink;
	$dashboard_events_permalink = $va_options->dashboard_events_permalink;
	$dashboard_event_comments_permalink = $va_options->dashboard_event_comments_permalink;
	$dashboard_event_favorites_permalink = $va_options->dashboard_event_favorites_permalink;
	$dashboard_events_attending_permalink = $va_options->dashboard_events_attending_permalink;

	$dashboard_all_permalinks =
			   $dashboard_events_permalink .
		'?|' . $dashboard_event_comments_permalink .
		'?|' . $dashboard_event_favorites_permalink .
		'?|' . $dashboard_events_attending_permalink;
}


add_filter('va_dashboard_all_permalinks', 'va_dashboard_event_permalinks');

function va_dashboard_event_permalinks( $all_permalinks ) {
	global $va_options;

	$all_permalinks[] = $va_options->dashboard_events_permalink;
	$all_permalinks[] = $va_options->dashboard_event_comments_permalink;
	$all_permalinks[] = $va_options->dashboard_event_favorites_permalink;
	$all_permalinks[] = $va_options->dashboard_events_attending_permalink;

	return $all_permalinks;
}


function va_dashboard_event_permalink_settings( $permalinks ) {
	global $va_options;

	$permalinks['events'] = $va_options->dashboard_events_permalink;
	$permalinks['events-attending'] = $va_options->dashboard_events_attending_permalink;
	$permalinks['event-comments'] = $va_options->dashboard_event_comments_permalink;
	$permalinks['event-favorites'] = $va_options->dashboard_event_favorites_permalink;

	return $permalinks;
}

function va_the_author_events_link( $user_id = '' ) {
	echo va_get_the_author_events_link( $user_id );
}

function va_get_the_author_events_link( $user_id = '' ) {

	$url = va_dashboard_url( 'events', $user_id, false );
	$display_name = get_the_author_meta( 'display_name', $user_id );

	return html_link( $url, $display_name );
}

function va_get_the_author_events_url( $user_id = '' , $self = false ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'va_dashboard_url()' );
	return va_dashboard_url( 'events', $user_id, $self );
}

function va_get_the_author_events_attending_url( $user_id = '' , $self = false ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'va_dashboard_url()' );
	return va_dashboard_url( 'events-attending', $user_id, $self );
}

function va_get_the_author_event_comments_link( $user_id = '', $self = false ) {

	$url = va_dashboard_url( 'event-comments', $user_id, $self );
	$display_name = get_the_author_meta( 'display_name', $user_id );

	return html_link( $url, $display_name );
}
function va_get_the_author_event_comments_url( $user_id = '' , $self = false ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'va_dashboard_url()' );
	return va_dashboard_url( 'event-comments', $user_id, $self );
}

function va_get_the_author_event_favorites_url( $user_id = '' , $self = false ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'va_dashboard_url()' );
	return va_dashboard_url( 'event-favorites', $user_id, $self );
}


function va_event_dashboard_sidebar_links( $dashboard_user, $is_own_dashboard ) {
	echo html( 'li', array('class'=>'view-events'), html_link( va_dashboard_url( 'events', $dashboard_user->ID, $is_own_dashboard ), __( 'View Events', APP_TD ) ) );

	echo html( 'li', array('class'=>'view-events-attending'), html_link( va_dashboard_url( 'events-attending', $dashboard_user->ID, $is_own_dashboard ), __( 'Events Attending', APP_TD ) ) );

	echo html( 'li', array('class'=>'view-event-comments'), html_link( va_dashboard_url( 'event-comments', $dashboard_user->ID, $is_own_dashboard ), __( 'Event Comments', APP_TD ) ) );

	if ( $is_own_dashboard ) {
		echo html( 'li', array('class'=>'view-event-favorites'), html_link( va_dashboard_url( 'event-favorites', $dashboard_user->ID, $is_own_dashboard ), __( 'Favorite Events', APP_TD ) ) );
	}
}

function va_events_get_dashboard_name( $name, $dashboard_type ) {
	if ( $dashboard_type == va_get_dashboard_permalink_setting('events') ) {
		$name = __( 'Events', APP_TD );
	} else if ( $dashboard_type == va_get_dashboard_permalink_setting('events-attending') ) {
		$name = __( 'Events Attending', APP_TD );
	} else if ( $dashboard_type == va_get_dashboard_permalink_setting('event-comments') ) {
		$name = __( 'Event Comments', APP_TD );
	} else if ( $dashboard_type == va_get_dashboard_permalink_setting('event-favorites') ) {
		$name = __( 'Favorite Events', APP_TD );
	}

	return $name;
}

function va_events_get_dashboard_type($type, $dashboard_type) {
	if ( $dashboard_type == va_get_dashboard_permalink_setting('events') ) {
		$type = 'events';
	} else if ( $dashboard_type == va_get_dashboard_permalink_setting('events-attending') ) {
		$type = 'events-attending';
	} else if ( $dashboard_type == va_get_dashboard_permalink_setting('event-favorites') ) {
		$type = 'event-favorites';
	} else if ( $dashboard_type == va_get_dashboard_permalink_setting('event-comments') ) {
		$type = 'event-comments';
	}

	return $type;
}

function va_dashboard_event_user_stat_sections( $stat_sections ) {

	$stat_sections['events'] = __( 'Events', APP_TD );
	$stat_sections['event_comments'] = __( 'Event Comments', APP_TD );

	return $stat_sections;
}

function va_dashboard_event_stats( $stat_sections, $user ) {

	$event_comments_live = va_get_user_event_comments_count( $user->ID, 'approve' );
	$event_comments_pending = va_get_user_event_comments_count( $user->ID, 'hold' );

	$stat_sections['event_comments'] = array(
		array(
			'name' => __( 'Live', APP_TD ),
			'value' => $event_comments_live,
			),
		array(
			'name' => __( 'Pending', APP_TD ),
			'value'=> $event_comments_pending,
			),
		array(
			'name' => __( 'Total', APP_TD ),
			'value' => $event_comments_live + $event_comments_pending,
		)
	);

	$events_live = va_count_user_posts( $user->ID, 'publish', VA_EVENT_PTYPE );
	$events_pending = va_count_user_posts( $user->ID, 'pending', VA_EVENT_PTYPE );
	$events_expired = va_count_user_posts( $user->ID, 'expired', VA_EVENT_PTYPE );
	$events_total = $events_live + $events_pending + $events_expired;

	$stat_sections['events'] = array(
		array(
			'name' => __( 'Live', APP_TD ),
			'value' => $events_live,
			),
		array(
			'name' => __( 'Pending', APP_TD ),
			'value'=> $events_pending,
			),
		array(
			'name' => __( 'Expired', APP_TD ),
			'value'=> $events_expired,
			),
		array(
			'name' => __( 'Total', APP_TD ),
			'value' => $events_total,
		)
	);

	return $stat_sections;
}

function va_the_event_expiration_notice( $event_id = '' ) {
	echo va_get_event_expiration_notice( $event_id );
}

function va_get_event_expiration_notice( $event_id = '' ) {

	$expiration_date = va_get_event_exipration_date( $event_id );
	if( !$expiration_date )
		return;

	$is_expired = strtotime($expiration_date) < time();
	if( $is_expired ){
		$notice = sprintf( __('Event Expired on: %s', APP_TD ), mysql2date( get_option('date_format'), $expiration_date ) );
	}else{
		$notice = sprintf( __('Event Expires on: %s', APP_TD ), mysql2date( get_option('date_format'), $expiration_date ) );
	}

	$output = '<p class="dashboard-expiration-meta">';
	$output .= $notice;
	$output .='</p>';
	return $output;
}

function va_get_event_exipration_date( $event_id = '' ) {
	global $post;

	$event_id = !empty( $event_id ) ? $event_id : get_the_ID();

	$duration = get_post_meta( $event_id, 'event_duration', true );

	if( empty( $duration ) ){
		return 0;
	}

	return va_get_expiration_date( $post->post_date, $duration );
}

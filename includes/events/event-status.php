<?php

add_action( 'init', 'va_schedule_event_prune' );
add_action( 'va_prune_expired_events', 'va_prune_expired_events' );

add_filter( 'posts_clauses', 'va_expired_event_sql', 10, 2 );

function va_maybe_publish_event( $event_id ) {
	global $va_options;

	if ( $va_options->moderate_events ) {
		wp_update_post( array(
			'ID' => $event_id,
			'post_status' => 'pending'
		) );
	} else {
		wp_update_post( array(
			'ID' => $event_id,
			'post_status' => 'publish'
		) );
	}
}

function va_schedule_event_prune() {
	if ( !wp_next_scheduled( 'va_prune_expired_events' ) )
		wp_schedule_event( time(), 'hourly', 'va_prune_expired_events' );
}

function va_prune_expired_events() {
	global $va_options;

	if ( empty( $va_options->event_expiration ) )
		return;

	$expired_posts = new WP_Query( array(
		'post_type' => VA_EVENT_PTYPE,
		'expired_events' => true,
		'nopaging' => true,
	) );

	foreach ( $expired_posts->posts as $post ) {
		wp_update_post( array(
			'ID' => $post->ID,
			'post_status' => 'expired'
		) );
	}
}

function va_expired_event_sql( $clauses, $wp_query ) {
	global $wpdb, $va_options;

	if ( $wp_query->get( 'expired_events' ) ) {
		$clauses['join'] .= " INNER JOIN " . $wpdb->postmeta ." AS exp1 ON (" . $wpdb->posts .".ID = exp1.post_id)";

		$clauses['where'] .= $wpdb->prepare( " AND ( exp1.meta_key = '%s' AND DATE_ADD(exp1.meta_value, INTERVAL %d DAY) < '%s' AND exp1.meta_value > 0 )", VA_EVENT_DATE_END_META_KEY, $va_options->event_expiration, current_time( 'mysql' ) );
	}

	return $clauses;
}

<?php

add_action( 'init', 'va_schedule_featured_prune' );
add_action( 'va_prune_expired_featured', 'va_prune_expired_featured' );
add_filter( 'posts_clauses', 'va_expired_featured_sql', 10, 2 );


function va_is_listing_featured( $post_id, $addon = '' ) {

	if(empty($addon)) {
		if ( va_is_post_type_home() ) {
			$addons = array( VA_ITEM_FEATURED_HOME );
		} elseif ( is_tax() ) {
			$addons = array( VA_ITEM_FEATURED_CAT );
		} else {
			$addons = array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT );
		}

		foreach( $addons as $addon ){
			$featured = get_post_meta( $post_id, $addon, true );

			if(!empty($featured)) return true;
		}
	} else {
		$featured = get_post_meta( $post_id, $addon, true );
		if(!empty($featured)) return true;
	}

	return false;
}

function va_featured_flag( $post ) {
	if ( empty( $post ) ) return false;

	if ( isset( $post->ID ) ) {
		$post_id = $post->ID;
	} elseif ( is_numeric( $post ) ) {
		$post_id = $post;
	} else {
		return false;
	}

	foreach( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
		$featured = get_post_meta( $post_id, $addon, true );

		if ( !empty( $featured ) ) {
			update_post_meta( $post_id, VA_ITEM_FEATURED, true );
			return;
		}
	}

	update_post_meta( $post_id, VA_ITEM_FEATURED, false );
}

function va_add_featured( $post_id, $addon, $duration ){

	update_post_meta( $post_id, $addon , true);
	update_post_meta( $post_id, $addon .'_start_date', current_time( 'mysql' ));
	update_post_meta( $post_id, $addon .'_duration', $duration);
	va_featured_flag( $post_id );

}

function va_remove_featured( $post_id, $addon ){

	update_post_meta( $post_id, $addon , '0' );
	delete_post_meta( $post_id, $addon .'_start_date' );
	delete_post_meta( $post_id, $addon .'_duration' );
	va_featured_flag( $post_id );

	va_send_expired_upgrade_notification( get_post( $post_id ), APP_Item_Registry::get_title( $addon ) );
}

function va_schedule_featured_prune() {
	if ( !wp_next_scheduled( 'va_prune_expired_featured' ) )
		wp_schedule_event( time(), 'hourly', 'va_prune_expired_featured' );
}

function va_prune_expired_featured() {

	foreach( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ){

		$expired_posts = new WP_Query( array(
			'post_type' => VA_LISTING_PTYPE,
			'expired' => $addon,
			'nopaging' => true,
		) );

		foreach ( $expired_posts->posts as $post ){
			if ( !_va_is_addon_expirable( $post->ID, $addon ) ) {
				continue;
			}

			va_remove_featured( $post->ID, $addon );
		}

	}

}

function va_expired_featured_sql( $clauses, $wp_query ) {
	global $wpdb;

	switch( $wp_query->get( 'expired' ) ){

		case VA_ITEM_FEATURED_HOME:
			$clauses['join'] .= _va_get_expired_sql_join( VA_ITEM_FEATURED_HOME );
			$clauses['where'] = _va_get_expired_sql_where( VA_ITEM_FEATURED_HOME );
			break;

		case VA_ITEM_FEATURED_CAT:
			$clauses['join'] .= _va_get_expired_sql_join( VA_ITEM_FEATURED_CAT );
			$clauses['where'] = _va_get_expired_sql_where( VA_ITEM_FEATURED_CAT );
			break;

	}

	return $clauses;
}

function _va_get_expired_sql_join( $addon ){
	global $wpdb;

	$output = '';
	$output .= " INNER JOIN " . $wpdb->postmeta ." AS duration ON (" . $wpdb->posts .".ID = duration.post_id)";
	$output .= " INNER JOIN " . $wpdb->postmeta ." AS start ON (" . $wpdb->posts .".ID = start.post_id)";

	return $output;

}

function _va_get_expired_sql_where( $addon ){

	$where = 'AND (';
		$where .= 'duration.meta_key = \'' . $addon . '_duration\' AND ';
		$where .= 'start.meta_key = \'' . $addon . '_start_date\'';
		$where .= ' AND ';
		$where .= ' DATE_ADD( start.meta_value, INTERVAL duration.meta_value DAY ) < \'' . current_time( 'mysql' ) . '\'';
		$where .= ' AND duration.meta_value > 0 ';
	$where .= ") ";

	return $where;

}

function _va_is_addon_expirable( $listing_id, $addon ) {

	$order = _va_postpone_recurring_order_expiraton( $listing_id );
	if ( !$order ) {
		return true;
	}

	if ( va_get_listing_exipration_date( $listing_id ) == va_get_featured_exipration_date( $addon, $listing_id ) ) {
		return false;
	}

	return true;
}

function va_any_featured_addon_enabled(){

	global $va_options;

	$addons = array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT );
	foreach( $addons as $addon ){
		if( $va_options->addons[$addon]['enabled'] == 'yes' )
			return true;
	}

	return false;
}

function va_show_featured(){
	$show = false;

	if ( va_is_post_type_home() || is_tax() ) {
		$show =  true;
	} else if( is_search() ) {
		$show = true;
	}

	$show = apply_filters('va_show_featured', $show );

	return $show;
}

function va_show_listings_featured_home() {
	global $wp_query;

	return ( is_post_type_archive( VA_LISTING_PTYPE ) && ( get_va_query_var( 'orderby', false ) == 'default' || get_va_query_var( 'orderby', false ) == '' ) );
}

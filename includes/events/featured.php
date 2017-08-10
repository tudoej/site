<?php
add_filter('va_show_featured', 'va_events_show_featured');


function va_events_show_featured( $show ) {
	if ( is_tax( VA_EVENT_DAY ) )
		return false;

	return $show;
}

function va_event_add_featured( $post_id, $addon ){
	update_post_meta( $post_id, $addon , true);
	va_featured_flag( $post_id );
}

function va_show_events_featured_home() {
	global $wp_query;

	return ( is_post_type_archive( VA_EVENT_PTYPE ) && ( get_va_query_var( 'orderby', false ) == 'default' || get_va_query_var( 'orderby', false ) == '' ) );
}
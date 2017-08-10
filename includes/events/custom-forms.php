<?php

add_action( 'init', 'va_event_forms_register_post_type', 11 );
add_action( 'wp_ajax_app-render-event-form', 'va_forms_ajax_render_event_form' );
add_action( 'edited_term_taxonomy', 'va_exclude_event_forms_from_counter', 11, 2 );


function va_event_forms_register_post_type() {
	register_taxonomy_for_object_type( VA_EVENT_CATEGORY, APP_FORMS_PTYPE );
}

function va_forms_ajax_render_event_form() {
	if ( empty( $_POST['_' . VA_EVENT_CATEGORY ] ) )
		die;

	$cat = $_POST['_' . VA_EVENT_CATEGORY ];

	$event_id = !empty( $_POST['event_id'] ) ? $_POST['event_id'] : '';

	the_files_editor( $event_id, __( 'Event Files', APP_TD ) );
	va_render_form( $cat, VA_EVENT_CATEGORY, $event_id );
	die;
}

function va_exclude_event_forms_from_counter( $term, $taxonomy ) {
	global $wpdb;
	if ( is_object( $taxonomy ) && $taxonomy->name == VA_EVENT_CATEGORY ) {
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status = 'publish' AND post_type = %s AND term_taxonomy_id = %d", VA_EVENT_PTYPE, $term ) );
		$wpdb->update( $wpdb->term_taxonomy, array( 'count' => $count ), array( 'term_taxonomy_id' => $term ) );
	}
}

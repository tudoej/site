<?php

add_action( 'appthemes_first_run', 'va_events_add_caps' );


function va_events_add_caps() {
	va_events_manage_caps( 'add_cap' );
}

function va_events_remove_caps() {
	va_events_manage_caps( 'remove_cap' );
}

function va_events_manage_caps( $operation ) {
	global $wp_roles;

	if ( ! isset( $wp_roles ) )
		$wp_roles = new WP_Roles();

	foreach ( $wp_roles->roles as $role => $details ) {
		foreach ( va_events_get_custom_caps( $role ) as $cap ) {
			$wp_roles->$operation( $role, $cap );
		}
	}
}

function va_events_get_custom_caps( $role ) {
	$caps = array(
		'edit_events',
		'edit_published_events',
		'delete_events',
	);

	if ( in_array( $role, array( 'editor', 'administrator' ) ) ) {
		$caps = array_merge( $caps, array(
			'edit_others_events',
			'publish_events',
			'delete_published_events',
			'delete_others_events'
		) );
	}

	return $caps;
}

<?php

add_action( 'appthemes_first_run', 'va_add_caps' );
add_action( 'admin_init', 'va_restrict_admin_access' );

function va_add_caps() {
	va_manage_caps( 'add_cap' );
}

function va_remove_caps() {
	va_manage_caps( 'remove_cap' );
}

function va_manage_caps( $operation ) {
	global $wp_roles;

	if ( ! isset( $wp_roles ) )
		$wp_roles = new WP_Roles();

	foreach ( $wp_roles->roles as $role => $details ) {
		foreach ( va_get_custom_caps( $role ) as $cap ) {
			$wp_roles->$operation( $role, $cap );
		}
	}
}

function va_get_custom_caps( $role ) {
	$caps = array(
		'edit_listings',
		'edit_published_listings',
		'delete_listings',
	);

	if ( in_array( $role, array( 'editor', 'administrator' ) ) ) {
		$caps = array_merge( $caps, array(
			'edit_others_listings',
			'publish_listings',
			'delete_published_listings',
			'delete_others_listings'
		) );
	}

	return $caps;
}

/**
 * Admin area access control with redirect
 */
function va_restrict_admin_access() {
	global $va_options;

	$access_level = $va_options->admin_security;

	if ( empty( $access_level ) ) {
		$access_level = 'manage_options';
	}

	if ( $access_level == 'disable' || current_user_can( $access_level ) || defined( 'DOING_AJAX' ) ) {
		return;
	} else {
		wp_redirect( site_url() );
		exit;
	}
}
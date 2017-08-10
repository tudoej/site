<?php
/**
 * Social meta functions
 *
 * @package Vantage\Events\Social
 * @author  AppThemes
 * @since   Vantage 1.4
 */


function va_get_allowed_event_networks() {
	return apply_filters( 'va_event_allowed_social_networks', _va_allowed_networks() );
}

function va_get_available_event_networks( $post_id = false ) {
	return _va_fill_available_networks_array( 'post', va_get_allowed_event_networks(), $post_id ) ;
}
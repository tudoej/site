<?php
/**
 * Delete Event processing
 *
 * @package Vantage\Delete-Event
 * @author  AppThemes
 * @since   Vantage 1.4
 */

add_action( 'init', 'va_delete_event_init', 14 );

function va_delete_event_init() {
	$ajax_action = 'vantage_delete_event';

	add_action( 'wp_ajax_' . $ajax_action, 'va_handle_ajax_delete_post' );
	add_action( 'wp_ajax_nopriv_' . $ajax_action, 'va_handle_ajax_delete_post' );
}

/**
 * Returns or echoes the delete button
 *
 * @param int     $listing_id The listing id to search in
 * @param bool    $echo If set to FALSE does not echo the button HTML
 *
 * @return string
 */
function va_display_delete_event_button( $listing_id, $echo = true ) {
	$button = '';

	if ( current_user_can( 'delete_events' ) && va_is_own_dashboard() ) {

		$text = __( 'Delete Event', APP_TD );

		$icon = html( 'span', array(
			'class' => 'delete-icon event-delete',
		), '' );

		$button = html( 'a', array(
			'class' => 'delete-button event-delete-link ',
			'href' => va_get_delete_post_url( $listing_id ),
			'rel' => 'nofollow',
		),  $icon . ' ' . $text );

	}

	if ( $echo ) {
		echo $button;
	} else {
		return $button;
	}
}
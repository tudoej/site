<?php

add_action( 'init', 'va_favorites_init', 13 );


/**
 * Initializes favorites.
 *
 * @return void
 */
function va_favorites_init() {
	$ajax_action = 'vantage_favorites';

	p2p_register_connection_type( array(
		'name' => VA_LISTING_FAVORITES,
		'from' => VA_LISTING_PTYPE,
		'to' => 'user'
	) );

	add_action( 'wp_ajax_' . $ajax_action, 'va_handle_ajax_favorites' );
	add_action( 'wp_ajax_nopriv_' . $ajax_action, 'va_handle_ajax_favorites' );
}


/**
 * Handle favorites ajax requests.
 *
 * @return void
 */
function va_handle_ajax_favorites() {
	if ( ! isset( $_POST['favorite'] ) && ! isset( $_POST['listing_id'] ) && ! isset( $_POST['current_url'] ) ) {
		return;
	}

	if ( ! in_array( $_POST['favorite'], array( 'add', 'delete' ) ) ) {
		return;
	}

	$listing_id = (int) $_POST['listing_id'];

	check_ajax_referer( 'favorite-' . $listing_id );

	$redirect = '';
	$status = 'success';

	if ( is_user_logged_in() ) {
		if ( 'add' == $_POST['favorite'] ) {
			$notice = sprintf( __( "Added '%s' to your favorites.", APP_TD ), get_the_title( $listing_id ) );
			$p2p = p2p_type( VA_LISTING_FAVORITES )->connect( $listing_id, get_current_user_id(), array( 'date' => current_time( 'mysql' ) ) );
		} else {
			$notice = sprintf( __( "Removed '%s' from your favorites.", APP_TD ), get_the_title( $listing_id ) );
			$p2p = p2p_type( VA_LISTING_FAVORITES )->disconnect( $listing_id, get_current_user_id() );
		}

		if ( is_wp_error( $p2p ) ) {
			$status = 'error';
			$notice = sprintf( __( "Could not add '%s' to favorites at this time.", APP_TD ), get_the_title( $listing_id ) );
		}
	} else {
		$redirect = esc_url( $_POST['current_url'] );
		$status = 'error';
		$notice = sprintf ( __( 'You must <a href="%1$s">login</a> to be able to favorite listings.', APP_TD ), wp_login_url( $redirect ) );
	}

	ob_start();
	appthemes_display_notice( $status, $notice );
	$notice = ob_get_clean();

	$result = array(
		'html'     => va_display_fave_button( $listing_id, false ),
		'status'   => $status,
		'notice'   => $notice,
		'redirect' => $redirect,
	);

	die( json_encode( $result ) );
}


/**
 * Check if a specific listing is already favorited.
 *
 * @param int $listing_id The listing id to search in
 *
 * @return bool Returns True if already favorited, False otherwise
 */
function va_is_fave_listing( $listing_id ) {

	$count = p2p_get_connections( VA_LISTING_FAVORITES, array(
		'direction' => 'from',
		'from'      => $listing_id,
		'to'        => get_current_user_id(),
		'fields'    => 'count'
	) );

	return (bool) $count;
}


/**
 * Return the current URL with additional query variables.
 *
 * @param int     $listing_id The listing id to search in
 * @param string  $action The favorite action - valid options (add|delete)
 *
 * @return bool
 */
function va_get_favorite_url( $listing_id, $action = 'add' ) {

	$args = array(
		'favorite'   => $action,
		'listing_id' => $listing_id,
		'ajax_nonce' => wp_create_nonce( 'favorite-' . $listing_id ),
	);
	return esc_url( add_query_arg( $args, home_url() ) );
}

/**
 * Returns or echoes the favorite button
 *
 * @param int     $listing_id The listing id to search in
 * @param bool    $echo If set to FALSE does not echo the button HTML
 *
 * @return string
 */
function va_display_fave_button( $listing_id, $echo = true ) {

	if ( ! va_is_fave_listing( $listing_id ) || ! is_user_logged_in() ) {
		$text = __( 'Add to Favorites', APP_TD );

		$icon = html( 'span', array(
			'class' => 'fave-icon listing-fave',
		), '' );

		$button = html( 'a', array(
			'class' => 'fave-button listing-fave-link',
			'href' => va_get_favorite_url( $listing_id ),
			'rel' => 'nofollow',
		), $icon . ' ' . $text );

	} else {
		$text = __( 'Delete Favorite', APP_TD );

		$icon = html( 'span', array(
			'class' => 'fave-icon listing-unfave',
		), '' );

		$button = html( 'a', array(
			'class' => 'fave-button listing-unfave-link ',
			'href' => va_get_favorite_url( $listing_id, 'delete' ),
			'rel' => 'nofollow',
		),  $icon . ' ' . $text );

	}

	if ( $echo ) {
		echo $button;
	} else {
		return $button;
	}
}

<?php
/**
 * Delete Listing processing
 *
 * @package Vantage\Delete-Listing
 * @author  AppThemes
 * @since   Vantage 1.4
 */

add_action( 'init', 'va_delete_listing_init', 14 );

function va_delete_listing_init() {
	$ajax_action = 'vantage_delete_listing';

	add_action( 'wp_ajax_' . $ajax_action, 'va_handle_ajax_delete_post' );
	add_action( 'wp_ajax_nopriv_' . $ajax_action, 'va_handle_ajax_delete_post' );
}

/**
 * Return the current URL with additional query variables
 *
 * @param int     $post_id The post id to search in
 *
 * @return string URL
 */
function va_get_delete_post_url( $post_id ) {
	$post = get_post( $post_id );
	$args = array (
		'delete'     => $post_id,
		'ajax_nonce' => wp_create_nonce( "delete-" . $post->post_type . "-" . $post_id ),
	);
	return esc_url( add_query_arg( $args ) );
}

function va_handle_ajax_delete_post() {

	if ( ! isset( $_POST['delete'] ) ) {
		return;
	}

	$post_id = (int) $_POST['delete'];
	$post    = get_post( $post_id );
	$type    = $post->post_type;

	check_ajax_referer( "delete-$type-$post_id" );

	$status = 'success';

	if ( ! current_user_can( 'delete_' . $type . 's', $post_id ) || get_current_user_id() != $post->post_author ) {

		$status = 'error';
		$notice = sprintf ( __( 'You do not have permission to delete this item.', APP_TD ) );

		_va_delete_post_send_ajax_response( $status, $notice );

	}

	wp_update_post( array(
		'ID' => $post_id,
		'post_status' => 'deleted'
	) );

	$message = __( "Deleted item '%s'.", APP_TD );
	$notice = sprintf( $message, get_the_title( $post_id ) );

	_va_delete_post_send_ajax_response( $status, $notice );
}

function _va_delete_post_send_ajax_response( $status, $notice ){

	ob_start();
	appthemes_display_notice( $status, $notice );
	$notice = ob_get_clean();

	$result = array(
		'html' 	 	=> '',
		'status' 	=> $status,
		'notice' 	=> $notice,
	);

	die ( json_encode( $result ) );

}

/**
 * Returns or echoes the delete button
 *
 * @param int     $listing_id The listing id to search in
 * @param bool    $echo If set to FALSE does not echo the button HTML
 *
 * @return string
 */
function va_display_delete_listing_button( $listing_id, $echo = true ) {
	$button = '';

	if ( current_user_can( 'delete_listings' ) && va_is_own_dashboard() ) {

		$text = __( 'Delete Listing', APP_TD );

		$icon = html( 'span', array(
			'class' => 'delete-icon listing-delete',
		), '' );

		$button = html( 'a', array(
			'class' => 'delete-button listing-delete-link ',
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
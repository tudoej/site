<?php
/**
 * Hold deprecated functions and hooks
 */


/**
 * Was checking if multi categories selection is enabled.
 *
 * @deprecated 1.2
 */
function va_multi_cat_enabled() {
	_deprecated_function( __FUNCTION__, '1.2' );
	return false;
}


/**
 * Displays listing categories.
 *
 * @deprecated Use the_listing_categories()
 * @see the_listing_categories()
 *
 * @param int $listing_id (optional)
 *
 * @return void
 */
function the_listing_category( $listing_id = 0 ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'the_listing_categories()' );
	the_listing_categories( $listing_id );
}


/**
 * Returns listing categories ids.
 *
 * @deprecated Use get_the_listing_categories()
 * @see get_the_listing_categories()
 *
 * @param int $listing_id (optional)
 *
 * @return array
 */
function get_the_listing_category( $listing_id = 0 ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'get_the_listing_categories()' );
	return get_the_listing_categories( $listing_id );
}


/**
 * Queries the database for attachments from custom fields or gallery
 * Uses the meta key '_va_attachment_type' to filter the available attachment types: gallery | file
 *
 * @deprecated Use va_get_post_attachments()
 * @see va_get_post_attachments()
 *
 * @param int  	  $listing_id The post ID
 * @param int     $how_many (optional) The number of attachments to retrieve
 * @param string  $type     (optional) The type of attachment to return (gallery|file)
 * @param string  $fields   (optional) The fields to be returned
 *
 * @return array
 */
function va_get_listing_attachments( $listing_id, $how_many = -1, $type = VA_ATTACHMENT_GALLERY, $fields = '' ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2.1', 'va_get_post_attachments()' );
	return va_get_post_attachments( $listing_id, $how_many, $type, $fields );
}


/**
 * Displays the listing files list.
 *
 * @deprecated Use va_the_files_list()
 * @see va_the_files_list()
 *
 * @return void
 */
function the_listing_files() {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2.1', 'va_the_files_list()' );
	va_the_files_list();
}


/**
 * Was used for sending emails with custom template.
 * Removed in favor of common appthemes_send_email() function
 *
 * @deprecated 1.3.3
 */
function va_send_email( $address, $subject, $content ) {
	_deprecated_function( __FUNCTION__, '1.3.3', 'appthemes_send_email()' );
	appthemes_send_email( $address, $subject, $content );
}


/**
 * Was used for displaying site logo, title and description.
 * HTML markup moved to the header.php file to make it more friendly
 * for child-theme developers
 *
 * @deprecated 1.3.3
 */
function va_display_logo() {
	_deprecated_function( __FUNCTION__, '1.3.3' );
	return false;
}

/**
 * Updates post status.
 *
 * @param int $post_id
 * @param string $new_status
 *
 * @deprecated 1.4.2
 */
function va_update_post_status( $post_id, $new_status ) {
	_deprecated_function( __FUNCTION__, '1.4.2', 'wp_update_post()' );
	wp_update_post( array(
		'ID' => $post_id,
		'post_status' => $new_status
	) );
}

/**
 * deprecated action and filter hooks
 *
 */
appthemes_deprecate_hook( 'va_email_admin_transaction_failed', 'appthemes_notify_admin_failed_transaction', '3.1', 'filter' );
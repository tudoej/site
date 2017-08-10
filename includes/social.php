<?php
/**
 * Social meta functions
 *
 * @package Vantage\Social
 * @author  AppThemes
 * @since   Vantage 1.4
 */

/**
 * Returns the title for the given social network
 *
 * @param string $social_network
 * @return string social network name
 */
function va_get_social_network_title( $social_network ) {
	return APP_Social_Networks::get_title( $social_network );
}

/**
 * Returns the tip for filling the given social network
 *
 * @param string $social_network
 * @return string social network tip
 */
function va_get_social_network_tip( $social_network ) {
	return APP_Social_Networks::get_tip( $social_network );
}

/**
 * Returns the user account URL for the given social network
 *
 * @param type $social_network
 * @param type $account
 * @return string Escaped URL
 */
function va_get_social_account_url( $social_network, $account = '' ) {
	return APP_Social_Networks::get_url( $social_network, $account );
}

/**
 * Retrieves an array of allowed social networks to be refined for each type of object
 *
 * @return array An array of allowed registered social networks
 */
function _va_allowed_networks() {
	return array(
		'google-plus',
		'facebook',
		'twitter',
	);
}

function va_get_allowed_user_networks() {
	return apply_filters( 'va_user_allowed_social_networks', _va_allowed_networks() );
}

function va_get_allowed_listing_networks() {
	return apply_filters( 'va_listing_allowed_social_networks', _va_allowed_networks() );
}

function va_get_post_social_account( $social_network, $post_id = false ) {
	return get_post_meta( $post_id, $social_network, true );
}

function va_get_user_social_account( $social_network, $post_id = false ) {
	return get_user_meta( $post_id, $social_network, true );
}

function _va_fill_available_networks_array( $obj_type = 'user', $networks = array(), $obj_id = false ) {
	$result = array();
	foreach( $networks as $network ){

		if ( 'post' === $obj_type ) {
			$account =  va_get_post_social_account( $network, $obj_id );
		} else {
			$account =  va_get_user_social_account( $network, $obj_id );
		}

		if ( $account ) {
			$result[ $network ] = $account;
		}
	}
	return $result;
}

function va_get_available_user_networks( $user_id = false ) {
	return _va_fill_available_networks_array( 'user', va_get_allowed_user_networks(), $user_id ) ;
}

function va_get_available_listing_networks( $post_id = false ) {
	return _va_fill_available_networks_array( 'post', va_get_allowed_listing_networks(), $post_id ) ;
}
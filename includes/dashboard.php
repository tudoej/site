<?php
function va_get_dashboard_permalink_setting( $permalink ) {
	global $va_options;

	$permalinks = array(
		'dashboard' => $va_options->dashboard_permalink,
		'listings' => $va_options->dashboard_listings_permalink,
		'reviews' => $va_options->dashboard_reviews_permalink,
		'favorites' => $va_options->dashboard_faves_permalink,
		'claimed-listings' => $va_options->dashboard_claimed_permalink,
	);

	$permalinks = apply_filters( 'va_dashboard_permalink_settings', $permalinks );

	return $permalinks[ $permalink ];
}

function va_get_dashboard_name() {
	global $wp_query;
	$dashboard_type = $wp_query->get( 'dashboard' );

	if ( $dashboard_type == va_get_dashboard_permalink_setting( 'reviews' ) ) {
		$name = __( 'Reviews', APP_TD );
	} else if ( $dashboard_type == va_get_dashboard_permalink_setting( 'claimed-listings' ) ) {
		$name = __( 'Claimed Listings', APP_TD );
	} else if ( $dashboard_type == va_get_dashboard_permalink_setting( 'favorites' ) ) {
		$name = __( 'Favorite Listings', APP_TD );
	} else {
		$name = __( 'Listings', APP_TD );
	}

	return apply_filters( 'va_get_dashboard_name', $name, $dashboard_type );
}

function va_get_dashboard_type() {
	global $wp_query;
	$dashboard_type = $wp_query->get( 'dashboard' );

	if ( $dashboard_type == va_get_dashboard_permalink_setting( 'reviews' ) ) {
		$type = 'reviews';
	} else if ( $dashboard_type == va_get_dashboard_permalink_setting( 'claimed-listings' ) ) {
		$type = 'claimed-listings';
	} elseif ( $dashboard_type == va_get_dashboard_permalink_setting( 'favorites' ) ) {
		$type = 'favorites';
	} else {
		$type = 'listings';
	}
	return apply_filters( 'va_get_dashboard_type', $type, $dashboard_type );
}

function va_is_own_dashboard() {
	global $wp_query;
	$dashboard_author = $wp_query->get( 'dashboard_author' );

	if ( $dashboard_author == 'self' ) {
		return true;
	} else {
		$user = get_user_by( 'slug', $dashboard_author );
		if ( $user && $user->ID == get_current_user_id() ) {
			return true;
		} else {
			return false;
		}
	}
}

function va_get_dashboard_author() {
	global $wp_query;
	$dashboard_author = $wp_query->get( 'dashboard_author' );

	if ( $dashboard_author == 'self' ) {
		$user = wp_get_current_user();
	} else {
		$user = get_user_by( 'slug', $dashboard_author );
	}

	if ( ! $user ) {
		return false;
	}

	$user->email_public = get_user_meta( $user->ID, 'email_public', true );
	$user->twitter = get_user_meta( $user->ID, 'twitter', true );
	$user->facebook = get_user_meta( $user->ID, 'facebook', true );
	$user->has_claimed = (bool) get_user_meta( $user->ID, 'claimee', true );

	return $user;
}

function va_get_dashboard_verbiage( $key ) {

	$dashboard_verbiage = array(
		'pending'         => __( 'Pending', APP_TD ),
		'pending-claimed' => __( 'Pending Claimed', APP_TD ),
		'publish'         => __( 'Active', APP_TD ),
		'expired'         => __( 'Expired', APP_TD ),
		'draft'           => __( 'Draft', APP_TD ),
	);

	$dashboard_verbiage = apply_filters( 'va_dashboard_verbiage', $dashboard_verbiage );

	return ! empty( $dashboard_verbiage[ $key ] ) ? $dashboard_verbiage[ $key ] : '';
}

function va_dashboard_get_user_stats( $user ) {
	global $wpdb;

	$stat_sections = array();
	$reviews_live = va_get_user_reviews_count( $user->ID, 'approve' );
	$reviews_pending = va_get_user_reviews_count( $user->ID, 'hold' );

	$stat_sections['reviews'] = array(
		array(
			'name' => __( 'Live', APP_TD ),
			'value' => $reviews_live,
			),
		array(
			'name' => __( 'Pending', APP_TD ),
			'value'=> $reviews_pending,
			),
		array(
			'name' => __( 'Total', APP_TD ),
			'value' => $reviews_live + $reviews_pending,
		)
	);

	$listings_live = va_count_user_posts( $user->ID, 'publish', VA_LISTING_PTYPE );
	$listings_pending = va_count_user_posts( $user->ID, 'pending', VA_LISTING_PTYPE );
	$listings_expired = va_count_user_posts( $user->ID, 'expired', VA_LISTING_PTYPE );
	$listing_drafts = va_count_user_posts( $user->ID, 'draft', VA_LISTING_PTYPE );
	$listings_total = $listings_live + $listings_pending + $listings_expired + $listing_drafts;

	$stat_sections['listings'] = array(
		array(
			'name' => __( 'Live', APP_TD ),
			'value' => $listings_live,
		),
		array(
			'name' => __( 'Pending', APP_TD ),
			'value'=> $listings_pending,
		),
		array(
			'name' => __( 'Expired', APP_TD ),
			'value'=> $listings_expired,
		),
		array(
			'name' => __( 'Drafts', APP_TD ),
			'value'=> $listing_drafts,
		),
		array(
			'name' => __( 'Total', APP_TD ),
			'value' => $listings_total,
		)
	);

	$stat_sections = apply_filters( 'va_dashboard_stats', $stat_sections, $user );

	return $stat_sections;
}

function va_dashboard_get_user_stat_sections() {
	$stat_sections = array(
		'reviews' => __( 'Reviews', APP_TD ),
		'listings' => __( 'Listings', APP_TD ),
	);

	return apply_filters( 'va_dashboard_user_stat_sections', $stat_sections );
}

function va_dashboard_user_stats_ui( $dashboard_user ) {
	$stat_sections = va_dashboard_get_user_stat_sections();

	$section_values = va_dashboard_get_user_stats( $dashboard_user );

	foreach ( $stat_sections as $stat_section => $section_name ) {

		$li = '';
		foreach( $section_values[ $stat_section ] as $stat ) {
			$data = html( 'span', array( 'class' => 'name' ), $stat['name'] );
			$data .= html( 'span', array( 'class' => 'sep' ), ': ' );
			$data .= html( 'span', array( 'class' => 'value' ), $stat['value'] );
			$li .= html( 'li', array( 'class' => 'stat stat-' . esc_attr( sanitize_title_with_dashes( $stat['name'] ) ) ), $data );
		}

		$h4 = html( 'h4', array( 'class' => 'stat-section-name' ), $section_name );
		$ul = html( 'ul', array( 'class' => 'stats' . ' stat-section-' . $stat_section ), $li );

		echo html( 'div', array( 'class' => 'stat-section', 'id' => 'stat-section-' . $stat_section ), $h4 . $ul );
	}
}

function va_count_user_posts( $user_id, $status, $post_type ) {
	global $wpdb;

	return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_author = %d AND post_type = %s AND post_status = %s", $user_id, $post_type, $status ) );
}


function va_dashboard_show_account_info( $user, $self = false ) {

	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( $self ) {
		return true;
	}


	$twitter = ( ! empty( $user->twitter ) ? true : false );

	if ( $twitter ) {
		return true;
	}

	$facebook = ( ! empty( $user->facebook ) ? true : false );

	if ( $facebook ) {
		return true;
	}

	$email = ( ! empty( $user->email_public ) ? true : false );

	if ( $email ) {
		return true;
	}

	$url = ( ! empty( $user->user_url ) ? true : false );

	if ( $url ) {
		return true;
	}

	return false;
}

function va_get_dashboard_listings( $user_id, $self = false ) {
	global $va_options;

	$args = array(
		'post_type' => VA_LISTING_PTYPE,
		'author' => $user_id,
		'paged' => get_query_var( 'paged' ),
		'posts_per_page' => $va_options->listings_per_page
	);

	if ( $self ) {
		$args['post_status'] = array( 'publish', 'pending', 'expired', 'draft' );
	} else {
		$args['post_status'] = array( 'publish' );
	}

	$query = new WP_Query( $args );
	return $query;
}

function va_get_dashboard_favorites( $user_id, $self = false ) {

	$favorites = new WP_Query( array(
	  'connected_type'  => 'va_favorites',
	  'connected_items' => $user_id,
	  'nopaging' 	    => true,
	) );

	return $favorites;

}

function va_get_dashboard_reviews( $user_id, $self = false ) {

	$limit = VA_REVIEWS_PER_PAGE;

	$page = max( 1, get_query_var( 'paged' ) );

	$offset = $limit * ( $page - 1 );

	$reviews = va_get_reviews( array(
			'user_id' => $user_id,
			'status' => ( $self === true ? '' : 'approve' ),
			'number' => $limit,
			'offset' => $offset,
		) );

	return $reviews;
}

function va_get_dashboard_claimed_listings( $user_id, $self = false ) {
	global $va_options;

	$args = array(
		'post_type' => VA_LISTING_PTYPE,
		'meta_key' => 'claimee',
		'meta_value' => $user_id,
		'paged' => get_query_var( 'paged' ),
		'posts_per_page' => $va_options->listings_per_page

	);

	if ( $self ) {
		$args['post_status'] = array( 'publish', 'pending', 'pending-claimed', 'expired' );
	} else {
		return array();
	}

	$query = new WP_Query( $args );
	return $query;
}

function va_the_payment_complete_actions( $listing_id = '' ) {
	$listing_id = ! empty( $listing_id ) ? $listing_id : get_the_ID();
	$actions = array();

	$order = appthemes_get_order_connected_to( $listing_id );
	if ( ! $order || ! in_array( $order->get_status(), array( APPTHEMES_ORDER_PENDING, APPTHEMES_ORDER_FAILED ) ) ) {
		return;
	}

	// pay order if order has checkout data
	if ( get_post_meta( $order->get_id(), 'cancel_url', true ) ) {
		$actions['pay_order'] = array(
			'title' => __( 'Pay now', APP_TD ),
			'href' => appthemes_get_order_url( $order->get_id() ),
		);

		if ( $order->get_gateway() ) {
			// reset gateway
			$actions['reset_gateway'] = array(
				'title' => __( 'Reset Gateway', APP_TD ),
				'href' => get_the_order_cancel_url( $order->get_id() ),
			);
		}
	} else {
		$actions['purchase'] = array(
			'title' => __( 'Pay now', APP_TD ),
			'href' => va_get_listing_purchase_url( $listing_id ),
		);
	}

	$output = '';
	foreach ( $actions as $action => $attr ) {
		$attr = array_merge( $attr, array( 'class' => 'listing-edit-link' ) );
		$output .= html( 'a', $attr, $attr['title'] );
	}

	echo $output;

}

function va_the_listing_expiration_notice( $listing_id = '' ) {
	echo va_get_listing_expiration_notice( $listing_id );
}

function va_get_listing_expiration_notice( $listing_id = '' ) {

	$expiration_date = va_get_listing_exipration_date( $listing_id );
	if ( ! $expiration_date ) {
		return;
	}

	$upcoming_recurring_payment_date = va_get_recurring_order_next_payment_date( $listing_id = 0 );
	$is_expired = strtotime( $expiration_date ) < time();

	if ( $is_expired ) {
		$notice = sprintf( __( 'Listing Expired on: %s', APP_TD ), mysql2date( get_option( 'date_format' ), $expiration_date ) );
	} elseif ( $upcoming_recurring_payment_date ) {
		$notice = sprintf( __( 'Upcoming Recurring Payment: %s', APP_TD ), $upcoming_recurring_payment_date );
	} else {
		$notice = sprintf( __( 'Listing Expires on: %s', APP_TD ), mysql2date( get_option( 'date_format' ), $expiration_date ) );
	}

	$output = '<p class="dashboard-expiration-meta">';
	$output .= $notice;
	$output .= '</p>';
	return $output;
}

function va_get_listing_exipration_date( $listing_id = '' ) {

	$listing_id = ! empty( $listing_id ) ? $listing_id : get_the_ID();
	$listing = get_post( $listing_id );

	$duration = get_post_meta( $listing_id, 'listing_duration', true );

	if ( empty( $duration ) ) {
		return 0;
	}

	return va_get_expiration_date( $listing->post_date, $duration );
}

function va_get_featured_exipration_date( $addon, $listing_id = '' ) {

	$listing_id = ! empty( $listing_id ) ? $listing_id : get_the_ID();

	$start_date = get_post_meta( $listing_id, $addon . '_start_date', true );

	$duration = get_post_meta( $listing_id, $addon.'_duration', true );

	if ( ! $start_date || ! $duration ) {
		return __( 'Never', APP_TD );
	}

	return va_get_expiration_date( $start_date, $duration );
}

function va_get_expiration_date( $start_date, $duration ) {

	$expiration_date = date( 'm/d/Y', strtotime( $start_date .' + ' . $duration . 'days' ) );

	return $expiration_date;
}

function va_get_dashboard_reviews_count( $user_id, $self = false ) {
	return max( 1, ceil( va_get_user_reviews_count( $user_id, ( $self === true ? '' : 'approve' ) ) / VA_REVIEWS_PER_PAGE ) );
}

function va_dashboard_url( $page, $user_id = '', $self = false ) {
	$nicename = get_the_author_meta( 'user_nicename', $user_id );

	$url = home_url();
	$permalink = '/' . va_get_dashboard_permalink_setting( 'dashboard' ) . '/' . va_get_dashboard_permalink_setting( $page );

	if ( $self ) {
		if ( get_option( 'permalink_structure' ) != '' ) {
			$url .= $permalink . '/';
		} else {
			$url .= '?dashboard=' . va_get_dashboard_permalink_setting( $page ) . '&dashboard_author=self';
		}
	} else {
		if ( get_option( 'permalink_structure' ) != '' ) {
			$url .=  $permalink . '/' . $nicename . '/';
		} else {
			$url .= '?dashboard=' . va_get_dashboard_permalink_setting( $page ) . '&dashboard_author=' . $nicename;
		}
	}

	return $url;
}

function va_the_author_listings_link( $user_id = '' ) {

	echo va_get_the_author_listings_link( $user_id );
}

function va_get_the_author_listings_link( $user_id = '' ) {

	$url = va_dashboard_url( 'listings', $user_id, false );
	$display_name = get_the_author_meta( 'display_name', $user_id );

	return html_link( $url, $display_name );
}

function va_get_the_author_listings_url( $user_id = '' , $self = false ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'va_dashboard_url()' );
	return va_dashboard_url( 'listings', $user_id, $self );
}

function va_get_claimed_listings_url() {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'va_dashboard_url()' );
	return va_dashboard_url( 'claimed-listings', '', true );
}

function va_the_author_reviews_link( $user_id = '' ) {
	echo va_get_the_author_reviews_link( $user_id );
}

function va_get_the_author_reviews_link( $user_id = '', $self = false ) {

	$url = va_dashboard_url( 'reviews', $user_id, $self );
	$display_name = get_the_author_meta( 'display_name', $user_id );

	return html_link( $url, $display_name );
}

function va_get_edit_review_url( $review_id ) {
	$url = home_url();
	$permalink = '/' . va_get_dashboard_permalink_setting( 'dashboard' ) . '/' . va_get_dashboard_permalink_setting( 'reviews' );

	if ( get_option( 'permalink_structure' ) != '' ) {
		$url .=  $permalink . '/#review-' . $review_id;
	} else {
		$url .= '?dashboard=' . va_get_dashboard_permalink_setting( 'reviews' ).'&dashboard_author=self#review-' . $review_id;
	}

	return $url;
}

function va_get_the_author_reviews_url( $user_id = '' , $self = false ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'va_dashboard_url()' );
	return va_dashboard_url( 'reviews', $user_id, $self );
}

function va_get_the_author_faves_url( $user_id = '' , $self = false ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'va_dashboard_url()' );
	return va_dashboard_url( 'favorites', $user_id, $self );
}

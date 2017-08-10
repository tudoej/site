<?php

// These utility functions can go away at any time. Don't rely on them.


/**
 * Wrapper function for registering sidebars.
 *
 * @param string $id
 * @param string $name
 * @param string $description (optional)
 *
 * @return void
 */
function va_register_sidebar( $id, $name, $description = '' ) {
	register_sidebar( array(
		'id' => $id,
		'name' => $name,
		'description' => $description,
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => "</aside>",
		'before_title' => '<div class="section-head"><h3>',
		'after_title' => '</h3></div>',
	) );
}


/**
 * Returns the excerpt more text.
 *
 * @param string $more_text
 *
 * @return string
 */
function va_excerpt_more() {
	return '&hellip;';
}


/**
 * Returns the excerpt length.
 *
 * @param int $length
 *
 * @return int
 */
function va_excerpt_length() {
	return 15;
}


/**
 * Returns value rounded to nearest.
 *
 * @param int $num
 * @param int $nearest (optional)
 * @param int $min (optional)
 *
 * @return float
 */
function va_round_to_nearest( $num, $nearest = 5, $min = '' ) {
	if ( $nearest < 1 ) {
		$new = round( ( 100 * $num ) / ( 100 * $nearest ) ) * ( 100 * $nearest )  / 100;
	} else {
		$new = round( $num / $nearest ) * $nearest;
	}

	if ( ! empty( $min ) ) {
		return max( $min, $new );
	} else {
		return $new;
	}
}


/**
 * Returns time ago string
 *
 * @param string $time
 *
 * @return string
 */
function va_string_ago( $time ) {

	$diff = (int) current_time( 'timestamp' ) - strtotime( $time );

	if ( $diff <= 0 ) {
		$string = __( 'just now', APP_TD );
	} else if ( $diff < 60 ) {
		$string = sprintf( _n( '%d second ago', '%d seconds ago', $diff, APP_TD ), $diff );
	} elseif ( $diff < 3600 ) {
		$diff = round( $diff/60, 0 );
		$string = sprintf( _n( '%d minute ago', '%d minutes ago', $diff, APP_TD ), $diff );
	} elseif ( $diff < 86400 ) {
		$diff = round( $diff/60/60, 0 );
		$string = sprintf( _n( '%d hour ago', '%d hours ago', $diff, APP_TD ), $diff );
	} elseif ( $diff < 604800 ) {
		$diff = round( $diff/60/60/24, 0 );
		$string = sprintf( _n( '%d day ago', '%d days ago', $diff, APP_TD ), $diff );
	} elseif ( $diff < 2592000 ) {
		$diff = round( $diff/60/60/24/7, 0 );
		$string = sprintf( _n( '%d week ago', '%d weeks ago', $diff, APP_TD ), $diff );
	} elseif ( $diff < 31104000 ) {
		$diff = round( $diff/60/60/24/30, 0 );
		$string = sprintf( _n( '%d month ago', '%d months ago', $diff, APP_TD ), $diff );
	} else {
		$diff = round( $diff/60/60/24/30/12, 0 );
		$string = sprintf( _n( '%d year ago', '%d years ago', $diff, APP_TD ), $diff );
	}
	return $string;
}


/**
 * Returns turncated text.
 *
 * @param string $string
 * @param int $limit
 * @param string $link
 * @param string $break
 * @param string $pad
 *
 * @return string
 */
function va_truncate( $string, $limit, $link, $break = '', $pad = '...' ) {

	// return with no change if string is shorter than $limit
	if ( strlen( $string ) <= $limit ) {
		return $string;
	}

	$string = substr( $string, 0, $limit );

	if ( false !== ( $breakpoint = strrpos( $string, $break ) ) ) {
		$string = substr( $string, 0, $breakpoint );
	}

	return $string . $pad . $link;
}


/**
 * Outputs search query var.
 *
 * @param string $qv
 *
 * @return void
 */
function va_show_search_query_var( $qv ) {
	echo va_get_search_query_var( $qv );
}


/**
 * Returns search query var.
 *
 * @param string $qv
 *
 * @return string
 */
function va_get_search_query_var( $qv ) {
	return stripslashes( esc_attr( trim( get_query_var( $qv ) ) ) );
}


function va_edit_listing_map( $listing_id = '', $map_div_id = 'listing-map', $zoom = 13, $dragend_cb = 'va_listing_map_dragend' ) {

	$listing_id = ! empty( $listing_id ) ? $listing_id : get_the_ID();

	$coord = appthemes_get_coordinates( $listing_id, false );

	appthemes_load_map_provider();

	?>
		<script type="text/javascript">
			jQuery(function() {
				<?php if ( $coord ) { ?>
				jQuery('#<?php echo esc_js( $map_div_id ); ?>').appthemes_map({
					zoom: <?php echo $zoom; ?>,
					center_lat: <?php echo $coord->lat; ?>,
					center_lng: <?php echo $coord->lng; ?>,
					marker_drag_end: function( lat, lng ) {
						<?php echo $dragend_cb; ?>( lat, lng );
					}
				});

				var marker_opts = {
					lat: <?php echo $coord->lat; ?>,
					lng: <?php echo $coord->lng; ?>,
					draggable: true,
				};

				var marker = jQuery('#<?php echo $mad_div_id; ?>').appthemes_map('add_marker', marker_opts );
				<?php } else { ?>

				<?php } ?>
			});
		</script>
	<?php

	$map_provider = APP_Map_Provider_Registry::get_active_map_provider();

	$map_provider->map_init($map_div_id, $coord->lat, $coord->lng, $zoom );

	$marker_args = array(
		'lat' => $coord->lat,
		'lng' => $coord->lng,
		'draggable' => true,
		'dragend_cb' => $dragend_cb,
	);

	$map_provider->add_marker( $marker_args );

	echo $map_provider->display();
}

// Fetches geo coordinates of an address, caches and returns them
function va_geocode_address( $listing_id ) {
	$coord = appthemes_get_coordinates( $listing_id, false );

	if ( ! empty( $coord->lat ) && $coord->lat !== '0.000000' ) {
		return $coord;
	}

	$address = get_post_meta( $listing_id, 'address', true );
	if ( empty( $address ) ) {
		return false;
	}

	$results = va_geocode_address_api( $address );

	if ( ! $results || empty( $results['coords'] ) ) {
		return false;
	}

	$lat = $results['coords']->lat;
	$lng = $results['coords']->lng;

	appthemes_set_coordinates( $listing_id, $lat, $lng );

	return appthemes_get_coordinates( $listing_id );
}

function va_geocode_address_api( $address ) {

	return appthemes_geocode_address( $address );
}

function va_geocode_lat_lng_api( $lat, $lng ) {

	return appthemes_geocode_lat_lng( $lat, $lng );
}

function va_geocode_api( $args = '' ) {

	if ( empty( $args ) ) {
		return false;
	}

	extract( appthemes_geo_get_args() );

	$defaults = array(
		'sensor' => 'false',
		'region' => $region,
		'language' => $language,
	);

	$args = wp_parse_args( $args, $defaults );

	$response = wp_remote_get( add_query_arg( $args, 'http://maps.googleapis.com/maps/api/geocode/json' ) );

	if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
		return false;
	}

	$results = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! $results || 'OK' != $results['status'] ) {
		return false;
	}

	return $results;
}


/**
 * Returns blog page title.
 *
 * @return string
 */
function get_blog_page_title() {
	return get_the_title( VA_Blog_Archive::get_id() );
}

/**
 * Saves permalink settings.
 *
 * Temporary workaround for wordpress bug #9296
 * Although there is a hook in the options-permalink.php to insert custom settings,
 * it does not actually save any custom setting which is added to that page.
 *
 * @see http://core.trac.wordpress.org/ticket/9296
 *
 * @return void
 */
function va_enable_permalink_settings() {
	global $new_whitelist_options;

	// save hook for permalinks page
	if ( isset( $_POST['permalink_structure'] ) || isset( $_POST['category_base'] ) ) {
		check_admin_referer( 'update-permalink' );

		$option_page = 'permalink';

		$capability = 'manage_options';
		$capability = apply_filters( "option_page_capability_{$option_page}", $capability );

		if ( ! current_user_can( $capability ) ) {
			wp_die( __( 'Cheatin&#8217; uh?', APP_TD ) );
		}

		// get extra permalink options
		$options = $new_whitelist_options[ $option_page ];

		if ( $options ) {
			foreach ( $options as $option ) {
				$option = trim( $option );
				$value = null;

				if ( isset( $_POST[ $option ] ) ) {
					$value = $_POST[ $option ];
				}

				if ( ! is_array( $value ) ) {
					$value = trim( $value );
				}

				$value = stripslashes_deep( $value );

				// get the old values to merge
				$db_option = get_option( $option );

				if ( is_array( $db_option ) ) {
					update_option( $option, array_merge( $db_option, $value ) );
				} else {
					update_option( $option, $value );
				}

				// flush rewrite rules using a transient
				//set_transient( 'va_flush_rewrite_rules', 1, 300 );
			}

			//Yes, we need to do this now, come back to this and make the transient work in the right timing.
			flush_rewrite_rules();
		}

		/**
		 *  Handle settings errors
		 */
		set_transient( 'settings_errors', get_settings_errors(), 30 );
	}
}


/**
 * Checks transient and flush the rewrite rules.
 *
 * @return void
 */
function va_check_rewrite_rules_transient() {

	if ( get_transient( 'va_flush_rewrite_rules' ) ) {
		delete_transient( 'va_flush_rewrite_rules' );
		flush_rewrite_rules();
	}

}


add_action( 'after_setup_theme', '_va_after_load_payments', 1001 );
function _va_after_load_payments() {
	if ( is_admin() ) {
		remove_filter( 'set-screen-option', array( 'APP_Connected_Post_Orders', 'save_screen_option' ), 10, 3 );
		add_filter( 'appthemes_save_screen_option_intercept_appthemes_connected_orders_per_post_page', array( 'APP_Connected_Post_Orders', 'save_screen_option' ), 10, 3 );

		remove_filter( 'set-screen-option', array( 'APP_Connected_User_Orders', 'save_screen_option' ), 10, 3 );
		add_filter( 'appthemes_save_screen_option_intercept_appthemes_connected_orders_per_user_page', array( 'APP_Connected_User_Orders', 'save_screen_option' ), 10, 3 );

		add_filter( 'set-screen-option', 'appthemes_save_screen_option_intercept', 10, 3 );
	}
}

function appthemes_save_screen_option_intercept( $status, $option, $value ) {
	$interceptor = apply_filters( 'appthemes_save_screen_option_intercept_' . $option, false, $option, $value );

	if ( false !== $interceptor ) {
		return $interceptor;
	}

	return false;
}

<?php

add_filter( 'get_' . VA_EVENT_DAY , 'va_get_term_event_day', 9, 2 );


function va_format_event_day( $term ) {

	if ( is_object( $term ) ) {
		$term = $term->slug;
	}

	if ( 7 == strlen( $term )  ) {
		$date_format = str_ireplace( array( '/d', 'd/', 'd.', 'j ', 'j, ', 'j,', '-d', 'd-' ), '', get_option( 'date_format' ) );
	} else {
		$date_format = get_option( 'date_format' );
	}

	$date_format = apply_filters( 'va_event_day_single_term_title_format', $date_format, $term );

	return apply_filters( 'va_event_day_single_term_title', date_i18n( $date_format, strtotime( $term ) ), $date_format, $term );
}

function va_get_term_event_day( $term, $taxonomy ) {

	$term->name = va_format_event_day( $term->name );

	return $term;
}

function va_event_day_get_term_link( $term ) {
	global $wp_rewrite;

	if ( ! is_object( $term ) ) {
		if ( is_int( $term ) ) {
			$_term = get_term( $term, VA_EVENT_DAY );
		} else {
			$_term = get_term_by( 'slug', $term, VA_EVENT_DAY );
		}
	}

	if ( ! is_object( $_term ) || is_wp_error( $_term ) ) {
		$_term = new stdClass();
		$_term->slug = $term;
		$_term->name = va_format_event_day( $term );
	}

	$term = $_term;

	$taxonomy = VA_EVENT_DAY;

	$termlink = $wp_rewrite->get_extra_permastruct( $taxonomy );

	$slug = $term->slug;
	$t = get_taxonomy( $taxonomy );

	if ( empty( $termlink ) ) {
		if ( $t->query_var ) {
			$termlink = "?$t->query_var=$slug";
		} else {
			$termlink = "?taxonomy=$taxonomy&term=$slug";
		}
		$termlink = home_url( $termlink );

	} else {
		$termlink = str_replace( "%$taxonomy%", $slug, $termlink );
		$termlink = home_url( user_trailingslashit( $termlink, 'category' ) );
	}

	return apply_filters( 'term_link', $termlink, $term, $taxonomy );
}

function va_event_day_get_term( $term ) {

	if ( ! is_object( $term ) ) {
		$_term = $term;
		if ( is_int( $term ) ) {
			$term = get_term( $term, VA_EVENT_DAY );
		} else {
			$term = get_term_by( 'slug', $term, VA_EVENT_DAY );
		}

		if ( ! is_object( $term ) ) {
			$new_term_id = va_insert_event_day( $_term );
			$term = get_term( $new_term_id, VA_EVENT_DAY );
		}
	} else {
		$term = get_term( $term, VA_EVENT_DAY );
	}

	if ( ! is_object( $term ) ) {
		return new WP_Error( 'invalid_event_term', __( 'Invalid Event Day Term', APP_TD ) );
	}

	return $term;
}

function va_event_archive_date_selector() {
	global $va_locale;

	$month_slug = '';
	$day_slug = '';

	$current_day_term = get_queried_object();

	if ( ! empty( $current_day_term->taxonomy ) && VA_EVENT_DAY == $current_day_term->taxonomy ) {
		$current_month = date( 'm', strtotime( $current_day_term->slug ) );

		$current_year = date( 'Y', strtotime( $current_day_term->slug ) );
		$slug = $current_day_term->slug;
		if ( strlen( $slug ) > 7 ) {
			$day_slug = $slug;
		} else {
			$month_slug = $slug;
		}
	} elseif( $date_slug = va_get_search_query_var( VA_EVENT_DAY ) ) {
		$current_month = date( 'm', strtotime( $date_slug ) );
		$current_year = date( 'Y', strtotime( $date_slug ) );
		$slug = $month_slug = $current_year . '-' . $current_month;
	} else {
		$current_month = date( 'm', time() );
		$current_year = date( 'Y', time() );
		$slug = $month_slug = $current_year . '-' . $current_month;
	}

	$link_base = va_event_get_term_link_base();

	$months = va_get_dropdown_months( $slug );
	$month_selector = array(
		'type' => 'select',
		'name' => 'month',
		'values' => $months,
		'extra' => array( 'id' => 'va_event_archive_month' ),
	);

	$month_selector = scbForms::input_with_value( $month_selector, $current_month );
	$li = '';
	foreach ( $months as $month => $title ) {
		$args = array( 'data-value' => $month );

		if ( $month == $current_month ) {
			$args['class'] = 'active';
		}

		$href = user_trailingslashit( $link_base . $current_year . '-' .$month );
		$link = html( 'a', array( 'href' => $href  ), $title );

		$li .= html( 'li', $args, $link );
	}

	$top_div_text = html( 'p', array(), $va_locale->get_month( date( 'm', strtotime( $slug ) ) ) );
	$top_div_control = html( 'div', array( 'class' => 'control' ) );
	$top_div = html( 'div', array( 'id' => 'va_event_archive_month_list_selected', 'class' => 'selected' ), $top_div_text . $top_div_control );

	$ul = html( 'ul', array('id' => 'va_event_archive_month_list' ), $li );
	$list = html( 'div', array( 'class' => 'va_sort_list_wrap' ), $ul );

	$month_selector = html( 'div', array( 'id' => 'va_event_archive_month_list_container', 'class' => 'va_event_archive_list_container va_sort_list_container nav_item' ), $month_selector . $top_div . $list );

	$years = va_get_dropdown_years( $slug );
	$year_selector = array(
		'type' => 'select',
		'name' => 'year',
		'values' => $years,
		'extra' => array( 'id' => 'va_event_archive_year' ),
	);

	$year_selector = scbForms::input_with_value( $year_selector, $current_year );

	$year_month = '';

	$li = '';
	foreach ( $years as $year => $title ) {
		$args = array( 'data-value' => $year );

		if ( $year == $current_year ) {
			$args['class'] = 'active';
		}

		$href = user_trailingslashit( $link_base . $year . '-' . $current_month );
		$link = html( 'a', array( 'href' => $href  ), $title );

		$li .= html( 'li', $args, $link );
	}

	$top_div_text = html( 'p', array(), date( 'Y', strtotime( $slug ) ) );
	$top_div_control = html( 'div', array( 'class' => 'control' ) );
	$top_div = html( 'div', array( 'id' => 'va_event_archive_year_list_selected', 'class' => 'selected' ), $top_div_text . $top_div_control );

	$ul = html( 'ul', array( 'id' => 'va_event_archive_year_list' ), $li );
	$list = html( 'div', array( 'class' => 'va_sort_list_wrap' ), $ul );

	$year_selector = html( 'div', array( 'id' => 'va_event_archive_year_list_container', 'class' => 'va_event_archive_list_container va_sort_list_container nav_item' ), $year_selector . $top_div . $list );

	$prev_month = strtotime( $slug . ' -1 month' );
	$prev_month_F = $va_locale->get_month( date( 'm', $prev_month ) );

	$prev_link = html( 'div', array( 'class' => 'control' ) ) . html_link( va_event_day_get_term_link( date( 'Y-m', $prev_month ) ), sprintf( '%s %s', $prev_month_F, date( 'Y', $prev_month ) ) );
	$prev = html( 'div', array( 'id' => 'va_event_archive_prev', 'class' => 'nav_item' ), $prev_link );

	$next_month = strtotime( $slug . ' +1 month' );
	$next_month_F = $va_locale->get_month( date( 'm', $next_month ) );

	$next_link = html_link( va_event_day_get_term_link( date( 'Y-m', $next_month ) ), sprintf( '%s %s', $next_month_F, date( 'Y', $next_month ) ) ) . html( 'div', array('class'=>'control') );
	$next = html( 'div', array( 'id' => 'va_event_archive_next', 'class' => 'nav_item' ), $next_link );

	return html( 'div', array( 'id'=> 'va_event_archive_navigation' ), $prev . $month_selector . $year_selector . $next );
}

function va_event_get_term_link_base() {
	return va_get_term_link_base( VA_EVENT_DAY );
}

function va_get_events_directory_uri( $name = '' ) {
	return get_template_directory_uri(). VA_EVENTS_DIR . $name;
}

function va_insert_event_day( $day ) {
	$day_date = date( 'Y-m-d', strtotime( $day ) );
	$month_date = date( 'Y-m', strtotime( $day ) );

	if ( strpos( $day_date, '1970-01' ) !== false ) {
		return false;
	}

	$day_term_id = term_exists( $day_date, VA_EVENT_DAY );
	$month_term_id = term_exists( $month_date, VA_EVENT_DAY );

	if ( empty( $day_term_id ) ) {
		if ( empty( $month_term_id ) ) {
			$month_term_id = wp_insert_term( $month_date, VA_EVENT_DAY, array( 'slug' => $month_date ) );
		}

		if ( strlen( $day ) > 7 ) {
			$day_term_id = wp_insert_term( $day_date, VA_EVENT_DAY, array( 'slug' => $day_date, 'parent' => $month_term_id['term_id'] ) );
			delete_option( VA_EVENT_DAY . '_children' ); // clear the cache
		}
	}

	return !empty( $day_term_id ) && strlen( $day ) >= 7 ? $day_term_id['term_id'] : $month_term_id['term_id'];
}

function va_get_events( $args = array() ) {

	$defaults = array(
		'post_type' => VA_EVENT_PTYPE,
	);

	$args = wp_parse_args( $args, $defaults );
	$args = apply_filters( 'va_get_events', $args );

	return new WP_Query( $args );
}

function va_get_events_on_days( $args = array(), $days = array() ) {

	if ( empty( $days ) ) {
		$days = array( date( 'Y-m-d', time() ) );
	}

	$args['tax_query'] = array(
		array(
			'taxonomy' => VA_EVENT_DAY,
			'field' => 'slug',
			'terms' => $days,
			'include_children' => false,
		),
	);

	return va_get_events( $args );
}

function va_get_events_on_month( $args = array(), $month = '' ) {
	if ( empty( $month ) ) {
		$month = array( date( 'Y-m', time() ) );
	}

	$args['tax_query'] = array(
		array(
			'taxonomy' => VA_EVENT_DAY,
			'field' => 'slug',
			'terms' => $month,
			'include_children' => true,
		),
	);

	return va_get_events( $args );
}

function va_make_event_time_select_options($start = 6, $end = 23 ) {
	$times = array();
	for ( $x = $start, $y = $start; $x <= $end; $x++ ) {

		$meridian = $x < 12 ? 'am' : 'pm';

		$hour = $x <= 12 ? $y : $y - 12;
		$min = ':00';
		$time = $hour . $min;
		$display_time =  $time . ' ' . $meridian;
		$times[$x.$min] = $display_time;

		$min = ':30';
		$time = $hour . $min;
		$display_time = $time . ' ' . $meridian;
		$times[$x.$min] = $display_time;

		$y++;
	}

	return $times;
}

function va_event_categories_locked() {
	$disabled = ! current_user_can( 'administrator' );
	return (bool) apply_filters( 'va_event_categories_locked' , $disabled );
}

function va_get_dropdown_months( $current = '', $span = 12 ) {
	global $va_locale;

	$current = !empty( $current ) ? $current : date( 'Y-m-d', time() );

	$back_span = round( ( $span / 2 ), 0 );
	$back_span_seconds =  $back_span * 30 * 24 * 60 * 60;
	$start_month = strtotime( $current ) - $back_span_seconds;

	$months = array();
	for ( $x = 0 ; $x <= $span ; $x++ ) {
		$date = strtotime( date( 'Y-m-d', $start_month ) . ' +' . $x . 'months' );
		$m = date( 'm', $date );
		$F = $va_locale->get_month( $m );
		$months[ $m ] = $F;
	}
	return $months;
}

function va_get_dropdown_years( $current = '', $span = 4 ) {
	$current = !empty( $current ) ? $current : date( 'Y-m-d', time() );

	$back_span = round( ( $span / 2 ), 0 );
	$back_span_seconds =  $back_span * 365 * 24 * 60 * 60;
	$start_year = strtotime( $current ) - $back_span_seconds;
	$years = array();
	for ( $x = 0 ; $x <= $span ; $x++ ) {
		$date = strtotime( date( 'Y-m-d', $start_year ) . ' +' . $x . 'years' );
		$years[ date( 'Y', $date ) ] = date( 'Y', $date );
	}
	return $years;
}

add_filter( 'va_show_search_controls', 'va_disable_search_controls_event_create' );

function va_disable_search_controls_event_create( $enabled ) {
	return (bool) !is_page_template( 'create-event.php' );
}

add_filter( 'va_event_attendee_link', 'va_get_the_author_events_attending_link', 10, 2 );

function va_get_the_author_events_attending_link( $link, $user, $self = false ) {

	if ( $self || get_current_user_id() == $user->ID ) {
		$url = va_dashboard_url( 'events-attending', $user->ID, true );
	} else {
		$url = va_dashboard_url( 'events-attending', $user->ID );
	}

	return $url;
}

add_action( 'va_search_for_above', 'va_search_for_above' );
function va_search_for_above() {
	echo html( 'div', array( 'class' => 'post_type' ), get_the_search_post_type() );
}

add_action( 'va_sidebar_refine_search_hidden', 'va_sidebar_refine_search_hidden_post_type' );
function va_sidebar_refine_search_hidden_post_type() {
	appthemes_pass_request_var( 'st' );
}

add_filter( 'va_sidebar_refine_category_ui', 'va_sidebar_refine_event_category_ui' );
function va_sidebar_refine_event_category_ui( $options ) {
	if ( get_query_var( 'st' ) == VA_EVENT_PTYPE ) {
		$options['taxonomy'] = VA_EVENT_CATEGORY;
		$options['request_var'] = 'event_cat';
	}
	return $options;
}

add_filter( 'va_sidebar_refine_order_ui', 'va_sidebar_refine_event_order_ui' );
function va_sidebar_refine_event_order_ui( $options ) {
	if ( get_query_var( 'st' ) == VA_EVENT_PTYPE ) {
		$options['popular'] = __( 'Popular', APP_TD );
		$options['event_date'] = __( 'Event Date', APP_TD );
	}
	return $options;
}

function va_events_base_url() {
	global $va_options;

	$url = '';
	$base = trailingslashit( home_url() );

	if ( is_tax( VA_EVENT_CATEGORY ) || is_tax( VA_EVENT_TAG ) || is_tax( VA_EVENT_DAY ) ) {
		$url = get_term_link( get_queried_object() );
	}

	if ( is_post_type_archive( VA_EVENT_PTYPE ) || va_is_home() ) {
		$url = $va_options->event_permalink;
		$url = trailingslashit( $base . $url );
	}

	return $url;
}

add_action( 'appthemes_pagenavi_args', 'va_events_home_pagenavi_args' );
function va_events_home_pagenavi_args( $args ) {

	if ( ! empty( $args['home_events'] ) ) {
		$events_permalink = get_post_type_archive_link( VA_EVENT_PTYPE );
		$home_permalink = get_permalink( VA_Home_Archive::get_id() );
		if ( get_option( 'permalink_structure' ) ) {
			$args['base'] = str_replace( $home_permalink, $events_permalink, $args['base'] );
		} else {
			$args['base'] = str_replace( $home_permalink . '?', $events_permalink . '&', $args['base'] );
		}
	}

	return $args;
}

function va_get_event_home_listings() {
	global $va_options, $wpdb;

	$args = array(
		'post_type' => VA_EVENT_PTYPE,
		'posts_per_page' => $va_options->events_per_page,
		'order' => 'asc',
	);

	$orderby = $va_options->default_event_home_sort;

	$args['orderby'] = $orderby;
	$args['va_orderby'] = $orderby;

	switch ( $orderby ) {
		case 'popular':
			$args['meta_key'] = VA_EVENT_ATTENDEES_META_KEY;
			$args['orderby'] = 'meta_value';
			$args['order'] = 'desc';
			break;
		case 'most_comments':
			$args['orderby'] = 'comment_count';
			$args['order'] = 'desc';
			break;
		case 'event_date':
			$args['meta_key'] = VA_EVENT_DATE_META_KEY;
			$args['orderby'] = 'meta_value';
			$args['order'] = 'asc';
			break;
		case 'newest':
			$args['order'] = 'desc';
			break;
		case 'recently_discussed':
				$result_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT p.ID FROM $wpdb->posts p LEFT JOIN $wpdb->comments c ON p.ID = c.`comment_post_ID` WHERE p.`post_type` = '%s' ORDER BY c.`comment_ID` DESC", VA_EVENT_PTYPE ) );
				$args['orderby'] = 'post__in';
				$args['post__in'] = $result_ids;
			break;
		case 'rand':
			$args['orderby'] = 'rand';
			break;
		case 'title':
			$args['orderby'] = 'title';
			break;
		case 'default':
		default:
			$args['meta_key'] = 'featured-home';
			$args['orderby'] = 'meta_value_num';
			$args['order'] = 'desc';
			$args['va-featured'] = true;
			break;
	}

	$query = new WP_Query( $args );
	return $query;
}

add_filter( 'appthemes_update_search_index_event', 'va_update_search_index_event_custom_fields', 10, 2 );
function va_update_search_index_event_custom_fields( $args, $post ) {

	$categories = array_keys( get_the_event_categories( $post->ID ) );
	foreach ( $categories as $category ) {
		foreach ( va_get_fields_for_cat( $category, VA_EVENT_CATEGORY ) as $field ) {
			$args['meta_keys'][] = $field['name'];
		}
	}

	return $args;
}

add_action( 'init', 'va_register_search_index_event_post_type', 10 );

function va_register_search_index_event_post_type() {

	if ( ! current_theme_supports( 'app-search-index' ) ) {
		return;
	}

	$event_index_args = array(
		'meta_keys' => array( 'address', 'facebook', 'twitter', 'website', 'phone', VA_EVENT_LOCATION_META_KEY, VA_EVENT_LOCATION_URL_META_KEY ),
		'taxonomies' => array( VA_EVENT_CATEGORY, VA_EVENT_TAG ),
	);

	APP_Search_Index::register( VA_EVENT_PTYPE, $event_index_args );
}

add_filter( 'appthemes_html_term_description_taxonomies', 'va_event_html_term_description_taxonomies' );

function va_event_html_term_description_taxonomies( $taxonomies ) {
	return array_merge( $taxonomies, array( VA_EVENT_CATEGORY, VA_EVENT_TAG ) );
}
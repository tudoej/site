<?php

// Various tweaks
add_action( 'admin_menu', 'va_events_admin_menu_tweak', 15 );
add_action( 'load-post-new.php', 'va_disable_admin_event_creation' );
add_action( 'load-post.php', 'va_disable_admin_event_editing' );

// Admin Scripts
add_action( 'admin_enqueue_scripts', 'va_event_add_admin_scripts', 10 );
add_action( 'admin_print_styles', 'va_events_icon' );

// Events First Run
add_action( 'va_events_first_run', 'va_init_events_menu_items' );
add_action( 'va_events_first_run', 'va_init_events_first_event' );
add_action( 'va_events_first_run', 'va_init_events_widgets' );

function va_init_events_menu_items() {
	$menu = wp_get_nav_menu_object( 'header' );

	if ( ( ! $menu && 0 !== $menu_id ) || is_wp_error( $menu ) ) {
		return;
	}

	$page_ids = array(
		VA_Event_Categories::get_id(),
		VA_Event_Create::get_id(),
	);

	$page_ids = apply_filters( 'va_init_event_menu_page_ids', $page_ids );

	foreach ( $page_ids as $page_id ) {
		$page = get_post( $page_id );

		if ( ! $page ) {
			continue;
		}

		$items = wp_get_associated_nav_menu_items( $page_id, 'post_type', 'page' );
		if ( ! empty( $items ) ) {
			continue;
		}

		wp_update_nav_menu_item( $menu->term_id, 0, array(
			'menu-item-type' => 'post_type',
			'menu-item-object' => 'page',
			'menu-item-object-id' => $page_id,
			'menu-item-title' => $page->post_title,
			'menu-item-url' => get_permalink( $page ),
			'menu-item-status' => 'publish',
		) );
	}
}

function va_init_events_first_event() {
	$events = get_posts( array(
		'post_type' => VA_EVENT_PTYPE,
		'posts_per_page' => 1,
	) );

	if ( empty( $events ) ) {
		$cat = appthemes_maybe_insert_term( 'WordPress', VA_EVENT_CATEGORY );

		$event_id = wp_insert_post( array(
			'post_type' => VA_EVENT_PTYPE,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
			'post_title' => 'WordCamp Moonbase 1',
			'post_content' => 'WordCamp is a conference that focuses on everything wordpress. Come join us on Moonbase 1 for a WordCamp that\'s out of this world!',
			'tax_input' => array(
				VA_EVENT_CATEGORY => array( $cat['term_id'] ),
				VA_EVENT_TAG => 'wordpress, wordcamp',
			)
		) );

		$days = $day_times = array();
		$sample_times = array( 'Sunrise-Sundown', '8:00 am-8:00 pm', '3:00-13:00' );
		for ( $x = 0; $x <= 2; $x++ ) {
			$date = date( 'Y-m-d', strtotime( '+' . ( $x + 10 ) . ' days' ) );
			$days[] = $date;
			$day_times[ $date ] = $sample_times[ $x ];
			va_insert_event_day( $date );
		}

		asort( $days );
		wp_set_object_terms( $event_id, $days, VA_EVENT_DAY );

		$data = array(
			VA_EVENT_LOCATION_META_KEY => '11 Armstrong Lane, Sea of Tranquility',
			VA_EVENT_LOCATION_URL_META_KEY => 'en.wikipedia.org/wiki/Mare_Tranquilitatis',
			VA_EVENT_COST_META_KEY => 'Free',

			VA_EVENT_DATE_META_KEY => reset( $days ),
			VA_EVENT_DATE_END_META_KEY => end( $days ),
			VA_EVENT_DAY_TIMES_META_KEY => $day_times,

			'va_id' => va_generate_id(),
			'address' => 'SR 405, Kennedy Space Center, FL 32899, USA',

			'featured-home' => 1,
			'featured-cat' => 0,
		);

		foreach ( $data as $key => $value ) {
			update_post_meta( $event_id, $key, $value );
		}

		appthemes_set_coordinates( $event_id, '28.522399', '-80.651235' );
	}
}

function va_init_events_widgets() {
	$sidebars_widgets = get_option( 'sidebars_widgets' );

	if ( ! array_key_exists( 'single-event', $sidebars_widgets ) ) {
		$sidebars_widgets['single-event'] = array();
		update_option( 'sidebars_widgets', $sidebars_widgets );
	}

	if ( ! empty( $sidebars_widgets['single-event'] ) ) {
		return;
	}

	$sidebars_widgets = array(
		'single-event' => array(
			'event_attendees' => array(
				'title' => __( 'Event Attendees', APP_TD ),
			),
			'listing_event_map' => array(
				'title' => __( 'Map', APP_TD ),
				'directions' => 1,
			),
			'sidebar_ad' => array(
				'title' => __( 'Sponsored Ad', APP_TD ),
				'text' => '<a href="https://www.appthemes.com" target="_blank"><img src="' . get_template_directory_uri() . '/images/cp-250x250a.gif" border="0" alt="ClassiPress - Premium Classified Ads Theme"></a>',
			),
			'recent_events' => array(
				'title' => __( 'Recently Added Events', APP_TD ),
				'number' => 5,
			),
		),
	);

	appthemes_install_widgets( $sidebars_widgets );
	appthemes_install_widget( 'create_event_button', 'main', array(), 1, 'prepend' );

}

function va_events_admin_menu_tweak() {
	global $menu;

	// move Events into Posts old spot
	$menu[7] = $menu[8];
	// clear the slot
	unset( $menu[8] );

}

function va_event_add_admin_scripts( $hook ) {
	global $post;

	if ( empty( $post ) || VA_EVENT_PTYPE != $post->post_type ) {
		return;
	}

	// selective load
	$pages = array ( 'edit.php', 'post.php', 'post-new.php', 'media-upload-popup' );

 	if ( ! in_array( $hook, $pages ) ) {
		return;
	}

	wp_enqueue_script( 'validate' );
	wp_enqueue_script( 'validate-lang' );

	wp_enqueue_script(
		'va-admin-event-edit',
		get_template_directory_uri() . '/includes/events/admin/scripts/event-edit.js',
		array( 'validate' ),
		VA_VERSION,
		true
	);

	wp_localize_script( 'va-admin-event-edit', 'VA_admin_l18n', array(
		'user_admin'     => current_user_can( 'manage_options' ),
		'event_type'     => VA_EVENT_PTYPE,
		'event_category' => VA_EVENT_CATEGORY,
		'post_type'      => ( isset( $post->post_type ) ? $post->post_type : '' ),
	) );

}


function va_events_icon() {
?>
<style type="text/css">
	#icon-post.icon32-posts-event,
	#icon-edit.icon32-posts-event {
		background: url('<?php echo get_stylesheet_directory_uri(); ?>/images/admin-icon-events-32x32.png') no-repeat 2px 6px;
	}
</style>
<?php
}

function va_disable_admin_event_creation() {
	if ( current_user_can( 'edit_others_events' ) ) {
		return;
	}

	if ( VA_EVENT_PTYPE != @$_GET['post_type'] ) {
		return;
	}

	wp_redirect( va_get_event_create_url() );
	exit;
}

function va_disable_admin_event_editing() {
	global $pagenow;

	if ( current_user_can( 'edit_others_events' ) ) {
		return;
	}

	if ( 'edit' != @$_GET['action'] ) {
		return;
	}

	$post_id = (int) @$_GET['post'];

	if ( VA_EVENT_PTYPE != get_post_type( $post_id ) ) {
		return;
	}

	wp_redirect( va_get_event_edit_url( $post_id ) );
	exit;
}

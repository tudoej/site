<?php

add_action( 'appthemes_framework_loaded', '_va_pre_load_events', 4 );
add_action( 'appthemes_framework_loaded', '_va_load_events', 5 );
add_action( 'appthemes_framework_loaded', '_va_load_events_admin_settings', 6 );

function va_events_enabled() {
	global $va_options;

	return $va_options->events_enabled;
}

function _va_load_events_admin_settings() {
	if ( is_admin() ) {
		appthemes_load_files( dirname( __FILE__ ) . '/admin/', array( 'settings.php' ) );
	}
}

function _va_pre_load_events() {
	global $va_options;

	if ( ! current_theme_supports( 'app-events' ) ) {
		return;
	}

	if ( is_admin() && !empty( $_GET['page'] ) && $_GET['page'] == 'app-settings' && !empty( $_GET['tab'] ) && $_GET['tab'] == 'events' ) {
		if ( $va_options->events_enabled ) {
			if( !empty( $_POST['action'] ) && $_POST['action'] == 'events' && empty( $_POST['events_enabled'] ) ) {
				// Events were enabled, and just were disabled.
				$va_options->events_enabled = false;
				return;
			}
		} else {
			if ( empty( $_POST['events_enabled'] ) ) {
				$va_options->events_enabled = false;
				return;
			} else {
				// Events were disabled, and were just enabled.
				add_action( 'tabs_vantage_page_app-settings', array( 'VA_Events_Settings_Tab', 'events_first_run' ), 11 );
				$va_options->events_enabled = true;
			}
		}
	}
}

function _va_load_events() {
	global $va_options;

	if ( ! current_theme_supports( 'app-events' ) ) {
		return;
	}

	if ( ! va_events_enabled() ) {
		return;
	}

	// Taxonomies need to be registered before the post type, in order for the rewrite rules to work
	add_action( 'init', '_va_register_event_taxonomies', 8 );
	add_action( 'init', '_va_register_event_post_types', 9 );
	add_action( 'init', 'va_setup_event_category_surcharges', 12 );

	extract( va_events_get_args(), EXTR_SKIP );

	$events_dir = str_replace( get_template_directory(), '', dirname( __FILE__ ) );

	define( 'VA_EVENTS_VERSION', '0.5' );
	define( 'VA_EVENTS_DIR', $events_dir );

	define( 'VA_EVENT_PTYPE', $post_type );
	define( 'VA_EVENT_CATEGORY', $category );
	define( 'VA_EVENT_TAG', $tag );
	define( 'VA_EVENT_DAY', $day );
	define( 'VA_EVENT_FAVORITES', 'va_event_favorites' );

	define( 'VA_EVENT_DAY_TIMES_META_KEY', '_' . $meta_key_prefix . 'event_day_times' );
	define( 'VA_EVENT_DATE_META_KEY', '_' . $meta_key_prefix . 'event_date' );
	define( 'VA_EVENT_DATE_END_META_KEY', '_' . $meta_key_prefix . 'event_end_date' );
	define( 'VA_EVENT_LOCATION_META_KEY', '_' . $meta_key_prefix . 'event_location' );
	define( 'VA_EVENT_LOCATION_URL_META_KEY', '_' . $meta_key_prefix . 'event_location_url' );
	define( 'VA_EVENT_COST_META_KEY', '_' . $meta_key_prefix . 'event_cost' );

	define( 'VA_EVENT_ATTENDEES_META_KEY', '_' . $meta_key_prefix . 'event_attendees' );

	define( 'VA_EVENT_COMMENT_CTYPE', $comment_type );

	define( 'VA_EVENT_ATTENDEE_CONNECTION', $attendee_connection );

	// register event item
	$payments = get_theme_support( 'app-payments' );
	define( 'VA_EVENT_ITEM_REGULAR', 'regular-event' );
	$payments[0]['items'][] = array(
		'type'  => VA_EVENT_ITEM_REGULAR,
		'title' => __( 'Regular Event', APP_TD ),
		'meta'  => array(
		'price' => $va_options->event_price
		)
	);

	$payments[0]['items_post_types'][] = VA_EVENT_PTYPE;

	add_theme_support( 'app-payments', $payments[0] );

	$load_files = array(
		'capabilities.php',
		'comments.php',
		'custom-forms.php',
		'dashboard.php',
		'emails.php',
		'event-activate.php',
		'event-form.php',
		'event-purchase.php',
		'event-status.php',
		'favorites.php',
		'featured.php',
		'functions.php',
		'template-tags.php',
		'views.php',
		'views-checkout.php',
		'widgets.php',
		'social.php',
		'delete-event.php',
	);
	appthemes_load_files( dirname( __FILE__ ) . '/', $load_files );

	$load_classes = array(
		'VA_Event_Archive',
		'VA_Event_Categories',
		'VA_Event_Create',
		'VA_Event_Edit',
		'VA_Event_Gateway_Select',
		'VA_Event_Gateway_Process',
		'VA_Event_Info_Edit',
		'VA_Event_Info_Purchase',
		'VA_Event_Order_Summary',
		'VA_Event_Purchase',
		'VA_Event_Search',
		'VA_Event_Single',
		'VA_Event_Taxonomy',
		'VA_Event_Taxonomy_404',
		'VA_Select_Event_Plan_New',
		'VA_Select_Event_Plan_Existing',
	);
	appthemes_add_instance( $load_classes );


	if ( is_admin() ) {

		$load_files = array(
			'admin.php',
			'event-list.php',
			'event-single.php',
			'importer.php',
			'pricing.php',
			'settings.php',
		);
		appthemes_load_files( dirname( __FILE__ ) . '/admin/', $load_files );

		$load_classes = array(
			'VA_Event_Author_Metabox',
			'VA_Event_Comments_Status_Metabox',
			'VA_Event_Contact_Metabox',
			'VA_Event_Custom_Forms_Metabox',
			'VA_Event_Featured_Metabox',
			'VA_Event_Dates_Metabox',
			'VA_Event_Location_Metabox',
			'VA_Event_Publish_Moderation_Metabox',
			'VA_Event_Attachments_Metabox',
		);
		appthemes_add_instance( $load_classes );

	}

	va_register_sidebar( 'single-event', __( 'Single Event Sidebar', APP_TD ), __( 'The sidebar for single Event page', APP_TD ) );
	va_register_sidebar( 'create-event', __( 'Create Event Sidebar', APP_TD ), __( 'The sidebar for create Event pages', APP_TD ) );
	va_register_sidebar( 'edit-event', __( 'Edit Event Sidebar', APP_TD ), __( 'The sidebar for the edit Event page', APP_TD ) );

	// Pings 'update services' while publish event.
	add_action( 'publish_' . VA_EVENT_PTYPE, '_publish_post_hook', 5, 1 );

}

function va_events_get_args() {

	if ( ! current_theme_supports( 'app-events' ) ) {
		return array();
	}

	list( $args ) = get_theme_support( 'app-events' );

	return $args;
}

function va_events_get_options() {
	$options = va_events_get_args();
	return $options['options'];
}

function _va_register_event_post_types() {
	if ( ! current_theme_supports( 'app-events' ) ) {
		return;
	}

	$options = va_events_get_options();

	$labels = array(
		'name'               => __( 'Events', APP_TD ),
		'singular_name'      => __( 'Event', APP_TD ),
		'add_new'            => __( 'Add New', APP_TD ),
		'add_new_item'       => __( 'Add New Event', APP_TD ),
		'edit_item'          => __( 'Edit Event', APP_TD ),
		'new_item'           => __( 'New Event', APP_TD ),
		'view_item'          => __( 'View Event', APP_TD ),
		'search_items'       => __( 'Search Events', APP_TD ),
		'not_found'          => __( 'No events found', APP_TD ),
		'not_found_in_trash' => __( 'No events found in Trash', APP_TD ),
		'parent_item_colon'  => __( 'Parent Event:', APP_TD ),
		'menu_name'          => __( 'Events', APP_TD ),
	);

	$args = array(
		'labels'              => $labels,
		'hierarchical'        => false,
		'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions' ),
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 8,
		'menu_icon'           => 'dashicons-calendar-alt',
		'show_in_nav_menus'   => false,
		'publicly_queryable'  => true,
		'exclude_from_search' => true,
		'has_archive'         => true,
		'query_var'           => true,
		'can_export'          => true,
		'rewrite'             => array(
			'slug'       => $options->event_permalink,
			'with_front' => false
		),
		'capability_type'     => 'listing',
		'map_meta_cap'        => true
	);

	if ( current_user_can( 'edit_others_posts' ) ) {
		$args['supports'][] = 'custom-fields';
	}

	register_post_type( VA_EVENT_PTYPE, $args );

	p2p_register_connection_type( array(
		'name'      => VA_EVENT_ATTENDEE_CONNECTION,
		'from'      => VA_EVENT_PTYPE,
		'to'        => 'user',
		'admin_box' => array(
			'show'    => 'any',
			'context' => 'advanced'
		),
	) );

}

function _va_register_event_taxonomies() {
	if ( ! current_theme_supports( 'app-events' ) ) {
		return;
	}

	$options = va_events_get_options();

	$labels = array(
		'name'                => __( 'Event Categories', APP_TD ),
		'singular_name'       => __( 'Event Category', APP_TD ),
		'search_items'        => __( 'Search Event Categories', APP_TD ),
		'all_items'           => __( 'All Categories', APP_TD ),
		'parent_item'         => __( 'Parent Event Category', APP_TD ),
		'parent_item_colon'   => __( 'Parent Event Category:', APP_TD ),
		'edit_item'           => __( 'Edit Event Category', APP_TD ),
		'update_item'         => __( 'Update Event Category', APP_TD ),
		'add_new_item'        => __( 'Add New Event Category', APP_TD ),
		'new_item_name'       => __( 'New Event Category Name', APP_TD ),
		'add_or_remove_items' => __( 'Add or remove event categories', APP_TD ),
		'menu_name'           => __( 'Categories', APP_TD ),
	);

	$args = array(
		'labels'            => $labels,
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => true,
		'show_tagcloud'     => false,
		'hierarchical'      => true,
		'query_var'         => true,
		'rewrite'           => array(
			'slug'       => $options->event_permalink . '/' . $options->event_cat_permalink,
			'with_front' => false
		),
	);

	register_taxonomy( VA_EVENT_CATEGORY, VA_EVENT_PTYPE, $args );

	$labels = array(
		'name'                       => __( 'Event Tags', APP_TD ),
		'singular_name'              => __( 'Event Tag', APP_TD ),
		'search_items'               => __( 'Search Event Tags', APP_TD ),
		'popular_items'              => __( 'Popular Event Tags', APP_TD ),
		'all_items'                  => __( 'All Event Tags', APP_TD ),
		'parent_item'                => __( 'Parent Event Tag', APP_TD ),
		'parent_item_colon'          => __( 'Parent Event Tag:', APP_TD ),
		'edit_item'                  => __( 'Edit Event Tag', APP_TD ),
		'update_item'                => __( 'Update Event Tag', APP_TD ),
		'add_new_item'               => __( 'Add New Event Tag', APP_TD ),
		'new_item_name'              => __( 'New Event Tag Name', APP_TD ),
		'separate_items_with_commas' => __( 'Separate event tags with commas', APP_TD ),
		'add_or_remove_items'        => __( 'Add or remove event tags', APP_TD ),
		'choose_from_most_used'      => __( 'Choose from the most used event tags', APP_TD ),
		'menu_name'                  => __( 'Tags', APP_TD ),
	);

	$args = array(
		'labels'            => $labels,
		'public'            => true,
		'show_in_nav_menus' => true,
		'show_ui'           => true,
		'show_tagcloud'     => true,
		'hierarchical'      => false,
		'query_var'         => true,
		'rewrite'           => array(
			'slug'       => $options->event_permalink . '/' . $options->event_tag_permalink,
			'with_front' => false
		),
	);

	register_taxonomy( VA_EVENT_TAG, VA_EVENT_PTYPE, $args );

	$labels = array(
		'name'                       => __( 'Event Days', APP_TD ),
		'singular_name'              => __( 'Event Day', APP_TD ),
		'search_items'               => __( 'Search Event Days', APP_TD ),
		'popular_items'              => __( 'Popular Event Days', APP_TD ),
		'all_items'                  => __( 'All Event Days', APP_TD ),
		'parent_item'                => __( 'Parent Event Day', APP_TD ),
		'parent_item_colon'          => __( 'Parent Event Day:', APP_TD ),
		'edit_item'                  => __( 'Edit Event Day', APP_TD ),
		'update_item'                => __( 'Update Event Day', APP_TD ),
		'add_new_item'               => __( 'Add New Event Day', APP_TD ),
		'new_item_name'              => __( 'New Event Day Name', APP_TD ),
		'separate_items_with_commas' => __( 'Separate event days with commas', APP_TD ),
		'add_or_remove_items'        => __( 'Add or remove event days', APP_TD ),
		'choose_from_most_used'      => __( 'Choose from the most used event days', APP_TD ),
		'menu_name'                  => __( 'Days', APP_TD ),
	);

	$args = array(
		'labels'            => $labels,
		'public'            => false,
		'show_in_nav_menus' => false,
		'show_ui'           => false,
		'show_tagcloud'     => false,
		'hierarchical'      => true,
		'query_var'         => true,
		'rewrite'           => array(
			'slug'       => $options->event_permalink . '/' . $options->event_day_permalink,
			'with_front' => false
		),
	);

	register_taxonomy( VA_EVENT_DAY, VA_EVENT_PTYPE, $args );
}


function va_setup_event_category_surcharges() {
	global $va_options;

	if ( ! $va_options->event_charge ) {
		return;
	}

	$args = array(
		'orderby'    => 'name',
		'hide_empty' => false,
	);

	APP_Item_Registry::register( VA_EVENT_PTYPE, __( 'Event', APP_TD ) );

	$event_categories = get_terms( VA_EVENT_CATEGORY, $args );
	foreach ( $event_categories as $category ) {
		APP_Item_Registry::register( VA_EVENT_CATEGORY . '_' . $category->term_id, 'Category: "'.$category->name.'"', $category );
	}

	add_filter( 'va_multiple_category_checklist_label', 'va_event_category_checklist_label_surcharges', 10, 3 );
}

function va_event_category_checklist_label_surcharges( $label, $category, $taxonomy ) {
	global $va_options;

	if ( VA_EVENT_CATEGORY != $taxonomy ) {
		return $label;
	}

	$surcharge = va_get_category_surcharge( $category, $taxonomy, 'id' );

	if ( ! empty( $surcharge ) ) {
		$label .= sprintf( __( ' (add %s)', APP_TD ), APP_Currencies::get_price( $surcharge ) );
	}

	return $label;
}

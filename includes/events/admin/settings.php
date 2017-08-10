<?php

add_action('admin_init', 'va_events_setup_settings_tab_init' );

function va_events_setup_settings_tab_init() {
	add_action( 'tabs_vantage_page_app-settings', array( 'VA_Events_Settings_Tab', 'init' ) );
	add_action( 'tabs_vantage_page_app-settings_page_content', array( 'VA_Events_Settings_Tab', 'page_content' ), 10 );
	add_action( 'admin_notices', array( 'VA_Events_Settings_Tab', 'prune_events' ) );
	add_action( 'admin_notices', array( 'VA_Events_Settings_Tab', 'events_enabled' ) );
}

class VA_Events_Settings_Tab {

	private static $page;

	static function prune_events() {
		if ( isset( $_GET['prune'] ) && $_GET['prune'] == 1 && isset( $_GET['tab'] ) && $_GET['tab'] == 'events' ) {
			va_prune_expired_events();
			echo scb_admin_notice( 'Expired events have been pruned' );
		}
	}

	static function events_enabled() {
		if ( isset( $_GET['enabled'] ) && $_GET['enabled'] == 1 && isset( $_GET['tab'] ) && $_GET['tab'] == 'events' ) {
			echo scb_admin_notice( 'Events have been enabled' );
		}
	}

	// only show the events enabled block by itself until events have been activated.
	static function page_content( $page ) {
		global $va_options;

		if ( false === $va_options->get('events_enabled') )
			$page->tab_sections['events'] = wp_array_slice_assoc( $page->tab_sections['events'], array('events_enabled'));
		else
			unset( $page->tab_sections['events']['events_enabled'] );

	}

	static function events_first_run( $page ) {
		global $va_options;

		if ( true === $va_options->events_enabled && !empty( $_POST['events_enabled'] ) ) {
			remove_action( 'admin_init', 'appthemes_update_redirect' );
			remove_action( 'appthemes_first_run', 'appthemes_updated_version_notice', 999 );
			do_action( 'appthemes_first_run' );
			do_action( 'va_events_first_run' );
			echo html( 'script', 'location.href="' . admin_url( 'admin.php?page=app-settings&tab=events&enabled=1' ) . '"' );
		}
	}

	static function init( $page ) {
		self::$page = $page;

		$page->tabs->add( 'events', __( 'Events', APP_TD ) );

		// we're calling this in two separate places
		$events_enabled = array(
			'title' => __( 'Enable Events', APP_TD ),
			'type' => 'checkbox',
			'name' => 'events_enabled',
			'value' => 1,
			'desc' => __( 'Activate the events component', APP_TD ),
			'tip' => '',
		);

		$page->tab_sections['events']['events_enabled'] = array(
			'fields' => array(
				$events_enabled,
			),
		);

		$page->tab_sections['events']['general'] = array(
			'title' => __( 'General', APP_TD ),
			'fields' => array(
				$events_enabled,
				array(
					'title' => __( 'Charge for Events', APP_TD ),
					'name' => 'event_charge',
					'type' => 'checkbox',
					'desc' => __( 'Start accepting payments', APP_TD ),
					'tip' => __( 'This activates the payments system. Left unchecked, listings will be free to post.', APP_TD ),
				),
				array(
					'title' => __( 'Categories', APP_TD ),
					'type' => 'number',
					'name' => 'event_included_categories',
					'extra'	 => array(
						'class' => 'small-text'
					),
					'sanitize' => 'absint',
					'desc' => __( 'Number of categories an event can belong to', APP_TD ),
					'tip' => __( "Allows users to choose this amount of categories for their event. Zero means unlimited. This option only works if 'Charge for Events' is not enabled.", APP_TD ),
				),
				array(
					'title' => __( 'Content Editor', APP_TD ),
					'name' => 'editor_event',
					'type' => 'select',
					'values' => array(
						'default' => __( 'Disabled', APP_TD ),
						'html' => __( 'HTML Editor', APP_TD ),
						'tmce' => __( 'TinyMCE Editor', APP_TD ),
					),
					'desc' => __( 'Turns on an advanced text editor for users', APP_TD ),
					'tip' => __( 'This allows listing owners to use html markup in text area fields.', APP_TD ),
				),
				array(
					'title' => __( 'Event Lifespan', APP_TD ),
					'type' => 'number',
					'name' => 'event_expiration',
					'extra'	 => array(
						'class' => 'small-text'
					),
					'sanitize' => 'absint',
					'desc' => __( 'Number of days later to remove the event after it expires', APP_TD ),
					'tip' => __( 'Allows you to keep events live after they expire. Zero (recommended) means the event will stay live forever.', APP_TD ),
				),
			)
		);

		$page->tab_sections['events']['appearance'] = array(
			'title' => __( 'Appearance', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Events Per Page', APP_TD ),
					'type' => 'number',
					'name' => 'events_per_page',
					'extra'	 => array(
						'class' => 'small-text'
					),
					'desc' => __( 'How many events per page to show', APP_TD ),
					'tip' => '',
				),
				array(
					'title' => __( 'General Events Sort', APP_TD ),
					'name' => 'default_event_sort',
					'type' => 'select',
					'values' => array(
						'title' => __( 'Alphabetical', APP_TD ),
						'event_date' => __( 'Event Date (recommended)', APP_TD ),
						'most_comments' => __( 'Most Comments', APP_TD ),
						'default' => __( 'Default', APP_TD ),
						'newest' => __( 'Newest First', APP_TD ),
						'popular' => __( 'Popular', APP_TD ),
						'rand' => __( 'Random', APP_TD ),
						'recently_discussed' => __( 'Recently Discussed', APP_TD ),
					),
					'desc' => __( 'The order events are displayed across your site', APP_TD ),
					'tip' => '',
				),
				array(
					'title' => __( 'Home Events Sort', APP_TD ),
					'name' => 'default_event_home_sort',
					'type' => 'select',
					'values' => array(
						'title' => __( 'Alphabetical', APP_TD ),
						'event_date' => __( 'Event Date (recommended)', APP_TD ),
						'most_comments' => __( 'Most Comments', APP_TD ),
						'default' => __( 'Default', APP_TD ),
						'newest' => __( 'Newest First', APP_TD ),
						'popular' => __( 'Popular', APP_TD ),
						'rand' => __( 'Random', APP_TD ),
						'recently_discussed' => __( 'Recently Discussed', APP_TD ),
					),
					'desc' => __( 'The order events are displayed on your home and main events page', APP_TD ),
					'tip' => '',
				),
				array(
					'title' => __( 'Featured Events Sort', APP_TD ),
					'type' => 'select',
					'name' => 'events_featured_sort',
					'values' => array(
						'newest' => __( 'Newest First', APP_TD ),
						'oldest' => __( 'Oldest First', APP_TD ),
						'random' => __( 'Random', APP_TD ),
					),
					'desc' => __( 'The order featured events are displayed', APP_TD ),
					'tip' => '',
				),
			),
		);

		$page->tab_sections['events']['moderate'] = array(
			'title' => __( 'Moderate', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Events', APP_TD ),
					'type' => 'checkbox',
					'name' => 'moderate_events',
					'desc' => __( 'Manually approve and publish each new event', APP_TD ),
					'tip' => __( 'Left unchecked, events go live immediately without being moderated (unless it has not been paid for).', APP_TD ),
				),
			)
		);

		$page->tab_sections['events']['search'] = array(
			'title' => __( 'Search', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'General Sorting', APP_TD ),
					'name' => 'default_event_search_sort',
					'type' => 'select',
					'values' => array(
						'title' => __( 'Alphabetical', APP_TD ),
						'event_date' => __( 'Event Date', APP_TD ),
						'most_comments' => __( 'Most Comments', APP_TD ),
						'default' => __( 'Most Relevant (recommended)', APP_TD ),
						'newest' => __( 'Newest First', APP_TD ),
						'popular' => __( 'Popular', APP_TD ),
						'rand' => __( 'Random', APP_TD ),
						'recently_discussed' => __( 'Recently Discussed', APP_TD ),
					),
					'desc' => __( 'When a search query excludes a location', APP_TD ),
					'tip' => __( 'The default search result order when a search is made without a location entered.', APP_TD ),
				),
				array(
					'title' => __( 'Location Sorting', APP_TD ),
					'name' => 'default_event_geo_search_sort',
					'type' => 'select',
					'values' => array(
						'title' => __( 'Alphabetical', APP_TD ),
						'distance' => __( 'Closest Distance (recommended)', APP_TD ),
						'event_date' => __( 'Event Date', APP_TD ),
						'most_comments' => __( 'Most Comments', APP_TD ),
						'default' => __( 'Most Relevant', APP_TD ),
						'newest' => __( 'Newest First', APP_TD ),
						'popular' => __( 'Popular', APP_TD ),
						'rand' => __( 'Random', APP_TD ),
						'recently_discussed' => __( 'Recently Discussed', APP_TD ),
					),
					'desc' => __( 'When a search query includes a location', APP_TD ),
					'tip' => __( 'The default search results order when a search is made with a location entered.', APP_TD ),
				),
			)
		);

		$page->tab_sections['events']['maintenance'] = array(
			'title' => __( 'Maintenance', APP_TD ),
			'fields' => array(
			array(
					'title' => __( 'Prune Events', APP_TD ),
					'name' => '_blank',
					'type' => '',
					'desc' => sprintf( __( 'Prune  <a href="%s">expired events</a>', APP_TD ), 'admin.php?page=app-settings&tab=events&prune=1' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'tip' => __( 'Manually prune any expired events. This will run only one time.', APP_TD ),
				),
			),
		);

		$page->tab_sections['events']['integration'] = array(
			'title' => __( 'ShareThis', APP_TD ),
			'desc' => sprintf( __( 'This option requires the <a href="%1$s" target="_blank">ShareThis</a> plugin to be installed first.', APP_TD ) , 'http://wordpress.org/extend/plugins/share-this/' ),
			'fields' => array(
				array(
					'title' => __( 'Events', APP_TD ),
					'type' => 'checkbox',
					'desc' => __( 'Show on the event lists view', APP_TD ),
					'name' => 'event_sharethis',
					'extra' => ( ! function_exists ( 'sharethis_button' ) ? array ( 'disabled' => 'disabled' ) : '' ),
					'tip' => '',
				),
			),
		);

	}

}
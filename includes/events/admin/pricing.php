<?php

add_action('admin_init', 'va_events_pricing_setup_settings_tab_init' );

function va_events_pricing_setup_settings_tab_init() {
	global $admin_page_hooks;

	add_action( 'tabs_'.$admin_page_hooks['app-payments'].'_page_app-payments-settings', array( 'VA_Events_Pricing_Settings_Tab', 'init' ) );
	add_action( 'tabs_'.$admin_page_hooks['app-payments'].'_page_app-payments-settings_page_content', array( 'VA_Events_Pricing_Settings_Tab', 'page_content' ), 10 );
}

class VA_Events_Pricing_Settings_Tab {

	private static $page;

	static function page_content( $page ) {
		global $va_options;

		if ( false === $va_options->get('events_enabled') ) {
			$page->tab_sections['events'] = wp_array_slice_assoc( $page->tab_sections['events'], array('events_enabled'));
		} else if( false === $va_options->get('event_charge') ) {
			$page->tab_sections['events'] = wp_array_slice_assoc( $page->tab_sections['events'], array('event_charge'));
		} else {
			unset( $page->tab_sections['events']['events_enabled'] );
			unset( $page->tab_sections['events']['event_charge'] );
		}
	}

	static function init( $page ) {

		self::$page = $page;

		$page->tabs->add_after( 'listings', 'events', __( 'Events', APP_TD ) );

		$page->tab_sections['events']['events_enabled'] = array(
			'title' => __( 'Pricing', APP_TD ),
			'desc' => sprintf( __( 'You need to <a href="%s">enable events</a> before setting up price models.', APP_TD ), 'admin.php?page=app-settings&tab=events' ),
			'fields' => array(
			),
		);

		$page->tab_sections['events']['event_charge'] = array(
			'title' => __( 'Pricing', APP_TD ),
			'desc' => sprintf( __( 'You need to enable the <a href="%s">"Charge for Events"</a> option before setting up price models.', APP_TD ), 'admin.php?page=app-settings&tab=events' ),
			'fields' => array(
			),
		);

		$page->tab_sections['events']['pricing'] = array(
			'title' => __( 'Pricing', APP_TD ),
			'fields' => array (
				array(
					'title' => __( 'Price', APP_TD ),
					'type' => 'text',
					'name' => 'event_price',
					'sanitize' => 'appthemes_absfloat',
					'desc' => __( 'Price to list an event', APP_TD ),
					'tip' => '',
					'extra' => array(
						'class' => 'small-text'
					),
				),
				array(
					'title' => __( 'Featured', APP_TD ),
					'type' => 'checkbox',
					'name' => 'event_featured-home_enabled',
					'desc' => __( 'Enable featured on home page option', APP_TD ),
					'tip' => '',
				),
				array(
					'title' => __( 'Featured Price', APP_TD ),
					'type' => 'text',
					'name' => 'event_featured-home_price',
					'sanitize' => 'appthemes_absfloat',
					'desc' => __( 'Price to be featured on the home page', APP_TD ),
					'tip' => '',
					'extra' => array(
						'class' => 'small-text'
					),
				),
				array(
					'title' => __( 'Featured Category', APP_TD ),
					'type' => 'checkbox',
					'name' => 'event_featured-cat_enabled',
					'desc' => __( 'Enable featured on category option', APP_TD ),
				),
				array(
					'title' => __( 'Featured Category Price', APP_TD ),
					'type' => 'text',
					'name' => 'event_featured-cat_price',
					'sanitize' => 'appthemes_absfloat',
					'desc' => __( 'Price to be featured on a category page', APP_TD ),
					'tip' => '',
					'extra' => array(
						'class' => 'small-text'
					),
				),
			),
		);

	}
}

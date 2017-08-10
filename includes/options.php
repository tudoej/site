<?php

$GLOBALS['va_options'] = new scbOptions( 'va_options', false, array(
	'geocoder' => 'google',
	'geocoder_settings' => array(
		'google' => array (
			'geo_region' => 'us',
			'geo_language' => 'en',
			'geo_unit' => 'mi',
			'api_key' => '',
		),
	),
	'map_provider' => 'google',
	'map_provider_settings' => array (
		'google' => array (
			'geo_region' => 'us',
			'geo_language' => 'en',
			'geo_unit' => 'mi',
			'api_key' => '',
		),
	),

	'geo_unit' => 'mi',

	'currency_code' => 'USD',
	'currency_identifier' => 'symbol',
	'currency_position' => 'left',
	'thousands_separator' => ',',
	'decimal_separator' => '.',
	'tax_charge' => 0,

	'color' => 'blue',

	// Security Settings
	'admin_security' => 'manage_options',
	'wp_login'       => false,

	// Listings
	'listing_price' => 0,
	'listing_charge' => 'no',
	'listing_duration' => 30,
	'moderate_listings' => 'no',
	'moderate_claimed_listings' => 'yes',
	'included_categories' => 0,

	'listings_per_page' => 10,
	'default_listing_sort' => 'default',
	'default_listing_home_sort' => 'default',
	'listings_featured_sort' => 'random',

	'editor_listing' => 'default',

	// Search
	'default_geo_search_sort' => 'distance',
	'default_search_sort' => 'rating',
	'default_radius' => '',

	// Featured Listings
	'addons' => array(
		VA_ITEM_FEATURED_HOME => array(
			'enabled' => 'yes',
			'price' => 0,
			'duration' => 30,
			'period' => 30,
			'period_type' => 'D',
		),

		VA_ITEM_FEATURED_CAT => array(
			'enabled' => 'yes',
			'price' => 0,
			'duration' => 30,
			'period' => 30,
			'period_type' => 'D',
		),
	),

	// Category Surcharges
	'category_surcharges' => array(),

	// Category Options
	'categories_menu' => array(
		'count' => 0,
		'depth' => 3,
		'sub_num' => 3,
		'hide_empty' => false,
		'only_sub_cats' => false,
	),
	'categories_dir' => array(
		'count' => 0,
		'depth' => 3,
		'sub_num' => 3,
		'hide_empty' => false,
	),

	// Events
	'events_enabled' => false,

	'default_event_geo_search_sort' => 'distance',
	'default_event_search_sort' => 'event_date',
	'default_event_radius' => '',

	'event_charge' => 'no',
	'event_price' => 0,
	'event_featured-home_enabled' => true,
	'event_featured-home_price' => 0,
	'event_featured-cat_enabled' => true,
	'event_featured-cat_price' => 0,
	'event_included_categories' => 0,
	'event_expiration' => 30,

	'moderate_events' => 'no',

	'events_per_page' => 10,
	'default_event_sort' => 'default',
	'default_event_home_sort' => 'default',
	'events_featured_sort' => 'random',

	'editor_event' => 'default',

	// Permalinks
	'listing_permalink' 		 	=> 'listings',
	'edit_listing_permalink'  	 	=> 'edit',
	'renew_listing_permalink'  	 	=> 'renew',
	'claim_listing_permalink' 	 	=> 'claim',
	'purchase_listing_permalink' 	=> 'purchase',
	'listing_cat_permalink' 	 	=> 'category',
	'listing_tag_permalink' 	 	=> 'tag',

	'event_permalink'				=> 'events',

	'edit_event_permalink'			=> 'edit',
	'purchase_event_permalink'		=> 'purchase',

	'event_cat_permalink'			=> 'category',
	'event_tag_permalink'			=> 'tag',
	'event_day_permalink'			=> 'day',

	'dashboard_events_permalink'	=> 'events',
	'dashboard_event_comments_permalink'	=> 'comments',
	'dashboard_event_favorites_permalink'	=> 'event-favorites',
	'dashboard_events_attending_permalink'	=> 'attending',

	'dashboard_permalink' 	 	 	=> 'dashboard',
	'dashboard_listings_permalink'	=> 'listings',
	'dashboard_claimed_permalink'	=> 'claimed-listings',
	'dashboard_reviews_permalink'	=> 'reviews',
	'dashboard_faves_permalink'  	=> 'favorites',

	// Gateways
	'gateways' => array(
		'enabled' => array()
	),

	// Integration
	'listing_sharethis' => 0,
	'event_sharethis' => 0,
	'blog_post_sharethis' => 0,

	// Upgrade Checks
	'page_template_updates_1_2' => false,
	'default_user_role_update_1_3_2' => false,
) );


<?php

class VA_Settings_Admin extends APP_Tabs_Page {

	protected $permalink_sections;
	protected $permalink_options;

	function setup() {

		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title' => __( 'Vantage Settings', APP_TD ),
			'menu_title' => __( 'Settings', APP_TD ),
			'page_slug' => 'app-settings',
			'parent' => 'app-dashboard',
			'screen_icon' => 'options-general',
			'admin_action_priority' => 10,
		);

		add_action( 'admin_notices', array( $this, 'admin_tools' ) );
	}

	public function admin_tools() {
		global $va_options;

		if ( isset( $_GET['prune'] ) && $_GET['prune'] == 1 && isset( $_GET['tab'] ) && $_GET['tab'] == 'advanced' ) {
			va_prune_expired_listings();
			va_prune_expired_featured();
			echo scb_admin_notice( __( 'Expired listings have been pruned', APP_TD ) );
		}

		if ( isset( $_GET['va_user_roles_ignore'] ) ) {
			$va_options->default_user_role_update_1_3_2 = true;
		}
	}

	protected function init_tabs() {
		// Remove unwanted query args from urls
		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'firstrun', 'enabled', 'prune', 'va_user_roles_ignore' ), $_SERVER['REQUEST_URI'] );

		$this->tabs->add( 'general', __( 'General', APP_TD ) );
		$this->tabs->add( 'listings', __( 'Listings', APP_TD ) );
		$this->tabs->add( 'security', __( 'Security', APP_TD ) );
		$this->tabs->add( 'advanced', __( 'Advanced', APP_TD ) );

		$this->tab_sections['general']['appearance'] = array(
			'title' => __( 'Appearance', APP_TD ),
			'desc' => sprintf( __( 'Further customize the look and feel by visiting the <a href="%1$s">WordPress customizer</a>.', APP_TD ), 'customize.php' ),
			'fields' => array(
				array(
					'title' => __( 'Scheme', APP_TD ),
					'type' => 'select',
					'name' => 'color',
					'values' => _va_get_color_choices(),
					'tip' => '',
				),
			),
		);

		$this->tab_sections['listings'][] = array(
			'title' => __( 'General', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Charge for Listings', APP_TD ),
					'name' => 'listing_charge',
					'type' => 'checkbox',
					'desc' => __( 'Start accepting payments', APP_TD ),
					'tip' => __( 'This activates the payments system. Left unchecked, listings will be free to post.', APP_TD ),
				),
				array(
					'title' => __( 'Categories', APP_TD ),
					'type' => 'number',
					'name' => 'included_categories',
					'desc' => __( 'Number of categories a listing can belong to', APP_TD),
					'extra'	 => array(
						'class' => 'small-text'
					),
					'sanitize' => 'absint',
					'tip' => __( "Allows users to choose this amount of categories for their listing. Zero means unlimited. This option only works if 'Charge for Listings' is not enabled.", APP_TD ),
				),
				array(
					'title' => __( 'Content Editor', APP_TD ),
					'name' => 'editor_listing',
					'type' => 'select',
					'values' => array(
						'default' => __( 'Disabled', APP_TD ),
						'html' => __( 'HTML Editor', APP_TD ),
						'tmce' => __( 'TinyMCE Editor', APP_TD ),
					),
					'desc' => __( 'Turns on an advanced text editor for users', APP_TD ),
					'tip' => __( 'This allows listing owners to use html markup in text area fields.', APP_TD ),
				),
			)
		);

		$this->tab_sections['listings']['appearance'] = array(
			'title' => __( 'Appearance', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Listings Per Page', APP_TD ),
					'type' => 'number',
					'name' => 'listings_per_page',
					'desc' => __( 'How many listings per page to show', APP_TD ),
					'tip' => '',
					'extra'	 => array(
						'class' => 'small-text'
					),
				),
				array(
					'title' => __( 'Main Listings Sort', APP_TD ),
					'name' => 'default_listing_sort',
					'type' => 'select',
					'values' => array(
						'title' => __( 'Alphabetical', APP_TD ),
						'default' => __( 'Default (recommended)', APP_TD ),
						'highest_rating' => __( 'Highest Rating', APP_TD ),
						'most_ratings' => __( 'Most Ratings', APP_TD ),
						'newest' => __( 'Newest', APP_TD ),
						'rand' => __( 'Random', APP_TD ),
						'recently_reviewed' => __( 'Recently Reviewed', APP_TD ),
					),
					'desc' => __( 'The order listings are displayed across your site', APP_TD ),
					'tip' => ''
				),
				array(
					'title' => __( 'Home Listings Sort', APP_TD ),
					'name' => 'default_listing_home_sort',
					'type' => 'select',
					'values' => array(
						'title' => __( 'Alphabetical', APP_TD ),
						'default' => __( 'Default (recommended)', APP_TD ),
						'highest_rating' => __( 'Highest Rating', APP_TD ),
						'most_ratings' => __( 'Most Ratings', APP_TD ),
						'newest' => __( 'Newest', APP_TD ),
						'rand' => __( 'Random', APP_TD ),
						'recently_reviewed' => __( 'Recently Reviewed', APP_TD ),
					),
					'desc' => __( 'The order listings are displayed on your home page', APP_TD ),
					'tip' => '',
				),
				array(
					'title' => __( 'Featured Listings Sort', APP_TD ),
					'type' => 'select',
					'name' => 'listings_featured_sort',
					'values' => array(
						'newest' => __( 'Newest First', APP_TD ),
						'oldest' => __( 'Oldest First', APP_TD ),
						'random' => __( 'Random', APP_TD ),
					),
					'desc' => __( 'The order featured listings are displayed', APP_TD ),
					'tip' => '',
				),
			),
		);

		$this->tab_sections['listings']['moderate'] = array(
			'title' => __( 'Moderate', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Listings', APP_TD ),
					'type' => 'checkbox',
					'name' => 'moderate_listings',
					'desc' => __( 'Manually approve and publish each new listing', APP_TD ),
					'tip' => __( 'Left unchecked, listings go live immediately without being moderated (unless it has not been paid for).', APP_TD ),
				),
				array(
					'title' => __( 'Claimed Listings', APP_TD ),
					'type' => 'checkbox',
					'name' => 'moderate_claimed_listings',
					'desc' => __( 'Manually approve each new listing claim', APP_TD ),
					'tip' => __( 'Left unchecked, listing claims are transfered immediately to the requesting claimee.', APP_TD ),
				),
			)
		);

		$this->tab_sections['listings']['search'] = array(
			'title' => __( 'Search', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'General Sorting', APP_TD ),
					'name' => 'default_search_sort',
					'type' => 'select',
					'values' => array(
						'title' => __( 'Alphabetical', APP_TD ),
						'highest_rating' => __( 'Highest Rating', APP_TD ),
						'most_ratings' => __( 'Most Ratings', APP_TD ),
						'default' => __( 'Most Relevant (recommended)', APP_TD ),
						'newest' => __( 'Newest', APP_TD ),
						'rand' => __( 'Random', APP_TD ),
						'recently_reviewed' => __( 'Recently Reviewed', APP_TD ),
					),
					'desc' => __( 'When a search query excludes a location', APP_TD ),
					'tip' => __( 'The default search results order when a search is made without a location entered.', APP_TD ),
				),
				array(
					'title' => __( 'Location Sorting', APP_TD ),
					'name' => 'default_geo_search_sort',
					'type' => 'select',
					'values' => array(
						'title' => __( 'Alphabetical', APP_TD ),
						'distance' => __( 'Closest Distance (recommended)', APP_TD ),
						'highest_rating' => __( 'Highest Rating', APP_TD ),
						'most_ratings' => __( 'Most Ratings', APP_TD ),
						'default' => __( 'Most Relevant', APP_TD ),
						'newest' => __( 'Newest', APP_TD ),
						'rand' => __( 'Random', APP_TD ),
						'recently_reviewed' => __( 'Recently Reviewed', APP_TD ),
					),
					'desc' => __( 'When a search query includes a location', APP_TD ),
					'tip' => __( 'The default search results order when a search is made with a location entered.', APP_TD ),
				),
			)
		);

		$this->tab_sections['listings']['integration'] = array(
			'title' => __( 'ShareThis', APP_TD ),
			'desc' => sprintf( __( 'These options require the <a href="%1$s" target="_blank">ShareThis</a> plugin to be installed first.', APP_TD ) , 'http://wordpress.org/extend/plugins/share-this/' ),
			'fields' => array(
				array(
					'title' => __( 'Listings', APP_TD ),
					'type' => 'checkbox',
					'desc' => __( 'Show on the listings view', APP_TD ),
					'name' => 'listing_sharethis',
					'extra' => ( ! function_exists ( 'sharethis_button' ) ? array ( 'disabled' => 'disabled' ) : '' ),
					'tip' => '',
				),
				array(
					'title' => __( 'Blog Posts', APP_TD ),
					'type' => 'checkbox',
					'desc' => __( 'Show on blog posts', APP_TD ),
					'name' => 'blog_post_sharethis',
					'extra' => ( ! function_exists ( 'sharethis_button' ) ? array ( 'disabled' => 'disabled' ) : '' ),
					'tip' => '',
				),
			),

		);

		$this->tab_sections['advanced']['maintenance'] = array(
			'title' => __( 'Maintenance', APP_TD ),
			'fields' => array(
			array(
					'title' => __( 'Prune Listings', APP_TD ),
					'name' => '_blank',
					'type' => '',
					'desc' => sprintf( __( 'Prune  <a href="%s">expired listings</a>', APP_TD ), 'admin.php?page=app-settings&tab=advanced&prune=1' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'tip' => __( 'Manually prune any expired listings. This event will run only one time.', APP_TD ),
				),
			),
		);

		$this->tab_sections['advanced']['user'] = array(
			'title' => __( 'User', APP_TD ),
			'fields' => array(
			array(
					'title' => __( 'Login Page', APP_TD ),
					'type' => 'checkbox',
					'name' => 'wp_login',
					'desc' => __( 'Use the default WordPress login and register pages', APP_TD ),
					'tip' => __( "Left unchecked, you'll use the theme's custom styled pages.", APP_TD ),
				),
			),
		);

		$this->tab_sections['general']['category_menu_options'] = array(
			'title' => __( 'Categories Menu', APP_TD ),
			'fields' => $this->categories_options( 'categories_menu' )
		);

		$this->tab_sections['general']['category_menu_options']['fields'][] = array(
			'title'  => __( 'Show Only Sub-categories', APP_TD ),
			'type'   => 'checkbox',
			'name'   => array( 'categories_menu', 'only_sub_cats' ),
			'desc'   => __( 'Display only sub-categories on the taxonomy archives', APP_TD ),
			'tip'    => '',
		);

		$this->tab_sections['general']['category_dir_options'] = array(
			'title' => __( 'Categories Page', APP_TD ),
			'fields' => $this->categories_options( 'categories_dir' )
		);

		$this->tab_sections['security']['security'] = array(
			'title' => __( 'General', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'WP-Admin', APP_TD ),
					'desc' => sprintf( __( "Restrict access by <a target='_blank' href='%s'>specific role</a>.", APP_TD ), 'http://codex.wordpress.org/Roles_and_Capabilities' ),
					'type' => 'select',
					'name' => 'admin_security',
					'values' => array(
						'manage_options' => __( 'Admins Only', APP_TD ),
						'edit_others_posts' => __( 'Admins, Editors', APP_TD ),
						'publish_posts' => __( 'Admins, Editors, Authors', APP_TD ),
						'edit_posts' => __( 'Admins, Editors, Authors, Contributors', APP_TD ),
						'read' => __( 'All Access', APP_TD ),
						'disable' => __( 'Disable', APP_TD ),
					),
					'tip' => '',
				),
			),
		);

	}

private function categories_options( $prefix ) {
	return array(
		array(
			'title'  => __( 'Show Count', APP_TD ),
			'type'   => 'checkbox',
			'name'   => array( $prefix, 'count' ),
			'desc'   => __( 'Display the number of listings next to the category name', APP_TD ),
			'tip'    => '',
		),
		array(
			'title'  => __( 'Hide Empty', APP_TD ),
			'type'   => 'checkbox',
			'name'   => array( $prefix, 'hide_empty' ),
			'desc'   => __( "Don't show the category if it has no listings", APP_TD ),
			'tip'    => '',
		),
		array(
			'title'  => __( 'Category Depth', APP_TD ),
			'type'   => 'select',
			'name'   => array( $prefix, 'depth' ),
			'values' => array(
				'999'  => __( 'Show All', APP_TD ),
				'0'    => '0',
				'1'    => '1',
				'2'    => '2',
				'3'    => '3',
				'4'    => '4',
				'5'    => '5',
				'6'    => '6',
				'7'    => '7',
				'8'    => '8',
				'9'    => '9',
				'10'   => '10',
			),
			'desc'   => __( 'The number of levels deep the category should display', APP_TD ),
		),
		array(
			'title'  => __( 'Number of Sub-Categories', APP_TD ),
			'type'   => 'select',
			'name'   => array( $prefix, 'sub_num' ),
			'values' => array(
				'999'  => __( 'Show All', APP_TD ),
				'0'    => '0',
				'1'    => '1',
				'2'    => '2',
				'3'    => '3',
				'4'    => '4',
				'5'    => '5',
				'6'    => '6',
				'7'    => '7',
				'8'    => '8',
				'9'    => '9',
				'10'   => '10',
			),
			'desc'   => __( 'The number of sub-categories each parent category should display', APP_TD ),
		),
	);
}

	function init_integrated_options() {

		// display additional section on the permalinks page
		$this->permalink_sections();

	}

	function permalink_sections() {

		$option_page = 'permalink';
		$new_section = 'va_options'; // store permalink options on global 'va_options'

		$this->permalink_sections = array(
			'listings'  => __( 'Vantage Custom Post Type & Taxonomy URLs', APP_TD ),
			'actions'   => __( 'Vantage Actions URLs', APP_TD ),
			'dashboard' => __( 'Vantage Dashboard URLs', APP_TD )
		);

		if ( current_theme_supports( 'app-events' ) ) {
			$this->permalink_sections['events'] = __( 'Vantage Event Post Type & Taxonomy URLs', APP_TD );
			$this->permalink_sections['dashboard_events'] = __( 'Vantage Event Dashboard URLs', APP_TD );
		}

		$this->permalink_options['listings'] = array (
			'listing_permalink'     => __('Listing Base URL',APP_TD),
			'listing_cat_permalink' => __('Listing Category Base URL',APP_TD),
			'listing_tag_permalink' => __('Listing Tag Base URL',APP_TD),
		);

		if ( current_theme_supports( 'app-events' ) ) {
			$this->permalink_options['events'] = array (
				'event_permalink'     => __('Event Base URL',APP_TD),
				'event_cat_permalink' => __('Event Category Base URL',APP_TD),
				'event_tag_permalink' => __('Event Tag Base URL',APP_TD),
				'event_day_permalink' => __('Event Day Base URL',APP_TD),
			);
		}

		$this->permalink_options['actions'] = array (
			'edit_listing_permalink'     => __('Edit Listing Base URL',APP_TD),
			'renew_listing_permalink'    => __('Renew Listing Base URL',APP_TD),
			'claim_listing_permalink'    => __('Claim Listing Base URL',APP_TD),
			'purchase_listing_permalink' => __('Purchase Listing Base URL',APP_TD),
		);

		$this->permalink_options['dashboard'] = array (
			'dashboard_permalink'          => __('Dashboard Base URL',APP_TD),
			'dashboard_listings_permalink' => __('Dashboard Listing Base URL',APP_TD),
			'dashboard_claimed_permalink'  => __('Dashboard Claimed Listings Base URL',APP_TD),
			'dashboard_reviews_permalink'  => __('Dashboard Reviews Base URL',APP_TD),
			'dashboard_faves_permalink'    => __('Dashboard Favorites Base URL',APP_TD),
		);

		if ( current_theme_supports( 'app-events' ) ) {
			$this->permalink_options['dashboard_events'] = array (
				'dashboard_events_permalink'           => __('Dashboard Event Base URL',APP_TD),
				'dashboard_events_attending_permalink' => __('Dashboard Events Attending Base URL',APP_TD),
				'dashboard_event_comments_permalink'   => __('Dashboard Event Comments Base URL',APP_TD),
				'dashboard_event_favorites_permalink'  => __('Dashboard Event Favorites Base URL',APP_TD),
			);
		}

		register_setting(
			$option_page,
			$new_section,
			array( $this, 'permalink_options_validate')
		);

		foreach ( $this->permalink_sections as $section => $title ) {

			add_settings_section(
				$section,
				$title,
				'__return_false',
				$option_page
			);

			foreach ( $this->permalink_options[$section] as $id => $title ) {

				add_settings_field(
					$new_section.'_'.$id,
					$title,
					array( $this, 'permalink_section_add_option'), // callback to output the new options
					$option_page, // options page
					$section, // section
					array( 'id' => $id ) // callback args [ database option, option id ]
				);

			}

		}
	}

	function permalink_section_add_option( $option ) {
		global $va_options;

		echo scbForms::input( array(
			'type'  => 'text',
			'name'  => 'va_options['.$option['id'].']',
			'extra' => array( 'size' => 53 ),
			'value' => $va_options->{$option['id']},
		) );

	}

	// validate/sanitize permalinks
	function permalink_options_validate( $input ) {
		global $va_options;

		$error_html_id = '';

		foreach ( $this->permalink_sections as $section => $title ) {

			foreach ( $this->permalink_options[$section] as $key => $value) {

				if ( empty($input[$key]) ) {
					$error_html_id = $key;
					// set option to previous value
					$input[$key] = $va_options->$key;
				} else {
					if ( !is_array($input[$key]) ) $input[$key] = trim($input[$key]);
					$input[$key] = stripslashes_deep($input[$key]);
				}

			}
		}

		if( $error_html_id ) {

			add_settings_error(
				'va_options',
				$error_html_id,
				__('Vantage custom post type and taxonomy URLs cannot be empty. Empty options will default to previous value.', APP_TD),
				'error'
			);

		}

		return $input;

	}

	function before_rendering_field( $field ) {
		if ( in_array( $field['name'], array( 'listing_price', 'featured_home_price', 'featured_cat_price' ) ) )
			$field['desc'] = APP_Currencies::get_current_symbol();

		if ( 'color' == $field['name'] && apply_filters( 'va_disable_color_stylesheet', false ) ) {
			$field['extra'] = array( 'disabled', true );
			$field['desc'] = '(' . __( 'chosen by child theme', APP_TD ) . ')';
		}

		return $field;
	}

}

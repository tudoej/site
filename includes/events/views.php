<?php

class VA_Event_Taxonomy_404 extends APP_View {
	function condition() {
		return is_404() && get_query_var( VA_EVENT_DAY );
	}

	function template_redirect() {
		add_filter( 'body_class', array( $this, 'body_class' ), 0 );
	}

	function parse_query( $wp_query ) {
		global $wpdb, $va_options;

		$wp_query->is_404 = false;
		$wp_query->is_tax = true;
		$wp_query->is_archive = true;

	}

	function template_include( $template ) {
		if ( '404.php' == basename( $template ) ) {
			return locate_template( 'archive-event.php' );
		}

		return $template;
	}

	function title_parts( $parts ) {
		return array( va_format_event_day( va_get_search_query_var( VA_EVENT_DAY ) ) );
	}

	function breadcrumbs( $trail ) {
		$new_trail = array( $trail[0] );
		$new_trail[] = va_format_event_day( va_get_search_query_var( VA_EVENT_DAY ) );
		return $new_trail;
	}

	function body_class( $classes ) {
		$classes[] = 'va_event_day_404';
		return $classes;
	}
}

class VA_Event_Archive extends APP_View {

	function condition() {
		return is_post_type_archive( VA_EVENT_PTYPE ) && ! is_tax() && ! is_admin();
	}

	function parse_query( $wp_query ) {
		global $wpdb, $va_options;

		$wp_query->set( 'post_type', VA_EVENT_PTYPE );
		$wp_query->set( 'posts_per_page', $va_options->events_per_page );

		if ( '' == $wp_query->get( 'order' ) ) {
			$wp_query->set( 'order', 'asc' );
		}

		$orderby = $wp_query->get( 'orderby' );

		if ( empty( $orderby ) ) {
			if ( va_is_post_type_home( VA_EVENT_PTYPE ) ) {
				$orderby = $va_options->default_event_home_sort;
			} else {
				$orderby = $va_options->default_event_sort;
			}

			$wp_query->set( 'orderby', $orderby );
		}

		$wp_query->set( 'va_orderby', $orderby );

		switch ( $orderby ) {
			case 'popular':
				$wp_query->set( 'meta_key', VA_EVENT_ATTENDEES_META_KEY );
				$wp_query->set( 'orderby', 'meta_value' );
				$wp_query->set( 'order', 'desc' );
				break;
			case 'most_comments':
				$wp_query->set( 'orderby', 'comment_count' );
				$wp_query->set( 'order', 'desc' );
				break;
			case 'event_date':
				$wp_query->set( 'meta_key', VA_EVENT_DATE_META_KEY );
				$wp_query->set( 'orderby', 'meta_value' );
				$wp_query->set( 'order', 'asc' );
				break;
			case 'newest':
				$wp_query->set( 'order', 'desc' );
				break;
			case 'recently_discussed':
					$result_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT p.ID FROM $wpdb->posts p INNER JOIN $wpdb->comments c ON p.ID = c.`comment_post_ID` WHERE p.`post_type` = '%s' AND p.`post_status` = 'publish' ORDER BY c.`comment_ID` DESC LIMIT 100", VA_EVENT_PTYPE ) );
					$wp_query->set( 'orderby', 'post__in' );
					$wp_query->set( 'post__in', $result_ids );
				break;
			case 'rand':
				$wp_query->set( 'orderby', 'rand' );
				$wp_query->set( 'no_found_rows', true );
				break;
			case 'title':
				$wp_query->set( 'orderby', 'title' );
				break;
			case 'default':
			default:
				$wp_query->set( 'meta_key', 'featured-home' );
				$wp_query->set( 'orderby', 'meta_value_num' );
				$wp_query->set( 'order', 'desc' );
				$wp_query->set( 'va-featured', true );
				break;
		}

		$wp_query->is_archive = true;
		$this->parse_query_after( $wp_query );
	}

	function parse_query_after( $wp_query ) {
		$wp_query->set( 'va_is_post_type_home', true );
	}

	function template_include( $template ) {
		if ( 'index.php' == basename( $template ) ) {
			return locate_template( 'archive-event.php' );
		}

		return $template;
	}
}

class VA_Event_Taxonomy extends VA_Event_Archive {

	function condition() {
		return is_tax( VA_EVENT_CATEGORY ) || is_tax( VA_EVENT_TAG ) || is_tax( VA_EVENT_DAY );
	}

	function parse_query_after( $wp_query ) {
		$wp_query->set( 'va_is_post_type_home', false );

		$orderby = get_va_query_var( 'orderby', false );
		if ( $orderby == 'default' || $orderby == '' && ! is_tax( VA_EVENT_DAY ) ) {
			$wp_query->set( 'meta_key', 'featured-cat' );
		}
	}
}


class VA_Event_Create extends APP_View_Page {

	private $errors;

	function __construct() {
		parent::__construct( 'create-event.php', __( 'Create Event', APP_TD ) );

		add_action( 'wp_ajax_vantage_create_event_geocode', array( $this, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_vantage_create_event_geocode', array( $this, 'handle_ajax' ) );
	}

	public function handle_ajax() {
		if ( ! isset( $_GET['address'] ) && ( ! isset( $_GET['lat'] ) && ! isset( $_GET['lng'] ) ) ) {
			return;
		}

		if ( isset( $_GET['address'] ) ) {
			$api_response = va_geocode_address_api( $_GET['address'] );
		} else if ( isset( $_GET['lat'] ) ) {
			$api_response = va_geocode_lat_lng_api( $_GET['lat'], $_GET['lng'] );
		}

		if ( ! $api_response ) {
			die( 'error' );
		}

		die( json_encode( $api_response ) );

	}

	static function get_id() {
		return parent::_get_page_id( 'create-event.php' );
	}

	function template_include( $path ) {

		if ( ! is_user_logged_in() ) {
			if ( get_option( 'users_can_register' ) ) {
				$message = sprintf( __( 'You must first login or <a href="%s">register</a> to Create an Event.', APP_TD ), add_query_arg( array( 'redirect_to' => urlencode( va_get_event_create_url() ) ), appthemes_get_registration_url() ) );
			} else {
				$message = __( 'You must first login to Create an Event.', APP_TD );
			}
			appthemes_set_visitor_transient( 'login_notice', array( 'error', $message ), 300 );
			wp_redirect( wp_login_url( va_get_event_create_url() ) );
			exit();
		}

		appthemes_setup_checkout( 'create-event', get_permalink( self::get_id() ) );
		$step_found = appthemes_process_checkout();
		if ( ! $step_found ) {
			return locate_template( '404.php' );
		}

		add_filter( 'va_show_search_controls', array( $this, 'disable_va_search_controls' ) );

		return $path;
	}

	function disable_va_search_controls( $enabled ) {
		return false;
	}

	function enqueue_scripts() {
		wp_enqueue_style( 'jquery-ui-style' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'validate' );
		wp_enqueue_script( 'validate-lang' );

		wp_enqueue_script(
			'va-event-edit',
			get_template_directory_uri() . '/scripts/event-edit.js',
			array( 'validate', 'jquery-ui-sortable' ),
			VA_VERSION,
			true
		);

		wp_localize_script(
			'va-event-edit',
			'VA_i18n',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
				'clear'	  => __( 'Clear', APP_TD ),
				'processing' => __( 'Processing...', APP_TD ),
				'category_limit' => __( 'You have exceeded the category selection quantity limit.', APP_TD ),
				// form validation error messages
				'error_required' => __( 'This field is required.', APP_TD ),
				'error_category' => __( 'Please choose at least one category.', APP_TD ),
				'error_event_date' => __( 'Please choose at least one event date.', APP_TD ),
			)
		);

	}

	function template_redirect() {
		global $va_options;

		$this->check_failed_upload();

		appthemes_load_map_provider();

		add_filter( 'body_class', array( $this, 'body_class' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		do_action( strtolower( __CLASS__ . '_' . __FUNCTION__ ) );
	}

	function body_class( $classes ) {
		if ( ! is_active_sidebar( 'create-event' ) ) {
			$classes[] = 'no_sidebar_bg';
		}

		$classes[] = 'va_event_create';
		return $classes;
	}

	function check_failed_upload() {
		if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		$max_size = $this->convert_hr_to_bytes( ini_get( 'upload_max_filesize' ) );
		$max_size_string = $this->convert_bytes_to_hr( $max_size );

		if ( ! empty( $_SERVER['CONTENT_LENGTH'] ) && $_SERVER['CONTENT_LENGTH'] > $max_size ) {
			$errors = va_get_event_error_obj();
			$errors->add( 'file-too-large', sprintf( __( 'Uploaded file was too large, maximum file size is %s', APP_TD ), $max_size_string ) );
		}
	}

	function convert_hr_to_bytes( $size ) {
		$size = strtolower( $size );
		$bytes = (int) $size;

		if ( strpos( $size, 'k' ) !== false ) {
			$bytes = intval( $size ) * 1024;
		} elseif ( strpos( $size, 'm' ) !== false ) {
			$bytes = intval( $size ) * 1024 * 1024;
		} elseif ( strpos( $size, 'g' ) !== false ) {
			$bytes = intval( $size ) * 1024 * 1024 * 1024;
		}

		return $bytes;
	}

	function convert_bytes_to_hr( $bytes ) {
		$units = array( 0 => 'B', 1 => 'kB', 2 => 'MB', 3 => 'GB' );
		$log = log( $bytes, 1024 );
		$power = (int) $log;
		$size = pow( 1024, $log - $power );
		return $size . $units[ $power ];
	}

}

function va_get_event_create_url() {
	return get_permalink( VA_Event_Create::get_id() );
}

class VA_Event_Edit extends VA_Event_Create {

	function init() {
		global $wp;
		$options = va_events_get_options();

		$wp->add_query_var( 'event_edit' );

		$event_permalink = $options->event_permalink;
		$permalink = $options->edit_event_permalink;

		appthemes_add_rewrite_rule( $event_permalink. '/' . $permalink . '/(\d+)/?$', array(
			'event_edit' => '$matches[1]'
		) );
	}

	function condition() {
		return (bool) get_query_var( 'event_edit' );
	}

	function parse_query( $wp_query ) {
		$event_id = $wp_query->get( 'event_edit' );

		if ( ! current_user_can( 'edit_post', $event_id ) ) {
			wp_die( __( 'You do not have permission to edit that event.', APP_TD ) );
		}

		$wp_query->is_home = false;

		$wp_query->query_vars = array_merge( $wp_query->query_vars, array(
			'post_type' => VA_EVENT_PTYPE,
			'post_status' => 'any',
			'post__in' => array( $event_id )
		) );
	}

	function the_posts( $posts, $wp_query ) {

		if ( ! empty( $posts ) ) {
			$wp_query->queried_object = reset( $posts );
			$wp_query->queried_object_id = $wp_query->queried_object->ID;
		}

		return $posts;
	}

	function template_include( $path ) {
		appthemes_setup_checkout( 'edit-event', va_get_event_edit_url( get_queried_object_id() ) );
		$found = appthemes_process_checkout( 'edit-event' );
		if ( ! $found ) {
			return locate_template( '404.php' );
		}

		return locate_template( 'edit-event.php' );
	}

	function title_parts( $parts ) {
		return array( sprintf( __( 'Edit "%s"', APP_TD ), get_the_title( get_queried_object_id() ) ) );
	}

	function body_class( $classes ) {
		if ( ! is_active_sidebar( 'edit-event' ) ) {
			$classes[] = 'no_sidebar_bg';
		}

		$classes[] = 'va_event_edit';
		return $classes;
	}
}


class VA_Event_Purchase extends APP_View {

	function init() {
		global $wp;
		$options = va_events_get_options();

		$wp->add_query_var( 'event_purchase' );

		$event_permalink = $options->event_permalink;
		$permalink = $options->purchase_event_permalink;

		appthemes_add_rewrite_rule( $event_permalink . '/' . $permalink . '/(\d+)/?$', array(
			'event_purchase' => '$matches[1]'
		) );
	}

	function condition() {
		return (bool) get_query_var( 'event_purchase' );
	}

	function parse_query( $wp_query ) {
		$event_id = $wp_query->get( 'event_purchase' );

		if ( ! current_user_can( 'edit_post', $event_id ) ) {
			wp_die( __( 'You do not have permission to purchase that event.', APP_TD ) );
		}

		$wp_query->is_home = false;
		$wp_query->query_vars = array_merge( $wp_query->query_vars, array(
			'post_type' => VA_EVENT_PTYPE,
			'post_status' => 'any',
			'post__in' => array( $event_id )
		) );

	}

	function the_posts( $posts, $wp_query ) {
		if ( ! empty( $posts ) ) {
			$wp_query->queried_object = reset( $posts );
			$wp_query->queried_object_id = $wp_query->queried_object->ID;
		}

		return $posts;
	}

	function template_include( $path ) {

		appthemes_setup_checkout( 'upgrade-event', va_get_event_purchase_url( get_queried_object_id() ) );
		$found = appthemes_process_checkout();
		if ( ! $found ) {
			return locate_template( '404.php' );
		}

		return locate_template( 'purchase-event.php' );
	}

	function title_parts( $parts ) {
		return array( sprintf( __( 'Purchase "%s"', APP_TD ), get_the_title( get_queried_object_id() ) ) );
	}

}

class VA_Event_Single extends APP_View {

	function condition() {
		return is_singular( VA_EVENT_PTYPE );
	}

	function template_redirect() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	function enqueue_scripts() {

		wp_enqueue_script( 'comment-reply' );
		wp_enqueue_script( 'colorbox' );
		wp_enqueue_style( 'colorbox' );

		wp_enqueue_script( 'validate' );
		wp_enqueue_script( 'validate-lang' );

	}

	// Show parent categories instead of listing archive
	function breadcrumbs( $trail ) {
		$cat = get_the_event_categories( get_queried_object_id() );

		if ( ! $cat ) {
			return $trail;
		}

		$cat = reset( $cat );
		$cat = (int) $cat->term_id;
		$chain = array_reverse( get_ancestors( $cat, VA_EVENT_CATEGORY ) );
		$chain[] = $cat;

		$new_trail = array( $trail[0], $trail[1] );

		foreach ( $chain as $cat ) {
			$cat_obj = get_term( $cat, VA_EVENT_CATEGORY );
			$new_trail[] = html_link( get_term_link( $cat_obj ), $cat_obj->name );
		}

		$new_trail[] = array_pop( $trail );

		return $new_trail;
	}

	function notices() {
		$status = get_post_status( get_queried_object() );

		if ( isset( $_GET['completed'] ) ) {
			if ( $status == 'pending' ) {
				appthemes_display_notice( 'success-pending', __( 'Your order has been successfully processed. It is currently pending and must be approved by an administrator.', APP_TD ) );
			} else {
				appthemes_display_notice( 'success', __( 'Your order has been successfully completed.', APP_TD ) );
			}

		} elseif ( isset( $_GET['updated'] ) ) {
			appthemes_display_notice( 'success', __( 'The event has been successfully updated.', APP_TD ) );

		} elseif ( $status == 'pending' ) {
			appthemes_display_notice( 'success-pending', __( 'This event is currently pending and must be approved by an administrator.', APP_TD ) );

		} elseif ( $status == 'draft' ) {
			appthemes_display_notice( 'success-pending', __( 'This event is currently awaiting payment and/or payment processing.', APP_TD ) );
		}
	}
}


class VA_Event_Search extends APP_View {

	function init() {
		global $wp;

		$wp->add_query_var( 'ls' );
		$wp->add_query_var( 'st' );
	}

	function condition() {
		return ( isset( $_GET['ls'] ) || get_query_var( 'location' ) ) && ( isset( $_GET['st'] ) && $_GET['st'] == 'event' );
	}

	function parse_query( $wp_query ) {
		global $va_options, $wpdb;

		$wp_query->set( 'ls', trim( get_query_var( 'ls' ) ) );
		$wp_query->set( 's', get_query_var( 'ls' ) );
		$wp_query->set( 'post_type', VA_EVENT_PTYPE );
		$wp_query->set( 'posts_per_page', $va_options->events_per_page );

		if ( '' == $wp_query->get( 'order' ) ) {
			$wp_query->set( 'order', 'asc' );
		}

		$orderby = $wp_query->get( 'orderby' );

		if ( empty( $orderby ) ) {
			$location = trim( $wp_query->get( 'location' ) );

			if ( ! empty( $location ) ) {
				$orderby = $va_options->default_event_geo_search_sort;
			} else {
				$orderby = $va_options->default_event_search_sort;
			}

			$wp_query->set( 'orderby', $orderby );
		}

		$wp_query->set( 'va_orderby', $orderby );

		switch ( $orderby ) {
			case 'popular':
				$wp_query->set( 'meta_key', VA_EVENT_ATTENDEES_META_KEY );
				$wp_query->set( 'orderby', 'meta_value' );
				$wp_query->set( 'order', 'desc' );
				break;
			case 'most_comments':
				$wp_query->set( 'orderby', 'comment_count' );
				$wp_query->set( 'order', 'desc' );
				break;
			case 'event_date':
				$wp_query->set( 'meta_key', VA_EVENT_DATE_META_KEY );
				$wp_query->set( 'orderby', 'meta_value' );
				$wp_query->set( 'order', 'asc' );
				break;
			case 'newest':
				$wp_query->set( 'order', 'desc' );
				break;
			case 'recently_discussed':
					$result_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT p.ID FROM $wpdb->posts p INNER JOIN $wpdb->comments c ON p.ID = c.`comment_post_ID` WHERE p.`post_type` = '%s' AND p.`post_status` = 'publish' ORDER BY c.`comment_ID` DESC LIMIT 100", VA_EVENT_PTYPE ) );
					$wp_query->set( 'orderby', 'post__in' );
					$wp_query->set( 'post__in', $result_ids );
				break;
			case 'rand':
				$wp_query->set( 'orderby', 'rand' );
				$wp_query->set( 'no_found_rows', true );
				break;
			case 'title':
				$wp_query->set( 'orderby', 'title' );
				break;
			case 'distance':
				break;
			case 'default':
			default:
				$wp_query->set( 'meta_key', VA_ITEM_FEATURED );
				$wp_query->set( 'orderby', 'meta_value_num' );
				$wp_query->set( 'order', 'desc' );
				$wp_query->set( 'va-featured', true );
				break;
		}

		if ( isset( $_GET['event_cat'] ) ) {
			$wp_query->set( 'tax_query', array(
				array(
					'taxonomy' => VA_EVENT_CATEGORY,
					'terms' => $_GET['event_cat']
				)
			) );
		}

		$wp_query->is_home = false;
		$wp_query->is_archive = true;
		$wp_query->is_search = true;
	}

	function posts_search( $sql, $wp_query ) {
		global $wpdb;

		$q = $wp_query->query_vars;
		$search = '';

		if ( empty( $q['search_terms'] ) ) {
			return $sql;
		}

		// BEGIN COPY FROM WP_Query
		$n = ! empty( $q['exact'] ) ? '' : '%';
		$searchand = '';
		foreach ( (array) $q['search_terms'] as $term ) {
			$term = ( method_exists( $wpdb, 'esc_like' ) ) ? $wpdb->esc_like( $term ) : like_escape( $term );
			$term = esc_sql( $term );

			if ( va_search_index_enabled() ) {
				// AppThemes Search Index
				$search .= "{$searchand}(
					$wpdb->posts.post_content_filtered LIKE '{$n}{$term}{$n}'
				)";
			} else {
				// ADDED tter.name
				$search .= "{$searchand}(
					($wpdb->posts.post_title LIKE '{$n}{$term}{$n}') OR
					($wpdb->posts.post_content LIKE '{$n}{$term}{$n}') OR
					(tter.name LIKE '{$n}{$term}{$n}')
				)";
			}

			$searchand = ' AND ';
		}

		if ( ! empty( $search ) ) {
			$search = " AND ({$search}) ";
			if ( ! is_user_logged_in() ) {
				$search .= " AND ($wpdb->posts.post_password = '') ";
			}
		}
		// END COPY

		return $search;
	}

	function posts_clauses( $clauses ) {
		global $wpdb;

		if ( ! va_search_index_enabled() ) {
			$taxonomies = scbUtil::array_to_sql( array( VA_EVENT_CATEGORY, VA_EVENT_TAG ) );

			$clauses['join'] .= "
				INNER JOIN $wpdb->term_relationships AS trel
				ON ($wpdb->posts.ID = trel.object_id)
				INNER JOIN $wpdb->term_taxonomy AS ttax
				ON (ttax.taxonomy IN ($taxonomies) AND trel.term_taxonomy_id = ttax.term_taxonomy_id)
				INNER JOIN $wpdb->terms AS tter ON (ttax.term_id = tter.term_id)
				";
		}

		$clauses['distinct'] = "DISTINCT";

		return $clauses;
	}

	function template_redirect() {

		wp_enqueue_script(
			'jquery-nouislider',
			get_template_directory_uri() . '/scripts/jquery.nouislider.all.min.js',
			array( 'jquery' ),
			'7.0.2',
			true
		);
		wp_enqueue_style(
			'jquery-nouislider-style',
			get_template_directory_uri() . '/styles/jquery.nouislider.css',
			false,
			'7.0.2'
		);
	}

}

class VA_Event_Categories extends APP_View_Page {

	function __construct() {
		parent::__construct( 'categories-list-event.php', __( 'Event Categories', APP_TD ) );

		// Replace any children the "Categories" menu item might have with the category dropdown
		add_filter( 'wp_nav_menu_objects', array( $this, 'disable_children' ), 10, 2 );
		add_filter( 'walker_nav_menu_start_el', array( $this, 'insert_dropdown' ), 10, 4 );
	}

	static function get_id() {
		return parent::_get_page_id( 'categories-list-event.php' );
	}

	function disable_children( $items, $args ) {
		foreach ( $items as $key => $item ) {
			if ( $item->object_id == self::get_id() ) {
				$item->current_item_ancestor = false;
				$item->current_item_parent = false;
				$menu_id = $item->ID;
			}
		}

		if ( isset( $menu_id ) ) {
			foreach ( $items as $key => $item ) {
				if ( $item->menu_item_parent == $menu_id ) {
					unset( $items[ $key ] );
				}
			}
		}

		return $items;
	}

	function insert_dropdown( $item_output, $item, $depth, $args ) {
		if ( $item->object_id == self::get_id() && $item->object == 'page' ) {
			$item_output .= '<div class="adv_categories" id="adv_categories_event">' . va_cat_menu_drop_down( 'menu', VA_EVENT_CATEGORY ) . '</div>';
		}
		return $item_output;
	}

}

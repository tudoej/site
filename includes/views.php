<?php

class VA_Home_Archive extends APP_View_Page {

	function __construct() {
		parent::__construct( 'va-home.php', __( 'Home', APP_TD ) );
	}

	function condition() {
		global $wp_query;

		$page_id = (int) get_query_var( 'page_id' );

		return $page_id && $page_id == self::_get_page_id( 'va-home.php' ); // for 'page_on_front'
	}

	function parse_query( $wp_query ) {
		$wp_query->is_home = true;
	}

	static function get_id() {
		return parent::_get_page_id( 'va-home.php' );
	}
}

function va_is_home() {
	$va_home_archive = appthemes_get_instance( 'VA_Home_Archive' );
	return (bool) $va_home_archive->condition();
}

function va_is_post_type_home( $post_type = '' ) {
	if ( va_is_home() ) {
		return true;
	} else if ( is_post_type_archive( $post_type ) && ! is_tax() ) {
		return true;
	}
	return false;
}

class VA_Blog_Archive extends APP_View_Page {

	function __construct() {
		parent::__construct( 'index.php', __( 'Blog', APP_TD ) );

		add_action( 'appthemes_before_blog_post_content', array( $this, 'blog_featured_image' ) );
	}

	static function get_id() {
		return parent::_get_page_id( 'index.php' );
	}

	public function blog_featured_image() {
		if ( ! is_singular() ) {
			if ( has_post_thumbnail() ) {
				echo html('a', array(
					'href' => get_permalink(),
					'title' => the_title_attribute( array( 'echo' => 0 ) ),
					), get_the_post_thumbnail( get_the_ID(), array( 420, 150 ), array( 'class' => 'alignleft' ) ) );
			}
		}
	}
}

class VA_Listing_Archive extends APP_View {

	function condition() {
		return is_post_type_archive( VA_LISTING_PTYPE ) && ! is_tax() && ! is_admin();
	}

	function parse_query( $wp_query ) {
		global $wpdb, $va_options;

		$wp_query->set( 'post_type', VA_LISTING_PTYPE );
		$wp_query->set( 'posts_per_page', $va_options->listings_per_page );

		if ( '' == $wp_query->get( 'order' ) ) {
			$wp_query->set( 'order', 'asc' );
		}

		$orderby = $wp_query->get( 'orderby' );

		if ( empty( $orderby ) ) {
			if ( va_is_post_type_home( VA_LISTING_PTYPE ) ) {
				$orderby = $va_options->default_listing_home_sort;
			} else {
				$orderby = $va_options->default_listing_sort;
			}

			$wp_query->set( 'orderby', $orderby );
		}

		$wp_query->set( 'va_orderby', $orderby );

		switch ( $orderby ) {
			case 'highest_rating':
				$wp_query->set( 'meta_key', 'rating_avg' );
				$wp_query->set( 'orderby', 'meta_value, comment_count' );
				$wp_query->set( 'order', 'desc' );
				break;
			case 'most_ratings':
				$wp_query->set( 'orderby', 'comment_count' );
				$wp_query->set( 'order', 'desc' );
				break;
			case 'newest':
				$wp_query->set( 'order', 'desc' );
				break;
			case 'recently_reviewed':
					$result_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT p.ID FROM $wpdb->posts p INNER JOIN $wpdb->comments c ON p.ID = c.`comment_post_ID` WHERE p.`post_type` = '%s' AND p.`post_status` = 'publish' ORDER BY c.`comment_ID` DESC LIMIT 100", VA_LISTING_PTYPE ) );
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
			return locate_template( 'archive-listing.php' );
		}

		return $template;
	}

	function breadcrumbs( $trail ) {
		if ( count( $trail ) > 2 ) {
			unset( $trail[1] );
		}

		return $trail;
	}

}


class VA_Listing_Categories extends APP_View_Page {

	function __construct() {
		parent::__construct( 'categories-list-listing.php', __( 'Categories', APP_TD ) );

		// Replace any children the "Categories" menu item might have with the category dropdown
		add_filter( 'wp_nav_menu_objects', array( $this, 'disable_children' ), 10, 2 );
		add_filter( 'walker_nav_menu_start_el', array( $this, 'insert_dropdown' ), 10, 4 );
	}

	static function get_id() {
		return parent::_get_page_id( 'categories-list-listing.php' );
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
			$item_output .= '<div class="adv_categories" id="adv_categories_listing">' . va_cat_menu_drop_down( 'menu', VA_LISTING_CATEGORY ) . '</div>';
		}
		return $item_output;
	}

}

class VA_Listing_Taxonomy extends VA_Listing_Archive {

	function condition() {
		return is_tax( VA_LISTING_CATEGORY ) || is_tax( VA_LISTING_TAG );
	}

	function parse_query_after( $wp_query ) {
		$wp_query->set( 'va_is_post_type_home', false );

		$orderby = $wp_query->get( 'va_orderby' );
		if ( $orderby == 'default' || empty( $orderby ) ) {
			$wp_query->set( 'meta_key', 'featured-cat' );
		}
	}

}

class VA_Listing_Search extends APP_View {

	function init() {
		global $wp;

		$wp->add_query_var( 'ls' );
		$wp->add_query_var( 'st' );
	}

	function condition() {
		return ( isset( $_GET['ls'] ) || get_query_var( 'location' ) ) && ( ( isset( $_GET['st'] ) && $_GET['st'] == 'listing' ) || ! isset( $_GET['st'] ) );
	}

	function parse_query( $wp_query ) {
		global $va_options, $wpdb;

		$wp_query->set( 'ls', trim( get_query_var( 'ls' ) ) );
		$wp_query->set( 's', get_query_var( 'ls' ) );
		$wp_query->set( 'post_type', VA_LISTING_PTYPE );
		$wp_query->set( 'posts_per_page', $va_options->listings_per_page );

		if ( '' == $wp_query->get( 'order' ) ) {
			$wp_query->set( 'order', 'asc' );
		}

		$orderby = $wp_query->get( 'orderby' );

		if ( empty( $orderby ) ) {
			$location = trim( $wp_query->get( 'location' ) );

			if ( ! empty( $location ) ) {
				$orderby = $va_options->default_geo_search_sort;
			} else {
				$orderby = $va_options->default_search_sort;
			}

			$wp_query->set( 'orderby', $orderby );
		}

		$wp_query->set( 'va_orderby', $orderby );

		switch ( $orderby ) {
			case 'highest_rating':
				$wp_query->set( 'meta_key', 'rating_avg' );
				$wp_query->set( 'orderby', 'meta_value, comment_count' );
				$wp_query->set( 'order', 'desc' );
				break;
			case 'most_ratings':
				$wp_query->set( 'orderby', 'comment_count' );
				$wp_query->set( 'order', 'desc' );
				break;
			case 'newest':
				$wp_query->set( 'order', 'desc' );
				break;
			case 'recently_reviewed':
					$result_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT p.ID FROM $wpdb->posts p INNER JOIN $wpdb->comments c ON p.ID = c.`comment_post_ID` WHERE p.`post_type` = '%s' AND p.`post_status` = 'publish' ORDER BY c.`comment_ID` DESC LIMIT 100", VA_LISTING_PTYPE ) );
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

		if ( isset( $_GET['listing_cat'] ) ) {
			$wp_query->set( 'tax_query', array(
				array(
					'taxonomy' => VA_LISTING_CATEGORY,
					'terms' => $_GET['listing_cat']
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
			if ( !is_user_logged_in() )
				$search .= " AND ($wpdb->posts.post_password = '') ";
		}
		// END COPY

		return $search;
	}

	function posts_clauses( $clauses ) {
		global $wpdb;

		if ( ! va_search_index_enabled() ) {
			$taxonomies = scbUtil::array_to_sql( array( VA_LISTING_CATEGORY, VA_LISTING_TAG ) );

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


class VA_Listing_Dashboard extends APP_View {

	private $error;

	function init() {
		$this->handle_form();
		$this->add_rewrite_rules();
	}

	private function handle_form() {
		if ( ! isset( $_POST['action'] ) || 'dashboard-reviews' != $_POST['action'] ) {
			return;
		}

		if ( empty( $_POST ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'va-dashboard-reviews' ) ) {
			//nonce did not verify
			$this->error = __( 'There was an error. Please try again.', APP_TD );
		} else {
			// process form data
			// nonce did verify
			$review = get_comment( $_POST['review_id'] );
			$user_id = get_current_user_id();
			if ( $user_id == $review->user_id ) {
				va_delete_review( $_POST['review_id'] );
				wp_redirect( './?deleted=true' );
				exit();
			} else {
				$this->error = __( 'Cannot delete review, it belongs to another user.', APP_TD );
			}
		}
	}

	private function add_rewrite_rules() {
		global $wp, $va_options;

		// User dashboard
		$wp->add_query_var( 'dashboard' );
		$wp->add_query_var( 'dashboard_author' );

		$dashboard_permalink = $va_options->dashboard_permalink;
		$dashboard_listings_permalink = $va_options->dashboard_listings_permalink;

		$all_permalinks = array(
			$va_options->dashboard_listings_permalink,
			$va_options->dashboard_reviews_permalink,
			$va_options->dashboard_faves_permalink,
			$va_options->dashboard_claimed_permalink,
		);

		$all_permalinks = apply_filters( 'va_dashboard_all_permalinks', $all_permalinks );

		$dashboard_all_permalinks = implode( '?|', $all_permalinks );

		// dashboard default permalink

		appthemes_add_rewrite_rule( $dashboard_permalink . '/?$', array(
			'dashboard' => $dashboard_listings_permalink,
			'dashboard_author' => 'self'
		) );

		appthemes_add_rewrite_rule( $dashboard_permalink . '/page/([0-9]+)/?$', array(
			'dashboard' => $dashboard_listings_permalink,
			'dashboard_author' => 'self',
			'paged' => '$matches[1]',
		) );

		// dashboard author (self) permalinks

		appthemes_add_rewrite_rule( $dashboard_permalink . '/(' . $dashboard_all_permalinks . '?)/?$', array(
			'dashboard' => '$matches[1]',
			'dashboard_author' => 'self'
		) );
		appthemes_add_rewrite_rule( $dashboard_permalink . '/(' . $dashboard_all_permalinks . '?)/?page/([0-9]+)/?$', array(
			'dashboard' => '$matches[1]',
			'dashboard_author' => 'self',
			'paged' => '$matches[2]',
		) );

		// dashboard author permalinks

		appthemes_add_rewrite_rule( $dashboard_permalink . '/(' . $dashboard_all_permalinks . '?)/(.*?)/page/([0-9]+)/?$', array(
			'dashboard' => '$matches[1]',
			'dashboard_author' => '$matches[2]',
			'paged' => '$matches[3]',
		) );
		appthemes_add_rewrite_rule( $dashboard_permalink . '/(' . $dashboard_all_permalinks . '?)/(.*?)/?$', array(
			'dashboard' => '$matches[1]',
			'dashboard_author' => '$matches[2]'
		) );

		do_action( strtolower( __CLASS__ . '_' . __FUNCTION__ ) );

	}

	function condition() {
		return (bool) get_query_var( 'dashboard' );
	}

	function template_redirect() {
		global $wp_query;
		$type = va_get_dashboard_type();
		$is_own = va_is_own_dashboard();

		if ( ( 'favorites' === $type || 'event-favorites' === $type ) && ! $is_own ) {
			wp_redirect( home_url() );
		}
		$wp_query->is_home = false;
		$wp_query->is_archive = true;
		$wp_query->is_404 = false;

		if ( get_query_var( 'dashboard_author' ) == 'self' ) {
			appthemes_auth_redirect_login();
		}

		add_filter( 'body_class', array( $this, 'body_class' ), 0 );
		add_filter( 'wp_title', array( $this, 'title' ), 0 );
	}

	function template_include( $path ) {
		$dashboard_author = va_get_dashboard_author();

		if ( ! $dashboard_author ) {
			return locate_template( '404.php' );
		}

		return locate_template( 'dashboard-setup.php' );
	}

	function body_class($classes) {
		$classes[] = 'va-dashboard';
		$classes[] = 'va-dashboard-' . va_get_dashboard_type();
		if ( va_is_own_dashboard() ) {
			$classes[] = 'va-dashboard-self';
		}

		return $classes;
	}

	function title() {
		return __( 'Dashboard', APP_TD );
	}

	function breadcrumbs( $trail ) {
		array_pop( $trail );
		$trail[] = $this->title();

		return $trail;
	}

	function notices() {
		if ( ! empty( $this->error ) ) {
			appthemes_display_notice( 'success-pending', $this->error );
		} elseif ( isset( $_GET['deleted'] ) ) {
			appthemes_display_notice( 'success', __( 'Review deleted.', APP_TD ) );
		}
	}
}

class VA_Listing_Create extends APP_View_Page {

	private $errors;

	function __construct() {
		parent::__construct( 'create-listing.php', __( 'Create Listing', APP_TD ) );

		add_action( 'wp_ajax_vantage_create_listing_geocode', array( $this, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_vantage_create_listing_geocode', array( $this, 'handle_ajax' ) );
	}

	public function handle_ajax() {
		if ( ! isset( $_GET['address'] ) && ( ! isset( $_GET['lat'] ) && ! isset( $_GET['lng'] ) ) ) {
			return;
		}

		if ( isset( $_GET['address'] ) ) {
			$api_response = va_geocode_address_api( $_GET['address'] );
		} elseif ( isset( $_GET['lat'] ) ) {
			$api_response = va_geocode_lat_lng_api( $_GET['lat'], $_GET['lng'] );
		}

		if ( ! $api_response ) {
			die( "error" );
		}

		die( json_encode( $api_response ) );

	}

	static function get_id() {
		return parent::_get_page_id( 'create-listing.php' );
	}

	function template_include( $path ) {

		if ( ! is_user_logged_in() ) {
			if ( get_option( 'users_can_register' ) ) {
				$message = sprintf( __( 'You must first login or <a href="%s">register</a> to Create a Listing.', APP_TD ), add_query_arg( array( 'redirect_to' => urlencode( va_get_listing_create_url() ) ), appthemes_get_registration_url() ) );
			} else {
				$message = __( 'You must first login to Create a Listing.', APP_TD );
			}
			appthemes_set_visitor_transient( 'login_notice', array( 'error', $message ), 300 );
			wp_redirect( wp_login_url( va_get_listing_create_url() ) );
			exit();
		}

		appthemes_setup_checkout( 'create-listing', get_permalink( self::get_id() ) );
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
		wp_enqueue_script( 'validate' );
		wp_enqueue_script( 'validate-lang' );

		wp_enqueue_script(
			'va-listing-edit',
			get_template_directory_uri() . '/scripts/listing-edit.js',
			array( 'validate', 'jquery-ui-sortable' ),
			VA_VERSION,
			true
		);

		wp_localize_script(
			'va-listing-edit',
			'VA_i18n',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
				'clear'	  => __( 'Clear', APP_TD ),
				'processing' => __( 'Processing...', APP_TD ),
				'category_limit' => __( 'You have exceeded the category selection quantity limit.', APP_TD ),
				// form validation error messages
				'error_required' => __( 'This field is required.', APP_TD ),
				'error_category' => __( 'Please choose at least one category.', APP_TD ),
			)
		);

	}

	function template_redirect() {

		$this->check_failed_upload();

		appthemes_load_map_provider();

		add_filter( 'body_class', array( $this, 'body_class' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		do_action( strtolower( __CLASS__ . '_' . __FUNCTION__ ) );
	}

	function body_class( $classes ) {
		if ( ! is_active_sidebar( 'create-listing' ) ) {
			$classes[] = 'no_sidebar_bg';
		}

		$classes[] = 'va_listing_create';
		return $classes;
	}

	function check_failed_upload() {
		if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		$max_size = $this->convert_hr_to_bytes( ini_get( 'upload_max_filesize' ) );
		$max_size_string = $this->convert_bytes_to_hr( $max_size );

		if ( !empty( $_SERVER['CONTENT_LENGTH'] ) && $_SERVER['CONTENT_LENGTH'] > $max_size ) {
			$errors = va_get_listing_error_obj();
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

class VA_Listing_Edit extends VA_Listing_Create {

	function init() {
		global $wp, $va_options;

		$wp->add_query_var( 'listing_edit' );

		$listing_permalink = $va_options->listing_permalink;
		$permalink = $va_options->edit_listing_permalink;

		appthemes_add_rewrite_rule( $listing_permalink. '/' . $permalink . '/(\d+)/?$', array(
			'listing_edit' => '$matches[1]'
		) );
	}

	function condition() {
		return (bool) get_query_var( 'listing_edit' );
	}

	function parse_query( $wp_query ) {
		$listing_id = $wp_query->get( 'listing_edit' );

		if ( ! current_user_can( 'edit_post', $listing_id ) ) {
			wp_die( __( 'You do not have permission to edit that listing.', APP_TD ) );
		}

		$wp_query->is_home = false;

		$wp_query->query_vars = array_merge( $wp_query->query_vars, array(
			'post_type' => VA_LISTING_PTYPE,
			'post_status' => 'any',
			'post__in' => array( $listing_id )
		) );

		if ( 'expired' ==  get_post_status( $listing_id ) ) {
			wp_redirect( va_get_listing_renew_url( $listing_id ) );
			exit;
		}
	}

	function the_posts( $posts, $wp_query ) {
		if ( ! empty( $posts ) ) {
			$wp_query->queried_object = reset( $posts );
			$wp_query->queried_object_id = $wp_query->queried_object->ID;
		}

		return $posts;
	}

	function template_include( $path ) {

		appthemes_setup_checkout( 'edit-listing', va_get_listing_edit_url( get_queried_object_id() ) );
		$found = appthemes_process_checkout( 'edit-listing' );
		if ( ! $found ) {
			return locate_template( '404.php' );
		}

		return locate_template( 'edit-listing.php' );
	}

	function title_parts( $parts ) {
		return array( sprintf( __( 'Edit "%s"', APP_TD ), get_the_title( get_queried_object_id() ) ) );
	}

	function body_class($classes) {
		if ( ! is_active_sidebar( 'edit-listing' ) ) {
			$classes[] = 'no_sidebar_bg';
		}

		$classes[] = 'va_listing_edit';
		return $classes;
	}
}


class VA_Listing_Renew extends VA_Listing_Edit {

	function init() {
		global $wp, $va_options;

		$wp->add_query_var( 'listing_renew' );

		$listing_permalink = $va_options->listing_permalink;
		$permalink = $va_options->renew_listing_permalink;

		appthemes_add_rewrite_rule( $listing_permalink. '/' . $permalink . '/(\d+)/?$', array(
			'listing_renew' => '$matches[1]'
		) );

		add_action( 'appthemes_transaction_completed', array( $this, 'handle_renew_transaction_completed' ) );

	}

	function condition() {
		return (bool) get_query_var( 'listing_renew' );
	}

	function parse_query( $wp_query ) {
		$listing_id = $wp_query->get( 'listing_renew' );

		if ( ! current_user_can( 'edit_post', $listing_id ) ) {
			if ( ! is_user_logged_in() ) {
				wp_die( sprintf( __( 'Please <a href="%s">login</a> to renew that listing.', APP_TD ), wp_login_url( va_get_listing_renew_url( $listing_id ) ) ) );
			} else {
				wp_die( __( 'You do not have permission to renew that listing.', APP_TD ) );
			}
		}

		$wp_query->is_home = false;

		$wp_query->query_vars = array_merge( $wp_query->query_vars, array(
			'post_type' => VA_LISTING_PTYPE,
			'post_status' => 'any',
			'post__in' => array( $listing_id )
		) );

		if ( 'expired' !=  get_post_status( $listing_id ) ) {
			wp_redirect( va_get_listing_edit_url( $listing_id ) );
			exit;
		}
	}

	function the_posts( $posts, $wp_query ) {
		if ( ! empty( $posts ) ) {
			$wp_query->queried_object = reset( $posts );
			$wp_query->queried_object_id = $wp_query->queried_object->ID;
		}

		return $posts;
	}

	function template_include( $path ) {

		appthemes_setup_checkout( 'renew-listing', va_get_listing_renew_url( get_queried_object_id() ) );
		$found = appthemes_process_checkout( 'renew-listing' );
		if ( ! $found ) {
			return locate_template( '404.php' );
		}

		return locate_template( 'edit-listing.php' );
	}

	function template_redirect() {

		wp_enqueue_script( 'validate' );
		wp_enqueue_script( 'validate-lang' );

		wp_enqueue_script(
			'va-listing-categories',
			get_template_directory_uri() . '/scripts/listing-categories.js',
			array( 'validate', 'jquery-ui-sortable' ),
			VA_VERSION,
			true
		);

		wp_localize_script(
			'va-listing-categories',
			'VA_i18n',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
				'clear'	  => __( 'Clear', APP_TD ),
				'category_limit' => __( 'You have exceeded the category selection quantity limit.', APP_TD )
			)
		);

		add_filter( 'body_class', array( $this, 'body_class' ), 0 );
	}

	function title_parts( $parts ) {
		return array( sprintf( __( 'Renew "%s"', APP_TD ), get_the_title( get_queried_object_id() ) ) );
	}

	function body_class($classes) {
		if ( ! is_active_sidebar( 'edit-listing' ) ) {
			$classes[] = 'no_sidebar_bg';
		}

		$classes[] = 'va_listing_edit';
		$classes[] = 'va_listing_renew';
		return $classes;
	}

	function handle_renew_transaction_completed( $order ) {
		if ( ! _va_is_renewal_order( $order ) ) {
			return;
		}

		$order_info = _va_get_order_listing_info( $order );

		$listing_id = $order_info['listing']->ID;

		$this->reset_start_date( $listing_id );

		$this->apply_categories( $listing_id, $order );
		$this->apply_plan( $listing_id, $order );
		$this->apply_addons( $listing_id, $order );

		wp_update_post( array(
			'ID' => $listing_id,
			'post_status' => 'publish'
		) );
	}

	function reset_start_date( $listing_id ) {
		$post = get_post( $listing_id );
		va_update_listing_start_date( $post );
	}

	function apply_categories( $listing_id, $order ) {
		$categories = get_post_meta( $order->get_id(), 'renew_categories', true );
		if ( empty( $categories ) ) {
			return;
		}

		va_set_listing_categories( $listing_id, $categories );
	}

	function apply_plan( $listing_id, $order ) {
		$listing_data = _va_get_order_listing_info( $order );

		if ( ! $listing_data ) {
			return;
		}

		extract( $listing_data );

		update_post_meta( $listing_id, 'listing_duration', $plan_data['duration'] );

		foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
			if ( ! empty( $plan_data[ $addon ] ) ) {
				va_add_featured( $listing_id, $addon, $plan_data[ $addon . '_duration' ] );
			}
		}
	}

	function apply_addons( $listing_id, $order ) {
		global $va_options;

		foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
			foreach ( $order->get_items( $addon ) as $item ) {
				va_add_featured( $item['post_id'], $addon, $va_options->addons[ $addon ]['duration'] );
			}
		}

	}
}

class VA_Listing_Purchase extends APP_View {

	function init() {
		global $wp, $va_options;

		$wp->add_query_var( 'listing_purchase' );

		$listing_permalink = $va_options->listing_permalink;
		$permalink = $va_options->purchase_listing_permalink;

		appthemes_add_rewrite_rule( $listing_permalink . '/' . $permalink . '/(\d+)/?$', array(
			'listing_purchase' => '$matches[1]'
		) );
	}

	function condition() {
		return (bool) get_query_var( 'listing_purchase' );
	}

	function parse_query( $wp_query ) {
		$listing_id = $wp_query->get( 'listing_purchase' );

		if ( 1 == get_post_meta( $listing_id, 'listing_claimable', true ) ) {
			// This is claimable, they may proceed with purchasing.
		} else if ( !current_user_can( 'edit_post', $listing_id ) ) {
			if ( ! is_user_logged_in() ) {
				wp_die( sprintf( __( 'Please <a href="%s">login</a> to purchase/upgrade that listing.', APP_TD ), wp_login_url( va_get_listing_purchase_url( $listing_id ) ) ) );
			} else {
				wp_die( __( 'You do not have permission to purchase that listing.', APP_TD ) );
			}
		}

		$wp_query->is_home = false;
		$wp_query->query_vars = array_merge( $wp_query->query_vars, array(
			'post_type' => VA_LISTING_PTYPE,
			'post_status' => 'any',
			'post__in' => array( $listing_id )
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

		add_filter( 'body_class', array( $this, 'body_class' ), 99 );

		appthemes_setup_checkout( 'upgrade-listing', va_get_listing_purchase_url( get_queried_object_id() ) );
		$found = appthemes_process_checkout();
		if ( ! $found ) {
			return locate_template( '404.php' );
		}

		return locate_template( 'purchase-listing.php' );
	}

	function body_class( $classes ) {
		if ( ! is_active_sidebar( 'edit-listing' ) ) {
			$classes[] = 'no_sidebar_bg';
		}

		return $classes;
	}

	function title_parts( $parts ) {
		return array( sprintf( __( 'Purchase "%s"', APP_TD ), get_the_title( get_queried_object_id() ) ) );
	}

}

class VA_Listing_Claim extends APP_View {

	function init() {
		global $wp, $va_options;

		$wp->add_query_var( 'listing_claim' );

		$listing_permalink = $va_options->listing_permalink;
		$permalink = $va_options->claim_listing_permalink;

		appthemes_add_rewrite_rule( $listing_permalink . '/' . $permalink . '/(\d+)/?$', array(
			'listing_claim' => '$matches[1]'
		) );

		add_action( 'appthemes_transaction_completed', array( $this, 'handle_claim_transaction_completed' ), 9 );
		add_action( 'pending-claimed_to_publish', array( $this, 'approve_claim' ) );
		add_action( 'appthemes_after_import_upload_row', array( $this, 'import_form_option' ) );
		add_action( 'appthemes_importer_import_row_post_meta', array( $this, 'import_form_action' ) );

		if ( isset( $_GET['rejected'] ) ) {
			add_action( 'admin_notices', array( $this, 'rejected_claim_success_notice' ) );
		}
	}

	function condition() {
		return (bool) get_query_var( 'listing_claim' );
	}

	function parse_query( $wp_query ) {
		$listing_id = $wp_query->get( 'listing_claim' );

		$claimable = get_post_meta( $listing_id, 'listing_claimable', true );

		if ( empty( $claimable ) ) {
			// During a 'un-moderated claim' checkout step, the claim will have been approved and 'claimee', which will equal get_current_user_id(), will be present in place of listing_claimable
			if ( get_current_user_id() != get_post_meta( $listing_id, 'claimee', true ) ) {
				wp_die( __( 'This listing is not claimable.', APP_TD ) );
			}
		}

		$wp_query->is_home = false;
		$wp_query->query_vars = array_merge( $wp_query->query_vars, array(
			'post_type' => VA_LISTING_PTYPE,
			'post_status' => 'any',
			'post__in' => array( $listing_id )
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
		appthemes_setup_checkout( 'claim-listing', va_get_listing_claim_url( get_queried_object_id() ) );
		$step_found = appthemes_process_checkout();
		if ( ! $step_found ) {
			return locate_template( '404.php' );
		}

		return locate_template( 'checkout.php' );
	}

	function template_redirect() {

		wp_enqueue_script( 'validate' );
		wp_enqueue_script( 'validate-lang' );

		wp_enqueue_script(
			'va-listing-categories',
			get_template_directory_uri() . '/scripts/listing-categories.js',
			array( 'validate', 'jquery-ui-sortable' ),
			VA_VERSION,
			true
		);

		wp_localize_script(
			'va-listing-categories',
			'VA_i18n',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
				'clear'	  => __( 'Clear', APP_TD ),
				'category_limit' => __( 'You have exceeded the category selection quantity limit.', APP_TD )
			)
		);

		add_filter( 'body_class', array( $this, 'body_class' ), 0 );
	}

	function body_class( $classes ) {
		$classes[] = 'va-claim-listing';

		return $classes;
	}

	function handle_no_charge_claim_listing( $listing_id ) {
		global $va_options;

		$claimee = get_current_user_id();

		add_post_meta( $listing_id, 'claimee', $claimee, true );
		delete_post_meta( $listing_id, 'listing_claimable' );
		add_user_meta( $claimee, 'claimee', 1 );

		if ( $va_options->moderate_claimed_listings ) {
			wp_update_post( array(
				'ID' => $listing_id,
				'post_status' => 'pending-claimed'
			) );
			$url = va_get_claimed_listings_url() . '#post-'. $listing_id;
		} else {
			self::approve_claim( get_post( $listing_id ) );
			$url = get_permalink( $listing_id );
		}
		wp_redirect( $url );
		exit;
	}

	function handle_claim_transaction_completed( $order ) {
		global $va_options;

		$claim_item = $order->get_item( VA_LISTING_CLAIM_ITEM );

		if ( empty( $claim_item ) ) {
			return;
		}

		$listing_id = $claim_item['post_id'];

		add_post_meta( $listing_id, 'claimee', $order->get_author(), true );

		add_user_meta( $order->get_author(), 'claimee', 1 );

		if ( ! $va_options->moderate_claimed_listings ) {
			$this->approve_claim( get_post( $listing_id ) );
		}
	}

	function approve_claim( $post ) {
		if ( isset( $_GET['reject'] ) ) {
			return;
		}

		$order = _va_get_listing_order( $post->ID );

		$this->reset_start_date( $post->ID );
		$this->update_listing_author( $post, $order );
		$this->email_approved_claim( $post );

		if ( ! empty( $_REQUEST['post'] ) ) {
			$current_post_id = $_REQUEST['post'];
		}

		if ( ! empty( $_REQUEST['ID'] ) ) {
			$current_post_id = $_REQUEST['ID'];
		}

		if ( ! empty( $current_post_id ) && APPTHEMES_ORDER_PTYPE == get_post_type( intval( $current_post_id ) ) ) {
			$this->approve_claim_save_post( $post->ID, $post );
		} else {
			add_action( 'save_post', array( $this, 'approve_claim_save_post' ), 20, 2 );
		}
	}

	function approve_claim_save_post( $post_id, $post ) {
		$order = _va_get_listing_order( $post_id );

		if ( $order ) {
			$this->apply_categories( $post, $order );
			$this->apply_plan( $post, $order );
			$this->apply_addons( $post, $order );
		}

		$this->unmark_claimable( $post->ID );
	}

	function reset_start_date( $listing_id ) {
		$post = get_post( $listing_id );
		va_update_listing_start_date( $post );
	}

	function email_approved_claim( $post ) {
		$listing = get_post( $post->ID );
		va_send_approved_claim_notification( $listing );
	}

	function apply_plan( $post, $order ) {
		$listing_data = _va_get_order_listing_info( $order );

		if ( ! $listing_data ) {
			return;
		}

		extract( $listing_data );

		update_post_meta( $listing_id, 'listing_duration', $plan_data['duration'] );

		foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
			if ( ! empty( $plan_data[ $addon ] ) ) {
				va_add_featured( $listing_id, $addon, $plan_data[ $addon . '_duration' ] );
			}
		}
	}

	function apply_addons( $post, $order ) {
		global $va_options;

		foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
			foreach ( $order->get_items( $addon ) as $item ) {
				va_add_featured( $item['post_id'], $addon, $va_options->addons[ $addon ]['duration'] );
			}
		}

	}

	function apply_categories( $post, $order ) {
		$categories = get_post_meta( $order->get_id(), 'claim_categories', true );
		if ( empty( $categories ) ) {
			return;
		}

		va_set_listing_categories( $post->ID, $categories );
	}

	function unmark_claimable( $post_id ) {
		unset( $_POST['listing_claimable'] );
		delete_post_meta( $post_id, 'listing_claimable' );
	}

	function update_listing_author( $post, $order ) {
		if ( isset( $_GET['reject'] ) ) {
			return;
		}

		$old_author = $post->post_author;

		$new_author = $order->get_author();

		if ( $old_author == $new_author ) {
			return;
		}

		wp_update_post( array(
			'ID' => $post->ID,
			'post_author' => $new_author,
		) );
	}

	public static function reject_claim() {
		global $pagenow;

		if ( 'post.php' != $pagenow || ! isset( $_GET['post'] ) ) {
			return;
		}

		$listing_id = intval( $_GET['post'] );

		if ( ! ( _va_is_claimed( _va_get_listing_order( $listing_id ) ) && get_post_status( $listing_id ) == 'pending-claimed' ) ) {
			return;
		}

		wp_update_post( array(
			'ID' => $listing_id,
			'post_status' => 'publish'
		) );

		$claimee = get_post_meta( $listing_id, 'claimee', true );
		if ( empty( $claimee ) ) {
			return;
		}

		va_send_rejected_claim_notification( get_post( $listing_id ), $claimee );

		delete_post_meta( $listing_id, 'claimee' );
		add_post_meta( $listing_id, 'listing_claimable', '1', true );

		// For future use to:
		// A. Just in case we would need to undo this rejection
		// B. To check for and deny future attempts to claim this listing by this user
		add_post_meta( $listing_id, 'rejected_claimee', $claimee );

		do_action( 'va_rejected_listing_claim', $listing_id, $claimee );

		return true;
	}

	function rejected_claim_success_notice() {
		echo scb_admin_notice( __( 'You have rejected the claim so this listing has been reset to <a href="#listing-claimable">claimable</a>.', APP_TD ) );
	}

	function import_form_option() {
		if ( empty( $_GET['page'] ) || $_GET['page'] !== 'app-importer-' . VA_LISTING_PTYPE ) {
			return;
		}
		?>
			<tr>
				<th>
					<label><?php _e( 'Claimable', APP_TD ); ?></label>
				</th>
				<td>
					<fieldset>
						<label><input type="checkbox" name="listing_claimable" value="1" /><?php _e( 'Allow listings to be claimable', APP_TD ); ?></label>
					</fieldset>
				<p class="description"><?php _e( 'Claimable listings will have a link that allows users to claim them.', APP_TD ); ?></p>
				</td>
			</tr>
		<?php
	}

	function import_form_action( $post_meta ) {
		if ( ! empty( $_POST['listing_claimable'] ) ) {
			$post_meta['listing_claimable'] = 1;
		}

		return $post_meta;
	}
}

class VA_Listing_Single extends APP_View {

	function condition() {
		return is_singular( VA_LISTING_PTYPE );
	}

	function template_redirect() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'script_init' ), 99 );
	}

	function enqueue_scripts() {

		wp_enqueue_script(
			'jquery-raty',
			get_template_directory_uri() . '/scripts/jquery.raty.min.js',
			array( 'jquery' ),
			'2.1.0',
			true
		);

		wp_enqueue_script( 'colorbox' );
		wp_enqueue_style( 'colorbox' );

		wp_enqueue_script( 'validate' );
		wp_enqueue_script( 'validate-lang' );

	}

	function script_init() {
		$hint_list = array(
			__( 'bad', APP_TD ),
			__( 'poor', APP_TD ),
			__( 'regular', APP_TD ),
			__( 'good', APP_TD ),
			__( 'excellent', APP_TD ),
		);

?>
<script type="text/javascript">
jQuery(function($){
	$('#review-rating').raty({
		hintList: <?php echo json_encode( $hint_list ); ?>,
		path: '<?php echo get_template_directory_uri() . '/images/'; ?>',
		scoreName: 'review_rating',
		click: function(score, evt) {
			jQuery('#add-review-form').find('.rating-error').remove();
		}
	});
});
</script>

<?php
	}

	// Show parent categories instead of listing archive
	function breadcrumbs( $trail ) {
		$cat = get_the_listing_categories( get_queried_object_id() );

		if ( ! $cat ) {
			return $trail;
		}

		$cat = reset( $cat );
		$cat = (int) $cat->term_id;
		$chain = array_reverse( get_ancestors( $cat, VA_LISTING_CATEGORY ) );
		$chain[] = $cat;

		$new_trail = array( $trail[0], $trail[1] );

		foreach ( $chain as $cat ) {
			$cat_obj = get_term( $cat, VA_LISTING_CATEGORY );
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
			appthemes_display_notice( 'success', __( 'The listing has been successfully updated.', APP_TD ) );
		} elseif ( $status == 'pending' ) {
			appthemes_display_notice( 'success-pending', __( 'This listing is currently pending and must be approved by an administrator.', APP_TD ) );
		} elseif ( $status == 'draft' ) {
			appthemes_display_notice( 'success-pending', __( 'This listing is currently awaiting payment and/or payment processing.', APP_TD ) );
		} elseif ( $status == 'expired' ) {
			appthemes_display_notice( 'success-pending', __( 'This listing is expired.', APP_TD ) );
		}
	}
}

/**
 * Redirects from AppThemes-created login pages to appropriate standard ones if option wp_login = true
 */
class VA_Login_Redirect extends APP_View {

	protected $page_template;

	function condition() {
		global $va_options;

		if ( ! is_page() || current_theme_supports( 'app-login' ) || ! $va_options->wp_login ) {
			return false;
		}

		$this->page_template = get_page_template_slug( get_queried_object_id() );

		return (bool) $this->page_template && in_array( $this->page_template, array(
			'form-login.php',
			'form-registration.php',
			'form-password-recovery.php',
			'form-password-reset.php'
		) );
	}

	function template_redirect() {
		switch ( $this->page_template ) {
			case 'form-login.php':
				wp_redirect( appthemes_get_login_url() );
				break;
			case 'form-registration.php':
				wp_redirect( appthemes_get_registration_url() );
				break;
			case 'form-password-recovery.php':
				wp_redirect( appthemes_get_password_recovery_url() );
				break;
			case 'form-password-reset.php':
				wp_redirect( appthemes_get_password_reset_url() );
				break;
		}
		exit();
	}

}
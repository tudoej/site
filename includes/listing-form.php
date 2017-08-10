<?php

add_action( 'va_listing_validate_fields', 'va_validate_listing_title' );
add_action( 'va_listing_validate_fields', 'va_validate_listing_category' );

add_action( 'va_handle_listing_contact_fields', 'va_format_listing_contact_fields', 10, 2 );

add_filter( 'va_handle_update_listing', 'va_validate_update_listing' );
add_action( 'va_handle_update_listing', 'va_set_meta_defaults' );
add_filter( 'va_handle_update_listing', 'appthemes_update_search_index' );

add_action( 'appthemes_notices', 'va_listing_error_notice' );


class VA_Listing_Info_Edit extends APP_Checkout_Step {

	public function __construct() {
		parent::__construct( 'edit-listing', array(
			'priority' => 1,
			'register_to' => array( 'edit-listing' ),
		) );
	}

	public function display( $order, $checkout ) {
		global $va_options;

		the_post();

		$listing = va_get_existing_listing_to_edit();

		$title = get_the_title( $listing->ID );
		$link = html_link( get_permalink( $listing ), $title );

		remove_filter( 'va_multiple_category_checklist_label', 'va_listing_category_checklist_label_surcharges', 10, 3 );

		if ( ! empty( $post->ID ) && false !== _va_get_last_plan_info( $post->ID ) ) {
			$disabled = current_user_can( 'administrator' ) ? false : true;
		}

		$plan = _va_get_last_plan_info( $listing->ID );
		if ( false !== $plan ) {
			$plan_data = va_get_plan_options( $plan['ID'] );

			$included_categories = va_get_plan_included_categories( $plan['ID'] );
			$categories_locked = current_user_can( 'administrator' ) ? false : true;
		} else {
			$included_categories = $va_options->included_categories;
			$categories_locked = false;
		}

		appthemes_load_template( 'form-listing.php', array(
			'title' => sprintf( __( 'Edit %s', APP_TD ), $link ),
			'listing' => $listing,
			'action' => __( 'Save Changes', APP_TD ),
			'form_action' => appthemes_get_step_url(),
			'included_categories' => $included_categories,
			'categories_locked' => $categories_locked
		) );

	}

	public function process( $order, $checkout ) {

		if ( ! isset( $_POST['action'] ) || ( 'new-listing' != $_POST['action'] && 'edit-listing' != $_POST['action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_listings' ) ) {
			return;
		}

		if ( isset( $_POST['ID'] ) && ! current_user_can( 'edit_listing', $_POST['ID'] ) ) {
			return;
		}

		check_admin_referer( 'va_create_listing' );

		$listing = $this->update_listing( $order, $checkout );
		if ( ! $listing ) {
			// there are errors, return to current page
			return;
		}

		wp_redirect( get_permalink( $listing ) );
	}

	protected function update_listing( $order, $checkout ) {

		$errors = apply_filters( 'va_listing_validate_fields', va_get_listing_error_obj() );
		if ( $errors->get_error_codes() ) {
			return false;
		}

		$args = wp_array_slice_assoc( $_POST, array( 'ID', 'post_title', 'post_content', 'tax_input' ) );
		$args['post_type'] = VA_LISTING_PTYPE;
		$args['post_name'] = _va_set_post_name( $args['post_title'], $args['ID'], $args['post_type'] );

		if ( empty( $_POST['ID'] ) || !(bool) get_post( $_POST['ID'] ) ) {
			$args['post_author'] = wp_get_current_user()->ID;
			$args['post_status'] = 'draft';
			$listing_id = wp_insert_post( $args );
		} else {
			$listing_id = wp_update_post( $args );
		}

		_va_set_guid( $listing_id );

		$listing_tags = va_get_listing_tags();
		va_set_listing_tags( $listing_id, $listing_tags );

		$listing_categories = va_get_listing_cat_id();
		$listing_categories = va_handle_categories_limit( $checkout, $listing_categories );
		va_set_listing_categories( $listing_id, $listing_categories );

		foreach ( va_get_listing_contact_fields() as $field ) {
			$field_value = apply_filters('va_handle_listing_contact_fields', strip_tags( _va_get_initial_field_value( $field ) ), $field, $listing_id );
			update_post_meta( $listing_id, $field, $field_value );
		}

		va_update_form_builder( $listing_categories, $listing_id, VA_LISTING_CATEGORY );

		appthemes_set_coordinates( $listing_id, $_POST['lat'], $_POST['lng'] );

		va_handle_files( $listing_id, $listing_categories, VA_LISTING_CATEGORY );

		return apply_filters( 'va_handle_update_listing', get_post( $listing_id ) );
	}

}

class VA_Listing_Info_Purchase extends VA_Listing_Info_Edit {

	public function __construct() {
		$this->setup( 'edit-listing', array(
			'priority' => 2,
			'register_to' => array( 'create-listing' => array( 'after' => 'purchase-listing' ) ),
		) );
	}

	public function display( $order, $checkout ) {
		global $va_options;

		the_post();

		$this->setup_coupon( $checkout->get_data( 'coupon-code' ) );

		$plan = $checkout->get_data( 'plan' );
		if ( false === $plan ) {
			$included_categories = $va_options->included_categories;
		} else {
			$included_categories = va_get_plan_included_categories( $plan );
		}

		appthemes_load_template( 'form-listing.php', array(
			'title' => __( 'Edit Listing Information', APP_TD ),
			'listing' => va_get_default_listing_to_edit(),
			'action' => __( 'Next Step', APP_TD ),
			'form_action' => appthemes_get_step_url(),
			'included_categories' => $included_categories,
			'categories_locked' => false,
		) );

	}

	public function process( $order, $checkout ) {
		global $va_options;

		if ( ! isset( $_POST['action'] ) || ( 'new-listing' != $_POST['action'] && 'edit-listing' != $_POST['action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_listings' ) ) {
			return;
		}

		check_admin_referer( 'va_create_listing' );

		$listing = $this->update_listing( $order, $checkout );
		if ( ! $listing ) {
			// there are errors, return to current page
			return;
		}

		if ( $va_options->listing_charge ) {
			va_add_plan_to_order( $order, $listing->ID, $checkout->get_data( 'plan' ) );
			va_add_addons_to_order( $order, $listing->ID, $checkout->get_data( 'addons' ) );
			va_add_category_surcharges( $order, $listing->ID, VA_LISTING_CATEGORY );
			va_add_order_description( $order, $listing->ID, $checkout->get_data( 'plan' ) );
			va_add_recurring_to_order( $order, $listing->ID, $checkout->get_data( 'plan' ), $checkout->get_data( 'recurring' ) );
		} else {
			va_add_non_plan_to_order( $order, $listing->ID );
		}

		do_action( 'appthemes_create_order', $order );
		$this->finish_step();
	}

	function setup_coupon( $coupon_code = '' ) {
		if ( empty( $coupon_code ) ) {
			return;
		}

		add_action( 'va_after_create_listing_form', array( $this, 'pass_coupon' ) );
	}

	function pass_coupon() {
		$checkout = appthemes_get_checkout();
		$coupon_code = $checkout->get_data( 'coupon-code' );

		echo html( 'input', array(
			'type' => 'hidden',
			'name' => 'coupon-code',
			'value' => $coupon_code
		) );
	}

}

function va_add_non_plan_to_order( $order, $listing_id ) {
	$order->add_item( VA_LISTING_PTYPE, 0, $listing_id, true );
}

function va_add_plan_to_order( $order, $listing_id, $plan_id ) {

	// remove any previously added plans...
	$plans = new WP_Query( array(
		'post_type' => APPTHEMES_PRICE_PLAN_PTYPE,
		'nopaging' => 1,
	) );

	foreach ( $plans->posts as $plan ) {
		$order->remove_item( $plan->post_name );
	}

	$plan = get_post( $plan_id );
	$plan_data = va_get_plan_options( $plan_id );

	$order->add_item( $plan->post_name, $plan_data['price'], $listing_id );
}

function va_add_order_description( $order, $post_id, $plan_id = '' ) {
	$order_description_tags = array(
		'%post_title%',
		'%post_type%',
		'%plan_title%',
	);

	$order_description_tags = apply_filters( 'va_order_description_tags', $order_description_tags );

	$order_description_tag_data = array();
	foreach ( $order_description_tags as $order_description_tag ) {
		$order_description_tag_slug = str_ireplace( '%', '', $order_description_tag );

		$order_description_tag_value = apply_filters( '_va_order_description_tag_' . $order_description_tag_slug, $order_description_tag, $order, $post_id, $plan_id );

		$order_description_tag_data[ $order_description_tag ] = $order_description_tag_value;
	}

	if ( ! empty( $plan_id ) ) {
		$order_description = apply_filters( 'va_order_description', __( '%post_type%: %post_title% - %plan_title%', APP_TD ) );
	} else {
		$order_description = apply_filters( 'va_order_description', __( '%post_type%: %post_title%', APP_TD ) );
	}

	foreach ( $order_description_tag_data as $order_description_tag => $order_description_tag_value ) {
		$order_description = str_ireplace( $order_description_tag, $order_description_tag_value, $order_description );
	}

	$order->set_description( $order_description );
}

add_filter( '_va_order_description_tag_post_title', '_va_order_description_tag_post_title', 10, 4 );
function _va_order_description_tag_post_title( $order_description_tag, $order, $post_id, $plan_id ) {
	return get_the_title( $post_id );
}

add_filter( '_va_order_description_tag_post_type', '_va_order_description_tag_post_type', 10, 4 );
function _va_order_description_tag_post_type( $order_description_tag, $order, $post_id, $plan_id ) {
	if ( $ptype = get_post_type( $post_id ) ) {
		return get_post_type_object( $ptype )->labels->singular_name;
	}
}

add_filter( '_va_order_description_tag_plan_title', '_va_order_description_tag_plan_title', 10, 4 );
function _va_order_description_tag_plan_title( $order_description_tag, $order, $post_id, $plan_id ) {
	return ! empty( $plan_id ) ? get_the_title( $plan_id ) : '';
}

function va_add_addons_to_order( $order, $listing_id, $addons ) {

	_va_reset_order_addons( $order );
	foreach ( $addons as $addon_id ) {

		if ( _va_already_featured( $addon_id, $listing_id ) ) {
			continue;
		}

		$price = APP_Item_Registry::get_meta( $addon_id, 'price' );

		$order->add_item( $addon_id, $price, $listing_id );
	}

}

function va_add_category_surcharges( $order, $post_id, $taxonomy ) {

	_va_reset_order_category_surcharges( $order, $post_id, $taxonomy );
	$selected_categories = get_the_terms( $post_id, $taxonomy );

	foreach ( $selected_categories as $category ) {

		$surcharge = va_get_category_surcharge( $category->term_id, $taxonomy, 'id' );

		if ( ! empty( $surcharge ) ) {
			$order->add_item( $taxonomy . '_' . $category->term_id , $surcharge, $post_id, true );
		}
	}
}

function _va_reset_order_addons( $order ) {
	foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
		$order->remove_item( $addon );
	}
}

function _va_reset_order_category_surcharges( $order, $post_id, $taxonomy ) {
	$items = $order->get_items();

	if ( ! $items ) {
		return;
	}

	foreach( $items as $item ) {
		if ( false !== strpos( $item['type'], $taxonomy . '_' ) ) {
			$order->remove_item_by_id( $item['unique_id'] );
		}
	}
}

function va_add_recurring_to_order( $order, $post_id, $plan_id, $recurring ) {
	if ( 'recurring' == $recurring ) {
		$plan_data = va_get_plan_options( $plan_id );
		$order->set_recurring_period( $plan_data['period'], $plan_data['period_type'] );
	}
}

// validates the listing data and returns the post if there are no errors. In case of errors, returns false
function va_validate_update_listing( $listing ) {

	$errors = va_get_listing_error_obj();
	if ( $errors->get_error_codes() ) {
		set_transient( 'va-errors', $errors );
		$listing = false;
	}

	return $listing;
}

// There is javascript that limits the categories selected, but this will override it and limit the categories even if somehow more than were allowed were passed.
function va_handle_categories_limit( $checkout, $categories ) {
	global $va_options;

	$plan_id = $checkout->get_data( 'plan' );
	if ( ! empty( $plan_id ) ) {
		$plan_data = va_get_plan_options( $plan_id );
		$included_categories = $plan_data['included_categories'];
	} else {
		$included_categories = $va_options->included_categories;
	}

	if ( ! $included_categories ) {
		return $categories;
	}

	$filtered_categories = array();
	$category_count = 0;
	foreach ( $categories as $category ) {
		if ( $category_count >= $included_categories ) {
			break;
		}
		$filtered_categories[] = $category;
		$category_count++;
	}

	return $filtered_categories;
}

function va_set_listing_categories( $listing_id, $categories ) {
	if ( empty( $categories ) || ! is_array( $categories ) ) {
		return;
	}

	$categories = array_map( 'intval', $categories );
	$categories = array_unique( $categories );

	wp_set_object_terms( $listing_id, $categories, VA_LISTING_CATEGORY );
}

function va_set_listing_tags( $listing_id, $tags ) {
	$tags = array_map( 'trim', $tags );
	$tags = array_unique( $tags );

	wp_set_object_terms( $listing_id, $tags, VA_LISTING_TAG );
}


function _va_get_listing_meta_defaults() {

	$defaults = array(
		'rating_avg' => 0,
		'featured' => 0,
		'featured-home' => 0,
		'featured-cat' => 0,
	);

	return apply_filters( 'va_listing_meta_defaults', $defaults );
}

function va_set_meta_defaults( $listing ) {

	if ( empty( $listing ) ) {
		return false;
	}

	if ( isset( $listing->ID ) ) {
		$listing_id = $listing->ID;
	} elseif ( is_numeric( $listing ) ) {
		$listing_id = $listing;
	} else {
		return false;
	}

	foreach ( _va_get_listing_meta_defaults() as $k => $v ) {
		$existing = get_post_meta( $listing_id, $k, true );
		if ( empty( $existing ) ) {
			update_post_meta( $listing_id, $k, $v );
		}
	}

	return $listing;
}

function _va_needs_purchase( $listing ) {
	global $va_options;
	return _va_needs_publish( $listing ) && $va_options->listing_charge;
}

function _va_needs_publish( $listing ) {
	return in_array( $listing->post_status, array( 'draft', 'expired' ) );
}

function _va_is_claimable( $listing_id = '' ) {
	$listing_id = ! empty( $listing_id ) ? $listing_id : get_the_ID();

	$claimable = get_post_meta( $listing_id, 'listing_claimable', true );

	if ( empty( $claimable ) ) {
		return false;
	}

	return true;
}

function va_validate_listing_title( $errors ) {

	if ( empty( $_POST['post_title'] ) ) {
		$errors->add( 'no-title', __( 'No title was submitted.', APP_TD ) );
	}

	return $errors;
}

function va_format_listing_contact_fields( $field_value, $field ) {

	if ( 'website' == $field ) {
		$field_value = str_ireplace( 'http://', '', $field_value );
	}

	if ( 'twitter' == $field ) {
		$field_value = str_ireplace( array( '@' ), '', $field_value );
	}

	return $field_value;
}

function va_validate_listing_category( $errors ) {
	$listing_categories = va_get_listing_cat_id();
	if ( ! $listing_categories ) {
		$errors->add( 'wrong-cat', __( 'No category was submitted.', APP_TD ) );
	}

	return $errors;
}

function va_get_listing_cat_id() {
	static $cat_id;

	if ( is_null( $cat_id ) ) {
		$cat_id = false;
		if ( ! empty( $_REQUEST[ '_' . VA_LISTING_CATEGORY ] ) ) {
			$cat_id = array();
			foreach ( $_REQUEST[ '_' . VA_LISTING_CATEGORY ] as $listing_cat ) {
				$listing_cat = get_term( $listing_cat, VA_LISTING_CATEGORY );
				$cat_id[] = is_wp_error( $listing_cat ) ? false : $listing_cat->term_id;
			}
		}
	}

	return $cat_id;
}

function va_get_listing_tags() {
	$tags = array();

	if ( ! empty( $_POST['tax_input'][ VA_LISTING_TAG ] ) ) {
		$tags = explode( ',', $_POST['tax_input'][ VA_LISTING_TAG ] );
	}

	return $tags;
}

function the_listing_tags_to_edit( $listing_id ) {
	$tags = get_the_terms( $listing_id, VA_LISTING_TAG );

	if ( empty( $tags ) ) {
		return;
	}

	echo esc_attr( implode( ', ', wp_list_pluck( $tags, 'name' ) ) );
}

function va_get_default_listing_to_edit() {
	require ABSPATH . '/wp-admin/includes/post.php';

	$listing = get_default_post_to_edit( VA_LISTING_PTYPE );

	$listing->categories = va_get_listing_cat_id();

	foreach ( array( 'post_title', 'post_content' ) as $field ) {
		$listing->$field = _va_get_initial_field_value( $field );
	}

	foreach ( va_get_listing_contact_fields() as $field ) {
		$listing->$field = _va_get_initial_field_value( $field );
	}

	return $listing;
}

function va_get_existing_listing_to_edit() {
	$listing = get_queried_object();

	$listing->categories = get_the_listing_categories( $listing->ID );

	foreach ( va_get_listing_contact_fields() as $field ) {
		$listing->$field = get_post_meta( $listing->ID, $field, true );
	}

	return $listing;
}

function va_get_listing_contact_fields() {
	$fields = apply_filters( 'va_get_listing_contact_fields', array( 'phone', 'address', 'website', 'email' ) );
	$fields = array_unique( array_merge( (array) $fields, (array) va_get_allowed_listing_networks() ) );
	return $fields;
}

function va_get_listing_error_obj() {

	static $errors;

	if ( ! $errors ) {
		$errors = new WP_Error();
	}

	return $errors;
}

function va_listing_error_notice() {

	$errors = va_get_listing_error_obj();
	if ( ! $errors ) {
		return;
	}

	// look for transient errors and merge them if they exist
	$transient_errors = get_transient( 'va-errors' );
	if ( is_wp_error( $transient_errors ) && $transient_errors->get_error_codes() ) {
		$errors->errors = array_merge( $errors->errors, $transient_errors->errors );
		delete_transient( 'va-errors' );
	}

	$map = array(
		'no-title' => __( 'The listing must have a title.', APP_TD ),
		'wrong-cat' => __( 'The selected category does not exist.', APP_TD ),
	);

	foreach ( $errors->get_error_messages() as $message ) {
		appthemes_display_notice( 'error', $message );
	}
}

<?php

add_action( 'va_event_validate_fields', 'va_validate_event_title' );
add_action( 'va_event_validate_fields', 'va_validate_event_category' );

add_action( 'va_handle_event_contact_fields', 'va_format_event_contact_fields', 10, 2 );

add_filter( 'va_handle_update_event', 'va_validate_update_event' );
add_action( 'va_handle_update_event', 'va_set_event_meta_defaults' );
add_filter( 'va_handle_update_event', 'va_handle_event_dates' );
add_filter( 'va_handle_update_event', 'appthemes_update_search_index' );

add_action( 'appthemes_notices', 'va_event_error_notice' );

class VA_Event_Info_Edit extends APP_Checkout_Step{

	public function __construct(){
		parent::__construct( 'edit-event', array(
			'priority' => 1,
			'register_to' => array( 'edit-event' ),
			));
	}

	public function display( $order, $checkout ){
		global $va_options;
		the_post();

		$event = va_get_existing_event_to_edit();

		$title = get_the_title( $event->ID );
		$link = html_link( get_permalink( $event ), $title );

		remove_filter( 'va_multiple_category_checklist_label', 'va_event_category_checklist_label_surcharges', 10, 3 );

		$categories_locked = current_user_can('administrator') ? false : true;
		$included_categories = $va_options->event_included_categories;

		appthemes_load_template( 'form-event.php', array(
			'title' => sprintf( __( 'Edit %s', APP_TD ), $link ),
			'event' => $event,
			'action' => __( 'Save Changes', APP_TD ),
			'form_action' => appthemes_get_step_url(),
			'included_categories' => $included_categories,
			'categories_locked' => $categories_locked,
		) );

	}

	public function process( $order, $checkout ){

		if ( !isset( $_POST['action'] ) || 'edit-event' != $_POST['action'] )
			return;

		if ( !current_user_can( 'edit_events' ) )
			return;

		if ( isset( $_POST['ID'] ) && ! current_user_can( 'edit_post', $_POST['ID'] ) ) {
			return;
		}

		check_admin_referer( 'va_create_event' );

		$event = $this->update_event( $order, $checkout );
		if ( ! $event ) {
			// there are errors, return to current page
			return;
		}

		wp_redirect( get_permalink( $event ) );
	}

	protected function update_event( $order, $checkout ){

		$errors = apply_filters( 'va_event_validate_fields', va_get_event_error_obj() );
		if( $errors->get_error_codes() ){
			return false;
		}

		$args = wp_array_slice_assoc( $_POST, array( 'ID', 'post_title', 'post_content', 'tax_input' ) );
		$args['post_type'] = VA_EVENT_PTYPE;
		$args['post_name'] = _va_set_post_name( $args['post_title'], $args['ID'], $args['post_type'] );

		if ( empty( $_POST['ID'] ) || !(bool) get_post( $_POST['ID'] ) ) {

			$args['post_author'] = wp_get_current_user()->ID;
			$args['post_status'] = 'draft';

			$event_id = wp_insert_post( $args );
		} else {
			$event_id = wp_update_post( $args );
		}

		_va_set_guid( $event_id );

		$event_tags = va_get_event_tags();
		va_set_event_tags( $event_id, $event_tags );

		$event_categories = va_get_event_cat_id();

		$event_categories = va_handle_event_categories_limit( $checkout, $event_categories );

		va_set_event_categories( $event_id, $event_categories );

		foreach ( va_get_event_contact_fields() as $field ) {
			$field_value = apply_filters('va_handle_event_contact_fields', strip_tags( _va_get_initial_field_value( $field ) ), $field, $event_id );
			update_post_meta( $event_id, $field, $field_value );
		}

		va_update_form_builder( $event_categories, $event_id, VA_EVENT_CATEGORY );

		appthemes_set_coordinates( $event_id, $_POST['lat'], $_POST['lng'] );

		va_handle_files( $event_id, $event_categories, VA_EVENT_CATEGORY );

		return apply_filters('va_handle_update_event', get_post( $event_id) );
	}

}

class VA_Event_Info_Purchase extends VA_Event_Info_Edit{

	public function __construct(){
		$this->setup( 'edit-event', array(
			'priority' => 2,
			'register_to' => array( 'create-event' => array( 'after' => 'purchase-event' ) ),
		));
	}

	public function display( $order, $checkout ){
		global $va_options;

		the_post();

		$this->setup_coupon( $checkout->get_data( 'coupon-code' ) );

		appthemes_load_template( 'form-event.php', array(
			'title' => __( 'Edit Event Information', APP_TD ),
			'event' => va_get_default_event_to_edit(),
			'action' => __( 'Next Step', APP_TD ),
			'form_action' => appthemes_get_step_url(),
			'included_categories' => $va_options->event_included_categories,
			'categories_locked' => false,
		) );

	}

	public function process( $order, $checkout ){
		global $va_options;

		if ( !isset( $_POST['action'] ) || 'new-event' != $_POST['action'] )
			return;

		if ( !current_user_can( 'edit_events' ) )
			return;

		check_admin_referer( 'va_create_event' );

		$event = $this->update_event( $order, $checkout );

		if ( ! $event ) {
			// there are errors, return to current page
			return;
		}

		if( $va_options->event_charge ) {
			va_event_add_non_plan_to_order( $order, $event->ID );
			va_event_add_addons_to_order( $order, $event->ID, $checkout->get_data( 'addons' ) );
			va_add_category_surcharges( $order, $event->ID, VA_EVENT_CATEGORY );
			va_add_order_description( $order, $event->ID );
		} else {
			va_event_add_non_plan_to_order( $order, $event->ID );
		}

		do_action( 'appthemes_create_order', $order );
		$this->finish_step();
	}

	function setup_coupon( $coupon_code = '' ) {
		if ( empty( $coupon_code ) )
			return;

		add_action( 'va_after_create_event_form', array( $this, 'pass_coupon' ) );
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

function va_event_add_non_plan_to_order( $order, $event_id ){
	global $va_options;

	$price = $va_options->event_charge ? $va_options->event_price : 0;

	$order->add_item( VA_EVENT_PTYPE, $price, $event_id, true );
}

function va_event_add_addons_to_order( $order, $event_id, $addons ){
	global $va_options;

	_va_reset_order_addons( $order );
	foreach( $addons as $addon ){

		if( _va_event_already_featured( $addon, $event_id ) )
			continue;

		$price = $va_options->{ 'event_' . $addon . '_price' };
		$order->add_item( $addon, $price, $event_id, true );
	}

}

// validates the event data and returns the post if there are no errors. In case of errors, returns false
function va_validate_update_event( $event ) {

	$errors = va_get_event_error_obj();
	if ( $errors->get_error_codes( )) {
		set_transient('va-errors', $errors );
		$event = false;
	}

	return $event;
}

function va_set_event_categories( $event_id, $categories ) {
	$categories = array_map( 'intval', $categories );
	$categories = array_unique( $categories );

	wp_set_object_terms( $event_id, $categories, VA_EVENT_CATEGORY );
}

function va_set_event_tags( $event_id, $tags ) {
	$tags = array_map( 'trim', $tags );
	$tags = array_unique( $tags );

	wp_set_object_terms( $event_id, $tags, VA_EVENT_TAG );
}

// There is javascript that limits the categories selected, but this will override it and limit the categories even if somehow more than were allowed were passed.
function va_handle_event_categories_limit( $checkout, $categories ) {
	global $va_options;

	$included_categories = $va_options->event_included_categories;
	if ( empty( $included_categories ) )
		return $categories;

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

function va_handle_event_dates( $event ) {
	// !TODO - Migrate this into being used by admin/event-single.php

	$days = array();
	$day_times = array();
	if ( !empty( $_POST['_event_day'] ) ) {
		foreach ( $_POST['_event_day'] as $day ) {
			$date = date( 'Y-m-d', strtotime( $day['date'] ) );
			$times = $day['time_start'].'-'.$day['time_end'];
			$day_times[ $date ] = $times;

			$days[] = $date;
			va_insert_event_day( $date );
		}

		update_post_meta( $event->ID, VA_EVENT_DAY_TIMES_META_KEY, $day_times );

		wp_set_object_terms( $event->ID, $days, VA_EVENT_DAY );
	}

	asort( $days );
	update_post_meta( $event->ID, VA_EVENT_DATE_META_KEY, reset( $days ) );
	update_post_meta( $event->ID, VA_EVENT_DATE_END_META_KEY, end( $days ) );

	return $event;
}

function _va_get_event_meta_defaults() {

	$defaults = array (
		VA_EVENT_ATTENDEES_META_KEY => 0,
		'featured-home' => 0,
		'featured-cat' => 0,
	);

	return apply_filters( 'va_event_meta_defaults', $defaults );
}

function va_set_event_meta_defaults( $event ) {

	if ( empty( $event ) ) return false;

	if ( isset( $event->ID ) ) {
		$event_id = $event->ID;
	} elseif ( is_numeric( $event ) ) {
		$event_id = $event;
	} else {
		return false;
	}

	foreach ( _va_get_event_meta_defaults() as $k => $v ) {
		$existing = get_post_meta( $event_id, $k, true );
		if ( empty( $existing ) ) {
			update_post_meta( $event_id, $k, $v );
		}
	}

	return $event;
}

function _va_event_needs_purchase( $event ){
	global $va_options;
	return _va_event_needs_publish( $event ) && $va_options->event_charge;
}

function _va_event_needs_publish( $event ){
	return in_array( $event->post_status, array( 'draft', 'expired' ));
}

function va_validate_event_title( $errors ){

	$args = wp_array_slice_assoc( $_POST, array( 'ID', 'post_title', 'post_content', 'tax_input' ) );
	if ( empty( $args['post_title'] ) )
		$errors->add( 'no-title', __( 'No title was submitted.', ADD_TD ) );

	return $errors;

}

function va_format_event_contact_fields( $field_value, $field ){

	if( 'website' == $field ) {
		$field_value = str_ireplace('http://', '', $field_value);
	}

	if( VA_EVENT_LOCATION_URL_META_KEY == $field ) {
		$field_value = str_ireplace('http://', '', $field_value);
	}

	if( 'twitter' == $field ) {
		$field_value = str_ireplace(array('@'), '', $field_value);
	}

	return $field_value;
}

function va_validate_event_category( $errors ){
	$event_categories = va_get_event_cat_id();
	if ( !$event_categories )
		$errors->add( 'wrong-cat', __( 'No category was submitted.', APP_TD ) );

	return $errors;
}

function va_event_update_form_builder( $event_cat, $event_id, $taxonomy ) {
	if ( !$event_cat )
		return;

	$fields = array();
	foreach($event_cat as $_cat){
		foreach ( va_get_fields_for_cat( $_cat, $taxonomy ) as $field ) {
			$fields[$field['name']] = $field;
		}
	}

	$to_update = scbForms::validate_post_data( $fields );

	scbForms::update_meta( $fields, $to_update, $event_id );
}

function va_get_event_cat_id() {
	static $cat_id;

	if ( is_null( $cat_id ) ) {
		$cat_id = false;
		if ( !empty( $_REQUEST[ '_' . VA_EVENT_CATEGORY ] ) ) {
			$cat_id = array();
			foreach ( $_REQUEST[ '_' . VA_EVENT_CATEGORY ] as $event_cat ) {
				$event_cat = get_term( $event_cat, VA_EVENT_CATEGORY );
				$cat_id[] = is_wp_error( $event_cat ) ? false : $event_cat->term_id;
			}
		}
	}

	return $cat_id;
}

function va_get_event_tags() {
	$tags = array();

	if ( !empty( $_POST['tax_input'][ VA_EVENT_TAG ] ) ) {
		$tags = explode( ',', $_POST['tax_input'][ VA_EVENT_TAG ] );
	}

	return $tags;

}

function the_event_tags_to_edit( $event_id ) {
	$tags = get_the_terms( $event_id, VA_EVENT_TAG );

	if ( empty( $tags ) )
		return;

	echo esc_attr( implode( ', ', wp_list_pluck( $tags, 'name' ) ) );
}

function va_get_default_event_to_edit() {
	require ABSPATH . '/wp-admin/includes/post.php';

	$event = get_default_post_to_edit( VA_EVENT_PTYPE );

	$event->categories = va_get_event_cat_id();

	foreach ( array( 'post_title', 'post_content' ) as $field ) {
		$event->$field = _va_get_initial_field_value( $field );
	}

	foreach ( va_get_event_contact_fields() as $field ) {
		$event->$field = _va_get_initial_field_value( $field );
	}

	return $event;
}

function va_get_existing_event_to_edit() {
	$event = get_queried_object();

	$event->categories = get_the_event_categories( $event->ID );

	foreach ( va_get_event_contact_fields() as $field ) {
		$event->$field = get_post_meta( $event->ID, $field, true );
	}

	return $event;
}

function va_get_event_contact_fields() {
	$fields = apply_filters( 'va_get_event_contact_fields', array(
		'phone',
		'address',
		'website',
		'email',
		VA_EVENT_LOCATION_META_KEY,
		VA_EVENT_LOCATION_URL_META_KEY,
		VA_EVENT_COST_META_KEY
	) );
	$fields = array_unique( array_merge( (array) $fields, (array) va_get_allowed_event_networks() ) );
	return $fields;
}

function va_get_event_error_obj(){

	static $errors;

	if ( !$errors ){
		$errors = new WP_Error();
	}

	return $errors;

}

function va_event_error_notice() {

	$errors = va_get_event_error_obj();
	if ( ! $errors )
		return;

	// look for transient errors and merge them if they exist
	$transient_errors = get_transient('va-errors');
	if ( is_wp_error( $transient_errors ) && $transient_errors->get_error_codes() ) {
		$errors->errors = array_merge( $errors->errors, $transient_errors->errors );
		delete_transient('va-errors');
	}

	$map = array(
		'no-title' => __( 'The event must have a title.', APP_TD ),
		'wrong-cat' => __( 'The selected category does not exist.', APP_TD ),
	);

	foreach( $errors->get_error_messages() as $message )
		appthemes_display_notice( 'error', $message );
}

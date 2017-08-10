<?php

class VA_Select_Event_Plan_New extends APP_Checkout_Step{

	protected $errors;

	public function __construct(){
		$this->setup( 'purchase-event', array(
			'priority' => 1,
			'register_to' => array(
				'create-event',
		       	),
		));
	}

	public function display( $order, $checkout ){
		global $va_options;

		appthemes_load_template( 'purchase-event-new.php', array(
			'va_options' => $va_options,
		) );

	}

	public function process( $order, $checkout ){
		global $va_options;

		if( ! $va_options->event_charge ) {
			$this->finish_step();
		}

		if ( !isset( $_POST['action'] ) || 'purchase-event' != $_POST['action'] )
			return;
	
		if ( !current_user_can( 'edit_events' ) )
			return;

		$this->errors = apply_filters( 'appthemes_validate_purchase_fields', va_get_event_error_obj() );

		$addons = $this->get_addons();
		$coupon_code = $this->get_coupon();

		if( $this->errors->get_error_codes() ){
			return false;
		}

		$checkout->add_data( 'addons', $addons );
		if ( !empty( $coupon_code ) )
			$checkout->add_data( 'coupon-code', $coupon_code );

		$this->finish_step();
	}

	protected function get_addons(){
		$addons = array();
		foreach( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ){

			if( !empty( $_POST[ $addon ] ) )
				$addons[] = $addon;

		}
		return $addons;
	}

	protected function get_coupon(){
		if ( defined('APPTHEMES_COUPON_PTYPE') && !empty( $_POST['coupon-code'] ) ) {
			return $_POST['coupon-code'];
		} else {
			return '';
		}
	}

}

class VA_Select_Event_Plan_Existing extends VA_Select_Event_Plan_New{

	public function __construct(){
		$this->setup( 'upgrade-event', array(
			'priority' => 1,
			'register_to' => array( 'upgrade-event' )
		) );
	}

	public function display( $order, $checkout ){
		global $va_options;

		$event = get_queried_object();

		appthemes_load_template( 'purchase-event-existing.php', array(
			'event' => $event,
			'va_options' => $va_options,
		) );

	}
	
	public function process( $order, $checkout ){

		if ( !isset( $_POST['action'] ) || 'purchase-event' != $_POST['action'] )
			return;
	
		if ( !current_user_can( 'edit_events' ) )
			return;

		$this->errors = apply_filters( 'appthemes_validate_purchase_fields', va_get_event_error_obj() );

		$addons = $this->get_addons();

		if( $this->errors->get_error_codes() ){
			return false;
		}

		va_event_add_addons_to_order( $order, get_queried_object_id(), $addons );

		do_action( 'appthemes_create_order', $order );

		$this->finish_step();
	}

	protected function get_addons() {

		$event = get_queried_object();
		$addons = parent::get_addons();

		foreach( $addons as $k => $addon ){
			if( _va_already_featured( $addon, $event->ID ) ){
				unset( $addons[ $k ] );
			}
		}

		return $addons;
	}

}



function _va_event_no_featured_available() { 
	if( _va_event_addon_disabled( VA_ITEM_FEATURED_HOME ) && _va_event_addon_disabled( VA_ITEM_FEATURED_CAT ) ) {
		return true;
	} else {
		return false;
	}

}

function _va_event_no_featured_purchasable( $event ) {
	if( _va_event_no_featured_available() ){
		return true;
	} 

	foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
		if( !_va_event_already_featured( $addon, $event->ID ) && !_va_event_addon_disabled( $addon ) ) {
			return false;
		}
	}
	return true;
}

function _va_event_already_featured( $addon, $event_id ){

	$meta = get_post_meta( $event_id, $addon, true );
	if ( $meta ){
		return true;
	} else {
		return false;
	}

}

function _va_event_addon_disabled( $addon ){
	global $va_options;
	return empty( $va_options->{ 'event_' . $addon . '_enabled' } );
}

/**
 * Shows the field for an addon that can be purchased
 */
function _va_event_show_purchasable_featured_addon( $addon ) {
	global $va_options;
	if( ! _va_event_addon_disabled( $addon ) ){

		_va_event_show_featured_option( $addon, false );

		$string = __( ' %s for only %s more.', APP_TD );
		printf( $string, APP_Item_Registry::get_title( $addon ), appthemes_get_price( $va_options->{ 'event_' . $addon . '_price' } ) );
	}
}

/**
 * Shows the field for an addon that has already been purchased
 */
function _va_event_show_purchased_featured_addon( $addon ) {

	_va_event_show_featured_option( $addon, true );

	$string = __( ' %s', APP_TD );
	printf( $string, APP_Item_Registry::get_title( $addon ) );
}

function _va_event_show_featured_option( $addon, $enabled = false ){
	echo html( 'input', array(
		'name' => $addon,
		'type' => 'checkbox',
		'disabled' => $enabled,
		'checked' => $enabled
	) );
}

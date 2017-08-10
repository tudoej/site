<?php

add_action( 'after_setup_theme', 'add_listing_claim_item' );
function add_listing_claim_item() {
	$payments = get_theme_support( 'app-payments' );
	define( 'VA_LISTING_CLAIM_ITEM', 'listing-claim' );

	$payments[0]['items'][] = array(
		'type' => VA_LISTING_CLAIM_ITEM,
		'title' => __( 'Listing Claim', APP_TD )
	);
	add_theme_support( 'app-payments', $payments[0] );

	new VA_Claim_Listing;
	new VA_Claim_Listing_Categories;
	new VA_Claim_Listing_Select_Plan;
}

class VA_Claim_Listing extends APP_Checkout_Step {

	public function __construct() {
		parent::__construct( 'claim-listing', array(
			'priority' => 1,
			'register_to' => array( 'claim-listing' ),
		));
	}

	public function display( $order, $checkout ) {

		the_post();

		appthemes_load_template( 'claim-listing.php', array(
			'title' => sprintf( __( 'Claim "%s"', APP_TD ), get_the_title( va_get_claimed_listing()->post ) ),
			'listing_query' => va_get_claimed_listing(),
			'listing' => va_get_claimed_listing()->post,
		) );

	}

	public function process( $order, $checkout ) {

		if ( ! isset( $_POST['action'] ) || 'claim-listing' != $_POST['action'] ) {
			return;
		}

		if ( ! current_user_can( 'edit_listings' ) ) {
			return;
		}

		check_admin_referer( 'va_claim_listing' );

		$errors = $this->validate_purchase_claimable( va_get_listing_error_obj() );
		if ( $errors->get_error_codes() ) {
			return false;
		}

		$this->add_claim_to_order( $order );

		$this->finish_step();
	}

	function add_claim_to_order( $order ) {
		$order->add_item( VA_LISTING_CLAIM_ITEM, 0, va_get_claimed_listing()->post->ID, true );
	}

	function validate_purchase_claimable( $errors ) {
		$claimable = get_post_meta( va_get_claimed_listing()->post->ID, 'listing_claimable', true );

		if ( empty( $claimable ) ) {
			$errors->add( 'not-claimable', __( 'This listing is not claimable.', APP_TD ) );
		}

		if ( get_current_user_id() == get_post( va_get_claimed_listing()->post->ID )->post_author ) {
			$errors->add( 'own-not-claimable', __( 'This listing already belongs to you.', APP_TD ) );
		}

		return $errors;
	}

}

class VA_Claim_Listing_Categories extends APP_Checkout_Step {

	public function __construct() {
		parent::__construct( 'claim-listing-categories', array(
			'priority' => 3,
			'register_to' => array( 'claim-listing' => array( 'after' => 'claim-listing-plan' ) ),
		) );
	}

	public function display( $order, $checkout ) {

		the_post();

		$plan_data = va_get_plan_options( $checkout->get_data( 'plan' ) );

		$this->setup_coupon( $checkout->get_data( 'coupon-code' ) );

		$listing = va_get_claimed_listing()->post;
		$listing->categories = get_the_listing_categories( $listing->ID );

		appthemes_load_template( 'claim-listing-categories.php', array(
			'title' => sprintf( __( 'Claim "%s"', APP_TD ), get_the_title( get_queried_object_id() ) ),
			'listing' => $listing,
			'form_action' => appthemes_get_step_url(),
			'action' => __( 'Next Step', APP_TD ),
			'included_categories' => va_get_plan_included_categories( $checkout->get_data( 'plan' ) ),
			'categories_locked' => false,
			'errors' => va_get_listing_error_obj(),
		) );
	}

	public function process( $order, $checkout ) {
		global $va_options;

		if ( ! isset( $_POST['action'] ) || 'claim-listing-categories' != $_POST['action'] ) {
			return;
		}

		if ( ! current_user_can( 'edit_listings' ) ) {
			return;
		}

		check_admin_referer( 'va_claim_listing_categories' );

		$errors = $this->validate_categories( va_get_listing_error_obj(), $checkout );
		if ( $errors->get_error_codes() ) {
			return false;
		}

		$listing = va_get_claimed_listing()->post;

		if ( $va_options->listing_charge ) {
			$this->add_category_surcharges( $order, $listing->ID );
			va_add_recurring_to_order( $order, $listing->ID, $checkout->get_data( 'plan' ), $checkout->get_data( 'recurring' ) );
		}

		$this->add_category_selections( $order, $listing->ID );

		do_action( 'appthemes_create_order', $order );

		$this->finish_step();
	}

	function add_category_selections( $order, $post_id ) {

		$category_ids = $this->get_categories();

		$custom_forms = $this->get_custom_forms_data( $category_ids );

		add_post_meta( $order->get_id(), 'claim_categories', $category_ids, true );

	}

	function add_category_surcharges( $order, $post_id ) {

		_va_reset_order_category_surcharges( $order, $post_id, VA_LISTING_CATEGORY );
		$category_ids = $this->get_categories();

		foreach ( $category_ids as $category_id ) {

			$surcharge = va_get_category_surcharge( $category_id, VA_LISTING_CATEGORY, 'id' );

			if ( ! empty( $surcharge ) ) {
				$order->add_item( VA_LISTING_CATEGORY . '_' . $category_id , $surcharge, $post_id, true );
			}
		}
	}

	function get_categories() {

		$categories = va_get_listing_cat_id();
		$categories = array_map( 'intval', $categories );
		$categories = array_unique( $categories );

		return $categories;
	}

	function get_custom_forms_data( $categories ) {
		if ( ! $categories ) {
			return;
		}

		$fields = array();
		foreach ( $categories as $_cat ) {
			foreach ( va_get_fields_for_cat( $_cat, VA_LISTING_CATEGORY ) as $field ) {
				$fields[ $field['name'] ] = $field;
			}
		}

		return scbForms::validate_post_data( $fields );
	}

	function validate_categories( $errors, $checkout ) {
		$categories = $this->get_categories();

		if ( ! $categories ) {
			$errors->add( 'wrong-cat', __( 'No category was submitted.', APP_TD ) );
		}

		if ( count( va_handle_categories_limit( $checkout, $categories ) ) != count( $categories ) ) {
			$errors->add( 'category-limit', __( 'Too many categories were submitted.', APP_TD ) );
		}

		return $errors;
	}

	function setup_coupon( $coupon_code = '' ) {
		if ( empty( $coupon_code ) ) {
			return;
		}

		add_action( 'va_after_claim_listing_categories_form', array( $this, 'pass_coupon' ) );
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

class VA_Claim_Listing_Select_Plan extends APP_Checkout_Step {

	protected $errors;

	public function __construct() {
		parent::__construct( 'claim-listing-plan', array(
			'register_to' => array(
				'claim-listing' => array(
					'after' => 'claim-listing'
				),
		 	)
		) );

	}

	public function display( $order, $checkout ) {
		global $va_options;

		$plans = $this->get_available_plans();
		appthemes_load_template( 'purchase-listing-new.php', array(
			'plans' => $plans,
			'va_options' => $va_options,
		) );

	}

	protected function get_available_plans() {

		$plans = new WP_Query( array(
			'post_type' => APPTHEMES_PRICE_PLAN_PTYPE,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'nopaging' => 1,
		) );

		$plans_data = array();
		foreach ( $plans->posts as $key => $plan ) {
			$plans_data[ $key ] = va_get_plan_options( $plan->ID );
			$plans_data[ $key ]['post_data'] = $plan;
		}

		return $plans_data;
	}

	public function process( $order, $checkout ) {
		global $va_options;

		if ( ! $va_options->listing_charge ) {
			$this->finish_step();
		}

		if ( ! isset( $_POST['action'] ) || 'purchase-listing' != $_POST['action'] ) {
			return;
		}

		if ( ! current_user_can( 'edit_listings' ) ) {
			return;
		}

		$this->errors = new WP_Error();

		$plan_id = $this->get_plan();
		$addons = $this->get_addons();
		$coupon_code = $this->get_coupon();
		$recurring = $this->get_recurring( $plan_id );

		if ( $this->errors->get_error_codes() ) {
			return false;
		}

		$checkout->add_data( 'plan', $plan_id );
		$checkout->add_data( 'addons', $addons );

		if ( ! empty( $coupon_code ) ) {
			$checkout->add_data( 'coupon-code', $coupon_code );
		}

		if ( ! empty( $recurring ) ) {
			$checkout->add_data( 'recurring', $recurring );
		}

		$listing = va_get_claimed_listing()->post;

		if ( $va_options->listing_charge ) {

			va_add_plan_to_order( $order, $listing->ID, $checkout->get_data( 'plan' ) );
			$this->add_addons_to_order( $order, $listing->ID, $checkout->get_data( 'addons' ) );

		} else {
			va_add_non_plan_to_order( $order, $listing->ID );
		}

		$this->finish_step();
	}

	function add_addons_to_order( $order, $listing_id, $addons ) {

		_va_reset_order_addons( $order );
		foreach ( $addons as $addon_id ) {
			$price = APP_Item_Registry::get_meta( $addon_id, 'price' );
			$order->add_item( $addon_id, $price, $listing_id, true );
		}
	}

	protected function get_plan() {

		if ( empty( $_POST['plan'] ) ) {
			$this->errors->add( 'no-plan', __( 'No plan was chosen.', APP_TD ) );
			return false;
		}

		$plan = get_post( intval( $_POST['plan'] ) );
		if ( ! $plan ) {
			$this->errors->add( 'invalid-plan', __( 'The plan you choose no longer exists.', APP_TD ) );
			return false;
		}
		return $plan->ID ;
	}

	protected function get_addons() {

		$addons = array();
		foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {

			if ( ! empty( $_POST[ $addon.'_'.intval( $_POST['plan'] ) ] ) ) {
				$addons[] = $addon;
			}

		}
		return $addons;
	}

	protected function get_coupon() {
		if ( defined( 'APPTHEMES_COUPON_PTYPE' ) && ! empty( $_POST['coupon-code'] ) ) {
			return $_POST['coupon-code'];
		} else {
			return '';
		}
	}

	protected function get_recurring( $plan_id ) {

		$recurring = _va_get_plan_recurring_option( $plan_id );

		if ( 'optional_recurring' == $recurring ) {
			if ( ! empty( $_POST[ 'recurring_' . $plan_id ] ) && in_array( $_POST[ 'recurring_' . $plan_id ], array( 'recurring', 'non_recurring' ) ) ) {
				return $_POST[ 'recurring_' . $plan_id ];
			} else {
				return 'non_recurring';
			}
		} elseif ( 'forced_recurring' == $recurring ) {
			return 'recurring';
		} else {
			return 'non_recurring';
		}

	}

}

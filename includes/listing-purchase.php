<?php

class VA_Select_Plan_New extends APP_Checkout_Step {

	protected $errors;

	public function __construct() {
		$this->setup( 'purchase-listing', array(
			'priority' => 1,
			'register_to' => array(
				'create-listing',
			),
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

		$this->errors = apply_filters( 'appthemes_validate_purchase_fields', va_get_listing_error_obj() );

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

		$this->finish_step();
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

			if ( ! empty( $_POST[ $addon . '_' . intval( $_POST['plan'] ) ] ) ) {
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

class VA_Select_Plan_Existing extends VA_Select_Plan_New {

	public function __construct() {
		$this->setup( 'upgrade-listing', array(
			'priority' => 1,
			'register_to' => array( 'upgrade-listing' )
		) );
	}

	public function display( $order, $checkout ) {
		global $va_options;

		$listing = get_queried_object();
		$prior_plan = _va_get_last_plan_info( $listing->ID );

		if ( ! $prior_plan ) {
			$plans = $this->get_available_plans();
			appthemes_load_template( 'purchase-listing-existing-planless.php', array(
				'listing' => $listing,
				'plans' => $plans,
				'va_options' => $va_options
			) );
		} else {

			if ( $last_order = _va_get_pending_recurring_listing_order( $listing->ID ) ) {
				$recurring = true;
			} else {
				$recurring = false;
			}

			$plan_data = va_get_plan_options( $prior_plan['ID'] );
			appthemes_load_template( 'purchase-listing-existing.php', array(
				'listing' => $listing,
				'plan' => $plan_data,
				'recurring' => $recurring,
			) );
		}

	}

	public function process( $order, $checkout ) {

		if ( ! isset( $_POST['action'] ) || 'purchase-listing' != $_POST['action'] ) {
			return;
		}

		if ( ! current_user_can( 'edit_listings' ) ) {
			return;
		}

		$this->errors = apply_filters( 'appthemes_validate_purchase_fields', va_get_listing_error_obj() );

		$prior_plan = _va_get_last_plan_info( get_queried_object_id() );

		if ( ! $prior_plan ) {
			$addons = parent::get_addons();
			$plan_id = $this->get_plan();
		} else {
			$addons = $this->get_addons();
		}

		if ( $this->errors->get_error_codes() ) {
			return false;
		}

		if ( ! $prior_plan ) {
			va_add_plan_to_order( $order, get_queried_object_id(), $plan_id );
		}

		va_add_addons_to_order( $order, get_queried_object_id(), $addons );

		do_action( 'appthemes_create_order', $order );

		$this->finish_step();
	}

	protected function get_addons() {

		$listing = get_queried_object();
		$addons = parent::get_addons();

		foreach ( $addons as $k => $addon ) {
			if ( _va_already_featured( $addon, $listing->ID ) ) {
				unset( $addons[ $k ] );
			}
		}

		return $addons;
	}

}

function va_handle_claim_listing_purchase() {
	global $va_options;

	if ( ! isset( $_POST['action'] ) || 'claim-listing' != $_POST['action'] ) {
		return;
	}

	if ( ! current_user_can( 'edit_listings' ) ) {
		return;
	}

	check_admin_referer( 'va_claim_listing' );

	if ( ! $va_options->listing_charge ) {
		VA_Listing_Claim::handle_no_charge_claim_listing( $_POST['ID'] );
	}
}

function va_get_addon_options( $addon ) {
	global $va_options;

	$addon_data = $va_options->addons[ $addon ];
	$period = ( ! isset( $addon_data['period'] ) ) ? $addon_data['duration'] : $addon_data['period'];
	$period_type = ( ! isset( $addon_data['period_type'] ) ) ? APP_Order::RECUR_PERIOD_TYPE_DAYS : $addon_data['period_type'];

	return array(
		'title' => APP_Item_Registry::get_title( $addon ),
		'price' => appthemes_get_price( APP_Item_Registry::get_meta( $addon, 'price' ) ),
		'duration' => $addon_data['duration'],
		'period' => $period,
		'period_type' => $period_type,
	);

}

function _va_get_chosen_plan() {
	$plan = get_post( intval( $_POST['plan'] ) );
	if ( ! $plan ) {
		return false;
	}

	$plan->plan_data = va_get_plan_options( $plan->ID );

	return $plan;
}

function va_get_plan_options( $plan_id ) {

	$data = get_post_custom( $plan_id );
	$collapsed_data = array();
	foreach ( $data as $key => $array ) {
		$collapsed_data[ $key ] = $array[0];
	}

	$collapsed_data['ID'] = $plan_id;

	// In case that new addon meta is not stored yet:
	foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
		if ( ! empty( $collapsed_data[ $addon ] ) ) {
			if ( ! isset( $collapsed_data[ $addon . '_period'  ] ) ) {
				$collapsed_data[ $addon . '_period'  ] = $collapsed_data[ $addon . '_duration' ];
			}
			if ( ! isset( $collapsed_data[ $addon . '_period_type'  ] ) ) {
				$collapsed_data[ $addon . '_period_type'  ] = APP_Order::RECUR_PERIOD_TYPE_DAYS;
			}
		}
	}

	return $collapsed_data;
}

function va_get_plan_included_categories( $plan_id ) {
	global $va_options;

	$plan = va_get_plan_options( $plan_id );

	if ( ! isset( $plan['included_categories'] ) ) {
		return 0;
	} else {
		return $plan['included_categories'];
	}
}

/**
 * Shows the field for an addon that can be purchased
 */
function _va_show_purchasable_featured_addon( $addon_id, $plan_id ) {

	$plan = va_get_plan_options( $plan_id );

	$addon = va_get_addon_options( $addon_id );

	if ( ! empty( $plan[ $addon_id ] ) ) {
		_va_show_featured_option( $addon_id, true, $plan_id );
		if ( $plan[ $addon_id . '_duration' ] == 0 ) {
			$string = __( ' %s is included in this plan for Unlimited days.', APP_TD );
			printf( $string, $addon['title'], $addon['price'] );
		} else {
			$period_type = appthemes_get_recurring_period_type_display( $plan[ $addon_id . '_period_type'], $plan[ $addon_id . '_period'] );
			$string = __( ' %1$s is included in this plan for %2$s %3$s.', APP_TD );
			printf( $string, $addon['title'], $plan[ $addon_id . '_period'], $period_type );
		}

	} else if ( ! _va_addon_disabled( $addon_id ) ) {
		if ( $addon['duration'] <= $plan['duration'] || $plan['duration'] == 0 || $addon['duration'] == 0 ) {
			_va_show_featured_option( $addon_id, false, $plan_id );
			if ( $addon['duration'] == 0 ) {
				$string = __( ' %1$s for Unlimited days [Add %2$s].', APP_TD );
				printf( $string, $addon['title'], $addon['price'] );
			} else {
				$period_type = appthemes_get_recurring_period_type_display( $addon['period_type'], $addon['period'] );
				$string = __( ' %1$s for %2$s %3$s [Add %4$s].', APP_TD );
				printf( $string, $addon['title'],  $addon['period'], $period_type, $addon['price'] );
			}
		}
	}

}

function va_get_recurring_order_next_payment_date( $listing_id = 0 ) {
	global $va_locale;

	$listing_id = $listing_id ? $listing_id : get_the_ID();

	$recurring_order = _va_get_pending_recurring_listing_order( $listing_id );
	if ( ! $recurring_order ) {
		return;
	}

	return $va_locale->date( get_option( 'date_format' ), strtotime( get_post( $recurring_order->get_id() )->post_date ) );
}

function _va_get_recurring_order_addons( $listing_id ) {

	$recurring_order = _va_get_recurring_listing_order( $listing_id );
	if ( ! $recurring_order ) {
		return;
	}

	$order_info = _va_get_order_listing_info( $recurring_order );

	$included_addons = array();
	foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
		if ( ! empty( $order_info['plan_data'][ $addon ] ) ) {
			$included_addons[] = $addon;
		} else {
			foreach ( $recurring_order->get_items( $addon ) as $item ) {
				$included_addons[] = $addon;
			}
		}
	}

	return $included_addons;
}

/**
 * Shows the field for an addon that has already been purchased
 */
function _va_show_purchased_featured_addon( $addon_id, $plan_id, $listing_id ) {

	$plan = va_get_plan_options( $plan_id );
	$addon = va_get_addon_options( $addon_id );

	_va_show_featured_option( $addon_id, true, $plan_id );

	$recurring_order = _va_get_pending_recurring_listing_order( $listing_id );

	if ( $recurring_order ) {
		echo $addon['title'];
		return;
	}

	$expiration_date = va_get_featured_exipration_date( $addon_id, $listing_id );
	if ( __( 'Never', APP_TD ) == $expiration_date ) {
		printf( __( ' %s for Unlimited days', APP_TD ), $addon['title'] );
	} else {
		printf( __( ' %s until %s', APP_TD ), $addon['title'], $expiration_date );
	}
	return;

}

function _va_no_featured_available( $plan ) {
	if ( empty( $plan[ VA_ITEM_FEATURED_HOME ] ) && empty( $plan[ VA_ITEM_FEATURED_CAT ] ) ) {
		foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon_id ) {
			if ( _va_addon_disabled( $addon_id ) ) {
				continue;
			}

			$addon = va_get_addon_options( $addon_id );
			if ( $addon['duration'] <= $plan['duration'] || $plan['duration'] == 0 || $addon['duration'] == 0 ) {
				return false;
			}
		}
		return true;
	} else {
		return false;
	}
}

function _va_no_featured_purchasable( $plan, $listing ) {
	if ( _va_no_featured_available( $plan ) ) {
		return true;
	}

	if ( _va_get_pending_recurring_listing_order( $listing->ID ) ) {
		return true;
	}

	foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
		if ( ! _va_already_featured( $addon, $listing->ID ) && ! _va_addon_disabled( $addon ) ) {
			return false;
		}
	}
	return true;
}

function _va_already_featured( $addon, $listing_id ) {

	$meta = get_post_meta( $listing_id, $addon, true );
	if ( $meta ) {
		return true;
	} else {
		return false;
	}

}

function _va_addon_disabled( $addon ) {
	global $va_options;
	return empty( $va_options->addons[ $addon ]['enabled'] );
}

function _va_show_featured_option( $addon, $enabled = false, $plan_id = '' ) {

	$name = $addon;
	if( !empty( $plan_id ) )
		$name = $addon . '_' . $plan_id;

	echo html( 'input', array(
		'name' => $name,
		'type' => 'checkbox',
		'disabled' => $enabled,
		'checked' => $enabled
	) );
}

function va_get_claimed_listing( $listing_id = '' ) {
	$listing_id = ! empty( $listing_id ) ? $listing_id : get_queried_object_id();
	$args = array(
		'post_type' => VA_LISTING_PTYPE,
		'post_status' => array( 'publish' ),
		'post__in' => array( $listing_id ),
	);

	$query = new WP_Query( $args );
	return $query;
}

function va_get_renewed_listing( $listing_id = '' ) {
	$listing_id = ! empty( $listing_id ) ? $listing_id : get_queried_object_id();

	$args = array (
		'post_type' => VA_LISTING_PTYPE,
		'post_status' => array( 'expired' ),
		'post__in' => array( $listing_id ),
	);

	$query = new WP_Query( $args );
	return $query;
}

function _va_get_plan_recurring_option( $plan_id ) {
	$plan = va_get_plan_options( $plan_id );
	if ( isset( $plan['recurring'] ) ) {
		return $plan['recurring'];
	} else {
		return 'non_recurring';
	}
}

function _va_show_recurring_option( $plan_id ) {

	$recurring = _va_get_plan_recurring_option( $plan_id );
	$name = 'recurring_' . $plan_id;

	if ( $recurring == 'optional_recurring' ) {

		echo html( 'div', array( 'class' => 'recurring-option' ),
	 	html( 'label',
		html( 'input', array(
			'name' => $name,
			'type' => 'radio',
			'checked' => true,
			'value' => 'recurring',
		) ), __( 'Automatically charge my account to renew my listing', APP_TD ) ) );

		echo html( 'div', array( 'class' => 'recurring-option' ),
	 	html( 'label',
		html( 'input', array(
			'name' => $name,
			'type' => 'radio',
			'value' => 'non_recurring',
		) ), __( 'Do not automatically charge my account - I will renew manually', APP_TD ) ) );
	} elseif ( $recurring == 'forced_recurring' ) {
		echo html( 'div', array( 'class' => 'recurring-option' ),
	 	html( 'label',
		html( 'input', array(
			'name' => $name,
			'type' => 'radio',
			'value' => 'recurring',
			'checked' => 'checked',
		) ), __( 'Recurring', APP_TD ) ) );

	}
}

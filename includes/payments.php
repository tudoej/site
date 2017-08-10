<?php

define( 'APPTHEMES_PRICE_PLAN_PTYPE', 'pricing-plan' );

add_action( 'init', 'appthemes_pricing_setup' );
add_action( 'admin_menu', 'appthemes_pricing_add_menu', 11 );

if( is_admin() ){
	add_filter( 'the_title', 'appthemes_pricing_modify_title', 10, 2 );
}

add_action( 'appthemes_first_run', 'va_payments_setup_upgrade' );

function appthemes_pricing_setup(){

	$labels = array(
		'name' => __( 'Plans', APP_TD ),
		'singular_name' => __( 'Plans', APP_TD ),
		'add_new' => __( 'Add New', APP_TD ),
		'add_new_item' => __( 'Add New Plan', APP_TD ),
		'edit_item' => __( 'Edit Plan', APP_TD ),
		'new_item' => __( 'New Plan', APP_TD ),
		'view_item' => __( 'View Plan', APP_TD ),
		'search_items' => __( 'Search Plans', APP_TD ),
		'not_found' => __( 'No Plans found', APP_TD ),
		'not_found_in_trash' => __( 'No Plans found in Trash', APP_TD ),
		'parent_item_colon' => __( 'Parent Plan:', APP_TD ),
		'menu_name' => __( 'Plans', APP_TD ),
	);

	$args = array(
		'labels' => $labels,
		'hierarchical' => false,
		'supports' => array( 'page-attributes' ),
		'public' => false,
		'capability_type' => 'page',
		'show_ui' => true,
		'show_in_menu' => false,
	);

	register_post_type( APPTHEMES_PRICE_PLAN_PTYPE, $args );

	$plans = new WP_Query( array( 'post_type' => APPTHEMES_PRICE_PLAN_PTYPE, 'nopaging' => 1 ) );
	foreach ( $plans->posts as $plan ) {
		$data = get_post_custom( $plan->ID );
		if ( isset( $data['plan_title'] ) ) {
			APP_Item_Registry::register( $plan->post_name, $data['plan_title'][0] );
		}
	}
}

function appthemes_pricing_add_menu(){
	global $pagenow, $typenow;
	$ptype = APPTHEMES_PRICE_PLAN_PTYPE;
	$ptype_obj = get_post_type_object( $ptype );

	add_submenu_page( 'app-payments', $ptype_obj->labels->name, $ptype_obj->labels->all_items, $ptype_obj->cap->edit_posts, "edit.php?post_type=$ptype" );

	if($pagenow == 'post-new.php' && $typenow == $ptype) {
		add_submenu_page( 'app-payments', $ptype_obj->labels->new_item, $ptype_obj->labels->new_item, $ptype_obj->cap->edit_posts, "post-new.php?post_type=$ptype" );
	}
}

add_filter('parent_file', 'appthemes_pricing_menu_edit_page_menu_workaround');

function appthemes_pricing_menu_edit_page_menu_workaround($parent_file) {
	global $pagenow, $typenow;

	$ptype = APPTHEMES_PRICE_PLAN_PTYPE;
	$ptype_obj = get_post_type_object( $ptype );

	if($parent_file == "edit.php?post_type=$ptype" && ($pagenow == 'post.php' || $pagenow == 'post-new.php') && $typenow == $ptype) {
		return 'app-payments';
	}

	return $parent_file;
}

function appthemes_pricing_modify_title( $title, $post_id ) {

	$post = get_post( $post_id );
	if ( ! $post || $post->post_type != APPTHEMES_PRICE_PLAN_PTYPE ) {
		return $title;
	}

	return get_post_meta( $post_id, 'plan_title', true );
}

function va_payments_setup_upgrade() {

	list( $args ) = get_theme_support( 'app-versions' );
	$previous_version = get_option( $args['option_key'] );

	if ( version_compare( $previous_version, '1.1', '<' ) ) {
		appthemes_upgrade_item_addons();
	}
}

function va_list_gateway_dropdown() {
	$checkout_recurring = appthemes_get_checkout()->get_data( 'recurring' );
 	if ( 'recurring' == $checkout_recurring ) {
	 	$recurring = true;
 	} else {
	 	$recurring = false;
 	}

 	return appthemes_list_gateway_dropdown( 'payment_gateway', $recurring );
}
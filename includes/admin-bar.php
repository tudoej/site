<?php

add_filter( 'show_admin_bar', '__return_true', 999 );

add_action( 'wp_before_admin_bar_render', 'va_remove_admin_bar_links' );

add_action( 'admin_bar_menu', 'va_admin_bar_login_form', 25 );
add_action( 'admin_bar_menu', 'va_add_admin_bar_links', 90 );

add_filter( 'edit_profile_url', 'va_user_edit_profile_url', 10, 2 );

function va_admin_bar_login_form( $wp_admin_bar ) {
	if ( is_user_logged_in() )
		return;

	$wp_admin_bar->add_node( array(
		'id'     => 'bar-login',
		'parent' => false,
		'title'  => __( 'Login', APP_TD ),
		'meta' => array( 'class' => 'opposite' ),
		'href' => wp_login_url(),
	) );
	$wp_admin_bar->add_node( array(
		'id'     => 'bar-login-form',
		'title'  => '&nbsp;',
		'parent' => 'bar-login',
		'meta' => array( 
			'class' => '',
			'html' => '<div id="bar-login-form-cont">
							<form action="' . wp_login_url() . '" method="POST">
								<label for="log">' . __( 'Username', APP_TD ) . '</label>
								<input id="log" type="text" tabindex="20" name="log" autocomplete="off" />
								
								<label for="pwd">' . __( 'Password', APP_TD ) . '</label>
								<input name="pwd" type="password" tabindex="21" autocomplete="off" />

								<div class="forgetmenot">
									<input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="24" checked="checked" />
									<label class="rememberme" for="rememberme">' . __( 'Remember Me', APP_TD ) . '</label>
								</div>
								
								' . va_after_admin_bar_login_form() . '
								
								<div class="submit">
									<input type="submit" id="wp-submit" tabindex="23" name="login" value="' . __( 'Login', APP_TD ) . '" />
								</div>
								<div class="clear"></div>
							</form>
						</div>',
			),
		'href' => ''
	) );
	
	$wp_admin_bar->add_node( array(
		'id'     => 'lostpassword',
		'title'  => __( 'Lost Password?', APP_TD ),
		'parent' => 'bar-login',
		'meta' => array('tabindex'=> 5),
		'href' => appthemes_get_password_recovery_url()
	) );

	if ( get_option( 'users_can_register' ) ) {
		$wp_admin_bar->add_node( array(
			'id'     => 'register',
			'title'  => __( 'Register', APP_TD ),
			'href' => appthemes_get_registration_url(),
			'meta' => array( 'class' => 'opposite', 'tabindex'=> 5 ),
		) );
	}
}


function va_remove_admin_bar_links() {
	global $wp_admin_bar;

	if ( ! current_user_can( 'manage_options' ) ) {
		$wp_admin_bar->remove_node( 'my-sites' );
		$wp_admin_bar->remove_node( 'site-name' );
	}

	$wp_admin_bar->remove_node( 'wp-logo' );
	$wp_admin_bar->remove_node( 'new-content' );
	$wp_admin_bar->remove_node( 'edit' );
	$wp_admin_bar->remove_node( 'comments' );
	$wp_admin_bar->remove_node( 'search' );
}

function va_add_admin_bar_links( $wp_admin_bar ) {
	global $va_options;

	if ( !is_user_logged_in() )
		return;

	$listings_permalink = $va_options->dashboard_listings_permalink;
	$permalink = $va_options->dashboard_permalink;

	if ( get_option('permalink_structure') != '' ) {
		$url = home_url( user_trailingslashit( $permalink ) );
	} else {
		$url = home_url( '?dashboard='.$listings_permalink.'&dashboard_author=self' );
	}

	$wp_admin_bar->add_node( array(
		'id'     => 'va-dashboard',
		'parent' => false,
		'meta' => array( 'class' => 'opposite' ),
		'title'  => __( 'Dashboard', APP_TD ),
		'href'   => $url
	) );

	if ( !current_user_can( 'edit_others_listings' ) )
		return;

	$pending = wp_count_posts( VA_LISTING_PTYPE )->pending;

	if ( $pending ) {
		$title = sprintf( _n( '%s pending listing', '%s pending listings', $pending, APP_TD ), number_format_i18n( $pending ) );

		$url = admin_url( 'edit.php?post_status=pending&post_type=' . VA_LISTING_PTYPE );

		$wp_admin_bar->add_node( array(
			'id'     => 'va-pending-listings',
			'title'  => $title,
			'href'   => $url
		) );
	}

	$pending_claimed = wp_count_posts( VA_LISTING_PTYPE )->{'pending-claimed'};

	if ( $pending_claimed ) {
		$title = sprintf( _n( '%s pending claimed listing', '%s pending claimed listings', $pending_claimed, APP_TD ), number_format_i18n( $pending_claimed ) );

		$url = admin_url( 'edit.php?post_status=pending-claimed&post_type=' . VA_LISTING_PTYPE );

		$wp_admin_bar->add_node( array(
			'id'     => 'va-pending-claimed-listings',
			'title'  => $title,
			'href'   => $url
		) );
	}

}

function va_user_edit_profile_url( $url, $user_id ) {
	global $pagenow;

	if ( in_array( $pagenow, array( 'profile.php', 'users.php', 'user-edit.php', 'user-new.php' ) ) )
		return $url;

	if ( get_current_user_id() == $user_id ) {
		if ( $page_id = APP_User_Profile::get_id() )
			return get_permalink( $page_id );
	}

	return $url;
}


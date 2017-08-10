<?php
if ( !is_user_logged_in() ) {
	$message = __( 'To create a listing, you need to <a href="%s">login</a> or <a href="%s">register</a> first.', APP_TD );
	$login_url = site_url( 'wp-login.php', 'login' );
	$registration_url = site_url( 'wp-login.php?action=register', 'login' );
	$message = sprintf( $message, $login_url, $registration_url );
}
else {
	$message = __( 'It seems you do not have permission to create listings.', APP_TD );
}
?>

<div id="main">
<div class="notice error extra"><span><?php echo $message; ?></span></div>
</div><!-- /#main -->

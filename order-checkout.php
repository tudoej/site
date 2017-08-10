<div id="main">
<?php
	process_the_order();

	$order = get_order();
	if( in_array( $order->get_status(), array( APPTHEMES_ORDER_COMPLETED, APPTHEMES_ORDER_ACTIVATED ) ) ){
		$redirect_to = get_post_meta( $order->get_id(), 'complete_url', true );
		if( !empty( $redirect_to ) ){
			va_js_redirect( $redirect_to );
		}
	}

?>
</div>

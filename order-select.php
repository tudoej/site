<div id="main">
	<div class="section-head">
		<h1><?php _e( 'Order Summary', APP_TD ); ?></h1>
	</div>
	<div class="order-summary">
		<?php the_order_summary(); ?>
		<form action="<?php echo appthemes_get_step_url(); ?>" method="POST">
			<p><?php _e( 'Please select a method for processing your payment:', APP_TD ); ?></p>
			<?php va_list_gateway_dropdown(); ?>
			<input type="submit" value="<?php _e( 'Submit', APP_TD ); ?>">
		</form>
	</div>
</div>
<div id="main">
<?php do_action( 'appthemes_notices' ); ?>
<div class="section-head">
	<h1><?php _e( 'Create an Event', APP_TD ); ?></h1>
</div>
<form id="create-event" method="POST" action="<?php echo appthemes_get_step_url(); ?>">
	<fieldset>
		<div class="pricing-options">
			<div class="plan">
				<div class="content">
					<div class="title">
						<?php _e( 'Event', APP_TD ); ?>
					</div>
					<div class="categories">
						<?php 
						if ( !isset( $va_options->event_included_categories ) ) {
							printf( _n( 'Choose up to %d category', 'Choose up to %d categories', $va_options->event_included_categories, APP_TD ), $va_options->event_included_categories );
						} else if ( isset( $va_options->event_included_categories ) && 0 == $va_options->event_included_categories ) {
							_e( 'Choose unlimited categories!', APP_TD );
						} else {
							printf( _n( 'Choose up to %d category', 'Choose up to %d categories', $va_options->event_included_categories, APP_TD ), $va_options->event_included_categories );
						}
						?>
					</div>
					<div class="featured-options">
					<?php if( _va_event_no_featured_available() ) { ?>
						<div class="option-header">
							<?php _e( 'Featured Events are not available.', APP_TD ); ?>
						</div>
					<?php } else { ?>
						<div class="option-header">
							<?php _e( 'Please choose featured options:', APP_TD ); ?>
						</div>
						<?php foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) : ?>
							<div class="featured-option"><label>
								<?php _va_event_show_purchasable_featured_addon( $addon ); ?>
							</label></div>
						<?php endforeach; ?>
					<?php } ?>
					</div>
				</div>
				<div class="price-box">
					<div class="price">
						<?php appthemes_display_price( $va_options->event_price ); ?>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
	<fieldset>
		<?php do_action( 'appthemes_purchase_fields' ); ?>
		<input type="hidden" name="action" value="purchase-event">
		<div class="form-field">
			<input type="submit" value="<?php _e( 'Continue', APP_TD ) ?>" />
		</div>
	</fieldset>
</form>
</div>

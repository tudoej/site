<div id="main">
<div class="section-head">
	  <h1><?php _e( 'Pricing Options', APP_TD ); ?></h1>
</div>
<form id="create-event" class="purchase-event" method="POST" action="<?php echo appthemes_get_step_url(); ?>">
	<fieldset>
		<div class="pricing-options">
			<div class="plan">
				<div class="content">
					<div class="title">
						<?php _e( 'Event', APP_TD ); ?>
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
								<?php if( _va_already_featured( $addon, $event->ID ) ): ?>
									<?php _va_event_show_purchased_featured_addon( $addon ); ?>
								<?php else: ?>
									<?php _va_event_show_purchasable_featured_addon( $addon ); ?>
								<?php endif; ?>
							</label></div>
						<?php endforeach; ?>
					<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
	<?php if( !_va_event_no_featured_purchasable( $event ) ): ?>
	<fieldset>
		<input type="hidden" name="action" value="purchase-event">
		<input type="hidden" name="ID" value="<?php echo $event->ID; ?>">
		<div classess="form-field"><input type="submit" value="<?php _e( 'Continue', APP_TD ) ?>" /></div>
	</fieldset>
	<?php endif; ?>
</form>
</div>
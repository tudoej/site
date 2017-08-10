<div id="main">
	<div class="section-head">
		  <h1><?php _e( 'Pricing Options', APP_TD ); ?></h1>
	</div>
	<form id="create-listing" method="POST" action="<?php echo appthemes_get_step_url(); ?>">
		<fieldset>
			<div class="pricing-options">
				<div class="plan">

					<?php if ( $recurring ) { ?>
						<div class="content recurring">
							<div class="head">
								<h3><?php _e( 'Active Subscription', APP_TD ); ?></h3>
							</div>
							<div class="title"><?php echo $plan['plan_title']; ?></div>
							<div class="description"><?php echo $plan['description']; ?></div>
							<?php if ( _va_get_recurring_order_addons( $listing->ID ) ) { ?>
								<div class="featured-options">
									<div class="option-header"><?php _e( 'Included recurring featured options:', APP_TD ); ?></div>
									<?php foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) { ?>
										<div class="featured-option"><label>
											<?php if( _va_already_featured( $addon, $listing->ID ) ){ ?>
												<?php _va_show_purchased_featured_addon( $addon, $plan['ID'], $listing->ID ); ?>
											<?php } ?>
										</label></div>
									<?php } ?>
								</div><!-- /.featured-options -->
							<?php } ?>
						</div><!-- /.content -->
						<div class="price-box">
							<div class="price">
								<?php appthemes_display_price( $plan['price'] ); ?>
							</div>
							<div class="duration">
								<?php $period_type = appthemes_get_recurring_period_type_display( $plan['period_type'], $plan['period'] ); ?>
								<?php printf( __( 'every <br /> %1$s %2$s', APP_TD ), $plan['period'], $period_type ); ?>
							</div>
							<div class="radio-button">
								<label>
								<input readonly="readonly" checked="checked" type="radio" name="plan" value="<?php echo $plan['ID']; ?>" />
									<?php _e( 'You chose this option', APP_TD ); ?>
								</label>
							</div><!-- /.radio-button -->
							<div class="next-date"><?php printf( __( 'Next payment: %s', APP_TD ), va_get_recurring_order_next_payment_date( $listing->ID ) ); ?></div>
						</div><!-- /.pricebox -->
					<?php } else { ?>

						<div class="content">
							<div class="title"><?php echo $plan['plan_title']; ?></div>
							<div class="description"><?php echo $plan['description']; ?></div>
							<div class="featured-options">
							<?php if ( _va_no_featured_available( $plan ) ) { ?>
								<div class="option-header"><?php _e( 'Featured Listings are not available for this price plan.', APP_TD ); ?></div>
							<?php } else { ?>
								<div class="option-header"><?php _e( 'Please choose a feature option (none, one or multiple):', APP_TD ); ?></div>

								<?php foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) { ?>
									<div class="featured-option"><label>
										<?php if( _va_already_featured( $addon, $listing->ID ) ): ?>
											<?php _va_show_purchased_featured_addon( $addon, $plan['ID'], $listing->ID ); ?>
										<?php else: ?>
											<?php _va_show_purchasable_featured_addon( $addon, $plan['ID'] ); ?>
										<?php endif; ?>
									</label></div>
								<?php } ?>
							<?php } ?>
							</div><!-- /.featured-options -->
						</div><!-- /.content -->
						<div class="price-box">
							<div class="price">
								<?php appthemes_display_price( $plan['price'] ); ?>
							</div>
							<div class="duration">
								<?php if( $plan['duration'] != 0 ) {

									$recurring = _va_get_plan_recurring_option( $plan['ID'] );
									if ( 'forced_recurring' === $recurring ) {
										$period = sprintf( _n( 'every', 'every %d', $plan['period'], APP_TD ), $plan['period'] ) . '<br />';
										$period_type = appthemes_get_recurring_period_type_display( $plan['period_type'], $plan['period'] );
									} else {
										$period = $plan['period'];
										$period_type = appthemes_get_recurring_period_type_display( $plan['period_type'], $plan['period'] );
									}
									printf( __( 'for <br /> %1$s %2$s', APP_TD ), $period, $period_type );

								} else {
									_e( 'Unlimited</br> days', APP_TD );
								} ?>
							</div><!-- /.duration -->
							<div class="radio-button">
								<label>
								<input readonly="readonly" checked="checked" type="radio" name="plan" value="<?php echo $plan['ID']; ?>" />
									<?php _e( 'You chose this option', APP_TD ); ?>
								</label>
							</div><!-- /.radio-button -->
						</div><!-- /.price-box -->
					<?php } ?>
				</div>
			</div>
		</fieldset>
		<?php if ( !_va_no_featured_purchasable( $plan, $listing ) ) { ?>
		<fieldset>
			<input type="hidden" name="action" value="purchase-listing">
			<input type="hidden" name="ID" value="<?php echo $listing->ID; ?>">
			<div classess="form-field"><input type="submit" value="<?php _e( 'Continue', APP_TD ) ?>" /></div>
		</fieldset>
		<?php } ?>
	</form>
</div>

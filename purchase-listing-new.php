<div id="main">
	<?php do_action( 'appthemes_notices' ); ?>
	<div class="section-head">
		  <h1><?php _e( 'Select a Plan', APP_TD ); ?></h1>
	</div>
	<form id="create-listing" method="POST" action="<?php echo appthemes_get_step_url(); ?>">
		<fieldset>
			<div class="pricing-options">
				<?php if( !empty( $plans ) ) { ?>
					<?php foreach( $plans as $key => $plan ){ ?>
						<div class="plan">
							<div class="content">
								<div class="title">
									<?php echo $plan['plan_title']; ?>
								</div>
								<div class="description">
									<?php echo $plan['description']; ?>
								</div>
								<div class="categories">
									<?php
									if ( !isset( $plan['included_categories'] ) ) {
										printf( _n( 'Choose up to %d category', 'Choose up to %d categories', $va_options->included_categories, APP_TD ), $va_options->included_categories );
									} else if( isset( $plan['included_categories'] ) && 0 == $plan['included_categories'] ) {
										_e( 'Choose unlimited categories!', APP_TD );
									} else {
										printf( _n( 'Choose up to %d category', 'Choose up to %d categories', $plan['included_categories'], APP_TD ), $plan['included_categories'] );
									}
									?>
								</div>
								<div class="featured-options">
								<?php if( _va_no_featured_available( $plan ) ) { ?>
									<div class="option-header">
										<?php _e( 'Featured Listings are not available for this price plan.', APP_TD ); ?>
									</div>
								<?php } else { ?>
									<div class="option-header">
										<?php _e( 'Please choose additional featured options:', APP_TD ); ?>
									</div>
									<?php foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) : ?>
									<div class="featured-option"><label>
										<?php _va_show_purchasable_featured_addon( $addon, $plan['post_data']->ID ); ?>
									</label></div>
									<?php endforeach; ?>
								<?php } ?>
								</div>
								<div class="recurring-options">
								<?php $recurring = _va_get_plan_recurring_option( $plan['ID'] ); ?>
								<?php if ( 'optional_recurring' == $recurring ) { ?>
									<p class="recurring-description"><?php _e( 'Please choose a recurring payments option', APP_TD ); ?></p>
									<?php _va_show_recurring_option( $plan['ID'] ); ?>
								<?php } else if( 'forced_recurring' == $recurring) { ?>
									<p class="recurring-description"><?php _e( '* This plan includes recurring pricing. Your account will be automatically charged to renew your listing.', APP_TD ); ?></p>
								<?php } ?>

								</div>
							</div>
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
								</div>
								<div class="radio-button">
									<label>
										<input name="plan" type="radio" <?php echo ($key == 0) ? 'checked="checked"' : ''; ?> value="<?php echo $plan['post_data']->ID; ?>" />
										<?php _e( 'Choose this option', APP_TD ); ?>
									</label>
								</div>
							</div>
						</div>
					<?php } ?>
				<?php } else { ?>
					<em><?php _e( 'No Plans are currently available for this category. Please come back later.', APP_TD ); ?></em>
				<?php } ?>
			</div>
		</fieldset>
		<fieldset>
			<?php do_action( 'appthemes_purchase_fields' ); ?>
			<input type="hidden" name="action" value="purchase-listing">
			<div class="form-field">
				<?php if( !empty( $plans ) ){ ?>
					<input type="submit" value="<?php _e( 'Continue', APP_TD ) ?>" />
				<?php } ?>
			</div>
		</fieldset>
	</form>
</div>

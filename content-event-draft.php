<?php
	echo va_get_the_event_cal_thumbnail();
?>

<?php appthemes_before_post_title( VA_EVENT_PTYPE ); ?>
<h2 class="entry-title"><?php the_title(); ?></h2>
<?php appthemes_after_post_title( VA_EVENT_PTYPE ); ?>

<div class="added" style="display:none;"><?php _e( 'Updated:', APP_TD ); ?> <span class="date updated"><?php the_modified_time('Y-m-d'); ?></span></div>
<p class="vcard author" style="display:none;"><?php printf( __( 'Added by %s', APP_TD ), '<span class="fn">'. va_get_the_author_events_link() .'</span>' ); ?> </p>

<p class="event-cat"><?php the_event_categories(); ?></p>

<div class="content-event event-faves">
	<?php the_event_delete_link(); ?>
</div>

<p class="event-span"><?php echo va_get_the_event_days_span( '', '', ', ' ); ?></p>

<div id="event-address">
	<p class="event-address"><?php echo get_the_event_address(); ?></p>
	<p class="event-phone"><?php echo esc_html( get_post_meta( get_the_ID(), 'phone', true ) ); ?></p>
</div>

<p class="event-description"><strong><?php _e( 'Description:', APP_TD ); ?></strong> <?php the_excerpt(); ?> <?php echo html_link( get_permalink(), __( 'Read more...', APP_TD ) ); ?></p>

<div class="draft-notice">
	<?php _e( 'This event is waiting payment.', APP_TD ); ?>
	<?php va_the_payment_complete_actions(); ?>
</div>

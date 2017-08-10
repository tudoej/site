<?php global $va_options; ?>

<?php
	echo html( 'a', array(
		'href' => get_permalink( get_the_ID() ),
		'title' => esc_attr( get_the_title() ) . ' - ' . va_get_the_event_days_list(),
		'rel' => 'bookmark',
	), va_get_the_event_cal_thumbnail() );
?>

<?php appthemes_before_post_title( VA_EVENT_PTYPE ); ?>
<h2 class="entry-title" itemprop="name"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>

<?php appthemes_after_post_title( VA_EVENT_PTYPE ); ?>

<div class="added" style="display:none;"><?php _e( 'Updated:', APP_TD ); ?> <span class="date updated"><?php the_modified_time('Y-m-d'); ?></span></div>
<p class="vcard author" style="display:none;"><?php printf( __( 'Added by %s', APP_TD ), '<span class="fn">'. va_get_the_author_events_link() .'</span>' ); ?> </p>

<p class="event-cat"><?php the_event_categories(); ?></p>
<?php if ( function_exists('sharethis_button') && $va_options->event_sharethis ): ?>
	<div class="event-sharethis"><?php sharethis_button(); ?></div>
	<div class="clear"></div>
<?php endif; ?>
<div class="content-event event-faves">
	<?php the_event_faves_link(); ?>
	<?php the_event_delete_link(); ?>
</div>
<p class="event-span"><?php echo va_get_the_event_days_span( '', '', ', ' ); ?></p>

<meta itemprop="startDate" content="<?php esc_attr_e( date( 'Y-m-d', strtotime( get_post_meta( get_the_ID(), VA_EVENT_DATE_META_KEY, true ) ) ) ); ?>" />

<div id="event-address" itemprop="location" itemscope itemtype="http://schema.org/Place">
	<p class="event-address" itemprop="address"><?php echo get_the_event_address(); ?></p>
	<p class="event-phone" itemprop="telephone"><?php echo esc_html( get_post_meta( get_the_ID(), 'phone', true ) ); ?></p>
	<?php
	$coord = appthemes_get_coordinates( $post->ID );
	if ( 0 < $coord->lat ) {
	?>
	<div itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">
		<meta itemprop="latitude" content="<?php esc_attr_e( $coord->lat ); ?>" />
		<meta itemprop="longitude" content="<?php esc_attr_e( $coord->lng ); ?>" />
	</div>
	<?php } ?>
</div>

<p class="event-description"><strong><?php _e( 'Description:', APP_TD ); ?></strong> <?php the_excerpt(); ?> <?php echo html_link( get_permalink(), __( 'Read more...', APP_TD ) ); ?></p>

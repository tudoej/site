<?php global $va_options; ?>
<div id="main">

<?php do_action( 'appthemes_notices' ); ?>

<div class="section-head">
	<h1><?php echo $title; ?></h1>
</div>

<form id="create-event" enctype="multipart/form-data" method="post" action="<?php echo $form_action; ?>">
	<?php wp_nonce_field( 'va_create_event' ); ?>
	<input type="hidden" name="action" value="<?php echo ( get_query_var('event_edit') ? 'edit-event' : 'new-event' ); ?>" />
	<input type="hidden" name="ID" value="<?php echo esc_attr( $event->ID ); ?>" />

<fieldset id="essential-fields">
	<div class="featured-head"><h3><?php _e( 'Essential info', APP_TD ); ?></h3></div>

	<div class="form-field"><label>
		<?php _e( 'Title', APP_TD ); ?>
		<input name="post_title" type="text" value="<?php echo esc_attr( $event->post_title ); ?>" class="required" />
	</label></div>

	<div class="form-field">
		<?php $coord = appthemes_get_coordinates( $event->ID ); ?>
		<input name="lat" type="hidden" value="<?php echo esc_attr( $coord->lat ); ?>" />
		<input name="lng" type="hidden" value="<?php echo esc_attr( $coord->lng ); ?>" />

		<label>
			<?php _e( 'Address (street nr., street, city, state, country)', APP_TD ); ?>
			<input id="event-address" name="address" type="text" value="<?php echo esc_attr( $event->address ); ?>" class="required" />
		</label>
		<input id="event-find-on-map" type="button" value="<?php esc_attr_e( 'Find on map', APP_TD ); ?>">

		<div id="event-map"></div>

		<script type='text/javascript'>
		/* <![CDATA[ */
			jQuery(document).ready(function() {
				vantage_map_edit();
			});
		/* ]]> */
		</script>
	</div>

	<div class="form-field location">
		<label>
			<?php _e( 'Event Location', APP_TD ); ?>
			<input name="<?php echo VA_EVENT_LOCATION_META_KEY; ?>" type="text" value="<?php echo esc_attr( $event->{ VA_EVENT_LOCATION_META_KEY } ); ?>" />
		</label>
	</div>
	<div class="form-field location-url">
		<label>
			<?php _e( 'Event Location URL', APP_TD ); ?>
			<input name="<?php echo VA_EVENT_LOCATION_URL_META_KEY; ?>" type="text" value="<?php echo esc_attr( $event->{ VA_EVENT_LOCATION_URL_META_KEY } ); ?>" />
		</label>
	</div>
	<div class="form-field cost">
		<label>
			<?php _e( 'Event Cost', APP_TD ); ?>
			<input name="<?php echo VA_EVENT_COST_META_KEY; ?>" type="text" value="<?php echo esc_attr( $event->{ VA_EVENT_COST_META_KEY } ); ?>" />
		</label>
	</div>
</fieldset>
<fieldset id="event-days">
	<div class="featured-head"><h3><?php _e( 'Event Dates and Times', APP_TD ); ?></h3></div>
	<div class="form-field">
	<label for="_blank_event_day_0" class="error" style="display:none;"><?php /* This will get populated by an error message 'VA_i18n.error_event_date' */ ?></label>
	<?php va_event_day_time_selection_ui( $event->ID ); ?>
	</div>
</fieldset>

<fieldset id="category-fields">
	<div class="featured-head"><h3><?php printf( _n( 'Event Category', 'Event Categories', $included_categories, APP_TD ), $included_categories ); ?></h3></div>

	<div class="form-field" id="categories" data-category-limit="<?php echo esc_attr( $included_categories ); ?>">
		<?php
		if ( !isset( $included_categories ) || $categories_locked ) {
			$label = __( 'Categories', APP_TD );
		} else if ( $included_categories == 0 ) {
			$label = __( 'Categories (choose unlimited categories)', APP_TD);
		} else {
			$label = sprintf( _n( 'Category (choose %d category)', 'Categories (choose %d categories)', $included_categories, APP_TD ), $included_categories );
		}
		va_get_edit_categories( $event, $label, VA_EVENT_CATEGORY, $categories_locked );
		?>
	</div>

	<div id="custom-fields">
	<?php
	if ( !empty( $event->categories ) ) {
		the_files_editor( $event->ID, __( 'Event Files', APP_TD ) );

		va_event_render_form( $event->ID, $event->categories );
	}
	?>
	</div>
</fieldset>

<fieldset id="contact-fields">
	<div class="featured-head"><h3><?php _e( 'Contact info', APP_TD ); ?></h3></div>

	<div class="form-field phone"><label>
		<?php _e( 'Phone Number', APP_TD ); ?>
		<input name="phone" type="text" value="<?php echo esc_attr( $event->phone ); ?>" />
	</label></div>

	<div class="form-field event-urls web">
		<label>
			<?php _e( 'Website', APP_TD ); ?><br />
			<input name="website" type="text" value="<?php echo esc_attr( $event->website ); ?>" />
		</label>
    </div>

    <div class="form-field event-urls email">
		<label>
			<?php _e( 'Email', APP_TD ); ?>
			<input name="email" type="text" class="email" value="<?php echo esc_attr( $event->email ); ?>" />
		</label>
	</div>

	<?php foreach ( va_get_allowed_event_networks() as $social_network ) { ?>
	<div class="form-field event-urls <?php echo esc_attr( $social_network ); ?>">
		<label>
			<?php echo va_get_social_network_title( $social_network ); ?>
			<input name="<?php echo esc_attr( $social_network ); ?>" type="text" value="<?php echo esc_attr( va_get_post_social_account( $social_network, get_the_ID() ) ); ?>" />
		</label>
		<div class="field-tip"><?php echo va_get_social_network_tip( $social_network ); ?></div>
	</div>
	<?php } ?>

	<?php do_action( 'va_event_contact_fields_form', $event ); ?>

</fieldset>

<fieldset id="misc-fields">
	<div class="featured-head"><h3><?php _e( 'Additional info', APP_TD ); ?></h3></div>

	<div class="form-field images">
		<label><?php _e( 'Event Images', APP_TD ); ?></label>
		<?php the_listing_image_editor( $event->ID );  ?>
	</div>

	<div class="form-field">
		<label for="post_content"><?php _e( 'Event Description', APP_TD ); ?></label>
		<?php switch ( $va_options->editor_event ) {
				case 'html':
					va_event_editor( $event->post_content, 'post_content', array( 'tinymce' => false ) );
					break;
				case 'tmce':
					va_event_editor( $event->post_content, 'post_content' );
					break;
				default: ?>
					<textarea name="post_content" id="post_content"><?php echo esc_textarea( $event->post_content ); ?></textarea>
				<?php break;
		} ?>
	</div>

	<div class="form-field"><label>
		<?php _e( 'Tags', APP_TD ); ?>
		<input name="tax_input[<?php echo VA_EVENT_TAG; ?>]" type="text" value="<?php the_event_tags_to_edit( $event->ID ); ?>" />
	</label></div>
</fieldset>

<?php do_action( 'va_after_create_event_form' ); ?>

<fieldset>
	<div class="form-field"><input type="submit" value="<?php echo esc_attr( $action ); ?>" /></div>
</fieldset>

</form>

</div><!-- #content -->

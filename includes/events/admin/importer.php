<?php
/**
 * CSV Events Importer
 *
 * @package Vantage\Importer
 * @author  AppThemes
 * @since   Vantage 1.0
 */

add_action( 'wp_loaded', 'va_csv_events_importer' );
add_action( 'appthemes_after_import_upload_row', 'va_geocode_events_on_import_option' );
add_action( 'appthemes_importer_import_row_after', 'va_geocode_events_on_import', 10, 2 );
add_filter( 'appthemes_importer_import_row_after', 'va_set_imported_event_times', 10, 2 );
add_filter( 'appthemes_importer_import_row_after', 'va_set_import_event_meta_defaults', 11 );

function va_csv_events_importer() {
	$fields = array(
		'title'       => 'post_title',
		'description' => 'post_content',
		'author'      => 'post_author',
		'date'        => 'post_date',
		'slug'        => 'post_name',
		'status'      => 'post_status'
	);

	$args = array(
		'taxonomies' => array( VA_EVENT_CATEGORY, VA_EVENT_TAG ), // FIX THIS!!! THERE will need to be specific meta fields generated for this.

		'custom_fields' => array(
			'id'                 => 'va_id',
			'address'            => array(),
			'event_location'     => VA_EVENT_LOCATION_META_KEY,
			'event_location_url' => VA_EVENT_LOCATION_URL_META_KEY,
			'event_cost'         => VA_EVENT_COST_META_KEY,
			'phone'              => array(),
			'website'            => array(),
			'email'              => array(),
		),

		'geodata' => true,
		'attachments' => true,
	);

	foreach ( va_get_allowed_event_networks() as $social_network ) {
		$args['custom_fields'][ $social_network ] = array();
	}

	$args = apply_filters( 'va_csv_importer_args', $args );

	$importer = new VA_Importer( VA_EVENT_PTYPE, $fields, $args );
}


function va_geocode_events_on_import_option() {
	if ( empty( $_GET['page'] ) || $_GET['page'] !== 'app-importer-' . VA_EVENT_PTYPE  ) {
		return;
	}
	?>
		<tr>
			<th>
				<label><?php _e( 'Template', APP_TD ); ?></label>
			</th>
			<td>
				<?php echo sprintf( __( 'Download the  <a href="%s">sample file</a> (.csv) and open it in a spreadsheet program to get started.', APP_TD ), get_template_directory_uri() . '/examples/events.csv' ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<label><?php _e( 'Geocoding', APP_TD ); ?></label>
			</th>
			<td>
				<fieldset>
					<label><input type="checkbox" name="geocode_imported" value="1" /><?php _e( 'Geocode events on import', APP_TD ); ?></label>
				</fieldset>
				<p class="description"><?php _e( 'Limit of 2,500 requests per 24 hour period (unless you have a premium Google Maps API account).', APP_TD ); ?></p>
			</td>
		</tr>
	<?php
}

function va_geocode_events_on_import( $event_id, $row ) {

	if ( VA_EVENT_PTYPE != get_post_type( $event_id ) ) {
		return;
	}

	if ( empty( $_POST['geocode_imported'] ) ) {
		return;
	}

	if ( ! empty( $row['lat'] ) && ! empty( $row['lng'] ) ) {
		return;
	}

	va_geocode_address( $event_id );
}

function va_set_imported_event_times( $event_id, $row ) {

	if ( VA_EVENT_PTYPE != get_post_type( $event_id ) ) {
		return;
	}

	if ( empty( $row['event_date_time'] ) ) {
		return;
	}

	$dates_n_times = array_map( 'trim', explode( ',', $row['event_date_time'] ) );
	$days = array();
	$day_times = array();
	foreach ( $dates_n_times as $_time_string ) {
		$_time_string = array_map( 'trim', explode( '=', $_time_string ) );
		$day_times[ $_time_string[0] ] = $_time_string[1];
		$days[] = $_time_string[0];
		va_insert_event_day( $_time_string[0] );
	}

	update_post_meta( $event_id, VA_EVENT_DAY_TIMES_META_KEY, $day_times );

	wp_set_object_terms( $event_id, $days, VA_EVENT_DAY );

	asort( $days );
	update_post_meta( $event_id, VA_EVENT_DATE_META_KEY, reset( $days ) );
	update_post_meta( $event_id, VA_EVENT_DATE_END_META_KEY, end( $days ) );

}

function va_set_import_event_meta_defaults( $event_id ) {
	if ( VA_EVENT_PTYPE != get_post_type( $event_id ) ) {
		return;
	}

	return va_set_event_meta_defaults( $event_id );
}


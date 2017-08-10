<?php

add_action( 'admin_init', 'va_event_hide_meta' );
add_action( 'save_post', 'va_admin_set_event_meta_defaults', 10, 2 );

add_action( 'wp_ajax_vantage_single_event_geocode', 'va_handle_event_geocode_ajax' );

function va_handle_event_geocode_ajax() {
	if ( !isset( $_GET['address'] ) && (!isset( $_GET['lat'] ) && !isset( $_GET['lng'] )) )
		return;

	if( isset( $_GET['address'] ) ) {
		$api_response = va_geocode_address_api( $_GET['address'] );
	} else if( isset( $_GET['lat'] ) ) {
		$api_response = va_geocode_lat_lng_api( $_GET['lat'], $_GET['lng'] );
	}

	if ( !$api_response )
		die( "error" );

	die( json_encode( $api_response ) );
}

/**
 * Hides a list of metaboxes
 */
function va_event_hide_meta() {

	$remove_boxes = array( 'commentstatusdiv', 'commentsdiv', 'postexcerpt', 'revisionsdiv', 'authordiv' );
	foreach ( $remove_boxes as $id ) {
		remove_meta_box( $id, VA_EVENT_PTYPE, 'normal' );
	}
}

function va_admin_set_event_meta_defaults( $post_id, $post ) {
	if ( VA_EVENT_PTYPE !== $post->post_type ) return;

	if ( !wp_is_post_revision( $post_id ) )
		va_set_event_meta_defaults( $post_id );
}

class VA_Event_Location_Metabox extends APP_Meta_Box {

	public function __construct(){
		parent::__construct( 'event-location', __( 'Location', APP_TD ), VA_EVENT_PTYPE, 'normal' );
	}

	public function admin_enqueue_scripts() {
		appthemes_load_map_provider();
	}

	public function after_form( $post ) {

		echo html( 'input', array(
			'type' => 'button',
			'class' => 'button',
			'value' => __( 'Find on Map', APP_TD ),
			'name' => '_blank',
			'id' => 'event-find-on-map',
		));

		$coord = appthemes_get_coordinates( $post->ID );

		echo html( 'input', array(
			'type' => 'hidden',
			'value' => esc_attr( $coord->lat ),
			'name' => 'lat',
		));

		echo html( 'input', array(
			'type' => 'hidden',
			'value' => esc_attr( $coord->lng ),
			'name' => 'lng',
		));

		echo html( 'div', array(
			'id' => 'event-map',
		));
		?>
		<script>
			jQuery(function() {
				vantage_map_edit();
			});
		</script>
		<?php
	}

	public function form_fields(){

		return array(
			array(
				'title' => __( 'Address', APP_TD ),
				'type' => 'text',
				'name' => 'address',
				'extra' => array (
					'id' => 'event-address',
				)
			),
		);

	}

	public function before_save( $data, $post_id ) {

		appthemes_set_coordinates( $post_id, $_POST['lat'], $_POST['lng'] );

		return $data;
	}

}


class VA_Event_Custom_Forms_Metabox extends VA_Custom_Forms_Metabox {

	public function __construct() {
		parent::__construct( VA_EVENT_PTYPE, VA_EVENT_CATEGORY );
	}

}


class VA_Event_Attachments_Metabox extends APP_Post_Attachments_Metabox {

	public function __construct() {
		parent::__construct( 'event-attachments', __( 'Event Attachments', APP_TD ), VA_EVENT_PTYPE );
	}

}


class VA_Event_Contact_Metabox extends APP_Meta_Box {

	public function __construct(){
		parent::__construct( 'event-contact', __( 'Contact Information', APP_TD ), VA_EVENT_PTYPE, 'normal' );
	}

	public function form_fields(){

		$fields = array(
			array(
				'title' => __( 'Event Location', APP_TD ),
				'type'  => 'text',
				'name'  => VA_EVENT_LOCATION_META_KEY,
			),
			array(
				'title' => __( 'Event Location URL', APP_TD ),
				'type'  => 'text',
				'name'  => VA_EVENT_LOCATION_URL_META_KEY,
			),
			array(
				'title' => __( 'Event Cost', APP_TD ),
				'type'  => 'text',
				'name'  => VA_EVENT_COST_META_KEY,
			),
			array(
				'title' => __( 'Phone Number', APP_TD ),
				'type'  => 'text',
				'name'  => 'phone',
			),
			array(
				'title' => __( 'Website', APP_TD ),
				'type'  => 'url',
				'name'  => 'website',
			),
			array(
				'title' => __( 'Email', APP_TD ),
				'type'  => 'email',
				'name'  => 'email',
			),
		);

		foreach ( va_get_allowed_event_networks() as $social_network ) {
			$field = array(
				'title' => va_get_social_network_title( $social_network ),
				'tip'   => va_get_social_network_tip( $social_network ),
				'type'  => 'text',
				'name'  => $social_network,
			);

			$fields[] = $field;
		}

		return $fields;

	}

	function before_save( $data, $post_id ) {

		foreach ( va_get_event_contact_fields() as $field ) {
			if(!empty($data[$field]))
				$data[$field] = va_format_event_contact_fields($data[$field], $field);
		}

		return $data;
	}

}

class VA_Event_Dates_Metabox extends APP_Meta_Box{

	public function __construct(){
		parent::__construct( 'event-days', __( 'Event Days', APP_TD ), VA_EVENT_PTYPE, 'normal', 'default' );
	}

	public function admin_enqueue_scripts(){
		if( is_admin() ){
			wp_enqueue_style( 'jquery-ui-style' );
			wp_enqueue_style( 'wp-jquery-ui-datepicker', APP_FRAMEWORK_URI . '/styles/datepicker/datepicker.css' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
		}
	}

	public function display( $post ) {
		$this->before_form( $post );
	}

	public function before_form( $post ) {
		global $va_locale;

		$date_format = get_option('date_format', 'm/d/Y');

		// build js array of localized month names for date dropdown for jQuery UI datepicker, like:
		// monthNames: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
		$months = array();
		for ( $x=1;$x<=12;$x++ )
			$months[] = '"' . $va_locale->get_month($x) . '"';

		$monthNames = '[' . implode(', ', $months) . ']';

		// 	dayNamesShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
		$short_days = array();
		for ( $x=0;$x<=6;$x++ )
			$short_days[] = '"' . $va_locale->get_weekday_abbrev( $va_locale->get_weekday($x) ). '"';

		$dayNamesShort = '[' . implode(', ', $short_days) . ']';

		// dayNamesMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
		$min_days = array();
		for ( $x=0;$x<=6;$x++ )
			$min_days[] = '"' . $va_locale->get_weekday_short_abbrev( $va_locale->get_weekday($x) ). '"';

		$dayNamesMin = '[' . implode(', ', $min_days) . ']';

		$firstDay = get_option( 'start_of_week' );

		switch ( $date_format ) {
			case "m/d/Y":
			case "n/j/Y":
				$ui_display_format = 'mm/dd/yy';
				$display_date_format = 'm/d/Y';
			break;
			case "d/m/Y":
			case "j/n/Y":
				$ui_display_format = 'dd-mm-yy';
				$display_date_format = 'd-m-Y';
			break;
			case "Y/m/d":
			case "Y/n/j":
			default:
				$ui_display_format = 'yy-mm-dd';
				$display_date_format = 'Y-m-d';
			break;
		}

		$days = va_get_the_event_days();
		?>
		<table class="form-table" id="event_days">
			<thead>
				<tr>
					<th><?php _e( 'Date', APP_TD ); ?></th>
					<th><?php _e( 'Start Time', APP_TD ); ?></th>
					<th><?php _e( 'End Time', APP_TD ); ?></th>
				</tr>
			</thead>
			<tbody>
		<?php
		$options = va_make_event_time_select_options();
		$day_count = 0;
		if ( !empty( $days ) ) {
			foreach( $days as $date_U => $term ) {
				$_date = $term->name;
				$times = va_get_the_event_day_times($_date);

				$date = mysql2date( $display_date_format, $_date );
				?>

				<tr class="event_day" id="event_day_<?php echo $day_count; ?>">
					<td class="date">
						<label style="display:none;">
							<input type="text" class="_event_day regular-text" id="_event_day_<?php echo $day_count; ?>" name="_event_day[<?php echo $day_count; ?>][date]" value="<?php echo esc_attr( $date ); ?>" />
						</label>
						<label>
							<input type="text" class="regular-text" id="_blank_event_day_<?php echo $day_count; ?>" name="_event_day[<?php echo $day_count; ?>][_blank_date]" value="<?php echo esc_attr( $date ); ?>" />
						</label>
					</td>
					<td class="time-start">
						<label>
							<input type="text" class="event_day_time" name="_event_day[<?php echo $day_count; ?>][time_start]" value="<?php echo esc_attr( $times['start_time'] ); ?>" / >
						</label>
					</td>
					<td class="time-end">
						<label>
							<input type="text" class="event_day_time" name="_event_day[<?php echo $day_count; ?>][time_end]" value="<?php echo esc_attr( $times['end_time'] ); ?>" / >
						</label>
					</td>
					<td class="delete">
						<?php if ( $day_count > 0 ) { ?>
						<span class="ui-icon ui-icon-circle-minus"></span>
						<?php } ?>
					</td>
				</tr>
				<?php

				$day_count++;
			}
		} else {
		?>
				<tr class="event_day" id="event_day_<?php echo $day_count; ?>">
					<td class="date">
						<label style="display:none;">
							<input type="text" class="_event_day regular-text" id="_event_day_0" name="_event_day[0][date]" value="" />
						</label>
						<label>
							<input type="text" class="regular-text" id="_blank_event_day_0" name="_event_day[0][_blank_date]" value="" />
						</label>
					</td>
					<td class="time-start">
						<label>
							<input type="text" class="event_day_time" name="_event_day[0][time_start]" value="" / >
						</label>
					</td>
					<td class="time-end">
						<label>
							<input type="text" class="event_day_time" name="_event_day[0][time_end]" value="" / >
						</label>
					</td>
					<td>
					</td>
				</tr>
		<?php
		}
		?>
			</tbody>
		</table>
		<input type="button" id="add_day" class="button" value="<?php _e( 'Add Day', APP_TD ); ?>" />
		<script type="text/javascript">

			jQuery(function($){

				$("#event_days").on({
					click: function(){
						$(this).parents('.event_day').remove();
					},
					mouseenter: function(){
						$(this).addClass('ui-state-hover');
					},
					mouseleave: function(){
						$(this).removeClass('ui-state-hover');
					}} , ".delete .ui-icon-circle-minus" );

				$('#add_day').click(function(){

					var new_event_day_id = parseInt( $('.event_day').last().attr('id').split('_')[2] ) + 1;

					$('#event_days > tbody').append('<tr class="event_day" id="event_day_'+new_event_day_id+'">\
						<td class="date">\
							<label style="display:none;">\
								<input type="text" class="_event_day regular-text" id="_event_day_'+new_event_day_id+'" name="_event_day['+new_event_day_id+'][date]" />\
							</label>\
							<label>\
								<input type="text" class="regular-text" id="_blank_event_day_'+new_event_day_id+'" name="_event_day['+new_event_day_id+'][_blank_date]" />\
							</label>\
						</td>\
						<td class="time-start">\
							<label>\
								<input type="text" class="event_day_time" name="_event_day['+new_event_day_id+'][time_start]" value="" / >\
							</label>\
						</td>\
						<td class="time-end">\
							<label>\
								<input type="text" class="event_day_time" name="_event_day['+new_event_day_id+'][time_end]" value="" / >\
							</label>\
						</td>\
						<td class="delete">\
							<span class="ui-icon ui-icon-circle-minus"></span>\
						</td>\
					</tr>');

					$( "#_blank_event_day_"+new_event_day_id ).datepicker({
						monthNames: <?php echo $monthNames; ?>,
						firstDay: <?php echo $firstDay; ?>,
						dayNamesShort: <?php echo $dayNamesShort; ?>,
						dayNamesMin: <?php echo $dayNamesMin; ?>,
						dateFormat: "<?php echo $ui_display_format; ?>",
						altField: "#_event_day_"+new_event_day_id,
						altFormat: "mm/dd/yy"
					});
				});

			<?php
				if ( !empty( $days ) ) {
					$day_count = 0;
					foreach( $days as $date_U => $term ) {
					?>
						$( "#_blank_event_day_<?php echo $day_count; ?>" ).datepicker({
							monthNames: <?php echo $monthNames; ?>,
							firstDay: <?php echo $firstDay; ?>,
							dayNamesShort: <?php echo $dayNamesShort; ?>,
							dayNamesMin: <?php echo $dayNamesMin; ?>,
							dateFormat: "<?php echo $ui_display_format; ?>",
							altField: "#_event_day_<?php echo $day_count; ?>",
							altFormat: "mm/dd/yy"
						});
					<?php
					$day_count++;
					}
				} else {
					?>
						$( "#_blank_event_day_0" ).datepicker({
							monthNames: <?php echo $monthNames; ?>,
							firstDay: <?php echo $firstDay; ?>,
							dayNamesShort: <?php echo $dayNamesShort; ?>,
							dayNamesMin: <?php echo $dayNamesMin; ?>,
							dateFormat: "<?php echo $ui_display_format; ?>",
							altField: "#_event_day_0",
							altFormat: "mm/dd/yy"
						});
					<?php
				}
			?>
			});
		</script>
		<?php

	}

	function before_save( $data, $post_id ) {

		$days = array();
		$day_times = array();
		if ( !empty( $_POST['_event_day'] ) ) {
			foreach ( $_POST['_event_day'] as $day ) {
				$date = date( 'Y-m-d', strtotime( $day['date'] ) );

				$times = $day['time_start'].'-'.$day['time_end'];
				$day_times[ $date ] = $times;
				$days[] = $date;
				va_insert_event_day( $date );
			}

			update_post_meta( $post_id, VA_EVENT_DAY_TIMES_META_KEY, $day_times );

			wp_set_object_terms( $post_id, $days, VA_EVENT_DAY );

			asort( $days );
			update_post_meta( $post_id, VA_EVENT_DATE_META_KEY, reset( $days ) );
			update_post_meta( $post_id, VA_EVENT_DATE_END_META_KEY, end( $days ) );
		}

		return $data;
	}
}

class VA_Event_Comments_Status_Metabox extends APP_Meta_Box {

	public function __construct(){
		parent::__construct( 'event-comments', __( 'Comments Status', APP_TD ), VA_EVENT_PTYPE );
	}

	public function display( $post ) {

		$form_fields = $this->form_fields();

		$form_data = array(
			'comment_status' => ( $post->comment_status=='open' ? 'open' : '' )
		);

		$form = $this->table( $form_fields, $form_data );

		echo $form;

	}

	public function form_fields() {
		return array(
			array(
				'title' => __( 'Enable Comments to be submitted on this listing?', APP_TD ),
				'type' => 'checkbox',
				'name' => 'comment_status',
				'desc' => __( 'Yes', APP_TD ),
				'value' => 'open',
			),
		);
	}

	function save( $post_id ) {
		// Handled by WordPress
	}
}

class VA_Event_Publish_Moderation_Metabox extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'event-publish-moderation', __( 'Moderation Queue', APP_TD ), VA_EVENT_PTYPE, 'side', 'high' );
	}

	function condition() {
		return ( isset( $_GET['post'] ) && get_post_status( $_GET['post'] ) == 'pending' );
	}

	public function display( $post ) {

		echo html( 'p', array(), __( 'You must approve this event before it can be published.', APP_TD ) );

		echo html( 'input', array(
			'type' => 'submit',
			'class' => 'button-primary',
			'value' => __( 'Accept', APP_TD ),
			'name' => 'publish',
			'style' => 'padding-left: 30px; padding-right: 30px; margin-right: 20px; margin-left: 15px;',
		));

		echo html( 'a', array(
			'class' => 'button',
			'style' => 'padding-left: 30px; padding-right: 30px;',
			'href' => get_delete_post_link($post->ID),
		), __( 'Reject', APP_TD ) );

		echo html( 'p', array(
				'class' => 'howto'
			), __( 'Rejecting an Event sends it to the trash.', APP_TD ) );

	}
}


class VA_Event_Author_Metabox extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'eventauthordiv', __( 'Author', APP_TD ), VA_EVENT_PTYPE, 'side', 'low' );
	}

	public function display( $post ) {
		global $user_ID;
		?>
		<label class="screen-reader-text" for="post_author_override"><?php _e('Author'); ?></label>
		<?php
		wp_dropdown_users( array(
			/* 'who' => 'authors', */
			'name' => 'post_author_override',
			'selected' => empty($post->ID) ? $user_ID : $post->post_author,
			'include_selected' => true
		) );
	}
}

class VA_Event_Featured_Metabox extends APP_Meta_Box {

	public function __construct(){
		parent::__construct( 'event-featured', __( 'Featured Information', APP_TD ), VA_EVENT_PTYPE, 'normal', 'low' );
	}
	public function form_fields(){

		$output = array();
		foreach( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ){

			$addon_output = array(
				'title' => APP_Item_Registry::get_title( $addon ),
				'type' => 'checkbox',
				'name' => $addon,
				'desc' => __( 'Yes', APP_TD ),
				"extra" => array(
					"id" => $addon,
				)
			);
			$output = array_merge( $output, array( $addon_output ));

		}

		return $output;
	}

	function before_save( $data, $post_id ){
		$data['featured'] = 0;

		foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
			if ( $data[$addon] ) {
				$data['featured'] = 1;
			}
		}

		return $data;
	}

}


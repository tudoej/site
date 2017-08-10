<?php

function get_the_event_address( $event_id = '' ) {
	$event_id = !empty( $event_id ) ? $event_id : get_the_ID();

	$html = '';
	$address = get_post_meta( $event_id, 'address', true );
	$location = get_post_meta( $event_id, VA_EVENT_LOCATION_META_KEY, true );
	$location_url = get_post_meta( $event_id, VA_EVENT_LOCATION_URL_META_KEY, true );

	if ( ! empty( $location ) ) {
		if ( ! empty( $location_url ) ) {
			$html .= html( 'div', array( 'class' => 'location location-link', 'itemprop' => 'address' ), html( 'a', array( 'href' => esc_url( $location_url ), 'target' => '_blank', 'itemprop' => 'url' ), $location ) );
		} else {
			$html .= html( 'div', array( 'class' => 'location', 'itemprop' => 'address' ), $location );
		}
	}

	if ( ! empty( $address ) ) {
		$html .= html( 'div', array( 'class' => 'address', 'itemprop' => 'address' ), $address );
	}

	return $html;
}

function get_the_event_cost( $event_id = '', $before = '', $after = '' ) {
	$event_id = !empty( $event_id ) ? $event_id : get_the_ID();

	$cost = get_post_meta( $event_id , VA_EVENT_COST_META_KEY, true );
	if ( !empty( $cost ) ) {
		return $before . $cost . $after;
	}
}

function the_event_tags( $before = null, $sep = ', ', $after = '' ) {
	if ( null === $before )
		$before = __( 'Tags: ', APP_TD );
	echo get_the_term_list( 0, VA_EVENT_TAG, $before, $sep, $after );
}

function the_event_category( $event_id = 0 ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'the_event_categories()' );
	return the_event_categories( $event_id );
}

function the_event_categories( $event_id = 0 ) {

	$event_id = $event_id ? $event_id : get_the_ID();

	$cats = get_the_event_categories( $event_id );
	if ( !$cats ) return;

	$_cats = array();

	foreach($cats as $cat) {
		$_cats[] = html_link( get_term_link( $cat ), $cat->name );
	}

	$cats_list = implode( ', ', $_cats);

	printf( __( 'Categories: %s', APP_TD ), $cats_list );

}

function va_event_render_form( $event_id, $categories ) {
	$event_categories = array();

	if ( is_array( $categories ) ) {
		$event_categories = array_keys( $categories );
	} else {
		$event_categories[] = $categories;
	}

	va_render_form( $event_categories, VA_EVENT_CATEGORY, $event_id );
}

function the_event_fields( $event_id = 0 ) {
	$event_id = $event_id ? $event_id : get_the_ID();

	$cats = array_keys( get_the_event_categories( $event_id ) );
	if ( !$cats )
		return;

	$fields = array();
	foreach($cats as $cat){
		foreach ( va_get_fields_for_cat( $cat, VA_EVENT_CATEGORY ) as $field ) {
			$fields[$field['name']] = $field;
		}
	}

	foreach( $fields as $field ) {
		if ( 'checkbox' == $field['type'] ) {
			$value = implode( ', ', get_post_meta( $event_id, $field['name'] ) );
		} else {
			$value = get_post_meta( $event_id, $field['name'], true );
		}

		if ( !$value )
			continue;

		$field['id_tag'] = va_make_custom_field_id_tag( $field['name'] );

		echo html( 'p', array('class' => 'event-custom-field', 'id' => $field['id_tag']),
			html('span', array('class' => 'custom-field-label'), $field['desc'] ). html('span', array('class' => 'custom-field-sep'), ': ' ) . html('span', array('class' => 'custom-field-value'), $value ) );
	}
}

function get_the_event_category( $event_id = 0 ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'get_the_event_categories()' );
	return get_the_event_categories( $event_id );
}

function get_the_event_categories( $event_id = 0 ) {
	$event_id = $event_id ? $event_id : get_the_ID();

	$_terms = get_the_terms( $event_id, VA_EVENT_CATEGORY );

	if ( !$_terms )
		return array();

	// WordPress does not always key with the term_id, but thats what we want for the key.
	$terms = array();
	foreach( $_terms as $_term ) {
		$terms[$_term->term_id] = $_term;
	}

	return $terms;
}

function the_event_edit_link( $event_id = 0, $text = '' ) {
	$event_id = $event_id ? $event_id : get_the_ID();

	if ( !current_user_can( 'edit_post', $event_id ) )
		return;

	if( empty( $text ) )
		$text = __( 'Edit Event', APP_TD );

	echo html( 'a', array(
		'class' => 'event-edit-link',
		'href' => va_get_event_edit_url( $event_id ),
	), $text );
}

function va_get_event_edit_url( $event_id ) {
	global $wp_rewrite, $va_options;

	if ( $wp_rewrite->using_permalinks() ) {
		$event_permalink = $va_options->event_permalink;
		$permalink = $va_options->edit_event_permalink;
		return home_url( user_trailingslashit( "$event_permalink/$permalink/$event_id" ) );
	}

	return home_url( "?event_edit=$event_id" );
}

function the_event_purchase_link( $event_id = 0, $text = '' ) {
	global $va_options;

	if( ! $va_options->event_charge )
		return;

	if( !va_any_featured_addon_enabled() )
		return;

	$event_id = $event_id ? $event_id : get_the_ID();

	if ( !current_user_can( 'edit_post', $event_id ) )
		return;

	if( empty( $text ) )
		$text = __( 'Upgrade Event', APP_TD );

	echo html( 'a', array(
		'class' => 'event-edit-link',
		'href' => va_get_event_purchase_url( $event_id ),
	), $text );
}

function va_get_event_purchase_url( $event_id ) {
	global $wp_rewrite, $va_options;

	if ( $wp_rewrite->using_permalinks() ) {
		$event_permalink = $va_options->event_permalink;
		$permalink = $va_options->purchase_event_permalink;
		return home_url( user_trailingslashit( "$event_permalink/$permalink/$event_id" ) );
	}

	return home_url( "?event_purchase=$event_id" );
}

function the_event_faves_link( $event_id = 0 ) {
	$event_id = $event_id ? $event_id : get_the_ID();
	va_display_event_fave_button( $event_id );
}

function va_get_the_attendee_count( $event_id = 0 ) {
	$event_id = $event_id ? $event_id : get_the_ID();
	return max( 0, get_post_meta( $event_id , VA_EVENT_ATTENDEES_META_KEY, true ) );
}

function va_get_the_event_days( $event_id = 0 ) {
	$event_id = $event_id ? $event_id : get_the_ID();

	$terms = get_the_terms( $event_id, VA_EVENT_DAY );
	if ( !$terms )
		return array();

	$days = array();
	foreach ( $terms as $term_id => $term ) {
		if ( $term->parent != 0 ) {
			$days[ strtotime($term->slug) ] = $term;
		}
	}

	return $days;
}

function va_get_the_event_days_list( $event_id = 0, $date_format = '', $sep = ', ' ) {
	global $va_locale;

	$event_id = $event_id ? $event_id : get_the_ID();

	$date_format = !empty( $date_format ) ? $date_format : get_option( 'date_format' );

	$days = array();
	$event_days = va_get_the_event_days( $event_id );
	foreach ( $event_days as $day )
		$days[] = $va_locale->date( $date_format, strtotime( $day->slug ) );

	return implode( $sep, $days );
}

function va_get_the_event_days_span( $event_id = 0, $date_format = '', $sep = ' - ' ) {
	global $va_locale;

	$event_id = $event_id ? $event_id : get_the_ID();

	$event_days = va_get_the_event_days( $event_id );

	if ( empty( $event_days ) )
		return;

	$date_format = !empty( $date_format ) ? $date_format : get_option( 'date_format' );

	if ( count( $event_days ) == 1 ) {
		$span = $va_locale->date( $date_format, strtotime( reset( $event_days )->slug ) );
		return $span;
	}

	$dates = array();
	foreach ( $event_days as $event_day ) {
		$dates[] = $va_locale->date( $date_format, strtotime( $event_day->slug ) );
	}

	$span = implode( $sep, $dates );
	return $span;
}

function va_get_the_event_day( $event_id = 0, $date_format = '') {
	global $va_locale;

	$event_id = $event_id ? $event_id : get_the_ID();

	$date_format = !empty( $date_format ) ? $date_format : get_option( 'date_format' );

	$event_days = va_get_the_event_days( $event_id );

	if ( empty( $event_days ) ) return;

	return $va_locale->date( $date_format, strtotime( reset( $event_days )->slug ) );
}

function va_get_the_event_day_times( $date, $event_id = 0 ) {
	$event_id = $event_id ? $event_id : get_the_ID();

	$times = get_post_meta( $event_id, VA_EVENT_DAY_TIMES_META_KEY, true );

	if ( empty( $times[ $date ] ) )
		return false;

	$time = explode( '-', $times[ $date ] );

	return array( 'start_time' => $time[0], 'end_time' => $time[1], 'span' => $times[ $date ] );
}

function va_get_the_event_day_time( $times, $sep = ' - ', $before = '', $after = '' ) {
	$time_span = '';

	if ( !empty( $times['start_time'] ) && !empty( $times['end_time'] ) ) {
		$time_span = $before . $times['start_time'] . $sep . $times['end_time'];
	} else {
		$time_span .= !empty( $times['start_time'] ) || !empty( $times['end_time'] ) ? $before : '';
		$time_span .= !empty( $times['start_time'] ) ? $times['start_time'] : '';
		$time_span .= !empty( $times['end_time'] ) ? $times['end_time'] : '';
		$time_span .= !empty( $times['start_time'] ) || !empty( $times['end_time'] ) ? $after : '';
	}

	return $time_span;
}

function va_get_the_event_cal_thumbnail( $event_id = 0 ) {
	global $va_locale;

	$event_id = $event_id ? $event_id : get_the_ID();

	$days = va_get_the_event_days();
	if ( empty( $days ) ) {
		$day = html( 'div', array( 'class' => 'month' ), '' );
		$month = html( 'div', array( 'class' => 'day' ), '?' );
		return html( 'div', array( 'class' => 'event-cal-thumb blank-date' ), $month . $day );
	}

	$days_count = count( $days );
	$today = time();

	// filter all past dates except latest
	foreach ( $days as $key => $day ) {
		if ( ( $key + 86399 < $today ) && 1 < count( $days ) ) {
			unset( $days[ $key ] );
		}
	}

	$days = reset( $days );
	$day = html( 'div', array( 'class' => 'month' ), strtoupper( $va_locale->date( 'M' , strtotime( $days->slug ) ) . '.' ) );
	$month = html( 'div', array( 'class' => 'day' ),  date( 'd' , strtotime( $days->slug ) ) );
	$days_class = $days_count > 1 ? ' multi' : '';
	return html( 'div', array( 'class' => 'event-cal-thumb' . $days_class ), $month . $day );
}

function get_the_contact_event_organizer_button( $event_id = 0 ) {
	$args = array(
		'button_text' => __( 'Contact Event Organizer' , APP_TD ),
		'form_title' => __( 'Contact the Event Organizer', APP_TD ),
		'form_title_helper' => __( 'To inquire about this event, complete the form below to send a message to the event organizer.', APP_TD ),
	);

	return va_contact_post_author_button( $event_id, $args );
}

function the_contact_event_organizer_button( $event_id = 0 ) {
	echo get_the_contact_event_organizer_button( $event_id );
}

function va_get_event_attendees( $event_id = 0 ) {
	$event_id = $event_id ? $event_id : get_the_ID();
	$users = get_users( array(
		'connected_type' => VA_EVENT_ATTENDEE_CONNECTION,
		'connected_from' => $event_id
	) );

	return $users;
}

function get_the_search_post_type() {

	$options = array(
		VA_LISTING_PTYPE => __( 'Businesses', APP_TD ),
		VA_EVENT_PTYPE => __( 'Events', APP_TD ),
	);

	$data = array( 'st' => get_query_var( 'st' ) );

	if ( empty( $data['st'] ) && ( is_tax( VA_EVENT_CATEGORY ) || is_tax( VA_EVENT_TAG ) || is_tax( VA_EVENT_DAY ) || is_singular( VA_EVENT_PTYPE ) ) ) {
		$data['st'] = VA_EVENT_PTYPE;
	}

	$data['st'] = !empty( $data['st'] ) ? $data['st'] : VA_LISTING_PTYPE;

	return scbForms::input( array(
		'type' => 'radio',
		'name' => 'st',
		'values' => $options
	), $data );
}

function va_event_time_select( $title, $name, $values, $current_value = '' ) {

	$select = array(
		'title' => $title,
		'type' => 'select',
		'name' => $name,
		'values' => $values,
		'extra' => array(
			'class' => 'event_day_time'
		)
	);

	return scbForms::input_with_value( $select, $current_value );
}

function va_js_redirect_to_event( $event_id, $query_args = array() ) {
	va_js_redirect_to_listing( $event_id, $query_args );
}

function va_event_day_time_selection_ui( $event_id = 0 ) {
	global $va_locale;

	$event_id = $event_id ? $event_id : get_the_ID();
	// !TODO - Migrate this into being used by admin/event-single.php

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

	$days = va_get_the_event_days( $event_id );

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
						<input type="text" class="_event_day regular-text" id="_event_day_<?php echo $day_count; ?>" name="_event_day[<?php echo esc_attr( $day_count ); ?>][date]" value="<?php echo $date; ?>" />
					</label>
					<label>
						<?php $required = $day_count == 0 ? 'required ' : ''; ?>
						<input type="text" class="<?php echo $required; ?>regular-text" id="_blank_event_day_<?php echo $day_count; ?>" name="_event_day[<?php echo esc_attr( $day_count ); ?>][_blank_date]" value="<?php echo $date; ?>" />
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
						<input type="text" class="required regular-text" id="_blank_event_day_0" name="_event_day[0][_blank_date]" value="" />
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

function va_event_editor( $content = '', $editor_id = '', $settings = array() ) {
	va_editor( $content, $editor_id, apply_filters( 'va_event_editor_settings', $settings ) );
}

/**
 * Displays event delete link.
 *
 * @param int $event_id (optional)
 */
function the_event_delete_link( $event_id = 0 ) {
	$event_id = $event_id ? $event_id : get_the_ID();
	va_display_delete_event_button( $event_id );
}
function vantage_map_edit() {
	function map_init( lat, lng ) {

		map_initialized = true;

		var markers_opts = [
			{
				"lat" : lat,
				"lng" : lng,
				'draggable' : true,
			}
		];

		jQuery('#event-map').appthemes_map({
			zoom : 15,
			center_lat : lat,
			center_lng : lng,
			markers: markers_opts,
			auto_zoom: false,
			marker_drag_end: function( marker_key, lat, lng ) {

				jQuery('input[name="lat"]').val( lat );
				jQuery('input[name="lng"]').val( lng );

				update_position( lat, lng , marker_key );
				geocode_lat_lng( lat, lng );
			}
		});

		var address = jQuery('#event-address').val();
		var lat = jQuery('input[name="lat"]').val();
		var lng = jQuery('input[name="lng"]').val();

		if ( address != '' && ( lat == 0 && lng == 0 ) )
			update_map(jQuery.noop);
	}

	function geocode_lat_lng(lat, lng) {

		jQuery.getJSON( ajaxurl, {
			action: 'vantage_single_event_geocode',
			lat: lat,
			lng: lng
		}, function(response) {

			if( response.address ) {
				jQuery('#event-address').val( response.address );
			}
		} );
	}

	function update_position( lat, lng, marker_key ) {

		if ( !map_initialized ) {
			return map_init( lat, lng );
		}

		var marker_update_opts = {
			marker_key: marker_key,
			lat: lat,
			lng: lng
		};

		jQuery('#event-map').appthemes_map('update_marker_position', marker_update_opts );
	}

	jQuery('#event-address').keydown(function(e) {
		if (e.keyCode == 13) {
			jQuery('#event-find-on-map').click();
			e.preventDefault();
		}
	});

	jQuery('#event-find-on-map').click(function(ev) {

		jQuery.getJSON( ajaxurl, {
			action: 'vantage_single_event_geocode',
			address: jQuery('#event-address').val(),
		}, function(response) {
			if ( response.formatted_address ) {
				jQuery('#event-address').val( response.formatted_address );
			}

			if ( response.coords.lat && response.coords.lng ) {
				jQuery('input[name="lat"]').val(response.coords.lat);
				jQuery('input[name="lng"]').val(response.coords.lng);

				update_position( response.coords.lat, response.coords.lng, 0 );
			}
		} );

	});

	var map_initialized = false;
	var address = jQuery('#event-address').val();
	var lat = jQuery('input[name="lat"]').val();
	var lng = jQuery('input[name="lng"]').val();

	if ( address != '' && ( lat == 0 && lng == 0 ) )
		update_map(jQuery.noop);

	if ( lat != 0 && lng != 0 )
		update_position( lat, lng, 0 );

	function update_map( callback ) {
		if ( typeof ajaxurl === 'undefined' ) {
			return setTimeout('update_map( callback )', 500);
		}

		if ( !map_initialized ) {
			var lat = jQuery('input[name="lat"]').val();
			var lng = jQuery('input[name="lng"]').val();
			return map_init( lat, lng );
		}

		jQuery.getJSON( ajaxurl, {
			action: 'vantage_single_event_geocode',
			address: jQuery('#event-address').val(),
		}, function(response) {
			if( response.address ) {
				jQuery('#event-address').val( response.address );
				jQuery('input[name="lat"]').val( response.coords.lat );
				jQuery('input[name="lng"]').val( response.coords.lng );
				update_position( response.coords.lat, response.coords.lng, 0 );
			}
		} );

	}

}

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

		jQuery('#listing-map').appthemes_map({
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

		var address = jQuery('#listing-address').val();
		var lat = jQuery('input[name="lat"]').val();
		var lng = jQuery('input[name="lng"]').val();

		if ( address != '' && ( lat == 0 && lng == 0 ) )
			update_map(jQuery.noop);
	}

	function geocode_lat_lng(lat, lng) {

		jQuery.getJSON( ajaxurl, {
			action: 'vantage_single_listing_geocode',
			lat: lat,
			lng: lng
		}, function(response) {

			if( response.address ) {
				jQuery('#listing-address').val( response.address );
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

		jQuery('#listing-map').appthemes_map('update_marker_position', marker_update_opts );
	}

	jQuery('#listing-address').keydown(function(e) {
		if (e.keyCode == 13) {
			jQuery('#listing-find-on-map').click();
			e.preventDefault();
		}
	});

	jQuery('#listing-find-on-map').click(function(ev) {

		jQuery.getJSON( ajaxurl, {
			action: 'vantage_single_listing_geocode',
			address: jQuery('#listing-address').val(),
		}, function(response) {
			if ( response.formatted_address ) {
				jQuery('#listing-address').val( response.formatted_address );
			}

			if ( response.coords.lat && response.coords.lng ) {
				jQuery('input[name="lat"]').val(response.coords.lat);
				jQuery('input[name="lng"]').val(response.coords.lng);

				update_position( response.coords.lat, response.coords.lng, 0 );
			}
		} );

	});

	var map_initialized = false;
	var address = jQuery('#listing-address').val();
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
			action: 'vantage_single_listing_geocode',
			address: jQuery('#listing-address').val(),
		}, function(response) {
			if( response.address ) {
				jQuery('#listing-address').val( response.address );
				jQuery('input[name="lat"]').val( response.coords.lat );
				jQuery('input[name="lng"]').val( response.coords.lng );
				update_position( response.coords.lat, response.coords.lng, 0 );
			}
		} );

	}

}

function quickEditListing() {

	if(typeof inlineEditPost === 'undefined') return;

	var _edit = inlineEditPost.edit;
	inlineEditPost.edit = function( id ) {

		var args = [].slice.call( arguments );
		_edit.apply( this, args );

		if ( typeof( id ) == 'object' ) {
			id = this.getId( id );
		}

		if ( this.type == 'post' ) {
			var editRow = jQuery( '#edit-' + id ), postRow = jQuery( '#post-'+id );

			// get the existing values
			var listing_claimable = ( 1 == jQuery( 'input[name="listing_claimable['+id+']"]', postRow ).val() ? true : false );

			// set the values in the quick-editor
			jQuery( ':input[name="listing_claimable"]', editRow ).attr( 'checked', listing_claimable );
		}
	};
}

// Ensure inlineEditPost.edit isn't patched until it's defined
if ( typeof inlineEditPost !== 'undefined' ) {
	quickEditListing();
} else {
	jQuery( quickEditListing );
}
